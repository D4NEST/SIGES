<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Panel Operativo
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Acciones rápidas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('uploads.index') }}"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow p-6 flex items-center gap-4 transition-colors">
                    <span class="text-3xl">📤</span>
                    <div>
                        <p class="font-bold text-lg">Cargar Padrón Excel</p>
                        <p class="text-indigo-200 text-sm">Subir archivo de personal o votos</p>
                    </div>
                </a>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 flex items-center gap-4">
                    <span class="text-3xl">📋</span>
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white text-lg">{{ $totalUploads }} cargas</p>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">realizadas en total</p>
                    </div>
                </div>
            </div>

            {{-- Historial de importaciones --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Historial de importaciones</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Archivo</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Filas</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($uploads as $upload)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $upload->original_name }}">
                                    📄 {{ $upload->original_name }}
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $statusColor = match($upload->status) {
                                            'completed'             => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'completed_with_errors' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'failed'                => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'processing'            => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            default                 => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        };
                                        $statusLabel = match($upload->status) {
                                            'completed'             => '✅ Completado',
                                            'completed_with_errors' => '⚠️ Con errores',
                                            'failed'                => '❌ Fallido',
                                            'processing'            => '⏳ Procesando',
                                            default                 => '🕐 Pendiente',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $statusColor }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $upload->processed_rows ?? '—' }}
                                    @if($upload->total_rows)
                                        / {{ $upload->total_rows }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono whitespace-nowrap">
                                    {{ $upload->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">
                                    Aún no has realizado ninguna importación.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $uploads->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
