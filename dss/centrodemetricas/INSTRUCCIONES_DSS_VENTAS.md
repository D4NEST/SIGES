# Refactorización: Sistema de Métricas de Votaciones → DSS de Ventas

## Resumen de Cambios

Este proyecto ha sido refactorizado completamente para transformar un sistema de métricas de votaciones en un **Sistema de Soporte a Decisiones (DSS) de Ventas**.

---

## 📊 Nueva Estructura de Base de Datos

### Tabla `ventas` (Nueva)

La tabla principal ahora almacena transacciones de ventas con los siguientes campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id_venta | bigint | ID único de la venta |
| producto | string | Nombre del producto |
| categoria | string | Categoría del producto |
| sku | string | Código SKU |
| sucursal | string | Nombre de la sucursal |
| region | string | Región geográfica |
| canal_venta | string | Canal (Tienda, Online, Distribuidor) |
| estado | string | Estado geográfico |
| cantidad | integer | Cantidad vendida |
| precio_unitario | decimal | Precio por unidad |
| total_venta | decimal | Monto total |
| costo | decimal | Costo del producto |
| margen | decimal | Porcentaje de margen |
| estado_venta | enum | completada/pendiente/cancelada |
| fecha_venta | date | Fecha de la transacción |
| hora_venta | time | Hora de la transacción |
| vendedor | string | Nombre del vendedor |
| cliente_id | string | ID del cliente |

---

## 🎯 Métricas del DSS de Ventas

### KPIs Principales
- Total de Ventas
- Ingresos Totales ($)
- Ventas Completadas
- Tasa de Conversión (%)
- Ticket Promedio
- Crecimiento vs Mes Anterior
- Ventas Pendientes

### Análisis Disponibles
1. **Top Productos Más Vendidos** - Por cantidad de unidades
2. **Top Productos Menos Vendidos** - Identificar productos de bajo rendimiento
3. **Ventas por Región** - Distribución geográfica
4. **Ventas por Canal** - Comparativa de canales de venta
5. **Ventas por Categoría** - Rendimiento por categoría de producto
6. **Ventas por Hora** - Horarios pico de ventas
7. **Tendencia de Ventas** - Últimos 30 días
8. **Productos con Mayor Margen** - Rentabilidad por producto
9. **Ventas por Vendedor** - Rendimiento del equipo

---

## 📁 Archivos Creados/Modificados

### Nuevos Archivos
- `app/Models/Venta.php` - Modelo de ventas
- `app/Imports/VentasImport.php` - Importador Excel de ventas
- `app/Services/VentasImportService.php` - Servicio de importación
- `app/Http/Controllers/VentasUploadController.php` - Controlador de carga
- `database/migrations/2026_09_02_191638_create_ventas_table.php` - Migración
- `database/seeders/VentasSeeder.php` - Datos de ejemplo

### Archivos Modificados
- `app/Services/MetricsService.php` - Consultas para DSS de ventas
- `app/Livewire/DashboardMetricas.php` - Componente Livewire actualizado
- `resources/views/livewire/dashboard-metricas.blade.php` - Dashboard de ventas
- `routes/web.php` - Nuevas rutas para ventas

---

## 🚀 Instrucciones de Instalación

### 1. Ejecutar Migraciones

Asegúrate de que tu servidor de base de datos esté ejecutándose, luego:

```bash
php artisan migrate
```

### 2. Cargar Datos de Ejemplo (Opcional)

Para probar el sistema con datos ficticios:

```bash
php artisan db:seed --class=VentasSeeder
```

Esto generará **500 registros de ventas de ejemplo** con:
- 15 productos diferentes
- 5 regiones geográficas
- 5 canales de venta
- 8 sucursales
- 8 vendedores
- Datos de los últimos 30 días

### 3. Acceder al Sistema

1. Inicia sesión en el sistema
2. El dashboard ahora mostrará métricas de ventas
3. Para cargar tus propios datos, ve a `/ventas/upload`

---

## 📤 Formato de Archivo Excel para Importación

El sistema acepta archivos Excel (.xlsx, .xls, .csv) con las siguientes columnas:

| Columna | Obligatoria | Descripción |
|---------|-------------|-------------|
| producto | ✅ Sí | Nombre del producto |
| categoria | No | Categoría del producto |
| sku | No | Código SKU del producto |
| sucursal | No | Nombre de la sucursal |
| region | No | Región geográfica |
| canal_venta | No | Canal de venta (Tienda, Online, etc.) |
| estado | No | Estado geográfico |
| cantidad | No | Cantidad vendida (default: 0) |
| precio_unitario | No | Precio por unidad |
| costo | No | Costo del producto |
| margen | No | Porcentaje de margen |
| estado_venta | No | completada/pendiente/cancelada |
| fecha_venta | No | Fecha de la venta |
| hora_venta | No | Hora de la venta |
| cliente_id | No | ID del cliente |
| vendedor | No | Nombre del vendedor |

### Ejemplo de estructura Excel:

```
| producto          | categoria    | cantidad | precio_unitario | fecha_venta |
|-------------------|--------------|----------|-----------------|-------------|
| Laptop HP         | Computadoras | 2        | 850.00          | 2026-09-01  |
| Mouse Logitech    | Periféricos  | 5        | 25.00           | 2026-09-01  |
```

---

## 🔧 Rutas del Sistema

| Ruta | Descripción |
|------|-------------|
| `/dashboard/admin` | Dashboard principal para administradores |
| `/dashboard/supervisor` | Dashboard para supervisores |
| `/ventas/upload` | Cargar archivo de ventas |
| `/ventas/upload/parse` | Procesar archivo cargado |

---

## 🎨 Características del Dashboard

### Actualización Automática
- El dashboard se actualiza automáticamente cada 30 segundos
- Gráficas interactivas con Chart.js
- Exportación a PDF de todas las métricas

### Navegación por Tabs
- 🏆 Más Vendidos
- 📉 Menos Vendidos
- 🗺️ Por Región
- 🛒 Por Canal
- 📦 Por Categoría
- 🕐 Por Hora
- 📈 Tendencia
- 💰 Mayor Margen
- 👤 Por Vendedor

---

## ⚠️ Notas Importantes

1. **Migración de Datos Existentes**: La tabla `votos_personal` se mantiene intacta. Si necesitas migrar datos, crea un script de migración personalizado.

2. **Backup**: Siempre realiza un backup de tu base de datos antes de ejecutar las migraciones.

3. **Permisos**: El sistema mantiene los roles existentes (admin, supervisor, operador).

4. **API Endpoints**: Los endpoints de la API (`/api/metricas/*`) siguen funcionando y retornan las nuevas métricas de ventas.

---

## 🔄 Comparativa: Antes vs Después

| Aspecto | Sistema Anterior (Votaciones) | Sistema Nuevo (Ventas) |
|---------|-------------------------------|------------------------|
| Modelo principal | VotoPersonal | Venta |
| Métrica principal | Votos | Ingresos ($) |
| Top 5 | Centros de votación | Productos más vendidos |
| Distribución | Por estado de votación | Por región geográfica |
| Trazabilidad | Votos por hora | Ventas por hora |
| Comparativa | Votantes vs No votantes | Ventas vs Mes anterior |
| KPI principal | Participación % | Tasa de conversión % |

---

## 📞 Soporte

Si encuentras algún problema durante la instalación o uso del sistema, verifica:

1. Que el servidor de base de datos esté ejecutándose
2. Que las credenciales en `.env` sean correctas
3. Que tengas los permisos necesarios en las carpetas `storage` y `bootstrap/cache`

```bash
# Permisos en Linux/Mac
chmod -R 775 storage bootstrap/cache
```

---

**Fecha de Refactorización**: 2 de septiembre de 2026
**Versión**: 2.0.0 - DSS de Ventas
