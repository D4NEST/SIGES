# SIGES — Descripción General del Proyecto

## ¿Qué es SIGES?

SIGES (Sistema Integrado de Gestión Empresarial) es una aplicación web basada en un **meta-modelador de bases de datos**. En lugar de tener tablas fijas para cada tipo de negocio, el sistema permite definir la estructura de datos (entidades y campos) según el rubro de la empresa, y genera las tablas físicas en PostgreSQL automáticamente.

El mismo sistema sirve para una cauchera, un restaurante, una ferretería o una distribuidora — sin cambiar el código.

## Objetivos

- Proveer un sistema de gestión adaptable a cualquier rubro comercial
- Generar tablas físicas dinámicamente a partir de un meta-modelo configurable
- Ofrecer una interfaz moderna con estética futurista (glassmorphism, fondo oscuro, gradientes azul eléctrico)
- Servir como base para un módulo DSS (Decision Support System) futuro
- Ser ejecutable localmente sin dependencias de nube

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | Python 3.x + Flask 2.3.3 |
| ORM | SQLAlchemy 2.0.23 + Flask-SQLAlchemy 3.1.1 |
| Base de datos | PostgreSQL (driver pg8000) |
| Autenticación | Flask sessions + Werkzeug password hashing |
| Frontend | HTML5 + CSS3 + JavaScript vanilla (sin frameworks) |
| Estilos | CSS custom con variables, glassmorphism, responsive |
| CORS | Flask-CORS 4.0.0 |

## Estructura de carpetas

```
Metamodelador/
├── backend/
│   ├── app.py           → Punto de entrada Flask, rutas estáticas
│   ├── database.py      → Configuración SQLAlchemy
│   ├── models.py        → Modelos ORM
│   ├── routes.py        → API REST completa (Blueprint /api)
│   ├── generator.py     → Generador de tablas físicas
│   ├── seed.py          → 8 rubros predefinidos
│   ├── requirements.txt → Dependencias
│   └── .env             → Variables de entorno (no subir a git)
│
├── frontend/
│   ├── index.html       → Login
│   ├── setup.html       → Wizard configuración inicial (una sola vez)
│   ├── dashboard.html   → Panel principal (EN DESARROLLO)
│   ├── admin.html       → Administración del meta-modelador
│   ├── siges.css        → Estilos globales
│   └── uploads/         → Logos de empresa subidos
│
├── docs/
│   └── documentacion.md → Documentación técnica completa
│
└── .kiro/steering/      → Archivos de contexto para Kiro
```

## Flujo del sistema

```
1. Primera vez → /setup
   Selecciona rubro → crea empresa → genera tablas físicas → crea usuario admin
   
2. Login → / (index.html)
   Carga config dinámica (nombre empresa, logo) → autentica → redirige

3. Dashboard → /dashboard
   Carga datos del usuario, stats, alertas, tareas → gestión diaria
```

## Convenciones importantes

- El sistema se configura **una sola vez** con el wizard `/setup`. Después queda bloqueado.
- Las tablas físicas siguen el patrón: `emp_{empresa_id}_{nombre_tabla}`
- El frontend se sirve desde Flask (mismo origen, sin CORS issues)
- Ventas/Facturas son solo informativas — sin lógica fiscal ni numeración legal
- El módulo DSS se implementa al final (el endpoint `/api/dss/resumen` ya existe como brecha)
- Estética: fondo negro `#0a0a0a`, azul eléctrico `#0066ff`, cian `#00d4ff`, glassmorphism

## Entorno de desarrollo

- OS: Windows
- Ruta del proyecto: `C:\Users\Yamileth\Metamodelador\`
- Shell: CMD
- Servidor: `http://localhost:5000`
