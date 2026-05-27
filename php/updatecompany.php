<?php
session_start();
require_once("dbconnect.php");
require_once("SED.php");
require_once("cockies.php");
require_once("userstate.php");
require_once("userlist.php");

// Definir zona horaria
date_default_timezone_set('America/Bogota');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idcompany = $_POST['idtb_company'];
    $tipodoc = $_POST['tpodoc'];
    $nrodocumento = $_POST['doc'];
    $nomcompany = $_POST['namecom'];
    $website = $_POST['web'];
    $dircompany = $_POST['address'];
    $telcompany = $_POST['phone'];
    $mailcompany = $_POST['email'];

    // Validar y manejar la imagen si se ha subido una nueva
    $logo = null;
    $icono = null;

    // Verificar si el nrodocumento ya existe
    $sql_update ="SELECT 1 FROM tb_company WHERE idtb_company = :idcompany";
    $checkQuery = $con->prepare($sql_update);
    $checkQuery->bindParam(':idcompany', $idcompany, PDO::PARAM_INT);
    $checkQuery->execute();
    $result = $checkQuery->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "<script> alert('ALERTA.! ¡Desea actualizar los datos del número de documento $nrodocumento..!');' </script>";

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK &&
        isset($_FILES['icono']) && $_FILES['icono']['error'] == UPLOAD_ERR_OK) {

        // Leer los archivos
        $logo = file_get_contents($_FILES['logo']['tmp_name']);
        $icono = file_get_contents($_FILES['icono']['tmp_name']);
        }

        if ($logo !== null || $icono !== null) {
        // Si hay una imagen nueva, también se actualiza
        // Preparar la consulta
        $stmt = $con->prepare("UPDATE tb_company SET tipodoc=?, nrodocumento=?, nomcompany=?, website=?, dircompany=?, telcompany=?, mailcompany=?, logo=?, icono=? WHERE idtb_company=?");
        $stmt->execute([$tipodoc, $nrodocumento, $nomcompany, $website, $dircompany, $telcompany, $mailcompany, $logo, $icono, $idcompany]);

        // Ejecutar la consulta
        if ($stmt->rowCount() > 0) {
            echo "<script> alert('EXITO.! ¡Actualizado exitosamente!');window.location= '/company.php' </script>";
        } else {
            echo "<script> alert('Error de registro.! Verifique los datos.');window.location= '/company.php' </script>";
        }
        }else{
        // Si no hay una imagen nueva, solo se actualizan los otros campos
        // Preparar la consulta
        
$stmt = $con->prepare("UPDATE tb_company SET tipodoc=?, nrodocumento=?, nomcompany=?, website=?, dircompany=?, telcompany=?, mailcompany=?, logo=?, icono=? WHERE idtb_company=?");
    
// Ejecutar la consulta
if ($stmt->execute([$tipodoc, $nrodocumento, $nomcompany, $website, $dircompany, $telcompany, $mailcompany, $logo, $icono, $idcompany])) {
    
    // Verificar si se actualizó alguna fila
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('EXITO.! ¡Actualizado exitosamente!'); window.location= '/company.php';</script>";
    } else {
        // No se actualizó ninguna fila (posiblemente los datos son iguales)
        echo "<script>alert('INFORMACIÓN: No se realizaron cambios. Los datos pueden ser iguales a los existentes.'); window.location= '/company.php';</script>";
    }
    
} else {
    // Error en la ejecución - mostrar detalles del error
    $errorInfo = $stmt->errorInfo();
    $errorMessage = "Error de registro! Código: " . $errorInfo[0] . " - " . $errorInfo[2];
    
    // También mostrar los valores para depuración
    $debugInfo = "Valores: tipodoc=$tipodoc, nrodocumento=$nrodocumento, nomcompany=$nomcompany, idcompany=$idcompany";
    
    echo "<script>
            console.error('Error PDO: " . addslashes($errorInfo[2]) . "');
            alert('" . $errorMessage . "\\\\n" . $debugInfo . "');
            window.location= '/company.php';
          </script>";
}

        }
    } else {
        echo "El número de documento no existe.";
        echo "<script> alert('ERROR.! ¡El número de documento $nrodocumento no existe.!');window.location= '/company.php' </script>";
    }
}
?>