<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Imports\Concerns\LoadsExistingImportState;
use App\Imports\Concerns\TracksImportTouchedFactures;
use App\Models\{Facture, ImportBatch};
use App\Services\ImportRowHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Maatwebsite\Excel\Concerns\{
    Importable,
    SkipsErrors,
    SkipsFailures,
    SkipsOnError,
    SkipsOnFailure,
    ToCollection,
    WithChunkReading,
    WithCustomValueBinder,
    WithHeadingRow,
};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class PrestationsImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData, LoadsExistingImportState, TracksImportTouchedFactures;

    private int $localCount = 0;
    private int $skippedCount = 0;
    private array $missingFactures = [];
    private array $factureIdCache = [];

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        $numeros = $rows
            ->map(fn ($row) => trim((string) $this->cellValue($row, 'facture', '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->preloadFactureIds($numeros, $this->factureIdCache);

        $pairs = [];

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            $article = trim((string) $this->cellValue($row, 'article', ''));

            if ($numero !== '' && $article !== '' && isset($this->factureIdCache[$numero])) {
                $pairs[] = [
                    'facture_id' => $this->factureIdCache[$numero],
                    'key' => $article,
                ];
            }
        }

        $existingHashes = $this->existingHashesForFacturePairs('prestations', 'article', $pairs);

        $now = now()->toDateTimeString();
        $upserts = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;
        $missingRows = 0;
        $changedRows = 0;
        $touchedFactureIds = [];

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            $article = trim((string) $this->cellValue($row, 'article', ''));

            if ($numero === '') {
                continue;
            }

            $factureId = $this->factureIdCache[$numero] ?? null;

            if (! $factureId) {
                $this->missingFactures[$numero] = true;
                $this->skippedCount++;
                $missingRows++;
                continue;
            }

            $rowHash = ImportRowHasher::hash($this->batch->type, $row);
            $existingKey = $factureId.'|'.$article;
            $existingHash = $existingHashes[$existingKey] ?? null;

            if (
                ! $this->batch->force_import
                && $existingHash
                && $existingHash !== '__EXISTS_NO_HASH__'
                && hash_equals((string) $existingHash, $rowHash)
            ) {
                $unchangedRows++;
                continue;
            }

            $record = [
                'facture_id' => $factureId,
                'article' => $article,
                'libelle' => trim((string) $this->cellValue($row, 'libelle', '')),
                'quantite' => $this->parseAmount($this->cellValue($row, 'quantite', '0')),
                'prix_unitaire' => $this->parseAmount($this->cellValue($row, 'prix', '0')),
                'taux_ht' => $this->parseAmount($this->cellValue($row, 'taux_ht', '0')),
                'total_ht' => $this->parseAmount($this->cellValue($row, 'total_ht', '0')),
                'total_tva' => $this->parseAmount($this->cellValue($row, 'total_tva', '0')),
                'total_ttc' => $this->parseAmount($this->cellValue($row, 'total_ttc', '0')),
                'row_hash' => $rowHash,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $upserts[$factureId.'|'.$article] = $record;
            $existingHash ? $updatedRows++ : $createdRows++;
            $changedRows++;
            $touchedFactureIds[$factureId] = $factureId;

            $this->localCount++;
        }

        if ($upserts !== []) {
            DB::table('prestations')->upsert(
                array_values($upserts),
                ['facture_id', 'article'],
                [
                    'libelle',
                    'quantite',
                    'prix_unitaire',
                    'taux_ht',
                    'total_ht',
                    'total_tva',
                    'total_ttc',
                    'row_hash',
                    'updated_at',
                ]
            );

            $this->recordTouchedFactureIds($touchedFactureIds);
        }

        $processedRows = $changedRows + $unchangedRows + $missingRows;
        Cache::increment("import_batch_{$this->batch->id}", $processedRows);
        $this->batch->increment('processed_rows', $processedRows);
        $this->batch->increment('created_rows', $createdRows);
        $this->batch->increment('updated_rows', $updatedRows);
        $this->batch->increment('skipped_rows', $unchangedRows);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function logMissingFactures(): void
    {
        if ($this->missingFactures === []) {
            return;
        }

        $count = count($this->missingFactures);
        $samples = array_slice(array_keys($this->missingFactures), 0, 20);

        Log::channel('imports')->warning("PrestationsImport [batch #{$this->batch->id}] missing invoices", [
            'samples' => $samples,
            'total' => $count,
        ]);

        $this->batch->increment('failed_rows', $this->skippedCount);
    }
}
