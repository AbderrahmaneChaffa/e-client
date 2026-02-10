<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestation extends Model
{
    protected $fillable = [
        'facture_id',
        'code_produit',
        'designation',
        'quantite',
        'prix_unitaire',
        'total_ht'
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
