<x-app-layout>
    <div class="py-12" x-data="{ tab: 'padron' }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative font-medium shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative font-medium shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex space-x-2 mb-4 bg-gray-200 p-1 rounded-lg">
                <button @click="tab = 'padron'" 
                    :class="tab === 'padron' ? 'bg-white text-indigo-700 shadow-md' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50'"
                    class="flex-1 text-center font-bold py-2.5 px-4 rounded-md transition text-sm">
                    👤 1. Cargar Padrón Maestro (Personal)
                </button>
                <button @click="tab = 'votos'" 
                    :class="tab === 'votos' ? 'bg-white text-green-700 shadow-md' : 'text-gray-600 hover:text-green-600 hover:bg-gray-50'"
                    class="flex-1 text-center font-bold py-2.5 px-4 rounded-md transition text-sm">
                    🗳️ 2. Sincronizar Estatus de Votos (SI/NO)
                </button>
            </div>

            <div x-show="tab === 'padron'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 transition">
                <h2 class="text-2xl font-bold mb-1 text-gray-800">Cargar Padrón de Personal</h2>
                <p class="text-xs text-gray-500 mb-4">Indexa o actualiza el maestro corporativo de 13 columnas. Si el formato es oficial, se procesará automáticamente.</p>
                
                <form action="{{ route('uploads.parse') }}" method="POST" enctype="multipart/form-data" 
                      x-data="{ isDragging: false, fileName: '' }">
                    @csrf
                    
                    <div class="border-4 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
                         :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; $refs.fileInputPadron.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0].name"
                         @click="$refs.fileInputPadron.click()">
                        
                        <input type="file" name="file" x-ref="fileInputPadron" class="hidden" 
                               @change="fileName = $refs.fileInputPadron.files[0].name" required>
                        
                        <div class="space-y-2" x-show="!fileName">
                            <svg class="mx-auto h-12 w-12 text-indigo-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v24a4 4 0 004 4h24a4 4 0 004-4V20L28 8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p class="text-gray-700 font-medium text-sm">Arrastra el Padrón Excel aquí o haz clic para buscar</p>
                            <p class="text-xs text-gray-400">Formatos: XLSX, CSV, XLS (Máx. 20MB)</p>
                        </div>

                        <div x-show="fileName" class="text-indigo-600 font-semibold flex flex-col items-center space-y-2" x-cloak>
                            <span class="text-xs text-gray-400">Archivo seleccionado para Padrón:</span>
                            <span x-text="fileName" class="text-base text-indigo-700 break-all"></span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded shadow transition text-sm">
                            Procesar Padrón Maestro →
                        </button>
                    </div>
                </form>
            </div>

            <div x-show="tab === 'votos'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 transition" x-cloak>
                <h2 class="text-2xl font-bold mb-1 text-gray-800">Sincronizar Estatus de Votación</h2>
                <p class="text-xs text-gray-500 mb-4">Actualiza masivamente quién ya ejerció el voto. Estructura fija requerida en el Excel: Columna A (Cédula) y Columna B (SI/NO).</p>
                
                <form action="{{ route('uploads.segundo') }}" method="POST" enctype="multipart/form-data" 
                      x-data="{ isDragging: false, fileName: '' }">
                    @csrf
                    
                    <div class="border-4 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
                         :class="isDragging ? 'border-green-500 bg-green-50' : 'border-gray-300 hover:border-green-400'"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; $refs.fileInputVotos.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0].name"
                         @click="$refs.fileInputVotos.click()">
                        
                        <input type="file" name="votos_file" x-ref="fileInputVotos" class="hidden" 
                               @change="fileName = $refs.fileInputVotos.files[0].name" required>
                        
                        <div class="space-y-2" x-show="!fileName">
                            <svg class="mx-auto h-12 w-12 text-green-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M9 22L20 32L39 12" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p class="text-gray-700 font-medium text-sm">Arrastra el archivo de Votos (SI/NO) aquí o haz clic</p>
                            <p class="text-xs text-gray-400">Formatos: XLSX, CSV, XLS (Máx. 10MB)</p>
                        </div>

                        <div x-show="fileName" class="text-green-600 font-semibold flex flex-col items-center space-y-2" x-cloak>
                            <span class="text-xs text-gray-400">Archivo de votación rápida seleccionado:</span>
                            <span x-text="fileName" class="text-base text-green-700 break-all"></span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow transition text-sm">
                            Sincronizar Votos en Tiempo Real ⚡
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>