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



// escaping, additionally removing everything that could be (html/javascript-) code
$nik = filter_input(INPUT_GET, 'nik', FILTER_VALIDATE_INT);

if ($nik === false || $nik === null || $nik <= 0) {
    // Redirigir o mostrar error
    echo "<script>alert('ID de cliente no válido.'); window.location.href='customer.php';</script>";
    exit;
}

try {
    // 1. CONSULTA SELECT CON PARÁMETRO PREPARADO
    $sql = "SELECT * FROM tb_cliente WHERE idtb_cliente = :nik";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':nik', $nik, PDO::PARAM_INT);
    $stmt->execute();
    
    // 2. VERIFICAR SI EXISTE EL CLIENTE
    if ($stmt->rowCount() == 0) {
        echo "<script> alert('No existe el cliente.'); </script>";
        header("Location: customer.php");
        exit;
    } else {
        // 3. OBTENER DATOS DEL CLIENTE
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $estado = $row['estado'];
        $cliente = $row['name'];
        
        // 4. DETERMINAR NUEVO ESTADO Y MENSAJE
        if ($estado == 1) {
            echo "<script> alert('El cliente esta activo actualmente, ¿lo desea Desactivar? $cliente');</script>";
            $nuevoEstado = 2;
            $accion = 'desactivado';
            $mensajeConfirmacion = 'desactivar';
        } elseif ($estado == 2) {
            echo "<script> alert('El cliente esta inactivo actualmente, ¿lo desea Activar? $cliente');</script>";
            $nuevoEstado = 1;
            $accion = 'activado';
            $mensajeConfirmacion = 'activar';
        } else {
            echo "<script> alert('Estado del cliente no válido.'); </script>";
            header("Location: customer.php");
            exit;
        }
        
        // 5. ACTUALIZAR ESTADO CON TRANSACCIÓN (MEJOR PRÁCTICA)
        $con->beginTransaction();
        
        try {
            $updateSql = "UPDATE tb_cliente SET estado = :nuevoEstado WHERE idtb_cliente = :nik";
            $updateStmt = $con->prepare($updateSql);
            $updateStmt->bindParam(':nuevoEstado', $nuevoEstado, PDO::PARAM_INT);
            $updateStmt->bindParam(':nik', $nik, PDO::PARAM_INT);
            
            if ($updateStmt->execute() && $updateStmt->rowCount() > 0) {
                $con->commit();
                
                // 6. MOSTRAR CONFIRMACIÓN Y REDIRIGIR
                echo "<script>
                    alert('El cliente $cliente ha sido $accion.');
                    window.location.href = '../customer.php?';
                </script>";
                exit;
            } else {
                $con->rollBack();
                throw new Exception("No se pudo actualizar el estado del cliente.");
            }
            
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }
    
} catch (PDOException $e) {
    error_log("Error PDO en activecustomer.php: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Error en la base de datos: ' . htmlspecialchars($e->getMessage()) . '
          </div>';
} catch (Exception $e) {
    error_log("Error general en activecustomer.php: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Error: ' . htmlspecialchars($e->getMessage()) . '
          </div>';
}
?>