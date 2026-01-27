<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo base para todos los modelos de la aplicación.
 * 
 * Incluye:
 * - HasFactory para factories
 * - Timestamps estándar de Laravel (created_at, updated_at)
 * 
 * Para SoftDeletes: agregar `use SoftDeletes` en los modelos que lo requieran.
 * Modelos con SoftDeletes: Coach, Cliente, Producto
 * 
 * Todos los modelos deben extender esta clase excepto User.
 */
abstract class ModeloBase extends Model
{
    use HasFactory;

    /**
     * Indica si el modelo debe tener timestamps.
     *
     * @var bool
     */
    public $timestamps = true;
}
