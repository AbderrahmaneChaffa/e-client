<?php

namespace App\Imports\Concerns;

use App\Models\Facture;
use Illuminate\Support\Facades\DB;

trait LoadsExistingImportState
{
    /**
     * @param array<int,string> $numeros
     * @param array<string,int|null> $cache
     */
    private function preloadFactureIds(array $numeros, array &$cache, bool $onlyActive = false): void
    {
        $newNumeros = array_values(array_diff($numeros, array_keys($cache)));

        if ($newNumeros === []) {
            return;
        }

        $found = Facture::query()
            ->when($onlyActive, fn ($query) => $query->where('annuler', 0))
            ->whereIn('numero_facture', $newNumeros)
            ->pluck('id', 'numero_facture')
            ->all();

        foreach ($newNumeros as $numero) {
            $cache[$numero] = isset($found[$numero]) ? (int) $found[$numero] : null;
        }
    }

    /**
     * @param array<int,array{facture_id:int|string|null, key:string|null}> $pairs
     * @return array<string,string>
     */
    private function existingHashesForFacturePairs(string $table, string $keyColumn, array $pairs): array
    {
        return collect($this->existingRowsForFacturePairs($table, $keyColumn, $pairs))
            ->map(fn ($row) => $row->row_hash ?? '__EXISTS_NO_HASH__')
            ->all();
    }

    /**
     * @param array<int,array{facture_id:int|string|null, key:string|null}> $pairs
     * @param array<int,string> $columns
     * @return array<string,object>
     */
    private function existingRowsForFacturePairs(string $table, string $keyColumn, array $pairs, array $columns = []): array
    {
        if (! in_array($table, ['prestations', 'paiements'], true)
            || ! in_array($keyColumn, ['article', 'recu'], true)) {
            throw new \InvalidArgumentException('Import hash lookup cible invalide.');
        }

        $requested = [];

        foreach ($pairs as $pair) {
            $factureId = (int) ($pair['facture_id'] ?? 0);
            $key = trim((string) ($pair['key'] ?? ''));

            if ($factureId <= 0 || $key === '') {
                continue;
            }

            $requested[$factureId.'|'.$key] = [
                'facture_id' => $factureId,
                'key' => $key,
            ];
        }

        if ($requested === []) {
            return [];
        }

        $columns = $this->safeImportStateColumns($table, $keyColumn, $columns);

        return DB::connection()->getDriverName() === 'mysql'
            ? $this->existingRowsForMysqlPairs($table, $keyColumn, array_values($requested), $columns)
            : $this->existingRowsForPortablePairs($table, $keyColumn, $requested, $columns);
    }

    /**
     * @param array<int,array{facture_id:int,key:string}> $pairs
     * @param array<int,string> $columns
     * @return array<string,object>
     */
    private function existingRowsForMysqlPairs(string $table, string $keyColumn, array $pairs, array $columns): array
    {
        $rows = [];

        foreach (array_chunk($pairs, 500) as $chunk) {
            $bindings = [];
            $placeholders = [];

            foreach ($chunk as $pair) {
                $placeholders[] = '(?, ?)';
                $bindings[] = $pair['facture_id'];
                $bindings[] = $pair['key'];
            }

            DB::table($table)
                ->whereRaw('(facture_id, '.$keyColumn.') IN ('.implode(', ', $placeholders).')', $bindings)
                ->select($columns)
                ->get()
                ->each(function ($row) use (&$rows, $keyColumn) {
                    $rows[$row->facture_id.'|'.$row->{$keyColumn}] = $row;
                });
        }

        return $rows;
    }

    /**
     * @param array<string,array{facture_id:int,key:string}> $requested
     * @param array<int,string> $columns
     * @return array<string,object>
     */
    private function existingRowsForPortablePairs(string $table, string $keyColumn, array $requested, array $columns): array
    {
        $factureIds = array_values(array_unique(array_column($requested, 'facture_id')));
        $keys = array_values(array_unique(array_column($requested, 'key')));
        $rows = [];

        DB::table($table)
            ->whereIn('facture_id', $factureIds)
            ->whereIn($keyColumn, $keys)
            ->select($columns)
            ->get()
            ->each(function ($row) use (&$rows, $requested, $keyColumn) {
                $compound = $row->facture_id.'|'.$row->{$keyColumn};

                if (isset($requested[$compound])) {
                    $rows[$compound] = $row;
                }
            });

        return $rows;
    }

    /**
     * @param array<int,string> $columns
     * @return array<int,string>
     */
    private function safeImportStateColumns(string $table, string $keyColumn, array $columns): array
    {
        $allowed = [
            'prestations' => [
                'facture_id',
                'article',
                'libelle',
                'quantite',
                'prix_unitaire',
                'taux_ht',
                'total_ht',
                'total_tva',
                'total_ttc',
                'row_hash',
            ],
            'paiements' => [
                'facture_id',
                'recu',
                'montant',
                'date_paiement',
                'numero_cheque',
                'banque',
                'facture_anterieur',
                'row_hash',
            ],
        ];

        return array_values(array_unique(array_filter([
            'facture_id',
            $keyColumn,
            'row_hash',
            ...$columns,
        ], fn ($column) => in_array($column, $allowed[$table] ?? [], true))));
    }
}
