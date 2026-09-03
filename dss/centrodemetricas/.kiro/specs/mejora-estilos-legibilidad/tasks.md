# Plan de Implementación - Correcciones Prioritarias

Enfocarse SOLAMENTE en resolver los 3 problemas urgentes mencionados por el usuario: modo oscuro no funciona, gráficas con colores muy similares, y falta de exportación a PDF.

- [ ] 1. Corregir el modo oscuro en todo el sistema
- [ ] 1.1 Verificar configuración actual del modo oscuro
  - Revisar implementación actual en app.blade.php
  - Verificar persistencia en localStorage
  - Comprobar si se aplican correctamente las clases dark
  - _Requisitos: 9.1, 9.2, 9.6_

- [ ] 1.2 Implementar selector de modo oscuro funcional
  - Crear botón de toggle en la barra de navegación
  - Implementar lógica para cambiar entre modo claro/oscuro
  - Asegurar que las preferencias se persistan entre sesiones
  - _Requisitos: 9.1, 9.2_

- [ ] 1.3 Aplicar estilos dark a componentes faltantes
  - Verificar que todos los componentes tengan variantes dark
  - Corregir colores que no cambian en modo oscuro
  - Asegurar contraste WCAG en modo oscuro
  - _Requisitos: 2.1, 2.2, 9.5_

- [ ] 2. Mejorar colores de las gráficas Chart.js
- [ ] 2.1 Implementar paleta de colores diferenciados
  - Crear paleta de 8 colores claramente distinguibles
  - Asegurar contraste mínimo 3:1 entre colores adyacentes
  - Implementar paleta tanto para modo claro como oscuro
  - _Requisitos: 7.1, 7.2, 7.6_

- [ ] 2.2 Actualizar configuración de Chart.js
  - Modificar la función initCharts en dashboard-metricas.blade.php
  - Aplicar nueva paleta de colores diferenciados
  - Configurar temas claro/oscuro para las gráficas
  - _Requisitos: 7.4, 9.6_

- [ ] 2.3 Implementar soporte para daltonismo
  - Crear paleta alternativa para usuarios con daltonismo
  - Añadir opción para cambiar paleta de colores
  - _Requisitos: 7.5_

- [ ] 3. Implementar exportación a PDF
- [ ] 3.1 Instalar librerías necesarias para PDF
  - Instalar `barryvdh/laravel-dompdf` o similar
  - Configurar servicio de generación de PDF
  - Configurar rutas y controladores necesarios
  - _Requisitos: 8.1, 8.2_

- [ ] 3.2 Crear vista para generación de PDF
  - Diseñar plantilla Blade optimizada para PDF
  - Incluir gráficas y datos en formato PDF
  - Implementar opciones de orientación y tamaño de página
  - _Requisitos: 8.3, 8.6_

- [ ] 3.3 Implementar botones de exportación
  - Añadir botón para exportar gráfica individual
  - Añadir botón para exportar resumen general
  - Implementar selección de gráficas para exportación
  - _Requisitos: 8.1, 8.2, 8.4_

- [ ] 3.4 Optimizar PDF para impresión
  - Ajustar colores para optimizar impresión en blanco/negro
  - Incluir metadatos en PDF (título, fecha, contexto)
  - Añadir resumen ejecutivo opcional con KPIs
  - _Requisitos: 8.5, 8.7_

- [ ] 4. Punto de control - Verificar funcionalidades implementadas
- [ ] 4.1 Asegurar que todas las funcionalidades funcionen correctamente
  - Verificar modo oscuro funciona en toda la aplicación
  - Confirmar que gráficas tienen colores diferenciados
  - Probar exportación a PDF con gráficas individuales y resumen
  - _Requisitos: 1.1, 2.1, 3.1, 7.1, 8.1, 8.2, 9.1_

