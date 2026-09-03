<nav x-data="{ 
        mobileOpen: false, 
        dark: localStorage.getItem('theme') === 'dark'
     }" 
     x-init="
        if (dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        $watch('dark', (value) => {
            localStorage.setItem('theme', value ? 'dark' : 'light');
            if (value) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            if (typeof initParticles === 'function') initParticles(value);
            if (typeof initCharts === 'function') setTimeout(initCharts, 100);
        });
     "
     class="relative backdrop-blur-xl border-b shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            {{-- LOGO + PANEL DE CONTROL --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <x-application-logo class="h-8 w-auto" />
                    <span class="text-lg font-bold text-gray-800 dark:text-white hidden sm:block">
                        Centro de Métricas
                    </span>
                </a>

                @auth
                    <span class="hidden md:block text-gray-300 dark:text-gray-600">|</span>
                    <span class="hidden md:block text-sm text-gray-600 dark:text-gray-400">
                        Panel de Control —
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">
                            {{ ucfirst(Auth::user()->role) }}
                        </span>
                    </span>
                @endauth
            </div>

            {{-- MENÚ DESKTOP --}}
            <div class="hidden md:flex items-center gap-6">
                @auth
                    <a href="{{ route('dashboard') }}" 
                       class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('dashboard*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }} pb-1">
                        Dashboard
                    </a>

                    @if(Auth::user()->role === 'admin')
                        <a href="{{ route('admin.users.index') }}" 
                           class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('admin.users*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }} pb-1">
                            Usuarios
                        </a>
                        <a href="{{ route('admin.audit.index') }}" 
                           class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('admin.audit*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }} pb-1">
                            Auditoría
                        </a>
                    @endif

                    @if(in_array(Auth::user()->role, ['admin', 'operador']))
                        <a href="{{ route('uploads.index') }}" 
                           class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('uploads*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }} pb-1">
                            Cargar datos
                        </a>
                    @endif
                @endauth
            </div>

            {{-- CONTROLES DERECHA --}}
            <div class="flex items-center gap-3">
                @auth
                    <span class="hidden sm:inline-block text-xs font-bold px-2 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                        {{ strtoupper(Auth::user()->role) }}
                    </span>

                    {{-- BOTÓN TEMA OSCURO/CLARO --}}
                    <button @click="$dispatch('toggle-theme')" 
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{-- Icono LUNA (modo claro) --}}
                        <svg x-show="!dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        {{-- Icono SOL (modo oscuro) --}}
                        <svg x-show="dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    {{-- DROPDOWN USUARIO --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <span class="hidden lg:inline">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-cloak x-show="open" 
                             @click.outside="open = false"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                            <a href="{{ route('profile.edit') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                ⚙️ Mi Perfil
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    🚪 Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                {{-- Hamburguesa móvil --}}
                <button @click="mobileOpen = !mobileOpen" 
                        class="md:hidden p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :aria-expanded="mobileOpen.toString()">
                    <svg x-show="!mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- MENÚ MÓVIL --}}
    @auth
        <div x-cloak x-show="mobileOpen" 
             class="md:hidden bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-t border-gray-200 dark:border-gray-800">
            <div class="px-4 py-3 space-y-1">
                <a href="{{ route('dashboard') }}" 
                   class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('dashboard*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                    Dashboard
                </a>

                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('admin.users.index') }}" 
                       class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('admin.users*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        Usuarios
                    </a>
                    <a href="{{ route('admin.audit.index') }}" 
                       class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('admin.audit*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        Auditoría
                    </a>
                @endif

                @if(in_array(Auth::user()->role, ['admin', 'operador']))
                    <a href="{{ route('uploads.index') }}" 
                       class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('uploads*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        Cargar datos
                    </a>
                @endif

                <hr class="my-2 border-gray-200 dark:border-gray-700">

                <a href="{{ route('profile.edit') }}" 
                   class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    ⚙️ Mi Perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="block w-full text-left px-3 py-2 rounded-lg text-base font-medium text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        🚪 Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    @endauth
</nav>