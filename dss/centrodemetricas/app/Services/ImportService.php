<?php

namespace App\Services;

use App\Models\CentroMetrica;
use App\Models\Importacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportService
{
    /**
     * Estructura oficial de columnas en orden estricto A-M.
     * Backported desde UploadController::parse() del Proyecto Trabajo.
     */
    public const ESTRUCTURA_OFICIAL = [
        'cedula',
        'nombres_y_apellidos',
        'cargo',
        'ubicacion_administrativa',
        'planta',
        'filial',
        'estado_ubicacion_fisica',
        'telefono',
        'estado',
        'municipio',
        'parroquia',
        'centro_de_votacion',
        'direccion_centro_de_votacion',
    ];

    /**
     * Diccionario: cabecera normalizada del Excel → campo real en centro_metricas.
     * Backported desde $traductorCamposBD en UploadController::parse() del Proyecto Trabajo.
     * Adaptado a los nombres de columnas del Laboratorio.
     */
    public const TRADUCTOR_CAMPOS_BD = [
        'cedula'                       => 'cedula',
        'nombres_y_apellidos'          => 'nombres_apellidos',
        'cargo'                        => 'cargo',
        'ubicacion_administrativa'     => 'ubicacion_administrativa',
        'planta'                       => 'planta',
        'filial'                       => 'filial',
        'estado_ubicacion_fisica'      => 'estado_ubicacion_fisica',
        'telefono'                     => 'telefono',
        'estado'                       => 'estado',
        'municipio'                    => 'municipio',
        'parroquia'                    => 'parroquia',
        'centro_de_votacion'           => 'centro_votacion',
        'direccion_centro_de_votacion' => 'direccion_centro_votacion',
    ];

    // -------------------------------------------------------------------------
    // Validación del archivo
    // -------------------------------------------------------------------------

    /**
     * Calcula el hash SHA-256 del contenido del archivo para detección de duplicados (Req 3.2).
     */
    public function calcularHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Comprueba si el archivo ya fue importado comparando el hash (Req 3.2).
     */
    public function esArchivoDuplicado(string $hash): bool
    {
        return Importacion::hashExiste($hash);
    }

    /**
     * Normaliza un encabezado de Excel al formato slug usado por Laravel Excel.
     * Backported desde la función anónima en UploadController::parse().
     */
    public function normalizarCabecera(string $heading): string
    {
        $limpio = trim(mb_strtolower($heading, 'UTF-8'));
        $limpio = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $limpio);
        $limpio = preg_replace('/[^a-z0-9_]/', '_', $limpio);
        return trim(preg_replace('/_+/', '_', $limpio), '_');
    }

    /**
     * Lee los encabezados de la primera fila del Excel (Req 3.3).
     * Retorna el array original (sin normalizar) para mostrarlos en el Wizard.
     *
     * @return array<int, string>
     */
    public function leerEncabezados(UploadedFile $file): array
    {
        $array = \Maatwebsite\Excel\Facades\Excel::toArray([], $file->getRealPath());
        return $array[0][0] ?? [];
    }

    // -------------------------------------------------------------------------
    // Validación de estructura (motor backported del Proyecto Trabajo)
    // -------------------------------------------------------------------------

    /**
     * Valida que el archivo cumpla exactamente la estructura oficial de 13 columnas
     * en el orden correcto A-M. Retorna array de errores (vacío = válido).
     *
     * Backported desde la Capa de Seguridad Operativa de UploadController::parse().
     *
     * @param  array<int, string> $headings  Encabezados crudos leídos del Excel
     * @return array{faltantes: string[], excedentes: string[], orden_incorrecto: bool}
     */
    public function validarEstructura(array $headings): array
    {
        $normalizados = array_map(fn($h) => $this->normalizarCabecera($h), $headings);
        $oficial = self::ESTRUCTURA_OFICIAL;

        return [
            'faltantes'        => array_values(array_diff($oficial, $normalizados)),
            'excedentes'       => array_values(array_diff($normalizados, $oficial)),
            'orden_incorrecto' => ($oficial !== $normalizados),
        ];
    }

    /**
     * Construye automáticamente el mapeo encabezado→campo_BD para archivos
     * que ya cumplen la estructura oficial. No requiere intervención del operador.
     *
     * Backported desde ejecutarImportacionDirecta() del Proyecto Trabajo.
     *
     * @param  array<int, string> $headings  Encabezados originales (sin normalizar)
     * @return array<string, string>  ['Nombre Original Excel' => 'campo_bd']
     */
    public function construirMapeoAutomatico(array $headings): array
    {
        $mapeo = [];
        foreach ($headings as $heading) {
            $normalizado = $this->normalizarCabecera($heading);
            $mapeo[$heading] = self::TRADUCTOR_CAMPOS_BD[$normalizado] ?? $normalizado;
        }
        return $mapeo;
    }

    /**
     * Valida que el campo 'cedula' esté presente en el mapeo (Req 3.4).
     */
    public function validarMapeo(array $mapeo): bool
    {
        return in_array('cedula', array_values($mapeo), true);
    }

    /**
     * Valida tipos de datos en una muestra de filas (Req 3.6).
     * Verifica que la columna mapeada a 'cedula' contenga solo valores numéricos de 7-8 dígitos.
     *
     * @param  array<int, array> $muestraDatos  Primeras N filas del Excel como arrays
     * @param  array<string, string> $mapeo
     * @return array<int, string>  Lista de errores (vacía = sin incompatibilidades)
     */
    public function validarTiposPostMapeo(array $muestraDatos, array $mapeo): array
    {
        // Encontrar la columna Excel que apunta al campo 'cedula'
        $columnaExcelCedula = array_search('cedula', $mapeo);

        if ($columnaExcelCedula === false) {
            return ['El campo cédula no está mapeado.'];
        }

        $normalizado = $this->normalizarCabecera($columnaExcelCedula);
        $errores = [];

        foreach ($muestraDatos as $i => $fila) {
            $valor = trim($fila[$normalizado] ?? '');

            if (empty($valor)) {
                continue; // Filas vacías se manejan en el Job
            }

            if (!ctype_digit($valor) || strlen($valor) < 7 || strlen($valor) > 8) {
                $errores[] = "Fila " . ($i + 2) . ": cédula '{$valor}' no es numérica o está fuera del rango 7-8 dígitos.";
            }
        }

        return $errores;
    }

    // -------------------------------------------------------------------------
    // Procesamiento de fila individual (motor backported del Proyecto Trabajo)
    // -------------------------------------------------------------------------

    /**
     * Procesa una fila del Excel aplicando el mapeo, validaciones y sanitización.
     * Retorna los datos listos para upsert, o null si la fila debe descartarse.
     *
     * Backported desde MetricsImport::model() del Proyecto Trabajo.
     * Adaptado a los nombres de columnas de centro_metricas del Laboratorio.
     *
     * @param  array<string, mixed> $row       Fila raw de Laravel Excel (keys = encabezados normalizados)
     * @param  array<string, string> $mapeo    ['Encabezado Excel' => 'campo_bd']
     * @param  int $numeroFila                 Para mensajes de error descriptivos
     * @return array{datos: array|null, error: string|null}
     */
    public function procesarFila(array $row, array $mapeo, int $numeroFila): array
    {
        $insertData = [];

        // 1. Aplicar mapeo dinámico de columnas
        foreach ($mapeo as $excelHeader => $dbField) {
            if (empty($dbField) || $dbField === 'ignore') {
                continue;
            }
            $normalizedHeader = Str::slug($excelHeader, '_');
            if (array_key_exists($normalizedHeader, $row)) {
                $insertData[$dbField] = $row[$normalizedHeader];
            }
        }

        // 2. Validación crítica: cédula obligatoria y numérica
        if (!isset($insertData['cedula']) || empty(trim($insertData['cedula']))) {
            return ['datos' => null, 'error' => "Fila {$numeroFila}: cédula vacía."];
        }

        $cleanCedula = trim($insertData['cedula']);

        if (!ctype_digit($cleanCedula)) {
            return ['datos' => null, 'error' => "Fila {$numeroFila}: cédula '{$cleanCedula}' contiene caracteres no numéricos."];
        }

        if (strlen($cleanCedula) < 7 || strlen($cleanCedula) > 8) {
            return ['datos' => null, 'error' => "Fila {$numeroFila}: cédula '{$cleanCedula}' fuera del rango 7-8 dígitos."];
        }

        // 3. Validación: nombre obligatorio
        if (!isset($insertData['nombres_apellidos']) || empty(trim($insertData['nombres_apellidos']))) {
            return ['datos' => null, 'error' => "Fila {$numeroFila}: campo 'nombres_apellidos' vacío."];
        }

        // 4. Sanitización y normalización
        // Campos dimensionales → mb_strtoupper para garantizar GROUP BY consistente en el dashboard.
        // Campos de texto libre → solo trim(), preservar capitalización original.
        $normalizar = fn(?string $v, string $default = 'N/P'): string =>
            mb_strtoupper(trim($v ?? $default), 'UTF-8');

        $insertData['cedula']                   = $cleanCedula;
        $insertData['nombres_apellidos']        = trim($insertData['nombres_apellidos']);
        $insertData['cargo']                    = $normalizar($insertData['cargo'] ?? null);
        $insertData['ubicacion_administrativa'] = $normalizar($insertData['ubicacion_administrativa'] ?? null);
        $insertData['planta']                   = $normalizar($insertData['planta'] ?? null);
        $insertData['filial']                   = $normalizar($insertData['filial'] ?? null);
        $insertData['estado_ubicacion_fisica']  = $normalizar($insertData['estado_ubicacion_fisica'] ?? null);
        $insertData['estado']                   = $normalizar($insertData['estado'] ?? null);
        $insertData['municipio']                = isset($insertData['municipio']) ? $normalizar($insertData['municipio'], '') ?: null : null;
        $insertData['parroquia']                = isset($insertData['parroquia']) ? $normalizar($insertData['parroquia'], '') ?: null : null;
        $insertData['telefono']                 = isset($insertData['telefono']) ? trim($insertData['telefono']) : null;
        $insertData['centro_votacion']          = isset($insertData['centro_votacion']) ? trim($insertData['centro_votacion']) : null;
        $insertData['direccion_centro_votacion']= isset($insertData['direccion_centro_votacion']) ? trim($insertData['direccion_centro_votacion']) : null;

        // El padrón principal siempre entra sin estatus de voto (se asigna en el cruce)
        $insertData['estatus_voto']             = null;

        return ['datos' => $insertData, 'error' => null];
    }

    // -------------------------------------------------------------------------
    // Cruce con archivo de votos (Req 5.1 – 5.4)
    // -------------------------------------------------------------------------

    /**
     * Procesa el segundo archivo (cédula + estatus Si/No) y actualiza centro_metricas.
     * Usa upsert por lotes de 500 en lugar de N queries individuales para soportar
     * 6,000+ registros sin timeouts (una query por lote vs una por fila).
     *
     * @param  UploadedFile $archivoVotos
     * @return array{actualizados: int, anomalias: string[], error: string|null}
     */
    public function cruzarConVotos(UploadedFile $archivoVotos): array
    {
        $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $archivoVotos->getRealPath());

        if (empty($rows) || !isset($rows[0][0])) {
            return ['actualizados' => 0, 'anomalias' => [], 'error' => 'El archivo de votos está vacío o mal formateado.'];
        }

        $hoja      = $rows[0];
        $cabeceras = $hoja[0];

        // Detección flexible de columnas de cédula y voto
        $indiceCedula = -1;
        $indiceVoto   = -1;

        foreach ($cabeceras as $index => $columna) {
            $col = strtolower(trim((string) $columna));
            if (in_array($col, ['cédula', 'cedula', 'id', 'documento'], true)) {
                $indiceCedula = $index;
            }
            if (in_array($col, ['voto', 'estado_voto', 'estado', 'votó', 'estatus', 'estatus_voto'], true)) {
                $indiceVoto = $index;
            }
        }

        if ($indiceCedula === -1 || $indiceVoto === -1) {
            return [
                'actualizados' => 0,
                'anomalias'    => [],
                'error'        => 'El archivo debe contener una columna "Cédula" y otra "Voto" o "Estatus".',
            ];
        }

        // 1. Construir el mapa completo cedula → estatus desde el archivo (en RAM, O(n) una sola vez)
        $mapaVotos = [];
        for ($i = 1; $i < count($hoja); $i++) {
            $cedula = trim((string) ($hoja[$i][$indiceCedula] ?? ''));
            $voto   = strtoupper(trim((string) ($hoja[$i][$indiceVoto] ?? '')));

            if ($cedula === '') {
                continue;
            }

            // Normalizar: SI / SÍ / S → 'Si', cualquier otro valor → 'No'
            $mapaVotos[$cedula] = in_array($voto, ['SI', 'SÍ', 'S'], true) ? 'Si' : 'No';
        }

        if (empty($mapaVotos)) {
            return ['actualizados' => 0, 'anomalias' => [], 'error' => 'El archivo de votos no contiene filas procesables.'];
        }

        // 2. Verificar qué cédulas existen realmente en centro_metricas (una sola query con IN)
        //    Hacemos la consulta en lotes de 1000 para no saturar el operador IN
        $cedulasDelArchivo  = array_keys($mapaVotos);
        $cedulasEnPadron    = collect();

        foreach (array_chunk($cedulasDelArchivo, 1000) as $lote) {
            $encontradas = CentroMetrica::whereIn('cedula', $lote)->pluck('cedula');
            $cedulasEnPadron = $cedulasEnPadron->merge($encontradas);
        }

        $cedulasEnPadronSet = $cedulasEnPadron->flip(); // Para O(1) lookup

        // 3. Separar coincidencias de anomalías
        $anomalias  = [];
        $loteUpsert = [];

        foreach ($mapaVotos as $cedula => $estatus) {
            if ($cedulasEnPadronSet->has($cedula)) {
                $loteUpsert[] = ['cedula' => $cedula, 'estatus_voto' => $estatus];
            } else {
                $anomalias[] = "Cédula {$cedula} no encontrada en el padrón principal.";
            }
        }

        // 4. Upsert en lotes de 500 — una query por lote en lugar de una por fila
        $actualizados = 0;

        DB::beginTransaction();
        try {
            foreach (array_chunk($loteUpsert, 500) as $chunk) {
                CentroMetrica::upsert(
                    $chunk,
                    uniqueBy: ['cedula'],          // columna de conflicto
                    update:   ['estatus_voto']     // columna a actualizar
                );
                $actualizados += count($chunk);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['actualizados' => 0, 'anomalias' => [], 'error' => $e->getMessage()];
        }

        return ['actualizados' => $actualizados, 'anomalias' => $anomalias, 'error' => null];
    }
}
