<x-mail::message>
# Bienvenido al Sistema Integrado del Centro de Métricas Nacionales

Hola **{{ $user->name }}**,

Se ha creado con éxito tu cuenta de acceso institucional en la plataforma con el rol de **{{ strtoupper($user->role) }}**. A continuación, se detallan tus credenciales oficiales para ingresar al sistema:

<x-mail::panel>
**Dirección de Acceso:** [http://localhost:8000/login](http://localhost:8000/login)
**Correo Electrónico:** {{ $user->email }}
**Contraseña Temporal:** `{{ $password }}`
</x-mail::panel>

<x-mail::button :url="url('/login')">
Ingresar al Sistema
</x-mail::button>

*Por motivos de seguridad perimetral, te recomendamos cambiar esta contraseña temporal desde tu perfil de usuario inmediatamente después de tu primer ingreso.*

Atentamente,
**Dirección de Tecnología e Infraestructura** Centro de Métricas Nacionales
</x-mail::message>
