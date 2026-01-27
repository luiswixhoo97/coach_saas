<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suscripcion extends ModeloBase
{

    protected $table = 'suscripciones';

    protected $fillable = [
        'cliente_id',
        'plan_id',
        'stripe_subscription_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function dietas(): HasMany
    {
        return $this->hasMany(DietaCliente::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_fin', '<', now());
    }

    public function scopeVigentes($query)
    {
        return $query->where('fecha_fin', '>=', now());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function estaActiva(): bool
    {
        return $this->estado === 'activa' && $this->fecha_fin >= now();
    }

    public function diasRestantes(): int
    {
        return max(0, now()->diffInDays($this->fecha_fin, false));
    }

    public function tieneStripe(): bool
    {
        return !empty($this->stripe_subscription_id);
    }
}
