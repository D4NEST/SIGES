<?php

namespace App\Http\Controllers;

use App\Imports\VentasImport;
use App\Models\Upload;
use App\Services\AuditService;
use App\Services\VentasImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class VentasUploadController extends Controller
{
    public function __construct(
        private VentasImportService $importService,
        private AuditService $audit,
    ) {}

    /**
     * Muestra la pantalla de carga de archivos de ventas.
     */
    public function index(): View
    {
        $uploads = Upload::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('uploads.ventas-index', compact('uploads'));
    }

    /**
     * Procesa el archivo Excel de ventas.
     */
    public function parse(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('temp-ventas');

        // Crear registro de upload
        $upload = Upload::create([
            'user_id'        => auth()->id(),
            'filename'       => $path,
            'original_name'  => $file->getClientOriginalName(),
            'status'         => 'pending',
            'processed_rows' => 0,
        ]);

        // Leer encabezados del archivo
        $headings = $this->importService->leerEncabezados($file);

        // Validar estructura
        $validacion = $this->importService->validarEstructura($headings);

        if (!$validacion['valido']) {
            $upload->update(['status' => 'failed']);
            Storage::delete($path);

            $this->audit->registrar(
                'RECHAZO_ESTRUCTURA_VENTAS',
                "Archivo {$upload->original_name} rechazado: {$validacion['mensaje']}"
            );

            return redirect()->route('ventas.upload.index')
                ->withErrors(['error' => $validacion['mensaje']]);
        }

        // Construir mapeo automático
        $mapping = $this->importService->construirMapeoAutomatico($headings);

        $this->audit->registrar(
            'CARGA_VENTAS_DETECTADA',
            "Estructura del archivo {$upload->original_name} validada. Procediendo a importación."
        );

        return $this->ejecutarImportacion($upload, $mapping);
    }

    /**
     * Ejecuta la importación de ventas.
     */
    private function ejecutarImportacion(Upload $upload, array $mapping): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $import = new VentasImport($mapping, $upload->id);
            Excel::import($import, Storage::path($upload->filename));

            DB::commit();

            // Contar registros importados
            $registrosImportados = \App\Models\Venta::where('upload_id', $upload->id)->count();
            
            $upload->update([
                'status'         => 'completed',
                'processed_rows' => $registrosImportados,
            ]);

            Storage::delete($upload->filename);

            $this->audit->registrar(
                'IMPORTACION_VENTAS_EXITOSA',
                "Se importaron {$registrosImportados} registros de ventas desde {$upload->original_name}"
            );

            return redirect()->route('ventas.upload.index')
                ->with('success', "¡Importación exitosa! Se procesaron {$registrosImportados} registros de ventas.");

        } catch (\Exception $e) {
            DB::rollBack();
            $upload->update(['status' => 'failed']);
            Storage::delete($upload->filename);

            $this->audit->registrar(
                'ERROR_IMPORTACION_VENTAS',
                "Error al importar {$upload->original_name}: {$e->getMessage()}"
            );

            return redirect()->route('ventas.upload.index')
                ->withErrors(['error' => 'Error en la importación: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesa mapeo manual de columnas.
     */
    public function processMapping(Request $request): RedirectResponse
    {
        $request->validate([
            'upload_id' => 'required|integer|exists:uploads,id',
            'mapping'   => 'required|array',
        ]);

        $upload = Upload::findOrFail($request->input('upload_id'));

        // Filtrar campos vacíos
        $mapping = array_filter($request->input('mapping', []), function ($value) {
            return !empty($value) && is_string($value);
        });

        if (empty($mapping)) {
            return redirect()->route('ventas.upload.index')
                ->withErrors(['error' => 'No se proporcionó ningún mapeo válido.']);
        }

        // Validar que el campo producto esté mapeado
        if (!in_array('producto', $mapping)) {
            return redirect()->route('ventas.upload.index')
                ->withErrors(['error' => 'El campo "producto" es obligatorio.']);
        }

        return $this->ejecutarImportacion($upload, $mapping);
    }

    /**
     * Muestra el wizard de mapeo manual.
     */
    public function showMappingWizard(int $uploadId): View
    {
        $upload = Upload::findOrFail($uploadId);
        $headings = $this->importService->leerEncabezados(Storage::path($upload->filename));
        $camposDisponibles = $this->importService->getCamposDisponibles();

        return view('uploads.ventas-mapping', compact('upload', 'headings', 'camposDisponibles'));
    }
}
