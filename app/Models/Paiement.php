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
        'date_paiement',
        'reference_recu',
        'numero_cheque',
        'banque',
        'montant_verse'
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
