<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Upload; 
use App\Imports\VotosImport; 
use App\Models\VotoPersonal; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\MetricasController; 
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UploadController extends Controller
{
    /**
     * Muestra la pantalla inicial de arrastrar y soltar archivo
     */
    public function index(): View
    {
        $uploads = Upload::where('user_id', Auth::id())->latest()->paginate(5);
        return view('uploads.index', compact('uploads'));
    }

    /**
     * Recibe el archivo, valida estrictamente la estructura oficial y procesa automáticamente.
     * Si no cumple perfectamente, se rechaza y se informa el error sin dar opción a mapeos.
     */
    public function parse(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:20480', 
        ]);

        $file = $request->file('file');
        $path = $file->store('temp-uploads');

        $upload = Upload::create([
            'user_id'         => Auth::id(),
            'filename'        => $path, 
            'original_name'   => $file->getClientOriginalName(), 
            'status'          => 'pending',
            'processed_rows'  => 0,
        ]);
        
        $copiedPath = Storage::path($path);
        
        $headings = [];
        $array = Excel::toArray([], $copiedPath);
        if (!empty($array) && isset($array[0][0])) {
            $headings = $array[0][0]; 
        }

        // =========================================================================
        // 🔒 CAPA DE SEGURIDAD OPERATIVA: VALIDACIÓN BASADA EN FORMATO REAL
        // =========================================================================
        
        // Estructura oficial limpia y mapeable 1:1 basada exactamente en image_463e5f.png
        $estructuraOficialMap = [
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
            'direccion_centro_de_votacion'
        ];

        // Normalización para neutralizar discrepancias de digitación humana en la plantilla
        $headingsNormalizados = array_map(function($heading) {
            $limpio = trim(mb_strtolower($heading, 'UTF-8'));
            // Sanitizar acentos comunes de la cabecera real (é, ó, í, á)
            $limpio = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $limpio);
            // Reemplazar espacios y conectores no permitidos por guiones bajos
            $limpio = preg_replace('/[^a-z0-9_]/', '_', $limpio);
            return trim(preg_replace('/_+/', '_', $limpio), '_');
        }, $headings);

        // Buscar discrepancias analíticas entre las estructuras
        $columnasFaltantes = array_diff($estructuraOficialMap, $headingsNormalizados);
        $columnasExcedentes = array_diff($headingsNormalizados, $estructuraOficialMap);

        // ESCENARIO 1: No cumple con todas las columnas obligatorias del formato real
        if (!empty($columnasFaltantes)) {
            $upload->update(['status' => 'failed']);
            Storage::delete($path);

            MetricasController::registrarAuditoria(
                'RECHAZO_ESTRUCTURA_INVALIDA', 
                "Archivo: {$upload->original_name} rechazado por falta de columnas oficiales."
            );

            return redirect()->route('uploads.index')->withErrors([
                'error' => "Estructura inválida. La plantilla oficial requiere campos esenciales que no se detectaron en su archivo. Por favor, rectifique el formato."
            ]);
        }

        // ESCENARIO 2: Intento flagrante de alterar la cantidad de columnas (Inyección)
        if (!empty($columnasExcedentes)) {
            $upload->update(['status' => 'failed']);
            Storage::delete($path);

            MetricasController::registrarAuditoria(
                'RECHAZO_INTENTO_INYECCION', 
                "Carga blaquébada por seguridad. Se detectó una estructura alterada en: {$upload->original_name}."
            );

            return redirect()->route('uploads.index')->withErrors([
                'error' => "Carga CLI_BLOCKED: Carga blaquébada por seguridad. Se detectaron columnas o campos adicionales no autorizados fuera del formato estándar."
            ]);
        }

        // ESCENARIO 3: Los nombres existen pero se desordenó la secuencia estricta A-M
        if ($estructuraOficialMap !== $headingsNormalizados) {
            $upload->update(['status' => 'failed']);
            Storage::delete($path);

            MetricasController::registrarAuditoria(
                'RECHAZO_ORDEN_ALTERADO', 
                "Archivo rechazado. Se detectó alteración en la secuencia de columnas de: {$upload->original_name}."
            );

            return redirect()->route('uploads.index')->withErrors([
                'error' => "Error de formato: El orden de las columnas ha sido alterado. El archivo debe respetar estrictamente la secuencia de la plantilla oficial."
            ]);
        }

        // =========================================================================
        // 🛠️ PROCESAMIENTO AUTOMÁTICO DIRECTO CON TRADUCCIÓN DE CAMPOS A LA BD
        // =========================================================================
        
        // Diccionario que mapea la cabecera del Excel ya normalizada al campo exacto de la BD
        $traductorCamposBD = [
            'cedula'                       => 'cedula',
            'nombres_y_apellidos'          => 'nombre_apellido', 
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
            'direccion_centro_de_votacion' => 'direccion_centro_votacion'
        ];

        $autoMapping = [];
        foreach ($headings as $index => $originalHeading) {
            $cabeceraNormalizada = $headingsNormalizados[$index];
            
            if (isset($traductorCamposBD[$cabeceraNormalizada])) {
                $autoMapping[$originalHeading] = $traductorCamposBD[$cabeceraNormalizada];
            } else {
                $autoMapping[$originalHeading] = $cabeceraNormalizada;
            }
        }

        MetricasController::registrarAuditoria(
            'CARGA_AUTOMATICA_DETECTADA', 
            "Estructura del archivo '{$upload->original_name}' validada con éxito. Procediendo a la inserción directa."
        );

        return $this->ejecutarImportacionDirecta($upload, $autoMapping);
    }

    /**
     * Sub-método privado que procesa la inyección directa en la BD para evitar duplicar código
     */
    private function ejecutarImportacionDirecta(Upload $upload, array $mapping): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $import = new VotosImport($mapping, $upload->id);
            Excel::import($import, Storage::path($upload->filename));

            if (!empty($import->errorsList)) {
                DB::rollBack();
                $upload->update(['status' => 'failed']);
                if (Storage::exists($upload->filename)) {
                    Storage::delete($upload->filename);
                }

                return redirect()->route('uploads.index')
                    ->withErrors(['error' => 'La carga automática falló por inconsistencia en los datos de las filas.'])
                    ->with('error_messages', $import->errorsList);
            }

            DB::commit();
            
            // =========================================================================
            // 🛡️ SOLUCIÓN AL ERROR 1265: Evitamos usar 'success' por restricción de ENUM
            // =========================================================================
            $upload->update(['status' => 'pending']); // Se mantiene en pending o cámbialo a 'completed' si tu BD lo usa
            
            if (Storage::exists($upload->filename)) {
                Storage::delete($upload->filename);
            }

            return redirect()->route('uploads.index')->with('success', "¡Padrón indexado automáticamente con éxito y sin intermediarios!");

        } catch (\Exception $e) {
            DB::rollBack();
            $upload->update(['status' => 'failed']);
            if (Storage::exists($upload->filename)) {
                Storage::delete($upload->filename);
            }
            return redirect()->route('uploads.index')->withErrors(['error' => 'Error crítico en inyección automática: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesa el mapeo manual enviado desde el asistente (`uploads.process`).
     */
    public function processMapping(Request $request): RedirectResponse
    {
        $request->validate([
            'upload_id' => 'required|integer|exists:uploads,id',
            'mapping' => 'required|array',
        ]);

        $upload = Upload::findOrFail($request->input('upload_id'));

        // Filtrar campos sin asignar (valor vacío) y normalizar a string
        $mappingRaw = $request->input('mapping', []);
        $mapping = [];
        foreach ($mappingRaw as $original => $dest) {
            if (!empty($dest) && is_string($dest)) {
                $mapping[$original] = $dest;
            }
        }

        if (empty($mapping)) {
            return redirect()->route('uploads.index')->withErrors(['error' => 'No se proporcionó ningún mapeo válido.']);
        }

        return $this->ejecutarImportacionDirecta($upload, $mapping);
    }

    /**
     * MOTOR ADICIONAL EXTRA: Procesa el SEGUNDO archivo (Estatus de Votación Rápida SI/NO)
     */
    public function procesarSegundoArchivoVotos(Request $request): RedirectResponse
    {
        $request->validate([
            'votos_file' => 'required|mimes:xlsx,csv,xls|max:10240'
        ]);

        $file = $request->file('votos_file');
        $path = $file->store('temp-votos');
        $realPath = Storage::path($path);

        $rows = Excel::toArray([], $realPath);
        
        if (empty($rows) || !isset($rows[0][0])) {
            Storage::delete($path);
            return redirect()->back()->withErrors(['error' => 'El archivo de votos está vacío o mal formateado.']);
        }

        $hoja = $rows[0];
        $cabeceras = $hoja[0]; 

        $indiceCedula = -1;
        $indiceVoto = -1;

        foreach ($cabeceras as $index => $columna) {
            $columnaLimpia = strtolower(trim($columna));
            
            if (in_array($columnaLimpia, ['cédula', 'cedula', 'id', 'documento'])) {
                $indiceCedula = $index;
            }
            if (in_array($columnaLimpia, ['voto', 'estado_voto', 'estado', 'votó', 'estatus'])) {
                $indiceVoto = $index;
            }
        }

        if ($indiceCedula === -1 || $indiceVoto === -1) {
            Storage::delete($path);
            return redirect()->back()->withErrors([
                'error' => 'No se pudo procesar automáticamente. El archivo debe contener una columna llamada "Cédula" y otra llamada "Voto" o "Estatus".'
            ]);
        }

        DB::beginTransaction();
        try {
            $totalActualizados = 0;
            
            for ($i = 1; $i < count($hoja); $i++) {
                $fila = $hoja[$i];
                
                $cedulaRaw = $fila[$indiceCedula] ?? '';
                $votoRaw   = $fila[$indiceVoto] ?? '';

                $cedula = trim($cedulaRaw); 
                $voto   = strtoupper(trim($votoRaw)); 

                if (!empty($cedula)) {
                    $actualizado = VotoPersonal::where('cedula', $cedula)
                        ->update(['estado_voto' => ($voto === 'SI' || $voto === 'SÍ' ? 'SI' : 'NO')]);
                    
                    if ($actualizado) {
                        $totalActualizados++;
                    }
                }
            }

            DB::commit();
            Storage::delete($path);

            MetricasController::registrarAuditoria(
                'ACTUALIZACION_VOTOS_MASIVA', 
                "Se sincronizaron los estatus de votación de forma automatizada. Total registros afectados: {$totalActualizados}."
            );

            return redirect()->route('uploads.index')->with('success', "¡Estatus de votación actualizado! Se sincronizaron {$totalActualizados} registros en tiempo real.");

        } catch (\Exception $e) {
            DB::rollBack();
            Storage::delete($path);
            return redirect()->back()->withErrors(['error' => 'Fallo al sincronizar el archivo de votos: ' . $e->getMessage()]);
        }
    }
}