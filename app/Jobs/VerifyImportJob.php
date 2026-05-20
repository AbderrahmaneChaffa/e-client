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

    public const GLOBAL_STATUS_CACHE_KEY = 'import_verification_global';
    public const GLOBAL_LOCK_CACHE_KEY = 'import_verification_global_running';

    public int $timeout = 3600;
    public int $tries = 2;
    public array $backoff = [60, 300];

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
        $releaseGlobalLock = true;

        $this->putStatus([
            'status' => 'processing',
            'message' => $this->isGlobalVerification()
                ? 'Verification globale en cours.'
                : 'Verification import en cours.',
            'batch_id' => $this->batchId,
            'related_batch_ids' => $this->relatedBatchIds,
            'started_at' => now()->toIso8601String(),
            'attempt' => $this->attempts(),
        ]);

        Log::channel('imports')->info('Import verification job started', [
            'batch_id' => $this->batchId,
            'related_batch_ids' => $this->relatedBatchIds,
            'attempt' => $this->attempts(),
        ]);

        try {
            $summary = $verifier->verify(
                $batch,
                $this->relatedBatchIds,
                fn (array $progress) => $this->putStatus([
                    'status' => 'processing',
                    ...$progress,
                ]),
            );

            $this->putStatus([
                'status' => 'completed',
                'message' => 'Verification terminee.',
                'percentage' => 100,
                'completed_at' => now()->toIso8601String(),
                ...$summary,
            ], now()->addDay());
        } catch (\Throwable $e) {
            $releaseGlobalLock = $this->attempts() >= $this->tries;

            $this->putStatus([
                'status' => $releaseGlobalLock ? 'failed' : 'retrying',
                'message' => $releaseGlobalLock
                    ? $e->getMessage()
                    : 'Echec temporaire, nouvelle tentative planifiee.',
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
                'attempt' => $this->attempts(),
            ], now()->addDay());

            Log::channel('imports')->error('Import verification job attempt failed', [
                'batch_id' => $this->batchId,
                'related_batch_ids' => $this->relatedBatchIds,
                'attempt' => $this->attempts(),
                'will_retry' => ! $releaseGlobalLock,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if ($this->isGlobalVerification() && $releaseGlobalLock) {
                Cache::forget(self::GLOBAL_LOCK_CACHE_KEY);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->putStatus([
            'status' => 'failed',
            'message' => $e->getMessage(),
            'error' => $e->getMessage(),
            'failed_at' => now()->toIso8601String(),
        ], now()->addDay());

        if ($this->isGlobalVerification()) {
            Cache::forget(self::GLOBAL_LOCK_CACHE_KEY);
        }

        Log::channel('imports')->error('Import verification job failed', [
            'batch_id' => $this->batchId,
            'related_batch_ids' => $this->relatedBatchIds,
            'message' => $e->getMessage(),
        ]);
    }

    private function isGlobalVerification(): bool
    {
        return $this->batchId === null && $this->relatedBatchIds === [];
    }

    /**
     * @return array<int,string>
     */
    private function statusCacheKeys(): array
    {
        if ($this->isGlobalVerification()) {
            return [self::GLOBAL_STATUS_CACHE_KEY];
        }

        return array_map(
            fn (int $id) => "import_verification_{$id}",
            array_values(array_unique($this->relatedBatchIds)),
        );
    }

    private function putStatus(array $payload, ?\DateTimeInterface $ttl = null): void
    {
        $ttl ??= now()->addHours(2);

        foreach ($this->statusCacheKeys() as $key) {
            $current = Cache::get($key, []);

            Cache::put($key, [
                ...$current,
                ...$payload,
                'updated_at' => now()->toIso8601String(),
            ], $ttl);
        }
    }
}
