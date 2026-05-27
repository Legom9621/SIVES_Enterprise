<?php 
// ====================== CONTROL DE SESIÓN ====================== //
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

// Si no hay sesión activa, redirigir
if (!isset($_SESSION['usr_id'])) {
    $redirectURL = "/index.php";
    if (!headers_sent()) {
        header("Location: $redirectURL");
        exit;
    } else {
        echo "<script>window.location.href='$redirectURL';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirectURL'></noscript>";
        exit;
    }
}
    global $con;
    $logo = "";
    $icono = "";
$userid  = $_SESSION['usr_id'];
$username = $_SESSION['username'] ?? "Sin nombre";
$perfil   = $_SESSION['perfil'] ?? 5; // 1=Admin 2=Director 3=Comercial 4=Consultor 5=Usuario

// ====================== INCLUDES BASE ====================== //
require_once("dbconnect.php");
require_once("SED.php");
require_once("cockies.php");

try {
    // Validar y sanitizar parámetro GET
    if (!isset($_GET["nik"]) || empty($_GET["nik"])) {
        throw new Exception("Parámetro de seguro no válido");
    }
    
    $nik = filter_var($_GET["nik"], FILTER_SANITIZE_NUMBER_INT);
    if (!$nik) {
        throw new Exception("ID de seguro inválido");
    }
    
    // Obtener información del seguro usando PDO
    $sql = "SELECT s.*, a.nombre 
            FROM tb_seguro s 
            INNER JOIN tb_agencia a ON s.id_agencia = a.nro_doc  
            WHERE s.idtb_seguro = :nik";
    
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':nik', $nik, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<script>alert('El seguro no existe...');</script>";
        header("Location: products.php");
        exit;
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $estado = $row['estado'];
    $seguro = htmlspecialchars($row['seguro'], ENT_QUOTES, 'UTF-8');
    $agencia = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
    
    // Determinar nueva acción según estado actual
    if ($estado == 1) {
        echo "<script>alert('El seguro $seguro de la Agencia $agencia está ACTIVO actualmente, ¿desea desactivarlo?');</script>";
        
        $updateSql = "UPDATE tb_seguro SET estado = 2 WHERE idtb_seguro = :nik";
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bindParam(':nik', $nik, PDO::PARAM_INT);
        
        if ($updateStmt->execute()) {
            echo "<script>alert('El seguro $seguro ha sido desactivado.'); window.location.href = '../products.php?';</script>";
            exit;
        } else {
            throw new Exception("Error al desactivar el seguro");
        }
        
    } elseif ($estado == 2) {
        echo "<script>alert('El seguro $seguro de la Agencia $agencia está INACTIVO actualmente, ¿desea activarlo?');</script>";
        
        $updateSql = "UPDATE tb_seguro SET estado = 1 WHERE idtb_seguro = :nik";
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bindParam(':nik', $nik, PDO::PARAM_INT);
        
        if ($updateStmt->execute()) {
            echo "<script>alert('El seguro $seguro ha sido activado.'); window.location.href = '../products.php?';</script>";
            exit;
        } else {
            throw new Exception("Error al activar el seguro");
        }
    } else {
        throw new Exception("Estado de seguro no reconocido: $estado");
    }
    
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Error en la operación con la base de datos.
          </div>';
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '
          </div>';
    echo "<script>setTimeout(function(){ window.location.href = '../products.php'; }, 3000);</script>";
}
?>