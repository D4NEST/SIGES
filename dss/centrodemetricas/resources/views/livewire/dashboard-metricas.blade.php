<div wire:poll.30s="actualizarMetricas">
    {{-- Botón de exportar resumen general --}}
    <div class="max-w-7xl mx-auto mb-4 flex justify-end">
        <button onclick="exportarResumenPDF()" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-500 dark:to-teal-500 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-medium">Exportar Resumen PDF</span>
        </button>
    </div>

    {{-- KPIs en tarjetas horizontales (estilo glass mejorado) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 max-w-7xl mx-auto">
        {{-- Tarjeta 1: Total Ventas --}}
        <x-kpi-card title="Total Ventas" :value="number_format($resumen['total_ventas'] ?? 0)">
            <x-slot name="icon">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </x-slot>
        </x-kpi-card>

        {{-- Tarjeta 2: Monto Total --}}
        <x-kpi-card title="Ingresos Totales" :value="'$' . number_format($resumen['monto_total'] ?? 0, 2)">
            <x-slot name="icon">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot>
        </x-kpi-card>

        {{-- Tarjeta 3: Completadas --}}
        <x-kpi-card title="Ventas Completadas" :value="number_format($resumen['completadas'] ?? 0)">
            <x-slot name="icon">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot>
        </x-kpi-card>

        {{-- Tarjeta 4: Tasa de Conversión --}}
        <x-kpi-card title="Tasa Conversión" :value="($resumen['tasa_conversion'] ?? 0) . '%'">
            <x-slot name="icon">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </x-slot>
            <x-slot name="extra">
                <div class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full mt-2">
                    <div class="h-full bg-emerald-600 dark:bg-emerald-400 rounded-full transition-all duration-1000" style="width: {{ $resumen['tasa_conversion'] ?? 0 }}%"></div>
                </div>
            </x-slot>
        </x-kpi-card>
    </div>

    {{-- Segunda fila de KPIs (estilo glass) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6 max-w-7xl mx-auto">
        {{-- Ticket Promedio --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ticket Promedio</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($ticketPromedio['promedio'] ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="mt-3 flex justify-between text-xs text-gray-400 dark:text-gray-500">
                <span>Mín: ${{ number_format($ticketPromedio['minimo'] ?? 0, 2) }}</span>
                <span>Máx: ${{ number_format($ticketPromedio['maximo'] ?? 0, 2) }}</span>
            </div>
        </div>

        {{-- Crecimiento Mensual --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                @php
                    $crecimiento = $comparativaMensual['crecimiento_monto'] ?? 0;
                    $bgClass = $crecimiento >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30';
                    $textClass = $crecimiento >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $pathD = $crecimiento >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6';
                @endphp
                <div class="p-3 {{ $bgClass }} rounded-lg">
                    <svg class="w-6 h-6 {{ $textClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $pathD }}"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">vs Mes Anterior</p>
                    <p class="text-2xl font-bold {{ $textClass }}">
                        {{ $crecimiento >= 0 ? '+' : '' }}{{ number_format($crecimiento, 1) }}%
                    </p>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                Este mes: ${{ number_format($comparativaMensual['este_mes']['monto'] ?? 0, 2) }}
            </div>
        </div>

        {{-- Ventas Pendientes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ventas Pendientes</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($resumen['pendientes'] ?? 0) }}</p>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                ${{ number_format($resumen['monto_pendiente'] ?? 0, 2) }} por cobrar
            </div>
        </div>
    </div>

    {{-- Tabs con efecto glass (estilo repo) --}}
    <div x-data="{ tab: 'mas_vendidos' }" class="max-w-7xl mx-auto relative rounded-2xl border border-white/10 dark:border-white/10 bg-white/10 dark:bg-gray-800/40 backdrop-blur-xl shadow-lg overflow-hidden">
        <div class="flex overflow-x-auto border-b border-white/10 dark:border-white/10 bg-white/5 dark:bg-black/20">
            <button @click="tab = 'mas_vendidos'" :class="tab === 'mas_vendidos' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">🏆 Más Vendidos</button>
            <button @click="tab = 'menos_vendidos'" :class="tab === 'menos_vendidos' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">📉 Menos Vendidos</button>
            <button @click="tab = 'region'" :class="tab === 'region' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">🗺️ Por Región</button>
            <button @click="tab = 'canal'" :class="tab === 'canal' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">🛒 Por Canal</button>
            <button @click="tab = 'categoria'" :class="tab === 'categoria' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">📦 Por Categoría</button>
            <button @click="tab = 'hora'" :class="tab === 'hora' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">🕐 Por Hora</button>
            <button @click="tab = 'tendencia'" :class="tab === 'tendencia' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">📈 Tendencia</button>
            <button @click="tab = 'margen'" :class="tab === 'margen' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">💰 Mayor Margen</button>
            <button @click="tab = 'vendedor'" :class="tab === 'vendedor' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-white/10 dark:bg-white/5' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/5 dark:hover:bg-white/5'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200">👤 Por Vendedor</button>
        </div>

        <div class="p-4">
            {{-- Top Productos Más Vendidos --}}
            <div x-show="tab === 'mas_vendidos'" x-cloak>
                <div class="space-y-2">
                    @forelse ($topMasVendidos ?? [] as $i => $producto)
                        <div class="flex items-center gap-3 p-3 rounded-lg transition-colors {{ $loop->first ? 'bg-emerald-50 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-700' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60' }}">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold {{ $loop->first ? 'bg-emerald-600 text-white dark:bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $i + 1 }}</span>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $producto['producto'] }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $producto['total_transacciones'] }} transacciones</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($producto['total_unidades']) }} uds</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">${{ number_format($producto['monto_total'], 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No hay datos disponibles</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Productos Menos Vendidos --}}
            <div x-show="tab === 'menos_vendidos'" x-cloak>
                <div class="space-y-2">
                    @forelse ($topMenosVendidos ?? [] as $i => $producto)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">{{ $i + 1 }}</span>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $producto['producto'] }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $producto['total_transacciones'] }} transacciones</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600 dark:text-red-400">{{ number_format($producto['total_unidades']) }} uds</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">${{ number_format($producto['monto_total'], 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No hay datos disponibles</p>
                    @endforelse
                </div>
            </div>

            {{-- Por Región --}}
            <div x-show="tab === 'region'" x-cloak>
                <div class="relative rounded-xl p-4 transition-all duration-300" style="height:350px;">
                    <canvas id="chartRegion" data-labels='@json(array_column($porRegion ?? [], 'region'))' data-values='@json(array_column($porRegion ?? [], 'monto_total'))'></canvas>
                </div>
            </div>

            {{-- Por Canal --}}
            <div x-show="tab === 'canal'" x-cloak>
                <div class="relative rounded-xl p-4 transition-all duration-300" style="height:350px;">
                    <canvas id="chartCanal" data-labels='@json(array_column($porCanal ?? [], 'canal_venta'))' data-values='@json(array_column($porCanal ?? [], 'monto_total'))'></canvas>
                </div>
            </div>

            {{-- Por Categoría --}}
            <div x-show="tab === 'categoria'" x-cloak>
                <div class="relative rounded-xl p-4 transition-all duration-300" style="height:350px;">
                    <canvas id="chartCategoria" data-labels='@json(array_column($porCategoria ?? [], 'categoria'))' data-values='@json(array_column($porCategoria ?? [], 'monto_total'))'></canvas>
                </div>
            </div>

            {{-- Por Hora --}}
            <div x-show="tab === 'hora'" x-cloak>
                <div class="relative rounded-xl p-4 transition-all duration-300" style="height:350px;">
                    <canvas id="chartHora" data-labels='@json(array_column($porHora ?? [], 'hora_formateada'))' data-values='@json(array_column($porHora ?? [], 'total_ventas'))'></canvas>
                </div>
            </div>

            {{-- Tendencia --}}
            <div x-show="tab === 'tendencia'" x-cloak>
                <div class="relative rounded-xl p-4 transition-all duration-300" style="height:350px;">
                    <canvas id="chartTendencia" data-labels='@json(array_column($tendencia ?? [], 'fecha_venta'))' data-values='@json(array_column($tendencia ?? [], 'monto_total'))'></canvas>
                </div>
            </div>

            {{-- Mayor Margen --}}
            <div x-show="tab === 'margen'" x-cloak>
                <div class="space-y-2">
                    @forelse ($mayorMargen ?? [] as $i => $producto)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">{{ $i + 1 }}</span>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $producto['producto'] }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $producto['ventas'] }} ventas</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-amber-600 dark:text-amber-400">{{ number_format($producto['margen_promedio'], 1) }}%</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">${{ number_format($producto['monto_total'], 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No hay datos disponibles</p>
                    @endforelse
                </div>
            </div>

            {{-- Por Vendedor --}}
            <div x-show="tab === 'vendedor'" x-cloak>
                <div class="space-y-2">
                    @forelse ($porVendedor ?? [] as $i => $vendedor)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">{{ $i + 1 }}</span>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $vendedor['vendedor'] }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Ticket prom: ${{ number_format($vendedor['ticket_promedio'], 2) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($vendedor['monto_total'], 2) }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $vendedor['total_ventas'] }} ventas</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No hay datos disponibles</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        // Paleta de colores para DSS de Ventas (tomada del repo, mejorada)
        const PALETTES = {
            region: ['#10B981', '#059669', '#047857', '#065F46', '#064E3B', '#022C22'],
            canal: ['#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981'],
            categoria: ['#F59E0B', '#EF4444', '#8B5CF6', '#3B82F6', '#10B981', '#EC4899', '#6366F1'],
            hora: ['#0EA5E9', '#06B6D4', '#14B8A6', '#10B981', '#22C55E', '#84CC16', '#EAB308', '#F59E0B'],
            tendencia: ['#10B981', '#059669', '#047857']
        };

        function initCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
            const textColor = isDark ? '#9CA3AF' : '#6B7280';

            const chartConfigs = {
                chartRegion: { 
                    type: 'bar', 
                    colors: PALETTES.region, 
                    label: 'Ingresos por Región ($)'
                },
                chartCanal: { 
                    type: 'doughnut', 
                    colors: PALETTES.canal, 
                    label: 'Ingresos por Canal ($)'
                },
                chartCategoria: { 
                    type: 'bar', 
                    colors: PALETTES.categoria, 
                    label: 'Ingresos por Categoría ($)'
                },
                chartHora: { 
                    type: 'line', 
                    colors: PALETTES.hora, 
                    label: 'Ventas por Hora',
                    lineColor: '#10B981',
                    lineBorderColor: '#059669'
                },
                chartTendencia: { 
                    type: 'line', 
                    colors: PALETTES.tendencia, 
                    label: 'Tendencia de Ventas ($)',
                    lineColor: '#10B981',
                    lineBorderColor: '#059669'
                }
            };

            Object.keys(chartConfigs).forEach(id => {
                const canvas = document.getElementById(id);
                if (!canvas) return;

                const existing = Chart.getChart(id);
                if (existing) existing.destroy();

                let labels = [];
                let values = [];
                try {
                    const labelsData = canvas.dataset.labels;
                    const valuesData = canvas.dataset.values;
                    
                    if (labelsData && labelsData !== '[]' && labelsData !== 'null') {
                        labels = JSON.parse(labelsData);
                    }
                    if (valuesData && valuesData !== '[]' && valuesData !== 'null') {
                        values = JSON.parse(valuesData);
                    }
                } catch (e) {
                    console.warn('Error parsing data for', id, e);
                }

                const config = chartConfigs[id];
                const isDoughnut = config.type === 'doughnut';
                const isLine = config.type === 'line';

                if (!labels.length || !values.length) {
                    labels = ['Sin datos'];
                    values = [0];
                }

                let backgroundColor;
                let borderColor;
                
                if (isLine) {
                    backgroundColor = config.lineColor;
                    borderColor = config.lineBorderColor;
                } else if (isDoughnut) {
                    backgroundColor = config.colors.slice(0, values.length);
                    borderColor = '#ffffff';
                } else {
                    backgroundColor = values.map((_, i) => config.colors[i % config.colors.length]);
                    borderColor = values.map((_, i) => config.colors[i % config.colors.length]);
                }

                new Chart(canvas, {
                    type: config.type,
                    data: {
                        labels: labels,
                        datasets: [{ 
                            label: config.label || 'Monto ($)', 
                            data: values,
                            backgroundColor: backgroundColor,
                            borderColor: borderColor,
                            borderWidth: isDoughnut ? 2 : isLine ? 3 : 1,
                            fill: isLine ? false : true,
                            tension: isLine ? 0.4 : 0,
                            pointBackgroundColor: isLine ? config.lineColor : undefined,
                            pointBorderColor: isLine ? '#fff' : undefined,
                            pointRadius: isLine ? 4 : undefined,
                            pointHoverRadius: isLine ? 6 : undefined
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                display: isDoughnut,
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    color: textColor,
                                    font: { size: 11 }
                                }
                            }
                        },
                        scales: !isDoughnut ? {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: textColor,
                                    font: { size: 10 }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: textColor,
                                    font: { size: 10 }
                                }
                            }
                        } : undefined
                    }
                });
            });
        }

        // Función para exportar resumen a PDF (fusionada y mejorada)
        async function exportarResumenPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            
            const pageWidth = doc.internal.pageSize.getWidth();
            let yPosition = 20;

            doc.setFontSize(20);
            doc.setTextColor(16, 185, 129);
            doc.text('Dashboard de Ventas - DSS', pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 10;

            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            const fecha = new Date().toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            doc.text('Generado: ' + fecha, pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 15;

            // KPIs principales (extraer del DOM)
            const kpiElements = document.querySelectorAll('.grid > div');
            const kpiData = [];
            kpiElements.forEach(card => {
                const title = card.querySelector('.kpi-title, h3, p:first-child');
                const value = card.querySelector('.kpi-value, .text-2xl, .font-bold');
                if (title && value) {
                    kpiData.push({
                        title: title.textContent.trim(),
                        value: value.textContent.trim()
                    });
                }
            });

            if (kpiData.length > 0) {
                doc.setFillColor(240, 253, 244);
                doc.roundedRect(15, yPosition, pageWidth - 30, 40, 3, 3, 'F');
                const kpiWidth = (pageWidth - 40) / kpiData.length;
                kpiData.forEach((kpi, index) => {
                    const xPosition = 20 + (index * kpiWidth);
                    doc.setFontSize(9);
                    doc.setTextColor(100, 100, 100);
                    doc.text(kpi.title, xPosition, yPosition + 12);
                    doc.setFontSize(16);
                    doc.setTextColor(16, 185, 129);
                    doc.text(kpi.value, xPosition, yPosition + 28);
                });
                yPosition += 50;
            }

            // Gráficas
            const chartIds = ['chartRegion', 'chartCanal', 'chartCategoria', 'chartHora', 'chartTendencia'];
            const chartNames = ['Por Región', 'Por Canal', 'Por Categoría', 'Por Hora', 'Tendencia'];

            for (let i = 0; i < chartIds.length; i++) {
                const canvas = document.getElementById(chartIds[i]);
                if (!canvas) continue;

                if (yPosition > 200) {
                    doc.addPage();
                    yPosition = 20;
                }

                doc.setFontSize(12);
                doc.setTextColor(50, 50, 50);
                doc.text(chartNames[i], 15, yPosition);
                yPosition += 5;

                try {
                    const chartImage = canvas.toDataURL('image/png', 1.0);
                    doc.addImage(chartImage, 'PNG', 15, yPosition, pageWidth - 30, 60);
                    yPosition += 70;
                } catch (e) {
                    console.warn('Error capturando gráfica:', chartIds[i], e);
                }
            }

            doc.save('dashboard_ventas_' + new Date().toISOString().slice(0,10) + '.pdf');
        }

        // Inicialización
        document.addEventListener('livewire:initialized', () => {
            setTimeout(initCharts, 200);
        });
        document.addEventListener('livewire:update', () => {
            setTimeout(initCharts, 300);
        });
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initCharts, 500);
        });
        setTimeout(initCharts, 1000);
    </script>
</div>