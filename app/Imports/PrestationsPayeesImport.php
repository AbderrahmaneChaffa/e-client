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

class PrestationsPayeesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData, LoadsExistingImportState, TracksImportTouchedFactures;

    private int $totalProcessed = 0;
    private int $totalSkipped = 0;
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

        $this->preloadFactureIds($numeros, $this->factureIdCache, onlyActive: true);

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
        $chunkInserted = 0;
        $chunkSkipped = 0;
        $chunkUnchanged = 0;
        $createdRows = 0;
        $updatedRows = 0;
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
                $chunkSkipped++;
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
                $chunkUnchanged++;
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
            $touchedFactureIds[$factureId] = $factureId;

            $chunkInserted++;
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

        $processedRows = $chunkInserted + $chunkSkipped + $chunkUnchanged;
        Cache::increment("import_batch_{$this->batch->id}", $processedRows);
        $this->batch->increment('processed_rows', $processedRows);
        $this->batch->increment('created_rows', $createdRows);
        $this->batch->increment('updated_rows', $updatedRows);
        $this->batch->increment('skipped_rows', $chunkUnchanged);

        if ($chunkSkipped > 0) {
            $this->batch->increment('failed_rows', $chunkSkipped);
        }

        $this->totalProcessed += $chunkInserted;
        $this->totalSkipped += $chunkSkipped;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function logMissingFactures(): void
    {
        if ($this->missingFactures === []) {
            return;
        }

        Log::channel('imports')->warning("PrestationsPayeesImport [batch #{$this->batch->id}] missing invoices", [
            'count' => count($this->missingFactures),
            'skipped' => $this->totalSkipped,
            'samples' => array_slice(array_keys($this->missingFactures), 0, 20),
        ]);
    }

    public function getTotalProcessed(): int
    {
        return $this->totalProcessed;
    }

    public function getTotalSkipped(): int
    {
        return $this->totalSkipped;
    }
}
