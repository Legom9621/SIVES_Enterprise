<?php
// sendnotificacion.php
// ====================== CONTROL DE SESIÓN ====================== //
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

// Si no hay sesión activa, redirigir
if (!isset($_SESSION['usr_id'])) {
    $redirectURL = "index.php";
    if (!headers_sent()) {
        header("Location: $redirectURL");
        exit;
    } else {
        echo "<script>window.location.href='$redirectURL';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirectURL'></noscript>";
        exit;
    }
}

$userid  = $_SESSION['usr_id'];
$username = $_SESSION['username'] ?? "Sin nombre";
$perfil   = $_SESSION['perfil'] ?? 5; // 1=Admin 2=Director 3=Comercial 4=Consultor 5=Usuario


// ====================== INCLUDES BASE ====================== //
require_once("dbconnect.php"); // Este archivo debe definir $con como conexión PDO
require_once("SED.php");
require_once __DIR__ . '/../class/class.phpmailer.php';
require_once __DIR__ . '/../class/class.smtp.php';

    $database = Database::getInstance();
    $con = $database->getConnection();

// OBTENER LOGO E ICONO DE LA COMPAÑIA DESDE LA BASE DE DATOS
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

// Validar que existe la conexión PDO
if (!isset($con) || !($con instanceof PDO)) {
    die("Error: Conexión a base de datos no disponible");
}

// Obtener parámetros de manera segura
$correo = $_GET["nik"] ?? null;
$filtro = $_GET["filter1"] ?? null;
$output = "";
$sendm = $_GET["filter1"] ?? null;

// Validar que el parámetro nik existe
if (!$correo) {
    die("Error: Parámetro 'nik' no proporcionado");
}

// Validar que sea numérico
if (!is_numeric($correo)) {
    die("Error: El documento debe ser numérico");
}

// CONSULTA SQL EN PDO (CORREGIDA)
try {
    $query = "SELECT 
                tb_cliente.mail as correoel, 
                tb_cliente.name as cliente, 
                tb_agencia.nombre as agencia, 
                tb_seguro.seguro as seguro,
                tb_tposeguro.tposeguro as tipo, 
                tb_productos.f_inicio,
                tb_productos.f_fin, 
                tb_productos.estado as stado,
                tb_productos.nro_poliza as poliza, 
                tb_productos.nrodoc_cliente as documento
              FROM tb_productos
              INNER JOIN tb_cliente ON tb_productos.nrodoc_cliente = tb_cliente.nro_doc
              INNER JOIN tb_agencia ON tb_productos.nrodoc_agencia = tb_agencia.nro_doc
              INNER JOIN tb_seguro ON tb_productos.idtb_seguro = tb_seguro.idtb_seguro
              INNER JOIN tb_tposeguro ON tb_productos.idtb_tposeguro = tb_tposeguro.idtb_tposeguro
              WHERE tb_productos.nrodoc_cliente = :correo
              GROUP BY tb_productos.idtb_productos";
    
    $stmt = $con->prepare($query);
    $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
    $stmt->execute();
    
    $nr = $stmt->rowCount();
    
    if ($nr == 1) {
        $mostrar = $stmt->fetch(PDO::FETCH_ASSOC);
        $namedestinatario = $mostrar['cliente'];
        $paracorreo = $mostrar['correoel'];
        $titulo = 'Notificación de Vencimiento';
        $mensaje  = $mostrar['tipo'];
        $fvencimiento = $mostrar['f_fin'];
        
        $mensaje1 =  '<!DOCTYPE html>'.
        '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.
            '<link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />'.
            '<link rel="stylesheet" href="css/main.css"/>'.
            '<link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet">'.
        '</head>'.
            
        '<body><div class="container">'.
            '<div class="jumbotron">'.
            '<div class="row">'.
                    '<div class="col-4 col-12-medium">'.
                        '<span class="image right"><img src="data:image/png;base64,'.$logo.'" alt="Logo" width="15%"/></a></span>'.
                    '</div>'.
                '</div>'. 
                
                '<h1 style="color:#1f1857;text-align: center;">Sistema de Vencimientos de Seguros</h1>'.
                
                '<br><p style="text-align: center;"><strong>'.$namedestinatario.'</strong> Su seguro <strong>'.$mensaje.'</strong>  </p>'.
                '<p style="text-align: center;"> Vencerá el próximo:  <strong>'.$fvencimiento.'</strong> </p>'.
                '<p style="text-align: center;"> Contactese con nosotros. </p>'.
                '<div class="container">'.
                '<br><p>A nuesto WebSite: <div class="footer span">'.
                
                    '<center> <b class="copyright"><a href="https://liansegurosltda.com/"> Lianseguros Ltda.</a>'.
                        '&copy; '.date("Y").' Vive tu Seguro</b>'.
                    '</center>'.
                    '<script src="jss/jquery.min.js"></script>'.
                    '<script src="js/bootstrap.min.js"></script>'.
                '</div>'.
        '</div></p>'.
            '</div>'.
        '</div>'.
        '</body>'.
        '</html>';

        // Configuración servidor mail
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'html';
        $mail->IsSMTP();
        $mail->Host = 'mail.liansegurosltda.com'; // servidor smtp
        $mail->Port = 465; // puerto
        $mail->SMTPAuth = true;
        $mail->Username = 'notificaciones@liansegurosltda.com'; // nombre usuario
        $mail->Password = 'Lian.seguros1992'; // contraseña
        $mail->SMTPSecure = 'ssl'; // seguridad
        $mail->From = 'notificaciones@liansegurosltda.com'; // remitente
        $mail->FromName = 'LIANSEGUROS LTDA.';
        $mail->AddAddress($paracorreo, $namedestinatario); // recipients email
        $mail->WordWrap = 50;
        $mail->IsHTML(true);
        $mail->Subject = $titulo;
        $mail->Body = $mensaje1;

        // $mail->AddEmbeddedImage('images/logo.png', 'header_jpg', 'attachment', 'base64', 'image/png'); 
        $mail->AltBody = '';

        // Avisar si fue enviado o no y dirigir al index
        if ($mail->Send()) {
            $sendm = $filtro;
            echo '<script type="text/javascript">
                alert("Se ha enviado un correo de notificación...!!! '.$sendm.'");
                window.location="../expiration.php?filter='.$sendm.'";
            </script>';
        } else {
            echo '<script type="text/javascript">
                alert("Error al enviar correo: ' . addslashes($mail->ErrorInfo) . '");
                window.location="../expiration.php?filter='.$sendm.'";
            </script>';
        }
    } else {
        echo '<script type="text/javascript">
            alert("NO EXISTE EL CORREO SOLICITADO, intentar de nuevo");
            window.location="../expiration.php?filter='.$sendm.'";
        </script>';
    }
    
} catch (PDOException $e) {
    // Manejo de errores de base de datos
    error_log("Error en consulta PDO: " . $e->getMessage());
    echo '<script type="text/javascript">
        alert("Error en la base de datos. Intente nuevamente.");
        window.location="../expiration.php?filter='.$sendm.'";
    </script>';
} catch (Exception $e) {
    // Manejo de otros errores
    error_log("Error general: " . $e->getMessage());
    echo '<script type="text/javascript">
        alert("Ocurrió un error inesperado. Intente nuevamente.");
        window.location="../expiration.php?filter='.$sendm.'";
    </script>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="icon" href="images/Icono.png" type="image/png" sizes="32x32" />
    <link rel="shortcut icon" type="image/png " href="images/Icono.png" />
</head>
<body>
</body>
</html>