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
require_once("mcustomer.php"); // <- AQUI IRA EL MENÚ NUEVO

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
$filter = '';

//check if form is submitted isset($_POST['signup'])
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $error = false;
    $nombre = $_POST['name'];
    $tpdoc = $_POST['filter'];
    $doc = $_POST['doc'];
    $fecnace = $_POST['fecnace'];
    $direcc = $_POST['direcc'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $movil = $_POST['movil'];

    //Nombre sólo puede contener caracteres alfabéticos y espacio (esto varia según requerimiento)
    if (!preg_match("/^[a-zA-Z ]+$/", $nombre)) {
        $error = true;
        $nombre_error = "El nombre debe contener solo caracteres del alfabeto y espacio.";
    }
    if (!preg_match("/^[a-zA-Z ]+$/", $tpdoc)) {
        $error = true;
        $tpdoc_error = "Ese tipo de docuemento no esta en las opciones";
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $correo_error = "Ingresa un correo electrónico válido.";
    }
    if (!preg_match('/[0-9]{2}/', $doc)) {
        $error = true;
        $doc_error = "Debe contener solo números.";
    }
    if (!preg_match('/[0-9]{2}/', $telefono)) {
        $error = true;
        $telefono_error = "Debe contener solo números.";
    }
    if (!preg_match('/[0-9]{2}/', $movil)) {
        $error = true;
        $movil_error = "Debe contener solo números.";
    }
    if (!preg_match("/^[a-zA-Z ]+$/", $direcc)) {
        $error = true;
        $direcc_error = "La dirección debe contener solo caracteres del alfabeto y espacio.";
    }

    if ($error == true) {
        $checkQuery = $con->prepare("SELECT 1 FROM tb_cliente WHERE nro_doc = :doc");
        $checkQuery->bindParam(':doc', $doc, PDO::PARAM_STR);
        $checkQuery->execute();
        
        if ($checkQuery->rowCount() > 0) {
            echo "<script> alert('ERROR.! ¡El cliente $nombre ya existe con el documento $doc ya existe.!');window.location= '../customer.php' </script>";
        } else {
            $fecha_nacimiento_obj = DateTime::createFromFormat('Y-m-d', $fecnace); //Ajusta el formato según el que uses en la base de datos
            $fechaupdate = $fecha_nacimiento_obj->format('d/m/Y');
            
            // Preparar la consulta INSERT con PDO
            $stmt = $con->prepare("INSERT INTO tb_cliente (name, tpodoc, nro_doc, date, addres, phone, mail, cellphone, estado) 
                                   VALUES (:nombre, :tpdoc, :doc, :fechaupdate, :direcc, :telefono, :correo, :movil, 1)");
            
            // Bind de parámetros
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':tpdoc', $tpdoc, PDO::PARAM_STR);
            $stmt->bindParam(':doc', $doc, PDO::PARAM_STR);
            $stmt->bindParam(':fechaupdate', $fechaupdate, PDO::PARAM_STR);
            $stmt->bindParam(':direcc', $direcc, PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->bindParam(':movil', $movil, PDO::PARAM_STR);
            
            // Ejecutar la consulta
            try {
                if ($stmt->execute()) {
                    echo "<script> alert('EXITO.! ¡Registrado exitosamente!');window.location= '../customer.php' </script>";
                } else {
                    echo "<script> alert('Error de registro.! Verifique los datos.');window.location= '../company.php' </script>";
                }
            } catch (PDOException $e) {
                echo "<script> alert('Error de registro.! " . addslashes($e->getMessage()) . "');window.location= '../company.php' </script>";
            }
        }
    }
}
?>