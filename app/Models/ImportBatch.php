<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_rows === 0) return 0;
        return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }
}
