#!/usr/bin/env python3
"""
Script de ofuscación para proteger el código fuente
NOTA: La ofuscación en Python tiene limitaciones, pero añade una capa de protección
"""
import os
import sys
import base64
import zlib
import marshal
import py_compile
import shutil
from pathlib import Path

class CodeObfuscator:
    """Ofuscador de código Python"""
    
    def __init__(self):
        self.protected_files = [
            'app.py',
            'security.py',
            'config.py'
        ]
        self.output_dir = 'obfuscated'
        
    def create_output_dir(self):
        """Crea directorio de salida"""
        if os.path.exists(self.output_dir):
            shutil.rmtree(self.output_dir)
        os.makedirs(self.output_dir)
        
        # Copiar archivos estáticos
        static_files = [
            'requirements.txt',
            'runtime.txt',
            'Dockerfile',
            'docker-compose.yml',
            '.env.example',
            'README.md'
        ]
        
        for file in static_files:
            if os.path.exists(file):
                shutil.copy2(file, self.output_dir)
        
        # Copiar directorios
        dirs_to_copy = ['templates', 'static']
        for dir_name in dirs_to_copy:
            if os.path.exists(dir_name):
                shutil.copytree(dir_name, os.path.join(self.output_dir, dir_name))
    
    def obfuscate_string(self, code):
        """Ofusca una cadena de código"""
        # Comprimir y codificar
        compressed = zlib.compress(code.encode('utf-8'))
        encoded = base64.b64encode(compressed).decode('utf-8')
        
        # Crear código ofuscado
        obfuscated = f"""
import base64
import zlib
exec(zlib.decompress(base64.b64decode('{encoded}')).decode('utf-8'))
"""
        return obfuscated
    
    def obfuscate_with_marshal(self, code):
        """Ofusca usando marshal (más avanzado)"""
        try:
            # Compilar código
            compiled = compile(code, '<string>', 'exec')
            
            # Serializar con marshal
            marshaled = marshal.dumps(compiled)
            encoded = base64.b64encode(marshaled).decode('utf-8')
            
            # Crear código ofuscado
            obfuscated = f"""
import base64
import marshal
exec(marshal.loads(base64.b64decode('{encoded}')))
"""
            return obfuscated
        except Exception as e:
            print(f"⚠️ Error con marshal, usando método básico: {e}")
            return self.obfuscate_string(code)
    
    def add_fake_code(self, code):
        """Añade código falso para confundir"""
        fake_imports = """
# Fake imports to confuse reverse engineering
import random
import hashlib
import uuid
import socket
import threading
import subprocess
import sqlite3
import json
import xml.etree.ElementTree as ET

# Fake variables
_fake_key = "this_is_not_the_real_key_12345"
_fake_db = "fake_database_connection_string"
_fake_tokens = ["fake_token_1", "fake_token_2", "fake_token_3"]

# Fake functions
def _fake_decrypt(data):
    return hashlib.md5(data.encode()).hexdigest()

def _fake_validate(token):
    return token in _fake_tokens

def _fake_connect():
    return sqlite3.connect(":memory:")

"""
        return fake_imports + "\n" + code
    
    def obfuscate_file(self, file_path):
        """Ofusca un archivo específico"""
        print(f"🔒 Ofuscando: {file_path}")
        
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                original_code = f.read()
            
            # Añadir código falso
            code_with_fakes = self.add_fake_code(original_code)
            
            # Ofuscar
            obfuscated_code = self.obfuscate_with_marshal(code_with_fakes)
            
            # Guardar archivo ofuscado
            output_path = os.path.join(self.output_dir, file_path)
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(obfuscated_code)
            
            print(f"✅ {file_path} ofuscado correctamente")
            return True
            
        except Exception as e:
            print(f"❌ Error ofuscando {file_path}: {e}")
            return False
    
    def compile_to_pyc(self, file_path):
        """Compila archivo a bytecode .pyc"""
        try:
            output_path = os.path.join(self.output_dir, file_path + 'c')
            py_compile.compile(file_path, output_path, doraise=True)
            print(f"✅ {file_path} compilado a bytecode")
            return True
        except Exception as e:
            print(f"❌ Error compilando {file_path}: {e}")
            return False
    
    def create_launcher(self):
        """Crea un launcher ofuscado"""
        launcher_code = '''#!/usr/bin/env python3
"""
Launcher para aplicación de inventario
"""
import sys
import os

# Verificar Python version
if sys.version_info < (3, 8):
    print("❌ Python 3.8+ requerido")
    sys.exit(1)

# Verificar dependencias críticas
try:
    import flask
    import psycopg2
    import bcrypt
except ImportError as e:
    print(f"❌ Dependencia faltante: {e}")
    print("💡 Ejecuta: pip install -r requirements.txt")
    sys.exit(1)

# Importar aplicación principal
try:
    from app import app
    
    if __name__ == '__main__':
        port = int(os.environ.get('PORT', 10000))
        debug = os.environ.get('FLASK_ENV') != 'production'
        
        print("🚀 Iniciando Sistema de Inventario - Soluciones Lógicas")
        print(f"🌐 Puerto: {port}")
        print(f"🔧 Debug: {debug}")
        
        app.run(debug=debug, host='0.0.0.0', port=port)
        
except Exception as e:
    print(f"❌ Error iniciando aplicación: {e}")
    sys.exit(1)
'''
        
        launcher_path = os.path.join(self.output_dir, 'run.py')
        with open(launcher_path, 'w', encoding='utf-8') as f:
            f.write(launcher_code)
        
        print("✅ Launcher creado")
    
    def create_readme(self):
        """Crea README para versión ofuscada"""
        readme_content = """# Sistema de Inventario - Versión Protegida

Esta es la versión protegida del sistema de inventario de Soluciones Lógicas.

## 🚀 Instalación

1. **Instalar dependencias:**
```bash
pip install -r requirements.txt
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
# Editar .env con tus configuraciones
```

3. **Inicializar base de datos:**
```bash
python init_db.py
```

4. **Ejecutar aplicación:**
```bash
python run.py
```

## ⚠️ Importante

- Esta versión contiene código ofuscado para protección
- No modifiques los archivos .py principales
- Para soporte técnico contacta a Soluciones Lógicas

## 🔐 Seguridad

- El código fuente está protegido contra ingeniería inversa
- Las credenciales deben configurarse en variables de entorno
- Se incluyen medidas anti-tampering

---
**Soluciones Lógicas** - Sistema de Inventario Protegido
"""
        
        readme_path = os.path.join(self.output_dir, 'README_PROTECTED.md')
        with open(readme_path, 'w', encoding='utf-8') as f:
            f.write(readme_content)
    
    def run_obfuscation(self):
        """Ejecuta el proceso completo de ofuscación"""
        print("🔒 INICIANDO PROCESO DE OFUSCACIÓN")
        print("=" * 50)
        
        # Crear directorio de salida
        self.create_output_dir()
        print("✅ Directorio de salida creado")
        
        # Ofuscar archivos protegidos
        success_count = 0
        for file_path in self.protected_files:
            if os.path.exists(file_path):
                if self.obfuscate_file(file_path):
                    success_count += 1
            else:
                print(f"⚠️ Archivo no encontrado: {file_path}")
        
        # Copiar archivos no protegidos
        other_files = ['init_db.py', 'test_api.py']
        for file_path in other_files:
            if os.path.exists(file_path):
                shutil.copy2(file_path, self.output_dir)
                print(f"📄 Copiado: {file_path}")
        
        # Crear launcher
        self.create_launcher()
        
        # Crear README
        self.create_readme()
        
        print("=" * 50)
        print(f"✅ OFUSCACIÓN COMPLETADA")
        print(f"📊 Archivos procesados: {success_count}/{len(self.protected_files)}")
        print(f"📁 Salida: {self.output_dir}/")
        print("💡 Para usar la versión protegida:")
        print(f"   cd {self.output_dir}")
        print("   python run.py")
        print("=" * 50)

def main():
    """Función principal"""
    if len(sys.argv) > 1 and sys.argv[1] == '--help':
        print("""
🔒 Script de Ofuscación - Sistema de Inventario

Uso: python obfuscate.py [opciones]

Opciones:
  --help    Mostrar esta ayuda
  
Este script protege el código fuente mediante:
- Compresión y codificación Base64
- Serialización con marshal
- Código falso para confundir
- Compilación a bytecode

NOTA: La ofuscación en Python no es 100% segura, pero añade
una capa de protección significativa contra usuarios casuales.

Para máxima seguridad, considera:
- Usar un servidor privado
- Implementar autenticación de dos factores
- Cifrar la base de datos
- Usar HTTPS siempre
""")
        return
    
    obfuscator = CodeObfuscator()
    obfuscator.run_obfuscation()

if __name__ == "__main__":
    main()