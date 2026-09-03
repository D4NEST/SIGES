# Design Document: SIGES Multi-Module Integration

## Overview

SIGES (Sistema Integrado de Gestión) is a multi-module system that unifies three independent applications under a centralized data definition architecture. Meta Modelador serves as the central brain, defining business rubros (categories), entidades (entities), and campos (fields). The DSS and Inventario modules consume this structure to dynamically adapt to any business rubro without code changes.

### Design Goals

1. **Centralized Configuration**: Single source of truth for data definitions across all modules
2. **Secure Inter-Module Communication**: API Key-based authentication with rotation support
3. **Dynamic Adaptation**: Modules automatically adapt to rubro configuration without code changes
4. **High Availability**: Graceful degradation when Meta Modelador is unavailable
5. **Scalability**: Support for 100+ rubros with 1000+ entities each

### Technology Stack

| Component | Technology | Database | Caching |
|-----------|------------|----------|---------|
| Meta Modelador | Flask 2.x + SQLAlchemy | PostgreSQL | Redis (optional) |
| DSS | Laravel 11.x | MySQL 8.x | Redis |
| Inventario | Flask 2.x + psycopg2 | PostgreSQL | Redis (optional) |
| Message Queue | Redis Streams / RabbitMQ | - | - |
| Authentication | JWT with shared secret | - | - |

---

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              SIGES ECOSYSTEM                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                         META MODELADOR                               │    │
│  │                    (Central Configuration Hub)                       │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │    │
│  │  │   Rubros    │  │  Entidades  │  │   Campos    │  │  API Keys  │ │    │
│  │  │   Manager   │  │   Manager   │  │   Manager   │  │  Manager   │ │    │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └────────────┘ │    │
│  │                                                                      │    │
│  │  Flask + PostgreSQL                          Port: 5001              │    │
│  └──────────────────────────┬──────────────────────────────────────────┘    │
│                             │                                                │
│              ┌──────────────┼──────────────┐                                │
│              │              │              │                                │
│              ▼              ▼              ▼                                │
│  ┌───────────────────┐           ┌───────────────────┐                      │
│  │        DSS        │           │    INVENTARIO     │                      │
│  │  (Decision Support│           │   (Product &      │                      │
│  │     System)       │           │   Inventory Mgmt) │                      │
│  ├───────────────────┤           ├───────────────────┤                      │
│  │ Laravel + MySQL   │           │ Flask + PostgreSQL│                      │
│  │ Port: 8000        │           │ Port: 5000        │                      │
│  ├───────────────────┤           ├───────────────────┤                      │
│  │ • Bridge Client   │           │ • Bridge Client   │                      │
│  │ • Cache Manager   │           │ • Schema Loader   │                      │
│  │ • Dashboard Router│           │ • Product Manager │                      │
│  │ • Metric Calculator│          │ • Inventory Sync  │                      │
│  └───────────────────┘           └───────────────────┘                      │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                    SHARED INFRASTRUCTURE                             │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │    │
│  │  │    Redis    │  │   Message   │  │     JWT     │  │   HTTPS    │ │    │
│  │  │   (Cache)   │  │   Queue     │  │  (Shared)   │  │   (TLS)    │ │    │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └────────────┘ │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Communication Flow

```
┌──────────────┐     API Key + HTTPS      ┌──────────────────┐
│              │ ───────────────────────▶ │                  │
│     DSS      │                          │   Meta Modelador │
│   (Laravel)  │ ◀─────────────────────── │     (Flask)      │
│              │   Configuration JSON      │                  │
└──────────────┘                           └──────────────────┘
       │                                           │
       │ Cache Miss                                │
       ▼                                           │
┌──────────────┐                                   │
│    Redis     │                                   │
│   (DSS)      │                                   │
└──────────────┘                                   │
                                                   │
┌──────────────┐     API Key + HTTPS              │
│              │ ───────────────────────▶          │
│  Inventario  │                          │       │
│   (Flask)    │ ◀─────────────────────── ┘       │
│              │   Configuration JSON              │
└──────────────┘                                   
       │                                           
       │ Events (Redis Streams)                    
       ▼                                           
┌──────────────┐     Sync Events      ┌──────────────┐
│     DSS      │ ◀─────────────────── │  Inventario  │
│   (Laravel)  │                      │   (Flask)    │
└──────────────┘                      └──────────────┘
```

---

## Components and Interfaces

### Phase 0: API Key Security

#### API Key Middleware Architecture

**Meta Modelador - API Gateway Middleware**

```python
# Meta Modelador: app/middleware/api_key_auth.py

from functools import wraps
from flask import request, jsonify, g
from models import APIKey
import bcrypt
from datetime import datetime

class APIKeyAuth:
    """Middleware for API key authentication and validation."""
    
    EXEMPT_ROUTES = ['/api/health', '/api/auth/login']
    
    @staticmethod
    def generate_key(length=32):
        """Generate cryptographically secure API key."""
        import secrets
        import string
        alphabet = string.ascii_letters + string.digits
        return ''.join(secrets.choice(alphabet) for _ in range(length))
    
    @staticmethod
    def hash_key(plaintext_key: str) -> str:
        """Hash API key using bcrypt."""
        return bcrypt.hashpw(plaintext_key.encode(), bcrypt.gensalt()).decode()
    
    @staticmethod
    def verify_key(plaintext_key: str, hashed_key: str) -> bool:
        """Verify API key against stored hash."""
        try:
            return bcrypt.checkpw(plaintext_key.encode(), hashed_key.encode())
        except:
            return False
    
    @staticmethod
    def middleware():
        """Flask middleware for API key validation."""
        # Skip exempt routes
        if any(request.path.startswith(route) for route in APIKeyAuth.EXEMPT_ROUTES):
            return None
        
        api_key = request.headers.get('X-API-Key')
        
        if not api_key:
            return jsonify({
                'error': 'API key required',
                'code': 'MISSING_API_KEY'
            }), 401
        
        # Find module by key hash
        for stored_key in APIKey.query.filter_by(activo=True).all():
            if APIKeyAuth.verify_key(api_key, stored_key.key_hash):
                # Update last used timestamp
                stored_key.last_used = datetime.utcnow()
                db.session.commit()
                
                # Attach module identity to request context
                g.module_id = stored_key.module_id
                g.module_name = stored_key.module_name
                
                # Log successful authentication
                AuditLog.log('api_auth_success', {
                    'module': stored_key.module_name,
                    'ip': request.remote_addr
                })
                return None
        
        # Log failed attempt
        AuditLog.log('api_auth_failed', {
            'ip': request.remote_addr,
            'path': request.path
        })
        
        return jsonify({
            'error': 'Invalid API key',
            'code': 'INVALID_API_KEY'
        }), 401
```

**API Key Model**

```python
# Meta Modelador: models/api_key.py

class APIKey(db.Model):
    __tablename__ = 'api_keys'
    
    id = db.Column(db.Integer, primary_key=True)
    module_name = db.Column(db.String(50), unique=True, nullable=False)
    key_hash = db.Column(db.String(255), nullable=False)
    rotation_grace_until = db.Column(db.DateTime, nullable=True)
    previous_key_hash = db.Column(db.String(255), nullable=True)
    activo = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    last_used = db.Column(db.DateTime, nullable=True)
    created_by = db.Column(db.Integer, db.ForeignKey('usuarios.id'))
    
    # Relationships
    creator = db.relationship('Usuario', backref='api_keys')
    
    @staticmethod
    def generate_for_module(module_name: str, created_by_id: int) -> str:
        """Generate new API key for a module. Returns plaintext key ONCE."""
        plaintext = APIKeyAuth.generate_key(32)
        
        existing = APIKey.query.filter_by(module_name=module_name).first()
        
        if existing:
            # Rotation: store old hash during grace period
            existing.previous_key_hash = existing.key_hash
            existing.rotation_grace_until = datetime.utcnow() + timedelta(hours=24)
            existing.key_hash = APIKeyAuth.hash_key(plaintext)
        else:
            new_key = APIKey(
                module_name=module_name,
                key_hash=APIKeyAuth.hash_key(plaintext),
                created_by=created_by_id
            )
            db.session.add(new_key)
        
        db.session.commit()
        return plaintext
```

**DSS - API Client Configuration**

```php
// DSS: app/Services/MetaModeladorApiClient.php

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class MetaModeladorApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private int $maxRetries;
    
    public function __construct()
    {
        $this->baseUrl = config('services.meta_modelador.url');
        $this->apiKey = config('services.meta_modelador.api_key');
        $this->timeout = config('services.meta_modelador.timeout', 10);
        $this->maxRetries = config('services.meta_modelador.retries', 3);
        
        if (empty($this->apiKey)) {
            Log::error('Meta Modelador API key not configured');
            throw new RuntimeException('API key not configured');
        }
    }
    
    /**
     * Make authenticated request with retry logic.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->{$method}("{$this->baseUrl}{$endpoint}", $data);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                if ($response->status() === 401) {
                    Log::error('Meta Modelador authentication failed', [
                        'endpoint' => $endpoint,
                        'status' => $response->status()
                    ]);
                    throw new MetaModeladorAuthException('Authentication failed');
                }
                
                throw new RuntimeException("Request failed: {$response->status()}");
                
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt < $this->maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s
                    usleep(pow(2, $attempt) * 1000000);
                }
            }
        }
        
        Log::error('Meta Modelador request failed after retries', [
            'endpoint' => $endpoint,
            'attempts' => $attempt,
            'error' => $lastException?->getMessage()
        ]);
        
        throw new MetaModeladorConnectionException(
            "Connection failed after {$attempt} attempts: " . $lastException?->getMessage()
        );
    }
    
    public function getRubros(): array
    {
        return $this->request('get', '/api/v1/rubros');
    }
    
    public function getEntidades(int $rubroId): array
    {
        return $this->request('get', "/api/v1/rubros/{$rubroId}/entidades");
    }
    
    public function getCampos(int $entidadId): array
    {
        return $this->request('get', "/api/v1/entidades/{$entidadId}/campos");
    }
    
    public function getFullConfiguration(): array
    {
        return $this->request('get', '/api/v1/configuration/full');
    }
}
```

**Environment Configuration**

```env
# DSS: .env

# Meta Modelador Connection
META_MODELADOR_URL=https://meta-modelador.siges.local
META_MODELADOR_API_KEY=encrypted:xxxxx  # Encrypted in database
META_MODELADOR_TIMEOUT=10
META_MODELADOR_RETRIES=3
META_MODELADOR_CACHE_TTL=3600

# Redis Cache
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CACHE_DB=1
```

---

### Phase 1: Configuration Wizard

#### Schema Scanner Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CONFIGURATION WIZARD                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐         │
│  │  Connection     │    │  Schema         │    │  Configuration  │         │
│  │  Manager        │───▶│  Scanner        │───▶│  Builder        │         │
│  └─────────────────┘    └─────────────────┘    └─────────────────┘         │
│         │                       │                       │                   │
│         ▼                       ▼                       ▼                   │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐         │
│  │  SSH Tunnel     │    │  MySQL/PostgreSQL│    │  YAML Generator │         │
│  │  (Optional)     │    │  Introspection   │    │                 │         │
│  └─────────────────┘    └─────────────────┘    └─────────────────┘         │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Schema Scanner - MySQL Implementation**

```python
# Meta Modelador: services/schema_scanner.py

from sqlalchemy import create_engine, inspect, text
from typing import List, Dict, Optional
import logging

logger = logging.getLogger(__name__)

class SchemaScanner:
    """Scans database schemas for MySQL and PostgreSQL."""
    
    MYSQL_SYSTEM_DATABASES = ['mysql', 'information_schema', 'performance_schema', 'sys']
    PG_SYSTEM_SCHEMAS = ['pg_catalog', 'information_schema']
    
    def __init__(self, connection_string: str, db_type: str = 'mysql'):
        self.engine = create_engine(connection_string)
        self.inspector = inspect(self.engine)
        self.db_type = db_type
    
    def scan_tables(self, schema: str = None) -> List[Dict]:
        """
        Scan all user-defined tables in the database.
        Returns hierarchical structure with columns.
        """
        tables = []
        
        if self.db_type == 'mysql':
            # Get all non-system databases
            databases = self._get_mysql_databases()
            
            for db_name in databases:
                if db_name in self.MYSQL_SYSTEM_DATABASES:
                    continue
                
                db_tables = self._get_mysql_tables(db_name)
                tables.extend(db_tables)
        
        elif self.db_type == 'postgresql':
            # Get all non-system schemas
            schemas = self._get_pg_schemas()
            
            for schema_name in schemas:
                if schema_name in self.PG_SYSTEM_SCHEMAS:
                    continue
                
                schema_tables = self._get_pg_tables(schema_name)
                tables.extend(schema_tables)
        
        return tables
    
    def _get_mysql_databases(self) -> List[str]:
        """Get all MySQL databases."""
        with self.engine.connect() as conn:
            result = conn.execute(text("SHOW DATABASES"))
            return [row[0] for row in result]
    
    def _get_mysql_tables(self, database: str) -> List[Dict]:
        """Get all tables in a MySQL database with columns."""
        tables = []
        
        with self.engine.connect() as conn:
            conn.execute(text(f"USE `{database}`"))
            result = conn.execute(text("SHOW TABLES"))
            table_names = [row[0] for row in result]
        
        for table_name in table_names:
            columns = self._get_mysql_columns(database, table_name)
            foreign_keys = self._get_mysql_foreign_keys(database, table_name)
            
            tables.append({
                'database': database,
                'table_name': table_name,
                'full_name': f"{database}.{table_name}",
                'columns': columns,
                'foreign_keys': foreign_keys,
                'row_count': self._get_row_count(database, table_name)
            })
        
        return tables
    
    def _get_mysql_columns(self, database: str, table: str) -> List[Dict]:
        """Get column details for a MySQL table."""
        query = """
        SELECT 
            COLUMN_NAME,
            DATA_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT,
            COLUMN_KEY,
            EXTRA,
            COLUMN_COMMENT,
            CHARACTER_MAXIMUM_LENGTH,
            NUMERIC_PRECISION,
            NUMERIC_SCALE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
        ORDER BY ORDINAL_POSITION
        """
        
        with self.engine.connect() as conn:
            result = conn.execute(text(query), {'db': database, 'table': table})
            
            return [{
                'name': row.COLUMN_NAME,
                'data_type': row.DATA_TYPE,
                'nullable': row.IS_NULLABLE == 'YES',
                'default': row.COLUMN_DEFAULT,
                'is_primary': row.COLUMN_KEY == 'PRI',
                'is_unique': row.COLUMN_KEY == 'UNI',
                'auto_increment': 'auto_increment' in (row.EXTRA or ''),
                'comment': row.COLUMN_COMMENT,
                'max_length': row.CHARACTER_MAXIMUM_LENGTH,
                'precision': row.NUMERIC_PRECISION,
                'scale': row.NUMERIC_SCALE
            } for row in result]
    
    def _get_mysql_foreign_keys(self, database: str, table: str) -> List[Dict]:
        """Get foreign key relationships for a MySQL table."""
        query = """
        SELECT
            COLUMN_NAME,
            REFERENCED_TABLE_SCHEMA,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :db 
          AND TABLE_NAME = :table
          AND REFERENCED_TABLE_NAME IS NOT NULL
        """
        
        with self.engine.connect() as conn:
            result = conn.execute(text(query), {'db': database, 'table': table})
            
            return [{
                'column': row.COLUMN_NAME,
                'references': {
                    'database': row.REFERENCED_TABLE_SCHEMA,
                    'table': row.REFERENCED_TABLE_NAME,
                    'column': row.REFERENCED_COLUMN_NAME
                }
            } for row in result]
    
    def _get_row_count(self, database: str, table: str) -> int:
        """Get approximate row count for a table."""
        try:
            with self.engine.connect() as conn:
                result = conn.execute(text(f"SELECT COUNT(*) FROM `{database}`.`{table}`"))
                return result.scalar()
        except:
            return -1  # Error getting count
    
    # PostgreSQL implementations
    def _get_pg_schemas(self) -> List[str]:
        """Get all PostgreSQL schemas."""
        return self.inspector.get_schema_names()
    
    def _get_pg_tables(self, schema: str) -> List[Dict]:
        """Get all tables in a PostgreSQL schema."""
        tables = []
        table_names = self.inspector.get_table_names(schema)
        
        for table_name in table_names:
            columns = self.inspector.get_columns(table_name, schema)
            foreign_keys = self.inspector.get_foreign_keys(table_name, schema)
            
            tables.append({
                'schema': schema,
                'table_name': table_name,
                'full_name': f"{schema}.{table_name}",
                'columns': [{
                    'name': col['name'],
                    'data_type': str(col['type']),
                    'nullable': col.get('nullable', True),
                    'default': col.get('default'),
                    'is_primary': col.get('primary_key', False)
                } for col in columns],
                'foreign_keys': [{
                    'constrained_columns': fk['constrained_columns'],
                    'referred_table': fk['referred_table'],
                    'referred_columns': fk['referred_columns']
                } for fk in foreign_keys]
            })
        
        return tables
    
    def infer_rubro_from_tables(self, tables: List[Dict]) -> Dict:
        """
        Analyze table structure and suggest rubro configuration.
        Uses naming patterns and relationships.
        """
        suggestions = {
            'entidades': [],
            'relationships': []
        }
        
        # Detect common patterns
        for table in tables:
            entity = self._infer_entity(table)
            if entity:
                suggestions['entidades'].append(entity)
        
        # Detect relationships from foreign keys
        for table in tables:
            for fk in table.get('foreign_keys', []):
                suggestions['relationships'].append({
                    'from': table['table_name'],
                    'to': fk.get('references', {}).get('table') or fk.get('referred_table'),
                    'type': 'many_to_one'
                })
        
        return suggestions
    
    def _infer_entity(self, table: Dict) -> Optional[Dict]:
        """Infer entity definition from table structure."""
        table_name = table['table_name']
        
        # Skip junction tables (typically have only foreign keys)
        non_fk_columns = [c for c in table['columns'] 
                         if not c.get('is_foreign_key')]
        
        if len(non_fk_columns) <= 2 and len(table.get('foreign_keys', [])) >= 2:
            return None  # Likely a junction table
        
        # Infer campos from columns
        campos = []
        for col in table['columns']:
            campo = self._infer_campo(col)
            if campo:
                campos.append(campo)
        
        return {
            'nombre': self._to_singular(table_name),
            'nombre_tabla': table_name,
            'nombre_plural': table_name,
            'campos': campos
        }
    
    def _infer_campo(self, column: Dict) -> Dict:
        """Infer campo definition from column metadata."""
        return {
            'nombre': column['name'],
            'nombre_fisico': column['name'],
            'tipo': self._map_data_type(column['data_type']),
            'etiqueta': self._to_label(column['name']),
            'es_requerido': not column['nullable'] and not column.get('default'),
            'es_indice': column.get('is_primary') or column.get('is_unique')
        }
    
    def _map_data_type(self, db_type: str) -> str:
        """Map database type to campo type."""
        type_map = {
            'varchar': 'string',
            'char': 'string',
            'text': 'text',
            'int': 'integer',
            'integer': 'integer',
            'bigint': 'integer',
            'decimal': 'float',
            'float': 'float',
            'double': 'float',
            'boolean': 'boolean',
            'bool': 'boolean',
            'date': 'date',
            'datetime': 'date',
            'timestamp': 'date',
            'time': 'string',
            'enum': 'select',
            'json': 'text'
        }
        
        base_type = str(db_type).lower().split('(')[0]
        return type_map.get(base_type, 'string')
    
    def _to_singular(self, word: str) -> str:
        """Convert plural table name to singular entity name."""
        if word.endswith('es'):
            return word[:-2]
        elif word.endswith('s'):
            return word[:-1]
        return word
    
    def _to_label(self, column_name: str) -> str:
        """Convert column_name to human-readable label."""
        return column_name.replace('_', ' ').title()
```

**SSH Tunnel Support**

```python
# Meta Modelador: services/ssh_tunnel.py

from sshtunnel import SSHTunnelForwarder
from typing import Optional
import logging

logger = logging.getLogger(__name__)

class SSHTunnelManager:
    """Manages SSH tunnels for remote database connections."""
    
    def __init__(self, ssh_config: dict):
        self.ssh_host = ssh_config.get('host')
        self.ssh_port = ssh_config.get('port', 22)
        self.ssh_username = ssh_config.get('username')
        self.ssh_key_path = ssh_config.get('key_path')
        self.ssh_password = ssh_config.get('password')
        
        self.tunnel: Optional[SSHTunnelForwarder] = None
    
    def connect(self, remote_host: str, remote_port: int) -> tuple:
        """
        Establish SSH tunnel to remote database.
        Returns (local_bind_address, local_bind_port).
        """
        self.tunnel = SSHTunnelForwarder(
            (self.ssh_host, self.ssh_port),
            ssh_username=self.ssh_username,
            ssh_password=self.ssh_password,
            ssh_pkey=self.ssh_key_path,
            remote_bind_address=(remote_host, remote_port)
        )
        
        self.tunnel.start()
        
        logger.info(f"SSH tunnel established: {self.ssh_host} -> {remote_host}:{remote_port}")
        
        return self.tunnel.local_bind_address, self.tunnel.local_bind_port
    
    def close(self):
        """Close SSH tunnel."""
        if self.tunnel:
            self.tunnel.stop()
            logger.info("SSH tunnel closed")
    
    def __enter__(self):
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        self.close()
```

**YAML Configuration Generator**

```python
# Meta Modelador: services/yaml_generator.py

import yaml
from typing import Dict, List
from datetime import datetime

class YAMLConfigGenerator:
    """Generates YAML configuration files from rubro definitions."""
    
    @staticmethod
    def generate_rubro_config(rubro: Dict, entidades: List[Dict]) -> str:
        """Generate complete YAML configuration for a rubro."""
        
        config = {
            'meta': {
                'version': '1.0',
                'generated_at': datetime.utcnow().isoformat(),
                'rubro_id': rubro['id'],
                'rubro_nombre': rubro['nombre']
            },
            'rubro': {
                'nombre': rubro['nombre'],
                'descripcion': rubro.get('descripcion', ''),
                'configuracion_base': rubro.get('configuracion_base', {})
            },
            'entidades': []
        }
        
        for entidad in entidades:
            entity_config = YAMLConfigGenerator._build_entity_config(entidad)
            config['entidades'].append(entity_config)
        
        return yaml.dump(config, default_flow_style=False, allow_unicode=True)
    
    @staticmethod
    def _build_entity_config(entidad: Dict) -> Dict:
        """Build configuration for a single entity."""
        
        return {
            'nombre': entidad['nombre'],
            'nombre_tabla': entidad['nombre_tabla'],
            'nombre_plural': entidad.get('nombre_plural', entidad['nombre'] + 's'),
            'icono': entidad.get('icono', '📦'),
            'descripcion': entidad.get('descripcion', ''),
            'modulo': entidad.get('modulo', 'erp'),
            'campos': [
                YAMLConfigGenerator._build_campo_config(campo)
                for campo in entidad.get('campos', [])
            ]
        }
    
    @staticmethod
    def _build_campo_config(campo: Dict) -> Dict:
        """Build configuration for a single campo."""
        
        return {
            'nombre': campo['nombre'],
            'nombre_fisico': campo['nombre_fisico'],
            'tipo': campo['tipo'],
            'etiqueta': campo.get('etiqueta', campo['nombre']),
            'placeholder': campo.get('placeholder', ''),
            'descripcion': campo.get('descripcion', ''),
            'es_requerido': campo.get('es_requerido', False),
            'es_unico': campo.get('es_unico', False),
            'es_indice': campo.get('es_indice', False),
            'valor_por_defecto': campo.get('valor_por_defecto'),
            'opciones': campo.get('opciones'),
            'validaciones': campo.get('validaciones', {}),
            'visible_en_tabla': campo.get('visible_en_tabla', True),
            'visible_en_formulario': campo.get('visible_en_formulario', True)
        }
    
    @staticmethod
    def parse_yaml_config(yaml_content: str) -> Dict:
        """Parse YAML configuration and validate structure."""
        
        config = yaml.safe_load(yaml_content)
        
        # Validate required fields
        if 'rubro' not in config:
            raise ValueError("Missing 'rubro' section in configuration")
        
        if 'entidades' not in config:
            raise ValueError("Missing 'entidades' section in configuration")
        
        return config
```

---

### Phase 2: Laravel-Meta Modelador Bridge

#### Bridge Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      DSS BRIDGE ARCHITECTURE                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │                      MetaModeladorService                              │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │  │
│  │  │   Client    │  │   Cache     │  │   Health    │  │   Webhook   │  │  │
│  │  │   (HTTP)    │  │  Manager    │  │   Monitor   │  │   Handler   │  │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                      │                                       │
│                                      ▼                                       │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │                           Redis Cache                                  │  │
│  │  ┌─────────────────────────────────────────────────────────────────┐  │  │
│  │  │  Key Pattern: siges:config:{rubro_id}:{entidad_id}              │  │  │
│  │  │  TTL: 3600s (1 hour)                                            │  │  │
│  │  │  Tags: rubro, entidad, campos                                    │  │  │
│  │  └─────────────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

**MetaModeladorService Implementation**

```php
// DSS: app/Services/MetaModeladorService.php

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\MetaModeladorConnectionException;

class MetaModeladorService
{
    private MetaModeladorApiClient $client;
    private int $cacheTtl;
    private bool $cacheEnabled;
    
    // Cache key patterns
    const CACHE_KEY_RUBROS = 'siges:rubros:all';
    const CACHE_KEY_RUBRO = 'siges:rubro:{id}';
    const CACHE_KEY_ENTIDADES = 'siges:rubro:{rubro_id}:entidades';
    const CACHE_KEY_CAMPOS = 'siges:entidad:{entidad_id}:campos';
    const CACHE_KEY_FULL = 'siges:config:full';
    
    // Health tracking
    private static array $healthStatus = [
        'healthy' => true,
        'consecutive_failures' => 0,
        'last_check' => null
    ];
    
    public function __construct(MetaModeladorApiClient $client)
    {
        $this->client = $client;
        $this->cacheTtl = config('services.meta_modelador.cache_ttl', 3600);
        $this->cacheEnabled = config('services.meta_modelador.cache_enabled', true);
    }
    
    /**
     * Get all rubros with caching.
     */
    public function getRubros(): array
    {
        return $this->cached(self::CACHE_KEY_RUBROS, fn() => 
            $this->client->getRubros()
        );
    }
    
    /**
     * Get rubro by ID with caching.
     */
    public function getRubro(int $id): array
    {
        $key = str_replace('{id}', $id, self::CACHE_KEY_RUBRO);
        
        return $this->cached($key, fn() => 
            $this->client->request('get', "/api/v1/rubros/{$id}")
        );
    }
    
    /**
     * Get entidades for a rubro with caching.
     */
    public function getEntidades(int $rubroId): array
    {
        $key = str_replace('{rubro_id}', $rubroId, self::CACHE_KEY_ENTIDADES);
        
        return $this->cached($key, fn() => 
            $this->client->getEntidades($rubroId)
        );
    }
    
    /**
     * Get campos for an entidad with caching.
     */
    public function getCampos(int $entidadId): array
    {
        $key = str_replace('{entidad_id}', $entidadId, self::CACHE_KEY_CAMPOS);
        
        return $this->cached($key, fn() => 
            $this->client->getCampos($entidadId)
        );
    }
    
    /**
     * Get full configuration in single request.
     */
    public function getFullConfiguration(): array
    {
        return $this->cached(self::CACHE_KEY_FULL, fn() => 
            $this->client->getFullConfiguration()
        );
    }
    
    /**
     * Get configuration for specific rubro with all entities and campos.
     */
    public function getRubroConfiguration(int $rubroId): array
    {
        $key = "siges:rubro:{$rubroId}:full";
        
        return $this->cached($key, function() use ($rubroId) {
            $rubro = $this->getRubro($rubroId);
            $entidades = $this->getEntidades($rubroId);
            
            foreach ($entidades as &$entidad) {
                $entidad['campos'] = $this->getCampos($entidad['id']);
            }
            
            return [
                'rubro' => $rubro,
                'entidades' => $entidades
            ];
        });
    }
    
    /**
     * Invalidate cache for a rubro.
     */
    public function invalidateRubroCache(int $rubroId): void
    {
        $keys = [
            self::CACHE_KEY_RUBROS,
            str_replace('{id}', $rubroId, self::CACHE_KEY_RUBRO),
            str_replace('{rubro_id}', $rubroId, self::CACHE_KEY_ENTIDADES),
            "siges:rubro:{$rubroId}:full",
            self::CACHE_KEY_FULL
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Log::info("Cache invalidated for rubro {$rubroId}");
    }
    
    /**
     * Handle webhook from Meta Modelador.
     */
    public function handleWebhook(array $payload, string $signature): bool
    {
        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($payload),
            config('services.meta_modelador.api_key')
        );
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid webhook signature received');
            return false;
        }
        
        // Invalidate relevant caches
        if (isset($payload['rubro_id'])) {
            $this->invalidateRubroCache($payload['rubro_id']);
        }
        
        if (isset($payload['full_invalidation']) && $payload['full_invalidation']) {
            $this->invalidateAllCache();
        }
        
        return true;
    }
    
    /**
     * Invalidate all cache.
     */
    public function invalidateAllCache(): void
    {
        // Use cache tags if supported (Redis)
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['siges', 'meta_modelador'])->flush();
        } else {
            // Fallback: clear all cache
            Cache::flush();
        }
        
        Log::info('All Meta Modelador cache invalidated');
    }
    
    /**
     * Check health status.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => config('services.meta_modelador.api_key')
            ])
            ->timeout(5)
            ->get(config('services.meta_modelador.url') . '/api/health');
            
            self::$healthStatus['healthy'] = $response->successful();
            self::$healthStatus['last_check'] = now();
            self::$healthStatus['consecutive_failures'] = 0;
            
        } catch (\Exception $e) {
            self::$healthStatus['healthy'] = false;
            self::$healthStatus['consecutive_failures']++;
            self::$healthStatus['last_check'] = now();
            
            Log::error('Meta Modelador health check failed', [
                'error' => $e->getMessage(),
                'consecutive_failures' => self::$healthStatus['consecutive_failures']
            ]);
            
            // Alert after 3 consecutive failures
            if (self::$healthStatus['consecutive_failures'] >= 3) {
                $this->alertAdministrators();
            }
        }
        
        return self::$healthStatus['healthy'];
    }
    
    /**
     * Get cached value or fetch from API.
     */
    private function cached(string $key, callable $fetcher)
    {
        if (!$this->cacheEnabled) {
            return $this->executeWithFallback($fetcher);
        }
        
        return Cache::remember($key, $this->cacheTtl, function() use ($fetcher) {
            return $this->executeWithFallback($fetcher);
        });
    }
    
    /**
     * Execute with graceful degradation.
     */
    private function executeWithFallback(callable $fetcher)
    {
        try {
            $result = $fetcher();
            self::$healthStatus['consecutive_failures'] = 0;
            return $result;
            
        } catch (MetaModeladorConnectionException $e) {
            Log::warning('Meta Modelador unavailable, using cached data');
            
            // Return stale cache if available
            $staleCache = Cache::get('stale:' . $key);
            if ($staleCache) {
                return $staleCache;
            }
            
            throw $e;
        }
    }
    
    /**
     * Alert administrators about Meta Modelador issues.
     */
    private function alertAdministrators(): void
    {
        // Implementation depends on notification system
        Log::critical('Meta Modelador has been unavailable for 3 consecutive checks', [
            'last_check' => self::$healthStatus['last_check'],
            'consecutive_failures' => self::$healthStatus['consecutive_failures']
        ]);
        
        // Could send email, Slack notification, etc.
    }
}
```

**Webhook Handler**

```php
// DSS: app/Http/Controllers/WebhookController.php

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MetaModeladorService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private MetaModeladorService $service;
    
    public function __construct(MetaModeladorService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Handle configuration change webhook from Meta Modelador.
     * 
     * @OA\Post(
     *     path="/api/webhooks/meta-modelador",
     *     summary="Handle configuration change webhook",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event", type="string", example="config_changed"),
     *             @OA\Property(property="rubro_id", type="integer", example=1),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="changes", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response="200", description="Webhook processed"),
     *     @OA\Response(response="401", description="Invalid signature")
     * )
     */
    public function handleMetaModelador(Request $request)
    {
        $signature = $request->header('X-Signature');
        $payload = $request->json()->all();
        
        if (!$signature) {
            Log::warning('Webhook received without signature');
            return response()->json(['error' => 'Missing signature'], 401);
        }
        
        $success = $this->service->handleWebhook($payload, $signature);
        
        if (!$success) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        Log::info('Webhook processed successfully', $payload);
        
        return response()->json(['status' => 'processed']);
    }
}
```

---

### Phase 3: Dynamic Dashboard

#### Dynamic KPI Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    DYNAMIC DASHBOARD ARCHITECTURE                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │                      DashboardController                               │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │  │
│  │  │    Rubro    │  │   Entity    │  │   Metric    │  │   Chart     │  │  │
│  │  │   Router    │──▶│   Loader    │──▶│ Calculator  │──▶│ Generator   │  │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                      │                                       │
│                                      ▼                                       │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │                      Livewire Components                               │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │  │
│  │  │   KPI Card  │  │ Data Table  │  │   Chart.js  │  │   Export    │  │  │
│  │  │  (Dynamic)  │  │  (Dynamic)  │  │  (Dynamic)  │  │   (XLS/PDF) │  │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Dynamic Metric Calculator**

```php
// DSS: app/Services/DynamicMetricCalculator.php

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\MetaModeladorService;

class DynamicMetricCalculator
{
    private MetaModeladorService $configService;
    
    public function __construct(MetaModeladorService $configService)
    {
        $this->configService = $configService;
    }
    
    /**
     * Calculate all metrics for a rubro dashboard.
     */
    public function calculateMetrics(int $rubroId): array
    {
        $config = $this->configService->getRubroConfiguration($rubroId);
        $metrics = [];
        
        foreach ($config['entidades'] as $entidad) {
            $tableName = $entidad['nombre_tabla'];
            $campos = $entidad['campos'];
            
            // Generate metrics based on campo types
            $metrics[$entidad['nombre']] = $this->generateEntityMetrics(
                $tableName,
                $campos,
                $entidad
            );
        }
        
        return $metrics;
    }
    
    /**
     * Generate metrics for a single entity.
     */
    private function generateEntityMetrics(string $tableName, array $campos, array $entidad): array
    {
        $metrics = [
            'total_records' => $this->getTotalRecords($tableName),
            'numeric_aggregations' => [],
            'categorical_distributions' => [],
            'time_series' => []
        ];
        
        foreach ($campos as $campo) {
            // Skip non-aggregatable fields
            if (!$this->isAggregatable($campo)) {
                continue;
            }
            
            switch ($campo['tipo']) {
                case 'integer':
                case 'float':
                case 'currency':
                    $metrics['numeric_aggregations'][$campo['nombre']] = 
                        $this->calculateNumericMetrics($tableName, $campo);
                    break;
                    
                case 'select':
                case 'boolean':
                case 'string':
                    $metrics['categorical_distributions'][$campo['nombre']] = 
                        $this->calculateCategoricalDistribution($tableName, $campo);
                    break;
                    
                case 'date':
                    $metrics['time_series'][$campo['nombre']] = 
                        $this->calculateTimeSeries($tableName, $campo);
                    break;
            }
        }
        
        return $metrics;
    }
    
    /**
     * Calculate numeric metrics (sum, avg, min, max).
     */
    private function calculateNumericMetrics(string $tableName, array $campo): array
    {
        $column = $campo['nombre_fisico'];
        
        $result = DB::table($tableName)
            ->selectRaw("
                COUNT({$column}) as count,
                SUM({$column}) as sum,
                AVG({$column}) as avg,
                MIN({$column}) as min,
                MAX({$column}) as max
            ")
            ->first();
        
        return [
            'label' => $campo['etiqueta'],
            'type' => $campo['tipo'],
            'count' => $result->count,
            'sum' => round($result->sum, 2),
            'avg' => round($result->avg, 2),
            'min' => round($result->min, 2),
            'max' => round($result->max, 2),
            'formatted' => $this->formatMetric($result->sum ?? 0, $campo['tipo'])
        ];
    }
    
    /**
     * Calculate distribution for categorical fields.
     */
    private function calculateCategoricalDistribution(string $tableName, array $campo): array
    {
        $column = $campo['nombre_fisico'];
        
        $results = DB::table($tableName)
            ->select($column)
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByDesc('count')
            ->limit(20)
            ->get();
        
        return [
            'label' => $campo['etiqueta'],
            'type' => 'distribution',
            'data' => $results->map(fn($row) => [
                'label' => $row->$column,
                'value' => $row->count
            ])->toArray()
        ];
    }
    
    /**
     * Calculate time series for date fields.
     */
    private function calculateTimeSeries(string $tableName, array $campo): array
    {
        $column = $campo['nombre_fisico'];
        
        // Last 30 days by default
        $results = DB::table($tableName)
            ->selectRaw("DATE({$column}) as date, COUNT(*) as count")
            ->where($column, '>=', now()->subDays(30))
            ->groupByRaw("DATE({$column})")
            ->orderBy('date')
            ->get();
        
        return [
            'label' => $campo['etiqueta'],
            'type' => 'timeseries',
            'interval' => 'daily',
            'data' => $results->map(fn($row) => [
                'date' => $row->date,
                'value' => $row->count
            ])->toArray()
        ];
    }
    
    /**
     * Get total records count.
     */
    private function getTotalRecords(string $tableName): int
    {
        return DB::table($tableName)->count();
    }
    
    /**
     * Check if field is aggregatable.
     */
    private function isAggregatable(array $campo): bool
    {
        // Skip text fields, unique identifiers, etc.
        $nonAggregatable = ['text', 'email'];
        
        return !in_array($campo['tipo'], $nonAggregatable);
    }
    
    /**
     * Format metric based on type.
     */
    private function formatMetric(float $value, string $type): string
    {
        return match($type) {
            'currency' => '$' . number_format($value, 2),
            'float' => number_format($value, 2),
            'integer' => number_format($value, 0),
            default => (string) $value
        };
    }
    
    /**
     * Execute custom SQL query for metric.
     */
    public function executeCustomQuery(string $query, array $bindings = []): array
    {
        // Security: Only allow SELECT queries
        if (!preg_match('/^\s*SELECT/i', $query)) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed');
        }
        
        // Sanitize query - prevent dangerous operations
        $dangerous = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
        foreach ($dangerous as $keyword) {
            if (preg_match("/\b{$keyword}\b/i", $query)) {
                throw new \InvalidArgumentException("Forbidden keyword: {$keyword}");
            }
        }
        
        try {
            $results = DB::select($query, $bindings);
            return [
                'success' => true,
                'data' => $results
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

**Chart.js Integration**

```php
// DSS: app/Services/ChartGeneratorService.php

<?php

namespace App\Services;

class ChartGeneratorService
{
    /**
     * Generate Chart.js configuration for metric data.
     */
    public function generateChartConfig(array $metric, string $chartType = 'auto'): array
    {
        if ($chartType === 'auto') {
            $chartType = $this->determineChartType($metric);
        }
        
        return match($chartType) {
            'line' => $this->generateLineChart($metric),
            'bar' => $this->generateBarChart($metric),
            'pie' => $this->generatePieChart($metric),
            'donut' => $this->generateDonutChart($metric),
            default => $this->generateBarChart($metric)
        };
    }
    
    /**
     * Determine best chart type based on metric data.
     */
    private function determineChartType(array $metric): string
    {
        if ($metric['type'] === 'timeseries') {
            return 'line';
        }
        
        if ($metric['type'] === 'distribution') {
            $dataPoints = count($metric['data']);
            return $dataPoints <= 10 ? 'pie' : 'bar';
        }
        
        return 'bar';
    }
    
    /**
     * Generate line chart configuration.
     */
    private function generateLineChart(array $metric): array
    {
        return [
            'type' => 'line',
            'data' => [
                'labels' => array_column($metric['data'], 'date'),
                'datasets' => [[
                    'label' => $metric['label'],
                    'data' => array_column($metric['data'], 'value'),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ]]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'tooltip' => ['mode' => 'index', 'intersect' => false]
                ],
                'scales' => [
                    'x' => ['grid' => ['display' => false]],
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];
    }
    
    /**
     * Generate bar chart configuration.
     */
    private function generateBarChart(array $metric): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($metric['data'], 'label'),
                'datasets' => [[
                    'label' => $metric['label'],
                    'data' => array_column($metric['data'], 'value'),
                    'backgroundColor' => $this->generateColors(count($metric['data'])),
                    'borderWidth' => 0
                ]]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false]
                ],
                'scales' => [
                    'x' => ['grid' => ['display' => false]],
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];
    }
    
    /**
     * Generate pie chart configuration.
     */
    private function generatePieChart(array $metric): array
    {
        return [
            'type' => 'pie',
            'data' => [
                'labels' => array_column($metric['data'], 'label'),
                'datasets' => [[
                    'data' => array_column($metric['data'], 'value'),
                    'backgroundColor' => $this->generateColors(count($metric['data'])),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff'
                ]]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'right'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Generate donut chart configuration.
     */
    private function generateDonutChart(array $metric): array
    {
        $config = $this->generatePieChart($metric);
        $config['type'] = 'doughnut';
        $config['options']['cutout'] = '60%';
        
        return $config;
    }
    
    /**
     * Generate colors for chart data.
     */
    private function generateColors(int $count): array
    {
        $baseColors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
        ];
        
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }
        
        return $colors;
    }
}
```

**Livewire Dashboard Component**

```php
// DSS: app/Livewire/DynamicDashboard.php

<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\MetaModeladorService;
use App\Services\DynamicMetricCalculator;
use App\Services\ChartGeneratorService;

class DynamicDashboard extends Component
{
    public int $rubroId;
    public array $rubro = [];
    public array $entidades = [];
    public array $metrics = [];
    public array $charts = [];
    public string $selectedEntity = '';
    public array $filters = [];
    
    protected MetaModeladorService $configService;
    protected DynamicMetricCalculator $calculator;
    protected ChartGeneratorService $chartGenerator;
    
    public function boot(
        MetaModeladorService $configService,
        DynamicMetricCalculator $calculator,
        ChartGeneratorService $chartGenerator
    ) {
        $this->configService = $configService;
        $this->calculator = $calculator;
        $this->chartGenerator = $chartGenerator;
    }
    
    public function mount(int $rubroId)
    {
        $this->rubroId = $rubroId;
        $this->loadDashboard();
    }
    
    public function loadDashboard()
    {
        $config = $this->configService->getRubroConfiguration($this->rubroId);
        
        $this->rubro = $config['rubro'];
        $this->entidades = $config['entidades'];
        
        if (empty($this->selectedEntity) && !empty($this->entidades)) {
            $this->selectedEntity = $this->entidades[0]['nombre'];
        }
        
        $this->loadMetrics();
    }
    
    public function loadMetrics()
    {
        $this->metrics = $this->calculator->calculateMetrics($this->rubroId);
        
        // Generate chart configurations
        $this->charts = [];
        foreach ($this->metrics as $entityName => $entityMetrics) {
            $this->charts[$entityName] = [];
            
            foreach ($entityMetrics['categorical_distributions'] ?? [] as $field => $metric) {
                $this->charts[$entityName][$field] = $this->chartGenerator->generateChartConfig($metric);
            }
            
            foreach ($entityMetrics['time_series'] ?? [] as $field => $metric) {
                $this->charts[$entityName][$field] = $this->chartGenerator->generateChartConfig($metric);
            }
        }
    }
    
    public function updatedSelectedEntity()
    {
        $this->loadMetrics();
    }
    
    public function applyFilters()
    {
        // Re-calculate metrics with filters
        $this->metrics = $this->calculator->calculateMetricsWithFilters(
            $this->rubroId,
            $this->filters
        );
    }
    
    public function exportPdf()
    {
        // Implementation for PDF export
    }
    
    public function exportExcel()
    {
        // Implementation for Excel export
    }
    
    public function render()
    {
        return view('livewire.dynamic-dashboard', [
            'rubro' => $this->rubro,
            'entidades' => $this->entidades,
            'metrics' => $this->metrics,
            'charts' => $this->charts,
            'selectedEntityMetrics' => $this->metrics[$this->selectedEntity] ?? []
        ]);
    }
}
```

---

### Phase 4: Inventario Integration

#### MetaModeladorClient for Flask

```python
# Inventario: services/meta_modelador_client.py

import requests
import hashlib
import hmac
import json
import logging
from typing import Dict, List, Optional
from functools import wraps
from flask import current_app, request, jsonify
from datetime import datetime

logger = logging.getLogger(__name__)


class MetaModeladorClient:
    """Client for communicating with Meta Modelador API."""
    
    def __init__(self, base_url: str, api_key: str, timeout: int = 10, max_retries: int = 3):
        self.base_url = base_url.rstrip('/')
        self.api_key = api_key
        self.timeout = timeout
        self.max_retries = max_retries
        self.session = requests.Session()
        self.session.headers.update({
            'X-API-Key': api_key,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })
    
    def _request(self, method: str, endpoint: str, **kwargs) -> Dict:
        """Make authenticated request with retry logic."""
        url = f"{self.base_url}{endpoint}"
        attempt = 0
        last_error = None
        
        while attempt < self.max_retries:
            try:
                response = self.session.request(
                    method,
                    url,
                    timeout=self.timeout,
                    **kwargs
                )
                
                if response.status_code == 401:
                    raise MetaModeladorAuthError("Authentication failed")
                
                response.raise_for_status()
                return response.json()
                
            except requests.exceptions.RequestException as e:
                last_error = e
                attempt += 1
                
                if attempt < self.max_retries:
                    # Exponential backoff
                    import time
                    time.sleep(2 ** attempt)
                    
        raise MetaModeladorConnectionError(
            f"Failed after {attempt} attempts: {last_error}"
        )
    
    def get_rubros(self) -> List[Dict]:
        """Get all rubros."""
        return self._request('GET', '/api/v1/rubros')
    
    def get_rubro(self, rubro_id: int) -> Dict:
        """Get rubro by ID."""
        return self._request('GET', f'/api/v1/rubros/{rubro_id}')
    
    def get_entidades(self, rubro_id: int) -> List[Dict]:
        """Get all entidades for a rubro."""
        return self._request('GET', f'/api/v1/rubros/{rubro_id}/entidades')
    
    def get_campos(self, entidad_id: int) -> List[Dict]:
        """Get all campos for an entidad."""
        return self._request('GET', f'/api/v1/entidades/{entidad_id}/campos')
    
    def get_full_configuration(self) -> Dict:
        """Get complete configuration in single request."""
        return self._request('GET', '/api/v1/configuration/full')


class MetaModeladorAuthError(Exception):
    """Raised when authentication fails."""
    pass


class MetaModeladorConnectionError(Exception):
    """Raised when connection fails after retries."""
    pass


def get_meta_modelador_client() -> MetaModeladorClient:
    """Factory function to create client from app config."""
    return MetaModeladorClient(
        base_url=current_app.config['META_MODELADOR_URL'],
        api_key=current_app.config['META_MODELADOR_API_KEY'],
        timeout=current_app.config.get('META_MODELADOR_TIMEOUT', 10),
        max_retries=current_app.config.get('META_MODELADOR_RETRIES', 3)
    )
```

**Dynamic Schema Validation**

```python
# Inventario: services/schema_validator.py

from typing import Dict, List, Any, Optional
from datetime import datetime, date
from decimal import Decimal
import re

class DynamicSchemaValidator:
    """Validates data against dynamic campo definitions."""
    
    TYPE_VALIDATORS = {
        'string': str,
        'text': str,
        'integer': int,
        'float': float,
        'boolean': bool,
        'date': date,
        'datetime': datetime,
        'email': str,
        'currency': (int, float, Decimal),
        'select': str
    }
    
    def __init__(self, campos: List[Dict]):
        self.campos = {c['nombre_fisico']: c for c in campos}
    
    def validate(self, data: Dict) -> Dict[str, Any]:
        """
        Validate data against campo definitions.
        Returns validated and sanitized data.
        Raises ValueError if validation fails.
        """
        validated = {}
        errors = []
        
        for nombre_fisico, campo in self.campos.items():
            value = data.get(nombre_fisico)
            
            # Check required fields
            if campo.get('es_requerido') and value is None:
                errors.append(f"{campo['etiqueta']} es requerido")
                continue
            
            # Skip null values for optional fields
            if value is None:
                validated[nombre_fisico] = campo.get('valor_por_defecto')
                continue
            
            # Type validation
            try:
                validated[nombre_fisico] = self._validate_type(value, campo)
            except ValueError as e:
                errors.append(str(e))
            
            # Custom validations
            if 'validaciones' in campo:
                custom_errors = self._validate_custom(value, campo)
                errors.extend(custom_errors)
        
        if errors:
            raise ValueError('; '.join(errors))
        
        return validated
    
    def _validate_type(self, value: Any, campo: Dict) -> Any:
        """Validate and convert value to correct type."""
        tipo = campo['tipo']
        
        if tipo == 'integer':
            return int(value)
        
        elif tipo == 'float':
            return float(value)
        
        elif tipo == 'boolean':
            if isinstance(value, bool):
                return value
            if isinstance(value, str):
                return value.lower() in ('true', '1', 'yes', 'sí')
            return bool(value)
        
        elif tipo == 'date':
            if isinstance(value, date):
                return value
            if isinstance(value, str):
                return datetime.strptime(value, '%Y-%m-%d').date()
            raise ValueError(f"Invalid date format for {campo['etiqueta']}")
        
        elif tipo == 'datetime':
            if isinstance(value, datetime):
                return value
            if isinstance(value, str):
                return datetime.fromisoformat(value)
            raise ValueError(f"Invalid datetime format for {campo['etiqueta']}")
        
        elif tipo == 'email':
            if not re.match(r'^[\w\.-]+@[\w\.-]+\.\w+$', str(value)):
                raise ValueError(f"Invalid email format for {campo['etiqueta']}")
            return str(value)
        
        elif tipo == 'select':
            opciones = campo.get('opciones', [])
            if opciones and str(value) not in opciones:
                raise ValueError(f"Invalid option for {campo['etiqueta']}: {value}")
            return str(value)
        
        elif tipo == 'currency':
            return Decimal(str(value))
        
        else:  # string, text
            return str(value)
    
    def _validate_custom(self, value: Any, campo: Dict) -> List[str]:
        """Apply custom validation rules."""
        errors = []
        validaciones = campo.get('validaciones', {})
        
        if 'min_length' in validaciones and len(str(value)) < validaciones['min_length']:
            errors.append(f"{campo['etiqueta']} must be at least {validaciones['min_length']} characters")
        
        if 'max_length' in validaciones and len(str(value)) > validaciones['max_length']:
            errors.append(f"{campo['etiqueta']} must be at most {validaciones['max_length']} characters")
        
        if 'min_value' in validaciones and float(value) < validaciones['min_value']:
            errors.append(f"{campo['etiqueta']} must be at least {validaciones['min_value']}")
        
        if 'max_value' in validaciones and float(value) > validaciones['max_value']:
            errors.append(f"{campo['etiqueta']} must be at most {validaciones['max_value']}")
        
        if 'pattern' in validaciones and not re.match(validaciones['pattern'], str(value)):
            errors.append(f"{campo['etiqueta']} has invalid format")
        
        return errors
```

**Cross-Module Event Synchronization**

```python
# Inventario: services/event_publisher.py

import json
import logging
from typing import Dict, Any
from datetime import datetime

logger = logging.getLogger(__name__)


class EventPublisher:
    """Publishes events to message queue for cross-module synchronization."""
    
    def __init__(self, redis_client):
        self.redis = redis_client
        self.stream_key = 'siges:events'
    
    def publish(self, event_type: str, rubro_id: int, entidad: str, data: Dict[str, Any]) -> str:
        """
        Publish an event to the message queue.
        
        Args:
            event_type: Type of event (created, updated, deleted)
            rubro_id: Rubro identifier
            entidad: Entity name
            data: Event payload
            
        Returns:
            Event ID from Redis Stream
        """
        event = {
            'event_type': event_type,
            'rubro_id': rubro_id,
            'entidad': entidad,
            'data': json.dumps(data, default=str),
            'timestamp': datetime.utcnow().isoformat(),
            'source': 'inventario'
        }
        
        try:
            event_id = self.redis.xadd(self.stream_key, event)
            logger.info(f"Published event: {event_type} for {entidad} in rubro {rubro_id}")
            return event_id
        except Exception as e:
            logger.error(f"Failed to publish event: {e}")
            raise
    
    def publish_inventory_update(self, rubro_id: int, producto_id: int, changes: Dict):
        """Publish inventory update event."""
        self.publish(
            event_type='inventory_updated',
            rubro_id=rubro_id,
            entidad='producto',
            data={
                'producto_id': producto_id,
                'changes': changes
            }
        )
    
    def publish_stock_change(self, rubro_id: int, producto_id: int, old_quantity: int, new_quantity: int):
        """Publish stock level change event."""
        self.publish(
            event_type='stock_changed',
            rubro_id=rubro_id,
            entidad='inventario',
            data={
                'producto_id': producto_id,
                'old_quantity': old_quantity,
                'new_quantity': new_quantity,
                'change': new_quantity - old_quantity
            }
        )


# DSS: Event Consumer (Laravel)
# app/Services/EventConsumerService.php

<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EventConsumerService
{
    private string $streamKey = 'siges:events';
    private string $consumerGroup = 'dss-consumer';
    private string $consumerName = 'dss-1';
    
    public function __construct()
    {
        // Ensure consumer group exists
        try {
            Redis::execute('XGROUP', ['CREATE', $this->streamKey, $this->consumerGroup, '$', 'MKSTREAM']);
        } catch (\Exception $e) {
            // Group already exists
        }
    }
    
    public function consume(int $count = 10, int $block = 5000): array
    {
        $messages = Redis::execute('XREADGROUP', [
            'GROUP',
            $this->consumerGroup,
            $this->consumerName,
            'COUNT',
            $count,
            'BLOCK',
            $block,
            'STREAMS',
            $this->streamKey,
            '>'
        ]);
        
        $processed = [];
        
        if ($messages) {
            foreach ($messages[1] ?? [] as $message) {
                $eventId = $message[0];
                $eventData = $message[1];
                
                try {
                    $this->handleEvent($eventData);
                    $this->acknowledge($eventId);
                    $processed[] = $eventId;
                } catch (\Exception $e) {
                    Log::error("Failed to process event {$eventId}: " . $e->getMessage());
                }
            }
        }
        
        return $processed;
    }
    
    private function handleEvent(array $eventData): void
    {
        $eventType = $eventData['event_type'];
        
        switch ($eventType) {
            case 'inventory_updated':
                $this->handleInventoryUpdate($eventData);
                break;
                
            case 'stock_changed':
                $this->handleStockChange($eventData);
                break;
        }
    }
    
    private function handleInventoryUpdate(array $event): void
    {
        // Invalidate cache for this rubro
        $rubroId = $event['rubro_id'];
        
        app(MetaModeladorService::class)->invalidateRubroCache($rubroId);
        
        Log::info("Cache invalidated for rubro {$rubroId} due to inventory update");
    }
    
    private function handleStockChange(array $event): void
    {
        // Update cached metrics
        // Implementation depends on caching strategy
    }
    
    private function acknowledge(string $eventId): void
    {
        Redis::execute('XACK', [$this->streamKey, $this->consumerGroup, $eventId]);
    }
}
```

**JWT Unified Authentication**

```python
# Shared: auth/jwt_handler.py

import jwt
from datetime import datetime, timedelta
from typing import Dict, Optional
from functools import wraps
from flask import request, jsonify, current_app

class JWTHandler:
    """Handles JWT token generation and validation for unified authentication."""
    
    ALGORITHM = 'HS256'
    TOKEN_EXPIRY_HOURS = 8
    
    @staticmethod
    def generate_token(user_id: int, email: str, role: str, rubros: list) -> str:
        """Generate JWT token for authenticated user."""
        payload = {
            'sub': user_id,
            'email': email,
            'role': role,
            'rubros': rubros,
            'iat': datetime.utcnow(),
            'exp': datetime.utcnow() + timedelta(hours=JWTHandler.TOKEN_EXPIRY_HOURS),
            'iss': 'siges-auth'
        }
        
        return jwt.encode(payload, current_app.config['JWT_SECRET'], algorithm=JWTHandler.ALGORITHM)
    
    @staticmethod
    def validate_token(token: str) -> Optional[Dict]:
        """Validate JWT token and return payload."""
        try:
            payload = jwt.decode(
                token,
                current_app.config['JWT_SECRET'],
                algorithms=[JWTHandler.ALGORITHM],
                issuer='siges-auth'
            )
            return payload
        except jwt.ExpiredSignatureError:
            return None
        except jwt.InvalidTokenError:
            return None
    
    @staticmethod
    def get_current_user() -> Optional[Dict]:
        """Get current user from JWT token in request."""
        auth_header = request.headers.get('Authorization', '')
        
        if not auth_header.startswith('Bearer '):
            return None
        
        token = auth_header.split(' ')[1]
        return JWTHandler.validate_token(token)


def jwt_required(f):
    """Decorator to require JWT authentication."""
    @wraps(f)
    def decorated(*args, **kwargs):
        user = JWTHandler.get_current_user()
        
        if not user:
            return jsonify({
                'error': 'Authentication required',
                'code': 'UNAUTHORIZED'
            }), 401
        
        # Attach user to request context
        request.current_user = user
        
        return f(*args, **kwargs)
    
    return decorated


def role_required(*required_roles):
    """Decorator to require specific role."""
    def decorator(f):
        @wraps(f)
        @jwt_required
        def decorated(*args, **kwargs):
            user = request.current_user
            
            if user['role'] not in required_roles:
                return jsonify({
                    'error': 'Insufficient permissions',
                    'code': 'FORBIDDEN'
                }), 403
            
            return f(*args, **kwargs)
        
        return decorated
    return decorator
```

---

## Data Models

### Meta Modelador Database Schema

```sql
-- PostgreSQL Schema for Meta Modelador

-- API Keys Table
CREATE TABLE api_keys (
    id SERIAL PRIMARY KEY,
    module_name VARCHAR(50) UNIQUE NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    previous_key_hash VARCHAR(255),
    rotation_grace_until TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP,
    created_by INTEGER REFERENCES usuarios(id)
);

-- Audit Log Table
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(50),
    user_id INTEGER,
    ip_address VARCHAR(45),
    details JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for efficient queries
CREATE INDEX idx_audit_logs_event_type ON audit_logs(event_type);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- Rubros Table (existing, extended)
CREATE TABLE rubros (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT,
    configuracion_base JSONB DEFAULT '{}',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Entidades Table (existing, extended)
CREATE TABLE entidades (
    id SERIAL PRIMARY KEY,
    rubro_id INTEGER NOT NULL REFERENCES rubros(id) ON DELETE CASCADE,
    nombre VARCHAR(50) NOT NULL,
    nombre_tabla VARCHAR(50) NOT NULL,
    nombre_plural VARCHAR(50),
    icono VARCHAR(20) DEFAULT '📦',
    descripcion VARCHAR(200),
    modulo VARCHAR(30) DEFAULT 'erp',
    orden INTEGER DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(rubro_id, nombre)
);

-- Campos Table (existing, extended)
CREATE TABLE campos (
    id SERIAL PRIMARY KEY,
    entidad_id INTEGER NOT NULL REFERENCES entidades(id) ON DELETE CASCADE,
    nombre VARCHAR(50) NOT NULL,
    nombre_fisico VARCHAR(50) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    etiqueta VARCHAR(100),
    placeholder VARCHAR(200),
    descripcion VARCHAR(200),
    es_requerido BOOLEAN DEFAULT FALSE,
    es_unico BOOLEAN DEFAULT FALSE,
    es_indice BOOLEAN DEFAULT FALSE,
    es_busqueda BOOLEAN DEFAULT FALSE,
    es_filtrable BOOLEAN DEFAULT FALSE,
    valor_por_defecto VARCHAR(100),
    opciones JSONB,
    validaciones JSONB DEFAULT '{}',
    orden INTEGER DEFAULT 0,
    visible_en_tabla BOOLEAN DEFAULT TRUE,
    visible_en_formulario BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(entidad_id, nombre),
    UNIQUE(entidad_id, nombre_fisico)
);

-- Schema Metadata Table (for Configuration Wizard)
CREATE TABLE schema_metadata (
    id SERIAL PRIMARY KEY,
    module_name VARCHAR(50) NOT NULL,
    database_type VARCHAR(20) NOT NULL,  -- mysql, postgresql
    connection_config JSONB NOT NULL,    -- encrypted
    scanned_tables JSONB,
    last_scan TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### DSS Database Changes

```sql
-- MySQL Schema Additions for DSS

-- Module Configuration Table
CREATE TABLE module_configuration (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL,
    value TEXT NOT NULL,
    encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_module_config_key (key)
);

-- API Key Storage (encrypted)
INSERT INTO module_configuration (`key`, `value`, `encrypted`)
VALUES ('meta_modelador_api_key', '<encrypted_value>', TRUE);

-- Health Check Log Table
CREATE TABLE health_check_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'unhealthy') NOT NULL,
    response_time_ms INT,
    error_message TEXT,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_health_check_service (service),
    INDEX idx_health_check_checked_at (checked_at)
);

-- Dashboard Configuration Cache Table
CREATE TABLE dashboard_config_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rubro_id INT NOT NULL,
    config_json LONGTEXT NOT NULL,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_dashboard_cache_rubro (rubro_id),
    INDEX idx_dashboard_cache_expires (expires_at)
);

-- Audit Log Table (sync with Meta Modelador)
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(50),
    user_id BIGINT UNSIGNED,
    ip_address VARCHAR(45),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_event_type (event_type),
    INDEX idx_audit_created_at (created_at)
);
```

### Inventario Database Changes

```sql
-- PostgreSQL Schema Additions for Inventario

-- Module Configuration Table
CREATE TABLE module_config (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT NOT NULL,
    encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cache Table for Meta Modelador Configuration
CREATE TABLE config_cache (
    id SERIAL PRIMARY KEY,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    cache_value JSONB NOT NULL,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_config_cache_key ON config_cache(cache_key);
CREATE INDEX idx_config_cache_expires ON config_cache(expires_at);

-- Sync Event Log
CREATE TABLE sync_event_log (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    rubro_id INTEGER NOT NULL,
    entidad VARCHAR(50) NOT NULL,
    event_data JSONB,
    processed BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sync_event_processed ON sync_event_log(processed);
CREATE INDEX idx_sync_event_created ON sync_event_log(created_at);
```

---

## Error Handling

### Error Response Format

All modules use a consistent error response format:

```json
{
    "error": "Human-readable error message",
    "code": "ERROR_CODE",
    "details": {
        "field": "additional_context"
    },
    "request_id": "uuid-for-tracing"
}
```

### Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `MISSING_API_KEY` | 401 | API key not provided |
| `INVALID_API_KEY` | 401 | API key validation failed |
| `API_KEY_EXPIRED` | 401 | API key has expired |
| `UNAUTHORIZED` | 401 | JWT token missing or invalid |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `CONFIGURATION_NOT_FOUND` | 404 | Rubro/entidad/campo not found |
| `CONNECTION_ERROR` | 503 | Unable to connect to Meta Modelador |
| `CACHE_ERROR` | 500 | Cache operation failed |

### Graceful Degradation Strategy

```php
// DSS: Graceful Degradation Implementation

class GracefulDegradationHandler
{
    public function handleMetaModeladorUnavailable(callable $fallback): mixed
    {
        try {
            return $this->executeWithFallback($fallback);
        } catch (MetaModeladorConnectionException $e) {
            // 1. Try stale cache
            $staleData = $this->getStaleCache();
            if ($staleData) {
                Log::warning('Using stale cache due to Meta Modelador unavailability');
                return $staleData;
            }
            
            // 2. Return maintenance page
            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Service temporarily unavailable',
                    'code' => 'SERVICE_UNAVAILABLE',
                    'retry_after' => 60
                ], 503);
            }
            
            return response()->view('errors.maintenance', [], 503);
        }
    }
}
```

---

## Testing Strategy

### Unit Tests

Unit tests verify specific examples, edge cases, and error conditions.

```php
// DSS: tests/Unit/MetaModeladorServiceTest.php

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MetaModeladorService;
use App\Services\MetaModeladorApiClient;
use Illuminate\Support\Facades\Cache;

class MetaModeladorServiceTest extends TestCase
{
    private MetaModeladorService $service;
    private MetaModeladorApiClient $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = $this->mock(MetaModeladorApiClient::class);
        $this->service = new MetaModeladorService($this->client);
    }
    
    public function test_get_rubros_returns_cached_data()
    {
        $rubros = [
            ['id' => 1, 'nombre' => 'Retail'],
            ['id' => 2, 'nombre' => 'Manufacturing']
        ];
        
        $this->client->expects('getRubros')
            ->once()
            ->andReturn($rubros);
        
        // First call - should fetch from API
        $result = $this->service->getRubros();
        $this->assertEquals($rubros, $result);
        
        // Second call - should return cached
        $result2 = $this->service->getRubros();
        $this->assertEquals($rubros, $result2);
    }
    
    public function test_invalid_api_key_throws_exception()
    {
        $this->client->expects('getRubros')
            ->andThrow(new MetaModeladorAuthException('Invalid API key'));
        
        $this->expectException(MetaModeladorAuthException::class);
        
        $this->service->getRubros();
    }
    
    public function test_connection_failure_uses_stale_cache()
    {
        $staleData = [['id' => 1, 'nombre' => 'Retail']];
        
        Cache::put('stale:siges:rubros:all', $staleData, 86400);
        
        $this->client->expects('getRubros')
            ->andThrow(new MetaModeladorConnectionException('Connection failed'));
        
        $result = $this->service->getRubros();
        
        $this->assertEquals($staleData, $result);
    }
}
```

```python
# Inventario: tests/test_schema_validator.py

import pytest
from services.schema_validator import DynamicSchemaValidator

class TestDynamicSchemaValidator:
    
    @pytest.fixture
    def campos(self):
        return [
            {
                'nombre': 'nombre',
                'nombre_fisico': 'nombre',
                'tipo': 'string',
                'etiqueta': 'Nombre',
                'es_requerido': True
            },
            {
                'nombre': 'precio',
                'nombre_fisico': 'precio',
                'tipo': 'currency',
                'etiqueta': 'Precio',
                'es_requerido': True,
                'validaciones': {'min_value': 0}
            },
            {
                'nombre': 'cantidad',
                'nombre_fisico': 'cantidad',
                'tipo': 'integer',
                'etiqueta': 'Cantidad',
                'es_requerido': False,
                'valor_por_defecto': 0
            }
        ]
    
    def test_validate_required_fields(self, campos):
        validator = DynamicSchemaValidator(campos)
        
        with pytest.raises(ValueError, match='Nombre es requerido'):
            validator.validate({'precio': 100})
    
    def test_validate_type_conversion(self, campos):
        validator = DynamicSchemaValidator(campos)
        
        result = validator.validate({
            'nombre': 'Producto Test',
            'precio': '99.99',
            'cantidad': '10'
        })
        
        assert result['precio'] == Decimal('99.99')
        assert result['cantidad'] == 10
    
    def test_validate_min_value(self, campos):
        validator = DynamicSchemaValidator(campos)
        
        with pytest.raises(ValueError, match='must be at least 0'):
            validator.validate({
                'nombre': 'Test',
                'precio': -10
            })
```

### Integration Tests

Integration tests verify end-to-end flows with real services (or Docker containers).

```php
// DSS: tests/Feature/DashboardIntegrationTest.php

<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class DashboardIntegrationTest extends TestCase
{
    public function test_dashboard_loads_with_rubro_configuration()
    {
        // Mock Meta Modelador API
        Http::fake([
            '*/api/v1/rubros/1' => Http::response([
                'id' => 1,
                'nombre' => 'Retail',
                'entidades' => [
                    [
                        'id' => 1,
                        'nombre' => 'Producto',
                        'nombre_tabla' => 'productos',
                        'campos' => [
                            ['nombre_fisico' => 'nombre', 'tipo' => 'string'],
                            ['nombre_fisico' => 'precio', 'tipo' => 'currency']
                        ]
                    ]
                ]
            ])
        ]);
        
        $response = $this->get('/dashboard/retail');
        
        $response->assertStatus(200);
        $response->assertSee('Retail');
        $response->assertSee('Producto');
    }
}
```

### API Endpoint Tests

```python
# Meta Modelador: tests/test_api_endpoints.py

import pytest
from app import create_app
from models import db, Rubro, Entidad, Campo, APIKey

@pytest.fixture
def app():
    app = create_app(testing=True)
    with app.app_context():
        db.create_all()
        yield app
        db.drop_all()

@pytest.fixture
def client(app):
    return app.test_client()

@pytest.fixture
def api_key(app):
    with app.app_context():
        key = APIKey(
            module_name='dss',
            key_hash=bcrypt.hashpw('test-api-key'.encode(), bcrypt.gensalt()).decode()
        )
        db.session.add(key)
        db.session.commit()
        return 'test-api-key'

class TestAPIKeyAuthentication:
    
    def test_missing_api_key_returns_401(self, client):
        response = client.get('/api/v1/rubros')
        assert response.status_code == 401
        assert response.json['code'] == 'MISSING_API_KEY'
    
    def test_invalid_api_key_returns_401(self, client):
        response = client.get('/api/v1/rubros', headers={
            'X-API-Key': 'invalid-key'
        })
        assert response.status_code == 401
        assert response.json['code'] == 'INVALID_API_KEY'
    
    def test_valid_api_key_allows_access(self, client, api_key):
        response = client.get('/api/v1/rubros', headers={
            'X-API-Key': api_key
        })
        assert response.status_code == 200

class TestRubroEndpoints:
    
    def test_get_rubros(self, client, api_key):
        # Setup
        with client.application.app_context():
            rubro = Rubro(nombre='Retail', descripcion='Test rubro')
            db.session.add(rubro)
            db.session.commit()
        
        response = client.get('/api/v1/rubros', headers={
            'X-API-Key': api_key
        })
        
        assert response.status_code == 200
        assert len(response.json) == 1
        assert response.json[0]['nombre'] == 'Retail'
```

---

## Security Considerations

### API Key Security

1. **Generation**: Use cryptographically secure random generation (secrets module)
2. **Storage**: Never store plaintext keys; use bcrypt or argon2 hashing
3. **Transmission**: Only via HTTPS with X-API-Key header
4. **Rotation**: Support grace period for zero-downtime rotation
5. **Logging**: Never log API keys; log only hash references

### JWT Security

1. **Secret**: Use strong, shared secret across all modules (minimum 256 bits)
2. **Algorithm**: Use HS256 with proper key management
3. **Expiration**: 8 hours maximum; refresh tokens for longer sessions
4. **Claims**: Include minimal necessary claims (user_id, role, rubros)
5. **Revocation**: Implement token blacklist for logout

### Data Security

1. **Encryption at Rest**: API keys stored encrypted
2. **Encryption in Transit**: TLS 1.2+ for all inter-module communication
3. **Input Validation**: All user input validated against campo definitions
4. **SQL Injection Prevention**: Parameterized queries only
5. **Rate Limiting**: 100 requests per minute per module

### Audit Logging

All authentication attempts, configuration changes, and administrative actions are logged with:

- Timestamp
- Module name
- User ID
- IP address
- Event details (JSON)

---

## Deployment Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PRODUCTION DEPLOYMENT                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                         LOAD BALANCER                                │    │
│  │                     (NGINX / CloudFlare)                             │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                      │                                       │
│              ┌───────────────────────┼───────────────────────┐              │
│              │                       │                       │              │
│              ▼                       ▼                       ▼              │
│  ┌───────────────────┐   ┌───────────────────┐   ┌───────────────────┐     │
│  │  Meta Modelador   │   │       DSS         │   │    Inventario     │     │
│  │  (Flask/Gunicorn) │   │ (Laravel/FPM)     │   │ (Flask/Gunicorn)  │     │
│  │  Port: 5001       │   │  Port: 8000       │   │  Port: 5000       │     │
│  │                   │   │                   │   │                   │     │
│  │  ┌─────────────┐  │   │  ┌─────────────┐  │   │  ┌─────────────┐  │     │
│  │  │  Container  │  │   │  │  Container  │  │   │  │  Container  │  │     │
│  │  │  Instance 1 │  │   │  │  Instance 1 │  │   │  │  Instance 1 │  │     │
│  │  └─────────────┘  │   │  └─────────────┘  │   │  └─────────────┘  │     │
│  │  ┌─────────────┐  │   │  ┌─────────────┐  │   │  ┌─────────────┐  │     │
│  │  │  Container  │  │   │  │  Container  │  │   │  │  Container  │  │     │
│  │  │  Instance 2 │  │   │  │  Instance 2 │  │   │  │  Instance 2 │  │     │
│  │  └─────────────┘  │   │  └─────────────┘  │   │  └─────────────┘  │     │
│  └───────────────────┘   └───────────────────┘   └───────────────────┘     │
│              │                       │                       │              │
│              ▼                       ▼                       ▼              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                         DATA LAYER                                   │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │    │
│  │  │  PostgreSQL │  │   MySQL     │  │   Redis     │  │  RabbitMQ  │ │    │
│  │  │  (Meta Inv) │  │   (DSS)     │  │   (Cache)   │  │  (Events)  │ │    │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └────────────┘ │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: API Key Uniqueness and Format

*For all* API keys generated by Meta_Modelador, each key SHALL be unique, have a minimum length of 32 characters, and contain only alphanumeric characters from a cryptographically secure random source.

**Validates: Requirements 1.1, 3.1**

### Property 2: API Key Hash Storage

*For all* API keys stored in the Meta_Modelador database, the stored value SHALL be a bcrypt or argon2 hash, and the plaintext key SHALL NOT be present in any database field.

**Validates: Requirements 1.3, 1.4**

### Property 3: API Key Authentication Response

*For all* requests to Meta_Modelador API endpoints (excluding exempt routes), if the `X-API-Key` header is missing or contains an invalid key, the response SHALL be HTTP 401 with error code `MISSING_API_KEY` or `INVALID_API_KEY`.

**Validates: Requirements 2.1, 2.2**

### Property 4: API Key Grace Period Acceptance

*For all* API key rotation operations, during the grace period both the old and new API keys SHALL be accepted for authentication, and after the grace period expires, only the new key SHALL be accepted.

**Validates: Requirements 3.2, 3.3**

### Property 5: API Key Non-Exposure

*For all* log entries and user-facing responses in DSS and Inventario, the API key plaintext SHALL NOT appear in any logged message or displayed output.

**Validates: Requirements 4.2, 5.2**

### Property 6: API Key Header Presence

*For all* outgoing requests from DSS and Inventario to Meta_Modelador, the `X-API-Key` header SHALL be present and contain a non-empty value when the API key is configured.

**Validates: Requirements 4.3, 5.3**

### Property 7: Configuration Encryption at Rest

*For all* configuration values stored by DSS and Inventario (API keys, database credentials), the values SHALL be stored in encrypted form using AES-256 or equivalent.

**Validates: Requirements 4.1, 5.1, 6.4**

### Property 8: Schema Scanner System Table Exclusion

*For all* schema scan results from MySQL databases, no tables from `mysql`, `information_schema`, `performance_schema`, or `sys` databases SHALL be included in the output.

**Validates: Requirements 7.1, 7.2**

### Property 9: Schema Scanner Metadata Completeness

*For all* tables discovered by the Schema Scanner, each table entry SHALL include column names, data types, nullable status, and foreign key relationships.

**Validates: Requirements 7.3, 8.3**

### Property 10: Rubro Name Validation

*For all* rubro names accepted by the Rubro_Manager, the name SHALL match the pattern `^[a-zA-Z0-9_-]+$` and SHALL be unique within the system.

**Validates: Requirements 9.1, 9.2**

### Property 11: Rubro ID Generation

*For all* newly created rubros, the system SHALL generate a unique identifier and record a valid timestamp within 1 second of creation.

**Validates: Requirements 9.4**

### Property 12: Entity Uniqueness Within Rubro

*For all* entities created within a rubro, the entity name SHALL be unique within that rubro context.

**Validates: Requirements 10.1**

### Property 13: Campo Auto-Generation from Table

*For all* entity creations where a source table is selected, the system SHALL create a campo definition for each column in the source table with correct data type mapping.

**Validates: Requirements 10.3**

### Property 14: Campo Required Field Validation

*For all* saved entities, every campo marked as required SHALL have a non-empty display name.

**Validates: Requirements 10.5**

### Property 15: Bridge Client Retry Behavior

*For all* failed network requests from DSS_Bridge_Client, the client SHALL retry up to 3 times with exponential backoff intervals (approximately 1s, 2s, 4s) before raising an exception.

**Validates: Requirements 13.2, 13.3**

### Property 16: Cache TTL Enforcement

*For all* cached configuration responses in DSS, the cached data SHALL be returned unchanged until the TTL expires, after which fresh data SHALL be fetched from Meta_Modelador.

**Validates: Requirements 14.1, 14.2, 14.3**

### Property 17: Webhook Signature Validity

*For all* webhooks sent from Meta_Modelador to DSS, the signature header SHALL be a valid HMAC-SHA256 of the request payload using the shared API key.

**Validates: Requirements 15.2**

### Property 18: Webhook Signature Verification

*For all* webhooks received by DSS with an invalid or missing signature, the response SHALL be HTTP 401 and the event SHALL be logged as a security event.

**Validates: Requirements 15.3, 15.4**

### Property 19: API Endpoint Authorization

*For all* requests to Meta_Modelador configuration endpoints, a valid API key SHALL be required and HTTP 401 SHALL be returned for invalid or missing keys.

**Validates: Requirements 16.1-16.5**

### Property 20: Dashboard Rubro Validation

*For all* requests to `/dashboard/{rubro}`, the system SHALL verify the rubro exists in the configuration before rendering; non-existent rubros SHALL return HTTP 404.

**Validates: Requirements 18.1, 18.2**

### Property 21: Dashboard Authorization

*For all* dashboard access attempts, the system SHALL verify the user's role permits access to the requested rubro dashboard.

**Validates: Requirements 18.4**

### Property 22: Entity List Pagination

*For all* entity listing requests, the system SHALL return at most the configured page size (default 25) records per page, and SHALL support pagination via offset/limit parameters.

**Validates: Requirements 19.3**

### Property 23: Metric Value Formatting

*For all* metric calculations, the formatted output SHALL match the defined display type: currency values prefixed with `$` and two decimal places, percentages with `%` suffix, integers without decimals.

**Validates: Requirements 20.3**

### Property 24: Dynamic Form Field Rendering

*For all* campo definitions of type `string`, `text`, `email`, the Product_Schema_Loader SHALL render a text input; for `integer`, `float`, `currency` types, a number input; for `date` type, a date picker; for `select` type, a dropdown.

**Validates: Requirements 24.2**

### Property 25: Dynamic Field Validation

*For all* campos marked as required in the entity definition, form submission SHALL be rejected if the campo value is empty or null.

**Validates: Requirements 24.3, 24.5**

### Property 26: Event Payload Structure

*For all* synchronization events published by Inventario, the payload SHALL contain `rubro_id`, `entidad`, and `changed_fields` keys with valid values.

**Validates: Requirements 26.3**

### Property 27: Event Retry Behavior

*For all* event publication failures due to message queue unavailability, the Sync_Notifier SHALL retry with exponential backoff up to a configurable maximum.

**Validates: Requirements 26.4**

### Property 28: JWT Token Claims

*For all* JWT tokens issued by the Auth_Service, the token payload SHALL contain `sub` (user_id), `email`, `role`, and `rubros` claims, and SHALL be signed with the shared secret.

**Validates: Requirements 27.1, 27.3**

---

## Appendix: API Endpoint Reference

### Meta Modelador Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/v1/rubros` | GET | List all rubros | API Key |
| `/api/v1/rubros/{id}` | GET | Get rubro details | API Key |
| `/api/v1/rubros/{id}/entidades` | GET | Get entidades for rubro | API Key |
| `/api/v1/entidades/{id}/campos` | GET | Get campos for entidad | API Key |
| `/api/v1/configuration/full` | GET | Get complete configuration | API Key |
| `/api/v1/api-keys` | POST | Generate new API key | Admin Session |
| `/api/v1/api-keys/{id}/rotate` | POST | Rotate API key | Admin Session |
| `/api/health` | GET | Health check endpoint | None |

### DSS Webhook Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/webhooks/meta-modelador` | POST | Configuration change webhook | Signature |
| `/api/dashboard/{rubro}` | GET | Dynamic dashboard | JWT |
| `/api/dashboard/{rubro}/metrics` | GET | Dashboard metrics | JWT |
| `/api/dashboard/{rubro}/export` | GET | Export dashboard data | JWT |

### Inventario Event Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/products` | GET | List products (dynamic schema) | JWT |
| `/api/products` | POST | Create product | JWT |
| `/api/inventory` | GET | List inventory | JWT |
| `/api/inventory/sync` | POST | Trigger sync event | Internal |
