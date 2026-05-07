<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Escale, Facture, ImportBatch, Navire};
use App\Services\ImportRowHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};
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

class FacturesPayeesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;
    private int $createdCount = 0;
    private int $updatedCount = 0;
    private int $skippedCount = 0;

    private array $clientCache = [];
    private array $navireCache = [];
    private array $escaleCache = [];

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        $now = now()->toDateTimeString();
        $userId = $this->batch->created_by;

        $numeros = $rows
            ->map(fn ($row) => trim((string) $this->cellValue($row, 'facture', '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingHashes = Facture::whereIn('numero_facture', $numeros)
            ->pluck('row_hash', 'numero_facture')
            ->map(fn ($hash) => $hash ?? '__EXISTS_NO_HASH__')
            ->all();

        $records = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            if ($numero === '') {
                continue;
            }

            $rowHash = ImportRowHasher::hash($this->batch->type, $row);
            $existingHash = $existingHashes[$numero] ?? null;

            if (
                ! $this->batch->force_import
                && $existingHash
                && $existingHash !== '__EXISTS_NO_HASH__'
                && hash_equals((string) $existingHash, $rowHash)
            ) {
                $this->skippedCount++;
                $unchangedRows++;
                continue;
            }

            $clientId = null;
            $codeClient = trim((string) $this->cellValue($row, 'code_client', ''));

            if ($codeClient !== '') {
                if (! isset($this->clientCache[$codeClient])) {
                    $this->clientCache[$codeClient] = Client::firstOrCreate(
                        ['code_client' => $codeClient],
                        [
                            'name' => trim((string) $this->cellValue($row, 'nom_client', '')),
                            'adresse' => trim((string) $this->cellValue($row, 'adresse', '')),
                            'rc' => trim((string) $this->cellValue($row, 'rc', '')),
                            'nis' => trim((string) $this->cellValue($row, 'nis', '')),
                            'ai' => trim((string) $this->cellValue($row, 'ai', '')),
                            'nif' => trim((string) $this->cellValue($row, 'nif', '')),
                        ]
                    );
                }

                $clientId = $this->clientCache[$codeClient]->id;
            }

            $navireNom = trim((string) $this->cellValue($row, 'navire', 'NAVIRE INCONNU'));
            $pavillon = trim((string) $this->cellValue($row, 'pavillon', 'INCONNU'));
            $navireKey = $navireNom.'|'.$pavillon;

            if (! isset($this->navireCache[$navireKey])) {
                $this->navireCache[$navireKey] = Navire::firstOrCreate([
                    'nom' => $navireNom,
                    'pavillon' => $pavillon,
                ]);
            }

            $navire = $this->navireCache[$navireKey];

            if (! isset($this->escaleCache[$navire->id])) {
                $this->escaleCache[$navire->id] = Escale::firstOrCreate(
                    ['navire_id' => $navire->id],
                    [
                        'date_arrivee' => $this->parseDate($this->cellValue($row, 'entree', '')),
                        'date_sortie' => $this->parseDate($this->cellValue($row, 'sortie', '')),
                    ]
                );
            }

            $existingHash ? $updatedRows++ : $createdRows++;
            $existingHash ? $this->updatedCount++ : $this->createdCount++;

            $records[] = [
                'numero_facture' => $numero,
                'client_id' => $clientId,
                'escale_id' => $this->escaleCache[$navire->id]->id,
                'date_facture' => $this->parseDate($this->cellValue($row, 'date', '')),
                'bordereau' => trim((string) $this->cellValue($row, 'bordereau', '')),
                'description' => trim((string) $this->cellValue($row, 'description', '')),
                'pour' => trim((string) $this->cellValue($row, 'pour', '')),
                'total_ht' => $this->parseAmount($this->cellValue($row, 'total_ht', '0')),
                'total_tva' => $this->parseAmount($this->cellValue($row, 'total_tva', '0')),
                'total_ttc' => $this->parseAmount($this->cellValue($row, 'total_ttc', '0')),
                'reste_a_payer' => $this->parseAmount($this->cellValue($row, 'reste', '0')),
                'devise' => trim((string) $this->cellValue($row, 'devise', 'DA')),
                'taux_devise' => $this->parseAmount($this->cellValue($row, 'taux_devise', '1')),
                'mode_paiement' => trim((string) $this->cellValue($row, 'paiement', '')),
                'annuler' => 0,
                'row_hash' => $rowHash,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->localCount++;
        }

        if ($records !== []) {
            DB::table('factures')->upsert(
                $records,
                ['numero_facture'],
                [
                    'client_id',
                    'escale_id',
                    'date_facture',
                    'bordereau',
                    'description',
                    'pour',
                    'total_ht',
                    'total_tva',
                    'total_ttc',
                    'reste_a_payer',
                    'devise',
                    'taux_devise',
                    'mode_paiement',
                    'annuler',
                    'row_hash',
                    'updated_at',
                ]
            );
        }

        $count = count($records) + $unchangedRows;
        Cache::increment("import_batch_{$this->batch->id}", $count);
        $this->batch->increment('processed_rows', $count);
        $this->batch->increment('created_rows', $createdRows);
        $this->batch->increment('updated_rows', $updatedRows);
        $this->batch->increment('skipped_rows', $unchangedRows);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getStats(): array
    {
        return [
            'processed' => $this->localCount,
            'created' => $this->createdCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
        ];
    }

    public function getLocalCount(): int
    {
        return $this->localCount;
    }
}
