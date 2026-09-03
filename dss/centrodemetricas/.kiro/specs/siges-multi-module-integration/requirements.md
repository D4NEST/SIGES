# Requirements Document

## Introduction

SIGES (Sistema Integrado de Gestión) is a multi-module system that unifies three independent applications under a centralized data definition architecture. Meta Modelador serves as the central brain, defining business rubros (categories), entidades (entities), and campos (fields). The DSS and Inventario modules consume this structure to dynamically adapt to any business rubro without code changes.

This integration spans 5 phases: API Key security, configuration wizard, Laravel-Meta Modelador bridge, dynamic dashboards, and Inventario integration.

## Glossary

- **SIGES**: Sistema Integrado de Gestión - The unified multi-module system
- **Meta Modelador**: Flask + PostgreSQL application serving as the central data definition API
- **DSS**: Decision Support System - Laravel + MySQL application for dashboards and metrics
- **Inventario**: Flask + PostgreSQL application for product and inventory management
- **Rubro**: Business category or sector (e.g., retail, manufacturing, healthcare)
- **Entidad**: Entity definition within a rubro (e.g., Product, Customer, Order)
- **Campo**: Field definition within an entity (e.g., name, price, quantity)
- **API Key**: Secure authentication token for inter-module communication
- **Schema Scanner**: Component that discovers database tables and columns
- **Configuration Wizard**: Guided setup process for defining rubros and entidades
- **Bridge**: Communication layer between modules with caching capabilities

---

## Requirements

## Phase 0: API Key Security

### Requirement 1: API Key Generation

**User Story:** As a system administrator, I want to generate secure API keys for each module, so that inter-module communication is authenticated and authorized.

#### Acceptance Criteria

1. WHEN an administrator requests a new API key, THE Meta_Modelador SHALL generate a cryptographically secure random key of at least 32 characters
2. THE Meta_Modelador SHALL store API keys with an association to the requesting module name
3. WHEN an API key is generated, THE Meta_Modelador SHALL return the key exactly once and SHALL NOT store the plaintext key after initial display
4. THE Meta_Modelador SHALL store a hashed version of each API key using bcrypt or argon2

### Requirement 2: API Key Validation

**User Story:** As a Meta Modelador developer, I want to validate incoming API requests, so that only authorized modules can access the API.

#### Acceptance Criteria

1. WHEN a request arrives at Meta_Modelador API, THE API_Gateway SHALL validate the API key in the `X-API-Key` header
2. IF the API key is missing or invalid, THEN THE API_Gateway SHALL return HTTP 401 Unauthorized with an error message
3. WHEN a valid API key is provided, THE API_Gateway SHALL identify the requesting module and attach module identity to the request context
4. THE API_Gateway SHALL log all authentication attempts with timestamp, module name, and success status

### Requirement 3: API Key Rotation

**User Story:** As a security officer, I want to rotate API keys periodically, so that compromised keys have limited exposure windows.

#### Acceptance Criteria

1. WHEN an administrator initiates key rotation, THE Meta_Modelador SHALL generate a new API key for the specified module
2. WHILE a key rotation is in progress, THE Meta_Modelador SHALL accept both the old and new API keys for a configurable grace period (default 24 hours)
3. WHEN the grace period expires, THE Meta_Modelador SHALL invalidate the old API key permanently
4. THE Meta_Modelador SHALL notify the affected module of the key rotation via a configured webhook

### Requirement 4: DSS API Key Configuration

**User Story:** As a DSS administrator, I want to configure the Meta Modelador API key in DSS, so that DSS can authenticate requests to Meta Modelador.

#### Acceptance Criteria

1. WHEN an administrator stores an API key in DSS, THE DSS_Configuration_Manager SHALL store the key in an encrypted environment variable
2. THE DSS_Configuration_Manager SHALL NOT log or expose the API key in any user-facing interface
3. WHEN DSS makes a request to Meta_Modelador, THE DSS_API_Client SHALL include the API key in the `X-API-Key` header
4. IF the API key is not configured, THE DSS_API_Client SHALL refuse to make requests and log an error

### Requirement 5: Inventario API Key Configuration

**User Story:** As an Inventario administrator, I want to configure the Meta Modelador API key in Inventario, so that Inventario can authenticate requests to Meta Modelador.

#### Acceptance Criteria

1. WHEN an administrator stores an API key in Inventario, THE Inventario_Configuration_Manager SHALL store the key in an encrypted environment variable
2. THE Inventario_Configuration_Manager SHALL NOT log or expose the API key in any user-facing interface
3. WHEN Inventario makes a request to Meta_Modelador, THE Inventario_API_Client SHALL include the API key in the `X-API-Key` header
4. IF the API key is not configured, THE Inventario_API_Client SHALL refuse to make requests and log an error

---

## Phase 1: Configuration Wizard

### Requirement 6: Database Connection Configuration

**User Story:** As a system administrator, I want to configure database connections for each module, so that the wizard can scan schemas across different databases.

#### Acceptance Criteria

1. WHEN an administrator accesses the configuration wizard, THE Wizard_UI SHALL display a form to enter database connection parameters (host, port, database name, username, password)
2. WHEN the administrator submits connection parameters, THE Wizard_Validator SHALL attempt a test connection before saving
3. IF the test connection fails, THE Wizard_Validator SHALL display a descriptive error message and allow the administrator to correct the parameters
4. WHEN the test connection succeeds, THE Wizard_Config_Manager SHALL save the connection parameters in an encrypted configuration store

### Requirement 7: Intelligent Schema Scanning - DSS

**User Story:** As a system administrator, I want the wizard to automatically discover tables in the DSS MySQL database, so that I can select which tables to include in the configuration.

#### Acceptance Criteria

1. WHEN the administrator initiates schema scanning for DSS, THE Schema_Scanner SHALL query the MySQL information_schema to retrieve all user-defined tables
2. THE Schema_Scanner SHALL exclude system tables (prefixed with `mysql`, `information_schema`, `performance_schema`)
3. WHEN tables are discovered, THE Schema_Scanner SHALL retrieve column names, data types, nullable status, and foreign key relationships for each table
4. THE Schema_Scanner SHALL present the discovered tables in a hierarchical tree view with expandable columns
5. WHEN scanning completes, THE Schema_Scanner SHALL store the schema metadata in Meta_Modelador database for future reference

### Requirement 8: Intelligent Schema Scanning - Inventario

**User Story:** As a system administrator, I want the wizard to automatically discover tables in the Inventario PostgreSQL database, so that I can select which tables to include in the configuration.

#### Acceptance Criteria

1. WHEN the administrator initiates schema scanning for Inventario, THE Schema_Scanner SHALL query the PostgreSQL information_schema to retrieve all user-defined tables
2. THE Schema_Scanner SHALL exclude system schemas (`pg_catalog`, `information_schema`)
3. WHEN tables are discovered, THE Schema_Scanner SHALL retrieve column names, data types, nullable status, and foreign key relationships for each table
4. THE Schema_Scanner SHALL present the discovered tables in a hierarchical tree view with expandable columns
5. WHEN scanning completes, THE Schema_Scanner SHALL store the schema metadata in Meta_Modelador database for future reference

### Requirement 9: Rubro Definition

**User Story:** As a business analyst, I want to define a new rubro (business category), so that the system can organize entities by business context.

#### Acceptance Criteria

1. WHEN an administrator creates a new rubro, THE Rubro_Manager SHALL require a unique name and optional description
2. THE Rubro_Manager SHALL validate that the rubro name contains only alphanumeric characters, underscores, and hyphens
3. IF a rubro with the same name already exists, THE Rubro_Manager SHALL display an error and suggest a unique name
4. WHEN a rubro is created, THE Rubro_Manager SHALL generate a unique identifier and record creation timestamp

### Requirement 10: Entity Definition with Field Mapping

**User Story:** As a business analyst, I want to create entities and map them to database tables, so that the system understands the data structure for each business context.

#### Acceptance Criteria

1. WHEN an administrator creates a new entity within a rubro, THE Entity_Manager SHALL require a unique entity name within that rubro
2. THE Entity_Manager SHALL allow the administrator to select a source table from previously scanned schemas
3. WHEN a source table is selected, THE Entity_Manager SHALL automatically create campo (field) definitions from the table columns
4. THE Entity_Manager SHALL allow the administrator to customize each campo with a display name, description, and data type override
5. WHEN an entity is saved, THE Entity_Manager SHALL validate that all required campos have non-empty display names

### Requirement 11: Campo Property Configuration

**User Story:** As a business analyst, I want to configure properties for each campo, so that the system knows how to display and validate the data.

#### Acceptance Criteria

1. WHEN an administrator edits a campo, THE Campo_Manager SHALL allow configuration of display name, description, and data type
2. THE Campo_Manager SHALL support the following data types: string, integer, decimal, boolean, date, datetime, enum, and reference
3. WHERE the data type is enum, THE Campo_Manager SHALL require a list of valid enum values
4. WHERE the data type is reference, THE Campo_Manager SHALL require selection of a referenced entity
5. THE Campo_Manager SHALL allow marking a campo as searchable, filterable, or required for dashboard display

### Requirement 12: Configuration Preview and Validation

**User Story:** As a system administrator, I want to preview and validate the complete configuration before applying it, so that I can catch errors early.

#### Acceptance Criteria

1. WHEN the administrator requests a preview, THE Configuration_Previewer SHALL display all rubros, entidades, and campos in a structured format
2. THE Configuration_Previewer SHALL validate that all referenced entities and campos exist
3. IF validation errors are found, THE Configuration_Previewer SHALL highlight each error with a suggested fix
4. WHEN validation passes, THE Configuration_Previewer SHALL enable the "Apply Configuration" action
5. WHEN the administrator applies the configuration, THE Configuration_Manager SHALL store all definitions in Meta_Modelador database atomically

---

## Phase 2: Laravel-Meta Modelador Bridge

### Requirement 13: Bridge API Client Implementation

**User Story:** As a DSS developer, I want a reusable API client for Meta Modelador, so that DSS can communicate with Meta Modelador reliably.

#### Acceptance Criteria

1. THE DSS_Bridge_Client SHALL implement methods for fetching rubros, entidades, and campos from Meta_Modelador
2. WHEN making a request, THE DSS_Bridge_Client SHALL include the API key header, timeout configuration, and retry logic
3. IF a request fails due to network error, THE DSS_Bridge_Client SHALL retry up to 3 times with exponential backoff
4. IF all retries fail, THE DSS_Bridge_Client SHALL throw a `MetaModeladorConnectionException` with the last error details
5. THE DSS_Bridge_Client SHALL log all requests and responses at debug level for troubleshooting

### Requirement 14: Configuration Caching

**User Story:** As a DSS developer, I want to cache Meta Modelador configurations locally, so that DSS performs efficiently without repeated API calls.

#### Acceptance Criteria

1. WHEN DSS fetches configuration from Meta_Modelador, THE DSS_Cache_Manager SHALL store the response in Redis with a configurable TTL (default 1 hour)
2. WHEN a subsequent request for the same configuration arrives, THE DSS_Cache_Manager SHALL return the cached version if not expired
3. WHEN the cache expires, THE DSS_Cache_Manager SHALL fetch fresh configuration from Meta_Modelador and update the cache
4. WHEN the cache is unavailable (Redis down), THE DSS_Cache_Manager SHALL fall back to direct API calls and log a warning
5. THE DSS_Cache_Manager SHALL provide a manual cache invalidation endpoint for administrators

### Requirement 15: Webhook-based Cache Invalidation

**User Story:** As a Meta Modelador administrator, I want to notify DSS when configuration changes, so that DSS updates its cache immediately.

#### Acceptance Criteria

1. WHEN configuration changes in Meta_Modelador, THE Webhook_Notifier SHALL send a POST request to the DSS webhook endpoint
2. THE Webhook_Notifier SHALL include a signature header computed from the payload using the shared API key
3. WHEN DSS receives the webhook, THE DSS_Webhook_Handler SHALL verify the signature before processing
4. IF the signature is invalid, THE DSS_Webhook_Handler SHALL return HTTP 401 and log the attempted intrusion
5. WHEN the webhook is valid, THE DSS_Webhook_Handler SHALL invalidate the relevant cache entries

### Requirement 16: Configuration Endpoint Exposure

**User Story:** As a DSS developer, I want Meta Modelador to expose RESTful endpoints for configuration retrieval, so that DSS can fetch rubro and entity definitions.

#### Acceptance Criteria

1. THE Meta_Modelador SHALL expose `GET /api/v1/rubros` returning a list of all rubros
2. THE Meta_Modelador SHALL expose `GET /api/v1/rubros/{rubro_id}/entidades` returning all entidades for a rubro
3. THE Meta_Modelador SHALL expose `GET /api/v1/entidades/{entidad_id}/campos` returning all campos for an entity
4. THE Meta_Modelador SHALL expose `GET /api/v1/configuration/full` returning the complete configuration in a single request
5. WHEN any endpoint is called, THE Meta_Modelador SHALL validate the API key and return HTTP 401 if invalid

### Requirement 17: Error Handling and Resilience

**User Story:** As a DSS developer, I want graceful degradation when Meta Modelador is unavailable, so that DSS continues operating with cached or default configurations.

#### Acceptance Criteria

1. WHEN Meta_Modelador is unreachable, THE DSS_Bridge SHALL serve requests using cached configuration
2. IF no cached configuration exists, THE DSS_Bridge SHALL display a maintenance page with estimated recovery time
3. THE DSS_Bridge SHALL track Meta_Modelador health status and alert administrators after 3 consecutive failures
4. WHEN Meta_Modelador recovers, THE DSS_Bridge SHALL automatically refresh the cache and resume normal operation
5. THE DSS_Bridge SHALL log all degradation events with timestamps for post-incident analysis

---

## Phase 3: Dynamic Dashboard

### Requirement 18: Rubro-aware Dashboard Routing

**User Story:** As a DSS user, I want to access a dashboard specific to my rubro, so that I see metrics relevant to my business context.

#### Acceptance Criteria

1. WHEN a user navigates to `/dashboard/{rubro}`, THE Dashboard_Router SHALL verify the rubro exists in Meta_Modelador configuration
2. IF the rubro does not exist, THE Dashboard_Router SHALL return HTTP 404 with a list of available rubros
3. WHEN the rubro exists, THE Dashboard_Router SHALL load the rubro configuration from cache and render the appropriate dashboard
4. THE Dashboard_Router SHALL apply role-based access control to ensure the user has permission to view the rubro dashboard
5. WHEN multiple rubros are available, THE Dashboard_Router SHALL display a rubro selector on the main dashboard page

### Requirement 19: Dynamic Entity Listing

**User Story:** As a DSS user, I want to view a list of entities within my rubro, so that I can navigate to specific data records.

#### Acceptance Criteria

1. WHEN a user selects an entity from the dashboard, THE Entity_List_Renderer SHALL query the DSS database for records in the corresponding table
2. THE Entity_List_Renderer SHALL display columns according to the campo definitions (display name, data type formatting)
3. THE Entity_List_Renderer SHALL support pagination with a configurable page size (default 25 records)
4. WHERE a campo is marked as searchable, THE Entity_List_Renderer SHALL display a search input for that field
5. WHERE a campo is marked as filterable, THE Entity_List_Renderer SHALL display a filter dropdown with distinct values

### Requirement 20: Dynamic Metric Calculation

**User Story:** As a DSS user, I want to see calculated metrics for my rubro, so that I can understand business performance at a glance.

#### Acceptance Criteria

1. WHEN the dashboard loads, THE Metric_Calculator SHALL retrieve metric definitions from Meta_Modelador configuration
2. THE Metric_Calculator SHALL execute SQL queries against the DSS database to compute each metric
3. THE Metric_Calculator SHALL format metric values according to the defined display type (number, currency, percentage)
4. THE Metric_Calculator SHALL cache computed metrics for a configurable period (default 5 minutes)
5. WHEN a metric calculation fails, THE Metric_Calculator SHALL display an error indicator and log the failure

### Requirement 21: Dynamic Chart Generation

**User Story:** As a DSS user, I want to visualize data through charts, so that I can identify trends and patterns.

#### Acceptance Criteria

1. WHEN the dashboard loads, THE Chart_Generator SHALL retrieve chart definitions from Meta_Modelador configuration
2. THE Chart_Generator SHALL support the following chart types: line, bar, pie, and donut
3. THE Chart_Generator SHALL execute the defined SQL query and transform results into chart-compatible JSON
4. THE Chart_Generator SHALL render charts using Chart.js or an equivalent client-side library
5. WHEN a chart query returns no data, THE Chart_Generator SHALL display a "No data available" message instead of an empty chart

### Requirement 22: Export Functionality

**User Story:** As a DSS user, I want to export dashboard data to Excel or PDF, so that I can share reports with stakeholders.

#### Acceptance Criteria

1. WHEN a user requests an export, THE Export_Generator SHALL compile all visible dashboard data into the selected format
2. THE Export_Generator SHALL include all metrics, charts (as images), and data tables in the export
3. THE Export_Generator SHALL apply the user's current filter and search criteria to the exported data
4. WHEN exporting to Excel, THE Export_Generator SHALL format numbers and dates according to campo definitions
5. WHEN exporting to PDF, THE Export_Generator SHALL include a header with the rubro name, export date, and user name

---

## Phase 4: Inventario Integration

### Requirement 23: Inventario Bridge Client Implementation

**User Story:** As an Inventario developer, I want a reusable API client for Meta Modelador, so that Inventario can communicate with Meta Modelador reliably.

#### Acceptance Criteria

1. THE Inventario_Bridge_Client SHALL implement methods for fetching rubros, entidades, and campos from Meta_Modelador
2. WHEN making a request, THE Inventario_Bridge_Client SHALL include the API key header, timeout configuration, and retry logic
3. IF a request fails due to network error, THE Inventario_Bridge_Client SHALL retry up to 3 times with exponential backoff
4. IF all retries fail, THE Inventario_Bridge_Client SHALL raise a `MetaModeladorConnectionError` with the last error details
5. THE Inventario_Bridge_Client SHALL log all requests and responses at debug level for troubleshooting

### Requirement 24: Dynamic Product Schema

**User Story:** As an Inventario user, I want product fields to adapt to my rubro configuration, so that I can manage products specific to my business context.

#### Acceptance Criteria

1. WHEN a user accesses the product management page, THE Product_Schema_Loader SHALL fetch the Product entity definition from Meta_Modelador
2. THE Product_Schema_Loader SHALL render form fields according to campo definitions (text input, number input, dropdown, date picker)
3. WHERE a campo is marked as required, THE Product_Schema_Loader SHALL enforce validation before form submission
4. WHERE a campo is of type reference, THE Product_Schema_Loader SHALL populate a dropdown with options from the referenced entity
5. WHEN the user saves a product, THE Product_Manager SHALL validate all campo constraints before persisting to the database

### Requirement 25: Dynamic Inventory Tracking

**User Story:** As an Inventario user, I want inventory fields to adapt to my rubro configuration, so that I can track inventory attributes specific to my business.

#### Acceptance Criteria

1. WHEN a user accesses the inventory management page, THE Inventory_Schema_Loader SHALL fetch the Inventory entity definition from Meta_Modelador
2. THE Inventory_Schema_Loader SHALL render additional fields beyond the standard quantity and location fields
3. THE Inventory_Schema_Loader SHALL support custom fields for batch numbers, expiration dates, and warehouse zones as defined in campos
4. WHEN inventory is updated, THE Inventory_Manager SHALL record all campo values including custom fields
5. THE Inventory_Manager SHALL support filtering and searching on all campos marked as searchable or filterable

### Requirement 26: Cross-Module Data Synchronization

**User Story:** As a system administrator, I want inventory changes to reflect in DSS dashboards, so that decision-makers have real-time inventory visibility.

#### Acceptance Criteria

1. WHEN inventory is updated in Inventario, THE Sync_Notifier SHALL publish an event to the message queue (Redis or RabbitMQ)
2. WHEN DSS receives the event, THE DSS_Sync_Handler SHALL update the relevant cached metrics
3. THE Sync_Notifier SHALL include the rubro identifier, entity identifier, and changed field names in the event payload
4. IF the message queue is unavailable, THE Sync_Notifier SHALL retry sending the event with exponential backoff
5. THE DSS_Sync_Handler SHALL log all synchronization events with timestamps for audit purposes

### Requirement 27: Unified Authentication Context

**User Story:** As a SIGES user, I want to access all modules with a single sign-on, so that I can navigate seamlessly between DSS and Inventario.

#### Acceptance Criteria

1. WHEN a user logs into any SIGES module, THE Auth_Service SHALL issue a JWT token valid across all modules
2. WHEN a user navigates from DSS to Inventario, THE Inventario_Auth_Handler SHALL validate the JWT token and establish the user session
3. THE JWT token SHALL contain the user's role, accessible rubros, and permissions
4. WHEN the JWT token expires, THE Auth_Service SHALL redirect the user to the login page with a return URL
5. WHEN a user logs out from any module, THE Auth_Service SHALL invalidate the JWT token across all modules

---

## Non-Functional Requirements

### Requirement 28: Performance

**User Story:** As a system administrator, I want the integration to perform efficiently under load, so that users experience responsive applications.

#### Acceptance Criteria

1. THE Meta_Modelador API SHALL respond to configuration requests within 200ms under normal load
2. THE DSS_Bridge cache hit ratio SHALL exceed 95% during normal operation
3. THE Dashboard_Router SHALL render the initial dashboard view within 2 seconds
4. THE Chart_Generator SHALL render each chart within 500ms after data retrieval
5. WHEN concurrent users exceed 100, THE system SHALL maintain response times within 50% of baseline

### Requirement 29: Security

**User Story:** As a security officer, I want all inter-module communication to be secured, so that data is protected in transit and at rest.

#### Acceptance Criteria

1. ALL inter-module API calls SHALL use HTTPS with TLS 1.2 or higher
2. API keys SHALL be stored encrypted at rest using AES-256 or equivalent
3. THE system SHALL log all authentication attempts, configuration changes, and administrative actions
4. THE system SHALL implement rate limiting on API endpoints (100 requests per minute per module)
5. THE system SHALL conduct input validation on all user-provided data before processing

### Requirement 30: Availability

**User Story:** As a system administrator, I want the integration to remain available during component failures, so that business operations continue.

#### Acceptance Criteria

1. DSS SHALL remain operational when Meta_Modelador is unavailable by serving cached configurations
2. Inventario SHALL remain operational when Meta_Modelador is unavailable by serving cached configurations
3. THE system SHALL implement health checks on all module endpoints every 60 seconds
4. WHEN a module becomes unhealthy, THE monitoring system SHALL alert administrators within 2 minutes
5. THE system SHALL achieve 99.5% uptime measured monthly across all integrated modules

### Requirement 31: Maintainability

**User Story:** As a developer, I want clear documentation and logging, so that I can troubleshoot issues efficiently.

#### Acceptance Criteria

1. ALL API endpoints SHALL have OpenAPI/Swagger documentation
2. ALL integration points SHALL produce structured JSON logs with correlation IDs
3. THE system SHALL provide a troubleshooting guide covering common error scenarios
4. THE configuration wizard SHALL validate configurations against a published schema
5. THE codebase SHALL include inline comments explaining integration logic

### Requirement 32: Scalability

**User Story:** As a system administrator, I want the system to scale with business growth, so that performance remains acceptable as data volume increases.

#### Acceptance Criteria

1. THE Meta_Modelador database SHALL support at least 100 rubros with 1000 entidades each
2. THE DSS dashboard SHALL render within 3 seconds for data sets up to 1 million records
3. THE caching layer SHALL support horizontal scaling via Redis Cluster
4. THE API gateway SHALL support horizontal scaling behind a load balancer
5. THE system SHALL handle a 10x increase in configuration size without architecture changes
