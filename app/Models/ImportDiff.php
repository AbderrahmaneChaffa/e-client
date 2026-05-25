<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportDiff extends Model
{
    protected $fillable = [
        'import_batch_id',
        'facture_id',
        'entity_type',
        'entity_key',
        'change_type',
        'severity',
        'label',
        'differences',
        'context',
    ];

    protected $casts = [
        'differences' => 'array',
        'context' => 'array',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }
}
