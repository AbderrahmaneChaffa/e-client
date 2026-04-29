<?php
// app/Imports/PrestationsImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Facture, ImportBatch};
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

class PrestationsImport extends StringValueBinder implements
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
    private array $missingFactures = [];

    // Cache local : numero_facture → facture_id
    private array $factureIdCache = [];

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        // ── 1. Collecter tous les numéros de facture du chunk ────────────
        $numeros = $rows
            ->pluck('facture')
            ->filter()
            ->map(fn($v) => trim($v))
            ->unique()
            ->values()
            ->toArray();

        // ── 2. Charger les IDs en UNE seule requête ──────────────────────
        $newNumeros = array_diff($numeros, array_keys($this->factureIdCache));

        if (!empty($newNumeros)) {
            Facture::whereIn('numero_facture', $newNumeros)
                ->select('id', 'numero_facture')
                ->get()
                ->each(function ($f) {
                    $this->factureIdCache[$f->numero_facture] = $f->id;
                });
        }

        // ── 3. Construire le batch d'upsert ──────────────────────────────
        $now = now()->toDateTimeString();
        $records = [];

        foreach ($rows as $row) {
            $numero = trim($row['facture'] ?? '');
            $article = trim($row['article'] ?? '');

            if (empty($numero))
                continue;

            $factureId = $this->factureIdCache[$numero] ?? null;

            if (!$factureId) {
                $this->missingFactures[$numero] = true;
                $this->skippedCount++;
                continue;
            }

            $records[] = [
                'facture_id' => $factureId,
                'article' => $article,
                'libelle' => trim($row['libelle'] ?? ''),
                'quantite' => $this->parseAmount($row['quantite'] ?? '0'),
                'prix_unitaire' => $this->parseAmount($row['prix'] ?? '0'),
                'taux_ht' => $this->parseAmount($row['taux_ht'] ?? '0'),
                'total_ht' => $this->parseAmount($row['total_ht'] ?? '0'),
                'total_tva' => $this->parseAmount($row['total_tva'] ?? '0'),
                'total_ttc' => $this->parseAmount($row['total_ttc'] ?? '0'),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->localCount++;
        }

        // ── 4. Upsert en une seule requête pour tout le chunk ────────────
        if (!empty($records)) {
            DB::table('prestations')->upsert(
                $records,
                ['facture_id', 'article'],          // clés uniques
                [                                    // colonnes à mettre à jour
                    'libelle',
                    'quantite',
                    'prix_unitaire',
                    'taux_ht',
                    'total_ht',
                    'total_tva',
                    'total_ttc',
                    'updated_at',
                ]
            );
        }

        // ── 5. Mise à jour progression ───────────────────────────────────
        Cache::increment("import_batch_{$this->batch->id}", count($records));
        $this->batch->increment('processed_rows', count($records));
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function logMissingFactures(): void
    {
        if (empty($this->missingFactures))
            return;

        $count = count($this->missingFactures);
        $samples = array_slice(array_keys($this->missingFactures), 0, 20);

        Log::warning("PrestationsImport [batch #{$this->batch->id}] : {$count} introuvable(s)", [
            'exemples' => $samples,
        ]);

        $this->batch->increment('failed_rows', $this->skippedCount);
    }
}