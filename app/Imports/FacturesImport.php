<?php
// app/Imports/FacturesImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Escale, Facture, ImportBatch, Navire};
use App\Services\ImportRowHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Log};
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
        $userId = $this->batch->created_by;
        $records = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;

        $numeros = $rows
            ->map(fn($row) => trim((string) $this->cellValue($row, 'facture', '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingHashes = Facture::whereIn('numero_facture', $numeros)
            ->pluck('row_hash', 'numero_facture')
            ->map(fn ($hash) => $hash ?? '__EXISTS_NO_HASH__')
            ->all();

        foreach ($rows as $row) {
            // ── Numéro de facture obligatoire ────────────────────────────
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            if (empty($numero))
                continue;

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

            // ── Client ───────────────────────────────────────────────────
            $clientId = null;
            $codeClient = trim((string) $this->cellValue($row, 'code_client', ''));

            if (!empty($codeClient)) {
                if (!isset($this->clientCache[$codeClient])) {
                    $this->clientCache[$codeClient] = Client::firstOrCreate(
                        ['code_client' => $codeClient],
                        [
                            'name' => trim((string) $this->cellValue($row, 'nom_client', '')),
                            'adresse' => trim((string) $this->cellValue($row, 'adresse', '')),
                            'rc' => trim((string) $this->cellValue($row, 'rc', '')),
                            'nis' => trim((string) $this->cellValue($row, 'nis', '')),
                            'ai' => trim((string) $this->cellValue($row, 'ai', '')),
                            'nif' => trim((string) $this->cellValue($row, 'nif', '')),
                        ]
                    );
                }
                $clientId = $this->clientCache[$codeClient]->id;
            }

            // ── Navire ───────────────────────────────────────────────────
            $escaleId = null;
            $navireNom = trim((string) $this->cellValue($row, 'navire', 'NAVIRE INCONNU'));
            $pavillon = trim((string) $this->cellValue($row, 'pavillon', 'INCONNU'));
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
                        'date_arrivee' => $this->parseDate($this->cellValue($row, 'entree', '')),
                        'date_sortie' => $this->parseDate($this->cellValue($row, 'sortie', '')),
                    ]
                );
            }
            $escaleId = $this->escaleCache[$navire->id]->id;

            // ── Montants ─────────────────────────────────────────────────
            $annule = (int) round($this->parseAmount($this->cellValue($row, 'annule', '0'))) === 1;

            $existingHash ? $updatedRows++ : $createdRows++;

            $records[] = [
                'numero_facture' => $numero,
                'client_id' => $clientId,
                'escale_id' => $escaleId,
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
                'annuler' => $annule ? 1 : 0,
                'row_hash' => $rowHash,
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
                    'row_hash',
                    'updated_at',
                ]
            );
        }

        // ── Progression ───────────────────────────────────────────────────
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
        // 300 lignes optimal pour ToCollection avec des entités liées
        return 500;
    }

    public function getLocalCount(): int
    {
        return $this->localCount;
    }
}
