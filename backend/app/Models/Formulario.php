<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formulario extends ModeloBase
{

    protected $table = 'formularios';

    protected $fillable = [
        'coach_id',
        'nombre',
        'preguntas',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'preguntas' => 'array',
            'activo' => 'boolean',
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

    public function respuestas(): HasMany
    {
        return $this->hasMany(FormularioRespuesta::class);
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

    public function scopeDelCoach($query, int $coachId)
    {
        return $query->where('coach_id', $coachId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function cantidadPreguntas(): int
    {
        return is_array($this->preguntas) ? count($this->preguntas) : 0;
    }

    public function cantidadRespuestas(): int
    {
        return $this->respuestas()->count();
    }
}
