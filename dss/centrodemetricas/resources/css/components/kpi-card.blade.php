@props(['title', 'value', 'icon' => null, 'extra' => null])

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-xl border border-white/10 dark:border-white/10 bg-white/5 dark:bg-white/5 backdrop-blur-xl p-6 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg hover:shadow-blue-500/10 hover:bg-white/10 dark:hover:bg-white/10']) }}>
    <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-blue-500 to-cyan-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>

    <div class="flex items-center gap-3">
        @if ($icon)
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-blue-500/10 dark:bg-blue-400/10 flex items-center justify-center">
                {{ $icon }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-400 uppercase tracking-wider truncate">{{ $title }}</p>
            <p class="text-3xl font-extrabold bg-gradient-to-r from-blue-500 to-cyan-400 bg-clip-text text-transparent mt-1">{{ $value }}</p>
        </div>
    </div>

    @if ($extra)
        <div class="mt-2">{{ $extra }}</div>
    @endif

    <div class="absolute -top-6 -right-6 w-12 h-12 rounded-full bg-white/10 dark:bg-white/5 blur-xl"></div>
</div>