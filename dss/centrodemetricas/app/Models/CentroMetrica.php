<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CentroMetrica extends Model
{
    protected $table = 'votos_personal';

    protected $primaryKey = 'cedula';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cedula',
        'nombre_apellido',
        'cargo',
        'ubicacion_administrativa',
        'planta',
        'filial',
        'estado_fisico',
        'telefono',
        'estado_voto',
        'municipio',
        'parroquia',
        'centro_votacion',
        'direccion_centro',
        'upload_id',
    ];

    protected $casts = [
        'estado_voto' => 'string',
    ];

    // --- Scopes para el dashboard y cruce de datos ---

    /** Filtra por estado geográfico (usa estado_fisico en votos_personal) */
    public function scopePorEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado_fisico', $estado);
    }

    /** Registros que ya ejercieron su voto */
    public function scopeVotantes(Builder $query): Builder
    {
        return $query->where('estado_voto', 'SI');
    }

    /** Registros que NO han ejercido su voto */
    public function scopeNoVotantes(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('estado_voto', 'NO')->orWhereNull('estado_voto');
        });
    }
}
