<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $fillable = [
        'hash_archivo',
        'nombre_archivo',
        'user_id',
        'estado',
        'total_filas',
        'filas_insertadas',
        'filas_actualizadas',
        'filas_rechazadas',
        'log_errores',
    ];

    protected $casts = [
        'log_errores'       => 'array',
        'total_filas'       => 'integer',
        'filas_insertadas'  => 'integer',
        'filas_actualizadas'=> 'integer',
        'filas_rechazadas'  => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Verifica si un hash de archivo ya fue importado */
    public static function hashExiste(string $hash): bool
    {
        return self::where('hash_archivo', $hash)->exists();
    }
}
