<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportRowHasher
{
    private const COLUMNS = [
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

    private const AMOUNT_COLUMNS = [
        'total_ht', 'total_tva', 'total_ttc', 'reste', 'taux_devise',
        'quantite', 'prix', 'taux_ht', 'paye',
    ];

    private const DATE_COLUMNS = ['date', 'entree', 'sortie'];

    public static function hash(string $type, mixed $row): string
    {
        $baseType = match ($type) {
            'factures_payees' => 'factures',
            'prestations_payees' => 'prestations',
            default => $type,
        };

        $payload = [];

        foreach (self::COLUMNS[$baseType] ?? [] as $column) {
            $payload[$column] = self::canonicalValue($column, self::value($row, $column));
        }

        return md5(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function key(string $type, mixed $row): ?string
    {
        return match (match ($type) {
            'factures_payees' => 'factures',
            'prestations_payees' => 'prestations',
            default => $type,
        }) {
            'factures' => self::blankToNull(self::canonicalText(self::value($row, 'facture'))),
            'prestations' => self::compoundKey(self::value($row, 'facture'), self::value($row, 'article')),
            'paiements' => self::compoundKey(self::value($row, 'facture'), self::value($row, 'recu')),
            default => null,
        };
    }

    public static function value(mixed $row, string $header, mixed $default = null): mixed
    {
        $items = $row instanceof Collection ? $row->all() : (array) $row;
        $normalizedHeader = ExcelTypeDetector::normalizeHeader($header);

        foreach ($items as $key => $value) {
            if (ExcelTypeDetector::normalizeHeader((string) $key) === $normalizedHeader) {
                return $value;
            }
        }

        return $default;
    }

    private static function canonicalValue(string $column, mixed $value): string
    {
        if (in_array($column, self::AMOUNT_COLUMNS, true)) {
            return number_format(self::parseAmount($value), 2, '.', '');
        }

        if (in_array($column, self::DATE_COLUMNS, true)) {
            return self::canonicalDate($value);
        }

        return self::canonicalText($value);
    }

    private static function canonicalText(mixed $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        return strtolower(Str::ascii($value));
    }

    private static function compoundKey(mixed $left, mixed $right): ?string
    {
        $left = self::blankToNull(self::canonicalText($left));
        $right = self::blankToNull(self::canonicalText($right));

        return $left && $right ? "{$left}|{$right}" : null;
    }

    private static function blankToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private static function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = preg_replace('/[\x20\xA0\x{202F}]/u', '', (string) $value) ?? '';

        if ($str === '' || $str === '-') {
            return 0.0;
        }

        if (preg_match('/\d+\.\d{3},\d+/', $str)) {
            $str = str_replace(['.', ','], ['', '.'], $str);
        } elseif (preg_match('/\d+,\d{3}\.\d+/', $str)) {
            $str = str_replace(',', '', $str);
        } else {
            $str = str_replace(',', '.', $str);
        }

        return is_numeric($str) ? (float) $str : 0.0;
    }

    private static function canonicalDate(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        $str = trim((string) $value);

        if (is_numeric($str) && ! str_contains($str, '.') && ! str_contains($str, ',') && (int) $str > 1000) {
            try {
                return Carbon::createFromTimestamp(ExcelDate::excelToTimestamp((int) $str))->format('Y-m-d');
            } catch (\Throwable) {
                return $str;
            }
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $str)->format('Y-m-d');
            } catch (\Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return strtolower($str);
        }
    }
}
