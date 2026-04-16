<?php

// app/Imports/PaiementsImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Facture, ImportBatch, Paiement};
use Illuminate\Support\Facades\{Auth, Cache, Log};
use Maatwebsite\Excel\Concerns\{
    Importable,
    SkipsErrors,
    SkipsFailures,
    SkipsOnError,
    SkipsOnFailure,
    ToModel,
    WithChunkReading,
    WithCustomValueBinder,
    WithHeadingRow,
};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class PaiementsImport extends StringValueBinder implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount   = 0;
    private int $skippedCount = 0;
    private const FLUSH_EVERY = 200;

    // Cache local : évite N requêtes répétées sur les mêmes numéros de facture
    private array $factureCache = [];
    // Liste des numéros introuvables : loggée une seule fois à la fin
    private array $missingFactures = [];

    public function __construct(private readonly ImportBatch $batch) {}

    public function model(array $row): ?Paiement
    {
        // ── Colonne : en-têtes EXACTS du fichier (HeadingRowFormatter = none) ──
        // recu | Date | code_client | Nom_client | facture |
        // facture_Anterieur | total_ttc | cheque | banque | paye | reste

        $factureNumero = trim($row['facture'] ?? '');
        $recu          = trim($row['recu'] ?? '');

        if (empty($factureNumero)) {
            return null;
        }

        // ── Compteur de progression ─────────────────────────────────────────
        Cache::increment("import_batch_{$this->batch->id}");
        $this->localCount++;

        if ($this->localCount % self::FLUSH_EVERY === 0) {
            $this->batch->increment('processed_rows', self::FLUSH_EVERY);
        }

        // ── Recherche de la facture (cache local) ────────────────────────────
        if (!array_key_exists($factureNumero, $this->factureCache)) {
            $this->factureCache[$factureNumero] = Facture::where('numero_facture', $factureNumero)->first();
        }

        $facture = $this->factureCache[$factureNumero];

        if (!$facture) {
            // On mémorise le numéro manquant sans spammer la DB
            $this->missingFactures[$factureNumero] = true;
            $this->skippedCount++;
            return null;
        }

        // ── Montants ────────────────────────────────────────────────────────
        $montant  = $this->parseAmount($row['paye']  ?? '0');
        $reste    = $this->parseAmount($row['reste'] ?? '0');

        // ── Mise à jour du solde de la facture ───────────────────────────────
        // On utilise le nom de colonne réel du modèle Facture
        // $facture->update([
        //     'reste_a_payer' => $reste,
        // ]);

        // ── Clé unique : (facture_id + recu + code_client) ──────────────────
        // On N'utilise PAS montant dans la clé unique :
        // un même reçu peut payer la même somme sur deux factures différentes
        // (ex : reçu 00001 paie 8 224,20 sur 2026C00340 ET sur 2026C00344).
        // L'unicité réelle est (facture_id + recu) : un reçu ne règle une
        // facture donnée qu'une seule fois.
        return Paiement::updateOrCreate(
            [
                'facture_id' => $facture->id,
                'recu'       => $recu,
            ],
            [
                'montant'           => $montant,
                'date_paiement'     => $this->parseDate($row['Date']             ?? ''),
                'numero_cheque'     => trim($row['cheque']                       ?? ''),
                'banque'            => trim($row['banque']                       ?? ''),
                // En-tête exact du fichier : "facture_Anterieur" (sans 'e' final)
                'facture_anterieur' => trim($row['facture_Anterieur']            ?? ''),
                'created_by'        => Auth::id() ?? null,
            ]
        );
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    /**
     * Appelé par ProcessImportJob après la fin de l'import.
     * Écrit un seul log groupé au lieu de 18 000 entrées individuelles.
     */
    public function logMissingFactures(): void
    {
        if (empty($this->missingFactures)) {
            return;
        }

        $count   = count($this->missingFactures);
        $samples = array_slice(array_keys($this->missingFactures), 0, 20);

        Log::warning("PaiementsImport [batch #{$this->batch->id}] : {$count} facture(s) introuvable(s)", [
            'exemples' => $samples,
            'total'    => $count,
        ]);

        // Mise à jour groupée en une seule requête
        $this->batch->increment('failed_rows', $this->skippedCount);
    }

    public function getLocalCount(): int
    {
        return $this->localCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
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
