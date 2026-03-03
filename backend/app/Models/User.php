<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'rol',
        'activo',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function coach(): HasOne
    {
        return $this->hasOne(Coach::class, 'usuario_id');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'usuario_id');
    }

    public function dispositivos(): HasMany
    {
        return $this->hasMany(Dispositivo::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de Rol
    |--------------------------------------------------------------------------
    */

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esCoach(): bool
    {
        return $this->rol === 'coach';
    }

    public function esCliente(): bool
    {
        return $this->rol === 'cliente';
    }
}
