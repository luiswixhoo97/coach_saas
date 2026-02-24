<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

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

