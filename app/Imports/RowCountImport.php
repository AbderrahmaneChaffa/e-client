<?php

// app/Imports/RowCountImport.php
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RowCountImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $count = 0;

    public function collection(Collection $rows): void
    {
        $this->count += $rows->count();
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
