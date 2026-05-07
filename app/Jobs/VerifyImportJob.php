<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\ImportVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerifyImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 2;

    /**
     * @param array<int> $relatedBatchIds
     */
    public function __construct(
        private readonly ?int $batchId = null,
        private readonly array $relatedBatchIds = [],
    ) {
    }

    public function handle(ImportVerificationService $verifier): void
    {
        $batch = $this->batchId ? ImportBatch::find($this->batchId) : null;

        foreach ($this->relatedBatchIds as $id) {
            Cache::put("import_verification_{$id}", ['status' => 'processing'], now()->addHour());
        }

        $summary = $verifier->verify($batch, $this->relatedBatchIds);

        foreach ($this->relatedBatchIds as $id) {
            Cache::put("import_verification_{$id}", ['status' => 'completed', ...$summary], now()->addHour());
        }
    }

    public function failed(\Throwable $e): void
    {
        foreach ($this->relatedBatchIds as $id) {
            Cache::put("import_verification_{$id}", [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], now()->addHour());
        }

        Log::channel('imports')->error('Import verification job failed', [
            'batch_id' => $this->batchId,
            'related_batch_ids' => $this->relatedBatchIds,
            'message' => $e->getMessage(),
        ]);
    }
}
