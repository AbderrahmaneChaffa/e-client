<?php

namespace App\Services\Notifications;

use App\Models\Facture;
use App\Models\ImportBatch;
use App\Models\ImportDiff;
use App\Models\ImportVerification;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use App\Notifications\ClientInvoiceNotification;
use App\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AlertNotificationService
{
    public function __construct(private readonly DeduplicatedNotificationDispatcher $dispatcher)
    {
    }

    public function notifyQueuedImport(ImportBatch $batch): void
    {
        $dedupeKey = "admin:import:queued:{$batch->id}";

        $this->notifyAdmins([
            'alert_type' => 'import_queued',
            'title' => 'Import en attente',
            'description' => $this->typeLabel($batch->type).' attend son traitement: '.$batch->original_filename,
            'status' => 'pending',
            'severity' => 'info',
            'icon' => 'clock',
            'color' => 'info',
            'url' => $this->url('admin.imports.show', ['batch' => $batch->id]),
            'action_label' => 'Voir import',
            'meta' => [
                'batch_id' => $batch->id,
                'type' => $batch->type,
                'filename' => $batch->original_filename,
            ],
        ], $dedupeKey);
    }

    /**
     * @param  iterable<ImportBatch>  $batches
     */
    public function notifyQueuedImports(iterable $batches): void
    {
        foreach ($batches as $batch) {
            $this->notifyQueuedImport($batch);
        }
    }

    public function notifyImportCompleted(ImportBatch $batch): void
    {
        $batch = $batch->fresh() ?? $batch;
        $diffCount = array_sum(array_map('intval', (array) data_get($batch->metadata, 'import_diffs', [])));
        $description = $this->typeLabel($batch->type).' termine: '.
            number_format((int) $batch->processed_rows, 0, ',', ' ').
            ' / '.number_format((int) $batch->total_rows, 0, ',', ' ').
            ' lignes traitees.';

        if ($diffCount > 0) {
            $description .= ' '.$diffCount.' ecart(s) detecte(s).';
        }

        $this->notifyAdmins([
            'alert_type' => 'import_completed',
            'title' => 'Import termine',
            'description' => $description,
            'status' => 'completed',
            'severity' => $diffCount > 0 ? 'warning' : 'success',
            'icon' => $diffCount > 0 ? 'circle-alert' : 'circle-check',
            'color' => $diffCount > 0 ? 'warning' : 'success',
            'url' => $this->url('admin.imports.show', ['batch' => $batch->id]),
            'action_label' => 'Voir details',
            'meta' => [
                'batch_id' => $batch->id,
                'type' => $batch->type,
                'processed_rows' => (int) $batch->processed_rows,
                'failed_rows' => (int) $batch->failed_rows,
                'diff_count' => $diffCount,
            ],
        ], "admin:import:completed:{$batch->id}:{$batch->processed_rows}:{$diffCount}");

        $this->notifyAdminImportDiffs($batch);
        $this->notifyClientAlertsForBatch($batch);
    }

    public function notifyImportFailed(ImportBatch $batch, ?Throwable $exception = null): void
    {
        $batch = $batch->fresh() ?? $batch;
        $message = $exception?->getMessage() ?: (string) data_get($batch->error_summary, 'message', 'Erreur import inconnue.');

        $this->notifyAdmins([
            'alert_type' => 'import_failed',
            'title' => 'Import en echec',
            'description' => $this->typeLabel($batch->type).' a echoue: '.$message,
            'status' => 'failed',
            'severity' => 'critical',
            'icon' => 'circle-alert',
            'color' => 'danger',
            'url' => $this->url('admin.imports.show', ['batch' => $batch->id]),
            'action_label' => 'Voir erreur',
            'meta' => [
                'batch_id' => $batch->id,
                'type' => $batch->type,
                'error' => $message,
            ],
        ], "admin:import:failed:{$batch->id}:".sha1($message));
    }

    public function notifyGlobalVerificationQueued(): void
    {
        $this->notifyAdmins([
            'alert_type' => 'integrity_check_queued',
            'title' => 'Controle integrite en file',
            'description' => 'Une verification globale des donnees attend son execution.',
            'status' => 'queued',
            'severity' => 'info',
            'icon' => 'clock',
            'color' => 'info',
            'url' => $this->url('admin.imports.index'),
            'action_label' => 'Voir imports',
        ], 'admin:verification:global:queued:'.now()->format('YmdHi'));
    }

    /**
     * @param  array<int>  $relatedBatchIds
     * @param  array<string,mixed>  $summary
     */
    public function notifyVerificationCompleted(?ImportBatch $batch, array $relatedBatchIds, array $summary): void
    {
        $critical = (int) ($summary['critical'] ?? 0);
        $warning = (int) ($summary['warning'] ?? 0);
        $affectedInvoices = (int) ($summary['affected_invoices'] ?? 0);
        $severity = $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'success');
        $scope = $batch?->id ? "batch:{$batch->id}" : 'global';

        $this->notifyAdmins([
            'alert_type' => 'integrity_check_completed',
            'title' => 'Controle integrite termine',
            'description' => $affectedInvoices > 0
                ? "{$affectedInvoices} facture(s) avec alerte(s): {$critical} critique(s), {$warning} avertissement(s)."
                : 'Aucune incoherence bloquante detectee.',
            'status' => 'completed',
            'severity' => $severity,
            'icon' => $severity === 'success' ? 'circle-check' : 'shield-check',
            'color' => $severity === 'critical' ? 'danger' : $severity,
            'url' => $batch
                ? $this->url('admin.imports.show', ['batch' => $batch->id])
                : $this->url('admin.factures.index', [], ['verification' => 'anomalies']),
            'action_label' => 'Voir alertes',
            'meta' => [
                'batch_id' => $batch?->id,
                'related_batch_ids' => $relatedBatchIds,
                'summary' => $summary,
            ],
        ], "admin:verification:completed:{$scope}:{$critical}:{$warning}:{$affectedInvoices}");

        $this->notifyVerificationRules($batch, $relatedBatchIds);
    }

    /**
     * @param  array<int>  $relatedBatchIds
     */
    public function notifyVerificationFailed(?ImportBatch $batch, array $relatedBatchIds, Throwable $exception): void
    {
        $scope = $batch?->id ? "batch:{$batch->id}" : 'global';

        $this->notifyAdmins([
            'alert_type' => 'integrity_check_failed',
            'title' => 'Controle integrite en echec',
            'description' => $exception->getMessage(),
            'status' => 'failed',
            'severity' => 'critical',
            'icon' => 'circle-alert',
            'color' => 'danger',
            'url' => $batch
                ? $this->url('admin.imports.show', ['batch' => $batch->id])
                : $this->url('admin.imports.index'),
            'action_label' => 'Voir imports',
            'meta' => [
                'batch_id' => $batch?->id,
                'related_batch_ids' => $relatedBatchIds,
                'error' => $exception->getMessage(),
            ],
        ], "admin:verification:failed:{$scope}:".sha1($exception->getMessage()));
    }

    public function notifyClientInvoiceStatus(Facture $facture): void
    {
        $this->notifyClientUnpaidInvoice($facture);
        $this->notifyClientOverdueInvoice($facture);
    }

    public function notifyClientAlertsForBatch(ImportBatch $batch): void
    {
        if (! Schema::hasTable('import_batch_factures')) {
            return;
        }

        DB::table('import_batch_factures')
            ->where('import_batch_id', $batch->id)
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use ($batch): void {
                $factureIds = $rows->pluck('facture_id')->filter()->unique()->values()->all();

                if ($factureIds === []) {
                    return;
                }

                $diffsByFacture = Schema::hasTable('import_diffs')
                    ? ImportDiff::query()
                        ->where('import_batch_id', $batch->id)
                        ->whereIn('facture_id', $factureIds)
                        ->get()
                        ->groupBy('facture_id')
                    : collect();

                Facture::query()
                    ->whereIn('id', $factureIds)
                    ->orderBy('id')
                    ->get()
                    ->each(function (Facture $facture) use ($batch, $diffsByFacture): void {
                        $diffs = $diffsByFacture->get($facture->id, collect());

                        $this->notifyClientInvoiceStatus($facture);
                        $this->notifyClientInvoiceUpdated($facture, $batch, $diffs);
                        $this->notifyClientPaymentsReceived($facture, $batch, $diffs);
                    });
            });
    }

    public function notifyClientOpenInvoiceAlerts(?int $limit = null): void
    {
        $query = Facture::query()
            ->active()
            ->unpaid()
            ->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit)->get()->each(fn (Facture $facture) => $this->notifyClientInvoiceStatus($facture));

            return;
        }

        $query->chunkById(500, function (Collection $factures): void {
            $factures->each(fn (Facture $facture) => $this->notifyClientInvoiceStatus($facture));
        });
    }

    public function notifyAdminOutstandingAlerts(): void
    {
        ImportBatch::query()
            ->where('status', 'pending')
            ->latest('id')
            ->limit(50)
            ->get()
            ->each(fn (ImportBatch $batch) => $this->notifyQueuedImport($batch));

        ImportBatch::query()
            ->where('status', 'failed')
            ->latest('id')
            ->limit(50)
            ->get()
            ->each(fn (ImportBatch $batch) => $this->notifyImportFailed($batch));

        if (Schema::hasTable('import_diffs')) {
            ImportBatch::query()
                ->whereHas('diffs')
                ->latest('id')
                ->limit(50)
                ->get()
                ->each(fn (ImportBatch $batch) => $this->notifyAdminImportDiffs($batch));
        }
    }

    private function notifyAdminImportDiffs(ImportBatch $batch): void
    {
        if (! Schema::hasTable('import_diffs')) {
            return;
        }

        $base = ImportDiff::query()->where('import_batch_id', $batch->id);
        $total = (clone $base)->count();

        if ($total === 0) {
            return;
        }

        $critical = (clone $base)->where('severity', 'critical')->count();
        $paymentIssues = (clone $base)->whereIn('entity_type', ['paiement', 'solde'])->count();
        $severity = $critical > 0 ? 'critical' : 'warning';

        $this->notifyAdmins([
            'alert_type' => 'data_gap',
            'title' => 'Ecarts import detectes',
            'description' => "{$total} ecart(s) detecte(s) dans {$this->typeLabel($batch->type)}.",
            'status' => 'open',
            'severity' => $severity,
            'icon' => 'circle-alert',
            'color' => $severity === 'critical' ? 'danger' : 'warning',
            'url' => $this->url('admin.imports.show', ['batch' => $batch->id]),
            'action_label' => 'Voir ecarts',
            'meta' => [
                'batch_id' => $batch->id,
                'diff_count' => $total,
                'critical_count' => $critical,
            ],
        ], "admin:import:diffs:{$batch->id}:{$total}:{$critical}");

        if ($paymentIssues > 0) {
            $this->notifyAdmins([
                'alert_type' => 'payment_inconsistency',
                'title' => 'Incoherences paiements',
                'description' => "{$paymentIssues} ecart(s) paiement ou solde detecte(s) dans l'import.",
                'status' => 'open',
                'severity' => $severity,
                'icon' => 'credit-card',
                'color' => $severity === 'critical' ? 'danger' : 'warning',
                'url' => $this->url('admin.imports.show', ['batch' => $batch->id]),
                'action_label' => 'Voir paiements',
                'meta' => [
                    'batch_id' => $batch->id,
                    'payment_issues' => $paymentIssues,
                ],
            ], "admin:import:payment-issues:{$batch->id}:{$paymentIssues}");
        }
    }

    /**
     * @param  array<int>  $relatedBatchIds
     */
    private function notifyVerificationRules(?ImportBatch $batch, array $relatedBatchIds): void
    {
        if (! Schema::hasTable('import_verifications')) {
            return;
        }

        ImportVerification::query()
            ->when($batch, fn ($query) => $query->where('import_batch_id', $batch->id), fn ($query) => $query->whereNull('import_batch_id'))
            ->where('affected_count', '>', 0)
            ->latest('id')
            ->get()
            ->unique('rule_code')
            ->each(function (ImportVerification $rule) use ($batch, $relatedBatchIds): void {
                $sampleId = collect($rule->sample_ids ?? [])->filter()->first();
                $alertType = $this->verificationAlertType($rule->rule_code);
                $severity = $rule->severity === 'critical' ? 'critical' : 'warning';
                $scope = $batch?->id ? "batch:{$batch->id}" : 'global';

                $this->notifyAdmins([
                    'alert_type' => $alertType,
                    'title' => $this->verificationTitle($rule->rule_code),
                    'description' => number_format((int) $rule->affected_count, 0, ',', ' ').
                        ' facture(s) concernee(s).',
                    'status' => $rule->severity,
                    'severity' => $severity,
                    'icon' => $this->verificationIcon($alertType),
                    'color' => $severity === 'critical' ? 'danger' : 'warning',
                    'url' => $sampleId
                        ? $this->url('admin.factures.show', ['facture' => $sampleId])
                        : ($batch ? $this->url('admin.imports.show', ['batch' => $batch->id]) : $this->url('admin.factures.index', [], ['verification' => 'anomalies'])),
                    'action_label' => 'Voir details',
                    'meta' => [
                        'batch_id' => $batch?->id,
                        'related_batch_ids' => $relatedBatchIds,
                        'rule_code' => $rule->rule_code,
                        'affected_count' => (int) $rule->affected_count,
                        'sample_ids' => $rule->sample_ids ?? [],
                        'details' => $rule->details ?? [],
                    ],
                ], "admin:verification:rule:{$scope}:{$rule->rule_code}:{$rule->affected_count}:".sha1(json_encode($rule->sample_ids ?? [])));
            });
    }

    /**
     * @param  Collection<int, ImportDiff>  $diffs
     */
    private function notifyClientInvoiceUpdated(Facture $facture, ImportBatch $batch, Collection $diffs): void
    {
        $invoiceDiffs = $diffs
            ->reject(fn (ImportDiff $diff) => $diff->entity_type === 'paiement')
            ->values();

        if ($invoiceDiffs->isEmpty()) {
            return;
        }

        $labels = $invoiceDiffs
            ->pluck('label')
            ->filter()
            ->unique()
            ->take(3)
            ->implode(', ');

        $dedupeKey = "client:invoice:updated:{$batch->id}:{$facture->id}";

        $this->notifyClientUsers((int) $facture->client_id, [
            'alert_type' => 'invoice_update',
            'title' => "Facture {$facture->numero_facture} mise a jour",
            'description' => $labels !== '' ? $labels : 'Des informations de facture ont ete actualisees.',
            'status' => 'updated',
            'severity' => $invoiceDiffs->contains('severity', 'critical') ? 'critical' : 'info',
            'icon' => 'file-text',
            'color' => $invoiceDiffs->contains('severity', 'critical') ? 'danger' : 'info',
            'url' => $this->url('client.factures.show', ['facture' => $facture->id]),
            'action_label' => 'Voir facture',
            'meta' => [
                'facture_id' => $facture->id,
                'numero_facture' => $facture->numero_facture,
                'batch_id' => $batch->id,
                'diff_count' => $invoiceDiffs->count(),
            ],
        ], $dedupeKey);
    }

    /**
     * @param  Collection<int, ImportDiff>  $diffs
     */
    private function notifyClientPaymentsReceived(Facture $facture, ImportBatch $batch, Collection $diffs): void
    {
        $diffs
            ->where('entity_type', 'paiement')
            ->where('change_type', 'new')
            ->each(function (ImportDiff $diff) use ($facture, $batch): void {
                $recu = $diff->entity_key ?: data_get($diff->context, 'recu', 'paiement');
                $amount = collect($diff->differences ?? [])
                    ->first(fn (array $difference) => ($difference['field'] ?? null) === 'montant')['new'] ?? null;
                $description = 'Paiement recu pour la facture '.$facture->numero_facture;

                if ($amount) {
                    $description .= ' - montant: '.$amount.' DA';
                }

                $this->notifyClientUsers((int) $facture->client_id, [
                    'alert_type' => 'payment_received',
                    'title' => 'Nouveau paiement recu',
                    'description' => $description,
                    'status' => 'received',
                    'severity' => 'success',
                    'icon' => 'credit-card',
                    'color' => 'success',
                    'url' => $this->url('client.factures.show', ['facture' => $facture->id]),
                    'action_label' => 'Voir facture',
                    'meta' => [
                        'facture_id' => $facture->id,
                        'numero_facture' => $facture->numero_facture,
                        'recu' => $recu,
                        'batch_id' => $batch->id,
                    ],
                ], "client:payment:received:{$facture->id}:{$recu}");
            });
    }

    private function notifyClientUnpaidInvoice(Facture $facture): void
    {
        if ((bool) $facture->annuler || (float) $facture->reste_a_payer <= 0) {
            return;
        }

        $this->notifyClientUsers((int) $facture->client_id, [
            'alert_type' => 'unpaid_invoice',
            'title' => "Facture impayee {$facture->numero_facture}",
            'description' => 'Reste a payer: '.$this->money((float) $facture->reste_a_payer),
            'status' => 'unpaid',
            'severity' => 'warning',
            'icon' => 'file-text',
            'color' => 'warning',
            'url' => $this->url('client.factures.show', ['facture' => $facture->id]),
            'action_label' => 'Regler facture',
            'meta' => [
                'facture_id' => $facture->id,
                'numero_facture' => $facture->numero_facture,
                'reste_a_payer' => (float) $facture->reste_a_payer,
            ],
        ], "client:invoice:unpaid:{$facture->id}");
    }

    private function notifyClientOverdueInvoice(Facture $facture): void
    {
        if (
            (bool) $facture->annuler
            || (float) $facture->reste_a_payer <= 0
            || ! $facture->date_echeance
            || ! $facture->date_echeance->lt(today())
        ) {
            return;
        }

        $this->notifyClientUsers((int) $facture->client_id, [
            'alert_type' => 'overdue_invoice',
            'title' => "Facture en retard {$facture->numero_facture}",
            'description' => 'Echeance depassee depuis le '.$facture->date_echeance->format('d/m/Y').
                '. Reste a payer: '.$this->money((float) $facture->reste_a_payer),
            'status' => 'overdue',
            'severity' => 'critical',
            'icon' => 'circle-alert',
            'color' => 'danger',
            'url' => $this->url('client.factures.show', ['facture' => $facture->id]),
            'action_label' => 'Voir facture',
            'meta' => [
                'facture_id' => $facture->id,
                'numero_facture' => $facture->numero_facture,
                'date_echeance' => $facture->date_echeance->toDateString(),
                'reste_a_payer' => (float) $facture->reste_a_payer,
            ],
        ], "client:invoice:overdue:{$facture->id}:{$facture->date_echeance->toDateString()}");
    }

    private function notifyAdmins(array $payload, string $dedupeKey): int
    {
        $sent = 0;
        $payload['dedupe_key'] = $dedupeKey;

        User::query()
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::SUPERADMIN->value])
            ->select(['id', 'name', 'email', 'role', 'client_id'])
            ->chunkById(100, function (Collection $admins) use ($payload, $dedupeKey, &$sent): void {
                foreach ($admins as $admin) {
                    $sent += $this->dispatcher->send(
                        $admin,
                        fn () => new AdminAlertNotification($payload),
                        $dedupeKey,
                    ) ? 1 : 0;
                }
            });

        return $sent;
    }

    private function notifyClientUsers(int $clientId, array $payload, string $dedupeKey): int
    {
        if ($clientId <= 0) {
            return 0;
        }

        $sent = 0;
        $payload['dedupe_key'] = $dedupeKey;

        User::query()
            ->where('role', UserRole::CLIENT->value)
            ->where('client_id', $clientId)
            ->select(['id', 'name', 'email', 'role', 'client_id'])
            ->chunkById(100, function (Collection $users) use ($payload, $dedupeKey, &$sent): void {
                foreach ($users as $user) {
                    $sent += $this->dispatcher->send(
                        $user,
                        fn () => new ClientInvoiceNotification($payload),
                        $dedupeKey,
                    ) ? 1 : 0;
                }
            });

        return $sent;
    }

    private function url(string $route, array $parameters = [], array $query = []): string
    {
        if (! Route::has($route)) {
            return Route::has('dashboard') ? route('dashboard') : url('/');
        }

        $url = route($route, $parameters);

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $url;
    }

    private function typeLabel(string $type): string
    {
        return [
            'factures' => 'Factures',
            'prestations' => 'Prestations',
            'paiements' => 'Paiements',
            'factures_payees' => 'Factures payees',
            'prestations_payees' => 'Prestations payees',
        ][$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    private function verificationAlertType(string $ruleCode): string
    {
        return [
            'prestations_total' => 'prestations_total_mismatch',
            'payment_balance' => 'payment_inconsistency',
            'duplicate_payments' => 'payment_inconsistency',
            'overpaid_invoices' => 'payment_inconsistency',
        ][$ruleCode] ?? 'integrity_alert';
    }

    private function verificationTitle(string $ruleCode): string
    {
        return [
            'tva_coherence' => 'TVA incoherente',
            'payment_balance' => 'Paiements incoherents',
            'negative_amounts' => 'Montants negatifs',
            'overpaid_invoices' => 'Factures sur-payees',
            'prestations_total' => 'Total prestations different',
            'duplicate_payments' => 'Paiements en doublon',
            'orphan_invoices' => 'Factures sans client',
            'future_invoice_date' => 'Dates de facture futures',
        ][$ruleCode] ?? 'Alerte integrite';
    }

    private function verificationIcon(string $alertType): string
    {
        return [
            'prestations_total_mismatch' => 'file-text',
            'payment_inconsistency' => 'credit-card',
        ][$alertType] ?? 'shield-check';
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' DA';
    }
}
