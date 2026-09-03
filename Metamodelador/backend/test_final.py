import os
from dotenv import load_dotenv

# Cargar .env de manera forzada
load_dotenv(verbose=True, override=True)

# Leer variable directamente
db_url = os.environ.get('DATABASE_URL')
print(f"📌 Variable DATABASE_URL: {db_url}")

if db_url:
    print("✅ Variable encontrada!")
else:
    print("❌ Variable NO encontrada")
    print("\n📝 Leyendo archivo .env directamente:")
    with open('.env', 'r') as f:
        contenido = f.read()
        print(contenido)
        # Buscar la línea de DATABASE_URL manualmente
        for linea in contenido.split('\n'):
            if 'DATABASE_URL' in linea:
                print(f"🔍 Línea encontrada: {linea}")
                valor = linea.split('=')[1].strip()
                print(f"✅ Valor extraído: {valor}")
