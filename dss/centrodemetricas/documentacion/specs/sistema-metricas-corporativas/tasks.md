# Plan de Implementación
## Sistema de Métricas Corporativas

- [x] 1. Preparar infraestructura base y modelos de datos
  - Instalar dependencia `maatwebsite/excel` para lectura de Excel
  - Crear migraciones para tablas `votos_personal`, `uploads`, `upload_errors`
  - Agregar columna `active` (BOOLEAN) a la tabla `users`
  - Crear modelos Eloquent `CentroMetrica` (apunta a `votos_personal`), `Importacion`
  - Actualizar modelo `User` con campo `active`
  - _Requisitos: 2.3, 3.3, 4.3, 5.1_

- [ ] 2. Implementar middlewares de seguridad

  - [ ] 2.1 Refactorizar middleware `CheckRole`
    - Registrar en `audit_logs` intentos de acceso no autorizado
    - Rechazar usuarios con `active = false` con HTTP 403
    - _Requisitos: 1.2, 1.3, 1.4, 2.3_

  - [ ] 2.2 Crear middleware `SessionTimeout`
    - Timeout de 900 segundos de inactividad
    - Redirigir al login con mensaje flash
    - _Requisito: 1.6_

- [ ] 3. Implementar `ImportService`

  - [ ] 3.1 Validación, hash y lectura de encabezados
    - `validarArchivo()`, `calcularHash()`, `leerEncabezados()`
    - _Requisitos: 3.1, 3.2, 3.3_

  - [ ] 3.2 Validación de mapeo y tipos
    - `validarMapeo()` — cédula obligatoria
    - `validarTiposPostMapeo()` — compatibilidad de tipos
    - _Requisitos: 3.4, 3.6_

- [ ] 4. Implementar Livewire `WizardMapeo`
  - 3 pasos: upload → mapeo → confirmación/despacho
  - Registrar `INICIO_IMPORTACION` en audit_logs
  - _Requisitos: 3.1–3.4, 3.6, 4.1_

- [ ] 5. Implementar `ImportacionExcelJob`
  - Chunks de 500 filas, upsert por cédula
  - tries=3, backoff=[60, 300, 900]s
  - Registrar `IMPORTACION_COMPLETADA` en audit_logs
  - _Requisitos: 4.1–4.5_

- [x] 6. Implementar `MetricsService` y Dashboard
  - `MetricsService` con todos los métodos de métricas
  - Componente Livewire `DashboardMetricas` con `wire:poll.30s`
  - Vistas para admin, supervisor y operador
  - _Requisitos: 6.1–6.6_

- [ ] 7. Implementar cruce de datos
  - `ImportService::cruzarConVotos()` — LEFT JOIN por cédula
  - Actualiza `estado_voto` en `votos_personal`
  - Reporte de anomalías (cédulas sin match)
  - _Requisitos: 5.1–5.4_

- [ ] 8. Implementar gestión de usuarios (Admin)
  - `UsuarioController` — CRUD completo
  - Encolar `NotificacionCredenciales` al crear usuario
  - Desactivar usuario: `active=false` + invalidar sesiones
  - Registrar eventos en audit_logs
  - _Requisitos: 2.1–2.4_

- [ ] 9. Implementar archivado de datos históricos
  - `ArchiveService::exportarAntiguos()` — CSV comprimido en ZIP
  - `ArchiveService::purgarRegistrosArchivados()` — dentro de transacción DB
  - `ArchivadoDatosJob` — orquesta exportación y purga
  - _Requisitos: 8.1–8.4_

- [ ] 10. Cablear rutas, auditoría y vistas restantes
  - Rutas protegidas por rol en `routes/web.php`
  - `AuditController` — lista paginada (50/página) de audit_logs
  - `ArchivadoController` — despacha `ArchivadoDatosJob`
  - _Requisitos: 7.3, 7.4, 6.1_

- [ ] 11. Configurar usuario MySQL de solo lectura para audit_logs
  - Usuario `metricas_audit` con permisos solo INSERT y SELECT
  - _Requisito: 7.2_

- [ ] 12. Checkpoint Final — Verificar que todos los tests pasan
