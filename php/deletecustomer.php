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

// ====================== INCLUDES BASE ====================== //
require_once("dbconnect.php");
require_once("SED.php");
require_once("cockies.php");
require_once("mcustomer.php"); // <- AQUI IRA EL MENÚ NUEVO

// ====================== CLASE PARA MANEJO DE REDIRECCIONES ====================== //
class MessageHandler {
    public static function redirectWithSuccess($msg) {
        $_SESSION['operation_success'] = $msg;
        header("Location: /customer.php");
        exit();
    }

    public static function redirectWithError($msg) {
        $_SESSION['operation_error'] = $msg;
        header("Location: /customer.php");
        exit();
    }
    
    public static function redirectWithInfo($msg) {
        $_SESSION['operation_info'] = $msg;
        header("Location: /customer.php");
        exit();
    }
}

// OBTENER LOGO E ICONO DE LA COMPAÑIA DESDE LA BASE DE DATOS
try {
    $database = Database::getInstance();
    $con = $database->getConnection();
    
    $imagen = "SELECT logo, icono FROM tb_company";
    $stmt = $con->prepare($imagen);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['logo'])) {
        $logo = base64_encode($result['logo']);
    } else {
        $logo = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
    }
    
    if ($result && !empty($result['icono'])) {
        $icono = base64_encode($result['icono']);
    } else {
        $icono = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
    }
} catch (PDOException $e) {
    error_log("Error al obtener logos: " . $e->getMessage());
    $logo = $icono = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
}

$user_id = $_SESSION['usr_id'];
$username = $_SESSION['username'] ?? "Sin nombre";
$perfil   = $_SESSION['perfil'] ?? 5; // 1=Admin 2=Director 3=Comercial 4=Consultor 5=Usuario


//Establece el error de validación como flag
$error = true;
$nombre = '';
$tpdoc = '';
$doc = '';
$fecnace = '';
$direcc = '';
$telefono = '';
$correo = '';
$movil = '';
$estado = '';
$user_id = $_SESSION['usr_id'];
    if (isset($_GET['aksi']) && $_GET['aksi'] == 'delete') {
        try {
            $nik = $_GET['nik'] ?? '';
            $nik = trim($nik);
            
            // Consulta SELECT con PDO
            $cekSql = "SELECT * FROM tb_cliente WHERE idtb_cliente = :nik";
            $cekStmt = $con->prepare($cekSql);
            $cekStmt->bindParam(':nik', $nik, PDO::PARAM_INT);
            $cekStmt->execute();
            
            if ($cekStmt->rowCount() == 0) {
                echo "<script>alert('No se encontraron datos con Id: $nik?'); window.location= '../customer.php'</script>";
            } else {
                $row = $cekStmt->fetch(PDO::FETCH_ASSOC);
                $tpdoc = $row['tpodoc'];
                $doc = $row['nro_doc'];
                $cliente = $row['name'];
                $fecnace = $row['date'];
                $direcc = $row['addres'];
                $telefono = $row['phone'];
                $movil = $row['cellphone'];
                $correo = $row['mail'];
                $estado = $row['estado'];
                
                echo "<script>alert('Desea eliminar al cliente con Id: $nik - $cliente?');</script>";
                
                // INSERT con PDO
                $bckdelete = "INSERT INTO tb_delcliente(name, tpodoc, nro_doc, date, addres, phone, mail, cellphone, estado, usr_id, fecdelete)
                            VALUES (:cliente, :tpdoc, :doc, :fecnace, :direcc, :telefono, :correo, :movil, :estado, :user_id, NOW())";
                
                $resultStmt = $con->prepare($bckdelete);
                $resultStmt->bindParam(':cliente', $cliente, PDO::PARAM_STR);
                $resultStmt->bindParam(':tpdoc', $tpdoc, PDO::PARAM_STR);
                $resultStmt->bindParam(':doc', $doc, PDO::PARAM_STR);
                $resultStmt->bindParam(':fecnace', $fecnace, PDO::PARAM_STR);
                $resultStmt->bindParam(':direcc', $direcc, PDO::PARAM_STR);
                $resultStmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
                $resultStmt->bindParam(':correo', $correo, PDO::PARAM_STR);
                $resultStmt->bindParam(':movil', $movil, PDO::PARAM_STR);
                $resultStmt->bindParam(':estado', $estado, PDO::PARAM_STR);
                $resultStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $resultStmt->execute();
                
                // DELETE con PDO
                $deleteSql = "DELETE FROM tb_cliente WHERE idtb_cliente = :nik";
                $deleteStmt = $con->prepare($deleteSql);
                $deleteStmt->bindParam(':nik', $nik, PDO::PARAM_INT);
                
                if ($deleteStmt->execute()) {
                    echo "<script>alert('Se Elimino con exito el cliente'); window.location= '../customer.php'</script>";
                } else {
                    echo "<script>alert('No se pudeo Eliminar con exito el cliente'); window.location= '../customer.php'</script>";
                }
            }
        } catch (PDOException $e) {
            error_log("Error PDO en delete: " . $e->getMessage());
            echo "<script>alert('Error en la base de datos'); window.location= '../customer.php'</script>";
        }
    }
?>