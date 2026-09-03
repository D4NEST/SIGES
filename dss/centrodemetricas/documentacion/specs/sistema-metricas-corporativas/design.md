# Documento de Diseño Técnico
## Sistema de Métricas Corporativas — Corporación Socialista de Cemento
### Laravel 11 | PHP 8.2 | MySQL 8

---

## 1. Visión General

El sistema es una aplicación web monolítica construida sobre Laravel 11 con separación clara de capas: presentación (Blade + Livewire), lógica de negocio (Controllers + Jobs + Services) y persistencia (Eloquent ORM + MySQL). La arquitectura prioriza la seguridad por capas, la trazabilidad completa de operaciones y el procesamiento asíncrono de archivos pesados mediante colas de trabajo.

La decisión de usar un monolito en lugar de microservicios responde al contexto: hardware con recursos limitados, equipo de mantenimiento pequeño y la necesidad de despliegue simple. Laravel proporciona todos los primitivos necesarios (Jobs, Middleware, Queues, Eloquent) sin overhead de infraestructura adicional.

---

## 2. Arquitectura

```
[Presentación]  Blade Templates + Livewire Components
      |
[HTTP]          Middleware: auth > CheckRole > SessionTimeout > Controllers
      |
[Negocio]       ImportService | MetricsService | ArchiveService | Jobs | Wizard
      |
[Datos]         Eloquent Models > MySQL 8 | Storage | Queue (database)
```

### Decisiones de Arquitectura

| Decisión | Alternativa descartada | Justificación |
|---|---|---|
| Jobs + Queue para importación | Importación síncrona | Evita timeout HTTP en archivos grandes |
| Upsert por cédula | Insert + validación en PHP | Una sola query SQL es más eficiente a 100k+ filas |
| Livewire polling (30s) | WebSockets (Reverb) | Menor complejidad de infraestructura |
| MySQL usuario de solo lectura para audit_logs | Sin restricción | Garantiza inmutabilidad a nivel de motor |
| Hash SHA-256 del archivo | Nombre del archivo | El hash detecta duplicados exactos |

---

## 3. Componentes e Interfaces

### 3.1 Middlewares

**`CheckRole`**
- Valida autenticación y coincidencia exacta de rol.
- El admin bypasea la restricción de rol.
- Registra intentos de acceso no autorizado en audit_logs.

**`SessionTimeout`**
- Lee `session('last_activity')`, calcula diferencia con `now()`.
- Si diferencia > 900 segundos: invalida sesión, redirige al login.
- Actualiza `last_activity` en cada petición exitosa.

### 3.2 Controllers

- **`UsuarioController`**: CRUD de usuarios (solo admin). Dispara `NotificacionCredenciales`.
- **`ImportacionController`**: Recibe el archivo, valida, calcula SHA-256, presenta Wizard, despacha Job.
- **`MetricasController`**: Delega consultas a `MetricsService`. Registra auditoría.
- **`AuditController`**: Lista paginada de `audit_logs` (solo admin).
- **`ArchivadoController`**: Dispara `ArchivadoDatosJob` (solo admin).

### 3.3 Services

**`ImportService`**
```php
public function validarArchivo(UploadedFile $file): ValidationResult
public function calcularHash(UploadedFile $file): string
public function leerEncabezados(UploadedFile $file): array
public function validarMapeo(array $mapeo, array $encabezados): MapeoResult
public function validarTiposPostMapeo(array $muestraDatos, array $mapeo): array
public function cruzarConVotos(UploadedFile $archivoVotos): CruceResult
```

**`MetricsService`**
```php
public function totalPorPlanta(): Collection
public function totalPorPlantaConVotos(): Collection
public function totalPorFilial(): Collection
public function inscritosVsNoInscritos(): array
public function distribucionPorEstado(): Collection
public function topCentrosVotacion(int $limite = 5): Collection
public function trazabilidadPorHora(): Collection
public function resumenDashboard(): array
```

**`ArchiveService`**
```php
public function exportarAntiguos(int $diasAntiguedad = 365): string
public function purgarRegistrosArchivados(string $archivoPath): int
```

### 3.4 Jobs

**`ImportacionExcelJob`** — chunks de 500 filas, tries=3, backoff=[60,300,900]s

**`ArchivadoDatosJob`** — exporta a CSV/ZIP, dentro de transacción DB elimina registros

### 3.5 Livewire Components

**`DashboardMetricas`**: Carga métricas desde `MetricsService`. Usa `wire:poll.30s`.

**`WizardMapeo`**: Multi-step. Paso 1: carga archivo. Paso 2: mapeo de columnas. Paso 3: confirmación y despacho.

---

## 4. Modelos de Datos

### 4.1 Tabla `users`
```sql
id, name, email, role ENUM('admin','supervisor','operador'),
active BOOLEAN DEFAULT TRUE, password, remember_token,
email_verified_at, timestamps
```

### 4.2 Tabla `votos_personal` (tabla principal de personal)
```sql
cedula          VARCHAR(20) PRIMARY KEY,
nombre_apellido VARCHAR(255),
cargo           VARCHAR(255),
ubicacion_administrativa VARCHAR(255),
planta          VARCHAR(255),
filial          VARCHAR(255),
estado_fisico   VARCHAR(255),
telefono        VARCHAR(50),
estado_voto     ENUM('SI','NO') DEFAULT 'NO',
municipio       VARCHAR(255),
parroquia       VARCHAR(255),
centro_votacion TEXT,
direccion_centro TEXT,
upload_id       BIGINT UNSIGNED FK -> uploads(id),
timestamps
```

### 4.3 Tabla `uploads` (seguimiento de importaciones)
```sql
id, user_id FK, filename, original_name,
status ENUM('pending','processing','completed','completed_with_errors','failed'),
total_rows, processed_rows, column_mapping JSON, timestamps
```

### 4.4 Tabla `audit_logs`
```sql
id, user_id FK, usuario_nombre, evento, descripcion,
detalles_extra JSON, url_solicitada, direccion_ip,
user_agent, created_at, updated_at
```
Restricción: usuario MySQL dedicado con solo INSERT y SELECT.

---

## 5. Propiedades de Corrección

| # | Propiedad | Requisitos |
|---|---|---|
| 1 | RBAC niega acceso a roles insuficientes | 1.2, 1.3 |
| 2 | Hash rechaza archivos duplicados | 3.2 |
| 3 | Mapeo requiere cédula obligatoriamente | 3.4 |
| 4 | Validación de tipos detecta incompatibilidades | 3.5, 3.6 |
| 5 | Chunks preservan total de registros válidos | 4.3 |
| 6 | Upsert es idempotente | 4.3 |
| 7 | Cruce clasifica todos los registros | 5.2, 5.4 |
| 8 | Archivado es atómico (exportación y purga inseparables) | 8.2, 8.3 |
| 9 | Auditoría registra toda acción clasificada sin pérdida | 7.1, 7.2 |

---

## 6. Manejo de Errores

| Escenario | Estrategia |
|---|---|
| Archivo no es Excel válido | HTTP 422 con mensaje. Validación en `ImportService`. |
| Archivo duplicado (hash) | HTTP 409 Conflict. Mensaje con fecha de importación original. |
| Cédula inválida en fila | Skip + registro en `upload_errors`. No detiene el Job. |
| Job falla 3 veces | `JobFailed` → registro en `audit_logs` con stack trace. |
| Timeout de sesión | Middleware `SessionTimeout` → redirect a login con flash. |
| Incompatibilidad de tipos en mapeo | Respuesta al Wizard con array de errores. No despacha Job. |
| Archivado fallido | Rollback DB. Elimina archivo parcial. Registra en audit. |
| Acceso no autorizado | HTTP 403. Registra en `audit_logs` el intento. |

---

## 7. Estrategia de Testing

**Librería:** `giorgiosironi/eris` para property-based testing (mínimo 100 iteraciones por test).

Cada test de propiedad debe anotarse con:
```
// Feature: sistema-metricas-corporativas, Property {N}: {texto}
```

Tests unitarios: `ImportServiceTest`, `WizardMapeoTest`, `MetricsServiceTest`, `CheckRoleMiddlewareTest`, `SessionTimeoutMiddlewareTest`.

Tests de propiedades: uno por cada propiedad del punto 5 (P1–P9).
