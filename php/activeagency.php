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

// Obtener y limpiar el parámetro de manera segura
$nik = isset($_GET["nik"]) ? strip_tags($_GET["nik"]) : null;

// Validar que se proporcionó el parámetro
if (!$nik) {
    echo "<script> alert('No se proporcionó identificación de la agencia.'); window.location= '/main.php'</script>";
    exit;
}

try {
    // Consulta para verificar si existe la agencia (usando parámetros preparados)
    $sql = "SELECT * FROM tb_agencia WHERE id_agencia = :nik";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':nik', $nik, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<script> alert('No existe la Agencia seleccionada.'); window.location= '/agency.php'</script>";
        exit;
    } else {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $estado = $row['estado'];
        $agencia = $row['nombre'];
        
        if ($estado == 1) {
            echo "<script> alert('La Agencia \"$agencia\" está activa actualmente, ¿desea desactivarla?');</script>";
            
            // Actualizar estado a inactivo (2)
            $updateSql = "UPDATE tb_agencia SET estado = 2 WHERE id_agencia = :nik";
            $updateStmt = $con->prepare($updateSql);
            $updateStmt->bindParam(':nik', $nik, PDO::PARAM_STR);
            
            if ($updateStmt->execute()) {
                echo "<script>alert('La Agencia \"$agencia\" ha sido desactivada.'); window.location.href = '/agency.php';</script>";
                exit;
            } else {
                echo '<div class="alert alert-danger alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        Error, no se pudo desactivar la agencia.
                      </div>';
                echo "<script>setTimeout(function(){ window.location.href = '/agency.php'; }, 3000);</script>";
            }
        } 
        elseif ($estado == 2) {
            echo "<script> alert('La Agencia \"$agencia\" está inactiva actualmente, ¿desea activarla?');</script>";
            
            // Actualizar estado a activo (1)
            $updateSql = "UPDATE tb_agencia SET estado = 1 WHERE id_agencia = :nik";
            $updateStmt = $con->prepare($updateSql);
            $updateStmt->bindParam(':nik', $nik, PDO::PARAM_STR);
            
            if ($updateStmt->execute()) {
                echo "<script>alert('La Agencia \"$agencia\" ha sido activada.'); window.location.href = '/agency.php';</script>";
                exit;
            } else {
                echo '<div class="alert alert-danger alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        Error, no se pudo activar la agencia.
                      </div>';
                echo "<script>setTimeout(function(){ window.location.href = '/agency.php'; }, 3000);</script>";
            }
        }
        else {
            // Estado no reconocido
            echo "<script>alert('Estado de agencia no reconocido.'); window.location.href = '/agency.php';</script>";
            exit;
        }
    }
    
} catch (PDOException $e) {
    // Manejo de errores de PDO
    error_log("Error PDO en activeagency.php: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Error en la base de datos. Por favor, intente nuevamente.
          </div>';
    echo "<script>setTimeout(function(){ window.location.href = '/agency.php'; }, 3000);</script>";
    exit;
} catch (Exception $e) {
    // Manejo de otros errores
    error_log("Error general en activeagency.php: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Ocurrió un error inesperado.
          </div>';
    echo "<script>setTimeout(function(){ window.location.href = '/agency.php'; }, 3000);</script>";
    exit;
}

