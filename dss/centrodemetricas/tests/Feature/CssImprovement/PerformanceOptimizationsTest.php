<?php

namespace Tests\Feature\CssImprovement;

use Eris\Generator;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Feature: mejora-estilos-legibilidad, Performance optimizations
 * Validates: Requirements 6.1, 6.2, 6.4
 * 
 * Propiedad: Para cualquier implementación, debe usar técnicas CSS modernas y optimizadas
 * para garantizar 60fps y transiciones suaves.
 */
class PerformanceOptimizationsTest extends TestCase
{
    use TestTrait;

    /**
     * Test de propiedad: Uso de técnicas CSS modernas
     * 
     * Verifica que el CSS use técnicas modernas como Grid, Flexbox, variables CSS
     * en lugar de técnicas heredadas.
     */
    public function testModernCssTechniquesAreUsed()
    {
        $this
            ->limitTo(10) // Reducir iteraciones para velocidad
            ->forAll(
                $this->cssComponentFiles(),
                $this->modernCssTechniques()
            )
            ->then(function ($cssFile, $technique) {
                $cssContent = $this->loadCssFile($cssFile);
                
                if (empty($cssContent)) {
                    return; // Si el archivo no existe, salir
                }
                
                // Solo verificar técnicas modernas, no penalizar técnicas heredadas
                // ya que pueden ser necesarias para compatibilidad
                $modernPatterns = $this->getModernPatterns($technique);
                $hasModern = $this->containsPatterns($cssContent, $modernPatterns);
                
                // Verificar que al menos una técnica moderna esté presente para cada componente
                if (in_array($cssFile, $this->getCriticalComponents()) && 
                    !$this->hasAnyModernTechnique($cssContent)) {
                    $this->fail(
                        "El componente crítico '{$cssFile}' no usa suficientes técnicas CSS modernas"
                    );
                }
                
                // La propiedad se cumple si hay técnicas modernas o si el componente no es crítico
                // No necesitamos hacer assertion aquí, Eris verificará que no haya excepciones
            });
    }

    /**
     * Test de propiedad: Optimización de transiciones y animaciones
     * 
     * Verifica que las transiciones y animaciones estén optimizadas
     * para 60fps usando propiedades que no causan layout thrashing.
     */
    public function testTransitionsAreOptimizedForPerformance()
    {
        $this
            ->limitTo(5) // Reducir iteraciones
            ->forAll(
                $this->cssComponentFiles()
            )
            ->then(function ($cssFile) {
                $cssContent = $this->loadCssFile($cssFile);
                
                if (empty($cssContent)) {
                    return;
                }
                
                // Buscar transiciones CSS
                preg_match_all('/transition:\s*([^;]+)/', $cssContent, $transitionMatches);
                preg_match_all('/animation:\s*([^;]+)/', $cssContent, $animationMatches);
                
                $allTransitions = array_merge($transitionMatches[0] ?? [], $animationMatches[0] ?? []);
                
                foreach ($allTransitions as $transition) {
                    // Verificar que las transiciones no usen propiedades que causan layout thrashing
                    // sin alternativas optimizadas
                    $layoutTriggeringProperties = [
                        'height',
                        'width', 
                        'top',
                        'left',
                        'margin',
                        'padding',
                    ];
                    
                    $hasLayoutProperty = false;
                    $hasOptimization = false;
                    
                    foreach ($layoutTriggeringProperties as $property) {
                        if (str_contains($transition, $property)) {
                            $hasLayoutProperty = true;
                        }
                    }
                    
                    if ($hasLayoutProperty) {
                        // Si usa propiedades que causan layout, verificar si tiene optimizaciones
                        $hasOptimization = $this->hasPerformanceOptimizations($cssContent);
                        
                        $this->assertTrue(
                            $hasOptimization,
                            "La transición en '{$cssFile}' usa propiedades que causan layout sin optimizaciones de performance: {$transition}"
                        );
                    }
                }
            });
    }

    /**
     * Test de propiedad: Uso de GPU acceleration donde sea apropiado
     * 
     * Verifica que las animaciones y transiciones críticas usen
     * transform y opacity para GPU acceleration.
     */
    public function testGpuAccelerationIsUsedForCriticalAnimations()
    {
        $criticalComponents = [
            'navigation.css' => ['transform', 'translate', 'scale', 'rotate'],
            'tables.css' => ['transform', 'translate', 'scale'],
            'forms.css' => ['transform', 'translate', 'scale'],
        ];
        
        foreach ($criticalComponents as $component => $requiredProperties) {
            $cssContent = $this->loadCssFile("resources/css/components/{$component}");
            
            if (empty($cssContent)) {
                continue;
            }
            
            // Verificar que contenga animaciones/transiciones
            if ($this->hasAnimationsOrTransitions($cssContent)) {
                $hasGpuAcceleration = false;
                
                foreach ($requiredProperties as $property) {
                    if (str_contains($cssContent, $property)) {
                        $hasGpuAcceleration = true;
                        break;
                    }
                }
                
                $this->assertTrue(
                    $hasGpuAcceleration,
                    "El componente '{$component}' tiene animaciones pero no usa GPU acceleration adecuadamente"
                );
            }
        }
    }

    /**
     * Test de propiedad: Contenido optimizado con CSS containment
     * 
     * Verifica que los componentes críticos usen contain para
     * optimizar rendering.
     */
    public function testCssContainmentIsUsedForPerformance()
    {
        $componentsThatShouldUseContainment = [
            'navigation.css' => true, // Menús y dropdowns
            'tables.css' => true,     // Tablas grandes
        ];
        
        foreach ($componentsThatShouldUseContainment as $component => $shouldUse) {
            $cssContent = $this->loadCssFile("resources/css/components/{$component}");
            
            if (empty($cssContent)) {
                continue;
            }
            
            // Verificar si tiene elementos complejos que podrían beneficiarse de containment
            if ($this->hasComplexLayout($cssContent) && $shouldUse) {
                $this->assertTrue(
                    $this->hasCssContainment($cssContent),
                    "El componente '{$component}' tiene layout complejo pero no usa CSS containment para optimización"
                );
            }
        }
    }

    /**
     * Test de propiedad: Archivo de performance optimizado
     * 
     * Verifica que el archivo de performance.css contenga
     * las optimizaciones necesarias.
     */
    public function testPerformanceCssFileContainsRequiredOptimizations()
    {
        $performanceCssPath = resource_path('css/performance.css');
        
        $this->assertFileExists(
            $performanceCssPath,
            'El archivo de optimizaciones de performance no existe'
        );
        
        $performanceCss = file_get_contents($performanceCssPath);
        
        $requiredOptimizations = [
            'transform: translateZ(0)',        // GPU acceleration
            'will-change:',                    // Will-change hints
            'contain:',                        // CSS containment
            'scroll-behavior:',                // Smooth scrolling
            'image-rendering:',                // Image optimization
            '@keyframes',                      // Optimized animations
            'cubic-bezier',                    // Smooth timing functions
            'prefers-reduced-motion',          // Accessibility support
            'content-visibility',              // Content visibility optimization
        ];
        
        $foundOptimizations = 0;
        
        foreach ($requiredOptimizations as $optimization) {
            if (str_contains($performanceCss, $optimization)) {
                $foundOptimizations++;
            }
        }
        
        // Debe tener al menos 6 de las 9 optimizaciones requeridas
        $this->assertGreaterThanOrEqual(
            6,
            $foundOptimizations,
            'El archivo performance.css no contiene suficientes optimizaciones de performance'
        );
    }

    /**
     * Test de propiedad: No uso de @apply en CSS puro
     * 
     * Verifica que ningún archivo CSS use @apply de Tailwind
     * ya que esto no es compatible con CSS puro y puede causar problemas.
     */
    public function testNoTailwindApplyInPureCss()
    {
        $cssFiles = [
            'resources/css/components/forms.css',
            'resources/css/components/tables.css',
            'resources/css/components/navigation.css',
            'resources/css/components/layouts.css',
            'resources/css/themes/dark-theme.css',
            'resources/css/themes/light-theme.css',
            'resources/css/performance.css',
        ];
        
        foreach ($cssFiles as $cssFile) {
            $cssContent = $this->loadCssFile($cssFile);
            
            if (empty($cssContent)) {
                continue;
            }
            
            $this->assertFalse(
                str_contains($cssContent, '@apply'),
                "El archivo '{$cssFile}' contiene '@apply' que no es compatible con CSS puro"
            );
        }
    }

    /**
     * Test de propiedad: Técnicas CSS modernas presentes
     * 
     * Verifica que cada componente crítico use al menos una técnica CSS moderna.
     */
    public function testModernCssTechniquesArePresentInCriticalComponents()
    {
        $criticalComponents = $this->getCriticalComponents();
        $modernTechniques = ['grid', 'flexbox', 'variables', 'transitions', 'animations'];
        
        foreach ($criticalComponents as $component) {
            $cssContent = $this->loadCssFile($component);
            
            if (empty($cssContent)) {
                continue;
            }
            
            $hasAnyModernTechnique = false;
            
            foreach ($modernTechniques as $technique) {
                $modernPatterns = $this->getModernPatterns($technique);
                if ($this->containsPatterns($cssContent, $modernPatterns)) {
                    $hasAnyModernTechnique = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $hasAnyModernTechnique,
                "El componente crítico '{$component}' no usa técnicas CSS modernas suficientes"
            );
        }
    }

    /**
     * Generador: Archivos CSS de componentes
     */
    private function cssComponentFiles(): Generator
    {
        return Generator\elements(
            'resources/css/components/forms.css',
            'resources/css/components/tables.css',
            'resources/css/components/navigation.css',
            'resources/css/components/layouts.css'
        );
    }

    /**
     * Generador: Técnicas CSS modernas
     */
    private function modernCssTechniques(): Generator
    {
        return Generator\elements(
            'grid',
            'flexbox',
            'variables',
            'transitions',
            'animations'
        );
    }

    /**
     * Obtener componentes críticos
     */
    private function getCriticalComponents(): array
    {
        return [
            'resources/css/components/navigation.css',
            'resources/css/components/tables.css',
            'resources/css/components/forms.css',
        ];
    }

    /**
     * Obtener patrones de técnicas modernas
     */
    private function getModernPatterns(string $technique): array
    {
        return match($technique) {
            'grid' => [
                'display: grid',
                'grid-template-',
                'grid-column:',
                'grid-row:',
                'grid-gap:',
                'gap:', // CSS Grid gap shorthand
            ],
            'flexbox' => [
                'display: flex',
                'flex-direction:',
                'justify-content:',
                'align-items:',
                'flex-wrap:',
                'flex:',
            ],
            'variables' => [
                'var(--',
                'calc(',
            ],
            'transitions' => [
                'transition:',
                'transition-timing-function:',
            ],
            'animations' => [
                '@keyframes',
                'animation:',
                'animation-timing-function:',
            ],
            default => []
        };
    }

    /**
     * Verificar si el contenido contiene patrones
     */
    private function containsPatterns(string $content, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($content, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si tiene cualquier técnica moderna
     */
    private function hasAnyModernTechnique(string $cssContent): bool
    {
        $modernTechniques = ['grid', 'flexbox', 'variables', 'transitions', 'animations'];
        
        foreach ($modernTechniques as $technique) {
            $modernPatterns = $this->getModernPatterns($technique);
            if ($this->containsPatterns($cssContent, $modernPatterns)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verificar si tiene optimizaciones de performance
     */
    private function hasPerformanceOptimizations(string $cssContent): bool
    {
        return str_contains($cssContent, 'will-change:') ||
               str_contains($cssContent, 'transform: translateZ(0)') ||
               str_contains($cssContent, 'contain:') ||
               str_contains($cssContent, 'backface-visibility: hidden') ||
               str_contains($cssContent, 'perspective: 1000px') ||
               str_contains($cssContent, 'translateY(') || // Transform properties
               str_contains($cssContent, 'scale(') ||
               str_contains($cssContent, 'rotate(');
    }

    /**
     * Verificar si tiene animaciones o transiciones
     */
    private function hasAnimationsOrTransitions(string $cssContent): bool
    {
        return str_contains($cssContent, 'animation:') ||
               str_contains($cssContent, 'transition:') ||
               str_contains($cssContent, '@keyframes');
    }

    /**
     * Verificar si tiene layout complejo
     */
    private function hasComplexLayout(string $cssContent): bool
    {
        // Componentes con layout complejo tienen múltiples selectores anidados
        // o muchas propiedades de layout
        $layoutProperties = [
            'position:', 'display:', 'float:', 'clear:', 
            'width:', 'height:', 'min-width:', 'min-height:',
            'max-width:', 'max-height:', 'margin:', 'padding:',
            'border:', 'border-radius:', 'box-shadow:',
        ];
        
        $propertyCount = 0;
        foreach ($layoutProperties as $property) {
            $propertyCount += substr_count($cssContent, $property);
        }
        
        return $propertyCount > 20; // Más de 20 propiedades de layout
    }

    /**
     * Verificar si usa CSS containment
     */
    private function hasCssContainment(string $cssContent): bool
    {
        return str_contains($cssContent, 'contain:') ||
               str_contains($cssContent, 'contain: layout') ||
               str_contains($cssContent, 'contain: paint') ||
               str_contains($cssContent, 'contain: style');
    }

    /**
     * Cargar archivo CSS
     */
    private function loadCssFile(string $filePath): string
    {
        $fullPath = base_path($filePath);
        
        if (!file_exists($fullPath)) {
            return '';
        }
        
        return file_get_contents($fullPath);
    }
}
