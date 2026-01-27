<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coach extends ModeloBase
{
    use SoftDeletes;

    protected $table = 'coaches';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'bio',
        'avatar',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'creado_por');
    }

    public function planes(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function rutinas(): HasMany
    {
        return $this->hasMany(Rutina::class);
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(Ejercicio::class);
    }

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class);
    }

    public function parametros(): HasMany
    {
        return $this->hasMany(Parametro::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
