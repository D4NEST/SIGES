# Plan de Implementación
## Sistema de Métricas Corporativas

- [x] 1. Preparar infraestructura base y modelos de datos



  - Instalar dependencia `maatwebsite/excel` para lectura de Excel y `giorgiosironi/eris` para property-based testing
  - Crear migración para tabla `centro_metricas` con todos los campos, índices y UNIQUE KEY en `cedula`
  - Crear migración para tabla `importaciones` con campos de seguimiento de Jobs
  - Agregar columna `active` (BOOLEAN DEFAULT TRUE) a la tabla `users` en una nueva migración
  - Crear modelos Eloquent `CentroMetrica` e `Importacion` con sus `$fillable`, casts y scopes definidos en el diseño
  - Actualizar modelo `User` agregando el campo `active` al `$fillable` y `$casts`
  - _Requisitos: 2.3, 3.3, 4.3, 5.1_

- [ ] 2. Implementar middlewares de seguridad
- [ ] 2.1 Refactorizar middleware `CheckRole` existente
  - Agregar registro en `audit_logs` cuando se detecta un intento de acceso no autorizado
  - Verificar que usuarios con `active = false` sean rechazados con HTTP 403
  - _Requisitos: 1.2, 1.3, 1.4, 2.3_

- [ ] 2.2 Crear middleware `SessionTimeout`
  - Leer `session('last_activity')`, calcular diferencia con `now()`
  - Si diferencia > 900 segundos: llamar a `Auth::logout()`, invalidar sesión, redirigir al login con mensaje flash
  - Actualizar `session('last_activity')` al final de cada petición exitosa
  - Registrar el middleware en `bootstrap/app.php` para aplicarse globalmente a rutas `auth`
  - _Requisitos: 1.6_

- [ ] 2.3 Escribir property test — Propiedad 1: RBAC niega acceso a roles insuficientes
  - **Feature: sistema-metricas-corporativas, Property 1: El RBAC niega acceso a roles insuficientes**
  - Generar combinaciones aleatorias de (rol_usuario, ruta_protegida_con_nivel_superior)
  - Verificar que CheckRole retorna HTTP 403 y no ejecuta el controlador
  - **Valida: Requisitos 1.2, 1.3**

- [ ] 3. Implementar `ImportService` — validación y hash
- [ ] 3.1 Crear `app/Services/ImportService.php`
  - Método `validarArchivo(UploadedFile): ValidationResult` — valida extensión (xlsx/xls), tamaño (≤20MB), MIME type real
  - Método `calcularHash(UploadedFile): string` — retorna SHA-256 del contenido del archivo
  - Método `leerEncabezados(UploadedFile): array` — lee primera fila del Excel y retorna array de strings con los nombres de columnas
  - _Requisitos: 3.1, 3.2, 3.3_
.
- [ ] 3.2 Escribir property test — Propiedad 2: Hash rechaza archivos duplicados
  - **Feature: sistema-metricas-corporativas, Property 2: El hash rechaza archivos duplicados**
  - Generar archivos Excel aleatorios, calcular hash, simular intento de segunda importación
  - Verificar que `Importacion::where('hash_archivo', $hash)->exists()` retorna true y el sistema rechaza con error
  - **Valida: Requisito 3.2**

- [ ] 3.3 Implementar validación de mapeo en `ImportService`
  - Método `validarMapeo(array $mapeo, array $encabezados): MapeoResult` — verifica que `cedula` está presente en el mapeo
  - Método `validarTiposPostMapeo(array $muestraDatos, array $mapeo): array` — toma primeras 10 filas del Excel y verifica compatibilidad de tipo para cada columna mapeada (numérico en cedula, strings en resto)
  - _Requisitos: 3.4, 3.6_

- [ ] 3.4 Escribir property test — Propiedad 3: Mapeo requiere cédula obligatoriamente
  - **Feature: sistema-metricas-corporativas, Property 3: El mapeo requiere la cédula obligatoriamente**
  - Generar configuraciones aleatorias de mapeo que excluyan el campo `cedula`
  - Verificar que `validarMapeo()` retorna `MapeoResult` con `isValid() === false`
  - **Valida: Requisito 3.4**

- [ ] 3.5 Escribir property test — Propiedad 4: Validación de tipos detecta incompatibilidades
  - **Feature: sistema-metricas-corporativas, Property 4: La validación de tipos detecta incompatibilidades**
  - Generar muestras de datos donde la columna mapeada a `cedula` contiene valores no numéricos o fuera del rango 7-8 dígitos
  - Verificar que `validarTiposPostMapeo()` retorna al menos un error de incompatibilidad
  - **Valida: Requisitos 3.5, 3.6**

- [ ] 4. Implementar Livewire `WizardMapeo`
  - Crear componente Livewire `app/Livewire/WizardMapeo.php` con 3 pasos: upload, mapeo, confirmación
  - Paso 1: recibe el archivo, llama a `ImportService::validarArchivo()` y `calcularHash()`, verifica duplicados en tabla `importaciones`
  - Paso 2: llama a `ImportService::leerEncabezados()`, renderiza selects dinámicos para cada campo de `centro_metricas`
  - Paso 3: llama a `ImportService::validarMapeo()` y `validarTiposPostMapeo()`, muestra errores o botón de confirmación
  - Al confirmar: guarda el archivo en `storage/app/private/uploads/`, crea registro en `importaciones` con estado `pendiente`, despacha `ImportacionExcelJob`
  - Registrar en `audit_logs` el evento `INICIO_IMPORTACION`
  - _Requisitos: 3.1, 3.2, 3.3, 3.4, 3.6, 4.1_

- [ ] 5. Implementar `ImportacionExcelJob`
- [ ] 5.1 Crear `app/Jobs/ImportacionExcelJob.php`
  - Implementar `ShouldQueue`, `WithChunkReading` (chunk size: 500)
  - Constructor recibe `$importacionId` y `$mapeo`
  - Leer archivo desde `storage/app/private/uploads/`
  - Por cada chunk: mapear columnas según `$mapeo`, filtrar filas con cédula inválida (< 7 dígitos, no numérica), ejecutar `CentroMetrica::upsert()` por cédula
  - Acumular contadores: `filas_procesadas`, `filas_insertadas`, `filas_actualizadas`, `filas_rechazadas`
  - Al completar: actualizar `importaciones` con estado `completado` y contadores, registrar en `audit_logs` con evento `IMPORTACION_COMPLETADA`
  - Configurar `$tries = 3` y `$backoff = [60, 300, 900]`
  - _Requisitos: 3.5, 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 5.2 Escribir property test — Propiedad 5: Chunks preservan total de registros válidos
  - **Feature: sistema-metricas-corporativas, Property 5: El procesamiento por chunks preserva el total de registros válidos**
  - Generar arrays de N filas con cédulas válidas (7-8 dígitos numéricos), ejecutar el proceso de importación
  - Verificar que `CentroMetrica::count()` después del Job == N
  - **Valida: Requisito 4.3**

- [ ] 5.3 Escribir property test — Propiedad 6: Upsert es idempotente
  - **Feature: sistema-metricas-corporativas, Property 6: El upsert es idempotente**
  - Generar conjunto de filas válidas, ejecutar upsert dos veces con los mismos datos
  - Verificar que el estado final de `centro_metricas` (count y valores) es idéntico al resultado de la primera ejecución
  - **Valida: Requisito 4.3**

- [ ] 6. Checkpoint — Verificar que todos los tests pasan hasta este punto
  - Ensure all tests pass, ask the user if questions arise.

- [-] 7. Implementar cruce de datos y `MetricsService`

- [x] 7.1 Crear `app/Services/MetricsService.php`


  - Método `totalPorPlanta(): Collection` — `CentroMetrica::selectRaw('planta, count(*) as total')->groupBy('planta')->get()`
  - Método `totalPorFilial(): Collection` — mismo patrón con `filial`
  - Método `inscritosVsNoInscritos(): array` — cuenta registros con `estatus_voto = 'Si'` y `!= 'Si'`
  - Método `distribucionPorEstado(): Collection` — agrupación por campo `estado`
  - Método `trazabilidadPorHora(): Collection` — query sobre `audit_logs` donde `evento = 'IMPORTACION_COMPLETADA'` agrupado por hora con `DATE_FORMAT(created_at, '%H:00')`
  - _Requisitos: 6.2, 6.3, 6.4, 6.5_

- [ ] 7.2 Implementar lógica de cruce en `ImportService`
  - Método `cruzarConVotos(UploadedFile $archivoVotos): CruceResult` — lee archivo de votos (columnas: cedula, estatus_voto), hace `LEFT JOIN` lógico en PHP o mediante query SQL, actualiza `estatus_voto` en `centro_metricas` con `upsert`, retorna conteos y lista de anomalías (cédulas en votos sin match en centro_metricas)
  - _Requisitos: 5.1, 5.2, 5.3, 5.4_

- [ ] 7.3 Escribir property test — Propiedad 7: Cruce clasifica todos los registros
  - **Feature: sistema-metricas-corporativas, Property 7: El cruce de datos clasifica todos los registros**
  - Generar conjuntos aleatorios de registros en `centro_metricas` y tabla de votos
  - Después del cruce, verificar que: (a) ningún registro con cédula en tabla_votos queda con `estatus_voto = NULL`, (b) ninguna cédula de tabla_votos sin match se inserta en `centro_metricas`
  - **Valida: Requisitos 5.2, 5.4**

- [x] 8. Implementar dashboard con Livewire



  - Crear componente `app/Livewire/DashboardMetricas.php`
  - Inyectar `MetricsService` en el constructor
  - Cargar las 5 métricas del diseño en el método `mount()`
  - Agregar directiva `wire:poll.30s` al componente para actualización automática
  - Crear vista `resources/views/livewire/dashboard-metricas.blade.php` con tarjetas para cada métrica
  - Registrar auditoría `ACCESO_DASHBOARD` al cargar el componente
  - _Requisitos: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [ ] 9. Implementar gestión de usuarios (Admin)
  - Crear `app/Http/Controllers/UsuarioController.php` con métodos: `index`, `create`, `store`, `edit`, `update`, `destroy`
  - En `store`: hashear contraseña con `bcrypt`, asignar rol validado (enum: admin/supervisor/operador), encolar `NotificacionCredenciales`
  - En `destroy`: setear `active = false`, llamar a `DB::table('sessions')->where('user_id', $id)->delete()` para invalidar sesiones
  - Registrar en `audit_logs` los eventos `CREACION_USUARIO`, `MODIFICACION_USUARIO`, `DESACTIVACION_USUARIO`
  - _Requisitos: 2.1, 2.2, 2.3, 2.4_

- [ ] 9.1 Escribir property test — Propiedad 9: Auditoría registra toda acción clasificada
  - **Feature: sistema-metricas-corporativas, Property 9: La auditoría registra toda acción clasificada sin pérdida**
  - Ejecutar acciones clasificadas aleatorias (crear usuario, iniciar importación, acceder dashboard)
  - Verificar que existe exactamente un registro en `audit_logs` por cada acción con los campos correctos
  - Verificar que intentar `DB::statement('DELETE FROM audit_logs WHERE id = ?', [$id])` lanza excepción o retorna 0 filas afectadas
  - **Valida: Requisitos 7.1, 7.2**

- [ ] 10. Implementar archivado de datos históricos
- [ ] 10.1 Crear `app/Services/ArchiveService.php`
  - Método `exportarAntiguos(int $dias = 365): string` — consulta `CentroMetrica::where('created_at', '<', now()->subDays($dias))->get()`, escribe a CSV en `storage/app/private/archivos/`, comprime a ZIP, retorna el path
  - Método `purgarRegistrosArchivados(string $archivoPath): int` — dentro de `DB::transaction()`: lee las cédulas del archivo, elimina los registros correspondientes de `centro_metricas`, retorna el conteo
  - _Requisitos: 8.1, 8.2, 8.3_

- [ ] 10.2 Crear `app/Jobs/ArchivadoDatosJob.php`
  - Llama a `ArchiveService::exportarAntiguos()`
  - Si exportación exitosa: llama a `purgarRegistrosArchivados()`
  - Si `exportarAntiguos()` lanza excepción: registra en `audit_logs` con evento `ARCHIVADO_FALLIDO`, no purga nada
  - Al completar exitosamente: registra en `audit_logs` con evento `ARCHIVADO_COMPLETADO` y detalles del archivo generado y conteo de registros
  - _Requisitos: 8.2, 8.3, 8.4_

- [ ] 10.3 Escribir property test — Propiedad 8: Archivado es atómico
  - **Feature: sistema-metricas-corporativas, Property 8: El archivado es atómico**
  - Generar datasets aleatorios con registros de distintas fechas (algunos > 365 días, otros recientes)
  - Escenario éxito: verificar que registros recientes permanecen en `centro_metricas` y los antiguos son eliminados
  - Escenario fallo: simular excepción en `exportarAntiguos()`, verificar que `centro_metricas` queda intacta
  - **Valida: Requisitos 8.2, 8.3**

- [ ] 11. Cablear rutas, vistas y controller de auditoría
  - Actualizar `routes/web.php` con todas las rutas nuevas protegidas por `role:admin`, `role:supervisor`, `role:operador`
  - Crear `app/Http/Controllers/AuditController.php` con método `index` — retorna vista paginada (50/página) de `audit_logs` ordenados por `created_at` DESC
  - Crear `app/Http/Controllers/ArchivadoController.php` que despacha `ArchivadoDatosJob`
  - Reemplazar los métodos del `MetricasController` que retornan HTML plano por redirecciones a vistas Blade/Livewire
  - _Requisitos: 7.3, 7.4, 6.1_

- [ ] 12. Configurar usuario MySQL de solo lectura/escritura para audit_logs
  - Crear script de migración que ejecuta `DB::statement()` para crear un usuario MySQL `metricas_audit` con permisos solo de `INSERT` y `SELECT` en la tabla `audit_logs`
  - Documentar en comentario del script los comandos SQL equivalentes para ejecución manual en producción
  - _Requisitos: 7.2_

- [ ] 13. Checkpoint Final — Verificar que todos los tests pasan
  - Ensure all tests pass, ask the user if questions arise.
