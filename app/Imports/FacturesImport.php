<?php
// app/Imports/FacturesImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Escale, Facture, ImportBatch, Navire};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Auth, Cache, DB, Log};
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

class FacturesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;
    private int $skippedCount = 0;

    // Caches locaux — évitent les requêtes répétées sur les mêmes entités
    private array $clientCache = [];   // code_client   → Client
    private array $navireCache = [];   // "nom|pavillon" → Navire
    private array $escaleCache = [];   // navire_id      → Escale

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        $now = now()->toDateTimeString();
        $userId = Auth::id();
        $records = [];

        foreach ($rows as $row) {
            // ── Numéro de facture obligatoire ────────────────────────────
            $numero = trim($row['FACTURE'] ?? '');
            if (empty($numero))
                continue;

            // ── Client ───────────────────────────────────────────────────
            $clientId = null;
            $codeClient = trim($row['CODE_CLIENT'] ?? '');

            if (!empty($codeClient)) {
                if (!isset($this->clientCache[$codeClient])) {
                    $this->clientCache[$codeClient] = Client::firstOrCreate(
                        ['code_client' => $codeClient],
                        [
                            'name' => trim($row['NOM_CLIENT'] ?? ''),
                            'adresse' => trim($row['ADRESSE'] ?? ''),
                            'rc' => trim($row['RC'] ?? ''),
                            'nis' => trim($row['NIS'] ?? ''),
                            'ai' => trim($row['AI'] ?? ''),
                            'nif' => trim($row['NIF'] ?? ''),
                        ]
                    );
                }
                $clientId = $this->clientCache[$codeClient]->id;
            }

            // ── Navire ───────────────────────────────────────────────────
            $escaleId = null;
            $navireNom = trim($row['NAVIRE'] ?? 'NAVIRE INCONNU');
            $pavillon = trim($row['PAVILLON'] ?? 'INCONNU');
            $navireKey = $navireNom . '|' . $pavillon;

            if (!isset($this->navireCache[$navireKey])) {
                $this->navireCache[$navireKey] = Navire::firstOrCreate(
                    ['nom' => $navireNom, 'pavillon' => $pavillon]
                );
            }
            $navire = $this->navireCache[$navireKey];

            // ── Escale (1 par navire, avec cache) ────────────────────────
            if (!isset($this->escaleCache[$navire->id])) {
                $this->escaleCache[$navire->id] = Escale::firstOrCreate(
                    ['navire_id' => $navire->id],
                    [
                        'date_arrivee' => $this->parseDate($row['ENTREE'] ?? ''),
                        'date_sortie' => $this->parseDate($row['SORTIE'] ?? ''),
                    ]
                );
            }
            $escaleId = $this->escaleCache[$navire->id]->id;

            // ── Montants ─────────────────────────────────────────────────
            $annule = (int) round($this->parseAmount($row['ANNULE'] ?? '0')) === 1;

            $records[] = [
                'numero_facture' => $numero,
                'client_id' => $clientId,
                'escale_id' => $escaleId,
                'date_facture' => $this->parseDate($row['DATE'] ?? ''),
                'bordereau' => trim($row['BORDEREAU'] ?? ''),
                'description' => trim($row['DESCRIPTION'] ?? ''),
                'pour' => trim($row['POUR'] ?? ''),
                'total_ht' => $this->parseAmount($row['TOTAL_HT'] ?? '0'),
                'total_tva' => $this->parseAmount($row['TOTAL_TVA'] ?? '0'),
                'total_ttc' => $this->parseAmount($row['TOTAL_TTC'] ?? '0'),
                'reste_a_payer' => $this->parseAmount($row['RESTE'] ?? '0'),
                'devise' => trim($row['DEVISE'] ?? 'DA'),
                'taux_devise' => $this->parseAmount($row['TAUX_DEVISE'] ?? '1'),
                'mode_paiement' => trim($row['PAIEMENT'] ?? ''),
                'annuler' => $annule ? 1 : 0,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->localCount++;
        }

        // ── Upsert en une seule requête pour tout le chunk ────────────────
        if (!empty($records)) {
            DB::table('factures')->upsert(
                $records,
                ['numero_facture'],    // clé unique pour la détection doublon
                [                      // colonnes à mettre à jour si existe
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
                    'updated_at',
                ]
            );
        }

        // ── Progression ───────────────────────────────────────────────────
        $count = count($records);
        Cache::increment("import_batch_{$this->batch->id}", $count);
        $this->batch->increment('processed_rows', $count);

        if ($this->skippedCount > 0) {
            $this->batch->increment('failed_rows', $this->skippedCount);
            $this->skippedCount = 0;
        }
    }

    public function chunkSize(): int
    {
        // 300 lignes optimal pour ToCollection avec des entités liées
        return 300;
    }

    public function getLocalCount(): int
    {
        return $this->localCount;
    }
}