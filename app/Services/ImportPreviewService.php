<?php

namespace App\Services;

use App\Imports\PreviewStatsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ImportPreviewService
{
    public function __construct(private readonly ExcelTypeDetector $detector)
    {
    }

    public function preview(UploadedFile $file, bool $forceImport = false): array
    {
        $inspection = $this->detector->inspect($file);

        if (! $inspection['type']) {
            throw new RuntimeException('Impossible de reconnaitre ce fichier Excel.');
        }

        $statsImport = new PreviewStatsImport($inspection['type'], $forceImport);
        Excel::import($statsImport, $file->getRealPath());
        $stats = $statsImport->report();

        return [
            ...$inspection,
            'row_count' => $stats['row_count'] ?: $inspection['row_count'],
            'valid' => $inspection['missing_headers'] === [],
            'impact' => $stats['impact'],
            'totals' => $stats['totals'],
            'sample_rows' => array_slice($stats['sample_rows'] ?: $inspection['sample_rows'], 0, 5),
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
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
                'created' => $previews->sum('impact.created'),
                'updated' => $previews->sum('impact.updated'),
                'skipped' => $previews->sum('impact.skipped'),
                'total_ttc' => round($previews->sum('totals.total_ttc'), 2),
                'total_paid' => round($previews->sum('totals.paye'), 2),
            ],
        ];
    }
}
