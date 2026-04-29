<?php
// app/Imports/FacturesPayeesImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Escale, Facture, ImportBatch, Navire};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Auth, Cache, DB};
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

class FacturesPayeesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;
    private int $createdCount = 0;
    private int $updatedCount = 0;
    private int $skippedCount = 0;

    // Caches locaux
    private array $clientCache = [];   // code_client    → Client
    private array $navireCache = [];   // "nom|pavillon"  → Navire
    private array $escaleCache = [];   // navire_id       → Escale

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        $now = now()->toDateTimeString();
        $userId = Auth::id();

        // ── Pré-charger les numéros existants pour stats create/update ────
        $numeros = $rows
            ->pluck('FACTURE')
            ->filter()
            ->map(fn($v) => trim($v))
            ->unique()
            ->toArray();

        $existants = Facture::whereIn('numero_facture', $numeros)
            ->pluck('numero_facture')
            ->flip()
            ->toArray();

        $records = [];

        foreach ($rows as $row) {
            // ── Numéro obligatoire ────────────────────────────────────────
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

            // ── Navire (même logique que FacturesImport) ──────────────────
            $navireNom = trim($row['NAVIRE'] ?? 'NAVIRE INCONNU');
            $pavillon = trim($row['PAVILLON'] ?? 'INCONNU');
            $navireKey = $navireNom . '|' . $pavillon;

            if (!isset($this->navireCache[$navireKey])) {
                $this->navireCache[$navireKey] = Navire::firstOrCreate(
                    ['nom' => $navireNom, 'pavillon' => $pavillon]
                );
            }
            $navire = $this->navireCache[$navireKey];

            // ── Escale (même logique que FacturesImport) ──────────────────
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

            // ── Stats create / update ─────────────────────────────────────
            isset($existants[$numero]) ? $this->updatedCount++ : $this->createdCount++;

            $records[] = [
                'numero_facture' => $numero,
                'client_id' => $clientId,
                'escale_id' => $escaleId,          // ← escale_id comme FacturesImport
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
                'annuler' => 0,                  // ← forcé à 0 toujours
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->localCount++;
        }

        // ── Upsert groupé ─────────────────────────────────────────────────
        if (!empty($records)) {
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
                    'updated_at',
                ]
            );
        }

        // ── Progression ───────────────────────────────────────────────────
        $count = count($records);
        Cache::increment("import_batch_{$this->batch->id}", $count);
        $this->batch->increment('processed_rows', $count);
    }

    public function chunkSize(): int
    {
        return 300;
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