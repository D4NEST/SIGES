# Arquitectura del Sistema — Centro de Métricas Nacionales
### Corporación Socialista de Cemento | IUTIRLA 2026
**Autor:** Néstor Patiño | T.S.U. en Informática

---

## ¿Qué es este sistema?

Es una aplicación web que permite a la Corporación Socialista de Cemento **cargar, cruzar y visualizar datos del personal** de sus distintas plantas y filiales. El objetivo principal es saber, en tiempo real, cuántos trabajadores votaron, cuántos no, y cómo se distribuye ese personal por planta, filial y estado geográfico.

El sistema reemplaza hojas de cálculo manuales por un panel centralizado con gráficos interactivos, control de accesos por rol y un registro forense de cada acción realizada.

---

## La analogía del edificio

Imagina el sistema como un edificio de 4 pisos:

```
┌─────────────────────────────────────────────────────┐
│  PISO 4 — Lo que el usuario VE (Blade + Livewire)   │
│           Dashboard, gráficos, formularios           │
├─────────────────────────────────────────────────────┤
│  PISO 3 — La LÓGICA (Controllers + Services)        │
│           Reglas de negocio, cálculos, validaciones  │
├─────────────────────────────────────────────────────┤
│  PISO 2 — La SEGURIDAD (Middlewares)                │
│           ¿Quién puede entrar? ¿Con qué rol?         │
├─────────────────────────────────────────────────────┤
│  PISO 1 — Los DATOS (Eloquent ORM + MySQL)           │
│           Tablas, modelos, consultas SQL              │
└─────────────────────────────────────────────────────┘
```

Cada petición del usuario (click, visita a una URL) sube por los 4 pisos de abajo hacia arriba y la respuesta baja de arriba hacia abajo.

---

## El viaje de una petición

Cuando un usuario escribe `http://localhost:8000/dashboard/admin` en el navegador, esto es lo que pasa paso a paso:

```
Navegador
   │
   │  GET /dashboard/admin
   ▼
routes/web.php          ← 1. El enrutador decide quién atiende esta URL
   │
   ▼
Middleware: auth        ← 2. ¿Hay sesión activa? Si no, al login
   │
   ▼
Middleware: CheckRole   ← 3. ¿El usuario tiene el rol correcto?
   │                         Si no, HTTP 403 + registro en audit_logs
   ▼
MetricasController      ← 4. Registra el acceso en auditoría y devuelve la vista
   │
   ▼
DashboardMetricas       ← 5. Componente Livewire carga las métricas
   │
   ▼
MetricsService          ← 6. Ejecuta las consultas SQL agrupadas
   │
   ▼
MySQL (votos_personal)  ← 7. La base de datos responde con los datos
   │
   ▼
Blade + Chart.js        ← 8. Se renderiza el HTML con los gráficos
   │
   ▼
Navegador muestra el dashboard
```

---

## Los 4 Pilares del Sistema

### Pilar 1 — Seguridad por Roles (RBAC)

**Archivo:** `app/Http/Middleware/CheckRole.php`

RBAC significa *Role-Based Access Control* — control de acceso basado en roles. La idea es simple: cada usuario tiene un rol asignado y solo puede ver lo que corresponde a ese rol.

Los 3 roles del sistema:

| Rol | Dashboard | Puede hacer |
|---|---|---|
| `admin` | `/dashboard/admin` | Todo — ver métricas, crear usuarios, gestionar el sistema |
| `supervisor` | `/dashboard/supervisor` | Ver métricas y reportes (solo lectura) |
| `operador` | `/dashboard/operador` | Cargar archivos Excel al sistema |

El middleware `CheckRole` actúa como el **guardia de seguridad** del edificio. Antes de dejar pasar cualquier petición, verifica 4 cosas en orden:

```
1. ¿Hay un usuario autenticado?        → Si no: HTTP 403
2. ¿El usuario está activo?            → Si no: logout + al login
3. ¿Es administrador?                  → Si sí: pasa directo (superusuario)
4. ¿Su rol coincide con la ruta?       → Si no: HTTP 403 + registra en audit_logs
```

La parte importante: cuando alguien intenta entrar a donde no debe, el sistema **no solo lo rechaza**, sino que deja evidencia en la base de datos (IP, hora, qué intentó hacer).

---

### Pilar 2 — Auditoría Forense

**Archivo:** `app/Services/AuditService.php`

Cada acción importante del sistema queda registrada en la tabla `audit_logs`. Es como una **cámara de seguridad** que nunca se apaga.

Lo que se guarda por cada evento:

```
- user_id        → quién lo hizo
- usuario_nombre → su nombre legible
- evento         → qué hizo (ej: ACCESO_DASHBOARD, ACCESO_DENEGADO)
- descripcion    → descripción en texto
- detalles_extra → datos extra en formato JSON
- url_solicitada → a qué URL fue
- direccion_ip   → desde qué IP
- user_agent     → desde qué navegador/dispositivo
- created_at     → exactamente cuándo
```

El `AuditService` es un servicio inyectable — cualquier parte del sistema puede llamarlo con una sola línea:

```php
$audit->registrar('ACCESO_DASHBOARD', 'El admin cargó el panel.');
```

Eventos que se registran actualmente:
- `ACCESO_DASHBOARD` — cuando alguien entra al panel
- `ACCESO_DENEGADO` — cuando alguien intenta entrar a donde no puede
- `CARGA_TEMPORAL` — cuando un operador sube un archivo
- `IMPORTACION_COMPLETADA` — cuando termina de procesarse un archivo

---

### Pilar 3 — Motor de Métricas

**Archivo:** `app/Services/MetricsService.php`

Este servicio es el cerebro del dashboard. Contiene todas las consultas SQL que alimentan los gráficos. En lugar de escribir las consultas directo en el controlador, están organizadas en métodos con nombres claros:

| Método | Qué calcula |
|---|---|
| `totalPorPlanta()` | Cuántos trabajadores hay en cada planta |
| `totalPorFilial()` | Cuántos hay en cada filial |
| `inscritosVsNoInscritos()` | Total votaron vs no votaron + % participación |
| `distribucionPorEstado()` | Cuántos hay por estado geográfico |
| `topCentrosVotacion()` | Los 5 centros con más personal asignado |
| `trazabilidadPorHora()` | A qué hora del día se cargaron los registros |
| `resumenDashboard()` | Llama a todos los anteriores de una sola vez |

El método `resumenDashboard()` es clave: ejecuta todas las consultas en un solo llamado para que el dashboard no tenga que hacer múltiples viajes a la base de datos.

---

### Pilar 4 — Dashboard Reactivo (Livewire)

**Archivo:** `app/Livewire/DashboardMetricas.php`

Livewire es la tecnología que hace que el dashboard se actualice solo sin recargar la página. Funciona así:

```
1. El usuario carga /dashboard/admin
2. Livewire carga el componente DashboardMetricas
3. mount() se ejecuta: carga las métricas + registra auditoría
4. La vista se renderiza con los datos
5. Cada 30 segundos: wire:poll.30s llama a actualizarMetricas()
6. Las métricas se actualizan automáticamente sin recargar la página
```

Las propiedades públicas del componente (`$porPlanta`, `$votos`, etc.) son **reactivas** — cuando cambian, la vista se actualiza automáticamente.

---

## La Base de Datos

### Tablas principales

```
users                   → Usuarios del sistema (admin, supervisor, operador)
votos_personal          → El padrón de personal con estatus de voto
uploads                 → Historial de archivos cargados
upload_errors           → Errores fila por fila de cada carga
audit_logs              → Bitácora forense de acciones
sessions                → Sesiones activas (manejadas por Laravel)
cache / jobs            → Cache y cola de trabajos asíncronos
```

### La tabla más importante: `votos_personal`

```sql
cedula                   → Cédula (clave primaria, tipo string)
nombre_apellido          → Nombre completo
cargo                    → Cargo en la empresa
planta                   → Planta donde trabaja
filial                   → Filial a la que pertenece
estado_fisico            → Estado geográfico donde está
estado_voto              → SI / NO (si ya votó)
municipio / parroquia    → Ubicación detallada
centro_votacion          → Centro donde le toca votar
upload_id                → FK → qué archivo lo trajo al sistema
```

### Por qué la cédula es la clave primaria

En lugar del típico `id` autoincremental, usamos la cédula como clave primaria. Esto tiene una ventaja enorme: cuando se cargan datos, se puede hacer un `UPSERT` (insert o update en una sola operación). Si la cédula ya existe, actualiza los datos. Si no existe, la inserta. Esto evita duplicados de forma automática y eficiente.

---

## El Flujo de Carga de Datos

Cuando un operador sube un archivo Excel, esto es lo que sucede:

```
Operador sube archivo .xlsx
         │
         ▼
ImportService::calcularHash()     → Calcula SHA-256 del archivo
         │                           Si ya existe ese hash → rechaza (duplicado)
         ▼
ImportService::leerEncabezados()  → Lee la primera fila del Excel
         │
         ▼
¿Coincide con estructura oficial? → SÍ: importación automática
                                  → NO: Wizard de mapeo manual
         │
         ▼
ImportacionExcelJob               → Procesa el archivo en chunks de 500 filas
         │                          (en segundo plano, no bloquea la interfaz)
         ▼
Por cada fila:
  - Validar cédula (7-8 dígitos numéricos)
  - Sanitizar campos de texto
  - UPSERT en votos_personal
         │
         ▼
AuditService::registrar(IMPORTACION_COMPLETADA)
```

El procesamiento en **chunks de 500 filas** es importante: en lugar de procesar 5,000 registros de golpe (lo que podría agotar la memoria del servidor), se procesan de a 500 en 500, haciendo una transacción de base de datos por lote.

---

## Los Gráficos (Chart.js)

Los gráficos se construyen en el navegador usando Chart.js. Los datos llegan desde PHP a JavaScript a través de atributos `data-*` en los elementos HTML:

```html
<!-- PHP pone los datos aquí -->
<canvas id="chartPlanta"
    data-labels='["Planta Mara","Planta Monay","Planta Lara"]'
    data-values='[1200, 980, 750]'>
</canvas>
```

```javascript
// JavaScript los lee y construye el gráfico
var el = document.getElementById('chartPlanta');
var labels = JSON.parse(el.dataset.labels);
var values = JSON.parse(el.dataset.values);
new Chart(el, { type: 'bar', data: { labels, datasets: [{ data: values }] } });
```

Esta separación PHP/JS es intencional: evita que el linter del editor confunda el código PHP con JavaScript.

Los 4 tipos de gráfico en el dashboard:

| Gráfico | Tipo | Por qué ese tipo |
|---|---|---|
| Por Planta | Barras verticales | Comparar cantidades entre pocas categorías |
| Por Filial | Doughnut | Ver proporciones del total |
| Por Estado | Barras horizontales | Nombres largos que no caben en eje X |
| Por Hora | Línea con área | Mostrar tendencia temporal (tráfico) |

---

## El Ciclo Completo: De Login a Dashboard

```
1. Usuario va a /login
2. Ingresa email y contraseña
3. Laravel verifica credenciales en la tabla users
4. Si es correcto: crea sesión + redirige según rol:
      admin      → /dashboard/admin
      supervisor → /dashboard/supervisor
      operador   → /dashboard/operador
5. CheckRole verifica rol + estado active en cada petición
6. El dashboard se actualiza solo cada 30 segundos
7. Cada acción queda en audit_logs
8. Al cerrar sesión: se destruye la sesión y redirige a /
```

---

## Conceptos Clave para la Exposición

**Middleware** — Código que se ejecuta antes de que la petición llegue al controlador. Como un filtro o guardia.

**RBAC** — Sistema de permisos basado en roles. Cada rol tiene acceso a ciertas rutas y nada más.

**Eloquent ORM** — Permite trabajar con la base de datos usando objetos PHP en lugar de escribir SQL puro. El modelo `CentroMetrica` representa la tabla `votos_personal`.

**Livewire** — Hace que partes de la página se actualicen sin recargarla completa. Similar a React pero escrito en PHP.

**Service Layer** — Patrón de diseño que separa la lógica de negocio de los controladores. `MetricsService` y `AuditService` son ejemplos.

**Upsert** — Operación de base de datos que inserta un registro si no existe, o lo actualiza si ya existe. Evita duplicados automáticamente.

**Queue / Cola** — Sistema para ejecutar tareas pesadas en segundo plano sin bloquear al usuario. Se usa para el envío de correos al crear usuarios.

**SHA-256** — Algoritmo que genera una firma digital única de un archivo. Si dos archivos tienen el mismo hash, son idénticos. Se usa para detectar cargas duplicadas.

---

## Resumen Visual

```
USUARIO
  │
  │ (navegador)
  ▼
RUTAS (routes/web.php)
  │ define qué URL hace qué
  ▼
MIDDLEWARE (CheckRole)
  │ verifica rol + activo + registra en audit_logs
  ▼
CONTROLADOR (MetricasController)
  │ orquesta la respuesta + registra en auditoría
  ▼
COMPONENTE LIVEWIRE (DashboardMetricas)
  │ gestiona el estado reactivo del dashboard
  ▼
SERVICIO DE MÉTRICAS (MetricsService)
  │ ejecuta todas las consultas SQL
  ▼
MODELO ELOQUENT (CentroMetrica)
  │ abstrae la tabla votos_personal
  ▼
MYSQL — tabla votos_personal
  │ ~5,200 registros reales
  ▼
(datos suben de vuelta por la cadena)
  ▼
BLADE + CHART.JS
  renderiza el HTML + gráficos interactivos en el navegador
```

---

*Documento generado para uso académico — Pasantías IUTIRLA 2026*
