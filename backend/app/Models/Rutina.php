<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rutina extends ModeloBase
{

    protected $table = 'rutinas';

    protected $fillable = [
        'coach_id',
        'nombre',
        'nivel',
        'objetivo',
        'clonada_de',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function rutinaOriginal(): BelongsTo
    {
        return $this->belongsTo(Rutina::class, 'clonada_de');
    }

    public function rutinasClonadas(): HasMany
    {
        return $this->hasMany(Rutina::class, 'clonada_de');
    }

    public function ejercicios(): BelongsToMany
    {
        return $this->belongsToMany(Ejercicio::class, 'rutina_ejercicios')
            ->withPivot(['series', 'repeticiones', 'descanso_segundos', 'bloque'])
            ->withTimestamps();
    }

    public function rutinaEjercicios(): HasMany
    {
        return $this->hasMany(RutinaEjercicio::class);
    }

    public function clientesAsignados(): HasMany
    {
        return $this->hasMany(RutinaCliente::class);
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

    public function scopeNivel($query, string $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeObjetivo($query, string $objetivo)
    {
        return $query->where('objetivo', $objetivo);
    }
}
