<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
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
