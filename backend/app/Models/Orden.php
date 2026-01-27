<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Orden extends ModeloBase
{

    protected $table = 'ordenes';

    protected $fillable = [
        'coach_id',
        'cliente_id',
        'total',
        'estado',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrdenItem::class);
    }

    public function pago(): HasOne
    {
        return $this->hasOne(PagoTienda::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelCoach($query, int $coachId)
    {
        return $query->where('coach_id', $coachId);
    }

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
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

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaCompletada(): bool
    {
        return $this->estado === 'completada';
    }

    public function estaCancelada(): bool
    {
        return $this->estado === 'cancelada';
    }

    public function estaPagada(): bool
    {
        return $this->pago !== null;
    }

    public function cantidadItems(): int
    {
        return $this->items->sum('cantidad');
    }

    public function calcularTotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->precio * $item->cantidad;
        });
    }
}
