<?php
// cockies.php
require_once("dbconnect.php");
require_once("SED.php");

date_default_timezone_set('America/Bogota');

try {
    $database = Database::getInstance();
    $con = $database->getConnection();

    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['user_id']) && (!isset($_COOKIE['user_id']) || !isset($_COOKIE['username']))) {
        echo "<script> alert('Por seguridad vuelva a logearse y seleccione RECORDARME para crear sesión');</script>";
        header("Location: /php/logout.php");
        exit();
    }

    // Si hay cookies, establecer la sesión
    if (isset($_COOKIE['user_id']) && isset($_COOKIE['username'])) {
        // Verificar que el usuario existe y está activo
        $sql = "SELECT id, user FROM users WHERE id = :user_id AND estado = 1";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':user_id', $_COOKIE['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['user'];
        } else {
            // Si el usuario no existe o no está activo, limpiar cookies y redirigir
            setcookie("user_id", "", time() - 3600, "/");
            setcookie("username", "", time() - 3600, "/");
            header("Location: /php/logout.php");
            exit();
        }
    }
    
} catch (PDOException $e) {
    error_log("Error en cockies.php: " . $e->getMessage());
    // En caso de error, redirigir al logout
    header("Location: /php/logout.php");
    exit();
}
?>