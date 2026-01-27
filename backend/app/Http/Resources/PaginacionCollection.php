<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginacionCollection extends ResourceCollection
{
    /**
     * El resource a usar para cada item.
     */
    public $collects;

    /**
     * Mensaje opcional para la respuesta.
     */
    protected ?string $mensaje = null;

    /**
     * Crear una nueva colección con paginación.
     */
    public function __construct($resource, ?string $resourceClass = null)
    {
        $this->collects = $resourceClass;
        parent::__construct($resource);
    }

    /**
     * Agregar mensaje a la respuesta.
     */
    public function conMensaje(string $mensaje): self
    {
        $this->mensaje = $mensaje;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'datos' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        $with = [
            'meta' => [
                'total' => $this->resource->total(),
                'por_pagina' => $this->resource->perPage(),
                'pagina_actual' => $this->resource->currentPage(),
                'ultima_pagina' => $this->resource->lastPage(),
            ],
        ];

        if ($this->mensaje) {
            $with['mensaje'] = $this->mensaje;
        }

        return $with;
    }
}
