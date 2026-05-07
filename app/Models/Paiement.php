<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Paiement extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'facture_id',
        'recu',
        'date_paiement',
        'montant',
        'mode_paiement',
        'numero_cheque',
        'banque',
        'image_recu',
        'facture_anterieur',
        'note','created_by',
        'row_hash',
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
