<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispositivo extends ModeloBase
{
    protected $table = 'dispositivos';

    protected $fillable = [
        'user_id',
        'fcm_token',
        'plataforma',
        'nombre',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
