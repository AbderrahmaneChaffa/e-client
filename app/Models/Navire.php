<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Navire extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = ['nom', 'pavillon', 'date_arrivee', 'date_sortie'];
}
