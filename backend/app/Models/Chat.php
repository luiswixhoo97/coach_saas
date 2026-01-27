<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends ModeloBase
{

    protected $table = 'chats';

    protected $fillable = [
        'coach_id',
        'cliente_id',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelCoach($query, int $coachId)
    {
        return $query->where('coach_id', $coachId);
    }

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function ultimoMensaje(): ?Mensaje
    {
        return $this->mensajes()->latest('enviado_en')->first();
    }

    public function mensajesNoLeidos(string $para): int
    {
        $emisorContrario = $para === 'coach' ? 'cliente' : 'coach';
        
        return $this->mensajes()
            ->where('emisor_tipo', $emisorContrario)
            ->where('leido', false)
            ->count();
    }

    public function marcarComoLeido(string $para): void
    {
        $emisorContrario = $para === 'coach' ? 'cliente' : 'coach';
        
        $this->mensajes()
            ->where('emisor_tipo', $emisorContrario)
            ->where('leido', false)
            ->update(['leido' => true]);
    }
}
