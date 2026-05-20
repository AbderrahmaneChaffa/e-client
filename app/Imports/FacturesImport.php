<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Imports\Concerns\ResolvesInvoiceImportRelations;
use App\Imports\Concerns\TracksImportTouchedFactures;
use App\Models\Facture;
use App\Models\ImportBatch;
use App\Services\ImportRowHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class FacturesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable;
    use SkipsErrors;
    use SkipsFailures;
    use ParsesExcelData;
    use ResolvesInvoiceImportRelations;
    use TracksImportTouchedFactures;

    private int $localCount = 0;
    private int $skippedCount = 0;

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        $now = now()->toDateTimeString();
        $userId = $this->batch->created_by;
        $records = [];
        $changedNumeros = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;

        $numeros = $rows
            ->map(fn ($row) => trim((string) $this->cellValue($row, 'facture', '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingFactures = Facture::whereIn('numero_facture', $numeros)
            ->get(['numero_facture', 'row_hash', 'annuler'])
            ->keyBy('numero_facture');

        $existingHashes = $existingFactures
            ->map(fn (Facture $facture) => $facture->row_hash ?? '__EXISTS_NO_HASH__')
            ->all();

        $this->resolveInvoiceImportRelations($rows);

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
                $unchangedRows++;
                continue;
            }

            $annuler = $this->hasCellHeading($row, 'annule')
                ? $this->parseBooleanFlag($this->cellValue($row, 'annule', '0'))
                : (bool) ($existingFactures->get($numero)?->annuler ?? false);

            $existingHash ? $updatedRows++ : $createdRows++;
            $changedNumeros[$numero] = true;

            $records[] = [
                'numero_facture' => $numero,
                'client_id' => $this->clientIdForInvoiceImportRow($row),
                'escale_id' => $this->escaleIdForInvoiceImportRow($row),
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
                'annuler' => $annuler ? 1 : 0,
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

            $this->recordTouchedFactureIds(
                Facture::whereIn('numero_facture', array_keys($changedNumeros))->pluck('id')->all()
            );
        }

        $count = count($records) + $unchangedRows;
        Cache::increment("import_batch_{$this->batch->id}", $count);
        $this->batch->increment('processed_rows', $count);
        $this->batch->increment('created_rows', $createdRows);
        $this->batch->increment('updated_rows', $updatedRows);
        $this->batch->increment('skipped_rows', $unchangedRows);

        if ($this->skippedCount > 0) {
            $this->batch->increment('failed_rows', $this->skippedCount);
            $this->skippedCount = 0;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getLocalCount(): int
    {
        return $this->localCount;
    }
}
