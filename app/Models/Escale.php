<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Escale extends Model
{
    use HasFactory;

    protected $fillable = [
        'navire_id',
        'numero_escale',
        // 'eta',
        'date_arrivee',
        'date_sortie',
        'poste_quai',
        'motif',
        'consignataire',
        'tirant_eau_arrivee',
        // 'statut'
    ];

    // Conversion automatique des dates pour pouvoir utiliser Carbon dessus
    protected $casts = [
        // 'eta' => 'datetime',
        'date_arrivee' => 'date',
        'date_sortie' => 'date',

    ];

    // Une escale appartient à un seul navire
    public function navire(): BelongsTo
    {
        return $this->belongsTo(Navire::class);
    }

    // relies ça à système de facturation plus tard
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }
}
