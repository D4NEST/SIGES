# Spec: Dashboard Dinámico SIGES

## Objetivo

Reemplazar el contenido estático de `frontend/dashboard.html` con un dashboard completamente dinámico que consuma la API REST del backend Flask. El dashboard es la pantalla principal de uso diario del sistema SIGES.

## Contexto

- El archivo `frontend/dashboard.html` existe pero contiene código del sistema anterior (tablas fijas de inventario)
- El backend está completo y funcional — todos los endpoints necesarios ya existen
- Los estilos globales están en `frontend/siges.css` (tema futurista oscuro, glassmorphism)
- La config global se obtiene de `/api/config` y contiene `empresa_id` y `rubro_id` que se usan en todos los endpoints dinámicos
- Ver `#[[file:.kiro/steering/api.md]]` para referencia completa de endpoints y ejemplos de respuesta
- Ver `#[[file:.kiro/steering/structure.md]]` para entender cómo funciona el meta-modelador

## Requisitos funcionales

### RF-01 Autenticación y carga inicial
- Al cargar, verificar sesión con `/api/auth/me`. Si 401, redirigir a `/`
- Cargar config con `/api/config`. Si no configurado, redirigir a `/setup`
- Mostrar logo dinámico (desde `config.logo_path`) y nombre de empresa en el header
- Mostrar nombre e inicial del usuario autenticado en el avatar

### RF-02 Stats cards
- Mostrar una card por cada entidad del rubro con su total de registros (desde `/api/dss/resumen`)
- Mostrar card de tareas pendientes y notificaciones no leídas

### RF-03 Panel de alertas
- Consumir `/api/alertas` y mostrar lista de alertas activas
- Diferenciar visualmente `stock_bajo` (warning) y `tarea_vencida` (danger)
- Mostrar contador de alertas en el header

### RF-04 Selector de entidad y tabla dinámica
- Mostrar botones/tabs con las entidades del rubro (desde `/api/rubros/{rubro_id}/entidades`)
- Al seleccionar una entidad, cargar su schema y sus registros
- Construir los encabezados de la tabla a partir de `schema.campos` donde `visible_en_tabla = true`
- Renderizar filas con los datos de `/api/empresa/{eid}/entidad/{entid}/registros`
- Botones de editar y eliminar por fila

### RF-05 Formulario dinámico (modal)
- Botón "Nuevo registro" abre un modal
- Los campos del formulario se generan dinámicamente desde `schema.campos` donde `visible_en_formulario = true`
- Cada tipo de campo (`string`, `integer`, `currency`, `boolean`, `date`, `select`, `text`, `email`) renderiza el input correcto
- Campos con `es_requerido = true` tienen el atributo `required`
- El mismo modal sirve para crear (POST) y editar (PUT) — cambia el título y el comportamiento del submit
- Al guardar, refrescar la tabla

### RF-06 Panel de tareas
- Listar tareas pendientes desde `/api/tareas`
- Checkbox para marcar como completada (PUT con `completada: true`)
- Formulario inline para crear nueva tarea (título + prioridad + fecha límite opcional)
- Indicador visual de prioridad (alta=rojo, media=amarillo, baja=verde)

### RF-07 Toggle modo claro/oscuro
- Botón en el header que alterna clase `light-mode` en `<body>`
- Las variables CSS en `siges.css` ya tienen la estructura base; agregar overrides para `body.light-mode`
- Persistir preferencia en `localStorage`

### RF-08 Logout
- Botón logout llama a `POST /api/auth/logout` y redirige a `/`

## Requisitos no funcionales

- Sin frameworks JS externos (vanilla JS únicamente)
- Usar Font Awesome 6 (CDN) para iconos
- Usar `frontend/siges.css` para estilos base — no duplicar estilos ya definidos
- Responsive: funcionar en móvil y desktop
- Manejo de errores: mostrar mensaje si la API falla, no romper la UI
- Cada sección debe funcionar independientemente (si alertas falla, el resto carga igual)
