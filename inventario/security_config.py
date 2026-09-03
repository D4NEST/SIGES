"""
Configuración avanzada de seguridad
"""
import os
from datetime import timedelta

class SecurityConfig:
    """Configuración de seguridad centralizada"""
    
    # Configuración de autenticación
    MAX_LOGIN_ATTEMPTS = 5
    LOCKOUT_DURATION = timedelta(minutes=15)
    SESSION_TIMEOUT = timedelta(hours=8)
    PASSWORD_MIN_LENGTH = 8
    
    # Configuración de rate limiting
    RATE_LIMIT_REQUESTS = 100
    RATE_LIMIT_WINDOW = timedelta(minutes=1)
    
    # Headers de seguridad
    SECURITY_HEADERS = {
        'X-Content-Type-Options': 'nosniff',
        'X-Frame-Options': 'DENY',
        'X-XSS-Protection': '1; mode=block',
        'Strict-Transport-Security': 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy': "default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com",
        'Referrer-Policy': 'strict-origin-when-cross-origin',
        'Permissions-Policy': 'geolocation=(), microphone=(), camera=()'
    }
    
    # IPs permitidas (whitelist) - vacío significa todas permitidas
    ALLOWED_IPS = []
    
    # IPs bloqueadas (blacklist)
    BLOCKED_IPS = []
    
    # Configuración de logging de seguridad
    SECURITY_LOG_FILE = 'logs/security.log'
    SECURITY_LOG_LEVEL = 'INFO'
    
    # Configuración de cifrado
    ENCRYPTION_KEY = os.environ.get('ENCRYPTION_KEY', 'default-key-change-in-production')
    
    # Configuración de base de datos
    DB_CONNECTION_TIMEOUT = 30
    DB_MAX_CONNECTIONS = 20
    
    # Configuración de archivos
    MAX_FILE_SIZE = 10 * 1024 * 1024  # 10MB
    ALLOWED_FILE_EXTENSIONS = ['.csv', '.xlsx', '.txt']
    
    # Configuración de API
    API_KEY_LENGTH = 32
    API_RATE_LIMIT = 1000  # requests per hour
    
    @classmethod
    def is_ip_allowed(cls, ip):
        """Verifica si una IP está permitida"""
        if ip in cls.BLOCKED_IPS:
            return False
        
        if not cls.ALLOWED_IPS:  # Si la lista está vacía, permitir todas
            return True
        
        return ip in cls.ALLOWED_IPS
    
    @classmethod
    def get_security_headers(cls):
        """Obtiene headers de seguridad según el entorno"""
        headers = cls.SECURITY_HEADERS.copy()
        
        # En desarrollo, relajar algunas restricciones
        if os.environ.get('FLASK_ENV') == 'development':
            headers['Content-Security-Policy'] = headers['Content-Security-Policy'].replace("'self'", "'self' 'unsafe-eval'")
        
        return headers

# Configuración específica por entorno
class DevelopmentSecurityConfig(SecurityConfig):
    """Configuración de seguridad para desarrollo"""
    MAX_LOGIN_ATTEMPTS = 10
    LOCKOUT_DURATION = timedelta(minutes=5)
    RATE_LIMIT_REQUESTS = 1000

class ProductionSecurityConfig(SecurityConfig):
    """Configuración de seguridad para producción"""
    MAX_LOGIN_ATTEMPTS = 3
    LOCKOUT_DURATION = timedelta(minutes=30)
    SESSION_TIMEOUT = timedelta(hours=4)
    RATE_LIMIT_REQUESTS = 50

def get_security_config():
    """Obtiene la configuración de seguridad según el entorno"""
    env = os.environ.get('FLASK_ENV', 'development')
    
    if env == 'production':
        return ProductionSecurityConfig()
    else:
        return DevelopmentSecurityConfig()