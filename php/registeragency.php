<?php
session_start();
include_once("dbconnect.php");
include_once("SED.php");
include_once("cockies.php");
//declaro el uso horario
date_default_timezone_set('UTC');

//Establece el error de validación como flag
$error = true;
$tpo_doc = '';
$nro_doc = '';
$nombre = '';
$contacto = '';
$cargo = '';
$nro_fijo = '';
$nro_cel = '';
$movil = '';
$email = '';
$estado = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$tpo_doc = mysqli_real_escape_string($con, $_POST['filter']);
	$nro_doc = mysqli_real_escape_string($con, $_POST['nro_doc']);
	$nombre = mysqli_real_escape_string($con, $_POST['nombre']);
	$contacto = mysqli_real_escape_string($con, $_POST['contacto']);
	$cargo = mysqli_real_escape_string($con, $_POST['cargo']);
	$nro_fijo = mysqli_real_escape_string($con, $_POST['nro_fijo']);
    $nro_cel = mysqli_real_escape_string($con, $_POST['nro_cel']);
    $email = mysqli_real_escape_string($con, $_POST['email']);

            //Nombre sólo puede contener caracteres alfabéticos y espacio (esto varia según requerimiento)
            if (!preg_match("/^[a-zA-Z ]+$/",$tpo_doc)) {
                $error = true;
                $nombre_error = "El tipo de documento no es correcto.";
            }
            if(!preg_match('/[0-9]{2}/',$nro_doc))  {
                $error = true;
                $doc_error = "Debe contener solo números.";
            }
            if (!preg_match("/^[a-zA-Z ]+$/",$nombre)) {
                $error = true;
                $tpdoc_error = "El nombre debe contener solo caracteres del alfabeto y espacio.";
            }
            if (!preg_match("/^[a-zA-Z ]+$/",$contacto)) {
                $error = true;
                $tpdoc_error = "El nombre debe contener solo caracteres del alfabeto y espacio.";
            }
            if (!preg_match("/^[a-zA-Z ]+$/",$cargo)) {
                $error = true;
                $tpdoc_error = "El nombre debe contener solo caracteres del alfabeto y espacio.";
            }
            if(!preg_match('/[0-9]{2}/',$nro_fijo))  {
                $error = true;
                $telefono_error = "Debe contener solo números.";
            }
            if(!preg_match('/[0-9]{2}/',$nro_cel))  {
                $error = true;
                $movil_error = "Debe contener solo números.";
            }
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) {
                $error = true;
                $correo_error = "Ingresa un correo electrónico válido.";
            }

            if ($error == true){
                // Verificar si la agencia ya existe
                $checkQuery = $con->prepare("SELECT 1 FROM tb_agencia WHERE nro_doc = ?");
                $checkQuery->bind_param('s', $nro_doc);
                $checkQuery->execute();
                $checkQuery->store_result();

                if ($checkQuery->num_rows > 0) {
                    echo "El número de documento ya existe.";
                    echo "<script> alert('ERROR.! ¡El número de documento $nro_doc ya existe.!');window.location= '../agency.php' </script>";
                } else {
                        // Preparar la consulta
                        $stmt = $con->prepare("INSERT INTO tb_agencia(tpo_doc, nro_doc, nombre, contacto, cargo, nro_fijo, nro_cel, email, estado)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param('sssssssss', $tpo_doc, $nro_doc, $nombre, $contacto, $cargo, $nro_fijo, $nro_cel, $email, $estado);

                        // Ejecutar la consultaindow.location= '../agency.php' 
                        if ($stmt->execute()) {
                            echo "<script> alert('EXITO.! ¡Registrado exitosamente!');window.location= '../agency.php'</script>";
                        } else {
                            echo "<script> alert('Error de registro.! Verifique los datos.  $stmt->error');window.location= '../agency.php' </script>";
                        }
                        $stmt->close();
                }
                $checkQuery->close();
            }
                $con->close();
}
?>