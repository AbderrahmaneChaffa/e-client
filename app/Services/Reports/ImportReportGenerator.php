<?php

namespace App\Services\Reports;

use App\Models\ImportBatch;

class ImportReportGenerator
{
    public function generateSummary(ImportBatch $batch): string
    {
        return view('reports.import-summary', compact('batch'))->render();
    }
}
