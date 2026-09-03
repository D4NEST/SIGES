# Documento de Requisitos

## Introducción

Esta funcionalidad implementa un sistema de auditoría de progreso de carga que permite a los administradores visualizar el avance de carga de datos por parte de los supervisores, y a los supervisores visualizar el progreso de carga de sus operadores asignados. El sistema mostrará métricas en tiempo real, barras de progreso y alertas de usuarios que no han completado sus cargas.

## Glosario

- **Sistema**: La aplicación web Laravel del Sistema de Métricas Corporativas
- **Administrador**: Usuario con rol 'admin' que puede ver el progreso de todos los supervisores
- **Supervisor**: Usuario con rol 'supervisor' que puede ver el progreso de sus operadores asignados
- **Operador**: Usuario con rol 'operador' responsable de cargar archivos de datos
- **Progreso de carga**: Porcentaje de archivos o registros que un usuario ha procesado respecto a lo esperado
- **Importación**: Registro de un archivo cargado y procesado en el sistema
- **Upload**: Registro de carga de archivo con estado y filas procesadas
- **Período de carga**: Ventana de tiempo definida para completar las cargas de datos

## Requisitos

### Requisito 1: Dashboard de Progreso para Administradores

**Historia de Usuario:** Como administrador, quiero ver el progreso de carga de todos los supervisores del sistema, para poder identificar quiénes han completado sus cargas y quiénes están pendientes.

#### Criterios de Aceptación

1. WHEN el administrador accede a la sección de auditoría, THE Sistema SHALL mostrar una lista de todos los supervisores con su estado de progreso
2. WHEN se muestra la lista de supervisores, THE Sistema SHALL incluir nombre, correo, total de cargas realizadas, y porcentaje de completitud
3. WHILE el administrador visualiza la lista, THE Sistema SHALL ordenar los supervisores por porcentaje de progreso ascendente para priorizar atención a quienes tienen menos avances
4. WHEN un supervisor no ha realizado ninguna carga, THE Sistema SHALL mostrar indicador visual de alerta y estado "Sin iniciar"
5. WHERE un supervisor tiene cargas incompletas o con errores, THE Sistema SHALL mostrar contador de errores y permitir ver detalles

### Requisito 2: Dashboard de Progreso para Supervisores

**Historia de Usuario:** Como supervisor, quiero ver el progreso de carga de los operadores bajo mi supervisión, para poder dar seguimiento a su trabajo y asegurar que completen sus cargas.

#### Criterios de Aceptación

1. WHEN el supervisor accede a la sección de auditoría, THE Sistema SHALL mostrar únicamente los operadores asignados a su zona o área
2. WHEN se muestra la lista de operadores, THE Sistema SHALL incluir nombre, centro de trabajo, total de archivos cargados, y estado actual
3. WHILE el supervisor visualiza la lista, THE Sistema SHALL permitir filtrar por estado de progreso (completado, en progreso, sin iniciar, con errores)
4. WHEN un operador no ha realizado cargas en el período actual, THE Sistema SHALL resaltarlo con indicador visual prioritario
5. WHERE existen operadores con errores de carga recientes, THE Sistema SHALL mostrar resumen de errores y opción de contacto

### Requisito 3: Visualización de Métricas de Progreso

**Historia de Usuario:** Como usuario del sistema (admin o supervisor), quiero ver métricas visuales claras del progreso de carga, para poder identificar rápidamente el estado general de las operaciones.

#### Criterios de Aceptación

1. WHEN se muestra el dashboard de auditoría, THE Sistema SHALL presentar tarjetas KPI con totales: usuarios activos, cargas completadas, pendientes, y con errores
2. WHEN se muestran los datos de progreso individual, THE Sistema SHALL utilizar barras de progreso animadas con colores semánticos (verde completado, amarillo en progreso, rojo con errores, gris sin iniciar)
3. WHILE el usuario observa el dashboard, THE Sistema SHALL actualizar automáticamente los datos cada 30 segundos sin necesidad de recargar la página
4. WHERE el progreso muestra porcentajes, THE Sistema SHALL incluir tanto el porcentaje como el conteo absoluto (ej: "75% - 15 de 20 archivos")
5. IF el usuario desea ver datos de un período específico, THE Sistema SHALL proporcionar filtros de fecha para seleccionar rango temporal

### Requisito 4: Detalles de Carga por Usuario

**Historia de Usuario:** Como administrador o supervisor, quiero poder ver los detalles de las cargas realizadas por cada usuario, para poder auditar el trabajo y detectar problemas.

#### Criterios de Aceptación

1. WHEN el usuario hace clic en el nombre de un operador o supervisor, THE Sistema SHALL mostrar un modal o panel con historial de cargas
2. WHEN se muestra el historial, THE Sistema SHALL incluir fecha, nombre de archivo, cantidad de registros procesados, estado y errores si existen
3. WHILE se visualizan los detalles, THE Sistema SHALL permitir exportar el historial a formato CSV o PDF
4. WHERE existen errores en una carga específica, THE Sistema SHALL mostrar mensaje de error detallado y sugerencia de corrección si aplica
5. IF una carga fue exitosa, THE Sistema SHALL mostrar resumen de filas insertadas, actualizadas y tiempo de procesamiento

### Requisito 5: Notificaciones y Alertas

**Historia de Usuario:** Como administrador, quiero recibir alertas automáticas cuando los supervisores u operadores no completen sus cargas en el tiempo esperado, para poder tomar acción correctiva.

#### Criterios de Aceptación

1. WHEN un supervisor no ha iniciado cargas 24 horas antes del cierre del período, THE Sistema SHALL mostrar alerta visual en el dashboard del administrador
2. WHEN un operador tiene más del 50% de sus cargas con errores, THE Sistema SHALL mostrar indicador de alerta en el dashboard del supervisor responsable
3. WHILE el administrador visualiza el dashboard, THE Sistema SHALL mostrar contador de alertas activas en la navegación
4. WHERE el usuario desea configurar umbrales de alerta personalizados, THE Sistema SHALL proporcionar configuración de notificaciones por email
5. IF todas las cargas están completas, THE Sistema SHALL mostrar indicador de éxito global con opción de generar reporte final

### Requisito 6: Exportación de Reportes de Auditoría

**Historia de Usuario:** Como administrador o supervisor, quiero poder exportar reportes de progreso de carga en PDF, para poder compartir el estado de las operaciones con otros departamentos.

#### Criterios de Aceptación

1. WHEN el usuario hace clic en "Exportar PDF", THE Sistema SHALL generar un documento con resumen de progreso, lista de usuarios y sus estados
2. WHEN se genera el PDF, THE Sistema SHALL incluir gráficos visuales de progreso, tablas de datos y fecha de generación
3. WHILE se genera el documento, THE Sistema SHALL mostrar indicador de progreso y notificar cuando esté listo para descarga
4. WHERE el usuario desea un reporte parcial, THE Sistema SHALL permitir seleccionar usuarios específicos para incluir en el reporte
5. IF el reporte es generado por un supervisor, THE Sistema SHALL incluir únicamente datos de sus operadores asignados
