<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroHorario extends Model
{
    protected $table = 'registro_horario';
    
    protected $fillable = [
        'cedula',
        'intervalo',
        'fecha_registro',
        'upload_id'
    ];
}