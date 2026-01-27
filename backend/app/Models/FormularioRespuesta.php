<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormularioRespuesta extends ModeloBase
{

    protected $table = 'formulario_respuestas';

    protected $fillable = [
        'formulario_id',
        'cliente_id',
        'fecha',
        'respuestas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'respuestas' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeDelFormulario($query, int $formularioId)
    {
        return $query->where('formulario_id', $formularioId);
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }
}
