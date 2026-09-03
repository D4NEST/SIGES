# SIGES — Arquitectura y Flujo de Datos

## El Meta-Modelador

El núcleo del sistema es un meta-modelador: en lugar de tablas fijas por negocio, hay tablas que describen la estructura de otras tablas.

### Tablas del meta-modelo (siempre presentes)

```
rubros          → tipos de negocio (Cauchos, Restaurante, Ferretería...)
  └── entidades → módulos del rubro (productos, clientes, proveedores...)
        └── campos → columnas de cada entidad (nombre, precio, stock...)

empresas        → la empresa configurada en el sistema
config_empresa  → configuración global (1 solo registro, nombre, logo, rubro_id, empresa_id)
usuarios        → usuarios del sistema con roles (admin, usuario)
tareas          → tareas pendientes con prioridad y fecha límite
notificaciones  → alertas del sistema (stock bajo, tareas vencidas)
```

### Tablas físicas generadas

Cuando se ejecuta el wizard `/setup`, el `generator.py` lee las entidades y campos del rubro seleccionado y crea tablas reales en PostgreSQL:

```
Patrón: emp_{empresa_id}_{nombre_tabla}

Ejemplo — empresa ID=1, rubro Cauchos:
  emp_1_productos_caucho   (columnas: id, ancho, perfil, diametro, marca, precio, stock)
  emp_1_proveedores        (columnas: id, nombre, telefono, email)
```

### Flujo de generación

```
Usuario elige rubro en /setup
    ↓
POST /api/config/setup
    ↓
routes.py → crea Empresa en BD → llama TableGenerator
    ↓
generator.py → lee Entidad.query.filter_by(rubro_id) → lee Campo.query.filter_by(entidad_id)
    ↓
SQLAlchemy Core → CREATE TABLE emp_{id}_{tabla} (id SERIAL, campo1 tipo1, ...)
    ↓
Tablas físicas listas para operar
```

### Mapeo de tipos de campo a SQL

| Tipo meta-modelo | Columna SQL |
|-----------------|-------------|
| `string` | VARCHAR(255) |
| `text` | TEXT |
| `integer` | INTEGER |
| `float` | FLOAT |
| `boolean` | BOOLEAN |
| `date` | DATETIME |
| `email` | VARCHAR(255) |
| `currency` | FLOAT |
| `select` | VARCHAR(255) |

## Modelos ORM — relaciones

```python
Rubro
  └── entidades: List[Entidad]  (cascade delete)
        └── campos: List[Campo]  (cascade delete)

ConfigEmpresa
  ├── rubro_id → Rubro
  └── empresa_id → Empresa

Empresa
  └── rubro_id → Rubro
```

## Comunicación Backend ↔ Frontend

### Patrón general

```
Frontend (fetch) → Flask Blueprint /api → SQLAlchemy → PostgreSQL
                ←  JSON response        ←             ←
```

- El frontend se sirve desde Flask (`send_from_directory('../frontend', ...)`)
- Mismo origen → no hay problemas de CORS para las páginas
- Flask-CORS está activo con `supports_credentials=True` para desarrollo
- Autenticación por cookies de sesión (`credentials: 'include'` en cada fetch)

### Rutas de páginas (Flask, no API)

```python
GET /          → frontend/index.html    (login)
GET /setup     → frontend/setup.html   (wizard)
GET /dashboard → frontend/dashboard.html
GET /uploads/<filename> → frontend/uploads/<filename>
```

### Operaciones CRUD en tablas físicas

El frontend nunca escribe SQL. Usa los endpoints dinámicos:

```
GET  /api/empresa/{eid}/entidad/{entid}/registros       → SELECT * FROM emp_{eid}_{tabla}
POST /api/empresa/{eid}/entidad/{entid}/registros       → INSERT INTO ...
PUT  /api/empresa/{eid}/entidad/{entid}/registros/{id}  → UPDATE ...
DEL  /api/empresa/{eid}/entidad/{entid}/registros/{id}  → DELETE ...
GET  /api/empresa/{eid}/entidad/{entid}/schema          → devuelve campos para construir formularios
```

### Cómo el dashboard obtiene empresa_id y entidad_id

```javascript
// 1. Obtener config global (tiene empresa_id y rubro_id)
const config = await fetch('/api/config').then(r => r.json())
// config.empresa_id → ID de la empresa
// config.rubro_id   → ID del rubro

// 2. Obtener entidades del rubro
const entidades = await fetch(`/api/rubros/${config.rubro_id}/entidades`).then(r => r.json())
// entidades[0].id → entidad_id para usar en los endpoints de registros

// 3. Obtener schema de una entidad (para construir formularios)
const schema = await fetch(`/api/empresa/${config.empresa_id}/entidad/${entidad_id}/schema`).then(r => r.json())
// schema.campos → array de campos con tipo, etiqueta, es_requerido, etc.

// 4. Obtener registros
const data = await fetch(`/api/empresa/${config.empresa_id}/entidad/${entidad_id}/registros`).then(r => r.json())
// data.registros → array de objetos con los datos
```

## Estructura de `routes.py`

El archivo está organizado en secciones con comentarios:

```
# ========== RUBROS ==========
# ========== ENTIDADES ==========
# ========== CAMPOS ==========
# ========== TIPOS DE CAMPO ==========
# ========== EMPRESAS ==========
# ========== GENERADOR ==========
# ========== OPERACIÓN DE DATOS (tablas físicas) ==========
# ========== AUTENTICACIÓN ==========
# ========== CONFIGURACIÓN EMPRESA ==========
# ========== NOTIFICACIONES ==========
# ========== TAREAS ==========
# ========== ALERTAS / DSS BRECHA ==========
```

## Notas importantes para el dashboard

- `config.empresa_id` y `config.rubro_id` son los IDs que se usan en todos los endpoints dinámicos
- El schema de una entidad (`/api/empresa/{eid}/entidad/{entid}/schema`) devuelve los campos con `visible_en_tabla` y `visible_en_formulario` para filtrar qué mostrar
- Las alertas (`/api/alertas`) son generadas dinámicamente consultando tablas físicas con campo `stock` y tareas vencidas
- El endpoint `/api/dss/resumen` es una brecha para el módulo DSS futuro — ya devuelve totales por entidad
