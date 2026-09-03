<?php

namespace App\Imports;

use App\Models\VotoPersonal;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class VotosImport implements ToModel, WithHeadingRow, WithChunkReading
{
    // Bolsa pública para atrapar los errores fila por fila
    public array $errorsList = []; 
    
    protected int $uploadId;
    protected array $mapping;
    protected int $currentRow = 1; // Rastreador físico de filas en el Excel

    /**
     * Constructor para inyectar el ID de la carga y la matriz del Wizard
     */
    public function __construct(array $mapping, int $uploadId)
    {
        $this->mapping  = $mapping;
        $this->uploadId = $uploadId;
    }

    /**
     * El corazón del procesador: Se ejecuta automáticamente por cada fila del Excel
     */
    public function model(array $row)
    {
        $this->currentRow++; // Sumamos una fila al contador por cada ciclo

        $insertData = [];

        // 1. Recorremos dinámicamente las 13 columnas mapeadas desde el Wizard
        foreach ($this->mapping as $excelHeader => $dbField) {
            if (empty($dbField) || $dbField === 'ignore') {
                continue;
            }

            // Normalizamos el encabezado del Excel a formato "slug" (le quita acentos y espacios)
            // Laravel Excel convierte "Nombre y Apellido" a "nombre_y_apellido" por debajo
            $normalizedHeader = \Illuminate\Support\Str::slug($excelHeader, '_');

            if (array_key_exists($normalizedHeader, $row)) {
                $insertData[$dbField] = $row[$normalizedHeader];
            }
        }

        // --- CAPA 1: VALIDACIONES CRÍTICAS DE NEGOCIO ---
        
        // 1. Validar la Cédula (Obligatoria, Limpia y Numérica)
        if (!isset($insertData['cedula']) || empty(trim($insertData['cedula']))) {
            $this->errorsList[] = "Fila {$this->currentRow}: El campo emparejado como 'Cédula' está vacío.";
            return null;
        }

        $cleanCedula = trim($insertData['cedula']);
        if (!is_numeric($cleanCedula)) {
            $this->errorsList[] = "Fila {$this->currentRow}: La cédula '{$cleanCedula}' contiene caracteres no numéricos.";
            return null;
        }

        // 2. Validar el Nombre y Apellido (Obligatorio)
        if (!isset($insertData['nombre_apellido']) || empty(trim($insertData['nombre_apellido']))) {
            $this->errorsList[] = "Fila {$this->currentRow}: El campo emparejado como 'Nombre y Apellido' está vacío.";
            return null;
        }


        // --- CAPA 2: SANITIZACIÓN Y VALORES POR DEFECTO ---
        $insertData['cedula']                   = $cleanCedula;
        $insertData['nombre_apellido']          = trim($insertData['nombre_apellido']);
        $insertData['cargo']                    = isset($insertData['cargo']) ? trim($insertData['cargo']) : 'N/P';
        $insertData['ubicacion_administrativa'] = isset($insertData['ubicacion_administrativa']) ? trim($insertData['ubicacion_administrativa']) : 'N/P';
        $insertData['planta']                   = isset($insertData['planta']) ? trim($insertData['planta']) : 'N/P';
        $insertData['filial']                   = isset($insertData['filial']) ? trim($insertData['filial']) : 'N/P';
        $insertData['estado_fisico']            = isset($insertData['estado_fisico']) ? trim($insertData['estado_fisico']) : 'N/P';
        $insertData['telefono']                 = isset($insertData['telefono']) ? trim($insertData['telefono']) : null;
        
        // El padrón inicial siempre entra con estado de voto en NO. El segundo archivo lo cambiará a SI
        $insertData['estado_voto']              = 'NO';
        
        $insertData['municipio']                = isset($insertData['municipio']) ? trim($insertData['municipio']) : null;
        $insertData['parroquia']                = isset($insertData['parroquia']) ? trim($insertData['parroquia']) : null;
        $insertData['centro_votacion']          = isset($insertData['centro_votacion']) ? trim($insertData['centro_votacion']) : null;
        $insertData['direccion_centro']         = isset($insertData['direccion_centro']) ? trim($insertData['direccion_centro']) : null;
        
        // Adjuntamos la llave foránea de auditoría
        $insertData['upload_id']                = $this->uploadId;


        // --- CAPA 3: PERSISTENCIA INTELIGENTE (Inyección sin duplicados) ---
        // Usamos updateOrCreate para que si la cédula ya existe, actualice los datos en vez de explotar por clave duplicada
        return VotoPersonal::updateOrCreate(
            ['cedula' => $cleanCedula],
            $insertData
        );
    }

    /**
     * Fragmenta la lectura del Excel en lotes de 1,000 filas para evitar
     * el desbordamiento de memoria de PHP en cargas masivas corporativas.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}