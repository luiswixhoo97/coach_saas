<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensaje extends ModeloBase
{

    protected $table = 'mensajes';

    protected $fillable = [
        'chat_id',
        'emisor_tipo',
        'mensaje',
        'enviado_en',
        'leido',
    ];

    protected function casts(): array
    {
        return [
            'enviado_en' => 'datetime',
            'leido' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(ArchivoMensaje::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeNoLeidos($query)
    {
        return $query->where('leido', false);
    }

    public function scopeLeidos($query)
    {
        return $query->where('leido', true);
    }

    public function scopeDeCoach($query)
    {
        return $query->where('emisor_tipo', 'coach');
    }

    public function scopeDeCliente($query)
    {
        return $query->where('emisor_tipo', 'cliente');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function esDeCoach(): bool
    {
        return $this->emisor_tipo === 'coach';
    }

    public function esDeCliente(): bool
    {
        return $this->emisor_tipo === 'cliente';
    }

    public function marcarComoLeido(): void
    {
        if (!$this->leido) {
            $this->update(['leido' => true]);
        }
    }
}
