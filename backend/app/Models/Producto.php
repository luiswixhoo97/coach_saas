<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends ModeloBase
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'coach_id',
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'stock_minimo',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'stock_minimo' => 'integer',
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

    public function ordenItems(): HasMany
    {
        return $this->hasMany(OrdenItem::class);
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

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeConStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function tieneStock(): bool
    {
        return $this->stock > 0;
    }

    public function stockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    public function esDigital(): bool
    {
        return $this->tipo === 'digital';
    }

    public function esFisico(): bool
    {
        return $this->tipo === 'fisico';
    }

    public function reducirStock(int $cantidad): bool
    {
        if ($this->stock >= $cantidad) {
            $this->decrement('stock', $cantidad);
            return true;
        }
        return false;
    }

    public function aumentarStock(int $cantidad): void
    {
        $this->increment('stock', $cantidad);
    }
}
