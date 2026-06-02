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
        'escale_id',
        'total_ht',
        'total_tva',
        'total_ttc',
        'montant_paye',
        'reste_a_payer',
        'bordereau',      // Ajouté
        'description',    // Ajouté
        'pour',           // Ajouté
        'devise',         // Ajouté
        'taux_devise',    // Ajouté
        'mode_paiement',  // Ajouté
        'annuler',        // Ajouté
        'created_by',     // Ajouté
        'row_hash',
        'needs_review',
        'verification_status',
        'verification_flags',
        'last_verified_at',
        'import_diff_status',
        'last_import_diff_type',
        'import_diff_count',
        'import_diff_summary',
        'last_import_batch_id',
        'last_import_diff_at',
    ];

    // Dates automatiques
    protected $casts = [
        'date_facture' => 'date',
        'date_echeance' => 'date',
        'annuler' => 'boolean',
        'imprimer' => 'boolean',
        'needs_review' => 'boolean',
        'verification_flags' => 'array',
        'last_verified_at' => 'datetime',
        'import_diff_summary' => 'array',
        'last_import_diff_at' => 'datetime',
    ];

    // Filtre pour les factures impayées (Utilisé pour l'affichage client)
    public function scopeActive(Builder $query): void
    {
        $query->where('annuler', false);
    }

    public function scopeCanceled(Builder $query): void
    {
        $query->where('annuler', true);
    }

    public function scopePaid(Builder $query): void
    {
        $query->active()->where('reste_a_payer', '<=', 0);
    }

    public function scopeUnpaid(Builder $query): void
    {
        $query->active()->where('reste_a_payer', '>', 0);
    }

    public function scopeImpayees(Builder $query): void
    {
        $query->unpaid();
    }

    public function scopeWithVerificationIssues(Builder $query): void
    {
        $query->whereIn('verification_status', ['warning', 'critical']);
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

    /**
     * Tickets de support liés à cette facture.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function importDiffs(): HasMany
    {
        return $this->hasMany(ImportDiff::class);
    }

    // public function navire(): BelongsTo
    // {
    //     return $this->belongsTo(Navire::class);
    // }
    public function escale(): BelongsTo
    {
        return $this->belongsTo(Escale::class);
    }
}
