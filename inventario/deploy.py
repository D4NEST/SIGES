#!/usr/bin/env python3
"""
Script de deployment seguro para el sistema de inventario
"""
import os
import sys
import subprocess
import shutil
import secrets
import hashlib
from datetime import datetime

class SecureDeployer:
    """Gestor de deployment seguro"""
    
    def __init__(self):
        self.deployment_dir = 'deployment'
        self.backup_dir = 'backups'
        self.required_files = [
            'app.py', 'security.py', 'config.py', 'requirements.txt',
            'templates/', 'static/', 'Dockerfile', 'docker-compose.yml'
        ]
        
    def create_directories(self):
        """Crea directorios necesarios"""
        for directory in [self.deployment_dir, self.backup_dir, 'logs']:
            if not os.path.exists(directory):
                os.makedirs(directory)
                print(f"✅ Directorio creado: {directory}")
    
    def generate_secure_keys(self):
        """Genera claves seguras para producción"""
        keys = {
            'SECRET_KEY': secrets.token_urlsafe(32),
            'ENCRYPTION_KEY': secrets.token_urlsafe(32),
            'SESSION_KEY': secrets.token_urlsafe(16),
            'API_KEY': secrets.token_urlsafe(24)
        }
        
        print("🔐 Claves seguras generadas:")
        for key, value in keys.items():
            print(f"   {key}: {value[:8]}...")
        
        return keys
    
    def create_production_env(self, keys):
        """Crea archivo .env para producción"""
        env_content = f"""# ====================================================================
# CONFIGURACIÓN DE PRODUCCIÓN - GENERADA AUTOMÁTICAMENTE
# ====================================================================
# Generado el: {datetime.now().isoformat()}

# Entorno
FLASK_ENV=production

# Claves de seguridad (GENERADAS AUTOMÁTICAMENTE)
SECRET_KEY={keys['SECRET_KEY']}
ENCRYPTION_KEY={keys['ENCRYPTION_KEY']}
SESSION_KEY={keys['SESSION_KEY']}
API_KEY={keys['API_KEY']}

# Base de datos (CONFIGURAR MANUALMENTE)
DATABASE_URL=postgresql://usuario:password@host:puerto/database

# Credenciales de administrador (CAMBIAR INMEDIATAMENTE)
ADMIN_USERNAME=admin
ADMIN_PASSWORD=CAMBIAR_ESTA_PASSWORD_INMEDIATAMENTE

# Configuración de servidor
PORT=10000

# Dominios permitidos (CONFIGURAR SEGÚN TU DOMINIO)
ALLOWED_ORIGINS=https://tu-dominio.com,https://www.tu-dominio.com

# Configuración de logs
LOG_LEVEL=WARNING

# ====================================================================
# INSTRUCCIONES IMPORTANTES:
# ====================================================================
# 1. CAMBIAR ADMIN_PASSWORD inmediatamente
# 2. CONFIGURAR DATABASE_URL con tus credenciales reales
# 3. ACTUALIZAR ALLOWED_ORIGINS con tu dominio
# 4. NUNCA subir este archivo a repositorios públicos
# 5. Hacer backup de estas claves en lugar seguro
# ====================================================================
"""
        
        env_path = os.path.join(self.deployment_dir, '.env.production')
        with open(env_path, 'w') as f:
            f.write(env_content)
        
        print(f"✅ Archivo de producción creado: {env_path}")
        print("⚠️  IMPORTANTE: Configura DATABASE_URL y ADMIN_PASSWORD")
    
    def create_security_checklist(self):
        """Crea checklist de seguridad"""
        checklist = """# 🔐 CHECKLIST DE SEGURIDAD - DEPLOYMENT

## ✅ Antes del Deployment

### Configuración
- [ ] Cambiar ADMIN_PASSWORD en .env.production
- [ ] Configurar DATABASE_URL correcta
- [ ] Verificar ALLOWED_ORIGINS
- [ ] Configurar SSL/HTTPS
- [ ] Verificar firewall del servidor

### Base de Datos
- [ ] Base de datos PostgreSQL configurada
- [ ] Usuario de BD con permisos mínimos necesarios
- [ ] Backup automático configurado
- [ ] Conexiones SSL habilitadas

### Servidor
- [ ] Sistema operativo actualizado
- [ ] Python 3.8+ instalado
- [ ] Nginx/Apache configurado como proxy reverso
- [ ] Certificado SSL válido
- [ ] Logs de sistema configurados

### Aplicación
- [ ] Variables de entorno configuradas
- [ ] Dependencias instaladas
- [ ] Base de datos inicializada
- [ ] Pruebas de conectividad exitosas

## ✅ Después del Deployment

### Verificación
- [ ] Login funciona correctamente
- [ ] HTTPS funciona sin errores
- [ ] Base de datos responde
- [ ] Logs se generan correctamente
- [ ] Rate limiting funciona

### Monitoreo
- [ ] Configurar alertas de sistema
- [ ] Monitoreo de base de datos
- [ ] Logs de seguridad activos
- [ ] Backup automático funcionando

### Seguridad
- [ ] Cambiar credenciales por defecto
- [ ] Verificar headers de seguridad
- [ ] Probar protección contra ataques
- [ ] Documentar procedimientos de emergencia

## 🚨 En Caso de Problemas

1. Verificar logs: `tail -f logs/security.log`
2. Verificar conexión BD: `python test_db.py`
3. Verificar variables de entorno
4. Contactar soporte técnico

---
**Fecha de deployment:** {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
"""
        
        checklist_path = os.path.join(self.deployment_dir, 'SECURITY_CHECKLIST.md')
        with open(checklist_path, 'w') as f:
            f.write(checklist)
        
        print(f"✅ Checklist de seguridad creado: {checklist_path}")
    
    def copy_files(self):
        """Copia archivos necesarios para deployment"""
        print("📁 Copiando archivos...")
        
        for item in self.required_files:
            src = item
            dst = os.path.join(self.deployment_dir, item)
            
            if os.path.isfile(src):
                shutil.copy2(src, dst)
                print(f"   ✅ {src}")
            elif os.path.isdir(src):
                if os.path.exists(dst):
                    shutil.rmtree(dst)
                shutil.copytree(src, dst)
                print(f"   ✅ {src}/")
            else:
                print(f"   ⚠️  No encontrado: {src}")
    
    def create_deployment_script(self):
        """Crea script de deployment automático"""
        script_content = """#!/bin/bash
# Script de deployment automático
# Uso: ./deploy.sh

set -e  # Salir si hay errores

echo "🚀 INICIANDO DEPLOYMENT - Sistema de Inventario"
echo "================================================"

# Verificar Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 no encontrado"
    exit 1
fi

# Verificar PostgreSQL
if ! command -v psql &> /dev/null; then
    echo "⚠️  PostgreSQL client no encontrado"
fi

# Crear entorno virtual
echo "📦 Creando entorno virtual..."
python3 -m venv venv
source venv/bin/activate

# Instalar dependencias
echo "📦 Instalando dependencias..."
pip install --upgrade pip
pip install -r requirements.txt

# Verificar archivo .env
if [ ! -f ".env.production" ]; then
    echo "❌ Archivo .env.production no encontrado"
    echo "💡 Copia .env.production.example y configúralo"
    exit 1
fi

# Copiar configuración de producción
cp .env.production .env

# Inicializar base de datos
echo "🗄️  Inicializando base de datos..."
python init_db.py

# Probar conexión
echo "🧪 Probando aplicación..."
python test_api.py

# Iniciar aplicación
echo "✅ Deployment completado"
echo "🚀 Para iniciar: python app.py"
echo "🌐 URL: http://localhost:10000"
echo "👤 Usuario: admin"
echo "🔑 Contraseña: (configurada en .env)"
"""
        
        script_path = os.path.join(self.deployment_dir, 'deploy.sh')
        with open(script_path, 'w') as f:
            f.write(script_content)
        
        # Hacer ejecutable
        os.chmod(script_path, 0o755)
        print(f"✅ Script de deployment creado: {script_path}")
    
    def create_docker_production(self):
        """Crea configuración Docker para producción"""
        dockerfile_prod = """# Dockerfile para producción
FROM python:3.11-slim

# Variables de entorno
ENV PYTHONUNBUFFERED=1
ENV FLASK_ENV=production

# Crear usuario no-root
RUN useradd --create-home --shell /bin/bash app

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \\
    postgresql-client \\
    && rm -rf /var/lib/apt/lists/*

# Crear directorio de trabajo
WORKDIR /app

# Copiar requirements y instalar dependencias Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiar código de aplicación
COPY . .

# Crear directorio de logs
RUN mkdir -p logs

# Cambiar propietario de archivos
RUN chown -R app:app /app

# Cambiar a usuario no-root
USER app

# Exponer puerto
EXPOSE 10000

# Comando de inicio
CMD ["python", "app.py"]
"""
        
        dockerfile_path = os.path.join(self.deployment_dir, 'Dockerfile.production')
        with open(dockerfile_path, 'w') as f:
            f.write(dockerfile_prod)
        
        # Docker Compose para producción
        compose_prod = """version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.production
    ports:
      - "10000:10000"
    environment:
      - FLASK_ENV=production
    env_file:
      - .env.production
    volumes:
      - ./logs:/app/logs
    restart: unless-stopped
    depends_on:
      - db
    networks:
      - app-network

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: inventario_sistema
      POSTGRES_USER: inventario_user
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./backups:/backups
    restart: unless-stopped
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app
    restart: unless-stopped
    networks:
      - app-network

volumes:
  postgres_data:

networks:
  app-network:
    driver: bridge
"""
        
        compose_path = os.path.join(self.deployment_dir, 'docker-compose.production.yml')
        with open(compose_path, 'w') as f:
            f.write(compose_prod)
        
        print("✅ Configuración Docker para producción creada")
    
    def run_deployment(self):
        """Ejecuta el proceso completo de deployment"""
        print("🚀 INICIANDO DEPLOYMENT SEGURO")
        print("=" * 50)
        
        # Crear directorios
        self.create_directories()
        
        # Generar claves seguras
        keys = self.generate_secure_keys()
        
        # Crear archivo de producción
        self.create_production_env(keys)
        
        # Copiar archivos
        self.copy_files()
        
        # Crear checklist de seguridad
        self.create_security_checklist()
        
        # Crear script de deployment
        self.create_deployment_script()
        
        # Crear configuración Docker
        self.create_docker_production()
        
        print("=" * 50)
        print("✅ DEPLOYMENT PREPARADO")
        print(f"📁 Archivos en: {self.deployment_dir}/")
        print()
        print("🔐 PRÓXIMOS PASOS:")
        print("1. Revisar SECURITY_CHECKLIST.md")
        print("2. Configurar .env.production")
        print("3. Ejecutar ./deploy.sh")
        print("4. Verificar que todo funcione")
        print()
        print("⚠️  IMPORTANTE:")
        print("- Cambiar ADMIN_PASSWORD inmediatamente")
        print("- Configurar DATABASE_URL")
        print("- Hacer backup de las claves generadas")
        print("=" * 50)

def main():
    """Función principal"""
    if len(sys.argv) > 1 and sys.argv[1] == '--help':
        print("""
🚀 Script de Deployment Seguro - Sistema de Inventario

Uso: python deploy.py

Este script prepara una versión segura para producción:
- Genera claves criptográficas seguras
- Crea configuración de producción
- Prepara archivos de deployment
- Genera checklist de seguridad
- Configura Docker para producción

Después de ejecutar este script:
1. Revisar y completar la configuración
2. Seguir el checklist de seguridad
3. Ejecutar el deployment en el servidor
""")
        return
    
    deployer = SecureDeployer()
    deployer.run_deployment()

if __name__ == "__main__":
    main()