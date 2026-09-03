<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'usuario_nombre',
        'evento',
        'descripcion',
        'detalles_extra',
        'url_solicitada',
        'direccion_ip',
        'user_agent'
    ];

    protected $casts = [
        'detalles_extra' => 'array',
    ];

    /**
     * Relación: Un registro de auditoría pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
