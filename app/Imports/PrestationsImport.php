<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
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
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

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

        $newNumeros = array_diff($numeros, array_keys($this->factureIdCache));

        if ($newNumeros !== []) {
            Facture::whereIn('numero_facture', $newNumeros)
                ->select('id', 'numero_facture')
                ->get()
                ->each(fn ($facture) => $this->factureIdCache[$facture->numero_facture] = $facture->id);
        }

        $factureIds = [];
        $articles = [];

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            $article = trim((string) $this->cellValue($row, 'article', ''));

            if ($numero !== '' && $article !== '' && isset($this->factureIdCache[$numero])) {
                $factureIds[] = $this->factureIdCache[$numero];
                $articles[] = $article;
            }
        }

        $existingHashes = [];
        if ($factureIds !== [] && $articles !== []) {
            DB::table('prestations')
                ->whereIn('facture_id', array_values(array_unique($factureIds)))
                ->whereIn('article', array_values(array_unique($articles)))
                ->select('facture_id', 'article', 'row_hash')
                ->get()
                ->each(function ($row) use (&$existingHashes) {
                    $existingHashes[$row->facture_id.'|'.$row->article] = $row->row_hash ?? '__EXISTS_NO_HASH__';
                });
        }

        $now = now()->toDateTimeString();
        $records = [];
        $updates = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;
        $missingRows = 0;

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

            if ($existingHash) {
                $updates[] = $record;
                $updatedRows++;
            } else {
                $records[$factureId.'|'.$article] = $record;
                $createdRows++;
            }

            $this->localCount++;
        }

        if ($records !== []) {
            DB::table('prestations')->insert(array_values($records));
        }

        foreach ($updates as $record) {
            DB::table('prestations')
                ->where('facture_id', $record['facture_id'])
                ->where('article', $record['article'])
                ->update([
                    'libelle' => $record['libelle'],
                    'quantite' => $record['quantite'],
                    'prix_unitaire' => $record['prix_unitaire'],
                    'taux_ht' => $record['taux_ht'],
                    'total_ht' => $record['total_ht'],
                    'total_tva' => $record['total_tva'],
                    'total_ttc' => $record['total_ttc'],
                    'row_hash' => $record['row_hash'],
                    'updated_at' => $record['updated_at'],
                ]);
        }

        $processedRows = count($records) + count($updates) + $unchangedRows + $missingRows;
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
