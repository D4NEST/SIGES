<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Bitácora de Auditoría
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $logs->total() }} eventos registrados · Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Usuario</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Evento</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Descripción</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono text-xs">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $log->usuario_nombre ?? '—' }}
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    @php
                                        $color = match(true) {
                                            str_contains($log->evento, 'DENEGADO') => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            str_contains($log->evento, 'CREACION') => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            str_contains($log->evento, 'ELIMINACION') => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                            str_contains($log->evento, 'IMPORTACION') => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            str_contains($log->evento, 'DASHBOARD') => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            default => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $color }}">
                                        {{ $log->evento }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $log->descripcion }}">
                                    {{ $log->descripcion }}
                                </td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs whitespace-nowrap">
                                    {{ $log->direccion_ip }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-400 text-sm">No hay eventos registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
