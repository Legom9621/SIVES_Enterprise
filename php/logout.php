<?php
session_start();
require_once("dbconnect.php");
date_default_timezone_set('America/Bogota');
class LogoutHandler {
    private $con;
    
    public function __construct($database) {
        $this->con = $database->getConnection();
    }
    
    public function logout() {
        try {
            // Registrar cierre de sesión si existe usuario en sesión
            if (isset($_SESSION['username'])) {
                $this->registerLogout($_SESSION['username']);
            }
            
            // Limpiar todas las variables de sesión
            $_SESSION = [];
            
            // Destruir la sesión
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
            
            // Eliminar cookies
            $this->clearCookies();
            
        } catch (Exception $e) {
            error_log("Error durante logout: " . $e->getMessage());
        }
    }
    
    private function registerLogout($username) {
        try {
            $stmt = $this->con->prepare("
                UPDATE tb_sesion 
                SET fechaout = NOW(), sesion = 1 
                WHERE username = :username AND sesion = 0
            ");

            if ($stmt) {
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
            }

        } catch (PDOException $e) {
            error_log("Error al registrar logout: " . $e->getMessage());
        }
    }


    
    private function clearCookies() {
        $cookies = ['user_id', 'username', 'session_token'];
        foreach ($cookies as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                setcookie($cookie, '', time() - 3600, '/', '', true, true);
            }
        }
    }
}

// Ejecutar logout
try {
    $database = Database::getInstance();
    $logoutHandler = new LogoutHandler($database);
    $logoutHandler->logout();
    
    // Redirigir al login
    header("Location: /index.php");
    exit();
    
} catch (Exception $e) {
    error_log("Error en proceso de logout: " . $e->getMessage());
    header("Location: /index.php");
    exit();
}
?>