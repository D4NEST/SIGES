# 📦 Sistema de Gestión de Inventario - Soluciones Lógicas

Sistema completo de gestión de inventario con seguimiento de seriales, desarrollado con Flask y PostgreSQL.

## 🚀 Características Principales

- ✅ **Gestión completa de productos** con marca, modelo y categorías
- ✅ **Seguimiento de seriales** con estados (Almacén, Instalado, Dañado, Retirado)
- ✅ **Dashboard en tiempo real** con estadísticas y alertas de stock bajo
- ✅ **Sistema de autenticación** seguro con sesiones
- ✅ **API REST completa** para integración con otros sistemas
- ✅ **Interfaz responsive** optimizada para móviles y escritorio
- ✅ **Búsqueda avanzada** por marca, modelo, SKU o categoría
- ✅ **Gestión de estados** con historial de cambios
- ✅ **Docker ready** para deployment fácil

## 🛠️ Stack Tecnológico

- **Backend**: Python Flask 2.3.3
- **Base de datos**: PostgreSQL con psycopg2
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Autenticación**: Flask Sessions con bcrypt
- **Deployment**: Docker + Docker Compose
- **Hosting**: Render.com compatible

## 📋 Requisitos Previos

- Python 3.8+
- PostgreSQL 12+
- Docker (opcional)

## 🚀 Instalación y Configuración

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/inventario-soluciones-logicas.git
cd inventario-soluciones-logicas
```

### 2. Configurar entorno virtual
```bash
python -m venv venv
source venv/bin/activate  # En Windows: venv\Scripts\activate
```

### 3. Instalar dependencias
```bash
pip install -r requirements.txt
```

### 4. Configurar variables de entorno
```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar .env con tus configuraciones
nano .env
```

### 5. Configurar base de datos
Asegúrate de tener las siguientes tablas en PostgreSQL:

```sql
-- Tabla de tipos de pieza/categorías
CREATE TABLE tipos_pieza (
    tipo_id SERIAL PRIMARY KEY,
    tipo_modelo VARCHAR(100) NOT NULL UNIQUE
);

-- Tabla de productos
CREATE TABLE productos (
    producto_id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    descripcion TEXT,
    tipo_pieza_id INTEGER REFERENCES tipos_pieza(tipo_id),
    codigo_sku VARCHAR(100) NOT NULL UNIQUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de seriales
CREATE TABLE seriales (
    serial_id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(producto_id),
    codigo_unico_serial VARCHAR(200) NOT NULL UNIQUE,
    estado VARCHAR(20) DEFAULT 'ALMACEN',
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 6. Iniciar la aplicación
```bash
python app.py
```

La aplicación estará disponible en: http://localhost:10000

## 🐳 Deployment con Docker

### Desarrollo
```bash
docker-compose up -d
```

### Producción
```bash
docker build -t inventario-app .
docker run -p 10000:10000 --env-file .env inventario-app
```

## 🔐 Credenciales por Defecto

- **Usuario**: `admin`
- **Contraseña**: `02120212$`

> ⚠️ **IMPORTANTE**: Cambia estas credenciales en producción editando el archivo `.env`

## 📚 API Endpoints

### Autenticación
- `POST /api/auth/login` - Iniciar sesión
- `POST /api/auth/logout` - Cerrar sesión
- `GET /api/auth/check` - Verificar sesión activa

### Inventario
- `GET /api/inventario/stock` - Obtener inventario completo
- `GET /api/inventario/productos` - Listar productos
- `POST /api/inventario/productos` - Crear producto
- `PUT /api/inventario/productos/<id>` - Actualizar producto
- `DELETE /api/inventario/productos/<id>` - Eliminar producto

### Seriales
- `POST /api/inventario/serial` - Registrar serial
- `GET /api/inventario/seriales/<producto_id>` - Ver seriales de producto
- `PUT /api/inventario/serial/<id>` - Actualizar estado de serial
- `DELETE /api/inventario/serial/<id>` - Eliminar serial

### Utilidades
- `GET /api/inventario/estadisticas` - Estadísticas generales
- `GET /api/inventario/stock_bajo` - Productos con stock bajo
- `GET /api/inventario/buscar?q=<termino>` - Búsqueda de productos

## 📁 Estructura del Proyecto

```
inventario-soluciones-logicas/
├── app.py                    # Aplicación Flask principal
├── requirements.txt          # Dependencias Python
├── runtime.txt              # Versión de Python para Render
├── Dockerfile               # Configuración Docker
├── docker-compose.yml       # Orquestación Docker
├── .env.example             # Variables de entorno de ejemplo
├── .gitignore              # Archivos ignorados por Git
├── README.md               # Documentación
├── templates/
│   └── index.html          # Frontend HTML
├── static/
│   ├── styles.css          # Estilos CSS
│   ├── app.js              # Lógica JavaScript
│   └── images/
│       └── logopeneges.png # Logo de la empresa
└── test_db.py              # Script de prueba de BD
```

## 🔧 Solución de Problemas Comunes

### Error de conexión a base de datos
1. Verifica que PostgreSQL esté corriendo
2. Confirma las credenciales en `.env`
3. Asegúrate de que las tablas existan
4. Prueba la conexión: `GET /health`

### Error de autenticación
1. Verifica las credenciales por defecto
2. Revisa la configuración de `SECRET_KEY`
3. Limpia las cookies del navegador

### Error de CORS
1. Verifica `ALLOWED_ORIGINS` en `.env`
2. Asegúrate de incluir el dominio correcto
3. Reinicia la aplicación después de cambios

## 🚀 Deployment en Render.com

1. Conecta tu repositorio a Render
2. Configura las variables de entorno en el dashboard
3. Render detectará automáticamente el `requirements.txt`
4. La aplicación se desplegará en el puerto configurado

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas:
- Email: soporte@solucioneslogicas.com
- Teléfono: +1 (555) 123-4567

---

**Desarrollado con ❤️ por Soluciones Lógicas**