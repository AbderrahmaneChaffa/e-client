<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\ImportVerification;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ImportVerificationService
{
    private const TVA_RATE = 1.19;
    private const MONEY_TOLERANCE = 0.01;
    private const PRESTATION_TOLERANCE = 1.00;

    private const SEVERITY_WEIGHT = [
        'info' => 0,
        'warning' => 1,
        'critical' => 2,
    ];

    /** @var array<int, array{severity:string, flags:array<int, array{code:string, label:string, severity:string}>}> */
    private array $invoiceIssues = [];

    /**
     * Run all import verification rules and persist aggregate results.
     *
     * @return array{critical:int, warning:int, info:int, affected_invoices:int, score:int}
     */
    public function verify(?ImportBatch $batch = null, array $relatedBatchIds = []): array
    {
        $startedAt = microtime(true);
        $this->invoiceIssues = [];

        ImportVerification::query()
            ->when($batch, fn ($query) => $query->where('import_batch_id', $batch->id), fn ($query) => $query->whereNull('import_batch_id'))
            ->delete();

        $this->resetInvoiceStatuses();

        $results = [
            $this->verifyVatCoherence($batch, $relatedBatchIds),
            $this->verifyPaymentBalance($batch, $relatedBatchIds),
            $this->verifyNegativeAmounts($batch, $relatedBatchIds),
            $this->verifyOverpaidInvoices($batch, $relatedBatchIds),
            $this->verifyPrestationsTotal($batch, $relatedBatchIds),
            $this->verifyDuplicatePayments($batch, $relatedBatchIds),
            $this->verifyOrphanInvoices($batch, $relatedBatchIds),
            $this->verifyFutureInvoices($batch, $relatedBatchIds),
        ];

        $this->flushInvoiceStatuses();

        $summary = [
            'critical' => collect($results)->where('severity', 'critical')->sum('affected_count'),
            'warning' => collect($results)->where('severity', 'warning')->sum('affected_count'),
            'info' => collect($results)->where('severity', 'info')->sum('affected_count'),
            'affected_invoices' => count($this->invoiceIssues),
            'score' => $this->healthScore(),
        ];

        Log::channel('imports')->info('Import verification completed', [
            'import_batch_id' => $batch?->id,
            'related_batch_ids' => $relatedBatchIds,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'summary' => $summary,
        ]);

        if ($summary['critical'] > 0) {
            Log::channel('imports')->critical('Critical import verification issues detected', [
                'import_batch_id' => $batch?->id,
                'critical_count' => $summary['critical'],
            ]);
        }

        return $summary;
    }

    public function latestHealthSummary(): array
    {
        if (
            ! Schema::hasTable('factures')
            || ! Schema::hasColumn('factures', 'verification_status')
            || ! Schema::hasTable('import_verifications')
        ) {
            $total = Schema::hasTable('factures') ? (int) DB::table('factures')->count() : 0;

            return [
                'score' => 100,
                'total_factures' => $total,
                'ok' => $total,
                'warning' => 0,
                'critical' => 0,
                'tva_anomalies' => 0,
                'overpaid_invoices' => 0,
                'payment_mismatches' => 0,
                'total_detected_delta' => 0.0,
                'last_verified_at' => null,
            ];
        }

        $total = (int) DB::table('factures')->whereNull('deleted_at')->count();
        $critical = (int) DB::table('factures')->whereNull('deleted_at')->where('verification_status', 'critical')->count();
        $warning = (int) DB::table('factures')->whereNull('deleted_at')->where('verification_status', 'warning')->count();
        $ok = max(0, $total - $critical - $warning);
        $score = $total > 0 ? max(0, (int) round(($ok / $total) * 100)) : 100;

        $latestByRule = ImportVerification::query()
            ->select('rule_code', 'affected_count', 'details', 'created_at')
            ->latest('id')
            ->get()
            ->unique('rule_code')
            ->keyBy('rule_code');

        return [
            'score' => $score,
            'total_factures' => $total,
            'ok' => $ok,
            'warning' => $warning,
            'critical' => $critical,
            'tva_anomalies' => (int) ($latestByRule['tva_coherence']->affected_count ?? 0),
            'overpaid_invoices' => (int) ($latestByRule['overpaid_invoices']->affected_count ?? 0),
            'payment_mismatches' => (int) ($latestByRule['payment_balance']->affected_count ?? 0),
            'total_detected_delta' => (float) ($latestByRule['tva_coherence']->details['total_delta'] ?? 0),
            'last_verified_at' => optional($latestByRule->first())->created_at,
        ];
    }

    private function verifyVatCoherence(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $query = DB::table('factures')
            ->whereNull('deleted_at')
            ->whereRaw('ABS(total_ttc - ROUND(total_ht * ?, 2)) > ?', [self::TVA_RATE, self::MONEY_TOLERANCE]);

        return $this->persistInvoiceRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            query: $query,
            ruleCode: 'tva_coherence',
            severity: 'critical',
            label: 'TVA incorrecte',
            details: [
                'expected_formula' => 'total_ttc = total_ht * 1.19',
                'tolerance' => self::MONEY_TOLERANCE,
                'total_delta' => (float) ((clone $query)->selectRaw('SUM(ABS(total_ttc - ROUND(total_ht * ?, 2))) AS delta', [self::TVA_RATE])->value('delta') ?? 0),
            ],
        );
    }

    private function verifyPaymentBalance(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $query = $this->facturesWithPaidTotals()
            ->whereRaw('ABS(factures.reste_a_payer - (factures.total_ttc - COALESCE(payments.paid_total, 0))) > ?', [self::MONEY_TOLERANCE]);

        return $this->persistInvoiceRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            query: $query,
            ruleCode: 'payment_balance',
            severity: 'critical',
            label: 'Paiements incoherents',
            details: [
                'expected_formula' => 'reste_a_payer = total_ttc - SUM(paiements.montant)',
                'tolerance' => self::MONEY_TOLERANCE,
                'total_delta' => (float) ((clone $query)->selectRaw('SUM(ABS(factures.reste_a_payer - (factures.total_ttc - COALESCE(payments.paid_total, 0)))) AS delta')->value('delta') ?? 0),
            ],
        );
    }

    private function verifyNegativeAmounts(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $factureIds = DB::table('factures')
            ->whereNull('deleted_at')
            ->where(function (Builder $query) {
                $query->where('total_ht', '<', 0)
                    ->orWhere('total_tva', '<', 0)
                    ->orWhere('total_ttc', '<', 0)
                    ->orWhere('reste_a_payer', '<', 0);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $paiementInvoiceIds = DB::table('paiements')
            ->join('factures', 'factures.id', '=', 'paiements.facture_id')
            ->whereNull('factures.deleted_at')
            ->where('paiements.montant', '<', 0)
            ->distinct()
            ->pluck('factures.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $prestationInvoiceIds = DB::table('prestations')
            ->join('factures', 'factures.id', '=', 'prestations.facture_id')
            ->whereNull('factures.deleted_at')
            ->where(function (Builder $query) {
                $query->where('prestations.total_ht', '<', 0)
                    ->orWhere('prestations.total_tva', '<', 0)
                    ->orWhere('prestations.total_ttc', '<', 0)
                    ->orWhere('prestations.prix_unitaire', '<', 0)
                    ->orWhere('prestations.quantite', '<', 0);
            })
            ->distinct()
            ->pluck('factures.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_values(array_unique([...$factureIds, ...$paiementInvoiceIds, ...$prestationInvoiceIds]));
        $this->flagInvoices($ids, 'negative_amounts', 'Montant negatif', 'critical');

        return $this->persistRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            ruleCode: 'negative_amounts',
            severity: 'critical',
            affectedCount: count($ids),
            sampleIds: array_slice($ids, 0, 50),
            details: [
                'factures' => count($factureIds),
                'paiements' => count($paiementInvoiceIds),
                'prestations' => count($prestationInvoiceIds),
            ],
        );
    }

    private function verifyOverpaidInvoices(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $query = $this->facturesWithPaidTotals()
            ->whereRaw('COALESCE(payments.paid_total, 0) - factures.total_ttc > ?', [self::MONEY_TOLERANCE]);

        return $this->persistInvoiceRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            query: $query,
            ruleCode: 'overpaid_invoices',
            severity: 'critical',
            label: 'Sur-payee',
            details: [
                'expected_formula' => 'SUM(paiements.montant) <= total_ttc',
                'tolerance' => self::MONEY_TOLERANCE,
                'total_delta' => (float) ((clone $query)->selectRaw('SUM(COALESCE(payments.paid_total, 0) - factures.total_ttc) AS delta')->value('delta') ?? 0),
            ],
        );
    }

    private function verifyPrestationsTotal(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $prestations = DB::table('prestations')
            ->select('facture_id', DB::raw('SUM(total_ttc) AS prestation_total'))
            ->groupBy('facture_id');

        $query = DB::table('factures')
            ->joinSub($prestations, 'prestations_sum', 'prestations_sum.facture_id', '=', 'factures.id')
            ->whereNull('factures.deleted_at')
            ->whereRaw('ABS(prestations_sum.prestation_total - factures.total_ttc) > ?', [self::PRESTATION_TOLERANCE]);

        return $this->persistInvoiceRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            query: $query,
            ruleCode: 'prestations_total',
            severity: 'warning',
            label: 'Prestations incoherentes',
            details: [
                'expected_formula' => 'SUM(prestations.total_ttc) ~= facture.total_ttc',
                'tolerance' => self::PRESTATION_TOLERANCE,
                'note' => 'Tolerance plus large car certains fichiers BIG peuvent contenir des prestations partielles.',
            ],
        );
    }

    private function verifyDuplicatePayments(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $duplicates = DB::table('paiements')
            ->select('recu', 'date_paiement', 'montant')
            ->whereNotNull('recu')
            ->where('recu', '<>', '')
            ->groupBy('recu', 'date_paiement', 'montant')
            ->havingRaw('COUNT(*) > 1');

        $ids = DB::table('paiements')
            ->joinSub($duplicates, 'duplicates', function ($join) {
                $join->on('duplicates.recu', '=', 'paiements.recu')
                    ->on('duplicates.date_paiement', '=', 'paiements.date_paiement')
                    ->on('duplicates.montant', '=', 'paiements.montant');
            })
            ->join('factures', 'factures.id', '=', 'paiements.facture_id')
            ->whereNull('factures.deleted_at')
            ->distinct()
            ->pluck('factures.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->flagInvoices($ids, 'duplicate_payments', 'Paiement en doublon', 'warning');

        return $this->persistRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            ruleCode: 'duplicate_payments',
            severity: 'warning',
            affectedCount: count($ids),
            sampleIds: array_slice($ids, 0, 50),
            details: ['match' => ['recu', 'date_paiement', 'montant']],
        );
    }

    private function verifyOrphanInvoices(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $query = DB::table('factures')
            ->leftJoin('clients', 'clients.id', '=', 'factures.client_id')
            ->whereNull('factures.deleted_at')
            ->where(function (Builder $query) {
                $query->whereNull('factures.client_id')->orWhereNull('clients.id');
            });

        return $this->persistInvoiceRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            query: $query,
            ruleCode: 'orphan_invoices',
            severity: 'critical',
            label: 'Client introuvable',
            details: ['expected' => 'Chaque facture doit pointer vers un client existant.'],
        );
    }

    private function verifyFutureInvoices(?ImportBatch $batch, array $relatedBatchIds): array
    {
        $query = DB::table('factures')
            ->whereNull('deleted_at')
            ->whereDate('date_facture', '>', now()->toDateString());

        return $this->persistInvoiceRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            query: $query,
            ruleCode: 'future_invoice_date',
            severity: 'warning',
            label: 'Date future',
            details: ['today' => now()->toDateString()],
        );
    }

    private function facturesWithPaidTotals(): Builder
    {
        $payments = DB::table('paiements')
            ->select('facture_id', DB::raw('COALESCE(SUM(montant), 0) AS paid_total'))
            ->groupBy('facture_id');

        return DB::table('factures')
            ->leftJoinSub($payments, 'payments', 'payments.facture_id', '=', 'factures.id')
            ->whereNull('factures.deleted_at')
            ->where('factures.annuler', 0);
    }

    private function persistInvoiceRule(
        ?ImportBatch $batch,
        array $relatedBatchIds,
        Builder $query,
        string $ruleCode,
        string $severity,
        string $label,
        array $details = [],
    ): array {
        $ids = (clone $query)
            ->distinct()
            ->pluck('factures.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->flagInvoices($ids, $ruleCode, $label, $severity);

        return $this->persistRule(
            batch: $batch,
            relatedBatchIds: $relatedBatchIds,
            ruleCode: $ruleCode,
            severity: $severity,
            affectedCount: count($ids),
            sampleIds: array_slice($ids, 0, 50),
            details: $details,
        );
    }

    private function persistRule(
        ?ImportBatch $batch,
        array $relatedBatchIds,
        string $ruleCode,
        string $severity,
        int $affectedCount,
        array $sampleIds,
        array $details = [],
    ): array {
        $payload = [
            'rule_code' => $ruleCode,
            'severity' => $severity,
            'affected_count' => $affectedCount,
            'sample_ids' => array_values($sampleIds),
            'details' => array_filter([
                ...$details,
                'related_batch_ids' => $relatedBatchIds ?: null,
            ], fn ($value) => $value !== null),
        ];

        if ($affectedCount > 0) {
            ImportVerification::create([
                'import_batch_id' => $batch?->id,
                ...$payload,
            ]);
        }

        return $payload;
    }

    private function resetInvoiceStatuses(): void
    {
        DB::table('factures')
            ->whereNull('deleted_at')
            ->update([
                'verification_status' => 'ok',
                'verification_flags' => null,
                'last_verified_at' => now(),
            ]);
    }

    private function flagInvoices(array $ids, string $ruleCode, string $label, string $severity): void
    {
        foreach (array_values(array_unique($ids)) as $id) {
            $current = $this->invoiceIssues[$id] ?? ['severity' => 'info', 'flags' => []];
            $current['severity'] = $this->maxSeverity($current['severity'], $severity);
            $current['flags'][$ruleCode] = [
                'code' => $ruleCode,
                'label' => $label,
                'severity' => $severity,
            ];

            $this->invoiceIssues[$id] = $current;
        }
    }

    private function flushInvoiceStatuses(): void
    {
        $groups = [];

        foreach ($this->invoiceIssues as $id => $issue) {
            $flags = array_values($issue['flags']);
            usort($flags, fn ($a, $b) => strcmp($a['code'], $b['code']));

            $json = json_encode($flags, JSON_UNESCAPED_SLASHES);
            $status = $issue['severity'];
            $groups[$status][$json][] = $id;
        }

        foreach ($groups as $status => $jsonGroups) {
            foreach ($jsonGroups as $json => $ids) {
                foreach (array_chunk($ids, 1000) as $chunk) {
                    DB::table('factures')
                        ->whereIn('id', $chunk)
                        ->update([
                            'verification_status' => $status,
                            'verification_flags' => $json,
                            'last_verified_at' => now(),
                        ]);
                }
            }
        }
    }

    private function maxSeverity(string $left, string $right): string
    {
        return self::SEVERITY_WEIGHT[$right] > self::SEVERITY_WEIGHT[$left] ? $right : $left;
    }

    private function healthScore(): int
    {
        $total = (int) DB::table('factures')->whereNull('deleted_at')->count();

        if ($total === 0) {
            return 100;
        }

        $critical = collect($this->invoiceIssues)->where('severity', 'critical')->count();
        $warning = collect($this->invoiceIssues)->where('severity', 'warning')->count();
        $penalty = ($critical * 2) + $warning;

        return max(0, (int) round(100 - (($penalty / $total) * 100)));
    }
}
