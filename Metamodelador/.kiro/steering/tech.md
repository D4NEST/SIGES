# SIGES — Tecnologías y Configuración

## Dependencias Python

```
Flask==2.3.3
Flask-SQLAlchemy==3.1.1
Flask-CORS==4.0.0
python-dotenv==1.0.0
pg8000==1.30.5
SQLAlchemy==2.0.23
Flask-Login==0.6.3
Werkzeug==3.0.1
```

## Variables de entorno (`backend/.env`)

```env
DATABASE_URL=postgresql+pg8000://postgres:123456@localhost:5432/MetaModelador
SECRET_KEY=clave-secreta-meta-modelador-2026
FLASK_APP=app.py
FLASK_ENV=development
```

Ajustar `postgres:123456` con las credenciales reales de PostgreSQL.

## Comandos de inicio

```cmd
cd C:\Users\Yamileth\Metamodelador\backend
venv\Scripts\activate
python app.py
```

Salida esperada:
```
✅ Base de datos conectada y tablas creadas
📌 Conectado a: postgresql+pg8000://...
 * Running on http://127.0.0.1:5000
```

## Otros comandos útiles

```cmd
# Cargar rubros predefinidos (solo primera vez o si se limpia la BD)
python seed.py

# Verificar conexión a la base de datos
python test_db.py

# Instalar dependencias desde cero
pip install -r requirements.txt
```

## URLs del sistema

| URL | Descripción |
|-----|-------------|
| `http://localhost:5000` | Login (index.html) |
| `http://localhost:5000/setup` | Wizard configuración inicial |
| `http://localhost:5000/dashboard` | Panel principal |
| `http://localhost:5000/api/health` | Health check de la API |

## Frontend — tecnologías

- HTML5 + CSS3 + JavaScript vanilla (sin React, Vue ni jQuery)
- Font Awesome 6.0.0-beta3 (CDN) para iconos
- Fetch API para llamadas HTTP
- CSS custom properties (variables) para theming
- `credentials: 'include'` en todos los fetch que requieren sesión

## Base de datos

- PostgreSQL corriendo en `localhost:5432`
- Base de datos: `MetaModelador`
- Driver: `pg8000` (puro Python, sin dependencias nativas)
- ORM: SQLAlchemy con Flask-SQLAlchemy

## Autenticación

- Basada en Flask sessions (cookies de sesión)
- Passwords hasheados con `werkzeug.security.generate_password_hash`
- Verificación con `check_password_hash`
- El `session['usuario_id']` se guarda al hacer login
- Todos los fetch del frontend usan `credentials: 'include'`
