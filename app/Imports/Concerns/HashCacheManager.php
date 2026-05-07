<?php

namespace App\Imports\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HashCacheManager
{
    protected const EXISTS_NO_HASH = '__EXISTS_NO_HASH__';

    /** @var array<string,string> */
    protected array $hashCache = [];
    protected bool $hashLoaded = false;
    protected int $hashHits = 0;
    protected int $hashMisses = 0;

    protected function loadHashes(string $table, array $keyColumns): void
    {
        if ($this->hashLoaded) {
            return;
        }

        DB::table($table)
            ->select(array_merge($keyColumns, ['row_hash']))
            ->orderBy($keyColumns[0])
            ->chunk(10000, function ($rows) use ($keyColumns): void {
                foreach ($rows as $row) {
                    $key = implode('|', array_map(fn ($column) => $row->{$column}, $keyColumns));
                    $this->hashCache[$key] = $row->row_hash ?? self::EXISTS_NO_HASH;
                }
            });

        $this->hashLoaded = true;

        Log::channel('imports')->info('HashCache chargé', [
            'table' => $table,
            'count' => count($this->hashCache),
        ]);
    }

    protected function hasChanged(string $key, string $newHash): bool
    {
        $existing = $this->hashCache[$key] ?? null;

        if ($existing === null) {
            $this->hashCache[$key] = $newHash;
            $this->hashMisses++;
            return true;
        }

        if ($existing === self::EXISTS_NO_HASH || $existing !== $newHash) {
            $this->hashCache[$key] = $newHash;
            $this->hashMisses++;
            return true;
        }

        $this->hashHits++;

        return false;
    }
}
