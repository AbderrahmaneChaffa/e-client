<?php

namespace App\Imports;

use App\Models\Facture;
use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class FacturesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue
{
    // Nombre de lignes traitées par paquet pour la performance
    public function batchSize(): int
    {
        return 500;
    }
    public function chunkSize(): int
    {
        return 500;
    }

    public function model(array $row)
    {
        // Vérification : si la ligne est vide, on l'ignore
        if (!isset($row['n_facture']) || empty($row['n_facture'])) {
            return null;
        }

        $client = Client::firstOrCreate(
            ['code' => trim($row['client_code'])],
            ['name' => $row['client_nom'] ?? 'Inconnu']
        );

        // Sécurité pour la date
        $dateFacture = now();
        try {
            if (is_numeric($row['date'])) {
                $dateFacture = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date']);
            } else {
                $dateFacture = Carbon::parse($row['date']);
            }
        } catch (\Exception $e) {
            // En cas d'erreur de date, on garde 'now' pour ne pas faire planter l'import
        }

        return new Facture([
            'numero_facture' => $row['n_facture'],
            'client_id'      => $client->id,
            'date_facture'   => $dateFacture,
            'total_ht'       => floatval($row['t_ht'] ?? 0),
            'total_tva'      => floatval($row['t_tva'] ?? 0),
            'total_ttc'      => floatval($row['t_ttc'] ?? 0),
            'montant_paye'   => 0,
            'reste_a_payer'  => floatval($row['t_ttc'] ?? 0),
        ]);
    }

    // Fonction pour convertir la date Excel en date PHP proprement
    private function transformDate($value)
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $value);
        }
    }
}
