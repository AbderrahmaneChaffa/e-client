<?php

namespace App\Services;

use App\Imports\Concerns\ParsesExcelData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use RuntimeException;

class ImportPreviewService
{
    use ParsesExcelData;

    private const SCAN_LIMIT = 500;

    public function __construct(private readonly ExcelTypeDetector $detector)
    {
    }

    public function preview(UploadedFile $file, bool $forceImport = false): array
    {
        $inspection = $this->detector->inspect($file, sampleRows: 5);

        if (! $inspection['type']) {
            throw new RuntimeException('Impossible de reconnaitre ce fichier Excel.');
        }

        $rows = $this->readPreviewRows((string) $file->getRealPath(), self::SCAN_LIMIT);
        $stats = $this->statsForRows($inspection['type'], new Collection($rows), $forceImport);
        $isLimited = $inspection['row_count'] > $stats['row_count'];

        return [
            ...$inspection,
            'row_count' => $inspection['row_count'],
            'scanned_rows' => $stats['row_count'],
            'preview_limited' => $isLimited,
            'impact_is_estimate' => $isLimited,
            'valid' => $inspection['missing_headers'] === [],
            'impact' => $stats['impact'],
            'totals' => $stats['totals'],
            'sample_rows' => array_slice($stats['sample_rows'] ?: $inspection['sample_rows'], 0, 5),
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'file_hash' => sha1_file($file->getRealPath()) ?: null,
            'force_import' => $forceImport,
        ];
    }

    /**
     * @param array<int,UploadedFile> $files
     */
    public function previewMany(array $files, bool $forceImport = false): array
    {
        $previews = collect($files)
            ->map(fn (UploadedFile $file) => $this->preview($file, $forceImport))
            ->sortBy(fn (array $preview) => array_search($preview['type'], $this->detector->importOrder(), true))
            ->values();

        $duplicates = $previews
            ->groupBy('type')
            ->filter(fn ($items) => $items->count() > 1)
            ->keys()
            ->values()
            ->all();

        return [
            'valid' => $duplicates === [] && $previews->every(fn ($preview) => $preview['valid']),
            'duplicates' => $duplicates,
            'files' => $previews->all(),
            'execution_order' => $previews->pluck('type')->all(),
            'summary' => [
                'files' => $previews->count(),
                'rows' => $previews->sum('row_count'),
                'scanned_rows' => $previews->sum('scanned_rows'),
                'created' => $previews->sum('impact.created'),
                'updated' => $previews->sum('impact.updated'),
                'skipped' => $previews->sum('impact.skipped'),
                'impact_is_estimate' => $previews->contains(fn ($preview) => $preview['impact_is_estimate']),
                'total_ttc' => round($previews->sum('totals.total_ttc'), 2),
                'total_paid' => round($previews->sum('totals.paye'), 2),
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function readPreviewRows(string $path, int $limit): array
    {
        $reader = IOFactory::createReader(IOFactory::identify($path));
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class($limit + 1) implements IReadFilter {
            public function __construct(private readonly int $maxRow)
            {
            }

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->maxRow;
            }
        });

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = min($sheet->getHighestDataRow(), $limit + 1);
        $rawRows = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false, false);
        $spreadsheet->disconnectWorksheets();

        $headers = array_map(fn ($header) => ExcelTypeDetector::normalizeHeader((string) $header), $rawRows[0] ?? []);
        $rows = [];

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $row = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $rawRow[$index] ?? null;
            }

            if (array_filter($row, fn ($value) => $value !== null && $value !== '') !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function statsForRows(string $type, Collection $rows, bool $forceImport): array
    {
        $totals = [
            'total_ht' => 0.0,
            'total_tva' => 0.0,
            'total_ttc' => 0.0,
            'paye' => 0.0,
            'reste' => 0.0,
        ];
        $samples = [];
        $seen = [];
        $duplicateRows = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (count($samples) < 5) {
                $samples[] = $row;
            }

            foreach (array_keys($totals) as $column) {
                $totals[$column] += $this->parseAmount($this->cellValue($row, $column, 0));
            }
        }

        $prepared = $rows
            ->map(fn ($row) => [
                'key' => ImportRowHasher::key($type, $row),
                'hash' => ImportRowHasher::hash($type, $row),
            ])
            ->filter(fn ($row) => $row['key'] !== null)
            ->values();

        $existing = $this->existingHashes($type, $prepared);

        foreach ($prepared as $item) {
            if (isset($seen[$item['key']])) {
                $duplicateRows++;
            }

            $seen[$item['key']] = true;
            $existingHash = $existing[$item['key']] ?? null;

            if ($existingHash === null) {
                $created++;
            } elseif (! $forceImport && $existingHash !== '__EXISTS_NO_HASH__' && hash_equals((string) $existingHash, $item['hash'])) {
                $skipped++;
            } else {
                $updated++;
            }
        }

        return [
            'row_count' => $rows->count(),
            'impact' => [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'duplicates_in_file' => $duplicateRows,
                'missing_parent_rows' => 0,
            ],
            'totals' => array_map(fn ($value) => round($value, 2), $totals),
            'sample_rows' => $samples,
        ];
    }

    /**
     * @param Collection<int,array{key:string,hash:string}> $prepared
     * @return array<string,string>
     */
    private function existingHashes(string $type, Collection $prepared): array
    {
        $baseType = match ($type) {
            'factures_payees' => 'factures',
            'prestations_payees' => 'prestations',
            default => $type,
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

        return DB::table('factures')
            ->whereIn(DB::raw('LOWER(numero_facture)'), $numeros)
            ->selectRaw('LOWER(numero_facture) AS numero, row_hash')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->numero => $row->row_hash ?? '__EXISTS_NO_HASH__'])
            ->all();
    }

    private function existingPrestationHashes(Collection $prepared): array
    {
        return $this->existingChildHashes($prepared, 'prestations', 'article');
    }

    private function existingPaiementHashes(Collection $prepared): array
    {
        return $this->existingChildHashes($prepared, 'paiements', 'recu');
    }

    private function existingChildHashes(Collection $prepared, string $table, string $keyColumn): array
    {
        $keys = $prepared->pluck('key')->all();
        $numeros = collect($keys)->map(fn ($key) => explode('|', $key)[0] ?? null)->filter()->unique()->values()->all();
        $children = collect($keys)->map(fn ($key) => explode('|', $key)[1] ?? null)->filter()->unique()->values()->all();

        if ($numeros === [] || $children === []) {
            return [];
        }

        $rows = DB::table($table)
            ->join('factures', 'factures.id', '=', "{$table}.facture_id")
            ->whereIn(DB::raw('LOWER(factures.numero_facture)'), $numeros)
            ->whereIn(DB::raw("LOWER({$table}.{$keyColumn})"), $children)
            ->selectRaw("LOWER(factures.numero_facture) AS numero, LOWER({$table}.{$keyColumn}) AS child_key, {$table}.row_hash")
            ->get();

        return $rows
            ->mapWithKeys(fn ($row) => ["{$row->numero}|{$row->child_key}" => $row->row_hash ?? '__EXISTS_NO_HASH__'])
            ->all();
    }
}
