<?php



namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Facture, ImportBatch, Prestation};
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\{
    Importable,
    SkipsOnError,
    SkipsOnFailure,
    SkipsErrors,
    SkipsFailures,
    ToModel,
    WithChunkReading,
    WithHeadingRow
};

class PrestationsImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;

    public function __construct(private readonly ImportBatch $batch) {}

    public function model(array $row): ?Prestation
    {
        $factureNumero = trim($row['facture'] ?? '');
        $article       = trim($row['article'] ?? '');

        if (empty($factureNumero)) return null;

        Cache::increment("import_batch_{$this->batch->id}");
        $this->localCount++;

        if ($this->localCount % 200 === 0) {
            $this->batch->increment('processed_rows', 200);
        }

        // La facture DOIT exister (l'import factures doit être fait avant)
        $facture = Facture::where('numero_facture', $factureNumero)->first();
        if (!$facture) {
            $this->batch->increment('failed_rows');
            return null;
        }

        return Prestation::updateOrCreate(
            [
                'facture_id' => $facture->id,
                'article'    => $article,
            ],
            [
                'libelle'    => trim($row['libelle'] ?? ''),
                'quantite'   => $this->parseAmount($row['quantite'] ?? '0'),
                'prix_unitaire'       => $this->parseAmount($row['prix'] ?? '0'),
                'taux_ht'    => $this->parseAmount($row['taux_ht'] ?? '0'),
                'total_ht'   => $this->parseAmount($row['total_ht'] ?? '0'),
                'total_tva'  => $this->parseAmount($row['total_tva'] ?? '0'),
                'total_ttc'  => $this->parseAmount($row['total_ttc'] ?? '0'),
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
