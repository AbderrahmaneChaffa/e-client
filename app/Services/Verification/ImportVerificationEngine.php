<?php

namespace App\Services\Verification;

use App\Models\ImportBatch;
use App\Services\ImportVerificationService;
use InvalidArgumentException;

class ImportVerificationEngine
{
    private array $rules = [
        'tva_coherence',
        'paiement_coherence',
        'negative_amounts',
        'overpaid_invoices',
        'duplicate_payments',
        'orphan_invoices',
    ];

    public function __construct(private readonly ImportVerificationService $service)
    {
    }

    public function runAll(int $batchId): ImportVerificationReport
    {
        $batch = ImportBatch::findOrFail($batchId);
        $summary = $this->service->verify($batch);

        $results = [
            new RuleResult('all', $summary['critical'] > 0 ? 'critical' : ($summary['warning'] > 0 ? 'warning' : 'info'), $summary['affected_invoices'], $summary),
        ];

        return new ImportVerificationReport($batchId, $results);
    }

    public function runRule(string $rule, int $batchId): RuleResult
    {
        if (! in_array($rule, $this->rules, true)) {
            throw new InvalidArgumentException("Règle inconnue: {$rule}");
        }

        $report = $this->runAll($batchId);

        return new RuleResult($rule, $report->criticalCount() > 0 ? 'critical' : 'info', $report->criticalCount());
    }
}
