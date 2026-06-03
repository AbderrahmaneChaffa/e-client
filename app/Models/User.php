<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'client_id',
        'is_validated',
    ];

    protected $attributes = [
        'role' => 'client',
    ];

    protected $casts = [
        'role' => UserRole::class,
        'is_validated' => 'boolean',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_validated' => 'boolean',
        ];
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('is_validated', true);
    }

    /**
     * Filtre les utilisateurs par rôle ou collection de rôles.
     *
     * @param  UserRole|string|array<int, UserRole|string>  $role
     */
    public function scopeRole(Builder $query, UserRole|string|array $role): Builder
    {
        $roles = collect(Arr::wrap($role))
            ->map(fn ($item) => $item instanceof UserRole ? $item->value : strtolower(trim((string) $item)))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values();

        return $roles->isEmpty()
            ? $query
            : $query->whereIn('role', $roles->all());
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN
            || strtolower((string) $this->getRawOriginal('role')) === UserRole::SUPERADMIN->value;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN
            || strtolower((string) $this->getRawOriginal('role')) === UserRole::ADMIN->value;
    }

    public function hasAdminAccess(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }
}
