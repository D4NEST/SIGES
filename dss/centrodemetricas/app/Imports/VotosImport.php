<?php

namespace App\Imports;

use App\Models\VotoPersonal;
use App\Models\RegistroHorario;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;

class VotosImport implements ToModel, WithHeadingRow, WithChunkReading
{
    protected array $mapping;
    protected int $uploadId;

    public function __construct(array $mapping, int $uploadId)
    {
        $this->mapping = $mapping;
        $this->uploadId = $uploadId;
    }

    public function model(array $row)
    {
        // 1. Buscamos si en esta fila hay una cédula (si el operador mapeó una columna como 'cedula')
        $cedula = null;
        foreach ($this->mapping as $header => $dbField) {
            if ($dbField === 'cedula') {
                $slug = Str::slug($header, '_');
                $cedula = trim($row[$slug] ?? '');
            }
        }

        // 2. Procesamos columnas de horas (la magia de las columnas hacia abajo)
        foreach ($this->mapping as $header => $dbField) {
            // Buscamos columnas que tengan formato de hora (ej: 7am-8am)
            if (preg_match('/(\d+)(am|pm)-(\d+)(am|pm)/i', $header)) {
                $slug = Str::slug($header, '_');
                $valorEnCelda = trim($row[$slug] ?? '');

                // Si la celda tiene una cédula (o una marca)
                if (!empty($valorEnCelda)) {
                    // Validamos que la cédula exista en el padrón
                    $persona = VotoPersonal::where('cedula', $valorEnCelda)->first();

                    if ($persona) {
                        // Registramos el horario
                        RegistroHorario::updateOrCreate([
                            'cedula'         => $valorEnCelda,
                            'intervalo'      => $header, 
                            'fecha_registro' => now()->format('Y-m-d')
                        ], [
                            'upload_id' => $this->uploadId
                        ]);

                        // Marcamos automáticamente como 'SI'
                        $persona->update(['estado_voto' => 'SI']);
                    }
                }
            }
        }

        // Si esta fila solo era de cédula (padrón), guardamos o actualizamos
        if (!empty($cedula)) {
            return VotoPersonal::updateOrCreate(
                ['cedula' => $cedula],
                ['upload_id' => $this->uploadId, 'estado_voto' => 'NO'] // Por defecto NO si no se marca hora
            );
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}