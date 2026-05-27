<?php
// userstate.php
require_once("dbconnect.php");
require_once("SED.php");
require_once("cockies.php");

date_default_timezone_set('America/Bogota');

// Establece el error de validación como flag
$error = true;
$name = '';
$user = '';
$email = '';
$password = '';
$cpassword = '';
$perfil = '';
$fotolog = '';
$PhotoUser = [];

try {
    $database = Database::getInstance();
    $con = $database->getConnection();

    // Obtener user_id de forma segura
    $userid = $_SESSION['usr_id'] ?? '';
    
    if (!empty($userid)) {
        // Usar prepared statements en lugar de mysqli_real_escape_string
        $userid = filter_var($userid, FILTER_SANITIZE_NUMBER_INT);
        
        // Valida si el usuario esta inactivo
        $usuariosInactivos = InactiveUsers($userid, $con);
        
        // Valida si el usuario esta activo
        $PhotoUser = ActiveUsers($userid, $con);
        
        // Seleccionar Foto
        foreach ($PhotoUser as $fila) {
            if ($fila['foto'] === null) {
                $fotolog = base64_encode(file_get_contents("images/login.png"));
            } else {
                $fotolog = base64_encode($fila['foto']);
            }
        }
    }

    // Selección de tipo de perfil
    $sqlp = "SELECT idtb_perfil, perfil FROM tb_perfil";
    $stmtp = $con->prepare($sqlp);
    $stmtp->execute();
    $resultp = $stmtp;

    // Verificar si la cookie y la sesión existen
    if (isset($_COOKIE['user_id']) && isset($_SESSION['login_time'])) {
        if ((time() - $_SESSION['login_time']) > 1200) {
            // Destruir la sesión y eliminar la cookie
            setcookie("user_id", " ", time() - 3600, "/");
            setcookie("username", " ", time() - 3600, "/");
            session_unset();
            session_destroy();

            // Redirigir al usuario al login
            header('Location: index.php');
            exit();
        } else {
            // Actualizar el tiempo de inicio de sesión
            $_SESSION['login_time'] = time();
        }
    } else {
        // Redirigir al usuario al login si no hay cookie o sesión activa
        header('Location: index.php');
        exit();
    }

} catch (PDOException $e) {
    error_log("Error en userstate.php: " . $e->getMessage());
    // En caso de error, redirigir al login
    header('Location: index.php');
    exit();
} catch (Exception $e) {
    error_log("Error general en userstate.php: " . $e->getMessage());
    header('Location: index.php');
    exit();
}

//Sección de Funciones
function InactiveUsers($userid, $con) {
    try {
        // Consulta SQL con prepared statement
        $sql = "SELECT * FROM users WHERE id = :userid AND estado = 2";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':userid', $userid, PDO::PARAM_INT);
        $stmt->execute();

        // Verificar si hay resultados
        $usuariosInactivos = [];
        if ($stmt) {
            $usuariosInactivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $usuariosInactivos;
    } catch (PDOException $e) {
        error_log("Error en InactiveUsers: " . $e->getMessage());
        return [];
    }
}

function ActiveUsers($userid, $con) {
    try {
        // Consulta SQL con prepared statement
        $sqla = "SELECT * FROM users WHERE id = :userid AND estado = 1";
        $stmt = $con->prepare($sqla);
        $stmt->bindValue(':userid', $userid, PDO::PARAM_INT);
        $stmt->execute();
        
        // Verificar si hay resultados
        $usuariosActivos = [];
        if ($stmt) {
            $usuariosActivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $usuariosActivos;
    } catch (PDOException $e) {
        error_log("Error en ActiveUsers: " . $e->getMessage());
        return [];
    }
}
?>