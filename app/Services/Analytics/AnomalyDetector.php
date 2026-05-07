<?php

namespace App\Services\Analytics;

class AnomalyDetector
{
    public function __construct(private readonly StatisticalAnalyzer $statistics = new StatisticalAnalyzer())
    {
    }

    /**
     * @return array<int|string,float>
     */
    public function detectOutliers(array $amounts): array
    {
        $scores = $this->statistics->zScore($amounts);
        $outliers = [];

        foreach ($scores as $key => $score) {
            if (abs($score) >= 3.0) {
                $outliers[$key] = (float) $amounts[$key];
            }
        }

        return $outliers ?: $this->statistics->iqrOutliers($amounts);
    }
}
