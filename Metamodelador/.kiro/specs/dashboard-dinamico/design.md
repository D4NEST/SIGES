# Design: Dashboard Dinámico SIGES

## Estructura HTML general

```
<body>
  <div id="app">

    <!-- HEADER sticky -->
    <header id="dashHeader">
      logo + nombre empresa | nombre entidad activa | alertas badge | toggle tema | avatar + logout
    </header>

    <!-- LAYOUT principal: sidebar + contenido -->
    <div id="layout">

      <!-- SIDEBAR izquierdo -->
      <aside id="sidebar">
        Botones de entidades del rubro (generados dinámicamente)
        ──────────────
        Sección Tareas (fija)
      </aside>

      <!-- ÁREA PRINCIPAL -->
      <main id="mainArea">

        <!-- STATS CARDS (fila superior) -->
        <section id="statsSection">
          Card por entidad (total registros) + card tareas pendientes
        </section>

        <!-- ALERTAS (colapsable) -->
        <section id="alertasSection">
          Lista de alertas activas
        </section>

        <!-- TABLA DE REGISTROS -->
        <section id="tablaSection">
          Título entidad activa + botón "Nuevo registro"
          Barra de búsqueda
          Tabla dinámica (thead y tbody generados por JS)
        </section>

        <!-- PANEL DE TAREAS -->
        <section id="tareasSection">
          Lista de tareas + formulario nueva tarea
        </section>

      </main>
    </div>

  </div>

  <!-- MODAL formulario dinámico -->
  <div id="modalRegistro">
    <div class="modal-content">
      <div class="modal-header"> Título dinámico + close </div>
      <form id="formRegistro">
        <!-- campos generados por JS según schema -->
      </form>
    </div>
  </div>

</body>
```

## Estado global JS

```javascript
const STATE = {
  config: null,        // respuesta de /api/config
  usuario: null,       // respuesta de /api/auth/me
  entidades: [],       // respuesta de /api/rubros/{id}/entidades
  entidadActiva: null, // entidad seleccionada actualmente
  schema: null,        // schema de la entidad activa
  registros: [],       // registros de la entidad activa
  editandoId: null,    // null = crear, number = editar
}
```

## Flujo de interacción

```
initDashboard()
  ├── cargar config + usuario (secuencial, si falla → redirect)
  ├── cargar resumen + alertas + tareas + entidades (Promise.all)
  ├── renderHeader(config, usuario)
  ├── renderStats(resumen)
  ├── renderAlertas(alertas)
  ├── renderSidebar(entidades)
  ├── renderTareas(tareas)
  └── seleccionarEntidad(entidades[0])  ← carga la primera por defecto
        ├── cargar schema
        ├── cargar registros
        ├── renderTablaHeaders(schema)
        └── renderTablaRows(registros)

click sidebar entidad
  └── seleccionarEntidad(entidad)

click "Nuevo registro"
  └── abrirModal(null)  ← modo crear
        └── generarCamposFormulario(schema)

click "Editar" en fila
  └── abrirModal(registro)  ← modo editar
        └── generarCamposFormulario(schema, datosExistentes)

submit formulario
  ├── si editandoId → PUT /registros/{id}
  └── si no → POST /registros
  └── cerrarModal() → recargarRegistros()

click "Eliminar" en fila
  └── confirmar → DELETE /registros/{id} → recargarRegistros()

click checkbox tarea
  └── PUT /tareas/{id} { completada: true } → recargarTareas()

click toggle tema
  └── body.classList.toggle('light-mode')
  └── localStorage.setItem('tema', ...)
```

## Generación dinámica de inputs por tipo

```javascript
function crearInput(campo, valor = '') {
  switch (campo.tipo) {
    case 'string':
    case 'email':
      return `<input type="${campo.tipo}" name="${campo.nombre_fisico}" value="${valor}"
               placeholder="${campo.placeholder || ''}" ${campo.es_requerido ? 'required' : ''}>`

    case 'integer':
      return `<input type="number" step="1" name="${campo.nombre_fisico}" value="${valor}"
               ${campo.es_requerido ? 'required' : ''}>`

    case 'float':
    case 'currency':
      return `<input type="number" step="0.01" name="${campo.nombre_fisico}" value="${valor}"
               ${campo.es_requerido ? 'required' : ''}>`

    case 'boolean':
      return `<input type="checkbox" name="${campo.nombre_fisico}" ${valor ? 'checked' : ''}>`

    case 'date':
      return `<input type="date" name="${campo.nombre_fisico}" value="${valor?.split('T')[0] || ''}"
               ${campo.es_requerido ? 'required' : ''}>`

    case 'text':
      return `<textarea name="${campo.nombre_fisico}" rows="3"
               ${campo.es_requerido ? 'required' : ''}>${valor}</textarea>`

    case 'select':
      const opts = (campo.opciones || []).map(o =>
        `<option value="${o}" ${valor === o ? 'selected' : ''}>${o}</option>`
      ).join('')
      return `<select name="${campo.nombre_fisico}" ${campo.es_requerido ? 'required' : ''}>
                <option value="">-- Selecciona --</option>${opts}
              </select>`
  }
}
```

## Variables CSS para modo claro (agregar a siges.css)

```css
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
```

## Layout CSS (agregar en dashboard.html `<style>`)

```css
#layout {
  display: flex;
  min-height: calc(100vh - 65px);
}
#sidebar {
  width: 220px;
  flex-shrink: 0;
  background: var(--color-bg-card);
  border-right: 1px solid var(--color-border);
  padding: 20px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
#mainArea {
  flex: 1;
  padding: 30px;
  overflow-y: auto;
}
.sidebar-btn {
  width: 100%;
  padding: 10px 14px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--color-text-secondary);
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}
.sidebar-btn:hover, .sidebar-btn.active {
  background: var(--color-bg-card-hover);
  border-color: var(--color-border);
  color: var(--color-text);
}
.sidebar-btn.active {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
```
