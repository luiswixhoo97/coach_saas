<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function formulariosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Formulario::class, 'formulario_cliente')
            ->withPivot('obligatorio', 'fecha_asignacion')
            ->withTimestamps();
    }

    public function parametrosHistorial(): HasMany
    {
        return $this->hasMany(ParametroCliente::class);
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

    /*
    |--------------------------------------------------------------------------
    | Helpers - Formularios
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener formulario obligatorio pendiente.
     */
    /**
     * Obtener el formulario estándar pendiente del coach.
     * Este es el formulario que el coach usa para todos sus clientes
     * (ej: "¿Cuántas veces comes?", "¿Tienes alergias?", etc.)
     */
    public function formularioObligatorioPendiente(): ?Formulario
    {
        // Cargar relación del coach si no está cargada
        if (!$this->relationLoaded('coach')) {
            $this->load('coach');
        }
        
        // Si el cliente no está activo o no tiene coach, no hay formulario pendiente
        if (!$this->activo || !$this->coach) {
            return null;
        }
        
        // Buscar el formulario estándar del coach (formulario_inicial_id)
        if (!$this->coach->tieneFormularioInicial()) {
            return null;
        }
        
        $formularioEstandar = $this->coach->formularioInicial;
        
        // Verificar si el cliente ya completó este formulario
        $completado = $this->respuestasFormularios()
            ->where('formulario_id', $formularioEstandar->id)
            ->exists();
        
        // Si no está completado, es pendiente
        if (!$completado) {
            return $formularioEstandar;
        }
        
        return null;
    }

    /**
     * Verificar si tiene formulario obligatorio pendiente.
     */
    public function tieneFormularioPendiente(): bool
    {
        return $this->formularioObligatorioPendiente() !== null;
    }

    /**
     * Verificar si puede acceder a funcionalidades completas.
     */
    public function puedeAcceder(): bool
    {
        return $this->activo && !$this->tieneFormularioPendiente();
    }
}
