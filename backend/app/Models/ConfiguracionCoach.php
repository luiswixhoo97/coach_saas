<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionCoach extends ModeloBase
{
    protected $table = 'configuraciones_coach';

    protected $fillable = [
        'coach_id',
        'nombre_cuenta',
        'banco',
        'clave',
        'semanas_entre_evaluaciones',
    ];

    protected function casts(): array
    {
        return [
            'semanas_entre_evaluaciones' => 'integer',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
