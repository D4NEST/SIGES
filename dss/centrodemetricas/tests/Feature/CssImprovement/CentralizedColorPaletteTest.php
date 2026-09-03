<?php

namespace Tests\Feature\CssImprovement;

use Eris\Generator;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Feature: mejora-estilos-legibilidad, Property 18: Paleta centralizada de colores de estado
 * Validates: Requirements 5.2
 * 
 * Propiedad: Para cualquier color usado para indicar estados (éxito, error, advertencia, información), 
 * debe provenir de una paleta definida centralmente.
 */
class CentralizedColorPaletteTest extends TestCase
{
    use TestTrait;

    /**
     * Test de propiedad: Colores de estado definidos centralmente
     * 
     * Verifica que todos los colores de estado estén definidos en la paleta centralizada
     * y se usen consistentemente en todo el sistema.
     */
    public function testStateColorsAreFromCentralizedPalette()
    {
        $this
            ->forAll(
                $this->stateTypes(),
                $this->cssFiles()
            )
            ->then(function ($stateType, $cssFile) {
                $cssContent = $this->loadCssFile($cssFile);
                
                // Buscar uso de colores de estado en el archivo CSS
                $stateColorFound = $this->containsStateColor($cssContent, $stateType);
                
                if ($stateColorFound) {
                    // Verificar que el color esté definido en la paleta centralizada
                    $this->assertTrue(
                        $this->isColorFromCentralPalette($cssContent, $stateType),
                        "El color de estado '{$stateType}' en '{$cssFile}' no proviene de la paleta centralizada"
                    );
                }
                
                // La propiedad se cumple si no hay colores de estado o si provienen de la paleta
                // No necesitamos hacer assertion aquí, Eris verificará que no haya excepciones
            });
    }

    /**
     * Test de propiedad: Variables CSS de estado definidas
     * 
     * Verifica que todas las variables CSS para colores de estado estén definidas.
     */
    public function testStateColorVariablesAreDefined()
    {
        $requiredStateVariables = [
            '--color-success',
            '--color-warning',
            '--color-error',
            '--color-info',
            '--color-success-light',
            '--color-warning-light',
            '--color-error-light',
            '--color-info-light',
            '--color-success-dark',
            '--color-warning-dark',
            '--color-error-dark',
            '--color-info-dark',
        ];

        $cssContent = $this->loadCssVariablesFile();
        
        foreach ($requiredStateVariables as $variable) {
            $this->assertStringContainsString(
                $variable,
                $cssContent,
                "La variable CSS de estado '{$variable}' no está definida en la paleta centralizada"
            );
        }
    }

    /**
     * Test de propiedad: Consistencia de colores de estado
     * 
     * Verifica que los mismos colores de estado se usen en todos los componentes.
     */
    public function testStateColorConsistencyAcrossComponents()
    {
        $componentFiles = [
            'resources/css/components/forms.css',
            'resources/css/components/tables.css',
            'resources/css/components/navigation.css',
        ];
        
        $stateColorUsage = [];
        
        foreach ($componentFiles as $file) {
            if (!file_exists(base_path($file))) {
                continue;
            }
            
            $cssContent = file_get_contents(base_path($file));
            
            // Extraer colores de estado usados en este componente
            foreach (['success', 'warning', 'error', 'info'] as $state) {
                if (preg_match_all("/var\\(--color-{$state}[^)]*\\)/", $cssContent, $matches)) {
                    $stateColorUsage[$state][$file] = array_unique($matches[0]);
                }
            }
        }
        
        // Verificar consistencia
        foreach ($stateColorUsage as $state => $fileUsages) {
            if (count($fileUsages) > 1) {
                $firstFile = array_key_first($fileUsages);
                $expectedColors = $fileUsages[$firstFile];
                
                foreach ($fileUsages as $file => $colors) {
                    $this->assertEquals(
                        $expectedColors,
                        $colors,
                        "Los colores de estado '{$state}' no son consistentes entre '{$firstFile}' y '{$file}'"
                    );
                }
            }
        }
    }

    /**
     * Test de ejemplo: Valores específicos de colores de estado
     * 
     * Verifica que los colores de estado tengan los valores correctos.
     */
    public function testStateColorValuesAreCorrect()
    {
        $expectedColors = [
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'error' => '#ef4444',
            'info' => '#3b82f6',
        ];
        
        $cssContent = $this->loadCssVariablesFile();
        
        foreach ($expectedColors as $state => $expectedColor) {
            // Buscar definición de la variable
            if (preg_match("/--color-{$state}:\s*([^;]+)/", $cssContent, $matches)) {
                $actualColor = trim($matches[1]);
                
                $this->assertEquals(
                    $expectedColor,
                    $actualColor,
                    "El color de estado '{$state}' es '{$actualColor}', se esperaba '{$expectedColor}'"
                );
            } else {
                $this->fail("Variable --color-{$state} no encontrada en la paleta centralizada");
            }
        }
    }

    /**
     * Generador: Tipos de estado
     */
    private function stateTypes(): Generator
    {
        return Generator\elements('success', 'warning', 'error', 'info');
    }

    /**
     * Generador: Archivos CSS del sistema
     */
    private function cssFiles(): Generator
    {
        return Generator\elements(
            'resources/css/components/forms.css',
            'resources/css/components/tables.css',
            'resources/css/components/navigation.css',
            'resources/css/components/layouts.css',
            'resources/css/themes/dark-theme.css',
            'resources/css/themes/light-theme.css'
        );
    }

    /**
     * Verificar si el contenido CSS contiene colores de estado
     */
    private function containsStateColor(string $cssContent, string $stateType): bool
    {
        $statePatterns = [
            "color-{$stateType}",
            "state-{$stateType}",
            "--color-{$stateType}",
            "{$stateType}-color",
        ];
        
        foreach ($statePatterns as $pattern) {
            if (stripos($cssContent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verificar si el color proviene de la paleta centralizada
     */
    private function isColorFromCentralPalette(string $cssContent, string $stateType): bool
    {
        // Buscar uso de variables CSS de la paleta centralizada
        $palettePatterns = [
            "var\\(--color-{$stateType}",
            "var\\(--theme-{$stateType}",
            "color-{$stateType}",
        ];
        
        foreach ($palettePatterns as $pattern) {
            if (preg_match("/{$pattern}[^)]*\\)/", $cssContent)) {
                return true;
            }
        }
        
        return false;
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

    /**
     * Cargar archivo de variables CSS
     */
    private function loadCssVariablesFile(): string
    {
        $filePath = resource_path('css/variables.css');
        
        if (!file_exists($filePath)) {
            return '';
        }
        
        return file_get_contents($filePath);
    }
}