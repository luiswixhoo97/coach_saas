<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends ModeloBase
{

    protected $table = 'pagos';

    protected $fillable = [
        'suscripcion_id',
        'monto',
        'metodo_pago',
        'referencia',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
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

    public function scopeMetodo($query, string $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function esStripe(): bool
    {
        return $this->metodo_pago === 'stripe';
    }

    public function esEfectivo(): bool
    {
        return $this->metodo_pago === 'efectivo';
    }
}
