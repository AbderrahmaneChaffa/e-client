<?php
// app/Imports/PrestationsPayeesImport.php
namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Facture, ImportBatch, Prestation};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\{
    Importable, SkipsErrors, SkipsFailures,
    SkipsOnError, SkipsOnFailure,
    ToModel, WithChunkReading,
    WithCustomValueBinder, WithHeadingRow,
};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Même logique que PrestationsImport.
 * Classe séparée pour traçabilité dans import_batches (type distinct).
 */
class PrestationsPayeesImport extends StringValueBinder implements
    ToModel, WithHeadingRow, WithChunkReading,
    WithCustomValueBinder, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount   = 0;
    private int $skippedCount = 0;
    private const FLUSH_EVERY = 200;

    private array $factureCache  = [];
    private array $missingFactures = [];

    public function __construct(private readonly ImportBatch $batch) {}

    public function model(array $row): ?Prestation
    {
        $factureNumero = trim($row['facture'] ?? '');
        $article       = trim($row['article'] ?? '');

        if (empty($factureNumero)) return null;

        Cache::increment("import_batch_{$this->batch->id}");
        $this->localCount++;

        if ($this->localCount % self::FLUSH_EVERY === 0) {
            $this->batch->increment('processed_rows', self::FLUSH_EVERY);
        }

        // Cache local pour éviter N+1
        if (!array_key_exists($factureNumero, $this->factureCache)) {
            $this->factureCache[$factureNumero] = Facture::where('numero_facture', $factureNumero)
                ->where('annuler', 0) // On ne lie qu'aux factures non annulées
                ->first();
        }

        $facture = $this->factureCache[$factureNumero];

        if (!$facture) {
            $this->missingFactures[$factureNumero] = true;
            $this->skippedCount++;
            return null;
        }

        return Prestation::updateOrCreate(
            [
                'facture_id' => $facture->id,
                'article'    => $article,
            ],
            [
                'libelle'   => trim($row['libelle']   ?? ''),
                'quantite'  => $this->parseAmount($row['quantite']  ?? '0'),
                'prix_unitaire'      => $this->parseAmount($row['prix']      ?? '0'),
                'taux_ht'   => $this->parseAmount($row['taux_ht']   ?? '0'),
                'total_ht'  => $this->parseAmount($row['total_ht']  ?? '0'),
                'total_tva' => $this->parseAmount($row['total_tva'] ?? '0'),
                'total_ttc' => $this->parseAmount($row['total_ttc'] ?? '0'),
            ]
        );
    }

    public function chunkSize(): int { return 500; }

    public function logMissingFactures(): void
    {
        if (empty($this->missingFactures)) return;

        $count   = count($this->missingFactures);
        $samples = array_slice(array_keys($this->missingFactures), 0, 20);

        Log::warning("PrestationsPayeesImport [batch #{$this->batch->id}] : {$count} facture(s) introuvable(s)", [
            'exemples' => $samples,
        ]);

        $this->batch->increment('failed_rows', $this->skippedCount);
    }
}