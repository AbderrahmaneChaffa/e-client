<?php

namespace App\Imports;

use App\Models\Paiement;
use App\Models\Facture;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PaiementsImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue
{
    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function model(array $row)
    {
        // ignore empty lines or missing invoice number
        if (!isset($row['n_facture']) || empty($row['n_facture'])) {
            return null;
        }

        // find facture by its number
        $facture = Facture::where('numero_facture', trim($row['n_facture']))->first();
        if (!$facture) {
            // invoice not found; skip it
            return null;
        }

        // parse date
        $datePaiement = $this->transformDate($row['date'] ?? $row['date_paiement'] ?? null);

        $montant = (float) ($row['montant'] ?? $row['montant'] ?? 0);
        if ($montant <= 0) {
            return null;
        }

        // create paiement model instance
        $paiement = new Paiement([
            'facture_id'    => $facture->id,
            'date_paiement' => $datePaiement,
            'recu' => $row['recu'] ?? $row['recu'] ?? null,
            'numero_cheque' => $row['numero_cheque'] ?? null,
            'banque'        => $row['banque'] ?? null,
            'montant' => $montant,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // update associated facture totals
        try {
            $facture->increment('montant_paye', $montant);
            $facture->decrement('reste_a_payer', $montant);
        } catch (\Throwable $e) {
            // ignore update errors; the import itself still returns the paiement instance
        }

        return $paiement;
    }

    private function transformDate($value)
    {
        if (empty($value)) {
            return now();
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return now();
        }
    }
}
