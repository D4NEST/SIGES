#!/usr/bin/env python3
"""
Script de pruebas básicas para la API del sistema de inventario
"""
import requests
import json
import sys
from datetime import datetime

# Configuración
BASE_URL = "http://localhost:10000"
API_URL = f"{BASE_URL}/api"

class InventoryAPITester:
    def __init__(self):
        self.session = requests.Session()
        self.authenticated = False
        
    def test_health_check(self):
        """Prueba el endpoint de health check"""
        print("🔍 Probando health check...")
        try:
            response = self.session.get(f"{BASE_URL}/health")
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Health check OK - Status: {data.get('status')}")
                return True
            else:
                print(f"❌ Health check falló - Status: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Error en health check: {e}")
            return False
    
    def test_login(self, username="admin", password="02120212$"):
        """Prueba el login"""
        print(f"🔐 Probando login con usuario: {username}")
        try:
            response = self.session.post(
                f"{API_URL}/auth/login",
                json={"username": username, "password": password},
                headers={"Content-Type": "application/json"}
            )
            
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Login exitoso - Usuario: {data.get('user', {}).get('name')}")
                self.authenticated = True
                return True
            else:
                print(f"❌ Login falló - Status: {response.status_code}")
                print(f"   Respuesta: {response.text}")
                return False
        except Exception as e:
            print(f"❌ Error en login: {e}")
            return False
    
    def test_auth_check(self):
        """Prueba la verificación de autenticación"""
        print("🔍 Verificando autenticación...")
        try:
            response = self.session.get(f"{API_URL}/auth/check")
            if response.status_code == 200:
                data = response.json()
                if data.get('authenticated'):
                    print(f"✅ Autenticación válida - Usuario: {data.get('user', {}).get('name')}")
                    return True
                else:
                    print("❌ No autenticado")
                    return False
            else:
                print(f"❌ Error verificando auth - Status: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Error verificando auth: {e}")
            return False
    
    def test_get_tipos_pieza(self):
        """Prueba obtener tipos de pieza"""
        print("📦 Probando obtener tipos de pieza...")
        try:
            response = self.session.get(f"{API_URL}/inventario/tipos_pieza")
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Tipos de pieza obtenidos - Total: {len(data)}")
                if data:
                    print(f"   Ejemplo: {data[0].get('tipo_modelo')}")
                return True
            else:
                print(f"❌ Error obteniendo tipos - Status: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Error obteniendo tipos: {e}")
            return False
    
    def test_get_productos(self):
        """Prueba obtener productos"""
        print("🛍️ Probando obtener productos...")
        try:
            response = self.session.get(f"{API_URL}/inventario/productos/detallado")
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Productos obtenidos - Total: {len(data)}")
                return True
            else:
                print(f"❌ Error obteniendo productos - Status: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Error obteniendo productos: {e}")
            return False
    
    def test_get_estadisticas(self):
        """Prueba obtener estadísticas"""
        print("📊 Probando obtener estadísticas...")
        try:
            response = self.session.get(f"{API_URL}/inventario/estadisticas")
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Estadísticas obtenidas:")
                print(f"   Total modelos: {data.get('total_modelos', 0)}")
                print(f"   Total seriales: {data.get('total_seriales', 0)}")
                print(f"   Stock bajo: {data.get('modelos_stock_bajo', 0)}")
                return True
            else:
                print(f"❌ Error obteniendo estadísticas - Status: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Error obteniendo estadísticas: {e}")
            return False
    
    def test_create_producto(self):
        """Prueba crear un producto de prueba"""
        print("➕ Probando crear producto de prueba...")
        try:
            # Primero obtener un tipo de pieza
            tipos_response = self.session.get(f"{API_URL}/inventario/tipos_pieza")
            if tipos_response.status_code != 200:
                print("❌ No se pudieron obtener tipos de pieza")
                return False
            
            tipos = tipos_response.json()
            if not tipos:
                print("❌ No hay tipos de pieza disponibles")
                return False
            
            tipo_id = tipos[0]['tipo_id']
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            
            producto_data = {
                "nombre": f"Producto de Prueba {timestamp}",
                "marca": "TEST",
                "modelo": f"MODEL-{timestamp}",
                "descripcion": "Producto creado por script de pruebas",
                "tipo_pieza_id": tipo_id,
                "codigo_sku": f"TEST-{timestamp}"
            }
            
            response = self.session.post(
                f"{API_URL}/inventario/productos",
                json=producto_data,
                headers={"Content-Type": "application/json"}
            )
            
            if response.status_code == 201:
                data = response.json()
                print(f"✅ Producto creado - ID: {data.get('producto_id')}")
                return data.get('producto_id')
            else:
                print(f"❌ Error creando producto - Status: {response.status_code}")
                print(f"   Respuesta: {response.text}")
                return False
        except Exception as e:
            print(f"❌ Error creando producto: {e}")
            return False
    
    def test_create_serial(self, producto_id):
        """Prueba crear un serial"""
        print(f"🏷️ Probando crear serial para producto {producto_id}...")
        try:
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            serial_data = {
                "producto_id": producto_id,
                "codigo_unico_serial": f"SN-TEST-{timestamp}"
            }
            
            response = self.session.post(
                f"{API_URL}/inventario/serial",
                json=serial_data,
                headers={"Content-Type": "application/json"}
            )
            
            if response.status_code == 201:
                data = response.json()
                print(f"✅ Serial creado - ID: {data.get('serial_id')}")
                return data.get('serial_id')
            else:
                print(f"❌ Error creando serial - Status: {response.status_code}")
                print(f"   Respuesta: {response.text}")
                return False
        except Exception as e:
            print(f"❌ Error creando serial: {e}")
            return False
    
    def test_logout(self):
        """Prueba el logout"""
        print("🚪 Probando logout...")
        try:
            response = self.session.post(f"{API_URL}/auth/logout")
            if response.status_code == 200:
                print("✅ Logout exitoso")
                self.authenticated = False
                return True
            else:
                print(f"❌ Error en logout - Status: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Error en logout: {e}")
            return False
    
    def run_all_tests(self):
        """Ejecuta todas las pruebas"""
        print("🧪 INICIANDO PRUEBAS DE LA API")
        print("=" * 50)
        
        tests_passed = 0
        total_tests = 0
        
        # Lista de pruebas
        tests = [
            ("Health Check", self.test_health_check),
            ("Login", lambda: self.test_login()),
            ("Auth Check", self.test_auth_check),
            ("Tipos de Pieza", self.test_get_tipos_pieza),
            ("Productos", self.test_get_productos),
            ("Estadísticas", self.test_get_estadisticas),
        ]
        
        # Ejecutar pruebas básicas
        for test_name, test_func in tests:
            total_tests += 1
            print(f"\n--- {test_name} ---")
            if test_func():
                tests_passed += 1
            else:
                print(f"❌ Prueba '{test_name}' falló")
        
        # Pruebas de creación (solo si estamos autenticados)
        if self.authenticated:
            total_tests += 2
            print(f"\n--- Crear Producto ---")
            producto_id = self.test_create_producto()
            if producto_id:
                tests_passed += 1
                
                print(f"\n--- Crear Serial ---")
                if self.test_create_serial(producto_id):
                    tests_passed += 1
        
        # Logout
        if self.authenticated:
            total_tests += 1
            print(f"\n--- Logout ---")
            if self.test_logout():
                tests_passed += 1
        
        # Resumen
        print("\n" + "=" * 50)
        print(f"📊 RESUMEN DE PRUEBAS")
        print(f"✅ Pruebas exitosas: {tests_passed}/{total_tests}")
        print(f"❌ Pruebas fallidas: {total_tests - tests_passed}/{total_tests}")
        
        if tests_passed == total_tests:
            print("🎉 ¡Todas las pruebas pasaron!")
            return True
        else:
            print("⚠️ Algunas pruebas fallaron")
            return False

def main():
    """Función principal"""
    print("🧪 Script de Pruebas - Sistema de Inventario")
    print("=" * 50)
    print(f"🌐 URL Base: {BASE_URL}")
    print(f"📡 API URL: {API_URL}")
    print()
    
    # Verificar que el servidor esté corriendo
    try:
        response = requests.get(BASE_URL, timeout=5)
        print("✅ Servidor accesible")
    except Exception as e:
        print(f"❌ No se puede acceder al servidor: {e}")
        print("💡 Asegúrate de que la aplicación esté corriendo:")
        print("   python app.py")
        sys.exit(1)
    
    # Ejecutar pruebas
    tester = InventoryAPITester()
    success = tester.run_all_tests()
    
    if success:
        sys.exit(0)
    else:
        sys.exit(1)

if __name__ == "__main__":
    main()