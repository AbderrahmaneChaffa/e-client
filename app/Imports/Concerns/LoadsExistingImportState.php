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

        return DB::connection()->getDriverName() === 'mysql'
            ? $this->existingHashesForMysqlPairs($table, $keyColumn, array_values($requested))
            : $this->existingHashesForPortablePairs($table, $keyColumn, $requested);
    }

    /**
     * @param array<int,array{facture_id:int,key:string}> $pairs
     * @return array<string,string>
     */
    private function existingHashesForMysqlPairs(string $table, string $keyColumn, array $pairs): array
    {
        $hashes = [];

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
                ->select('facture_id', $keyColumn, 'row_hash')
                ->get()
                ->each(function ($row) use (&$hashes, $keyColumn) {
                    $hashes[$row->facture_id.'|'.$row->{$keyColumn}] = $row->row_hash ?? '__EXISTS_NO_HASH__';
                });
        }

        return $hashes;
    }

    /**
     * @param array<string,array{facture_id:int,key:string}> $requested
     * @return array<string,string>
     */
    private function existingHashesForPortablePairs(string $table, string $keyColumn, array $requested): array
    {
        $factureIds = array_values(array_unique(array_column($requested, 'facture_id')));
        $keys = array_values(array_unique(array_column($requested, 'key')));
        $hashes = [];

        DB::table($table)
            ->whereIn('facture_id', $factureIds)
            ->whereIn($keyColumn, $keys)
            ->select('facture_id', $keyColumn, 'row_hash')
            ->get()
            ->each(function ($row) use (&$hashes, $requested, $keyColumn) {
                $compound = $row->facture_id.'|'.$row->{$keyColumn};

                if (isset($requested[$compound])) {
                    $hashes[$compound] = $row->row_hash ?? '__EXISTS_NO_HASH__';
                }
            });

        return $hashes;
    }
}
