<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = ['code', 'name', 'nis', 'rc', 'ai'];

    // Un client peut avoir plusieurs comptes utilisateurs (employés)
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Un client a plusieurs factures
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }
}