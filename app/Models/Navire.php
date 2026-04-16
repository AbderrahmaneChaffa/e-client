<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Navire extends Model
{
    use HasFactory, Notifiable;

     protected $fillable = [
        'nom',
        'numero_imo',
        'pavillon',
        'type_navire',
        'longueur_hors_tout',
        'largeur',
        'tirant_eau_max',
        'jauge_brute',
        'annee_construction'
    ];

    // Un navire peut avoir plusieurs escales
    public function escales(): HasMany
    {
        return $this->hasMany(Escale::class);
    }
}
