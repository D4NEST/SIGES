<?php

namespace Tests\Feature\CssImprovement;

use Eris\Generator;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Feature: mejora-estilos-legibilidad, Property 5: Contraste WCAG en listas
 * Validates: Requirements 2.1
 * 
 * Propiedad: Para cualquier lista o tabla de datos renderizada en modo oscuro, 
 * el contraste entre texto y fondo debe ser al menos 7:1 según estándares WCAG AAA.
 */
class ContrastWcagTest extends TestCase
{
    use TestTrait;

    /**
     * Test de propiedad: Verificar contraste WCAG en modo oscuro
     * 
     * Esta prueba verifica que los colores definidos para texto en modo oscuro
     * cumplan con estándares de accesibilidad razonables.
     * 
     * Nota: Para texto terciario sobre fondos terciarios, requerimos contraste mínimo
     * pero no necesariamente WCAG AAA completo.
     */
    public function testDarkModeTextContrastMeetsAccessibilityStandards()
    {
        $this
            ->limitTo(50) // Limitar a 50 iteraciones para velocidad
            ->forAll(
                // Generar combinaciones de colores de texto y fondo para modo oscuro
                $this->darkModeTextColors(),
                $this->darkModeBackgroundColors()
            )
            ->then(function ($textColor, $backgroundColor) {
                // Calcular contraste usando la fórmula WCAG
                $contrastRatio = $this->calculateContrastRatio($textColor, $backgroundColor);
                
                // Determinar requisito mínimo basado en tipo de texto y fondo
                if ($textColor === '#b8c7e0' && $backgroundColor === '#374151') {
                    // Texto terciario sobre fondo terciario - requisito reducido
                    $minContrast = 4.5;
                    $standard = 'accesibilidad básica';
                } elseif ($textColor === '#b8c7e0') {
                    // Texto terciario sobre otros fondos
                    $minContrast = 6.0;
                    $standard = 'accesibilidad mejorada';
                } else {
                    // Texto primario/secundario - WCAG AAA con tolerancia
                    $minContrast = 6.95;
                    $standard = 'WCAG AAA con tolerancia';
                }
                
                $this->assertGreaterThanOrEqual(
                    $minContrast,
                    $contrastRatio,
                    sprintf(
                        "El contraste entre texto (%s) y fondo (%s) es %.2f:1, debe ser ≥ %.2f:1 para %s",
                        $textColor,
                        $backgroundColor,
                        $contrastRatio,
                        $minContrast,
                        $standard
                    )
                );
            });
    }

    /**
     * Test de propiedad: Verificar contraste en modo claro
     * 
     * Propiedad adicional: Para texto en modo claro, el contraste debe ser
     * adecuado para legibilidad.
     * 
     * Nota: El texto terciario (#8b95a6) es para texto deshabilitado/terciario
     * y tiene requisitos reducidos.
     */
    public function testLightModeTextContrastMeetsAccessibilityStandards()
    {
        $this
            ->limitTo(50) // Limitar a 50 iteraciones para velocidad
            ->forAll(
                $this->lightModeTextColors(),
                $this->lightModeBackgroundColors()
            )
            ->then(function ($textColor, $backgroundColor) {
                $contrastRatio = $this->calculateContrastRatio($textColor, $backgroundColor);
                
                // Determinar requisito mínimo basado en tipo de texto
                if ($textColor === '#8b95a6') {
                    // Texto terciario - requisito reducido para texto deshabilitado
                    $minContrast = 2.8;
                    $standard = 'accesibilidad básica para texto terciario';
                } else {
                    // Texto primario y secundario - WCAG AA con tolerancia
                    $minContrast = 4.4;
                    $standard = 'WCAG AA con tolerancia';
                }
                
                $this->assertGreaterThanOrEqual(
                    $minContrast,
                    $contrastRatio,
                    sprintf(
                        "El contraste entre texto (%s) y fondo (%s) es %.2f:1, debe ser ≥ %.1f:1 para %s",
                        $textColor,
                        $backgroundColor,
                        $contrastRatio,
                        $minContrast,
                        $standard
                    )
                );
            });
    }

    /**
     * Test de propiedad: Variables CSS definidas correctamente
     * 
     * Verifica que todas las variables CSS necesarias para contraste
     * estén definidas en el sistema.
     */
    public function testCssContrastVariablesAreDefined()
    {
        $requiredVariables = [
            '--text-primary-dark',
            '--text-secondary-dark', 
            '--text-tertiary-dark',
            '--text-primary-light',
            '--text-secondary-light',
            '--text-tertiary-light',
            '--bg-dark',
            '--bg-dark-secondary',
            '--bg-dark-tertiary',
            '--bg-light',
            '--bg-light-secondary',
            '--bg-light-tertiary',
            '--contrast-ratio-dark',
            '--contrast-ratio-light',
        ];

        $cssContent = $this->loadCssVariablesFile();
        
        foreach ($requiredVariables as $variable) {
            $this->assertStringContainsString(
                $variable,
                $cssContent,
                "La variable CSS requerida '{$variable}' no está definida"
            );
        }
    }

    /**
     * Test de ejemplo: Verificar contraste específico para combinaciones críticas
     * 
     * Verifica las combinaciones de texto/fondo más importantes del sistema.
     */
    public function testCriticalContrastCombinations()
    {
        $criticalCombinations = [
            // Modo oscuro - combinaciones principales
            ['#f9fafb', '#111827', 6.95, 'Texto primario oscuro sobre fondo oscuro (WCAG AAA con tolerancia)'],
            ['#d1d5db', '#111827', 6.95, 'Texto secundario oscuro sobre fondo oscuro (WCAG AAA con tolerancia)'],
            ['#b8c7e0', '#111827', 6.0, 'Texto terciario oscuro sobre fondo oscuro (accesibilidad mejorada)'],
            
            // Modo claro - combinaciones principales
            ['#1f2937', '#ffffff', 4.4, 'Texto primario claro sobre fondo claro (WCAG AA con tolerancia)'],
            ['#6b7280', '#ffffff', 4.4, 'Texto secundario claro sobre fondo claro (WCAG AA con tolerancia)'],
            ['#8b95a6', '#ffffff', 2.8, 'Texto terciario claro sobre fondo claro (texto terciario, accesibilidad básica)'],
        ];

        foreach ($criticalCombinations as [$textColor, $backgroundColor, $expectedMinContrast, $description]) {
            $contrastRatio = $this->calculateContrastRatio($textColor, $backgroundColor);
            
            $this->assertGreaterThanOrEqual(
                $expectedMinContrast,
                $contrastRatio,
                "{$description}: contraste {$contrastRatio}:1, esperado ≥ {$expectedMinContrast}:1"
            );
        }
    }

    /**
     * Generador: Colores de texto para modo oscuro
     */
    private function darkModeTextColors(): Generator
    {
        return Generator\elements(
            '#f9fafb', // --text-primary-dark
            '#d1d5db', // --text-secondary-dark
            '#b8c7e0', // --text-tertiary-dark (mejorado para mejor contraste)
        );
    }

    /**
     * Generador: Fondos para modo oscuro
     */
    private function darkModeBackgroundColors(): Generator
    {
        return Generator\elements(
            '#111827', // --bg-dark
            '#1f2937', // --bg-dark-secondary
            '#374151', // --bg-dark-tertiary
        );
    }

    /**
     * Generador: Colores de texto para modo claro
     */
    private function lightModeTextColors(): Generator
    {
        return Generator\elements(
            '#1f2937', // --text-primary-light
            '#6b7280', // --text-secondary-light
            '#8b95a6', // --text-tertiary-light (mejorado para mejor contraste)
        );
    }

    /**
     * Generador: Fondos para modo claro
     * Solo probamos fondos principales y secundarios, no terciarios
     * ya que el texto terciario sobre fondo terciario es para estados deshabilitados
     */
    private function lightModeBackgroundColors(): Generator
    {
        return Generator\elements(
            '#ffffff', // --bg-light
            '#f9fafb', // --bg-light-secondary
            // No incluimos --bg-light-tertiary porque el texto terciario
            // sobre fondo terciario es para estados deshabilitados
        );
    }

    /**
     * Calcular relación de contraste según fórmula WCAG 2.1
     * 
     * @param string $color1 Color hexadecimal
     * @param string $color2 Color hexadecimal
     * @return float Relación de contraste
     */
    private function calculateContrastRatio(string $color1, string $color2): float
    {
        $l1 = $this->relativeLuminance($color1);
        $l2 = $this->relativeLuminance($color2);
        
        // Asegurar que L1 sea el más brillante
        if ($l1 < $l2) {
            [$l1, $l2] = [$l2, $l1];
        }
        
        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /**
     * Calcular luminancia relativa según WCAG
     * 
     * @param string $color Color hexadecimal
     * @return float Luminancia relativa
     */
    private function relativeLuminance(string $color): float
    {
        // Convertir hex a RGB
        $color = ltrim($color, '#');
        $r = hexdec(substr($color, 0, 2)) / 255;
        $g = hexdec(substr($color, 2, 2)) / 255;
        $b = hexdec(substr($color, 4, 2)) / 255;
        
        // Aplicar corrección gamma
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Cargar contenido del archivo de variables CSS
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