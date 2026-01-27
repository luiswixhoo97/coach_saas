<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModeloBase extends Model
{
    use SoftDeletes;

    /**
     * Nombre de la columna para soft deletes
     */
    const DELETED_AT = 'eliminado_el';

    /**
     * Nombre de la columna created_at
     */
    const CREATED_AT = 'creado_el';

    /**
     * Nombre de la columna updated_at
     */
    const UPDATED_AT = 'actualizado_el';

     /**
     * Obtener el nombre de la clase sin namespace
     */
    protected function getClassName(): string
    {
        return class_basename($this);
    }
}
