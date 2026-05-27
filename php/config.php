<?php
declare(strict_types=1);
/**
 * Configuración de seguridad para SED
 * En producción, usar variables de entorno
 */
date_default_timezone_set('America/Bogota');

class SecurityConfig {
    // Estas constantes deberían ser reemplazadas por variables de entorno en producción
    private const ENV_SECRET_KEY = 'SED_SECRET_KEY';
    private const ENV_SECRET_IV = 'SED_SECRET_IV';
    
    /**
     * Obtiene la clave secreta desde variables de entorno
     */
    public static function getSecretKey(): string {
        return $_ENV[self::ENV_SECRET_KEY] ?? '$CARLOS@2016';
    }
    
    /**
     * Obtiene el IV secreto desde variables de entorno
     */
    public static function getSecretIV(): string {
        return $_ENV[self::ENV_SECRET_IV] ?? '101712';
    }
    
    /**
     * Verifica si la configuración es segura
     */
    public static function isSecure(): bool {
        $key = self::getSecretKey();
        $iv = self::getSecretIV();
        
        // Verificar que no se usen valores por defecto en producción
        if ($key === '$CARLOS@2016' || $iv === '101712') {
            error_log('ADVERTENCIA: Se están usando valores por defecto para encriptación');
            return false;
        }
        
        return true;
    }
}
?>