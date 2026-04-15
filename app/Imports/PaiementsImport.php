<?php


namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Facture, ImportBatch, Paiement};
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\{
    Importable,
    SkipsErrors,
    SkipsFailures,
    SkipsOnError,
    SkipsOnFailure,
    ToModel,
    WithChunkReading,
    WithHeadingRow
};

class PaiementsImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;

    public function __construct(private readonly ImportBatch $batch) {}

    public function model(array $row): ?Paiement
    {
        $factureNumero = trim($row['facture'] ?? '');
        $recu          = trim($row['recu'] ?? '');

        if (empty($factureNumero)) return null;

        Cache::increment("import_batch_{$this->batch->id}");
        $this->localCount++;

        if ($this->localCount % 200 === 0) {
            $this->batch->increment('processed_rows', 200);
        }

        $facture = Facture::where('numero', $factureNumero)->first();
        if (!$facture) {
            $this->batch->increment('failed_rows');
            return null;
        }

        $montant = $this->parseAmount($row['paye'] ?? '0');
        $reste   = $this->parseAmount($row['reste'] ?? '0');

        // Mise à jour du solde de la facture
        $facture->update([
            'reste'  => $reste,
            'statut' => $reste <= 0 ? 'payee' : 'impayee',
        ]);

        return Paiement::updateOrCreate(
            [
                'facture_id'   => $facture->id,
                'numero_recu'  => $recu,
                'montant'      => $montant,    // triplet unique : reçu + facture + montant
            ],
            [
                'date'              => $this->parseDate($row['date'] ?? ''),
                'reference_cheque'  => trim($row['cheque'] ?? ''),
                'banque'            => trim($row['banque'] ?? ''),
                'reste'             => $reste,
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}


// namespace App\Imports;

// use App\Models\Paiement;
// use App\Models\Facture;
// use Maatwebsite\Excel\Concerns\ToModel;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithChunkReading;
// use Maatwebsite\Excel\Concerns\WithBatchInserts;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Carbon\Carbon;
// use PhpOffice\PhpSpreadsheet\Shared\Date;

// class PaiementsImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue
// {
//     public function chunkSize(): int
//     {
//         return 1000;
//     }

//     public function batchSize(): int
//     {
//         return 1000;
//     }

//     public function model(array $row)
//     {
//         // ignore empty lines or missing invoice number
//         if (!isset($row['n_facture']) || empty($row['n_facture'])) {
//             return null;
//         }

//         // find facture by its number
//         $facture = Facture::where('numero_facture', trim($row['n_facture']))->first();
//         if (!$facture) {
//             // invoice not found; skip it
//             return null;
//         }

//         // parse date
//         $datePaiement = $this->transformDate($row['date'] ?? $row['date_paiement'] ?? null);

//         $montant = (float) ($row['montant'] ?? $row['montant'] ?? 0);
//         if ($montant <= 0) {
//             return null;
//         }

//         // create paiement model instance
//         $paiement = new Paiement([
//             'facture_id'    => $facture->id,
//             'date_paiement' => $datePaiement,
//             'recu' => $row['recu'] ?? $row['recu'] ?? null,
//             'numero_cheque' => $row['numero_cheque'] ?? null,
//             'banque'        => $row['banque'] ?? null,
//             'montant' => $montant,
//             'created_at'    => now(),
//             'updated_at'    => now(),
//         ]);

//         // update associated facture totals
//         try {
//             $facture->increment('montant_paye', $montant);
//             $facture->decrement('reste_a_payer', $montant);
//         } catch (\Throwable $e) {
//             // ignore update errors; the import itself still returns the paiement instance
//         }

//         return $paiement;
//     }

//     private function transformDate($value)
//     {
//         if (empty($value)) {
//             return now();
//         }

//         try {
//             if (is_numeric($value)) {
//                 return Carbon::instance(Date::excelToDateTimeObject($value));
//             }
//             return Carbon::parse($value);
//         } catch (\Throwable $e) {
//             return now();
//         }
//     }
// }
