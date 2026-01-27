<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietaCliente extends ModeloBase
{

    protected $table = 'dieta_cliente';

    protected $fillable = [
        'suscripcion_id',
        'archivo',
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

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(Suscripcion::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function cliente(): ?Cliente
    {
        return $this->suscripcion?->cliente;
    }
}
