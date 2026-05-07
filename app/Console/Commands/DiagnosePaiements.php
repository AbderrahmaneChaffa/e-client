<?php

namespace App\Console\Commands;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\Facture;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class DiagnosePaiements extends Command
{
    protected $signature = 'epo:diagnose-paiements {fichier_paiements}';
    protected $description = 'Compare les numéros de facture du fichier paiements avec la base.';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('fichier_paiements'));

        if (! is_file($path)) {
            $this->error("Fichier introuvable: {$path}");
            return self::FAILURE;
        }

        $reader = new class implements ToCollection, WithHeadingRow {
            use Importable, ParsesExcelData;

            /** @var array<string,bool> */
            public array $factures = [];
            /** @var array<int,string> */
            public array $keys = [];

            public function collection(Collection $rows): void
            {
                if ($this->keys === []) {
                    $this->keys = array_keys($rows->first()?->toArray() ?? []);
                }

                foreach ($rows as $row) {
                    $numero = trim((string) $this->cellValue($row, 'facture', ''));

                    if ($numero !== '') {
                        $this->factures[$numero] = true;
                    }
                }
            }
        };

        Excel::import($reader, $path);

        $numeros = array_keys($reader->factures);
        $found = Facture::query()
            ->whereIn('numero_facture', $numeros)
            ->pluck('numero_facture')
            ->all();
        $found = array_values(array_unique($found));

        $foundMap = array_fill_keys($found, true);
        $missing = array_values(array_filter($numeros, fn ($numero) => ! isset($foundMap[$numero])));

        $this->info('Colonnes détectées: '.implode(', ', $reader->keys));
        $this->table(['Métrique', 'Valeur'], [
            ['Factures uniques dans fichier', count($numeros)],
            ['Trouvées en base', count($found)],
            ['Introuvables', count($missing)],
        ]);

        $this->line('Exemples introuvables: '.implode(', ', array_slice($missing, 0, 10)));
        $this->line('Exemples présentes en base: '.implode(', ', array_slice($found, 0, 5)));

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        foreach ([
            storage_path("app/private/{$path}"),
            storage_path("app/{$path}"),
            base_path($path),
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $path;
    }
}
