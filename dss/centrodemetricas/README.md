<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="350" alt="Laravel Logo">
</p>

# 📊 Centro de Métricas Nacionales

### Corporación Socialista de Cemento | Proyecto de Pasantías IUTIRLA 2026

Plataforma analítica corporativa para centralizar, auditar, procesar y visualizar datos operativos del personal provenientes de las filiales y departamentos de la Corporación Socialista de Cemento. Construida sobre Laravel 11 con arquitectura orientada a la seguridad por capas, trazabilidad forense y procesamiento asíncrono de alta velocidad.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 / PHP 8.2+ |
| Frontend | Blade + Livewire + Alpine.js + Tailwind CSS |
| Gráficos | Chart.js 4.4 |
| Base de datos | MySQL 8 (InnoDB) |
| Procesamiento Excel | Maatwebsite/Laravel Excel |
| Correo (dev) | Mailtrap SMTP |
| Servidor local | Laragon / `php artisan serve` |

---

## ✅ Funcionalidades Implementadas

### 🔐 Control de Accesos (RBAC)
- Middleware `CheckRole` con validación estricta por rol en cada petición HTTP
- Redirección automática post-login según rol: `admin → /dashboard/admin`, `supervisor → /dashboard/supervisor`, `operador → /dashboard/operador`
- Bloqueo de usuarios desactivados con cierre de sesión inmediato
- Registro en `audit_logs` de cada intento de acceso no autorizado (IP, user-agent, ruta intentada)

### 📋 Dashboard de Métricas en Tiempo Real
- Panel con **actualización automática cada 30 segundos** (Livewire `wire:poll`)
- KPIs en tarjetas: Total inscritos, Votaron, No votaron, % Participación
- Gráficos interactivos organizados en **tabs** (sin scroll, una vista a la vez):
  - 📊 **Por Planta** — barras verticales con paleta corporativa
  - 🏢 **Por Filial** — doughnut con leyenda inferior
  - 🗺️ **Por Estado** — barras horizontales (acomoda nombres largos)
  - 🕐 **Carga por Hora** — línea con área rellena, tráfico real de ingesta
  - ✅ **Participación por Planta** — barras de progreso con conteo votaron/pendientes
  - 🏫 **Top Centros de Votación** — ranking numerado

### 🔍 Auditoría Forense
- Registro inmutable de eventos críticos: accesos, importaciones, creación de usuarios, accesos denegados
- Captura automática de IP, user-agent, URL y timestamp en cada evento
- Modelo `AuditLog` con servicio inyectable `AuditService` disponible en toda la app

### 📥 Motor de Ingesta (Wizard)
- Carga de archivos Excel (.xlsx/.xls) hasta 20 MB
- Wizard de mapeo de columnas: asocia cabeceras del Excel a campos de la BD
- Procesamiento por chunks de 500 filas con `upsert` por cédula (sin duplicados)
- Log de errores por fila para reportar cédulas inválidas al operador
- Validación automática de formato oficial (detección sin Wizard)

### 📧 Notificaciones Asíncronas
- Cola de trabajo (`database queue`) para despacho de credenciales por correo al crear usuarios
- Integración con Mailtrap para pruebas en entorno de desarrollo

---

## 🚀 Instalación Local

```bash
# 1. Clonar el repositorio
git clone https://github.com/pasantedevcsc-glitch/Pasantiacsc.git
cd Pasantiacsc

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias frontend
npm install

# 4. Configurar entorno
cp .env.example .env
# Editar .env: credenciales MySQL, Mailtrap, APP_KEY

# 5. Generar clave y migrar BD
php artisan key:generate
php artisan migrate --seed

# 6. Compilar assets frontend
npm run build

# 7. (Opcional) Cargar datos de prueba masivos
php artisan db:seed --class=VotosPersonalSeeder

# 8. Levantar servidor
php artisan serve
```

Para el flujo de notificaciones por correo, levantar el procesador de colas en una terminal separada:

```bash
php artisan queue:work
```

---

## 👤 Credenciales de Prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | admin@test.com | password123 |
| Operador | operador@test.com | (ver seeder) |

---

## 📁 Estructura Relevante

```
app/
├── Http/Middleware/CheckRole.php     # RBAC + auditoría de accesos
├── Livewire/DashboardMetricas.php    # Componente principal del dashboard
├── Services/MetricsService.php       # Queries del dashboard
├── Services/AuditService.php         # Registro de auditoría
├── Models/CentroMetrica.php          # Modelo → tabla votos_personal
database/
├── seeders/VotosPersonalSeeder.php   # Carga masiva de datos de prueba
documentacion/
└── specs/sistema-metricas-corporativas/
    ├── requirements.md               # Requisitos funcionales
    ├── design.md                     # Diseño técnico y arquitectura
    └── tasks.md                      # Plan de implementación
```

---

## 📅 Próximas Fases

- [ ] Módulo de gestión de usuarios desde el panel admin (CRUD completo)
- [ ] Filtros dinámicos en dashboard por filial, estado y rango de fechas
- [ ] Vista de bitácora de auditoría paginada para el administrador
- [ ] Archivado y purga de datos históricos (exportación CSV/ZIP)
- [ ] Panel de operador con historial de importaciones y estado de cada carga

---

Desarrollado por **Néstor Patiño** | T.S.U. en Informática (En progreso) — IUTIRLA 2026
