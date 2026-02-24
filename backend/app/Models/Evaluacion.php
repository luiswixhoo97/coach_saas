<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluacion extends ModeloBase
{

    protected $table = 'evaluaciones';

    protected $fillable = [
        'suscripcion_id',
        'fecha',
        'hora',
        'ubicacion_id',
        'ubicacion_o_link',
        'direccion',
        'modo',
        'estado',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(Suscripcion::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function parametrosEvaluacion(): HasMany
    {
        return $this->hasMany(ParametroEvaluacion::class);
    }

    public function parametrosCliente(): HasMany
    {
        return $this->hasMany(ParametroCliente::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoEvaluacion::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeModo($query, string $modo)
    {
        return $query->where('modo', $modo);
    }


    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function cliente(): ?Cliente
    {
        return $this->suscripcion?->cliente;
    }

    public function esPresencial(): bool
    {
        return $this->modo === 'presencial';
    }

    public function esOnline(): bool
    {
        return $this->modo === 'online';
    }
}
