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
use PhpOffice\PhpSpreadsheet\Shared\Date;

class FacturesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue
{
    // Traitement par lot pour la performance (RAM)
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
        // 1. Ignorer les lignes vides
        if (!isset($row['n_facture']) || empty($row['n_facture'])) {
            return null;
        }

        // 2. Gestion Robuste du Client
        $client = Client::firstOrCreate(
            ['code' => trim($row['client_code'])],
            [
                'name' => $row['client_nom'] ?? 'Client Inconnu',
                'nis'  => $row['nis'] ?? null
            ]
        );

        // 3. Gestion Robuste de la Date (CORRECTION DU BUG)
        $dateFacture = $this->transformDate($row['date']);

        // 4. Création de la facture
        return new Facture([
            'numero_facture' => trim($row['n_facture']),
            'client_id'      => $client->id,
            'date_facture'   => $dateFacture,
            'total_ht'       => (float) ($row['t_ht'] ?? 0),
            'total_tva'      => (float) ($row['t_tva'] ?? 0),
            'total_ttc'      => (float) ($row['t_ttc'] ?? 0),
            'reste_a_payer'  => (float) ($row['t_ttc'] ?? 0),
            'montant_paye'   => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Cette fonction empêche le crash "floor(): Argument #1"
     */
    private function transformDate($value)
    {
        try {
            // Cas 1 : C'est un numéro Excel (ex: 45367)
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value));
            }

            // Cas 2 : C'est une chaine de texte (ex: "2026-02-11" ou "11/02/2026")
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            // Cas 3 : Format impossible à lire -> on met la date d'aujourd'hui pour ne pas bloquer l'import
            return now();
        }
    }
}
