<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RebuildRowHashes extends Command
{
    protected $signature = 'epo:rebuild-hashes';
    protected $description = 'Reconstruit les row_hash manquants sans relire les fichiers Excel.';

    public function handle(): int
    {
        foreach (['factures', 'prestations', 'paiements'] as $table) {
            if (! Schema::hasColumn($table, 'row_hash')) {
                $this->warn("{$table}: colonne row_hash absente, ignoré.");
                continue;
            }

            $this->rebuildTable($table);
        }

        return self::SUCCESS;
    }

    private function rebuildTable(string $table): void
    {
        $total = DB::table($table)->whereNull('row_hash')->count();
        $this->info("{$table}: {$total} lignes sans hash");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::table($table)
            ->whereNull('row_hash')
            ->orderBy('id')
            ->chunkById(5000, function ($rows) use ($table, $bar): void {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'id' => $row->id,
                        'row_hash' => $this->hashRow($table, $row),
                    ];
                    $bar->advance();
                }

                foreach (array_chunk($payload, 1000) as $chunk) {
                    DB::table($table)->upsert($chunk, ['id'], ['row_hash']);
                }
            });

        $bar->finish();
        $this->newLine(2);
    }

    private function hashRow(string $table, object $row): string
    {
        $values = match ($table) {
            'factures' => [
                $row->numero_facture,
                $row->client_id,
                $row->total_ht,
                $row->total_tva,
                $row->total_ttc,
                $row->reste_a_payer,
                $row->annuler,
            ],
            'prestations' => [
                $row->facture_id,
                $row->article,
                $row->quantite,
                $row->prix_unitaire,
                $row->total_ht,
                $row->total_tva,
                $row->total_ttc,
            ],
            'paiements' => [
                $row->facture_id,
                $row->recu,
                $row->montant,
                $row->numero_cheque,
                $row->banque,
            ],
            default => [],
        };

        return md5(implode('|', array_map(fn ($value) => (string) $value, $values)));
    }
}
