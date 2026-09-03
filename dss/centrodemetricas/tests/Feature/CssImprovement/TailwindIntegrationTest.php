<?php

namespace Tests\Feature\CssImprovement;

use Tests\TestCase;

/**
 * Test de integración de Tailwind con variables CSS personalizadas
 * 
 * Verifica que Tailwind esté correctamente configurado para usar
 * nuestras variables CSS personalizadas.
 */
class TailwindIntegrationTest extends TestCase
{
    /**
     * Test: Configuración de Tailwind carga correctamente
     */
    public function testTailwindConfigFileExistsAndIsValid()
    {
        $configPath = base_path('tailwind.config.js');
        
        $this->assertFileExists(
            $configPath,
            'El archivo de configuración de Tailwind no existe'
        );
        
        $configContent = file_get_contents($configPath);
        
        // Verificar que la configuración contiene extensiones de tema
        $this->assertStringContainsString(
            'extend',
            $configContent,
            'La configuración de Tailwind no contiene sección extend'
        );
        
        // Verificar que contiene configuraciones de colores personalizados
        $this->assertStringContainsString(
            'colors',
            $configContent,
            'La configuración de Tailwind no contiene configuración de colores'
        );
    }
    
    /**
     * Test: Variables CSS están definidas en la configuración de Tailwind
     */
    public function testCssVariablesAreIntegratedInTailwindConfig()
    {
        $configContent = file_get_contents(base_path('tailwind.config.js'));
        
        $requiredColorVariables = [
            'var(--color-primary',
            'var(--color-secondary',
            'var(--color-success',
            'var(--color-warning',
            'var(--color-error',
            'var(--color-info',
        ];
        
        foreach ($requiredColorVariables as $variable) {
            $this->assertStringContainsString(
                $variable,
                $configContent,
                "La variable CSS '{$variable}' no está integrada en la configuración de Tailwind"
            );
        }
        
        // Verificar otras integraciones
        $otherIntegrations = [
            'var(--font-size-',
            'var(--spacing-',
            'var(--border-radius-',
            'var(--shadow-',
            'var(--transition-',
            'var(--z-',
        ];
        
        foreach ($otherIntegrations as $integration) {
            // Al menos algunas de estas deben estar presentes
            $this->assertTrue(
                str_contains($configContent, $integration),
                "Las variables CSS '{$integration}' no están integradas en Tailwind"
            );
        }
    }
    
    /**
     * Test: Modo oscuro está configurado correctamente
     */
    public function testDarkModeIsConfigured()
    {
        $configContent = file_get_contents(base_path('tailwind.config.js'));
        
        $this->assertStringContainsString(
            "darkMode: 'class'",
            $configContent,
            'El modo oscuro no está configurado como clase en Tailwind'
        );
    }
    
    /**
     * Test: Archivo CSS principal importa correctamente
     */
    public function testMainCssFileImportsCorrectly()
    {
        $appCssPath = resource_path('css/app.css');
        
        $this->assertFileExists(
            $appCssPath,
            'El archivo CSS principal no existe'
        );
        
        $appCssContent = file_get_contents($appCssPath);
        
        // Verificar importaciones requeridas
        $requiredImports = [
            '@tailwind base',
            '@tailwind components',
            '@tailwind utilities',
            '@import \'./variables.css\'',
        ];
        
        foreach ($requiredImports as $import) {
            $this->assertStringContainsString(
                $import,
                $appCssContent,
                "El archivo CSS principal no importa: {$import}"
            );
        }
    }
    
    /**
     * Test: Componentes CSS están organizados correctamente
     */
    public function testCssComponentsAreOrganized()
    {
        $componentsDir = resource_path('css/components');
        
        $this->assertDirectoryExists(
            $componentsDir,
            'El directorio de componentes CSS no existe'
        );
        
        $requiredComponentFiles = [
            'forms.css',
            'tables.css',
            'navigation.css',
            'layouts.css',
        ];
        
        foreach ($requiredComponentFiles as $file) {
            $filePath = resource_path("css/components/{$file}");
            
            $this->assertFileExists(
                $filePath,
                "El archivo de componente CSS '{$file}' no existe"
            );
            
            // Verificar que los archivos no estén vacíos
            $content = file_get_contents($filePath);
            $this->assertGreaterThan(
                0,
                strlen(trim($content)),
                "El archivo de componente CSS '{$file}' está vacío"
            );
        }
    }
    
    /**
     * Test: Temas CSS están configurados
     */
    public function testCssThemesAreConfigured()
    {
        $themesDir = resource_path('css/themes');
        
        $this->assertDirectoryExists(
            $themesDir,
            'El directorio de temas CSS no existe'
        );
        
        $requiredThemeFiles = [
            'dark-theme.css',
            'light-theme.css',
        ];
        
        foreach ($requiredThemeFiles as $file) {
            $filePath = resource_path("css/themes/{$file}");
            
            $this->assertFileExists(
                $filePath,
                "El archivo de tema CSS '{$file}' no existe"
            );
            
            // Verificar contenido específico de temas
            $content = file_get_contents($filePath);
            
            if ($file === 'dark-theme.css') {
                $this->assertStringContainsString(
                    'Tema Oscuro',
                    $content,
                    'El tema oscuro no tiene la documentación correcta'
                );
                $this->assertStringContainsString(
                    'var(--bg-dark)',
                    $content,
                    'El tema oscuro no usa variables de fondo oscuro'
                );
            }
            
            if ($file === 'light-theme.css') {
                $this->assertStringContainsString(
                    'Tema Claro',
                    $content,
                    'El tema claro no tiene la documentación correcta'
                );
                $this->assertStringContainsString(
                    'var(--bg-light)',
                    $content,
                    'El tema claro no usa variables de fondo claro'
                );
            }
        }
    }
    
    /**
     * Test: Variables CSS están definidas correctamente
     */
    public function testCssVariablesFileExistsAndIsComplete()
    {
        $variablesPath = resource_path('css/variables.css');
        
        $this->assertFileExists(
            $variablesPath,
            'El archivo de variables CSS no existe'
        );
        
        $variablesContent = file_get_contents($variablesPath);
        
        // Verificar secciones principales
        $requiredSections = [
            'PALETA DE COLORES PRINCIPALES',
            'COLORES DE TEXTO - MODO CLARO',
            'COLORES DE TEXTO - MODO OSCURO',
            'FONDOS',
            'BORDES',
            'ESPACIADO',
            'TIPOGRAFÍA',
            'RADIOS DE BORDE',
            'SOMBRAS',
        ];
        
        foreach ($requiredSections as $section) {
            $this->assertStringContainsString(
                $section,
                $variablesContent,
                "La sección '{$section}' no está en el archivo de variables CSS"
            );
        }
        
        // Verificar variables críticas
        $criticalVariables = [
            '--color-primary:',
            '--color-secondary:',
            '--text-primary-dark:',
            '--text-primary-light:',
            '--bg-dark:',
            '--bg-light:',
            '--spacing-md:',
            '--font-size-base:',
            '--border-radius-md:',
        ];
        
        foreach ($criticalVariables as $variable) {
            $this->assertStringContainsString(
                $variable,
                $variablesContent,
                "La variable crítica '{$variable}' no está definida"
            );
        }
    }
}