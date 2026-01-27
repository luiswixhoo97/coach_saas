<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoTienda extends ModeloBase
{

    protected $table = 'pagos_tienda';

    protected $fillable = [
        'orden_id',
        'metodo_pago',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class);
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
