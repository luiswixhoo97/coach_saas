<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parametro extends ModeloBase
{

    protected $table = 'parametros';

    protected $fillable = [
        'nombre',
        'unidad_medida',
        'tipo_dato',
        'es_predeterminado',
        'coach_id',
    ];

    protected function casts(): array
    {
        return [
            'es_predeterminado' => 'boolean',
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

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(ParametroEvaluacion::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePredeterminados($query)
    {
        return $query->where('es_predeterminado', true);
    }

    public function scopePersonalizados($query)
    {
        return $query->where('es_predeterminado', false);
    }

    public function scopeDelCoach($query, int $coachId)
    {
        return $query->where('coach_id', $coachId);
    }

    public function scopeDisponiblesParaCoach($query, int $coachId)
    {
        return $query->where(function ($q) use ($coachId) {
            $q->where('es_predeterminado', true)
              ->orWhere('coach_id', $coachId);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function esGlobal(): bool
    {
        return $this->es_predeterminado && $this->coach_id === null;
    }
}
