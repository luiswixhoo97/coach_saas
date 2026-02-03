<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends ModeloBase
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'sexo',
        'fecha_nacimiento',
        'altura',
        'objetivo',
        'activo',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'altura' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'creado_por');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function rutinasAsignadas(): HasMany
    {
        return $this->hasMany(RutinaCliente::class);
    }

    public function respuestasFormularios(): HasMany
    {
        return $this->hasMany(FormularioRespuesta::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelCoach($query, int $coachId)
    {
        return $query->where('creado_por', $coachId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function suscripcionActiva(): ?Suscripcion
    {
        return $this->suscripciones()
            ->where('estado', 'activa')
            ->latest()
            ->first();
    }

    public function tieneSubscripcionActiva(): bool
    {
        return $this->suscripcionActiva() !== null;
    }

    public function edad(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    /**
     * Indica si el cliente tiene al menos una dieta activa (en alguna suscripción).
     */
    public function tieneDietaActiva(): bool
    {
        return $this->suscripciones()
            ->whereHas('dietas', fn($q) => $q->where('activo', true))
            ->exists();
    }
}
