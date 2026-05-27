<?php
session_start();
include_once("dbconnect.php");
include_once("SED.php");
include_once("cockies.php");
//declaro el uso horario
date_default_timezone_set('UTC');

$tpo_doc = '';
$nro_doc = '';
$nombre = '';
$contacto = '';
$cargo = '';
$nro_fijo = '';
$nro_cel = '';
$email = '';
$estado = '';

if (isset($_POST["import"])) {

    // Verificar si se ha subido un archivo
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];

        // Solo permitir archivos CSV
        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'csv') {
            echo "<script> alert('Por favor, sube un archivo CSV....'); </script>";
            exit;
        }

        // Leer el archivo CSV
        if (($handle = fopen($fileTmpPath, 'r')) !== false) {
            // Ignorar la primera fila (cabeceras)
            fgetcsv($handle);

            // Leer cada fila del archivo CSV
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                // Asumiendo que el archivo CSV tiene el siguiente orden de columnas:
                // tpo_doc, nro_doc, nombre, contacto, cargo, nro_fijo, nro_cel, email, estado
                $tpo_doc = $data[0];
                $nro_doc = $data[1];
                $nombre = $data[2];
                $contacto = $data[3];
                $cargo = $data[4];
                $nro_fijo = $data[5];
                $nro_cel = $data[6];
                $email = $data[7];
                $estado = $data[8];

                // Preparar la consulta SQL para insertar los datos  window.location= '../agency.php'  window.location= '../main.php'
                $stmt = $con->prepare("INSERT INTO tb_agencia (tpo_doc, nro_doc, nombre, contacto, cargo, nro_fijo, nro_cel, email, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tpo_doc, $nro_doc, $nombre, $contacto, $cargo, $nro_fijo, $nro_cel, $email, $estado]);
            }
            echo "<script> alert('Datos importados de la Agencia Correctamente...');window.location= '../agency.php'</script>";
            fclose($handle);
        } else {
            echo "<script> alert('Error al abrir el archivo CSV....');window.location= '../agency.php'</script>";
            exit;
        }
    } else {
        echo "<script> alert('No se ha subido ningún archivo.....');window.location= '../main.php'</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="icon" href="images/Icono.png" type="image/png" sizes="32x32" />
    <link rel="shortcut icon" type="image/png " href="images/Icono.png" />
</head>

<body>
    <div class="footer span-12">
        <div class="container">
            <center> <b class="copyright"><a href="https://liansegurosltda.com/" target="_blank"> Lianseguros Ltda.</a>
                    &copy; <?php echo date("Y") ?> Vive tu Seguro</b>
            </center><br>
            <center><br>
                <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
            </center>
            <script src="/JS/jquery.min.js"></script>
            <script src="/JS/bootstrap.min.js"></script>
            <script src="/JS/jquery-1.12.4-jquery.min.js"></script>
        </div>
    </div>
</body>
</htmnl>