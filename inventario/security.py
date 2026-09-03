"""
Módulo de seguridad para el sistema de inventario
Incluye funciones de autenticación, autorización y protección
"""
import hashlib
import secrets
import time
import os
from functools import wraps
from flask import session, request, jsonify
import bcrypt

class SecurityManager:
    """Gestor de seguridad centralizado"""
    
    def __init__(self):
        self.failed_attempts = {}  # IP -> {count, last_attempt}
        self.max_attempts = 5
        self.lockout_time = 300  # 5 minutos
        self.session_timeout = 28800  # 8 horas
        
    def hash_password(self, password):
        """Hashea una contraseña usando bcrypt"""
        salt = bcrypt.gensalt()
        return bcrypt.hashpw(password.encode('utf-8'), salt)
    
    def verify_password(self, password, hashed):
        """Verifica una contraseña contra su hash"""
        if isinstance(hashed, str):
            hashed = hashed.encode('utf-8')
        return bcrypt.checkpw(password.encode('utf-8'), hashed)
    
    def get_client_ip(self, request):
        """Obtiene la IP real del cliente"""
        # Verificar headers de proxy
        if request.headers.get('X-Forwarded-For'):
            return request.headers.get('X-Forwarded-For').split(',')[0].strip()
        elif request.headers.get('X-Real-IP'):
            return request.headers.get('X-Real-IP')
        else:
            return request.remote_addr
    
    def is_ip_locked(self, ip):
        """Verifica si una IP está bloqueada por intentos fallidos"""
        if ip not in self.failed_attempts:
            return False
            
        attempt_data = self.failed_attempts[ip]
        
        # Si han pasado más de lockout_time segundos, resetear
        if time.time() - attempt_data['last_attempt'] > self.lockout_time:
            del self.failed_attempts[ip]
            return False
        
        return attempt_data['count'] >= self.max_attempts
    
    def record_failed_attempt(self, ip):
        """Registra un intento fallido de login"""
        current_time = time.time()
        
        if ip in self.failed_attempts:
            # Si el último intento fue hace más de lockout_time, resetear
            if current_time - self.failed_attempts[ip]['last_attempt'] > self.lockout_time:
                self.failed_attempts[ip] = {'count': 1, 'last_attempt': current_time}
            else:
                self.failed_attempts[ip]['count'] += 1
                self.failed_attempts[ip]['last_attempt'] = current_time
        else:
            self.failed_attempts[ip] = {'count': 1, 'last_attempt': current_time}
    
    def clear_failed_attempts(self, ip):
        """Limpia los intentos fallidos para una IP (login exitoso)"""
        if ip in self.failed_attempts:
            del self.failed_attempts[ip]
    
    def generate_session_token(self):
        """Genera un token de sesión seguro"""
        return secrets.token_urlsafe(32)
    
    def validate_session(self, session):
        """Valida que la sesión sea válida y no haya expirado"""
        if 'user_id' not in session:
            return False
        
        if 'login_time' not in session:
            return False
        
        # Verificar timeout de sesión
        login_time = session.get('login_time')
        if isinstance(login_time, str):
            from datetime import datetime
            try:
                login_time = datetime.fromisoformat(login_time)
                login_timestamp = login_time.timestamp()
            except:
                return False
        else:
            login_timestamp = login_time
        
        if time.time() - login_timestamp > self.session_timeout:
            return False
        
        return True
    
    def sanitize_input(self, input_string):
        """Sanitiza entrada del usuario"""
        if not isinstance(input_string, str):
            return str(input_string)
        
        # Remover caracteres peligrosos
        dangerous_chars = ['<', '>', '"', "'", '&', '\x00']
        sanitized = input_string
        
        for char in dangerous_chars:
            sanitized = sanitized.replace(char, '')
        
        return sanitized.strip()
    
    def validate_sku_format(self, sku):
        """Valida formato de SKU"""
        import re
        # SKU debe ser alfanumérico con guiones, 3-50 caracteres
        pattern = r'^[A-Z0-9\-]{3,50}$'
        return bool(re.match(pattern, sku.upper()))
    
    def validate_serial_format(self, serial):
        """Valida formato de serial"""
        import re
        # Serial debe ser alfanumérico con guiones, 3-100 caracteres
        pattern = r'^[A-Z0-9\-]{3,100}$'
        return bool(re.match(pattern, serial.upper()))
    
    def log_security_event(self, event_type, details, ip=None):
        """Registra eventos de seguridad"""
        timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
        log_entry = f"[{timestamp}] SECURITY: {event_type} - {details}"
        if ip:
            log_entry += f" - IP: {ip}"
        
        print(log_entry)
        
        # En producción, esto debería escribir a un archivo de log
        # with open('security.log', 'a') as f:
        #     f.write(log_entry + '\n')

# Instancia global del gestor de seguridad
security_manager = SecurityManager()

def require_auth():
    """Decorator para rutas que requieren autenticación"""
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            if not security_manager.validate_session(session):
                security_manager.log_security_event(
                    "UNAUTHORIZED_ACCESS_ATTEMPT", 
                    f"Route: {request.endpoint}",
                    security_manager.get_client_ip(request)
                )
                return jsonify({"error": "No autorizado"}), 401
            return f(*args, **kwargs)
        return decorated_function
    return decorator

def require_admin():
    """Decorator para rutas que requieren permisos de administrador"""
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            if not security_manager.validate_session(session):
                return jsonify({"error": "No autorizado"}), 401
            
            if session.get('role') != 'admin':
                security_manager.log_security_event(
                    "ADMIN_ACCESS_DENIED", 
                    f"User: {session.get('username')} - Route: {request.endpoint}",
                    security_manager.get_client_ip(request)
                )
                return jsonify({"error": "Permisos insuficientes"}), 403
            
            return f(*args, **kwargs)
        return decorated_function
    return decorator

def rate_limit(max_requests=10, window=60):
    """Decorator para limitar la tasa de requests"""
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            ip = security_manager.get_client_ip(request)
            
            # Implementación simple de rate limiting
            # En producción usar Redis o similar
            current_time = time.time()
            key = f"rate_limit_{ip}_{f.__name__}"
            
            # Por simplicidad, usar un diccionario en memoria
            if not hasattr(rate_limit, 'requests'):
                rate_limit.requests = {}
            
            if key not in rate_limit.requests:
                rate_limit.requests[key] = []
            
            # Limpiar requests antiguos
            rate_limit.requests[key] = [
                req_time for req_time in rate_limit.requests[key] 
                if current_time - req_time < window
            ]
            
            # Verificar límite
            if len(rate_limit.requests[key]) >= max_requests:
                security_manager.log_security_event(
                    "RATE_LIMIT_EXCEEDED", 
                    f"Function: {f.__name__} - Requests: {len(rate_limit.requests[key])}",
                    ip
                )
                return jsonify({"error": "Demasiadas solicitudes"}), 429
            
            # Registrar request actual
            rate_limit.requests[key].append(current_time)
            
            return f(*args, **kwargs)
        return decorated_function
    return decorator

def validate_input(**validators):
    """Decorator para validar entrada de datos"""
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            if request.is_json:
                data = request.get_json()
                if not data:
                    return jsonify({"error": "Datos JSON requeridos"}), 400
                
                for field, validator in validators.items():
                    if field in data:
                        if not validator(data[field]):
                            security_manager.log_security_event(
                                "INVALID_INPUT", 
                                f"Field: {field} - Value: {data[field][:50]}",
                                security_manager.get_client_ip(request)
                            )
                            return jsonify({"error": f"Formato inválido para {field}"}), 400
            
            return f(*args, **kwargs)
        return decorated_function
    return decorator

# Funciones de validación comunes
def is_valid_string(value, min_len=1, max_len=200):
    """Valida que sea string con longitud apropiada"""
    return isinstance(value, str) and min_len <= len(value.strip()) <= max_len

def is_valid_sku(value):
    """Valida formato de SKU"""
    return security_manager.validate_sku_format(value)

def is_valid_serial(value):
    """Valida formato de serial"""
    return security_manager.validate_serial_format(value)

def is_valid_estado(value):
    """Valida que el estado sea válido"""
    estados_validos = ['ALMACEN', 'INSTALADO', 'DAÑADO', 'RETIRADO']
    return value in estados_validos