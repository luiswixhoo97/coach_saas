<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutinaCliente extends ModeloBase
{

    protected $table = 'rutina_cliente';

    protected $fillable = [
        'rutina_id',
        'cliente_id',
        'dia',
        'series_realizadas',
        'reps_realizadas',
        'peso_usado',
    ];

    protected function casts(): array
    {
        return [
            'dia' => 'array',
            'series_realizadas' => 'integer',
            'reps_realizadas' => 'integer',
            'peso_usado' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function tieneProgreso(): bool
    {
        return $this->series_realizadas !== null 
            || $this->reps_realizadas !== null 
            || $this->peso_usado !== null;
    }
}
