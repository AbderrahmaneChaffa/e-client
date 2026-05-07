<?php

namespace App\Services\Verification;

class RuleResult
{
    public function __construct(
        public readonly string $rule,
        public readonly string $severity,
        public readonly int $affectedCount,
        public readonly array $details = [],
    ) {
    }
}
