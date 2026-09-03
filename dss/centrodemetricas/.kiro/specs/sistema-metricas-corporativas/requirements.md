# Documento de Requisitos

## Introducción

El Sistema de Métricas Corporativas es una aplicación web desarrollada en Laravel 11 para la Corporación Socialista de Cemento. Su propósito es centralizar, procesar y visualizar datos operativos del personal mediante la carga de archivos Excel, el cruce de información entre fuentes de datos y la proyección de métricas en tiempo real. El sistema gestiona tres niveles de acceso (Administrador, Supervisor, Operador), garantiza la inmutabilidad y trazabilidad de los datos mediante auditoría, y procesa grandes volúmenes de registros de forma eficiente mediante colas de trabajo asíncronas.

---

## Glosario

- **Sistema**: La aplicación web Laravel 11 del Centro de Métricas Corporativas.
- **Operador**: Usuario con rol `operador` que carga archivos Excel al sistema.
- **Supervisor**: Usuario con rol `supervisor` que consulta reportes y métricas.
- **Administrador**: Usuario con rol `admin` que gestiona usuarios, purga datos y accede a todas las funcionalidades.
- **Archivo Principal**: Archivo Excel con las columnas: cedula, nombres_apellidos, cargo, ubicacion_administrativa, planta, filial, estado_ubicacion_fisica, telefono, estado, municipio, parroquia, centro_votacion, direccion_centro_votacion.
- **Archivo de Votos**: Archivo Excel con las columnas: cedula, estatus_voto (Si/No).
- **Tabla `centro_metricas`**: Tabla principal de la base de datos donde se almacenan los registros procesados del personal.
- **Wizard de Mapeo**: Componente de interfaz de usuario que permite al Operador asociar columnas del Excel a campos de la base de datos antes de la importación.
- **Job de Importación**: Proceso asíncrono de Laravel (Queue Job) que ejecuta la carga masiva de registros en segundo plano.
- **Chunk**: Fragmento de N filas del Excel procesadas en una sola transacción de base de datos.
- **RBAC**: Control de Acceso Basado en Roles (Role-Based Access Control).
- **AuditLog**: Modelo y tabla que registra cada acción relevante con usuario, IP, timestamp y detalles.
- **Cruce de Cédulas**: Proceso de JOIN entre `centro_metricas` y los datos del Archivo de Votos usando el campo `cedula` como llave foránea lógica.
- **Hash SHA-256**: Huella digital del archivo cargado para detectar duplicados y garantizar integridad.

---

## Requisitos

### Requisito 1: Autenticación y Control de Acceso por Roles (RBAC)

**Historia de Usuario:** Como administrador del sistema, quiero que cada usuario solo acceda a las funcionalidades correspondientes a su rol, para que la información sensible esté protegida contra accesos no autorizados.

#### Criterios de Aceptación

1. WHEN un usuario no autenticado solicita cualquier ruta protegida, THE Sistema SHALL redirigir al usuario a la página de inicio de sesión.
2. WHEN un usuario autenticado con rol `operador` solicita una ruta de `supervisor` o `admin`, THE Sistema SHALL responder con un error HTTP 403 y no exponer ningún dato.
3. WHEN un usuario autenticado con rol `supervisor` solicita una ruta de `admin`, THE Sistema SHALL responder con un error HTTP 403 y no exponer ningún dato.
4. WHEN un usuario autenticado con rol `admin` solicita cualquier ruta protegida del sistema, THE Sistema SHALL conceder acceso sin restricciones de rol.
5. WHILE un usuario tiene una sesión activa, THE Sistema SHALL validar el rol en cada petición HTTP mediante el middleware `CheckRole`.
6. WHILE un usuario autenticado no realiza ninguna petición HTTP durante 15 minutos consecutivos, THE Sistema SHALL invalidar la sesión activa y redirigir al usuario a la página de inicio de sesión con un mensaje informativo.

---

### Requisito 2: Gestión de Usuarios por el Administrador

**Historia de Usuario:** Como administrador, quiero crear, editar y desactivar cuentas de usuario con roles asignados y notificación automática de credenciales, para que el acceso al sistema esté controlado desde un panel centralizado.

#### Criterios de Aceptación

1. WHEN el administrador crea un nuevo usuario, THE Sistema SHALL almacenar la contraseña usando el algoritmo `bcrypt` con un factor de coste mínimo de 10 y enviar un correo de bienvenida con las credenciales temporales mediante una cola de trabajo asíncrona.
2. WHEN el administrador asigna un rol a un usuario, THE Sistema SHALL validar que el rol sea uno de los valores permitidos: `admin`, `supervisor`, u `operador`.
3. WHEN el administrador desactiva un usuario, THE Sistema SHALL invalidar todas las sesiones activas de ese usuario de forma inmediata.
4. WHEN se crea o modifica un usuario, THE Sistema SHALL registrar el evento en la tabla `audit_logs` con el identificador del administrador que realizó la acción, la IP de origen y el timestamp exacto.

---

### Requisito 3: Carga de Archivos Excel mediante Wizard de Mapeo

**Historia de Usuario:** Como operador, quiero subir archivos Excel y mapear sus columnas a los campos de la base de datos a través de un asistente visual, para que la importación sea flexible ante variaciones en el formato del archivo recibido.

#### Criterios de Aceptación

1. WHEN el operador sube un archivo, THE Sistema SHALL validar que el archivo tenga extensión `.xlsx` o `.xls`, un tamaño máximo de 20 MB y que el contenido sea un documento Office Open XML válido.
2. WHEN el archivo pasa la validación, THE Sistema SHALL calcular el hash SHA-256 del archivo y rechazar la importación si un archivo con el mismo hash ya fue procesado anteriormente.
3. WHEN el archivo es aceptado, THE Sistema SHALL leer los encabezados de la primera fila y presentar al operador el Wizard de Mapeo para asociar cada columna del Excel a un campo de `centro_metricas`.
4. WHEN el operador confirma el mapeo, THE Sistema SHALL validar que el campo `cedula` haya sido mapeado obligatoriamente antes de despachar el Job de Importación.
5. IF el archivo contiene filas con el campo `cedula` vacío o con formato no numérico de 7 a 8 dígitos, THEN THE Sistema SHALL omitir esas filas, registrarlas en un log de errores de importación y continuar procesando el resto.
6. WHEN el operador confirma el mapeo, THE Sistema SHALL validar que el tipo de dato de cada columna mapeada sea compatible con el tipo del campo destino en `centro_metricas` antes de despachar el Job de Importación, y rechazar el mapeo presentando al operador un reporte de incompatibilidades si se detectan columnas de tipo texto mapeadas a campos numéricos o de tipo fecha.

---

### Requisito 4: Procesamiento Asíncrono de Alta Velocidad

**Historia de Usuario:** Como operador, quiero que la importación de archivos grandes se procese en segundo plano sin bloquear la interfaz, para que pueda continuar trabajando mientras el sistema importa los datos.

#### Criterios de Aceptación

1. WHEN el operador despacha una importación, THE Sistema SHALL encolar un Job de Importación y devolver una respuesta HTTP 202 con un identificador de seguimiento en menos de 3 segundos.
2. WHILE el Job de Importación se ejecuta, THE Sistema SHALL procesar los registros en chunks de 500 filas por transacción de base de datos para limitar el uso de memoria RAM.
3. WHEN un chunk de 500 filas se procesa exitosamente, THE Sistema SHALL realizar un `upsert` basado en el campo `cedula` para evitar duplicados en `centro_metricas`.
4. IF el Job de Importación falla por una excepción no controlada, THEN THE Sistema SHALL reintentar el Job hasta 3 veces con retardo exponencial y registrar el fallo final en `audit_logs`.
5. WHEN el Job de Importación completa todos los chunks, THE Sistema SHALL registrar en `audit_logs` el total de filas procesadas, filas insertadas, filas actualizadas y filas rechazadas.

---

### Requisito 5: Cruce de Datos (Personal vs. Votos)

**Historia de Usuario:** Como supervisor, quiero cruzar el padrón de trabajadores con el archivo de estatus de voto, para que pueda identificar qué empleados han ejercido su voto y generar reportes diferenciados.

#### Criterios de Aceptación

1. WHEN el supervisor inicia el cruce de datos, THE Sistema SHALL realizar un `LEFT JOIN` entre `centro_metricas` y la tabla de votos usando el campo `cedula` como llave de enlace.
2. WHEN el cruce se completa, THE Sistema SHALL clasificar cada registro como `votó` (Si) o `no votó` (No/NULL).
3. WHEN se genera el resultado del cruce, THE Sistema SHALL presentar el conteo total de inscritos, el total de votantes y el porcentaje de participación.
4. IF una cédula existe en el archivo de votos pero no en `centro_metricas`, THEN THE Sistema SHALL registrar esa cédula en un reporte de anomalías sin insertarla en la tabla principal.

---

### Requisito 6: Dashboard de Métricas en Tiempo Real

**Historia de Usuario:** Como supervisor o administrador, quiero visualizar métricas actualizadas del padrón en un dashboard, para que pueda tomar decisiones operativas basadas en datos precisos y vigentes.

#### Criterios de Aceptación

1. WHEN un supervisor o administrador accede al dashboard, THE Sistema SHALL ejecutar consultas Eloquent con índices optimizados sobre `centro_metricas` y devolver los resultados en menos de 5 segundos para conjuntos de hasta 100,000 registros.
2. WHEN el dashboard carga, THE Sistema SHALL mostrar el conteo de registros agrupados por `planta` y `filial`.
3. WHEN el dashboard carga, THE Sistema SHALL mostrar el conteo de inscritos vs. no inscritos al cruce de votos.
4. WHEN el dashboard carga, THE Sistema SHALL mostrar la distribución de registros por `estado` geográfico.
5. WHEN el dashboard carga, THE Sistema SHALL mostrar la trazabilidad de cédulas cargadas al sistema agrupadas por hora del día usando el campo `created_at` de `audit_logs`.
6. WHILE el dashboard está abierto en el navegador, THE Sistema SHALL actualizar automáticamente todas las métricas cada 30 segundos mediante polling HTTP sin recargar la página completa, utilizando Livewire con la directiva `wire:poll.30s`.

---

### Requisito 7: Trazabilidad e Inmutabilidad de la Auditoría

**Historia de Usuario:** Como administrador, quiero que cada acción relevante del sistema quede registrada de forma permanente e inalterable, para que exista evidencia forense de cualquier operación realizada.

#### Criterios de Aceptación

1. WHEN cualquier usuario realiza una acción clasificada (login, logout, carga de archivo, creación de usuario, consulta de reporte), THE Sistema SHALL insertar un registro en `audit_logs` con: `user_id`, `usuario_nombre`, `evento`, `descripcion`, `detalles_extra` (JSON), `url_solicitada`, `direccion_ip`, `user_agent` y `created_at`.
2. THE Sistema SHALL prohibir las operaciones `UPDATE` y `DELETE` sobre la tabla `audit_logs` a nivel de base de datos mediante permisos de usuario de MySQL dedicado con privilegios de solo `INSERT` y `SELECT`.
3. WHEN el administrador consulta la bitácora de auditoría, THE Sistema SHALL paginar los resultados en grupos de 50 registros ordenados por `created_at` descendente.
4. IF el usuario autenticado no tiene rol `admin`, THEN THE Sistema SHALL negar el acceso a la vista de auditoría con error HTTP 403.
.
---

### Requisito 8: Mantenimiento y Archivado de Datos Históricos

**Historia de Usuario:** Como administrador, quiero exportar y purgar registros antiguos del sistema, para que el rendimiento de la base de datos se mantenga óptimo conforme crezca el volumen de datos a lo largo de los años.

#### Criterios de Aceptación

1. WHEN el administrador ejecuta la función de archivado, THE Sistema SHALL exportar todos los registros de `centro_metricas` con `created_at` anterior a 365 días en un archivo comprimido en formato `.zip` que contenga un archivo `.csv` con todos los campos, almacenado en `storage/app/private/archivos/`.
2. WHEN la exportación del archivo comprimido se complete exitosamente, THE Sistema SHALL eliminar de la tabla `centro_metricas` únicamente los registros incluidos en ese archivo, utilizando una transacción de base de datos para garantizar la atomicidad de la operación.
3. IF la exportación falla por cualquier motivo antes de completarse, THEN THE Sistema SHALL cancelar la eliminación de registros, mantener la base de datos en su estado original y registrar el fallo en `audit_logs`.
4. WHEN el archivado se completa, THE Sistema SHALL registrar en `audit_logs` el nombre del archivo generado, el total de registros archivados y el identificador del administrador que ejecutó la acción.
