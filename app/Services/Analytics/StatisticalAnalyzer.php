<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;

class StatisticalAnalyzer
{
    /**
     * @return array<int|string,float>
     */
    public function zScore(array $values): array
    {
        $numbers = array_map('floatval', array_values($values));
        $count = count($numbers);

        if ($count === 0) {
            return [];
        }

        $mean = array_sum($numbers) / $count;
        $variance = array_sum(array_map(fn ($value) => ($value - $mean) ** 2, $numbers)) / $count;
        $std = sqrt($variance);

        if ($std == 0.0) {
            return array_fill_keys(array_keys($values), 0.0);
        }

        $scores = [];

        foreach ($values as $key => $value) {
            $scores[$key] = round(((float) $value - $mean) / $std, 4);
        }

        return $scores;
    }

    /**
     * @return array<int|string,float>
     */
    public function iqrOutliers(array $values, float $factor = 1.5): array
    {
        if (count($values) < 4) {
            return [];
        }

        $sorted = array_values(array_map('floatval', $values));
        sort($sorted);

        $q1 = $this->percentile($sorted, 25);
        $q3 = $this->percentile($sorted, 75);
        $iqr = $q3 - $q1;
        $min = $q1 - ($factor * $iqr);
        $max = $q3 + ($factor * $iqr);
        $outliers = [];

        foreach ($values as $key => $value) {
            $amount = (float) $value;

            if ($amount < $min || $amount > $max) {
                $outliers[$key] = $amount;
            }
        }

        return $outliers;
    }

    public function isSuspicious(float $amount, string $clientCode): bool
    {
        $history = DB::table('factures')
            ->join('clients', 'clients.id', '=', 'factures.client_id')
            ->where('clients.code_client', $clientCode)
            ->where('factures.total_ttc', '>', 0)
            ->orderByDesc('factures.id')
            ->limit(200)
            ->pluck('factures.total_ttc')
            ->map(fn ($value) => (float) $value)
            ->all();

        if (count($history) < 10) {
            return false;
        }

        $scores = $this->zScore([...$history, 'current' => $amount]);

        return abs($scores['current'] ?? 0) >= 3.0;
    }

    private function percentile(array $sorted, float $percentile): float
    {
        $index = ($percentile / 100) * (count($sorted) - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return $sorted[$lower];
        }

        return $sorted[$lower] + (($sorted[$upper] - $sorted[$lower]) * ($index - $lower));
    }
}
