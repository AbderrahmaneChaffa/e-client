<?php
// app/Imports/PrestationsPayeesImport.php
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

class PrestationsPayeesImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $totalProcessed = 0;   // ← total cumulatif (pour stats finales)
    private int $totalSkipped = 0;   // ← total cumulatif des ignorées
    private array $missingFactures = [];
    private array $factureIdCache = []; // ← persiste entre les chunks

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        // ── Compteur LOCAL au chunk courant ──────────────────────────────────
        // CRITIQUE : ne pas utiliser $this->localCount ici car il est cumulatif
        $chunkInserted = 0;
        $chunkSkipped = 0;

        // ── 1. Charger les IDs facture du chunk en 1 requête ─────────────────
        $numeros = $rows
            ->pluck('facture')
            ->filter()
            ->map(fn($v) => trim((string) $v))
            ->unique()
            ->toArray();

        $newNumeros = array_diff($numeros, array_keys($this->factureIdCache));

        if (!empty($newNumeros)) {
            Facture::whereIn('numero_facture', $newNumeros)
                ->where('annuler', 0)
                ->select('id', 'numero_facture')
                ->get()
                ->each(fn($f) => $this->factureIdCache[$f->numero_facture] = $f->id);
        }

        // ── 2. Construire les enregistrements du chunk ────────────────────────
        $now = now()->toDateTimeString();
        $records = [];

        foreach ($rows as $row) {
            $numero = trim((string) ($row['facture'] ?? ''));
            $article = trim((string) ($row['article'] ?? ''));

            if (empty($numero))
                continue;

            $factureId = $this->factureIdCache[$numero] ?? null;

            if (!$factureId) {
                $this->missingFactures[$numero] = true;
                $chunkSkipped++;
                continue;
            }

            $records[] = [
                'facture_id' => $factureId,
                'article' => $article,
                'libelle' => trim((string) ($row['libelle'] ?? '')),
                'quantite' => $this->parseAmount($row['quantite'] ?? '0'),
                'prix_unitaire' => $this->parseAmount($row['prix'] ?? '0'),
                'taux_ht' => $this->parseAmount($row['taux_ht'] ?? '0'),
                'total_ht' => $this->parseAmount($row['total_ht'] ?? '0'),
                'total_tva' => $this->parseAmount($row['total_tva'] ?? '0'),
                'total_ttc' => $this->parseAmount($row['total_ttc'] ?? '0'),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $chunkInserted++;
        }

        // ── 3. Upsert en transaction — sous-lots de 500 ──────────────────────
        if (!empty($records)) {
            DB::transaction(function () use ($records) {
                foreach (array_chunk($records, 500) as $chunk) {
                    DB::table('prestations')->upsert(
                        $chunk,
                        ['facture_id', 'article'],
                        [
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
            });
        }

        // ── 4. Mise à jour de la progression — UNIQUEMENT les lignes du chunk ─
        // C'était ici le bug : on utilisait $this->localCount (cumulatif)
        // au lieu de $chunkInserted (lignes du chunk courant uniquement)
        if ($chunkInserted > 0) {
            Cache::increment("import_batch_{$this->batch->id}", $chunkInserted);
            $this->batch->increment('processed_rows', $chunkInserted);
        }

        if ($chunkSkipped > 0) {
            $this->batch->increment('failed_rows', $chunkSkipped);
        }

        // ── 5. Accumuler les totaux globaux ────────────────────────────────────
        $this->totalProcessed += $chunkInserted;
        $this->totalSkipped += $chunkSkipped;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function logMissingFactures(): void
    {
        if (empty($this->missingFactures))
            return;

        Log::warning("PrestationsPayeesImport [batch #{$this->batch->id}]", [
            'count' => count($this->missingFactures),
            'skipped' => $this->totalSkipped,
            'samples' => array_slice(array_keys($this->missingFactures), 0, 20),
        ]);

        // NE PAS incrémenter failed_rows ici — c'est déjà fait dans collection()
        // pour éviter le double comptage
    }

    public function getTotalProcessed(): int
    {
        return $this->totalProcessed;
    }
    public function getTotalSkipped(): int
    {
        return $this->totalSkipped;
    }
}