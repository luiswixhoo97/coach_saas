<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coach extends ModeloBase
{
    use SoftDeletes;

    protected $table = 'coaches';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'bio',
        'avatar',
        'activo',
        'formulario_registro_id',
        'formulario_inicial_id',
        'token_registro',
        'link_registro_activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
            'link_registro_activo' => 'boolean',
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

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'creado_por');
    }

    public function planes(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function rutinas(): HasMany
    {
        return $this->hasMany(Rutina::class);
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(Ejercicio::class);
    }

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class);
    }

    public function parametros(): HasMany
    {
        return $this->hasMany(Parametro::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
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

    /*
    |--------------------------------------------------------------------------
    | Relaciones - Formularios
    |--------------------------------------------------------------------------
    */

    public function formularioRegistro(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_registro_id');
    }

    public function formularioInicial(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_inicial_id');
    }

    public function configuracion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ConfiguracionCoach::class, 'coach_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers - Formularios y Registro
    |--------------------------------------------------------------------------
    */

    public function tieneFormularioRegistro(): bool
    {
        return $this->formulario_registro_id !== null;
    }

    public function tieneFormularioInicial(): bool
    {
        return $this->formulario_inicial_id !== null;
    }

    public function generarTokenRegistro(): string
    {
        do {
            $token = \Illuminate\Support\Str::random(32);
        } while (self::where('token_registro', $token)->exists());
        
        $this->update(['token_registro' => $token]);
        
        return $token;
    }

    public function obtenerLinkRegistro(): ?string
    {
        if (!$this->token_registro) {
            return null;
        }
        
        // Obtener URL del frontend desde configuración o variable de entorno
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        
        // Asegurar que no termine con /
        $frontendUrl = rtrim($frontendUrl, '/');
        
        return "{$frontendUrl}/registro/{$this->token_registro}";
    }

    public function linkRegistroActivo(): bool
    {
        return $this->link_registro_activo && $this->token_registro !== null;
    }
}
