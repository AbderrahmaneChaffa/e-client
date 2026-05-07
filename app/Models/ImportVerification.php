<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportVerification extends Model
{
    protected $fillable = [
        'import_batch_id',
        'rule_code',
        'severity',
        'affected_count',
        'sample_ids',
        'details',
    ];

    protected $casts = [
        'sample_ids' => 'array',
        'details' => 'array',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
