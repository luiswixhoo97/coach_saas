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

    /**
     * Dietas de clientes de un coach (vía suscripción -> cliente -> creado_por).
     */
    public function scopeDelCoach($query, int $coachId)
    {
        return $query->whereHas('suscripcion.cliente', fn($q) => $q->where('creado_por', $coachId));
    }

    /**
     * Cantidad de clientes (de un coach) que tienen al menos una dieta activa.
     */
    public static function clientesConDietaCount(int $coachId): int
    {
        $clienteIds = Cliente::where('creado_por', $coachId)->select('id')->pluck('id');

        return (int) self::activas()
            ->delCoach($coachId)
            ->join('suscripciones', 'dieta_cliente.suscripcion_id', '=', 'suscripciones.id')
            ->whereIn('suscripciones.cliente_id', $clienteIds)
            ->select('suscripciones.cliente_id')
            ->groupBy('suscripciones.cliente_id')
            ->get()
            ->count();
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
