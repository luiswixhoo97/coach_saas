<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroEvaluacion extends ModeloBase
{

    protected $table = 'parametros_evaluacion';

    protected $fillable = [
        'evaluacion_id',
        'parametro_id',
        'valor',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function valorNumerico(): ?float
    {
        return is_numeric($this->valor) ? (float) $this->valor : null;
    }
}
