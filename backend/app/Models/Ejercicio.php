<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ejercicio extends ModeloBase
{

    protected $table = 'ejercicios';

    protected $fillable = [
        'coach_id',
        'nombre',
        'grupo_muscular',
        'video_url',
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

    public function rutinas(): BelongsToMany
    {
        return $this->belongsToMany(Rutina::class, 'rutina_ejercicios')
            ->withPivot(['series', 'repeticiones', 'descanso_segundos', 'bloque'])
            ->withTimestamps();
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

    public function scopeGrupoMuscular($query, string $grupo)
    {
        return $query->where('grupo_muscular', $grupo);
    }

    public function scopeConVideo($query)
    {
        return $query->whereNotNull('video_url');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function tieneVideo(): bool
    {
        return !empty($this->video_url);
    }
}
