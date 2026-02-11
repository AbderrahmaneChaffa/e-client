<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Facture extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'numero_facture',
        'date_facture',
        'date_mise_en_ligne',
        'date_echeance',
        'client_id',
        'navire_id',
        'total_ht',
        'total_tva',
        'total_ttc',
        'montant_paye',
        'reste_a_payer'
    ];

    // Dates automatiques
    protected $casts = [
        'date_facture' => 'date',
        'date_echeance' => 'date',
    ];

    // Filtre pour les factures impayées (Utilisé pour l'affichage client)
    public function scopeImpayees(Builder $query): void
    {
        $query->where('reste_a_payer', '>', 0);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function prestations(): HasMany
    {
        return $this->hasMany(Prestation::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function navire(): BelongsTo
    {
        return $this->belongsTo(Navire::class);
    }
}
