<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\Facture;
use App\Services\ImportRowHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class PreviewStatsImport extends StringValueBinder implements ToCollection, WithHeadingRow, WithChunkReading, WithCustomValueBinder
{
    use Importable, ParsesExcelData;

    private int $rowCount = 0;
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $duplicateRows = 0;
    private int $missingParentRows = 0;

    /** @var array<string,float> */
    private array $totals = [
        'total_ht' => 0.0,
        'total_tva' => 0.0,
        'total_ttc' => 0.0,
        'paye' => 0.0,
        'reste' => 0.0,
    ];

    /** @var array<int,array<string,mixed>> */
    private array $samples = [];

    /** @var array<string,bool> */
    private array $seenKeys = [];

    public function __construct(
        private readonly string $type,
        private readonly bool $forceImport = false,
        private readonly int $sampleLimit = 5,
    ) {
    }

    public function collection(Collection $rows): void
    {
        $this->rowCount += $rows->count();

        foreach ($rows as $row) {
            if (count($this->samples) < $this->sampleLimit) {
                $this->samples[] = $row instanceof Collection ? $row->all() : (array) $row;
            }

            foreach (array_keys($this->totals) as $column) {
                $this->totals[$column] += $this->parseAmount($this->cellValue($row, $column, 0));
            }
        }

        $prepared = $rows
            ->map(fn ($row) => [
                'key' => ImportRowHasher::key($this->type, $row),
                'hash' => ImportRowHasher::hash($this->type, $row),
                'row' => $row,
            ])
            ->filter(fn ($row) => $row['key'] !== null)
            ->values();

        $existing = $this->existingHashes($prepared);

        foreach ($prepared as $item) {
            if (isset($this->seenKeys[$item['key']])) {
                $this->duplicateRows++;
            }

            $this->seenKeys[$item['key']] = true;
            $existingHash = $existing[$item['key']] ?? null;

            if ($existingHash === null) {
                $this->created++;
            } elseif (! $this->forceImport && $existingHash !== '__EXISTS_NO_HASH__' && hash_equals((string) $existingHash, $item['hash'])) {
                $this->skipped++;
            } else {
                $this->updated++;
            }
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function report(): array
    {
        return [
            'row_count' => $this->rowCount,
            'impact' => [
                'created' => $this->created,
                'updated' => $this->updated,
                'skipped' => $this->skipped,
                'duplicates_in_file' => $this->duplicateRows,
                'missing_parent_rows' => $this->missingParentRows,
            ],
            'totals' => array_map(fn ($value) => round($value, 2), $this->totals),
            'sample_rows' => $this->samples,
        ];
    }

    /**
     * @param Collection<int,array{key:string,hash:string,row:mixed}> $prepared
     * @return array<string,string|null>
     */
    private function existingHashes(Collection $prepared): array
    {
        $baseType = match ($this->type) {
            'factures_payees' => 'factures',
            'prestations_payees' => 'prestations',
            default => $this->type,
        };

        return match ($baseType) {
            'factures' => $this->existingFactureHashes($prepared),
            'prestations' => $this->existingPrestationHashes($prepared),
            'paiements' => $this->existingPaiementHashes($prepared),
            default => [],
        };
    }

    private function existingFactureHashes(Collection $prepared): array
    {
        $numeros = $prepared->pluck('key')->unique()->values()->all();

        return Facture::query()
            ->whereIn(DB::raw('LOWER(numero_facture)'), $numeros)
            ->selectRaw('LOWER(numero_facture) AS numero, row_hash')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->numero => $row->row_hash ?? '__EXISTS_NO_HASH__'])
            ->all();
    }

    private function existingPrestationHashes(Collection $prepared): array
    {
        $keys = $prepared->pluck('key')->all();
        $numeros = collect($keys)->map(fn ($key) => explode('|', $key)[0] ?? null)->filter()->unique()->values()->all();
        $articles = collect($keys)->map(fn ($key) => explode('|', $key)[1] ?? null)->filter()->unique()->values()->all();

        if ($numeros === [] || $articles === []) {
            return [];
        }

        $rows = DB::table('prestations')
            ->join('factures', 'factures.id', '=', 'prestations.facture_id')
            ->whereIn(DB::raw('LOWER(factures.numero_facture)'), $numeros)
            ->whereIn(DB::raw('LOWER(prestations.article)'), $articles)
            ->selectRaw('LOWER(factures.numero_facture) AS numero, LOWER(prestations.article) AS article, prestations.row_hash')
            ->get();

        return $rows->mapWithKeys(fn ($row) => ["{$row->numero}|{$row->article}" => $row->row_hash ?? '__EXISTS_NO_HASH__'])->all();
    }

    private function existingPaiementHashes(Collection $prepared): array
    {
        $keys = $prepared->pluck('key')->all();
        $numeros = collect($keys)->map(fn ($key) => explode('|', $key)[0] ?? null)->filter()->unique()->values()->all();
        $recus = collect($keys)->map(fn ($key) => explode('|', $key)[1] ?? null)->filter()->unique()->values()->all();

        if ($numeros === [] || $recus === []) {
            return [];
        }

        $rows = DB::table('paiements')
            ->join('factures', 'factures.id', '=', 'paiements.facture_id')
            ->whereIn(DB::raw('LOWER(factures.numero_facture)'), $numeros)
            ->whereIn(DB::raw('LOWER(paiements.recu)'), $recus)
            ->selectRaw('LOWER(factures.numero_facture) AS numero, LOWER(paiements.recu) AS recu, paiements.row_hash')
            ->get();

        return $rows->mapWithKeys(fn ($row) => ["{$row->numero}|{$row->recu}" => $row->row_hash ?? '__EXISTS_NO_HASH__'])->all();
    }
}
