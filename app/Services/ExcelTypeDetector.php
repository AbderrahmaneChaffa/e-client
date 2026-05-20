<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use RuntimeException;

class ExcelTypeDetector
{
    private const ORDER = [
        'factures',
        'prestations',
        'paiements',
        'factures_payees',
        'prestations_payees',
    ];

    private const HEADERS = [
        'factures' => [
            'facture', 'date', 'code_client', 'nom_client', 'adresse', 'rc', 'nis', 'ai', 'nif',
            'paiement', 'entree', 'sortie', 'bordereau', 'description', 'navire', 'pavillon',
            'pour', 'total_ht', 'total_tva', 'total_ttc', 'reste', 'devise', 'taux_devise',
            'user', 'annule',
        ],
        'prestations' => [
            'facture', 'article', 'libelle', 'quantite', 'prix', 'taux_ht',
            'total_ht', 'total_tva', 'total_ttc',
        ],
        'paiements' => [
            'recu', 'date', 'code_client', 'nom_client', 'facture', 'facture_anterieur',
            'total_ttc', 'cheque', 'banque', 'paye', 'reste',
        ],
    ];

    public function detect(UploadedFile|string $file): string
    {
        $inspection = $this->inspect($file, sampleRows: 2);

        if (! $inspection['type']) {
            throw new RuntimeException('Type de fichier Excel non reconnu.');
        }

        return $inspection['type'];
    }

    /**
     * @return array{
     *     type:?string,
     *     confidence:float,
     *     ambiguous:bool,
     *     row_count:int,
     *     found_headers:array<int,string>,
     *     normalized_headers:array<int,string>,
     *     expected_headers:array<int,string>,
     *     missing_headers:array<int,string>,
     *     extra_headers:array<int,string>,
     *     sample_rows:array<int,array<string,mixed>>
     * }
     */
    public function inspect(UploadedFile|string $file, int $sampleRows = 5): array
    {
        $path = $this->path($file);
        $filename = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $sheetInfo = $this->worksheetInfo($path);
        [$headers, $samples] = $this->headersAndSamples($path, $sampleRows);

        $normalized = collect($headers)
            ->map(fn ($header) => self::normalizeHeader((string) $header))
            ->filter()
            ->values()
            ->all();

        [$type, $confidence, $ambiguous] = $this->resolveType($normalized, $filename, $samples);
        $expected = $type ? $this->expectedHeaders($type) : [];

        return [
            'type' => $type,
            'confidence' => $confidence,
            'ambiguous' => $ambiguous,
            'row_count' => max(0, (int) ($sheetInfo['totalRows'] ?? 1) - 1),
            'found_headers' => array_values(array_filter(array_map('strval', $headers), fn ($header) => trim($header) !== '')),
            'normalized_headers' => $normalized,
            'expected_headers' => $expected,
            'missing_headers' => array_values(array_diff($expected, $normalized)),
            'extra_headers' => array_values(array_diff($normalized, $expected)),
            'sample_rows' => $samples,
        ];
    }

    /**
     * @return array<int,string>
     */
    public function importOrder(): array
    {
        return self::ORDER;
    }

    /**
     * @return array<int,string>
     */
    public function expectedHeaders(string $type): array
    {
        return self::HEADERS[$this->baseType($type)] ?? [];
    }

    public function baseType(string $type): string
    {
        return match ($type) {
            'factures_payees' => 'factures',
            'prestations_payees' => 'prestations',
            default => $type,
        };
    }

    public static function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = strtr($header, [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
        ]);

        $header = Str::ascii($header);
        $header = strtolower($header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    private function path(UploadedFile|string $file): string
    {
        return $file instanceof UploadedFile ? (string) $file->getRealPath() : $file;
    }

    private function worksheetInfo(string $path): array
    {
        $reader = IOFactory::createReader(IOFactory::identify($path));
        $reader->setReadDataOnly(true);

        return $reader->listWorksheetInfo($path)[0] ?? ['totalRows' => 0, 'totalColumns' => 0];
    }

    /**
     * @return array{0:array<int,string>,1:array<int,array<string,mixed>>}
     */
    private function headersAndSamples(string $path, int $sampleRows): array
    {
        $reader = IOFactory::createReader(IOFactory::identify($path));
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class($sampleRows + 1) implements IReadFilter {
            public function __construct(private readonly int $maxRow)
            {
            }

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->maxRow;
            }
        });

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = min($sheet->getHighestDataRow(), $sampleRows + 1);
        $rows = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false, false);
        $spreadsheet->disconnectWorksheets();

        $headers = array_map(fn ($header) => trim((string) $header), $rows[0] ?? []);
        $samples = [];

        foreach (array_slice($rows, 1) as $row) {
            $sample = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $sample[$header] = $row[$index] ?? null;
            }

            if (array_filter($sample, fn ($value) => $value !== null && $value !== '') !== []) {
                $samples[] = $sample;
            }
        }

        return [$headers, $samples];
    }

    /**
     * @param array<int,string> $normalizedHeaders
     * @param array<int,array<string,mixed>> $samples
     * @return array{0:?string,1:float,2:bool}
     */
    private function resolveType(array $normalizedHeaders, string $filename, array $samples): array
    {
        $scores = [];

        foreach (self::HEADERS as $type => $expected) {
            $matched = count(array_intersect($expected, $normalizedHeaders));
            $scores[$type] = $matched / max(count($expected), 1);
        }

        arsort($scores);
        $baseType = array_key_first($scores);
        $confidence = (float) current($scores);
        $second = (float) (array_values($scores)[1] ?? 0);

        if ($confidence < 0.55) {
            return [null, $confidence, false];
        }

        $ambiguous = abs($confidence - $second) < 0.05;
        $normalizedFilename = self::normalizeHeader($filename);
        $looksPaid = str_contains($normalizedFilename, 'payee')
            || str_contains($normalizedFilename, 'payees')
            || str_contains($normalizedFilename, 'paye');

        $type = match ($baseType) {
            'factures' => $looksPaid || $this->allSamplesMarkedPaid($samples) ? 'factures_payees' : 'factures',
            'prestations' => $looksPaid ? 'prestations_payees' : 'prestations',
            default => $baseType,
        };

        return [$type, $confidence, $ambiguous || in_array($baseType, ['factures', 'prestations'], true) && ! $looksPaid];
    }

    /**
     * @param array<int,array<string,mixed>> $samples
     */
    private function allSamplesMarkedPaid(array $samples): bool
    {
        if ($samples === []) {
            return false;
        }

        foreach ($samples as $row) {
            foreach ($row as $header => $value) {
                if (self::normalizeHeader((string) $header) === 'annule' && trim((string) $value) !== '0') {
                    return false;
                }
            }
        }

        return false;
    }
}
