<?php

namespace App\Imports\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ResolvesInvoiceImportRelations
{
    /** @var array<string,int> */
    private array $clientIdCache = [];

    /** @var array<string,int> */
    private array $navireIdCache = [];

    /** @var array<int,int> */
    private array $escaleIdCache = [];

    private function resolveInvoiceImportRelations(Collection $rows): void
    {
        $this->warmClientIds($rows);
        $this->warmNavireIds($rows);
        $this->warmEscaleIds($rows);
    }

    private function clientIdForInvoiceImportRow(mixed $row): ?int
    {
        $codeClient = $this->clientCodeFromInvoiceRow($row);

        return $codeClient !== '' ? ($this->clientIdCache[$codeClient] ?? null) : null;
    }

    private function escaleIdForInvoiceImportRow(mixed $row): ?int
    {
        $navireId = $this->navireIdCache[$this->navireKeyFromInvoiceRow($row)] ?? null;

        return $navireId ? ($this->escaleIdCache[$navireId] ?? null) : null;
    }

    private function warmClientIds(Collection $rows): void
    {
        $firstRowsByCode = [];

        foreach ($rows as $row) {
            $codeClient = $this->clientCodeFromInvoiceRow($row);

            if ($codeClient === '' || isset($this->clientIdCache[$codeClient]) || isset($firstRowsByCode[$codeClient])) {
                continue;
            }

            $firstRowsByCode[$codeClient] = $row;
        }

        $codes = array_values(array_diff(array_keys($firstRowsByCode), array_keys($this->clientIdCache)));

        if ($codes === []) {
            return;
        }

        DB::table('clients')
            ->whereIn('code_client', $codes)
            ->select('id', 'code_client')
            ->get()
            ->each(function ($client): void {
                $this->clientIdCache[$client->code_client] = (int) $client->id;
            });

        $missingCodes = array_values(array_diff($codes, array_keys($this->clientIdCache)));

        if ($missingCodes !== []) {
            $now = now()->toDateTimeString();
            $records = [];

            foreach ($missingCodes as $codeClient) {
                $row = $firstRowsByCode[$codeClient];
                $records[] = [
                    'code_client' => $codeClient,
                    'name' => trim((string) $this->cellValue($row, 'nom_client', '')),
                    'adresse' => trim((string) $this->cellValue($row, 'adresse', '')),
                    'rc' => trim((string) $this->cellValue($row, 'rc', '')),
                    'nis' => trim((string) $this->cellValue($row, 'nis', '')),
                    'ai' => trim((string) $this->cellValue($row, 'ai', '')),
                    'nif' => trim((string) $this->cellValue($row, 'nif', '')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('clients')->insertOrIgnore($records);

            DB::table('clients')
                ->whereIn('code_client', $missingCodes)
                ->select('id', 'code_client')
                ->get()
                ->each(function ($client): void {
                    $this->clientIdCache[$client->code_client] = (int) $client->id;
                });
        }
    }

    private function warmNavireIds(Collection $rows): void
    {
        $firstRowsByKey = [];
        $names = [];
        $pavillons = [];

        foreach ($rows as $row) {
            $key = $this->navireKeyFromInvoiceRow($row);

            if (isset($this->navireIdCache[$key]) || isset($firstRowsByKey[$key])) {
                continue;
            }

            [$name, $pavillon] = explode('|', $key, 2);
            $firstRowsByKey[$key] = $row;
            $names[$name] = true;
            $pavillons[$pavillon] = true;
        }

        $keys = array_values(array_diff(array_keys($firstRowsByKey), array_keys($this->navireIdCache)));

        if ($keys === []) {
            return;
        }

        DB::table('navires')
            ->whereIn('nom', array_keys($names))
            ->whereIn('pavillon', array_keys($pavillons))
            ->select('id', 'nom', 'pavillon')
            ->get()
            ->each(function ($navire): void {
                $this->navireIdCache[$this->navireKey((string) $navire->nom, (string) $navire->pavillon)] = (int) $navire->id;
            });

        $missingKeys = array_values(array_diff($keys, array_keys($this->navireIdCache)));

        if ($missingKeys !== []) {
            $now = now()->toDateTimeString();
            $records = [];

            foreach ($missingKeys as $key) {
                [$name, $pavillon] = explode('|', $key, 2);
                $records[] = [
                    'nom' => $name,
                    'pavillon' => $pavillon,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('navires')->insert($records);

            DB::table('navires')
                ->whereIn('nom', array_column($records, 'nom'))
                ->whereIn('pavillon', array_column($records, 'pavillon'))
                ->select('id', 'nom', 'pavillon')
                ->get()
                ->each(function ($navire): void {
                    $this->navireIdCache[$this->navireKey((string) $navire->nom, (string) $navire->pavillon)] = (int) $navire->id;
                });
        }
    }

    private function warmEscaleIds(Collection $rows): void
    {
        $firstRowsByNavireId = [];

        foreach ($rows as $row) {
            $navireId = $this->navireIdCache[$this->navireKeyFromInvoiceRow($row)] ?? null;

            if ($navireId && ! isset($this->escaleIdCache[$navireId], $firstRowsByNavireId[$navireId])) {
                $firstRowsByNavireId[$navireId] = $row;
            }
        }

        $navireIds = array_values(array_diff(array_keys($firstRowsByNavireId), array_keys($this->escaleIdCache)));

        if ($navireIds === []) {
            return;
        }

        DB::table('escales')
            ->whereIn('navire_id', $navireIds)
            ->orderBy('id')
            ->select('id', 'navire_id')
            ->get()
            ->each(function ($escale): void {
                $this->escaleIdCache[(int) $escale->navire_id] ??= (int) $escale->id;
            });

        $missingNavireIds = array_values(array_diff($navireIds, array_keys($this->escaleIdCache)));

        if ($missingNavireIds !== []) {
            $now = now()->toDateTimeString();
            $records = [];

            foreach ($missingNavireIds as $navireId) {
                $row = $firstRowsByNavireId[$navireId];
                $records[] = [
                    'navire_id' => $navireId,
                    'date_arrivee' => $this->parseDate($this->cellValue($row, 'entree', ''))?->toDateString(),
                    'date_sortie' => $this->parseDate($this->cellValue($row, 'sortie', ''))?->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('escales')->insert($records);

            DB::table('escales')
                ->whereIn('navire_id', $missingNavireIds)
                ->orderBy('id')
                ->select('id', 'navire_id')
                ->get()
                ->each(function ($escale): void {
                    $this->escaleIdCache[(int) $escale->navire_id] ??= (int) $escale->id;
                });
        }
    }

    private function clientCodeFromInvoiceRow(mixed $row): string
    {
        return trim((string) $this->cellValue($row, 'code_client', ''));
    }

    private function navireKeyFromInvoiceRow(mixed $row): string
    {
        return $this->navireKey(
            trim((string) $this->cellValue($row, 'navire', 'NAVIRE INCONNU')),
            trim((string) $this->cellValue($row, 'pavillon', 'INCONNU')),
        );
    }

    private function navireKey(string $name, string $pavillon): string
    {
        return $name.'|'.$pavillon;
    }
}
