# SIGES — Referencia de API para el Dashboard

Base URL: `http://localhost:5000/api`  
Todos los fetch del frontend usan `credentials: 'include'`.

---

## GET /api/config

Estado global del sistema. Primer llamado al cargar cualquier página.

```javascript
const config = await fetch('/api/config').then(r => r.json())
```

**Respuesta (sistema configurado):**
```json
{
  "id": 1,
  "nombre": "Distribuidora El Sol",
  "rubro_id": 1,
  "empresa_id": 1,
  "logo_path": "/uploads/logo.png",
  "configurado": true,
  "stock_minimo_alerta": 5
}
```

**Respuesta (no configurado):**
```json
{ "configurado": false }
```

Si `configurado` es `false`, redirigir a `/setup`.

---

## GET /api/auth/me

Usuario autenticado en la sesión actual.

```javascript
const res = await fetch('/api/auth/me', { credentials: 'include' })
```

**Respuesta (autenticado):**
```json
{
  "usuario": {
    "id": 1,
    "nombre": "María González",
    "email": "admin@empresa.com",
    "rol": "admin",
    "activo": true
  }
}
```

**Respuesta (no autenticado):** HTTP 401  
Si 401, redirigir a `/`.

---

## GET /api/dss/resumen

Totales agregados por entidad. Usado para las stats cards del dashboard.

```javascript
const resumen = await fetch('/api/dss/resumen', { credentials: 'include' }).then(r => r.json())
```

**Respuesta:**
```json
{
  "empresa": "Distribuidora El Sol",
  "rubro": "Cauchos",
  "entidades": [
    { "nombre": "producto_caucho", "tabla": "emp_1_productos_caucho", "total_registros": 45 },
    { "nombre": "proveedor",       "tabla": "emp_1_proveedores",       "total_registros": 8  }
  ],
  "tareas_pendientes": 3,
  "notificaciones_no_leidas": 2
}
```

---

## GET /api/alertas

Alertas dinámicas: stock bajo en tablas físicas + tareas vencidas.

```javascript
const alertas = await fetch('/api/alertas', { credentials: 'include' }).then(r => r.json())
```

**Respuesta:**
```json
[
  {
    "tipo": "stock_bajo",
    "titulo": "Stock bajo en Productos",
    "mensaje": "Registro #12 tiene stock de 2 unidades",
    "entidad": "producto_caucho",
    "tabla": "emp_1_productos_caucho"
  },
  {
    "tipo": "tarea_vencida",
    "titulo": "Tarea vencida: Llamar proveedor",
    "mensaje": "Venció el 15/03/2026"
  }
]
```

---

## GET /api/tareas

Lista de tareas ordenadas por completada y fecha límite.

```javascript
const tareas = await fetch('/api/tareas', { credentials: 'include' }).then(r => r.json())
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "titulo": "Llamar proveedor Bridgestone",
    "descripcion": "Pedir cotización de lote 205/55R16",
    "completada": false,
    "fecha_limite": "2026-03-20T00:00:00",
    "prioridad": "alta",
    "fecha_creacion": "2026-03-15T10:00:00"
  }
]
```

**Crear tarea:**
```javascript
await fetch('/api/tareas', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    titulo: 'Mi tarea',
    prioridad: 'media',           // alta | media | baja
    fecha_limite: '2026-04-01'    // opcional
  })
})
```

**Completar tarea:**
```javascript
await fetch(`/api/tareas/${id}`, {
  method: 'PUT',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ completada: true })
})
```

---

## GET /api/rubros/{rubro_id}/entidades

Entidades del rubro activo. Usar `config.rubro_id`.

```javascript
const entidades = await fetch(`/api/rubros/${config.rubro_id}/entidades`).then(r => r.json())
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "rubro_id": 1,
    "nombre": "producto_caucho",
    "nombre_tabla": "productos_caucho",
    "nombre_plural": "Productos",
    "icono": "🛞",
    "descripcion": "",
    "orden": 0,
    "campos": [ ... ]
  },
  {
    "id": 2,
    "rubro_id": 1,
    "nombre": "proveedor",
    "nombre_tabla": "proveedores",
    "nombre_plural": "Proveedores",
    "icono": "🏭",
    "orden": 1,
    "campos": [ ... ]
  }
]
```

---

## GET /api/empresa/{eid}/entidad/{entid}/schema

Definición de campos de una entidad. Usar para construir tablas y formularios dinámicos.  
Usar `config.empresa_id` como `eid`.

```javascript
const schema = await fetch(
  `/api/empresa/${config.empresa_id}/entidad/${entidad_id}/schema`,
  { credentials: 'include' }
).then(r => r.json())
```

**Respuesta:**
```json
{
  "entidad_id": 1,
  "entidad": "producto_caucho",
  "tabla": "emp_1_productos_caucho",
  "campos": [
    {
      "id": 1,
      "nombre": "ancho",
      "nombre_fisico": "ancho",
      "tipo": "integer",
      "etiqueta": "Ancho (mm)",
      "placeholder": "",
      "es_requerido": true,
      "es_unico": false,
      "opciones": null,
      "orden": 0,
      "visible_en_tabla": true,
      "visible_en_formulario": true
    },
    {
      "id": 5,
      "nombre": "precio",
      "nombre_fisico": "precio",
      "tipo": "currency",
      "etiqueta": "Precio",
      "es_requerido": true,
      "visible_en_tabla": true,
      "visible_en_formulario": true
    }
  ]
}
```

**Uso para construir encabezados de tabla:**
```javascript
const headers = schema.campos.filter(c => c.visible_en_tabla)
```

**Uso para construir campos de formulario:**
```javascript
const formFields = schema.campos.filter(c => c.visible_en_formulario)
```

---

## GET /api/empresa/{eid}/entidad/{entid}/registros

Todos los registros de una tabla física.

```javascript
const data = await fetch(
  `/api/empresa/${config.empresa_id}/entidad/${entidad_id}/registros`,
  { credentials: 'include' }
).then(r => r.json())
// data.registros → array de objetos
// data.total     → cantidad total
```

**Respuesta:**
```json
{
  "entidad": "producto_caucho",
  "tabla": "emp_1_productos_caucho",
  "total": 2,
  "registros": [
    { "id": 1, "ancho": 205, "perfil": 55, "diametro": 16, "marca": "Bridgestone", "precio": 85.0, "stock": 10 },
    { "id": 2, "ancho": 195, "perfil": 65, "diametro": 15, "marca": "Michelin",    "precio": 92.0, "stock": 3  }
  ]
}
```

**Crear registro:**
```javascript
await fetch(`/api/empresa/${eid}/entidad/${entid}/registros`, {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ ancho: 205, perfil: 55, diametro: 16, marca: 'Pirelli', precio: 78.0, stock: 5 })
})
```

**Editar registro:**
```javascript
await fetch(`/api/empresa/${eid}/entidad/${entid}/registros/${id}`, {
  method: 'PUT',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ stock: 15 })
})
```

**Eliminar registro:**
```javascript
await fetch(`/api/empresa/${eid}/entidad/${entid}/registros/${id}`, {
  method: 'DELETE',
  credentials: 'include'
})
```

---

## POST /api/auth/logout

```javascript
await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' })
window.location.href = '/'
```

---

## Patrón de carga inicial del dashboard

```javascript
async function initDashboard() {
  // 1. Config global
  const config = await fetch('/api/config').then(r => r.json())
  if (!config.configurado) { window.location.href = '/setup'; return }

  // 2. Usuario autenticado
  const meRes = await fetch('/api/auth/me', { credentials: 'include' })
  if (!meRes.ok) { window.location.href = '/'; return }
  const { usuario } = await meRes.json()

  // 3. Datos del dashboard (en paralelo)
  const [resumen, alertas, tareas] = await Promise.all([
    fetch('/api/dss/resumen',  { credentials: 'include' }).then(r => r.json()),
    fetch('/api/alertas',      { credentials: 'include' }).then(r => r.json()),
    fetch('/api/tareas',       { credentials: 'include' }).then(r => r.json()),
  ])

  // 4. Entidades del rubro
  const entidades = await fetch(`/api/rubros/${config.rubro_id}/entidades`).then(r => r.json())

  // Renderizar todo...
}
```
