<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Imports\Concerns\ResolvesInvoiceImportRelations;
use App\Imports\Concerns\TracksImportTouchedFactures;
use App\Models\Facture;
use App\Models\ImportBatch;
use App\Services\ImportDeltaService;
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

class FacturesPayeesImport extends StringValueBinder implements
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
    private int $createdCount = 0;
    private int $updatedCount = 0;
    private int $skippedCount = 0;

    private const DIFF_COLUMNS = [
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
    ];

    private const DIFF_LABELS = [
        'client_id' => 'Client',
        'escale_id' => 'Escale',
        'date_facture' => 'Date facture',
        'bordereau' => 'Bordereau',
        'description' => 'Description',
        'pour' => 'Pour',
        'total_ht' => 'Total HT',
        'total_tva' => 'TVA',
        'total_ttc' => 'Total TTC',
        'reste_a_payer' => 'Reste a payer',
        'devise' => 'Devise',
        'taux_devise' => 'Taux devise',
        'mode_paiement' => 'Mode paiement',
        'annuler' => 'Annulation',
    ];

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        $deltaService = app(ImportDeltaService::class);
        $now = now()->toDateTimeString();
        $userId = $this->batch->created_by;

        $numeros = $rows
            ->map(fn ($row) => trim((string) $this->cellValue($row, 'facture', '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingFactures = Facture::whereIn('numero_facture', $numeros)
            ->get([
                'id',
                'numero_facture',
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
            ])
            ->keyBy('numero_facture');

        $existingHashes = $existingFactures
            ->map(fn (Facture $facture) => $facture->row_hash ?? '__EXISTS_NO_HASH__')
            ->all();

        $this->resolveInvoiceImportRelations($rows);

        $records = [];
        $changedNumeros = [];
        $seenNumeros = [];
        $diffs = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));

            if ($numero === '') {
                continue;
            }

            $seenNumeros[$numero] = true;
            $rowHash = ImportRowHasher::hash($this->batch->type, $row);
            $existingHash = $existingHashes[$numero] ?? null;
            $existingFacture = $existingFactures->get($numero);

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

            $existingHash ? $updatedRows++ : $createdRows++;
            $existingHash ? $this->updatedCount++ : $this->createdCount++;
            $changedNumeros[$numero] = true;
            $annuler = $this->hasCellHeading($row, 'annule')
                ? $this->parseBooleanFlag($this->cellValue($row, 'annule', '0'))
                : (bool) ($existingFacture?->annuler ?? false);

            $record = [
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

            $recordDiff = $deltaService->diffForRecord(
                entityType: 'facture',
                entityKey: $numero,
                factureId: $existingFacture?->id,
                existing: $existingFacture,
                newRecord: $record,
                columns: self::DIFF_COLUMNS,
                labels: self::DIFF_LABELS,
                context: ['numero_facture' => $numero],
            );

            if ($recordDiff) {
                $diffs[] = $recordDiff;
            }

            array_push($diffs, ...$deltaService->factureInconsistencies($numero, $existingFacture?->id, $record));
            $records[] = $record;
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

        if ($seenNumeros !== []) {
            $factureIdsByNumero = Facture::whereIn('numero_facture', array_keys($seenNumeros))
                ->pluck('id', 'numero_facture')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->recordTouchedFactureIds(array_values($factureIdsByNumero));

            $diffs = $deltaService->attachFactureIds($diffs, $factureIdsByNumero);
            $deltaService->record($this->batch, $diffs);
            $diffFactureIds = collect($diffs)->pluck('facture_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();
            $resolvedIds = array_values(array_diff(array_values($factureIdsByNumero), $diffFactureIds));
            $deltaService->clearResolvedFactures($this->batch, $resolvedIds, 'facture');
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
