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
