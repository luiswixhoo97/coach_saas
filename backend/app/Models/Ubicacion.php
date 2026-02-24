<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends ModeloBase
{
    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'direccion',
        'link_google_maps',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Buscar ubicaciones por query (nombre o dirección)
     */
    public function scopeBuscarPorQuery(Builder $query, string $searchQuery): Builder
    {
        $searchTerm = '%' . $searchQuery . '%';
        
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('nombre', 'LIKE', $searchTerm)
              ->orWhere('direccion', 'LIKE', $searchTerm);
        });
    }
}

