# 🏗️ SIGES — Sistema Integrado de Gestión Empresarial

**SIGES** es un ecosistema modular de gestión empresarial diseñado para adaptarse automáticamente a cualquier rubro de negocio. Su núcleo es un **Meta Modelador** que define la estructura de datos (rubros, entidades y campos) y genera dinámicamente las tablas físicas en la base de datos. Los módulos de **Inventario** y **DSS** (Decision Support System) consumen esta configuración para ofrecer dashboards, métricas y gestión de stock sin necesidad de modificar el código fuente.

> *"El software se adapta a tu negocio, no al revés."*

---

## 🧠 ¿Por qué SIGES?

* **Adaptabilidad Dinámica:** Los ERPs genéricos son rígidos; el Meta Modelador permite definir rubros, entidades y campos sin alterar la base de código.
* **Automatización:** El Agente Inteligente escanea bases de datos existentes para generar configuraciones automáticas y migrar datos dispersos.
* **Ecosistema Integrado:** Unifica facturación, inventario y métricas en servicios desacoplados bajo una sola arquitectura.
* **Despliegue Rápido:** Configuración guiada mediante Wizard en menos de una hora.

---

## 🏛️ Arquitectura General

```mermaid
flowchart TB
    subgraph SIGES [SIGES ECOSYSTEM]
        MM[META MODELADOR - Flask<br>Central Configuration Hub & Brain]
        DSS[DSS - Laravel<br>Dashboards, KPIs, Métricas]
        INV[INVENTARIO - Flask<br>Productos, Seriales, Stock]
        FAC[FACTURACIÓN - Laravel<br>Clientes, Facturas]
        INFRA[SHARED INFRASTRUCTURE<br>Redis · RabbitMQ · JWT]
    end

    MM --> DSS
    MM --> INV
    MM --> FAC

    DSS -.-> INFRA
    INV -.-> INFRA
    FAC -.-> INFRA

    

🧩 Módulos
Meta Modelador (Flask + PostgreSQL): Cerebro central. Define rubros, entidades y campos, generando las tablas físicas dinámicamente.

DSS (Laravel + MySQL): Dashboards interactivos, KPIs dinámicos, gráficas y generación de reportes ejecutivos.

Inventario (Flask + PostgreSQL): Gestión de productos, seguimiento de seriales, stock y control de movimientos.

Facturación (Laravel): Emisión de facturas, registro de ventas y sincronización con inventario y DSS.

🚀 Tecnologías
Backend (DSS & Facturación): PHP 8.2 / Laravel 11.x

Backend (Meta Modelador & Inventario): Python 3.11+ / Flask 2.3.x / SQLAlchemy

Bases de Datos: PostgreSQL 15+ / MySQL 8.0 / Supabase

Caché & Mensajería: Redis 7.x / RabbitMQ

Frontend: Blade, Livewire, Alpine.js, Tailwind CSS, Chart.js 4.4

Infraestructura: Docker / Docker Compose

📦 Instalación y Despliegue (Docker)
Clonar el repositorio:

git clone [https://github.com/D4NEST/SIGES.git](https://github.com/D4NEST/SIGES.git)
cd SIGES
Configurar variables de entorno:
Copia los archivos .env.example a .env en cada módulo y ajusta las credenciales según tu entorno.

Levantar contenedores con Docker Compose:

Bash
docker-compose up -d
DSS: http://localhost:8000

Inventario: http://localhost:5000

Meta Modelador: http://localhost:5001

Configurar el sistema:
Accede al Wizard de Configuración en http://localhost:8000/setup para definir rubros, mapear entidades y desplegar la configuración inicial.

🛠️ Desarrollo Local (Sin Docker)
DSS (Laravel)
Bash
cd dss/centrodemetricas
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
Inventario (Flask)
Bash
cd inventario
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python app.py
Meta Modelador (Flask)
Bash
cd Metamodelador/backend
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python app.py
📊 Estado del Proyecto
Módulo	Estado	Completitud
Meta Modelador	✅ Funcional (con setup wizard)	70%
DSS	✅ Funcional (dashboard con métricas)	60%
Inventario	✅ Funcional (gestión de productos/seriales)	80%
Facturación	🟡 En desarrollo	20%
Integración	🟡 En diseño (especificación completa)	10%
🤝 Contribución
Haz un Fork del repositorio.

Crea una rama para tu característica: git checkout -b feature/nueva-funcionalidad

Realiza tus cambios y confirma: git commit -am 'Agregar nueva funcionalidad'

Publica la rama: git push origin feature/nueva-funcionalidad

Abre un Pull Request.

📄 Licencia
Este proyecto es de desarrollo privado. Todos los derechos reservados.

📬 Contacto
Autor: Néstor Patiño

GitHub: @D4NEST

Correo: danestsantos@gmail.com
