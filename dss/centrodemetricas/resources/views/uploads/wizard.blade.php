<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-2 text-gray-800">Asistente de Mapeo de Columnas</h2>
                <p class="text-sm text-gray-500 mb-6">Asocia las columnas de tu archivo <strong>({{ $upload->original_name }})</strong> con los campos del padrón electoral requeridos por el sistema.</p>
                
                <form action="{{ route('uploads.process') }}" method="POST"> 
                    @csrf
                    <input type="hidden" name="upload_id" value="{{ $upload->id }}">

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 font-bold text-gray-700 border-b pb-2">
                            <div>Columna del Archivo Detectada</div>
                            <div>Campo correspondiente en el Sistema</div>
                        </div>

                        @foreach($headings as $index => $heading)
                            <div class="grid grid-cols-2 gap-4 items-center bg-gray-50 p-3 rounded-md hover:bg-gray-100 transition">
                                <span class="font-medium text-gray-800">{{ $heading }}</span>
                                
                                <div>
                                    <select name="mapping[{{ $heading }}]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="">-- Ignorar esta columna --</option>
                                        <option value="cedula">Cédula de Identidad (cedula)</option>
                                        <option value="nombre_apellido">Nombre y Apellido (nombre_apellido)</option>
                                        <option value="cargo">Cargo del Trabajador (cargo)</option>
                                        <option value="ubicacion_administrativa">Ubicación Administrativa / Dpto (ubicacion_administrativa)</option>
                                        <option value="planta">Planta Física (planta)</option>
                                        <option value="filial">Filial / Empresa (filial)</option>
                                        <option value="estado_fisico">Estado Ubicación Física (estado_fisico)</option>
                                        <option value="telefono">Número Telefónico (telefono)</option>
                                        <option value="municipio">Municipio Electoral (municipio)</option>
                                        <option value="parroquia">Parroquia Electoral (parroquia)</option>
                                        <option value="centro_votacion">Centro de Votación (centro_votacion)</option>
                                        <option value="direccion_centro">Dirección del Centro de Votación (direccion_centro)</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 flex justify-between border-t pt-4">
                        <a href="{{ route('uploads.index') }}" class="text-gray-600 hover:text-gray-800 font-medium py-2">
                            ← Cancelar y volver
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow transition">
                            Iniciar Carga de Personal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>