<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Prestation extends Model
{
    use HasFactory, Notifiable;

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
