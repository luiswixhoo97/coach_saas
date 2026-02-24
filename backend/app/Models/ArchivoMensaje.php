<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoMensaje extends ModeloBase
{
    protected $table = 'archivos_mensaje';

    protected $fillable = [
        'mensaje_id',
        'tipo',
        'nombre_original',
        'ruta',
        'tamaño',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'tamaño' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeImagenes($query)
    {
        return $query->where('tipo', 'imagen');
    }

    public function scopeDocumentos($query)
    {
        return $query->where('tipo', 'documento');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function esImagen(): bool
    {
        return $this->tipo === 'imagen';
    }

    public function esDocumento(): bool
    {
        return $this->tipo === 'documento';
    }

    public function obtenerUrl(): string
    {
        return asset('storage/' . $this->ruta);
    }

    public function obtenerTamañoFormateado(): string
    {
        $bytes = $this->tamaño;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }
}





