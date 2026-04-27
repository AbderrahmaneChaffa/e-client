<?php
// app/Imports/FacturesPayeesImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Facture, ImportBatch, Navire};
use Illuminate\Support\Facades\{Auth, Cache};
use Maatwebsite\Excel\Concerns\{
    Importable, SkipsErrors, SkipsFailures,
    SkipsOnError, SkipsOnFailure,
    ToModel, WithChunkReading,
    WithCustomValueBinder, WithHeadingRow,
};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Différence avec FacturesImport :
 * - Ce fichier ne contient QUE des factures non annulées (annuler = 0)
 * - On met à jour les factures EXISTANTES (updateOrCreate mais force annuler=0)
 * - On crée quand même si la facture n'existe pas encore
 */
class FacturesPayeesImport extends StringValueBinder implements
    ToModel, WithHeadingRow, WithChunkReading,
    WithCustomValueBinder, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount  = 0;
    private int $updatedCount = 0;
    private int $createdCount = 0;
    private const FLUSH_EVERY = 200;

    private array $clientCache = [];
    private array $navireCache = [];

    public function __construct(private readonly ImportBatch $batch) {}

    public function model(array $row): ?Facture
    {
        $numero = trim($row['FACTURE'] ?? '');
        if (empty($numero)) return null;

        Cache::increment("import_batch_{$this->batch->id}");
        $this->localCount++;

        if ($this->localCount % self::FLUSH_EVERY === 0) {
            $this->batch->increment('processed_rows', self::FLUSH_EVERY);
        }

        // ── Client ──────────────────────────────────────────────────────────
        $client     = null;
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

        // ── Navire ──────────────────────────────────────────────────────────
        $navire    = null;
        $navireNom = trim($row['NAVIRE'] ?? '');

        if (!empty($navireNom)) {
            if (!isset($this->navireCache[$navireNom])) {
                $this->navireCache[$navireNom] = Navire::firstOrCreate(
                    ['nom'      => $navireNom],
                    ['pavillon' => trim($row['PAVILLON'] ?? '')]
                );
            }
            $navire = $this->navireCache[$navireNom];
        }

        // ── Montants ────────────────────────────────────────────────────────
        $reste = $this->parseAmount($row['RESTE'] ?? '0');

        // ── updateOrCreate : force annuler=0 quoi qu'il arrive ──────────────
        // C'est la différence clé avec FacturesImport.
        // Si la facture existait avec annuler=1, elle sera remise à 0.
        $facture = Facture::updateOrCreate(
            ['numero_facture' => $numero],
            [
                'client_id'      => $client?->id,
                'navire_id'      => $navire?->id,
                'date_facture'   => $this->parseDate($row['DATE']   ?? ''),
                'bordereau'      => trim($row['BORDEREAU']   ?? ''),
                'description'    => trim($row['DESCRIPTION'] ?? ''),
                'pour'           => trim($row['POUR']        ?? ''),
                'total_ht'       => $this->parseAmount($row['TOTAL_HT']  ?? '0'),
                'total_tva'      => $this->parseAmount($row['TOTAL_TVA'] ?? '0'),
                'total_ttc'      => $this->parseAmount($row['TOTAL_TTC'] ?? '0'),
                'reste_a_payer'  => $reste,
                'devise'         => trim($row['DEVISE']      ?? 'DA'),
                'taux_devise'    => $this->parseAmount($row['TAUX_DEVISE'] ?? '1'),
                'mode_paiement'  => trim($row['PAIEMENT']    ?? ''),
                // ── DIFFÉRENCE CLÉ : force toujours à 0 ────────────────────
                'annuler'        => 0,
                'created_by'     => Auth::id(),
            ]
        );

        $facture->wasRecentlyCreated ? $this->createdCount++ : $this->updatedCount++;

        return null; // on retourne null car updateOrCreate a déjà sauvé
    }

    public function chunkSize(): int { return 500; }

    public function getStats(): array
    {
        return [
            'processed' => $this->localCount,
            'updated'   => $this->updatedCount,
            'created'   => $this->createdCount,
        ];
    }
}