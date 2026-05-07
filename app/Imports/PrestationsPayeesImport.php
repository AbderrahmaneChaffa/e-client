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

class PrestationsPayeesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

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

        $newNumeros = array_diff($numeros, array_keys($this->factureIdCache));

        if ($newNumeros !== []) {
            Facture::whereIn('numero_facture', $newNumeros)
                ->where('annuler', 0)
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
        $chunkInserted = 0;
        $chunkSkipped = 0;
        $chunkUnchanged = 0;
        $createdRows = 0;
        $updatedRows = 0;

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

            if ($existingHash) {
                $updates[] = $record;
                $updatedRows++;
            } else {
                $records[$factureId.'|'.$article] = $record;
                $createdRows++;
            }

            $chunkInserted++;
        }

        if ($records !== []) {
            DB::transaction(function () use ($records) {
                foreach (array_chunk(array_values($records), 2000) as $chunk) {
                    DB::table('prestations')->insert($chunk);
                }
            });
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
