<?php


namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Facture, ImportBatch, Navire};
use Illuminate\Support\Facades\{Auth, Cache};
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

/**
 * StringValueBinder force PhpSpreadsheet à lire TOUTES les cellules
 * comme des chaînes brutes, ce qui supprime l'erreur
 * "The separation symbol could not be found" causée par le
 * formateur de nombres quand la locale du fichier est française.
 *
 * Notre parseAmount() du trait gère ensuite la conversion.
 */
class FacturesImport extends StringValueBinder implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;
    private const FLUSH_EVERY = 200;

    // Cache local pour éviter N requêtes répétées sur les mêmes clients/navires
    private array $clientCache = [];
    private array $navireCache = [];

    public function __construct(private readonly ImportBatch $batch) {}

    public function model(array $row): ?Facture
    {
        // ── Sécurité : ignorer les lignes sans numéro de facture ────────────
        $numero = trim($row['FACTURE'] ?? '');
        if (empty($numero)) {
            return null;
        }

        // ── Compteur de progression ─────────────────────────────────────────
        Cache::increment("import_batch_{$this->batch->id}");
        $this->localCount++;

        if ($this->localCount % self::FLUSH_EVERY === 0) {
            $this->batch->increment('processed_rows', self::FLUSH_EVERY);
        }

        // ── Client (avec cache local pour éviter N+1) ───────────────────────
        $client    = null;
        $codeClient = trim($row['CODE_CLIENT'] ?? '');

        if (!empty($codeClient)) {
            if (!isset($this->clientCache[$codeClient])) {
                $this->clientCache[$codeClient] = Client::firstOrCreate(
                    ['code_client' => $codeClient],
                    [
                        'name'    => trim($row['NOM_CLIENT'] ?? ''),
                        'adresse' => trim($row['ADRESSE']   ?? ''),
                        'rc'      => trim($row['RC']        ?? ''),
                        'nis'     => trim($row['NIS']       ?? ''),
                        'ai'      => trim($row['AI']        ?? ''),
                        'nif'     => trim($row['NIF']       ?? ''),
                    ]
                );
            }
            $client = $this->clientCache[$codeClient];
        }

        // ── Navire (avec cache local) ────────────────────────────────────────
        // Note : les dates ENTREE/SORTIE appartiennent à la Facture,
        // pas au Navire. Le Navire n'est identifié que par son nom + pavillon.
        $navire    = null;
        $navireNom = trim($row['NAVIRE'] ?? '');

        // ── Dates (parseDate() retourne null si vide ou invalide) ───────────
        // ENTREE et SORTIE peuvent être vides dans l'ERP → on ne crash pas
        $dateFacture = $this->parseDate($row['DATE']   ?? '');
        $dateEntree  = $this->parseDate($row['ENTREE'] ?? '');
        $dateSortie  = $this->parseDate($row['SORTIE'] ?? '');
        if (!empty($navireNom)) {
            if (!isset($this->navireCache[$navireNom])) {
                $this->navireCache[$navireNom] = Navire::firstOrCreate(
                    ['nom'      => $navireNom],
                    ['pavillon' => trim($row['PAVILLON'] ?? ''),
                        'date_arrivee' => $dateEntree,
                        'date_sortie' => $dateSortie,
                    ]
                );
            }
            $navire = $this->navireCache[$navireNom];
        }


        // ── Montants ────────────────────────────────────────────────────────
        $totalHt  = $this->parseAmount($row['TOTAL_HT']  ?? '0');
        $totalTva = $this->parseAmount($row['TOTAL_TVA'] ?? '0');
        $totalTtc = $this->parseAmount($row['TOTAL_TTC'] ?? '0');
        $reste    = $this->parseAmount($row['RESTE']     ?? '0');

        // ── Annulation ──────────────────────────────────────────────────────
        // La colonne ANNULE vaut "1,00" (annulé) ou "0,00" (normal)
        $annule = (int) round($this->parseAmount($row['ANNULE'] ?? '0')) === 1;

        return Facture::updateOrCreate(
            ['numero_facture' => $numero],
            [
                'client_id'       => $client?->id,
                'navire_id'       => $navire?->id,
                'date_facture'    => $dateFacture,
                // Si votre migration a ces colonnes sur la table factures :
                // 'date_entree'  => $dateEntree,
                // 'date_sortie'  => $dateSortie,
                'bordereau'       => trim($row['BORDEREAU']   ?? ''),
                'description'     => trim($row['DESCRIPTION'] ?? ''),
                'pour'            => trim($row['POUR']        ?? ''),
                'total_ht'        => $totalHt,
                'total_tva'       => $totalTva,
                'total_ttc'       => $totalTtc,
                'reste_a_payer'   => $reste,
                'devise'          => trim($row['DEVISE']      ?? 'DA'),
                'taux_devise'     => $this->parseAmount($row['TAUX_DEVISE'] ?? '1'),
                'mode_paiement'   => trim($row['PAIEMENT']    ?? ''),
                'annuler'         => $annule ? 1 : 0,
                'created_by'      => Auth::id(),
            ]
        );
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
