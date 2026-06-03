<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'type',
        'original_filename',
        'stored_path',
        'file_hash',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'created_rows',
        'updated_rows',
        'skipped_rows',
        'force_import',
        'metadata',
        'error_summary',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'error_summary' => 'array',
        'metadata' => 'array',
        'force_import' => 'boolean',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifications()
    {
        return $this->hasMany(ImportVerification::class);
    }

    public function diffs(): HasMany
    {
        return $this->hasMany(ImportDiff::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_rows === 0) return 0;
        return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed')
            ->whereNotNull('completed_at');
    }

    public function scopeCleanupCandidates(Builder $query, int $days, ?string $type = null): Builder
    {
        $query->completed()
            ->where('completed_at', '<', now()->subDays(max(1, $days)));

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        return $query;
    }

    public function markCleanupMetadata(array $attributes): void
    {
        $metadata = $this->metadata ?? [];
        $this->forceFill([
            'metadata' => array_merge($metadata, $attributes),
        ])->save();
    }
}
