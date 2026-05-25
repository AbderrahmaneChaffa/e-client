<?php

namespace App\Services;

use App\Models\ImportBatch;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportDeltaService
{
    private const CHANGE_PRIORITY = [
        'new' => 1,
        'modified' => 2,
        'missing' => 3,
        'inconsistent' => 4,
    ];

    private const SEVERITY_PRIORITY = [
        'info' => 0,
        'warning' => 1,
        'critical' => 2,
    ];

    private const MONEY_COLUMNS = [
        'total_ht',
        'total_tva',
        'total_ttc',
        'reste_a_payer',
        'montant',
        'prix_unitaire',
        'taux_ht',
        'taux_tva',
    ];

    private const QUANTITY_COLUMNS = ['quantite'];
    private const RATE_COLUMNS = ['taux_devise'];
    private const DATE_COLUMNS = ['date_facture', 'date_paiement'];
    private const BOOLEAN_COLUMNS = ['annuler'];

    /**
     * @param array<int,string> $columns
     * @param array<string,string> $labels
     * @param array<string,mixed> $context
     */
    public function diffForRecord(
        string $entityType,
        string $entityKey,
        ?int $factureId,
        mixed $existing,
        array $newRecord,
        array $columns,
        array $labels = [],
        array $context = [],
        ?string $newLabel = null,
        ?string $modifiedLabel = null,
    ): ?array {
        $context = ['entity_key' => $entityKey, ...$context];

        if ($existing === null) {
            $differences = [];

            foreach ($columns as $column) {
                $newValue = $newRecord[$column] ?? null;

                if ($this->isBlankValue($newValue)) {
                    continue;
                }

                $differences[] = [
                    'field' => $column,
                    'label' => $labels[$column] ?? $this->fieldLabel($column),
                    'old' => null,
                    'new' => $this->displayValue($column, $newValue),
                    'type' => 'new',
                ];
            }

            return $this->delta(
                factureId: $factureId,
                entityType: $entityType,
                entityKey: $entityKey,
                changeType: 'new',
                severity: 'info',
                label: $newLabel ?? $this->defaultNewLabel($entityType),
                differences: $differences,
                context: $context,
            );
        }

        $differences = [];
        $hasMissingValue = false;

        foreach ($columns as $column) {
            $oldValue = $this->valueFrom($existing, $column);
            $newValue = $newRecord[$column] ?? null;

            if ($this->sameValue($column, $oldValue, $newValue)) {
                continue;
            }

            $isMissing = $this->isBlankValue($newValue) && ! $this->isBlankValue($oldValue);
            $hasMissingValue = $hasMissingValue || $isMissing;

            $differences[] = [
                'field' => $column,
                'label' => $labels[$column] ?? $this->fieldLabel($column),
                'old' => $this->displayValue($column, $oldValue),
                'new' => $this->displayValue($column, $newValue),
                'type' => $isMissing ? 'missing' : 'modified',
            ];
        }

        if ($differences === []) {
            return null;
        }

        return $this->delta(
            factureId: $factureId,
            entityType: $entityType,
            entityKey: $entityKey,
            changeType: $hasMissingValue ? 'missing' : 'modified',
            severity: 'warning',
            label: $hasMissingValue ? 'Donnees manquantes dans le fichier' : ($modifiedLabel ?? $this->defaultModifiedLabel($entityType)),
            differences: $differences,
            context: $context,
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function factureInconsistencies(string $numero, ?int $factureId, array $record): array
    {
        $issues = [];
        $totalHt = (float) ($record['total_ht'] ?? 0);
        $totalTva = (float) ($record['total_tva'] ?? 0);
        $totalTtc = (float) ($record['total_ttc'] ?? 0);
        $reste = (float) ($record['reste_a_payer'] ?? 0);

        if (abs(($totalHt + $totalTva) - $totalTtc) > 0.01) {
            $issues[] = $this->delta(
                factureId: $factureId,
                entityType: 'facture',
                entityKey: $numero,
                changeType: 'inconsistent',
                severity: 'critical',
                label: 'Total facture incoherent',
                differences: [[
                    'field' => 'total_ttc',
                    'label' => 'Total TTC',
                    'old' => number_format($totalHt + $totalTva, 2, ',', ' '),
                    'new' => number_format($totalTtc, 2, ',', ' '),
                    'type' => 'inconsistent',
                ]],
                context: ['expected_formula' => 'total_ht + total_tva = total_ttc', 'numero_facture' => $numero],
            );
        }

        if ($reste - $totalTtc > 0.01) {
            $issues[] = $this->delta(
                factureId: $factureId,
                entityType: 'facture',
                entityKey: $numero,
                changeType: 'inconsistent',
                severity: 'warning',
                label: 'Reste a payer superieur au TTC',
                differences: [[
                    'field' => 'reste_a_payer',
                    'label' => 'Reste a payer',
                    'old' => '<= '.number_format($totalTtc, 2, ',', ' '),
                    'new' => number_format($reste, 2, ',', ' '),
                    'type' => 'inconsistent',
                ]],
                context: ['numero_facture' => $numero],
            );
        }

        foreach (['total_ht', 'total_tva', 'total_ttc', 'reste_a_payer'] as $column) {
            if ((float) ($record[$column] ?? 0) >= 0) {
                continue;
            }

            $issues[] = $this->delta(
                factureId: $factureId,
                entityType: 'facture',
                entityKey: $numero,
                changeType: 'inconsistent',
                severity: 'critical',
                label: 'Montant facture negatif',
                differences: [[
                    'field' => $column,
                    'label' => $this->fieldLabel($column),
                    'old' => '>= 0',
                    'new' => $this->displayValue($column, $record[$column]),
                    'type' => 'inconsistent',
                ]],
                context: ['numero_facture' => $numero],
            );
        }

        return $issues;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function lineTotalInconsistencies(string $entityType, string $entityKey, ?int $factureId, array $record): array
    {
        $issues = [];
        $totalHt = (float) ($record['total_ht'] ?? 0);
        $totalTva = (float) ($record['total_tva'] ?? 0);
        $totalTtc = (float) ($record['total_ttc'] ?? 0);

        if (abs(($totalHt + $totalTva) - $totalTtc) > 0.01) {
            $issues[] = $this->delta(
                factureId: $factureId,
                entityType: $entityType,
                entityKey: $entityKey,
                changeType: 'inconsistent',
                severity: 'warning',
                label: 'Ligne importee incoherente',
                differences: [[
                    'field' => 'total_ttc',
                    'label' => 'Total TTC',
                    'old' => number_format($totalHt + $totalTva, 2, ',', ' '),
                    'new' => number_format($totalTtc, 2, ',', ' '),
                    'type' => 'inconsistent',
                ]],
                context: ['expected_formula' => 'total_ht + total_tva = total_ttc'],
            );
        }

        return $issues;
    }

    /**
     * @param array<int,array<string,mixed>> $diffs
     */
    public function record(ImportBatch $batch, array $diffs): void
    {
        if ($diffs === [] || ! Schema::hasTable('import_diffs')) {
            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];
        $factureIds = [];

        foreach ($diffs as $diff) {
            $factureId = isset($diff['facture_id']) ? (int) $diff['facture_id'] : null;

            if ($factureId) {
                $factureIds[$factureId] = $factureId;
            }

            $rows[] = [
                'import_batch_id' => $batch->id,
                'facture_id' => $factureId ?: null,
                'entity_type' => substr((string) ($diff['entity_type'] ?? 'facture'), 0, 32),
                'entity_key' => substr((string) ($diff['entity_key'] ?? ''), 0, 255),
                'change_type' => substr((string) ($diff['change_type'] ?? 'modified'), 0, 32),
                'severity' => substr((string) ($diff['severity'] ?? 'warning'), 0, 20),
                'label' => substr((string) ($diff['label'] ?? 'Ecart detecte'), 0, 255),
                'differences' => $this->json($diff['differences'] ?? []),
                'context' => $this->json($diff['context'] ?? []),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('import_diffs')->insert($chunk);
        }

        $this->refreshFactureSummaries($batch, array_values($factureIds));
        $this->incrementBatchDeltaCounters($batch, $diffs);
    }

    /**
     * @param array<int,int|string|null> $factureIds
     */
    public function clearResolvedFactures(ImportBatch $batch, array $factureIds, string $entityType): void
    {
        if ($factureIds === [] || ! $this->hasFactureSummaryColumns()) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $factureIds))));

        foreach (array_chunk($ids, 1000) as $chunk) {
            DB::table('factures')
                ->whereIn('id', $chunk)
                ->where('last_import_diff_type', $entityType)
                ->update([
                    'import_diff_status' => null,
                    'last_import_diff_type' => null,
                    'import_diff_count' => 0,
                    'import_diff_summary' => null,
                    'last_import_batch_id' => $batch->id,
                    'last_import_diff_at' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function resetBatch(ImportBatch $batch): void
    {
        if (Schema::hasTable('import_diffs')) {
            DB::table('import_diffs')->where('import_batch_id', $batch->id)->delete();
        }

        $metadata = $this->batchMetadata($batch);
        $metadata['import_diffs'] = [
            'new' => 0,
            'modified' => 0,
            'missing' => 0,
            'inconsistent' => 0,
        ];
        unset($metadata['missing_existing_factures']);
        DB::table('import_batches')->where('id', $batch->id)->update([
            'metadata' => $this->json($metadata),
        ]);

        if (! $this->hasFactureSummaryColumns()) {
            return;
        }

        DB::table('factures')
            ->where('last_import_batch_id', $batch->id)
            ->update([
                'import_diff_status' => null,
                'last_import_diff_type' => null,
                'import_diff_count' => 0,
                'import_diff_summary' => null,
                'last_import_batch_id' => null,
                'last_import_diff_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function markMissingFacturesFromSnapshot(ImportBatch $batch): int
    {
        if (
            $batch->type !== 'factures'
            || ! Schema::hasTable('import_batch_factures')
            || ! Schema::hasTable('import_diffs')
        ) {
            return 0;
        }

        $count = 0;
        $lastId = 0;

        do {
            $rows = DB::table('factures')
                ->leftJoin('import_batch_factures', function ($join) use ($batch) {
                    $join->on('import_batch_factures.facture_id', '=', 'factures.id')
                        ->where('import_batch_factures.import_batch_id', '=', $batch->id);
                })
                ->whereNull('import_batch_factures.id')
                ->whereNull('factures.deleted_at')
                ->where('factures.id', '>', $lastId)
                ->orderBy('factures.id')
                ->limit(1000)
                ->get(['factures.id', 'factures.numero_facture']);

            if ($rows->isEmpty()) {
                break;
            }

            $diffs = [];

            foreach ($rows as $row) {
                $lastId = (int) $row->id;
                $count++;
                $diffs[] = $this->delta(
                    factureId: (int) $row->id,
                    entityType: 'facture',
                    entityKey: (string) $row->numero_facture,
                    changeType: 'missing',
                    severity: 'warning',
                    label: 'Absente du dernier fichier factures',
                    differences: [[
                        'field' => 'numero_facture',
                        'label' => 'Facture',
                        'old' => (string) $row->numero_facture,
                        'new' => null,
                        'type' => 'missing',
                    ]],
                    context: ['source' => 'database_not_in_import_file'],
                );
            }

            $this->record($batch, $diffs);
        } while (true);

        if ($count > 0) {
            $metadata = $this->batchMetadata($batch);
            $metadata['missing_existing_factures'] = $count;
            DB::table('import_batches')->where('id', $batch->id)->update([
                'metadata' => $this->json($metadata),
            ]);
        }

        return $count;
    }

    /**
     * @param array<int,array<string,mixed>> $diffs
     * @param array<string,int> $factureIdsByNumero
     * @return array<int,array<string,mixed>>
     */
    public function attachFactureIds(array $diffs, array $factureIdsByNumero): array
    {
        foreach ($diffs as &$diff) {
            if (! empty($diff['facture_id'])) {
                continue;
            }

            $numero = (string) data_get($diff, 'context.numero_facture', data_get($diff, 'entity_key', ''));

            if ($numero !== '' && isset($factureIdsByNumero[$numero])) {
                $diff['facture_id'] = (int) $factureIdsByNumero[$numero];
            }
        }

        return $diffs;
    }

    /**
     * @param array<int,mixed> $differences
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function delta(
        ?int $factureId,
        string $entityType,
        string $entityKey,
        string $changeType,
        string $severity,
        string $label,
        array $differences = [],
        array $context = [],
    ): array {
        return [
            'facture_id' => $factureId,
            'entity_type' => $entityType,
            'entity_key' => $entityKey,
            'change_type' => $changeType,
            'severity' => $severity,
            'label' => $label,
            'differences' => $differences,
            'context' => $context,
        ];
    }

    /**
     * @param array<int,int> $factureIds
     */
    private function refreshFactureSummaries(ImportBatch $batch, array $factureIds): void
    {
        if ($factureIds === [] || ! $this->hasFactureSummaryColumns()) {
            return;
        }

        foreach (array_chunk(array_values(array_unique($factureIds)), 500) as $chunk) {
            $diffs = DB::table('import_diffs')
                ->where('import_batch_id', $batch->id)
                ->whereIn('facture_id', $chunk)
                ->orderBy('facture_id')
                ->orderByDesc('id')
                ->get();

            $updates = [];

            foreach ($diffs->groupBy('facture_id') as $factureId => $items) {
                $top = $items->sortByDesc(fn ($item) => self::CHANGE_PRIORITY[$item->change_type] ?? 0)->first();
                $summary = $items->take(5)->map(function ($item) {
                    $differences = json_decode((string) $item->differences, true) ?: [];

                    return [
                        'change_type' => $item->change_type,
                        'entity_type' => $item->entity_type,
                        'label' => $item->label,
                        'severity' => $item->severity,
                        'fields' => array_slice(array_values(array_filter(array_map(
                            fn ($diff) => $diff['label'] ?? $diff['field'] ?? null,
                            $differences,
                        ))), 0, 4),
                    ];
                })->values()->all();

                $updates[] = [
                    'id' => (int) $factureId,
                    'import_diff_status' => $top?->change_type,
                    'last_import_diff_type' => $top?->entity_type,
                    'import_diff_count' => $items->count(),
                    'import_diff_summary' => $this->json($summary),
                    'last_import_batch_id' => $batch->id,
                    'last_import_diff_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->updateExistingFactureSummaries($updates);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $updates
     */
    private function updateExistingFactureSummaries(array $updates): void
    {
        if ($updates === []) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            foreach ($updates as $update) {
                $id = $update['id'];
                unset($update['id']);

                DB::table('factures')->where('id', $id)->update($update);
            }

            return;
        }

        $columns = [
            'import_diff_status',
            'last_import_diff_type',
            'import_diff_count',
            'import_diff_summary',
            'last_import_batch_id',
            'last_import_diff_at',
            'updated_at',
        ];

        foreach (array_chunk($updates, 200) as $chunk) {
            $bindings = [];
            $setClauses = [];

            foreach ($columns as $column) {
                $caseSql = "`{$column}` = CASE `id`";

                foreach ($chunk as $update) {
                    $caseSql .= ' WHEN ? THEN ?';
                    $bindings[] = (int) $update['id'];
                    $bindings[] = $update[$column] ?? null;
                }

                $caseSql .= " ELSE `{$column}` END";
                $setClauses[] = $caseSql;
            }

            $ids = array_map(fn (array $update) => (int) $update['id'], $chunk);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            DB::update(
                'UPDATE `factures` SET '.implode(', ', $setClauses)." WHERE `id` IN ({$placeholders})",
                [...$bindings, ...$ids],
            );
        }
    }

    /**
     * @param array<int,array<string,mixed>> $diffs
     */
    private function incrementBatchDeltaCounters(ImportBatch $batch, array $diffs): void
    {
        $metadata = $this->batchMetadata($batch);
        $current = $metadata['import_diffs'] ?? [
            'new' => 0,
            'modified' => 0,
            'missing' => 0,
            'inconsistent' => 0,
        ];

        foreach ($diffs as $diff) {
            $type = (string) ($diff['change_type'] ?? 'modified');
            $current[$type] = (int) ($current[$type] ?? 0) + 1;
        }

        $metadata['import_diffs'] = $current;
        DB::table('import_batches')->where('id', $batch->id)->update([
            'metadata' => $this->json($metadata),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function batchMetadata(ImportBatch $batch): array
    {
        $metadata = DB::table('import_batches')->where('id', $batch->id)->value('metadata');

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata) && $metadata !== '') {
            return json_decode($metadata, true) ?: [];
        }

        return $batch->metadata ?? [];
    }

    private function sameValue(string $column, mixed $oldValue, mixed $newValue): bool
    {
        return $this->canonicalValue($column, $oldValue) === $this->canonicalValue($column, $newValue);
    }

    private function canonicalValue(string $column, mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            $value = $value->toDateString();
        }

        if (in_array($column, self::DATE_COLUMNS, true)) {
            if ($this->isBlankValue($value)) {
                return '';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
                return trim((string) $value);
            }
        }

        if (in_array($column, self::MONEY_COLUMNS, true)) {
            return number_format((float) ($value ?: 0), 2, '.', '');
        }

        if (in_array($column, self::RATE_COLUMNS, true)) {
            return number_format((float) ($value ?: 0), 4, '.', '');
        }

        if (in_array($column, self::QUANTITY_COLUMNS, true)) {
            return number_format((float) ($value ?: 0), 4, '.', '');
        }

        if (in_array($column, self::BOOLEAN_COLUMNS, true)) {
            return (string) (int) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return Str::lower(Str::ascii(preg_replace('/\s+/u', ' ', trim((string) $value)) ?? ''));
    }

    private function displayValue(string $column, mixed $value): ?string
    {
        if ($this->isBlankValue($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y');
        }

        if (in_array($column, self::DATE_COLUMNS, true)) {
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return trim((string) $value);
            }
        }

        if (in_array($column, [...self::MONEY_COLUMNS, ...self::RATE_COLUMNS, ...self::QUANTITY_COLUMNS], true)) {
            return number_format((float) $value, in_array($column, self::RATE_COLUMNS, true) ? 4 : 2, ',', ' ');
        }

        if (in_array($column, self::BOOLEAN_COLUMNS, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : 'Non';
        }

        return trim((string) $value);
    }

    private function valueFrom(mixed $source, string $key): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }

        if (is_object($source)) {
            return $source->{$key} ?? null;
        }

        return null;
    }

    private function isBlankValue(mixed $value): bool
    {
        if ($value instanceof CarbonInterface) {
            return false;
        }

        return $value === null || trim((string) $value) === '';
    }

    private function fieldLabel(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }

    private function defaultNewLabel(string $entityType): string
    {
        return [
            'facture' => 'Nouvelle facture',
            'prestation' => 'Nouvelle prestation',
            'paiement' => 'Nouveau paiement',
            'solde' => 'Nouveau solde',
        ][$entityType] ?? 'Nouvelle entree';
    }

    private function defaultModifiedLabel(string $entityType): string
    {
        return [
            'facture' => 'Facture modifiee',
            'prestation' => 'Prestation modifiee',
            'paiement' => 'Paiement modifie',
            'solde' => 'Solde modifie',
        ][$entityType] ?? 'Valeurs modifiees';
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private function hasFactureSummaryColumns(): bool
    {
        return Schema::hasTable('factures')
            && Schema::hasColumn('factures', 'import_diff_status')
            && Schema::hasColumn('factures', 'import_diff_summary')
            && Schema::hasColumn('factures', 'last_import_batch_id');
    }
}
