<?php

namespace App\Imports\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait TracksImportTouchedFactures
{
    /**
     * @param array<int,int|string|null> $factureIds
     */
    private function recordTouchedFactureIds(array $factureIds): void
    {
        if (! Schema::hasTable('import_batch_factures')) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $factureIds))));

        if ($ids === []) {
            return;
        }

        $now = now()->toDateTimeString();
        $records = array_map(fn (int $factureId) => [
            'import_batch_id' => $this->batch->id,
            'facture_id' => $factureId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        foreach (array_chunk($records, 1000) as $chunk) {
            DB::table('import_batch_factures')->insertOrIgnore($chunk);
        }
    }
}
