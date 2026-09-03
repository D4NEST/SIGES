<x-app-layout>
    <div id="tsparticles" class="fixed inset-0 -z-10 bg-slate-50"></div>

    <div class="container mx-auto px-4 py-6 relative z-10">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Centro de Métricas Analíticas</h1>
        </div>

        <livewire:dashboard-metricas />
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>

    <script>
        tsParticles.load("tsparticles", {
            particles: {
                number: { value: 40 },
                color: { value: "#60a5fa" },
                links: { enable: true, distance: 150, color: "#93c5fd", opacity: 0.3 },
                move: { enable: true, speed: 0.5 },
                size: { value: 2 }
            }
        });
    </script>
</x-app-layout>