# Tasks: Dashboard Dinámico SIGES

Cada tarea es independiente y ejecutable en un chat nuevo.
Antes de cada tarea, leer: `frontend/dashboard.html`, `frontend/siges.css`, `.kiro/steering/api.md`

---

## Tarea 1 — Estructura base HTML

**Archivo:** `frontend/dashboard.html`  
**Dependencias:** ninguna (es la base)  
**Estado:** pendiente

### Qué hacer

Reemplazar completamente el contenido de `frontend/dashboard.html` con la estructura HTML base del dashboard dinámico. Sin lógica JS aún — solo el esqueleto con IDs correctos.

### Estructura requerida

```html
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <!-- meta tags, title "SIGES — Dashboard" -->
  <!-- Font Awesome CDN -->
  <!-- link a /static/siges.css  (Flask sirve frontend/ como static) -->
  <!-- <style> con layout sidebar+main, sidebar-btn, light-mode overrides -->
</head>
<body>
<div id="app">

  <header id="dashHeader">
    <!-- .header-logo: img#logoImg + div.header-titles (h1#companyName, p#rubroName) -->
    <!-- .header-center: span#entidadActiva -->
    <!-- .header-right: 
         span#alertasBadge (badge contador)
         button#toggleTema (☀️/🌙)
         div.user-avatar#userAvatar
         span#userName
         button#btnLogout -->
  </header>

  <div id="layout">

    <aside id="sidebar">
      <!-- div#sidebarEntidades (botones generados por JS) -->
      <!-- hr separador -->
      <!-- button fijo "📋 Tareas" que hace scroll a #tareasSection -->
    </aside>

    <main id="mainArea">

      <section id="statsSection">
        <!-- div.stats → cards generadas por JS -->
      </section>

      <section id="alertasSection">
        <!-- div#alertasList → items generados por JS -->
        <!-- oculto si no hay alertas -->
      </section>

      <section id="tablaSection">
        <div class="page-title">
          <h1 id="tablaTitle">Selecciona una entidad</h1>
          <button class="btn" id="btnNuevoRegistro">
            <i class="fas fa-plus"></i> Nuevo registro
          </button>
        </div>
        <div class="card">
          <div class="search-bar">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar...">
          </div>
          <div class="table-responsive">
            <table id="tablaRegistros">
              <thead id="tablaHead"></thead>
              <tbody id="tablaBody">
                <tr><td colspan="99" style="text-align:center;padding:40px">
                  Selecciona una entidad del menú lateral
                </td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="tareasSection">
        <div class="page-title">
          <h1>📋 Tareas pendientes</h1>
        </div>
        <div class="card">
          <div id="tareasList"></div>
          <!-- formulario nueva tarea inline -->
          <div id="formNuevaTarea" style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
            <input type="text" id="nuevaTareaTitulo" class="form-control"
                   placeholder="Nueva tarea..." style="flex:1;min-width:200px">
            <select id="nuevaTareaPrioridad" class="form-control" style="width:120px">
              <option value="alta">🔴 Alta</option>
              <option value="media" selected>🟡 Media</option>
              <option value="baja">🟢 Baja</option>
            </select>
            <input type="date" id="nuevaTareaFecha" class="form-control" style="width:160px">
            <button class="btn btn-sm" id="btnAgregarTarea">
              <i class="fas fa-plus"></i> Agregar
            </button>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

<!-- MODAL registro -->
<div class="modal" id="modalRegistro" style="display:none">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modalTitulo">Nuevo registro</h2>
      <button class="close-btn" id="btnCerrarModal">&times;</button>
    </div>
    <form id="formRegistro" style="padding:30px">
      <div id="camposFormulario"></div>
      <div style="margin-top:20px;display:flex;gap:10px">
        <button type="submit" class="btn" style="flex:1" id="btnGuardarRegistro">
          <i class="fas fa-save"></i> Guardar
        </button>
        <button type="button" class="btn btn-danger" style="width:120px" id="btnCancelarModal">
          Cancelar
        </button>
      </div>
      <p id="modalError" style="color:var(--color-danger);margin-top:10px;display:none"></p>
    </form>
  </div>
</div>

<script>
  // TODO: implementar en Tarea 2
  console.log('Dashboard cargado — JS pendiente')
</script>
</body>
</html>
```

### Criterio de éxito
- La página carga en `http://localhost:5000/dashboard` sin errores de consola
- Se ve el header, sidebar vacío, secciones vacías con sus IDs correctos
- El modal existe pero está oculto

---

## Tarea 2 — Carga inicial y stats/alertas

**Archivo:** `frontend/dashboard.html`  
**Dependencias:** Tarea 1 completada  
**Estado:** pendiente

### Qué hacer

Implementar el bloque `<script>` con la carga inicial de datos y el renderizado de header, stats cards, alertas y tareas.

### Funciones a implementar

```javascript
const API = '/api'
const STATE = { config: null, usuario: null, entidades: [], entidadActiva: null, schema: null, registros: [], editandoId: null }

async function initDashboard() { ... }   // orquesta todo
function renderHeader(config, usuario) { ... }
function renderStats(resumen) { ... }    // genera .stat-card por entidad + tareas_pendientes
function renderAlertas(alertas) { ... }  // genera items en #alertasList, oculta sección si vacío
function renderTareas(tareas) { ... }    // genera items en #tareasList con checkbox y prioridad
async function toggleTarea(id, completada) { ... }
async function agregarTarea() { ... }
function aplicarTema() { ... }           // lee localStorage y aplica clase light-mode
```

### Detalle de renderStats

```javascript
function renderStats(resumen) {
  const section = document.getElementById('statsSection')
  const cards = resumen.entidades.map(e => `
    <div class="stat-card">
      <div class="stat-value">${e.total_registros}</div>
      <div class="stat-label">${e.nombre}</div>
    </div>
  `).join('')
  // + card tareas_pendientes + card notificaciones_no_leidas
  section.innerHTML = `<div class="stats">${cards}...</div>`
}
```

### Detalle de renderAlertas

- Si `alertas.length === 0`, hacer `alertasSection.style.display = 'none'`
- Si hay alertas, mostrar con badge de tipo (`stock_bajo` → `.badge-warning`, `tarea_vencida` → `.badge-danger`)
- Actualizar `#alertasBadge` en el header con el conteo

### Criterio de éxito
- Al cargar `/dashboard` se ven las stats cards con datos reales
- Las alertas aparecen si existen
- Las tareas se listan con checkbox funcional
- El toggle de tema funciona y persiste en localStorage

---

## Tarea 3 — Selector de entidad (sidebar)

**Archivo:** `frontend/dashboard.html`  
**Dependencias:** Tarea 2 completada  
**Estado:** pendiente

### Qué hacer

Implementar el sidebar con botones de entidades y la función `seleccionarEntidad`.

### Funciones a implementar

```javascript
function renderSidebar(entidades) {
  // Genera botones .sidebar-btn en #sidebarEntidades
  // Cada botón tiene data-entidad-id y onclick → seleccionarEntidad(entidad)
  // El primero se marca como .active automáticamente
}

async function seleccionarEntidad(entidad) {
  // 1. Marcar botón activo en sidebar
  // 2. Actualizar STATE.entidadActiva
  // 3. Actualizar #entidadActiva en header y #tablaTitle
  // 4. Cargar schema: GET /api/empresa/{eid}/entidad/{entid}/schema
  // 5. Cargar registros: GET /api/empresa/{eid}/entidad/{entid}/registros
  // 6. Llamar renderTablaHeaders(schema) y renderTablaRows(registros)
}
```

### Criterio de éxito
- El sidebar muestra un botón por cada entidad del rubro con su icono y nombre plural
- Al hacer click en un botón, la tabla cambia de contenido
- El botón activo tiene estilo diferenciado
- La primera entidad se carga automáticamente al iniciar

---

## Tarea 4 — Tabla dinámica de registros

**Archivo:** `frontend/dashboard.html`  
**Dependencias:** Tarea 3 completada  
**Estado:** pendiente

### Qué hacer

Implementar el renderizado de la tabla con headers y filas dinámicas, búsqueda y eliminación.

### Funciones a implementar

```javascript
function renderTablaHeaders(schema) {
  // Filtra campos con visible_en_tabla = true
  // Genera <th> por cada campo usando campo.etiqueta
  // Agrega columna "Acciones" al final
}

function renderTablaRows(registros, campos) {
  // Por cada registro genera <tr>
  // Por cada campo visible_en_tabla genera <td> con el valor
  // Última celda: botones editar (btn-editar) y eliminar (btn-eliminar)
  // Si registros vacío: mostrar mensaje "Sin registros aún"
}

async function eliminarRegistro(id) {
  // Confirmar con confirm()
  // DELETE /api/empresa/{eid}/entidad/{entid}/registros/{id}
  // Recargar registros
}

function filtrarTabla(texto) {
  // Filtra STATE.registros por texto en cualquier campo
  // Re-renderiza solo las filas
}
```

### Formato de valores por tipo

```javascript
function formatearValor(valor, tipo) {
  if (valor === null || valor === undefined) return '—'
  if (tipo === 'boolean') return valor ? '✅' : '❌'
  if (tipo === 'currency') return `$${parseFloat(valor).toFixed(2)}`
  if (tipo === 'date') return new Date(valor).toLocaleDateString('es-VE')
  return valor
}
```

### Criterio de éxito
- La tabla muestra columnas correctas según el schema de la entidad activa
- Los valores se formatean según su tipo
- El buscador filtra en tiempo real
- Eliminar pide confirmación y refresca la tabla

---

## Tarea 5 — Modal de formulario dinámico

**Archivo:** `frontend/dashboard.html`  
**Dependencias:** Tarea 4 completada  
**Estado:** pendiente

### Qué hacer

Implementar el modal con generación dinámica de campos, y las operaciones POST (crear) y PUT (editar).

### Funciones a implementar

```javascript
function abrirModal(registro = null) {
  // Si registro → modo editar (STATE.editandoId = registro.id)
  // Si null → modo crear (STATE.editandoId = null)
  // Actualizar #modalTitulo
  // Llamar generarCamposFormulario(STATE.schema.campos, registro)
  // Mostrar modal (display: 'flex')
}

function cerrarModal() {
  document.getElementById('modalRegistro').style.display = 'none'
  STATE.editandoId = null
}

function generarCamposFormulario(campos, datosExistentes = {}) {
  // Filtra campos con visible_en_formulario = true
  // Por cada campo genera:
  //   <div class="form-group">
  //     <label>campo.etiqueta</label>
  //     [input según tipo — ver design.md]
  //   </div>
  // Inyecta en #camposFormulario
}

async function guardarRegistro(e) {
  e.preventDefault()
  // Recoger datos del formulario (FormData o manual por campo)
  // Si STATE.editandoId → PUT /registros/{id}
  // Si no → POST /registros
  // Si ok → cerrarModal() + recargarRegistros()
  // Si error → mostrar en #modalError
}
```

### Tipos de input por tipo de campo

| tipo | input HTML |
|------|-----------|
| string | `<input type="text">` |
| email | `<input type="email">` |
| integer | `<input type="number" step="1">` |
| float / currency | `<input type="number" step="0.01">` |
| boolean | `<input type="checkbox">` |
| date | `<input type="date">` |
| text | `<textarea rows="3">` |
| select | `<select>` con opciones de `campo.opciones` |

### Criterio de éxito
- "Nuevo registro" abre el modal con campos correctos para la entidad activa
- "Editar" abre el modal con los datos del registro precargados
- Guardar crea o actualiza el registro y refresca la tabla
- Los campos requeridos tienen validación HTML5 (`required`)
- Errores de la API se muestran en el modal sin cerrarlo

---

## Tarea 6 — Toggle modo claro/oscuro

**Archivos:** `frontend/dashboard.html`, `frontend/siges.css`  
**Dependencias:** Tarea 2 completada (puede hacerse en paralelo con 3-5)  
**Estado:** pendiente

### Qué hacer

#### En `frontend/siges.css` — agregar al final:

```css
/* ====================================================================
   MODO CLARO
==================================================================== */
body.light-mode {
  --color-bg: #f0f4f8;
  --color-bg-card: rgba(255, 255, 255, 0.9);
  --color-bg-card-hover: rgba(255, 255, 255, 1);
  --color-bg-modal: rgba(240, 244, 248, 0.98);
  --color-text: #1a1a2e;
  --color-text-secondary: #4a4a6a;
  --color-text-muted: #7a7a9a;
  --color-border: rgba(0, 0, 0, 0.1);
}

body.light-mode body::before {
  background:
    radial-gradient(circle at 20% 80%, rgba(0, 102, 255, 0.05) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(0, 212, 255, 0.04) 0%, transparent 50%);
}

body.light-mode .form-control {
  background: rgba(0, 0, 0, 0.03);
  color: var(--color-text);
}

body.light-mode select.form-control {
  background-color: rgba(240, 244, 248, 0.95) !important;
  color: var(--color-text) !important;
}
```

#### En `frontend/dashboard.html` — función JS:

```javascript
function aplicarTema() {
  const tema = localStorage.getItem('siges-tema') || 'dark'
  document.body.classList.toggle('light-mode', tema === 'light')
  document.getElementById('toggleTema').textContent = tema === 'light' ? '🌙' : '☀️'
}

function toggleTema() {
  const actual = localStorage.getItem('siges-tema') || 'dark'
  const nuevo = actual === 'dark' ? 'light' : 'dark'
  localStorage.setItem('siges-tema', nuevo)
  aplicarTema()
}
```

Llamar `aplicarTema()` al inicio de `initDashboard()`.  
El botón `#toggleTema` llama a `toggleTema()`.

### Criterio de éxito
- El botón alterna entre ☀️ (modo oscuro activo) y 🌙 (modo claro activo)
- El fondo, cards, textos y bordes cambian correctamente
- La preferencia persiste al recargar la página
- El gradiente azul y los colores primarios se mantienen en ambos modos
