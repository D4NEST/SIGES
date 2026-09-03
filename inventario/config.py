"""
Configuración centralizada para la aplicación de inventario
"""
import os
from datetime import timedelta
from dotenv import load_dotenv

load_dotenv()

class Config:
    """Configuración base"""
    
    # Configuración básica de Flask
    SECRET_KEY = os.environ.get('SECRET_KEY', 'clave-secreta-desarrollo-32-caracteres-aqui')
    FLASK_ENV = os.environ.get('FLASK_ENV', 'development')
    
    # Configuración de sesiones
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SAMESITE = 'Lax'
    SESSION_COOKIE_NAME = 'inventario_session'
    PERMANENT_SESSION_LIFETIME = timedelta(hours=8)
    SESSION_REFRESH_EACH_REQUEST = True
    SESSION_COOKIE_PATH = '/'
    SESSION_COOKIE_DOMAIN = None
    
    # Configuración de base de datos
    DATABASE_URL = os.environ.get('DATABASE_URL')
    DB_HOST = os.environ.get('DB_HOST', 'localhost')
    DB_NAME = os.environ.get('DB_NAME', 'inventario_sistema')
    DB_USER = os.environ.get('DB_USER', 'postgres')
    DB_PASS = os.environ.get('DB_PASS', 'password')
    DB_PORT = os.environ.get('DB_PORT', '5432')
    
    # Configuración de autenticación
    ADMIN_USERNAME = os.environ.get('ADMIN_USERNAME', 'admin')
    ADMIN_PASSWORD = os.environ.get('ADMIN_PASSWORD', '02120212$')
    
    # Configuración de CORS
    ALLOWED_ORIGINS = os.environ.get('ALLOWED_ORIGINS', 
        'http://localhost:10000,http://127.0.0.1:10000,https://inventario-soluciones-logicas.onrender.com'
    ).split(',')
    
    # Puerto del servidor
    PORT = int(os.environ.get('PORT', 10000))
    
    # Configuración de logging
    LOG_LEVEL = os.environ.get('LOG_LEVEL', 'INFO')

class DevelopmentConfig(Config):
    """Configuración para desarrollo"""
    DEBUG = True
    SESSION_COOKIE_SECURE = False
    
class ProductionConfig(Config):
    """Configuración para producción"""
    DEBUG = False
    SESSION_COOKIE_SECURE = True
    PREFERRED_URL_SCHEME = 'https'
    
    # Validaciones adicionales para producción
    @classmethod
    def validate_production_config(cls):
        """Valida que la configuración de producción sea segura"""
        errors = []
        
        if cls.SECRET_KEY == 'clave-secreta-desarrollo-32-caracteres-aqui':
            errors.append("SECRET_KEY debe cambiarse en producción")
            
        if cls.ADMIN_PASSWORD == '02120212$':
            errors.append("ADMIN_PASSWORD debe cambiarse en producción")
            
        if not cls.DATABASE_URL:
            errors.append("DATABASE_URL es requerida en producción")
            
        if errors:
            raise ValueError(f"Errores de configuración: {', '.join(errors)}")

class TestingConfig(Config):
    """Configuración para testing"""
    TESTING = True
    SESSION_COOKIE_SECURE = False
    DB_NAME = 'inventario_test'

# Mapeo de configuraciones
config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'testing': TestingConfig,
    'default': DevelopmentConfig
}

def get_config():
    """Obtiene la configuración según el entorno"""
    env = os.environ.get('FLASK_ENV', 'development')
    config_class = config.get(env, config['default'])
    
    # Validar configuración de producción
    if env == 'production':
        config_class.validate_production_config()
    
    return config_class