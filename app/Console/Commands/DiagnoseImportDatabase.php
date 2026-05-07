<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseImportDatabase extends Command
{
    protected $signature = 'epo:diagnose-import-db';
    protected $description = 'Affiche les index import et l’état des row_hash.';

    public function handle(): int
    {
        foreach (['factures', 'prestations', 'paiements', 'clients', 'navires'] as $table) {
            $this->info("INDEX {$table}");
            $indexes = DB::connection()->getSchemaBuilder()->getIndexes($table);
            $this->table(['name', 'columns', 'unique'], array_map(fn ($index) => [
                $index['name'] ?? '',
                implode(',', $index['columns'] ?? []),
                ($index['unique'] ?? false) ? 'yes' : 'no',
            ], $indexes));
        }

        $hashes = DB::select("
            SELECT 'factures' as t, COUNT(*) as total,
                SUM(CASE WHEN row_hash IS NULL THEN 1 ELSE 0 END) as sans_hash,
                SUM(CASE WHEN row_hash IS NOT NULL THEN 1 ELSE 0 END) as avec_hash
            FROM factures
            UNION ALL
            SELECT 'prestations', COUNT(*),
                SUM(CASE WHEN row_hash IS NULL THEN 1 ELSE 0 END),
                SUM(CASE WHEN row_hash IS NOT NULL THEN 1 ELSE 0 END)
            FROM prestations
            UNION ALL
            SELECT 'paiements', COUNT(*),
                SUM(CASE WHEN row_hash IS NULL THEN 1 ELSE 0 END),
                SUM(CASE WHEN row_hash IS NOT NULL THEN 1 ELSE 0 END)
            FROM paiements
        ");

        $this->info('ETAT ROW_HASH');
        $this->table(['table', 'total', 'sans_hash', 'avec_hash'], array_map(fn ($row) => [
            $row->t,
            $row->total,
            $row->sans_hash,
            $row->avec_hash,
        ], $hashes));

        $duplicates = DB::table('prestations')
            ->select('facture_id', 'article', DB::raw('COUNT(*) as total'))
            ->groupBy('facture_id', 'article')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->warn('DOUBLONS prestations détectés: impossible de créer un index unique facture_id/article sans nettoyage.');
            $this->table(['facture_id', 'article', 'doublons'], $duplicates->map(fn ($row) => [
                $row->facture_id,
                $row->article,
                $row->total,
            ])->all());
        }

        return self::SUCCESS;
    }
}
