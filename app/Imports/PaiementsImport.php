<?php
// app/Imports/PaiementsImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Facture, ImportBatch};
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

class PaiementsImport extends StringValueBinder implements
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

    // Cache local : numero_facture → facture_id
    // Persiste entre les chunks pour éviter les requêtes répétées
    private array $factureCache = [];
    private array $missingFactures = [];

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        // ── 1. Collecter tous les numéros de facture du chunk ────────────────
        $numeros = $rows
            ->pluck('facture')
            ->filter()
            ->map(fn($v) => trim((string) $v))
            ->unique()
            ->toArray();

        // ── 2. Charger uniquement les IDs non encore en cache (1 requête) ────
        $newNumeros = array_diff($numeros, array_keys($this->factureCache));

        if (!empty($newNumeros)) {
            Facture::whereIn('numero_facture', $newNumeros)
                ->select('id', 'numero_facture')
                ->get()
                ->each(function ($f) {
                    $this->factureCache[$f->numero_facture] = $f->id;
                });
        }

        // ── 3. Construire les enregistrements à insérer/mettre à jour ────────
        $now = now()->toDateTimeString();
        $userId = Auth::id();
        $paiements = [];
        $soldesMAJ = []; // [facture_id => reste] — dernier reste connu du chunk

        foreach ($rows as $row) {
            $numero = trim((string) ($row['facture'] ?? ''));
            $recu = trim((string) ($row['recu'] ?? ''));

            if (empty($numero))
                continue;

            $factureId = $this->factureCache[$numero] ?? null;

            if (!$factureId) {
                $this->missingFactures[$numero] = true;
                $this->skippedCount++;
                continue;
            }

            $montant = $this->parseAmount($row['paye'] ?? '0');
            $reste = $this->parseAmount($row['reste'] ?? '0');

            $paiements[] = [
                'facture_id' => $factureId,
                'recu' => $recu,
                'montant' => $montant,
                'date_paiement' => $this->parseDate($row['Date'] ?? ''),
                'numero_cheque' => trim((string) ($row['cheque'] ?? '')),
                'banque' => trim((string) ($row['banque'] ?? '')),
                'facture_anterieur' => trim((string) ($row['facture_Anterieur'] ?? '')),
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // On garde le dernier reste connu pour chaque facture du chunk
            $soldesMAJ[$factureId] = $reste;

            $this->localCount++;
        }

        // ── 4. Upsert paiements — 1 requête pour tout le chunk ───────────────
        if (!empty($paiements)) {
            DB::table('paiements')->upsert(
                $paiements,
                ['facture_id', 'recu'],          // clé unique
                [                                 // colonnes à mettre à jour
                    'montant',
                    'date_paiement',
                    'numero_cheque',
                    'banque',
                    'facture_anterieur',
                    'updated_at',
                ]
            );
        }

        // ── 5. MAJ des soldes factures — 1 requête CASE WHEN ─────────────────
        if (!empty($soldesMAJ)) {
            $cases = '';
            $ids = [];
            $bindings = [];

            foreach ($soldesMAJ as $factureId => $reste) {
                $cases .= " WHEN ? THEN ?";
                $bindings[] = $factureId;
                $bindings[] = $reste;
                $ids[] = $factureId;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $allBindings = array_merge($bindings, $ids);

            DB::statement(
                "UPDATE factures
                 SET reste_a_payer = CASE id {$cases} END
                 WHERE id IN ({$placeholders})",
                $allBindings
            );
        }

        // ── 6. Mise à jour progression ────────────────────────────────────────
        $count = count($paiements);
        if ($count > 0) {
            Cache::increment("import_batch_{$this->batch->id}", $count);
            $this->batch->increment('processed_rows', $count);
        }
    }

    /**
     * Taille du chunk — 500 est le bon équilibre :
     * - Assez grand pour que le SELECT whereIn soit efficace
     * - Assez petit pour ne pas saturer la mémoire PHP
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Appelé par ProcessImportJob après la fin de l'import.
     * Log groupé — évite des milliers d'entrées individuelles.
     */
    public function logMissingFactures(): void
    {
        if (empty($this->missingFactures))
            return;

        $count = count($this->missingFactures);
        $samples = array_slice(array_keys($this->missingFactures), 0, 20);

        Log::warning("PaiementsImport [batch #{$this->batch->id}] : {$count} facture(s) introuvable(s)", [
            'exemples' => $samples,
            'total' => $count,
        ]);

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