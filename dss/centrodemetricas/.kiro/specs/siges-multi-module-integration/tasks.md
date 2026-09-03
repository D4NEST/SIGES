# Implementation Plan: SIGES Multi-Module Integration

## Overview

This implementation plan covers the integration of three SIGES modules (Meta Modelador, DSS, Inventario) following the requirements-first workflow. The project is organized into 5 phases with 32 requirements total.

**Technology Stack:**
- Meta Modelador: Flask 2.x + SQLAlchemy + PostgreSQL
- DSS: Laravel 11.x + MySQL 8.x + Redis
- Inventario: Flask 2.x + psycopg2 + PostgreSQL

**Module Paths:**
- DSS (Laravel): C:\Users\Yamileth\Documents\SIGES\dss\centrodemetricas
- Inventario (Flask): C:\Users\Yamileth\Documents\SIGES\inventario
- Meta Modelador (Flask): C:\Users\Yamileth\Documents\SIGES\Metamodelador

---

## Tasks

- [ ] 1. Phase 0: API Key Security Infrastructure
  - [ ] 1.1 Create API Key model and database migration in Meta Modelador
    - Create `models/api_key.py` with APIKey SQLAlchemy model
    - Create migration for `api_keys` table with fields: id, module_name, key_hash, previous_key_hash, rotation_grace_until, activo, created_at, last_used, created_by
    - Add relationship to Usuario model for created_by foreign key
    - _Requirements: 1.1, 1.3, 1.4_

  - [ ] 1.2 Implement API Key authentication middleware in Meta Modelador
    - Create `app/middleware/api_key_auth.py` with APIKeyAuth class
    - Implement `generate_key()` method using secrets module for 32-char keys
    - Implement `hash_key()` and `verify_key()` methods using bcrypt
    - Implement `middleware()` method for Flask request interception
    - Define EXEMPT_ROUTES for health and auth endpoints
    - Add module identity to Flask g context on successful auth
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ] 1.3 Implement API Key management endpoints in Meta Modelador
    - Create `app/routes/api_key_routes.py` with admin endpoints
    - Implement POST `/api/v1/api-keys` for key generation
    - Implement POST `/api/v1/api-keys/{id}/rotate` for key rotation
    - Implement GET `/api/v1/api-keys` for listing keys (hash only, no plaintext)
    - Add grace period logic for rotation (24-hour default)
    - _Requirements: 1.1, 3.1, 3.2, 3.3_

  - [ ] 1.4 Create audit logging infrastructure in Meta Modelador
    - Create `models/audit_log.py` with AuditLog model
    - Create migration for `audit_logs` table
    - Implement logging for all authentication attempts (success/failure)
    - Add indexes on event_type and created_at for query performance
    - _Requirements: 2.4_

  - [ ] 1.5 Configure API Key storage in DSS
    - Create `config/services.php` entries for Meta Modelador connection
    - Add environment variables: META_MODELADOR_URL, META_MODELADOR_API_KEY, META_MODELADOR_TIMEOUT, META_MODELADOR_RETRIES
    - Create database migration for `module_configuration` table
    - Implement encrypted storage for API key using Laravel's encryption
    - _Requirements: 4.1, 4.4_

  - [ ] 1.6 Implement MetaModeladorApiClient in DSS
    - Create `app/Services/MetaModeladorApiClient.php`
    - Implement constructor with config loading and validation
    - Implement `request()` method with retry logic (3 attempts, exponential backoff)
    - Implement methods: `getRubros()`, `getEntidades()`, `getCampos()`, `getFullConfiguration()`
    - Add X-API-Key header to all requests
    - Create custom exceptions: `MetaModeladorAuthException`, `MetaModeladorConnectionException`
    - _Requirements: 4.2, 4.3, 13.2, 13.3_

  - [ ] 1.7 Configure API Key storage in Inventario
    - Create `config.py` entries for Meta Modelador connection
    - Add environment variables to `.env.example`: META_MODELADOR_URL, META_MODELADOR_API_KEY, META_MODELADOR_TIMEOUT, META_MODELADOR_RETRIES
    - Implement encrypted storage using cryptography library (Fernet)
    - _Requirements: 5.1, 5.4_

  - [ ] 1.8 Implement MetaModeladorClient in Inventario
    - Create `services/meta_modelador_client.py`
    - Implement `__init__()` with config loading and validation
    - Implement `_request()` method with retry logic and exponential backoff
    - Implement methods: `get_rubros()`, `get_rubro()`, `get_entidades()`, `get_campos()`, `get_full_configuration()`
    - Add X-API-Key header to all requests
    - Create custom exceptions: `MetaModeladorAuthError`, `MetaModeladorConnectionError`
    - _Requirements: 5.2, 5.3, 23.2, 23.3_

  - [ ]* 1.9 Write unit tests for API Key authentication
    - Test key generation produces 32-char alphanumeric keys
    - Test bcrypt hashing and verification
    - Test middleware rejects missing/invalid keys with HTTP 401
    - Test grace period acceptance of both old and new keys
    - Test API key is never exposed in logs
    - _Requirements: 1.1, 1.4, 2.1, 2.2, 3.2_

  - [ ]* 1.10 Write integration tests for inter-module communication
    - Test DSS client successfully authenticates with valid API key
    - Test Inventario client successfully authenticates with valid API key
    - Test both clients handle connection failures gracefully
    - Test retry logic with exponential backoff
    - _Requirements: 4.3, 5.3, 13.2, 23.2_

- [ ] 2. Checkpoint - Phase 0 complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify API key authentication works across all three modules

- [ ] 3. Phase 1: Configuration Wizard
  - [ ] 3.1 Create database connection configuration UI in Meta Modelador
    - Create `app/routes/wizard_routes.py` with configuration endpoints
    - Create `templates/wizard/connection_form.html` with connection form
    - Implement connection parameter validation (host, port, database, username, password)
    - Add support for MySQL and PostgreSQL connection strings
    - _Requirements: 6.1, 6.2_

  - [ ] 3.2 Implement connection testing and validation in Meta Modelador
    - Create `services/connection_tester.py` with test connection logic
    - Implement MySQL connection testing with SQLAlchemy
    - Implement PostgreSQL connection testing with psycopg2
    - Return descriptive error messages for connection failures
    - Store validated connection parameters in encrypted configuration
    - _Requirements: 6.2, 6.3, 6.4_

  - [ ] 3.3 Implement SSH tunnel support for remote databases in Meta Modelador
    - Create `services/ssh_tunnel.py` with SSHTunnelManager class
    - Implement `connect()` method using sshtunnel library
    - Support both password and key-based authentication
    - Implement context manager for automatic tunnel cleanup
    - _Requirements: 6.1_

  - [ ] 3.4 Implement MySQL schema scanner in Meta Modelador
    - Create `services/schema_scanner.py` with SchemaScanner class
    - Implement `scan_tables()` for MySQL databases
    - Filter out system databases: mysql, information_schema, performance_schema, sys
    - Implement `_get_mysql_columns()` for column metadata extraction
    - Implement `_get_mysql_foreign_keys()` for relationship discovery
    - _Requirements: 7.1, 7.2, 7.3_

  - [ ] 3.5 Implement PostgreSQL schema scanner in Meta Modelador
    - Add PostgreSQL support to `services/schema_scanner.py`
    - Filter out system schemas: pg_catalog, information_schema
    - Implement `_get_pg_tables()` using SQLAlchemy inspector
    - Handle PostgreSQL-specific data types
    - _Requirements: 8.1, 8.2, 8.3_

  - [ ] 3.6 Implement schema inference engine in Meta Modelador
    - Add `infer_rubro_from_tables()` method to SchemaScanner
    - Implement `_infer_entity()` to suggest entity definitions from tables
    - Implement `_infer_campo()` to map columns to campo definitions
    - Implement `_map_data_type()` for database type to campo type conversion
    - Implement `_to_singular()` for table name to entity name conversion
    - _Requirements: 7.3, 8.3, 10.3_

  - [ ] 3.7 Create Rubro model and management endpoints in Meta Modelador
    - Create `models/rubro.py` with Rubro SQLAlchemy model
    - Create migration for `rubros` table
    - Create `app/routes/rubro_routes.py` with CRUD endpoints
    - Implement name validation (alphanumeric, underscores, hyphens only)
    - Implement uniqueness constraint and validation
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [ ] 3.8 Create Entidad model and management endpoints in Meta Modelador
    - Create `models/entidad.py` with Entidad SQLAlchemy model
    - Create migration for `entidades` table with rubro_id foreign key
    - Create `app/routes/entidad_routes.py` with CRUD endpoints
    - Implement uniqueness constraint within rubro context
    - Support automatic campo creation from source table selection
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [ ] 3.9 Create Campo model and management endpoints in Meta Modelador
    - Create `models/campo.py` with Campo SQLAlchemy model
    - Create migration for `campos` table with entidad_id foreign key
    - Create `app/routes/campo_routes.py` with CRUD endpoints
    - Support data types: string, integer, float, boolean, date, datetime, select, reference
    - Implement validation for required fields and display names
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

  - [ ] 3.10 Implement YAML configuration generator in Meta Modelador
    - Create `services/yaml_generator.py` with YAMLConfigGenerator class
    - Implement `generate_rubro_config()` for complete YAML export
    - Implement `parse_yaml_config()` for YAML import and validation
    - Support all campo properties and nested entity structures
    - _Requirements: 12.1, 12.2_

  - [ ] 3.11 Create configuration preview and validation in Meta Modelador
    - Add preview endpoint to wizard routes
    - Implement cross-reference validation for entidades and campos
    - Implement error highlighting with suggested fixes
    - Implement atomic configuration storage on apply
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

  - [ ] 3.12 Create database migrations for schema metadata storage in Meta Modelador
    - Create migration for `schema_metadata` table
    - Store scanned table information for future reference
    - Track last scan timestamp per module
    - _Requirements: 7.4, 8.4_

  - [ ]* 3.13 Write unit tests for schema scanner
    - Test MySQL system database exclusion
    - Test PostgreSQL system schema exclusion
    - Test column metadata extraction accuracy
    - Test foreign key relationship discovery
    - Test entity inference from table structure
    - _Requirements: 7.1, 7.2, 8.1, 8.2, 10.3_

  - [ ]* 3.14 Write unit tests for configuration validation
    - Test rubro name validation patterns
    - Test entity uniqueness within rubro
    - Test campo required field validation
    - Test YAML generation and parsing
    - _Requirements: 9.2, 10.1, 10.5, 12.2_

- [ ] 4. Checkpoint - Phase 1 complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify configuration wizard can scan databases and create rubro definitions

- [ ] 5. Phase 2: Laravel-Meta Modelador Bridge
  - [ ] 5.1 Implement MetaModeladorService with caching in DSS
    - Create `app/Services/MetaModeladorService.php`
    - Implement cached() method for cache-first data retrieval
    - Implement methods: `getRubros()`, `getRubro()`, `getEntidades()`, `getCampos()`, `getFullConfiguration()`, `getRubroConfiguration()`
    - Define cache key patterns: `siges:rubros:all`, `siges:rubro:{id}`, etc.
    - Configure TTL from environment (default 3600s)
    - _Requirements: 14.1, 14.2, 14.3_

  - [ ] 5.2 Implement cache invalidation methods in DSS
    - Implement `invalidateRubroCache()` for specific rubro
    - Implement `invalidateAllCache()` using cache tags
    - Store stale cache for graceful degradation
    - _Requirements: 14.5, 17.1_

  - [ ] 5.3 Implement webhook handler in DSS
    - Create `app/Http/Controllers/WebhookController.php`
    - Implement POST `/api/webhooks/meta-modelador` endpoint
    - Implement signature verification using HMAC-SHA256
    - Call cache invalidation on valid webhook
    - Log invalid webhook attempts as security events
    - _Requirements: 15.2, 15.3, 15.4, 15.5_

  - [ ] 5.4 Implement health monitoring in DSS
    - Add `isHealthy()` method to MetaModeladorService
    - Implement GET `/api/health` check to Meta Modelador
    - Track consecutive failures with threshold alerting (3 failures)
    - Store health status in service static property
    - _Requirements: 17.3, 17.4_

  - [ ] 5.5 Implement graceful degradation handler in DSS
    - Create `app/Services/GracefulDegradationHandler.php`
    - Implement stale cache fallback when Meta Modelador unavailable
    - Return maintenance page (HTTP 503) when no cache available
    - Add retry-after header for client guidance
    - _Requirements: 17.1, 17.2_

  - [ ] 5.6 Create health check logs table in DSS
    - Create migration for `health_check_logs` table
    - Log service, status, response_time_ms, error_message, checked_at
    - Add indexes for service and timestamp queries
    - _Requirements: 17.3, 17.5_

  - [ ] 5.7 Implement webhook notifier in Meta Modelador
    - Create `services/webhook_notifier.py` with WebhookNotifier class
    - Implement signature generation using HMAC-SHA256
    - Send POST to configured DSS webhook URL on configuration changes
    - Include rubro_id and change details in payload
    - _Requirements: 15.1, 15.2_

  - [ ] 5.8 Create configuration API endpoints in Meta Modelador
    - Implement GET `/api/v1/rubros` returning all rubros
    - Implement GET `/api/v1/rubros/{rubro_id}/entidades` returning entidades
    - Implement GET `/api/v1/entidades/{entidad_id}/campos` returning campos
    - Implement GET `/api/v1/configuration/full` for complete config
    - Apply API key middleware to all endpoints
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_

  - [ ] 5.9 Create dashboard config cache table in DSS
    - Create migration for `dashboard_config_cache` table
    - Store rubro_id, config_json, cached_at, expires_at
    - Add indexes for rubro_id and expires_at lookups
    - _Requirements: 14.1, 14.2_

  - [ ]* 5.10 Write unit tests for bridge services
    - Test cache hit/miss scenarios
    - Test webhook signature verification
    - Test graceful degradation with stale cache
    - Test health monitoring failure threshold
    - _Requirements: 14.1, 14.2, 15.3, 17.1_

  - [ ]* 5.11 Write integration tests for bridge communication
    - Test full configuration retrieval from Meta Modelador
    - Test webhook-triggered cache invalidation
    - Test retry logic with network failures
    - Test API key authentication on all endpoints
    - _Requirements: 13.2, 13.3, 15.5, 16.5_

- [ ] 6. Checkpoint - Phase 2 complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify DSS can fetch and cache configuration from Meta Modelador

- [ ] 7. Phase 3: Dynamic Dashboard
  - [ ] 7.1 Implement DashboardController with rubro routing in DSS
    - Create `app/Http/Controllers/DashboardController.php`
    - Implement GET `/dashboard/{rubro}` route
    - Verify rubro exists in Meta Modelador configuration
    - Return HTTP 404 with available rubros if not found
    - Apply role-based access control middleware
    - _Requirements: 18.1, 18.2, 18.3, 18.4_

  - [ ] 7.2 Implement DynamicMetricCalculator in DSS
    - Create `app/Services/DynamicMetricCalculator.php`
    - Implement `calculateMetrics()` for rubro-wide metrics
    - Implement `generateEntityMetrics()` for entity-specific metrics
    - Implement `calculateNumericMetrics()` for sum, avg, min, max
    - Implement `calculateCategoricalDistribution()` for group by counts
    - Implement `calculateTimeSeries()` for date-based trends
    - _Requirements: 20.1, 20.2, 20.3_

  - [ ] 7.3 Implement metric formatting and caching in DSS
    - Add `formatMetric()` method for currency, percentage, number formatting
    - Cache computed metrics for 5 minutes (configurable)
    - Implement error handling with error indicator display
    - _Requirements: 20.3, 20.4, 20.5_

  - [ ] 7.4 Implement custom query execution in DSS
    - Add `executeCustomQuery()` method to DynamicMetricCalculator
    - Validate only SELECT queries are allowed
    - Sanitize against dangerous keywords (DROP, DELETE, UPDATE, etc.)
    - Return structured success/error response
    - _Requirements: 20.2_

  - [ ] 7.5 Implement ChartGeneratorService in DSS
    - Create `app/Services/ChartGeneratorService.php`
    - Implement `generateChartConfig()` with auto chart type detection
    - Implement `generateLineChart()` for time series data
    - Implement `generateBarChart()` for categorical distributions
    - Implement `generatePieChart()` and `generateDonutChart()` for small datasets
    - Generate Chart.js compatible JSON configuration
    - _Requirements: 21.1, 21.2, 21.3, 21.4_

  - [ ] 7.6 Create DynamicDashboard Livewire component in DSS
    - Create `app/Livewire/DynamicDashboard.php`
    - Implement `mount()` with rubro configuration loading
    - Implement `loadMetrics()` for metric calculation
    - Implement `updatedSelectedEntity()` for entity switching
    - Implement `applyFilters()` for metric recalculation
    - _Requirements: 18.3, 18.5, 19.1_

  - [ ] 7.7 Implement Entity List Renderer in DSS
    - Add entity listing functionality to DynamicDashboard
    - Apply campo definitions for column display names and formatting
    - Implement pagination with configurable page size (default 25)
    - Add search input for campos marked as searchable
    - Add filter dropdown for campos marked as filterable
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5_

  - [ ] 7.8 Create dashboard blade views in DSS
    - Create `resources/views/livewire/dynamic-dashboard.blade.php`
    - Implement KPI card components for metrics
    - Implement Chart.js canvas rendering with dynamic config
    - Implement entity selector dropdown
    - Implement filter sidebar component
    - _Requirements: 18.3, 21.4_

  - [ ] 7.9 Implement export functionality in DSS
    - Add `exportPdf()` method to DynamicDashboard
    - Add `exportExcel()` method to DynamicDashboard
    - Include metrics, charts (as images), and data tables
    - Apply current filters to exported data
    - Add header with rubro name, export date, user name
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5_

  - [ ]* 7.10 Write unit tests for metric calculator
    - Test numeric metric calculations (sum, avg, min, max)
    - Test categorical distribution queries
    - Test time series generation
    - Test metric formatting for different types
    - Test SQL injection prevention in custom queries
    - _Requirements: 20.1, 20.2, 20.3, 20.5_

  - [ ]* 7.11 Write unit tests for chart generator
    - Test chart type auto-detection
    - Test line chart configuration generation
    - Test bar chart configuration generation
    - Test pie/donut chart configuration generation
    - Test color palette generation
    - _Requirements: 21.1, 21.2, 21.3_

  - [ ]* 7.12 Write integration tests for dashboard
    - Test dashboard loads with valid rubro
    - Test HTTP 404 for invalid rubro
    - Test entity switching updates metrics
    - Test filter application
    - Test export functionality
    - _Requirements: 18.1, 18.2, 19.1, 22.1_

- [ ] 8. Checkpoint - Phase 3 complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify dynamic dashboard renders with rubro-specific metrics and charts

- [ ] 9. Phase 4: Inventario Integration
  - [ ] 9.1 Implement DynamicSchemaValidator in Inventario
    - Create `services/schema_validator.py` with DynamicSchemaValidator class
    - Implement `validate()` method for data validation against campos
    - Implement `_validate_type()` for type conversion and validation
    - Implement `_validate_custom()` for custom validation rules
    - Support all campo types: string, integer, float, boolean, date, datetime, email, currency, select
    - _Requirements: 24.3, 24.5, 25.4_

  - [ ] 9.2 Implement Product Schema Loader in Inventario
    - Create `services/product_schema_loader.py`
    - Implement dynamic form field rendering based on campo definitions
    - Map campo types to HTML inputs: text, number, date, dropdown
    - Populate reference dropdowns from related entities
    - Enforce required field validation on form submission
    - _Requirements: 24.1, 24.2, 24.3, 24.4_

  - [ ] 9.3 Implement Inventory Schema Loader in Inventario
    - Create `services/inventory_schema_loader.py`
    - Extend standard inventory fields with dynamic campos
    - Support batch numbers, expiration dates, warehouse zones
    - Implement filtering and searching on dynamic campos
    - _Requirements: 25.1, 25.2, 25.3, 25.5_

  - [ ] 9.4 Implement EventPublisher in Inventario
    - Create `services/event_publisher.py` with EventPublisher class
    - Implement `publish()` method for Redis Streams
    - Implement `publish_inventory_update()` for product changes
    - Implement `publish_stock_change()` for quantity changes
    - Include rubro_id, entidad, and changed_fields in payload
    - _Requirements: 26.1, 26.3_

  - [ ] 9.5 Implement Event Consumer Service in DSS
    - Create `app/Services/EventConsumerService.php`
    - Implement Redis Stream consumer group setup
    - Implement `consume()` method for event processing
    - Implement `handleInventoryUpdate()` for cache invalidation
    - Implement `handleStockChange()` for metric updates
    - Implement `acknowledge()` for event confirmation
    - _Requirements: 26.2, 26.5_

  - [ ] 9.6 Implement sync event log table in Inventario
    - Create migration for `sync_event_log` table
    - Store event_type, rubro_id, entidad, event_data, processed, processed_at
    - Add indexes for processed and created_at queries
    - _Requirements: 26.5_

  - [ ] 9.7 Create shared JWT authentication handler
    - Create `shared/auth/jwt_handler.py` with JWTHandler class
    - Implement `generate_token()` with standard claims (sub, email, role, rubros)
    - Implement `validate_token()` with signature and expiration check
    - Implement `get_current_user()` for request context extraction
    - Implement `@jwt_required` decorator for Flask routes
    - Implement `@role_required` decorator for authorization
    - _Requirements: 27.1, 27.2, 27.3_

  - [ ] 9.8 Implement JWT authentication in DSS
    - Create `app/Services/JwtService.php`
    - Implement token validation using shared secret
    - Implement middleware for JWT verification on protected routes
    - Handle token expiration with redirect to login
    - Implement token blacklist for logout
    - _Requirements: 27.2, 27.4, 27.5_

  - [ ] 9.9 Implement JWT authentication in Inventario
    - Integrate `shared/auth/jwt_handler.py` into Flask app
    - Apply `@jwt_required` decorator to protected routes
    - Validate tokens from DSS sessions for SSO
    - Handle token expiration with redirect to login
    - _Requirements: 27.2, 27.4, 27.5_

  - [ ] 9.10 Create module config tables for encrypted settings
    - Create migration in Inventario for `module_config` table
    - Create migration in Inventario for `config_cache` table
    - Implement encrypted storage using Fernet symmetric encryption
    - _Requirements: 5.1, 23.1_

  - [ ]* 9.11 Write unit tests for schema validator
    - Test required field validation
    - Test type conversion for all campo types
    - Test custom validation rules (min/max, pattern, length)
    - Test select field option validation
    - _Requirements: 24.3, 24.5, 25.4_

  - [ ]* 9.12 Write unit tests for event synchronization
    - Test event publication to Redis Streams
    - Test event payload structure validation
    - Test consumer group event processing
    - Test event acknowledgment
    - _Requirements: 26.1, 26.2, 26.3_

  - [ ]* 9.13 Write integration tests for JWT SSO
    - Test token generation with correct claims
    - Test token validation across modules
    - Test token expiration handling
    - Test role-based authorization
    - _Requirements: 27.1, 27.2, 27.3, 27.4, 27.5_

- [ ] 10. Checkpoint - Phase 4 complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify cross-module synchronization and SSO work correctly

- [ ] 11. Phase 5: Non-Functional Requirements
  - [ ] 11.1 Implement rate limiting on API endpoints
    - Add rate limiting middleware to Meta Modelador (100 req/min per module)
    - Add rate limiting middleware to DSS API endpoints
    - Add rate limiting to Inventario API endpoints
    - Return HTTP 429 with retry-after header on limit exceeded
    - _Requirements: 29.4_

  - [ ] 11.2 Implement structured JSON logging
    - Configure structlog in Meta Modelador Flask app
    - Configure structured logging in DSS Laravel app
    - Configure structlog in Inventario Flask app
    - Include correlation IDs in all log entries
    - _Requirements: 31.2_

  - [ ] 11.3 Create OpenAPI documentation for Meta Modelador
    - Add Flask-RESTX or Flasgger for OpenAPI generation
    - Document all API endpoints with request/response schemas
    - Document authentication requirements
    - Expose `/api/docs` endpoint
    - _Requirements: 31.1_

  - [ ] 11.4 Create troubleshooting documentation
    - Document common error scenarios and resolutions
    - Document configuration schema for wizard validation
    - Create runbook for Meta Modelador unavailability
    - Document cache invalidation procedures
    - _Requirements: 31.3, 31.4, 31.5_

  - [ ] 11.5 Implement performance monitoring
    - Add response time logging to all API endpoints
    - Track cache hit ratios in DSS
    - Track dashboard render times
    - Set up alerting for performance degradation
    - _Requirements: 28.1, 28.2, 28.3, 28.4, 28.5_

  - [ ] 11.6 Implement health check endpoints
    - Ensure `/api/health` exists in Meta Modelador
    - Ensure `/api/health` exists in DSS
    - Ensure `/api/health` exists in Inventario
    - Include database connectivity check
    - Include Redis connectivity check
    - _Requirements: 30.3, 30.4_

  - [ ]* 11.7 Write performance tests
    - Test Meta Modelador API response under load (< 200ms)
    - Test DSS dashboard render time (< 2s)
    - Test chart generation time (< 500ms after data retrieval)
    - Test concurrent user handling (100+ users)
    - _Requirements: 28.1, 28.3, 28.4, 28.5_

  - [ ]* 11.8 Write security tests
    - Test TLS 1.2+ enforcement on all endpoints
    - Test API key encryption at rest
    - Test rate limiting effectiveness
    - Test input validation on all endpoints
    - _Requirements: 29.1, 29.2, 29.4, 29.5_

- [ ] 12. Final Checkpoint - All phases complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify complete SIGES integration with all modules communicating

---

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property-based tests validate correctness properties defined in design
- Unit tests validate specific examples and edge cases

**Module Ownership:**
- Meta Modelador tasks: Work in `C:\Users\Yamileth\Documents\SIGES\Metamodelador`
- DSS tasks: Work in `C:\Users\Yamileth\Documents\SIGES\dss\centrodemetricas`
- Inventario tasks: Work in `C:\Users\Yamileth\Documents\SIGES\inventario`
- Shared tasks: Create `C:\Users\Yamileth\Documents\SIGES\shared` directory

**Estimated Effort:**
- Phase 0: 40-60 hours (security infrastructure)
- Phase 1: 60-80 hours (configuration wizard)
- Phase 2: 40-50 hours (bridge implementation)
- Phase 3: 60-80 hours (dynamic dashboard)
- Phase 4: 50-70 hours (inventario integration)
- Phase 5: 20-30 hours (non-functional requirements)

**Total Estimated Effort:** 270-370 hours

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.4", "3.7", "3.12", "9.10"] },
    { "id": 1, "tasks": ["1.2", "1.5", "1.7", "3.1", "5.6", "5.9", "9.6"] },
    { "id": 2, "tasks": ["1.3", "1.6", "1.8", "3.2", "3.8", "5.1", "9.1", "9.7"] },
    { "id": 3, "tasks": ["3.3", "3.4", "3.5", "3.9", "5.2", "5.7", "5.8", "9.2", "9.3", "9.8"] },
    { "id": 4, "tasks": ["3.6", "3.10", "3.11", "5.3", "5.4", "5.5", "9.4", "9.5", "9.9"] },
    { "id": 5, "tasks": ["7.1", "7.2", "7.5", "11.1", "11.2", "11.3", "11.6"] },
    { "id": 6, "tasks": ["7.3", "7.4", "7.6", "11.5"] },
    { "id": 7, "tasks": ["7.7", "7.8", "7.9"] },
    { "id": 8, "tasks": ["1.9", "1.10", "3.13", "3.14", "5.10", "5.11", "7.10", "7.11", "7.12", "9.11", "9.12", "9.13", "11.7", "11.8"] },
    { "id": 9, "tasks": ["11.4"] }
  ]
}
```

**Wave Explanation:**
- **Wave 0**: Database models and migrations (can run in parallel across modules)
- **Wave 1**: Configuration setup and additional migrations
- **Wave 2**: API clients and core services
- **Wave 3**: Schema scanning and specialized services
- **Wave 4**: Integration services (webhooks, validators, event handlers)
- **Wave 5**: Dashboard infrastructure and documentation
- **Wave 6**: Dashboard features and monitoring
- **Wave 7**: Dashboard UI components and export
- **Wave 8**: All testing tasks (run after implementation complete)
- **Wave 9**: Documentation (final deliverable)
