<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;

    // Le indicamos las columnas de la tabla que se pueden llenar de forma masiva
    protected $fillable = [
    'user_id',
    'filename',
    'original_name',
    'status',
    'total_rows',
    'processed_rows',
    'column_mapping'
    ];

    /**
     * Relación: Una carga pertenece a un Usuario (Operador)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}