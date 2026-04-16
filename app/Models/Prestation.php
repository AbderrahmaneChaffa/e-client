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
        'article',
        'libelle',
        'quantite',
        'prix_unitaire',
        'total_ht',
        'taux_ht',
        'taux_tva',
        'total_tva',
        'total_ttc'

    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
