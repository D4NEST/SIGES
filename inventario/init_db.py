#!/usr/bin/env python3
"""
Script de inicialización de base de datos para el sistema de inventario
"""
import os
import sys
import psycopg2
from psycopg2 import sql
from urllib.parse import urlparse
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    """Establece conexión con PostgreSQL"""
    try:
        DATABASE_URL = os.environ.get('DATABASE_URL')
        
        if DATABASE_URL:
            # Conexión usando DATABASE_URL (Render/Heroku style)
            parsed_url = urlparse(DATABASE_URL)
            conn_params = {
                'host': parsed_url.hostname,
                'database': parsed_url.path[1:],
                'user': parsed_url.username,
                'password': parsed_url.password,
                'port': parsed_url.port,
                'sslmode': 'require'
            }
        else:
            # Conexión local
            conn_params = {
                'host': os.environ.get('DB_HOST', 'localhost'),
                'database': os.environ.get('DB_NAME', 'inventario_sistema'),
                'user': os.environ.get('DB_USER', 'postgres'),
                'password': os.environ.get('DB_PASS', 'password'),
                'port': os.environ.get('DB_PORT', '5432')
            }
        
        conn = psycopg2.connect(**conn_params)
        return conn
        
    except Exception as e:
        print(f"❌ Error conectando a la base de datos: {e}")
        return None

def create_tables(conn):
    """Crea las tablas necesarias para el sistema"""
    
    tables_sql = [
        # Tabla de tipos de pieza/categorías
        """
        CREATE TABLE IF NOT EXISTS tipos_pieza (
            tipo_id SERIAL PRIMARY KEY,
            tipo_modelo VARCHAR(100) NOT NULL UNIQUE,
            descripcion TEXT,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        """,
        
        # Tabla de productos
        """
        CREATE TABLE IF NOT EXISTS productos (
            producto_id SERIAL PRIMARY KEY,
            nombre VARCHAR(200) NOT NULL,
            marca VARCHAR(100),
            modelo VARCHAR(100),
            descripcion TEXT,
            tipo_pieza_id INTEGER REFERENCES tipos_pieza(tipo_id) ON DELETE SET NULL,
            codigo_sku VARCHAR(100) NOT NULL UNIQUE,
            precio_unitario DECIMAL(10,2) DEFAULT 0.00,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE
        );
        """,
        
        # Tabla de seriales
        """
        CREATE TABLE IF NOT EXISTS seriales (
            serial_id SERIAL PRIMARY KEY,
            producto_id INTEGER REFERENCES productos(producto_id) ON DELETE CASCADE,
            codigo_unico_serial VARCHAR(200) NOT NULL UNIQUE,
            estado VARCHAR(20) DEFAULT 'ALMACEN' CHECK (estado IN ('ALMACEN', 'INSTALADO', 'DAÑADO', 'RETIRADO')),
            ubicacion VARCHAR(200),
            notas TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        """,
        
        # Tabla de historial de estados (opcional, para auditoría)
        """
        CREATE TABLE IF NOT EXISTS historial_estados (
            historial_id SERIAL PRIMARY KEY,
            serial_id INTEGER REFERENCES seriales(serial_id) ON DELETE CASCADE,
            estado_anterior VARCHAR(20),
            estado_nuevo VARCHAR(20),
            usuario VARCHAR(100),
            notas TEXT,
            fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        """,
        
        # Índices para mejorar rendimiento
        """
        CREATE INDEX IF NOT EXISTS idx_productos_sku ON productos(codigo_sku);
        CREATE INDEX IF NOT EXISTS idx_productos_marca_modelo ON productos(marca, modelo);
        CREATE INDEX IF NOT EXISTS idx_seriales_codigo ON seriales(codigo_unico_serial);
        CREATE INDEX IF NOT EXISTS idx_seriales_estado ON seriales(estado);
        CREATE INDEX IF NOT EXISTS idx_seriales_producto ON seriales(producto_id);
        """
    ]
    
    try:
        cur = conn.cursor()
        
        for table_sql in tables_sql:
            cur.execute(table_sql)
            
        conn.commit()
        cur.close()
        print("✅ Tablas creadas exitosamente")
        return True
        
    except Exception as e:
        print(f"❌ Error creando tablas: {e}")
        conn.rollback()
        return False

def insert_default_data(conn):
    """Inserta datos por defecto en el sistema"""
    
    # Categorías por defecto
    categorias_default = [
        'Computadoras y Laptops',
        'Servidores y Mainframe',
        'Redes y Comunicaciones',
        'Almacenamiento',
        'Componentes de Hardware',
        'Periféricos',
        'Cables y Conectores',
        'Software y Licencias',
        'Equipos de Seguridad',
        'Fuentes de Poder y UPS',
        'Refacciones y Repuestos',
        'Consumibles'
    ]
    
    try:
        cur = conn.cursor()
        
        # Insertar categorías si no existen
        for categoria in categorias_default:
            cur.execute("""
                INSERT INTO tipos_pieza (tipo_modelo) 
                VALUES (%s) 
                ON CONFLICT (tipo_modelo) DO NOTHING
            """, (categoria,))
        
        conn.commit()
        cur.close()
        print("✅ Datos por defecto insertados")
        return True
        
    except Exception as e:
        print(f"❌ Error insertando datos por defecto: {e}")
        conn.rollback()
        return False

def verify_installation(conn):
    """Verifica que la instalación sea correcta"""
    try:
        cur = conn.cursor()
        
        # Verificar tablas
        cur.execute("""
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name IN ('tipos_pieza', 'productos', 'seriales', 'historial_estados')
            ORDER BY table_name;
        """)
        
        tables = [row[0] for row in cur.fetchall()]
        expected_tables = ['historial_estados', 'productos', 'seriales', 'tipos_pieza']
        
        if set(tables) == set(expected_tables):
            print("✅ Todas las tablas están presentes")
        else:
            print(f"⚠️ Tablas faltantes: {set(expected_tables) - set(tables)}")
        
        # Verificar categorías
        cur.execute("SELECT COUNT(*) FROM tipos_pieza")
        count = cur.fetchone()[0]
        print(f"✅ Categorías disponibles: {count}")
        
        cur.close()
        return True
        
    except Exception as e:
        print(f"❌ Error verificando instalación: {e}")
        return False

def main():
    """Función principal"""
    print("🚀 Inicializando base de datos del sistema de inventario...")
    print("=" * 60)
    
    # Conectar a la base de datos
    conn = get_db_connection()
    if not conn:
        print("❌ No se pudo conectar a la base de datos")
        sys.exit(1)
    
    print("✅ Conexión a base de datos establecida")
    
    # Crear tablas
    if not create_tables(conn):
        print("❌ Error creando tablas")
        conn.close()
        sys.exit(1)
    
    # Insertar datos por defecto
    if not insert_default_data(conn):
        print("❌ Error insertando datos por defecto")
        conn.close()
        sys.exit(1)
    
    # Verificar instalación
    if not verify_installation(conn):
        print("❌ Error verificando instalación")
        conn.close()
        sys.exit(1)
    
    conn.close()
    
    print("=" * 60)
    print("🎉 ¡Base de datos inicializada correctamente!")
    print("📋 Próximos pasos:")
    print("   1. Ejecutar: python app.py")
    print("   2. Abrir: http://localhost:10000")
    print("   3. Login con: admin / 02120212$")
    print("=" * 60)

if __name__ == "__main__":
    main()