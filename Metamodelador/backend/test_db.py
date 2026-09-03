import os
from dotenv import load_dotenv
import pg8000

# Cargar variables
load_dotenv()

# Obtener DATABASE_URL
db_url = os.getenv('DATABASE_URL')
print(f"📌 Conectando a: {db_url}")

# Parsear la URL manualmente
# postgresql+pg8000://postgres:123456@localhost:5432/metamodelador_db
partes = db_url.replace('postgresql+pg8000://', '').split('@')
user_pass = partes[0].split(':')
host_db = partes[1].split('/')

usuario = user_pass[0]
password = user_pass[1]
host = host_db[0].split(':')[0]
puerto = int(host_db[0].split(':')[1]) if ':' in host_db[0] else 5432
database = host_db[1]

print(f"📌 Usuario: {usuario}")
print(f"📌 Database: {database}")
print(f"📌 Host: {host}")
print(f"📌 Puerto: {puerto}")

# Intentar conexión directa con pg8000
try:
    conn = pg8000.connect(
        user=usuario,
        password=password,
        host=host,
        port=puerto,
        database=database
    )
    print("✅ Conexión exitosa a PostgreSQL con pg8000!")
    conn.close()
except Exception as e:
    print(f"❌ Error de conexión: {e}")
