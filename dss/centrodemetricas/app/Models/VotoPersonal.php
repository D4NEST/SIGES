<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotoPersonal extends Model
{
    // Definimos la tabla real
    protected $table = 'votos_personal';

    // Le indicamos a Laravel que la llave primaria no es un 'id' autoincremental
    protected $primaryKey = 'cedula';
    public $incrementing = false;
    protected $keyType = 'string';

    // Habilitamos la asignación masiva para todos los campos del Excel
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
        'upload_id'
    ];
}