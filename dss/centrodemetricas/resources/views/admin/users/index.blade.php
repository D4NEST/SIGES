<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Gestión de Usuarios
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg text-sm font-medium">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg text-sm font-medium">
                    ⚠️ {{ session('error') }}
                </div>
            @endif

            {{-- KPIs --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-purple-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Administradores</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalAdmins }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-amber-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Supervisores</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalSupervisors }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Operadores Activos</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalOperators }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Formulario crear usuario --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Registrar nuevo usuario</h3>

                    @if ($errors->any())
                        <div class="bg-red-50 dark:bg-red-900 border border-red-300 text-red-700 dark:text-red-300 px-3 py-2 rounded text-sm mb-4">
                            @foreach ($errors->all() as $error)
                                <p>• {{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo electrónico</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
                            <select name="role" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="operador">Operador</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition-colors">
                            Crear usuario
                        </button>
                    </form>
                </div>

                {{-- Tabla de usuarios --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow lg:col-span-2 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Usuarios del sistema</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rol</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($users as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $user->name }}</td>
                                    <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                                    <td class="px-5 py-3">
                                        @php
                                            $badge = match($user->role) {
                                                'admin'      => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                'supervisor' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                                default      => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $badge }}">
                                            {{ strtoupper($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($user->active)
                                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Activo</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($user->id !== auth()->id())
                                            <div class="flex items-center gap-2">
                                                {{-- Toggle activo/inactivo --}}
                                                <form action="{{ route('admin.users.toggle', $user) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                        class="text-xs px-2 py-1 rounded {{ $user->active ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-200' }} transition-colors">
                                                        {{ $user->active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                                {{-- Eliminar --}}
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                    onsubmit="return confirm('¿Eliminar a {{ $user->name }}? Esta acción no se puede deshacer.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-xs px-2 py-1 rounded bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200 transition-colors">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">Tú</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-400 text-sm">No hay usuarios registrados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $users->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
