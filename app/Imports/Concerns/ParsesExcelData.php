<?php

// app/Imports/Concerns/ParsesExcelData.php
namespace App\Imports\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

trait ParsesExcelData
{
    private function cellValue(mixed $row, string $heading, mixed $default = null): mixed
    {
        $candidates = array_unique([
            $heading,
            strtoupper($heading),
            strtolower($heading),
            $this->normalizeHeading($heading),
        ]);

        foreach ($candidates as $candidate) {
            if (is_array($row) && array_key_exists($candidate, $row)) {
                return $row[$candidate];
            }

            if ($row instanceof \ArrayAccess && isset($row[$candidate])) {
                return $row[$candidate];
            }
        }

        $items = $row instanceof \Illuminate\Support\Collection ? $row->all() : (array) $row;
        $normalizedHeading = $this->normalizeHeading($heading);

        foreach ($items as $key => $value) {
            if ($this->normalizeHeading((string) $key) === $normalizedHeading) {
                return $value;
            }
        }

        return $default;
    }

    private function normalizeHeading(string $heading): string
    {
        $heading = trim($heading);
        $heading = strtr($heading, [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
        ]);

        $heading = strtolower($heading);
        $heading = preg_replace('/[^a-z0-9]+/', '_', $heading) ?? $heading;

        return trim($heading, '_');
    }

    /**
     * Convertit "128 661,48" / "128.661,48" / 128661.48 en float.
     */
    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = (string) $value;

        // Supprime espaces ordinaires, insécables (U+00A0), fines (U+202F)
        $str = preg_replace('/[\x20\xA0\x{202F}]/u', '', $str);

        if ($str === '' || $str === '-') {
            return 0.0;
        }

        // Format européen "1.234,56" → supprime point, virgule → point
        if (preg_match('/\d+\.\d{3},\d+/', $str)) {
            $str = str_replace(['.', ','], ['', '.'], $str);
        }
        // Format US "1,234.56" → supprime virgule
        elseif (preg_match('/\d+,\d{3}\.\d+/', $str)) {
            $str = str_replace(',', '', $str);
        }
        // Cas simple : virgule = décimale
        else {
            $str = str_replace(',', '.', $str);
        }

        return is_numeric($str) ? (float) $str : 0.0;
    }

    /**
     * Convertit une valeur de cellule Excel en Carbon.
     *
     * Gère TROIS cas :
     *   1. Numéro de série Excel (ex: 46027) → cellule Date native Excel
     *   2. Chaîne formatée FR (ex: "31/12/2025") → texte brut dans l'ERP
     *   3. Chaîne ISO ou autre format → parsing Carbon libre
     *
     * Ne lève JAMAIS d'exception. Retourne null si non parseable.
     */
    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        if ($str === '' || $str === '-') {
            return null;
        }

        // ── CAS 1 : numéro de série Excel (ex: "46027", "45657") ──────────
        // StringValueBinder expose le nombre brut des cellules de type Date.
        // Plage valide : >1000 (évite les petits entiers comme "1,00") et
        // <200000 (après l'an 2400 environ).
        if (
            is_numeric($str)
            && strpos($str, ',') === false
            && strpos($str, '.') === false
            && (int) $str > 1000
            && (int) $str < 200000
        ) {
            try {
                // PhpSpreadsheet fournit un convertisseur officiel
                $timestamp = ExcelDate::excelToTimestamp((int) $str);
                return Carbon::createFromTimestamp($timestamp)->startOfDay();
            } catch (\Exception) {
                // Continue vers les autres cas
            }
        }

        // ── CAS 2 : format JJ/MM/AAAA (format ERP BIG) ───────────────────
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $str)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $str)->startOfDay();
            } catch (\Exception) {
                // Continue
            }
        }

        // ── CAS 3 : parsing libre (ISO 8601, etc.) ─────────────────────────
        try {
            $date = Carbon::parse($str)->startOfDay();
            // Sanity check : rejet des dates aberrantes
            if ($date->year < 1990 || $date->year > 2100) {
                return null;
            }
            return $date;
        } catch (\Exception) {
            return null;
        }
    }
}
