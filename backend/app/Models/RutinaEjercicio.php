<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutinaEjercicio extends ModeloBase
{

    protected $table = 'rutina_ejercicios';

    protected $fillable = [
        'rutina_id',
        'ejercicio_id',
        'series',
        'repeticiones',
        'descanso_segundos',
        'bloque',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'series' => 'integer',
            'repeticiones' => 'integer',
            'descanso_segundos' => 'integer',
            'bloque' => 'integer',
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

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeBloque($query, int $bloque)
    {
        return $query->where('bloque', $bloque);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('bloque')->orderBy('id');
    }
}
