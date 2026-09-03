# 🏗️ SIGES — Sistema Integrado de Gestión Empresarial

**SIGES** es un ecosistema modular de gestión empresarial diseñado para adaptarse automáticamente a cualquier rubro de negocio. Su núcleo es un **Meta Modelador** que define la estructura de datos (rubros, entidades y campos) y genera dinámicamente las tablas físicas en la base de datos. Los módulos de **Inventario** y **DSS** (Decision Support System) consumen esta configuración para ofrecer dashboards, métricas y gestión de stock sin necesidad de modificar el código fuente.

> **"El software se adapta a tu negocio, no al revés."**

---

## 🧠 ¿Por qué SIGES?

| Problema | Solución SIGES |
|:---|:---|
| Los ERPs genéricos son rígidos y caros de adaptar | El Meta Modelador permite definir rubros, entidades y campos dinámicamente |
| Los datos están dispersos en Excel | El Agente Inteligente escanea la BD existente y genera la configuración automáticamente |
| La facturación, inventario y métricas viven en sistemas separados | SIGES unifica todo en un ecosistema integrado pero desacoplado |
| Las PYMES no pueden pagar implementaciones de meses | SIGES se configura en **menos de 1 hora** con su Wizard de Configuración |

---

## 🏛️ Arquitectura General
┌─────────────────────────────────────────────────────────────────┐
│ SIGES ECOSYSTEM │
├─────────────────────────────────────────────────────────────────┤
│ ┌───────────────────────────────────────────────────────────┐ │
│ │ META MODELADOR (Flask) │ │
│ │ Central Configuration Hub & Brain │ │
│ │ - Define rubros, entidades, campos │ │
│ │ - Genera tablas físicas en PostgreSQL │ │
│ │ - Expone API REST para los módulos │ │
│ └───────────────────────────────────────────────────────────┘ │
│ │ │
│ ┌──────────────┼──────────────┐ │
│ │ │ │ │
│ ▼ ▼ ▼ │
│ ┌───────────────────┐ ┌───────────────────┐ ┌───────────────┐│
│ │ DSS │ │ INVENTARIO │ │ FACTURACIÓN ││
│ │ (Laravel) │ │ (Flask) │ │ (Laravel) ││
│ │ Dashboards, KPIs, │ │ Productos, │ │ Clientes, ││
│ │ Métricas, │ │ Seriales, Stock │ │ Facturas, ││
│ │ Reportes PDF │ │ API REST │ │ Integración ││
│ └───────────────────┘ └───────────────────┘ └───────────────┘│
│ │ │
│ ┌───────────────────────────────────────────────────────────┐ │
│ │ SHARED INFRASTRUCTURE │ │
│ │ Redis (Cache) · RabbitMQ (Events) · JWT (Auth) │ │
│ └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘

text

---

## 🧩 Módulos

| Módulo | Tecnología | Función |
|:---|:---|:---|
| **Meta Modelador** | Flask + PostgreSQL | Cerebro central: define rubros, entidades y campos, genera tablas físicas |
| **DSS** | Laravel + MySQL | Dashboards interactivos, KPIs dinámicos, gráficas, reportes PDF |
| **Inventario** | Flask + PostgreSQL | Gestión de productos, seriales, stock, movimientos |
| **Facturación** | Laravel | Emisión de facturas, registro de ventas, sincronización con inventario y DSS |

---

## 🚀 Tecnologías

| Capa | Tecnología | Versión |
|:---|:---|:---|
| **Backend (DSS)** | Laravel | 11.x |
| **Backend (Meta/Inv)** | Flask | 2.3.x |
| **Base de Datos (DSS)** | MySQL | 8.0 |
| **Base de Datos (Meta/Inv)** | PostgreSQL | 15+ |
| **Caché / Colas** | Redis / RabbitMQ | 7.x |
| **Frontend** | Blade + Livewire + Alpine.js | — |
| **Gráficos** | Chart.js | 4.4 |
| **Contenedores** | Docker / Docker Compose | — |

---

## 📦 Instalación y Despliegue (Docker)

### 1. Clonar el repositorio
```bash
git clone https://github.com/D4NEST/SIGES.git
cd SIGES
2. Configurar variables de entorno
Copia los archivos .env.example a .env en cada módulo y ajusta las credenciales según tu entorno.

3. Levantar con Docker Compose
bash
docker-compose up -d
Esto levantará todos los servicios:

DSS en http://localhost:8000

Inventario en http://localhost:5000

Meta Modelador en http://localhost:5001

PostgreSQL, MySQL, Redis, RabbitMQ

4. Configurar el sistema
Accede al Wizard de Configuración en http://localhost:8000/setup:

Selecciona el rubro (o detecta automáticamente desde tu BD).

Conecta tu base de datos existente (opcional).

Revisa y edita la propuesta de entidades y campos.

Haz clic en "Desplegar Configuración" y ¡listo!

🗺️ Roadmap de Desarrollo
Fase	Descripción	Estado
Fase 0	Seguridad: API Key, JWT, variables de entorno	⏳ En planificación
Fase 1	Wizard de Configuración (Agente Inteligente)	⏳ En planificación
Fase 2	Puente Laravel ↔ Meta Modelador (caché, servicios)	⏳ En planificación
Fase 3	Dashboard Dinámico (KPIs, gráficos, tablas)	⏳ En planificación
Fase 4	Conectar Inventario al Meta Modelador	⏳ En planificación
Fase 5	Módulo de Facturación completo	⏳ En planificación
Fase 6	Pruebas, optimización, despliegue	⏳ En planificación
El plan completo de requisitos, diseño y tareas se encuentra en .kiro/specs/siges-multi-module-integration/.

🛠️ Desarrollo Local (Sin Docker)
Si prefieres desarrollar sin Docker, cada módulo se ejecuta de forma independiente:

DSS (Laravel)
bash
cd dss/centrodemetricas
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
Inventario (Flask)
bash
cd inventario
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python app.py
Meta Modelador (Flask)
bash
cd Metamodelador/backend
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python app.py
📊 Estado del Proyecto
Módulo	Estado	Completitud
Meta Modelador	✅ Funcional (con setup wizard)	70%
DSS	✅ Funcional (dashboard con métricas fijas)	60%
Inventario	✅ Funcional (gestión de productos y seriales)	80%
Facturación	🟡 En desarrollo	20%
Integración	🟡 En diseño (spec completo)	10%
🤝 Contribución
Si quieres contribuir a SIGES:

Fork el repositorio.

Crea una rama con tu feature: git checkout -b feature/nueva-funcionalidad.

Commit tus cambios: git commit -am 'Agregar nueva funcionalidad'.

Push a la rama: git push origin feature/nueva-funcionalidad.

Abre un Pull Request.

Asegúrate de que tus cambios mantengan la coherencia de estilo y pasen las pruebas (cuando estén implementadas).

📄 Licencia
Este proyecto es de uso privado. Todos los derechos reservados.

