<?php

namespace App\Services\AI;

use App\Services\Analytics\StatisticalAnalyzer;
use Illuminate\Support\Facades\Log;

class ImportAIAnalyzer
{
    public function __construct(private readonly StatisticalAnalyzer $statistics = new StatisticalAnalyzer())
    {
    }

    public function analyzeBeforeImport(array $sampleRows, string $type): array
    {
        $warnings = [];
        $amounts = [];

        foreach ($sampleRows as $index => $row) {
            $normalized = collect($row)->mapWithKeys(fn ($value, $key) => [strtolower((string) $key) => $value]);
            $ht = $this->amount($normalized['total_ht'] ?? 0);
            $ttc = $this->amount($normalized['total_ttc'] ?? 0);

            if ($ttc > 0) {
                $amounts[$index + 2] = $ttc;
            }

            if ($ht > 0 && $ttc > 0 && abs($ttc - round($ht * 1.19, 2)) > 0.01) {
                $warnings[] = [
                    'row' => $index + 2,
                    'message' => 'Le total TTC ne correspond pas à HT * 1.19 dans l’échantillon.',
                ];
            }
        }

        $outliers = $this->statistics->iqrOutliers($amounts);

        foreach ($outliers as $row => $amount) {
            $warnings[] = [
                'row' => $row,
                'message' => "Montant TTC statistiquement atypique: {$amount}.",
            ];
        }

        Log::channel('imports')->debug('Analyse locale import terminée', [
            'type' => $type,
            'warnings' => count($warnings),
            'outliers' => count($outliers),
        ]);

        return [
            'anomalies' => [],
            'warnings' => $warnings,
            'suggestions' => ['Analyse locale appliquée: aucune API externe utilisée.'],
            'type' => $type,
        ];
    }

    public function suggestColumnMapping(array $foundHeaders, array $expectedHeaders): array
    {
        $suggestions = [];

        foreach ($foundHeaders as $found) {
            $best = null;
            $bestScore = 0;

            foreach ($expectedHeaders as $expected) {
                similar_text(strtolower((string) $found), strtolower((string) $expected), $score);

                if ($score > $bestScore) {
                    $best = $expected;
                    $bestScore = $score;
                }
            }

            if ($best && $bestScore >= 70 && $found !== $best) {
                $suggestions[] = [
                    'from' => $found,
                    'to' => $best,
                    'confidence' => round($bestScore / 100, 2),
                ];
            }
        }

        return $suggestions;
    }

    private function amount(mixed $value): float
    {
        $value = preg_replace('/[\x20\xA0\x{202F}]/u', '', (string) $value) ?? '';
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
