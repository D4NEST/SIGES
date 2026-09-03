# Plan de Implementación Focalizado

Convertir los 3 problemas principales en tareas concretas que resuelvan: modo oscuro, gráficas con colores diferentes, y exportación a PDF.

## Fase 1: Corrección del Modo Oscuro

### 1.1 Mejorar persistencia del modo oscuro
- Corregir el código JavaScript en app.blade.php para que persista correctamente
- Asegurar que el tema se cargue al iniciar la aplicación
- Implementar detección automática de preferencia del sistema
- Referencia: Requisito 9 (Corrección del Modo Oscuro)

### 1.2 Optimizar paleta de colores para modo oscuro
- Mejorar contraste en todos los elementos según WCAG AAA
- Asegurar que todas las clases `dark:` funcionen correctamente
- Probar en todas las páginas del sistema
- Referencia: Requisito 2.1, 2.2, 2.3

### 1.3 Implementar botón de toggle visible
- Agregar botón para cambiar modo oscuro/claro en la navegación
- Proporcionar feedback visual claro
- Mantener estado entre sesiones
- Referencia: Requisito 9.1, 9.2

## Fase 2: Gráficas con Colores Distinguibles

### 2.1 Implementar paleta de colores diferenciada
- Crear paleta con al menos 8 colores claramente distintos
- Configurar Chart.js para usar paleta diferenciada
- Garantizar contraste mínimo 3:1 entre colores adyacentes
- Referencia: Requisito 7.1, 7.2, 7.6

### 2.2 Optimizar colores para modo oscuro
- Ajustar saturación y luminosidad automáticamente
- Mantener diferenciabilidad en ambos modos
- Implementar paletas específicas para modo claro/oscuro
- Referencia: Requisito 7.4, 9.6

### 2.3 Mejorar accesibilidad visual
- Agregar patrones/texturas para usuarios con daltonismo
- Proporcionar leyendas claras y descriptivas
- Asegurar que etiquetas sean legibles
- Referencia: Requisito 7.5

## Fase 3: Exportación a PDF

### 3.1 Instalar y configurar librería PDF
- Instalar DomPDF o similar para Laravel
- Configurar en composer.json
- Crear servicio para generación de PDFs
- Referencia: Requisito 8.1, 8.2

### 3.2 Implementar exportación de gráfica individual
- Crear botón "Exportar a PDF" por gráfica
- Generar PDF con gráfica de alta calidad
- Incluir metadatos (título, fecha, contexto)
- Referencia: Requisito 8.1, 8.3

### 3.3 Implementar exportación de resumen general
- Crear botón "Exportar todas las gráficas"
- Generar PDF organizado con todas las gráficas
- Incluir resumen ejecutivo con KPIs principales
- Referencia: Requisito 8.2, 8.3, 8.7

### 3.4 Optimizar PDF para impresión
- Ajustar colores para impresión blanco/negro
- Proporcionar opciones de orientación y tamaño
- Mantener calidad de gráficas en PDF
- Referencia: Requisito 8.5, 8.6

## Fase 4: Integración y Testing

### 4.1 Punto de control: Verificar modo oscuro
- Probar en todas las páginas y componentes
- Verificar persistencia entre sesiones
- Validar contraste WCAG AAA

### 4.2 Punto de control: Verificar gráficas
- Comprobar que colores son distinguibles
- Validar funcionamiento en modo claro/oscuro
- Probar accesibilidad visual

### 4.3 Punto de control: Verificar exportación PDF
- Probar exportación individual y general
- Verificar calidad y contenido de PDFs
- Validar metadatos y organización

### 4.4 Punto final: Validación completa
- Asegurar que todas las funcionalidades trabajen juntas
- Verificar performance y usabilidad
- Documentar cambios realizados