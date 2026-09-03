<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Centro de Métricas') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

   <style>
    [x-cloak] { display: none !important; }

    #particles-canvas {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        pointer-events: none;
        z-index: -1;
    }
    .app-wrapper { 
        position: relative; 
        z-index: 1; 
    }

    /* ====== MODO CLARO ====== */
    body {
        background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 50%, #cbd5e1 100%) !important;
        min-height: 100vh;
    }

    nav {
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        z-index: 100 !important;
    }

    header {
        background: rgba(255, 255, 255, 0.75) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
    }

    .logo-csc {
        color: #1e3a8a;
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
    }

    /* ====== MODO OSCURO - CORREGIDO ====== */
    html.dark body {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%) !important;
    }

    html.dark nav {
        background: rgba(15, 23, 42, 0.92) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    html.dark header {
        background: rgba(15, 23, 42, 0.88) !important;
    }

    html.dark .logo-csc {
        color: #60a5fa;
    }

    /* Contenedores en modo oscuro */
    html.dark .bg-white {
        background-color: rgba(30, 41, 59, 0.95) !important;
    }

    html.dark .bg-gray-50,
    html.dark .bg-gray-100 {
        background-color: rgba(51, 65, 85, 0.6) !important;
    }

    html.dark .bg-gray-200 {
        background-color: rgba(71, 85, 105, 0.5) !important;
    }

    /* Bordes en modo oscuro */
    html.dark .border-gray-200,
    html.dark .border-gray-100 {
        border-color: rgba(148, 163, 184, 0.3) !important;
    }

    /* Sombras en modo oscuro */
    html.dark .shadow {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4) !important;
    }

    /* Textos en modo oscuro */
    html.dark .text-gray-800,
    html.dark .text-gray-900 { color: #f1f5f9 !important; }
    html.dark .text-gray-700 { color: #e2e8f0 !important; }
    html.dark .text-gray-600 { color: #cbd5e1 !important; }
    html.dark .text-gray-500 { color: #94a3b8 !important; }
    html.dark .text-gray-400 { color: #64748b !important; }

    /* Transiciones suaves */
    *, *::before, *::after {
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.2s ease;
    }
</style>
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-950 min-h-screen">

    @include('layouts.navigation')

    <canvas id="particles-canvas"></canvas>

    @livewireScripts

    <script>
        // Mejor inicialización del modo oscuro
        (function() {
            function getThemePreference() {
                const saved = localStorage.getItem('theme');
                if (saved === 'dark' || saved === 'light') {
                    return saved;
                }
                // Detectar preferencia del sistema
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    return 'dark';
                }
                return 'light';
            }
            
            function applyTheme(theme) {
                const isDark = theme === 'dark';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                // Disparar evento para otros componentes
                document.documentElement.setAttribute('data-theme', theme);
                
                if (typeof initParticles === 'function') {
                    initParticles(isDark);
                }
                
                // Forzar re-render de gráficos si existen
                if (typeof initCharts === 'function') {
                    setTimeout(initCharts, 100);
                }
            }
            
            // Aplicar tema inmediatamente al cargar
            const theme = getThemePreference();
            applyTheme(theme);
            
            // Exponer funciones globalmente
            window.toggleTheme = function() {
                const current = getThemePreference();
                const newTheme = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
                return newTheme;
            };
            
            window.getCurrentTheme = function() {
                return getThemePreference();
            };
            
            // Escuchar cambios en preferencias del sistema
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                const saved = localStorage.getItem('theme');
                if (!saved) { // Solo si usuario no eligió manualmente
                    const newTheme = e.matches ? 'dark' : 'light';
                    applyTheme(newTheme);
                }
            });
        })();
    </script>

    <div x-data="{
            dark: false,
            init() {
                // Sincronizar con el sistema global
                this.dark = window.getCurrentTheme() === 'dark';
                this.$watch('dark', (value) => {
                    if (value !== (window.getCurrentTheme() === 'dark')) {
                        window.toggleTheme();
                    }
                });
                
                // Escuchar cambios desde otros componentes
                this.$watch('$store.darkMode', (value) => {
                    this.dark = value;
                });
            }
         }"
         @toggle-theme.window="window.toggleTheme()"
         class="app-wrapper min-h-screen">

        <main>
            {{ $slot }}
        </main>
    </div>

    <script>
    function initParticles(darkMode) {
        var canvas = document.getElementById('particles-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;

        var particleColor = darkMode ? 'rgba(200,200,220,' : 'rgba(80,80,100,';
        var lineColor = darkMode ? 'rgba(200,200,220,' : 'rgba(80,80,100,';
        var count = 55;
        var particles = [];

        for (var i = 0; i < count; i++) {
            particles.push({
                x:  Math.random() * canvas.width,
                y:  Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                r:  Math.random() * 2 + 1,
            });
        }

        if (window._particleFrame) cancelAnimationFrame(window._particleFrame);

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (var i = 0; i < particles.length; i++) {
                for (var j = i + 1; j < particles.length; j++) {
                    var dx   = particles[i].x - particles[j].x;
                    var dy   = particles[i].y - particles[j].y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 120) {
                        var op = (1 - dist / 120) * 0.25;
                        ctx.beginPath();
                        ctx.strokeStyle = lineColor + op + ')';
                        ctx.lineWidth   = 0.6;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }

            particles.forEach(function(p) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = particleColor + '0.5)';
                ctx.fill();
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > canvas.width)  p.vx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
            });

            window._particleFrame = requestAnimationFrame(draw);
        }

        draw();

        window.onresize = function() {
            canvas.width  = window.innerWidth;
            canvas.height = window.innerHeight;
        };
    }
    </script>
</body>
</html>