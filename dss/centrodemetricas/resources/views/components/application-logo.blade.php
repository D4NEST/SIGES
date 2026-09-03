{{-- Logo corporativo: si existe public/images/logo.png lo usa, si no muestra texto --}}
@if(file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="Centro de Métricas" {{ $attributes->merge(['class' => 'h-9 w-auto']) }}>
@elseif(file_exists(public_path('images/logo.svg')))
    <img src="{{ asset('images/logo.svg') }}" alt="Centro de Métricas" {{ $attributes->merge(['class' => 'h-9 w-auto']) }}>
@else
    <span {{ $attributes->merge(['class' => 'text-sm font-bold tracking-wide']) }}>
        Centro de Métricas
    </span>
@endif
