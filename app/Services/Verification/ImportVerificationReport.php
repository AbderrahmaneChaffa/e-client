<?php

namespace App\Services\Verification;

class ImportVerificationReport
{
    /**
     * @param array<int,RuleResult> $results
     */
    public function __construct(public readonly int $batchId, public readonly array $results)
    {
    }

    public function criticalCount(): int
    {
        return collect($this->results)
            ->where('severity', 'critical')
            ->sum('affectedCount');
    }
}
