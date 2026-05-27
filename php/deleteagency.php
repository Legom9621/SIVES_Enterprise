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
require_once("magency.php"); // <- AQUI IRA EL MENÚ NUEVO

// ====================== CLASE PARA MANEJO DE REDIRECCIONES ====================== //
class MessageHandler {
    public static function redirectWithSuccess($msg) {
        $_SESSION['operation_success'] = $msg;
        header("Location: /agency.php");
        exit();
    }

    public static function redirectWithError($msg) {
        $_SESSION['operation_error'] = $msg;
        header("Location: /agency.php");
        exit();
    }
    
    public static function redirectWithInfo($msg) {
        $_SESSION['operation_info'] = $msg;
        header("Location: /agency.php");
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
$nro_doc = '';
$nombre = '';
$contacto = '';
$cargo = '';
$nro_fijo = '';
$nro_cel = '';
$email = '';

if (isset($_GET['aksi']) && $_GET['aksi'] === 'delete') {
    
    // Sanitizar el parámetro
    $nik = isset($_GET['nik']) ? strip_tags($_GET['nik']) : '';
    
    if (empty($nik)) {
        MessageHandler::redirectWithError("No se proporcionó identificación de la agencia.");
    }

    // Verificar si existen registros
    try {
        $sqlCheck = "SELECT * FROM tb_agencia WHERE id_agencia = :nik";
        $stmtCheck = $con->prepare($sqlCheck);
        $stmtCheck->bindParam(':nik', $nik, PDO::PARAM_STR);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() == 0) {
            MessageHandler::redirectWithInfo("No se encontró la agencia especificada.");
        } else {
            // Primero obtener datos de la agencia para el log
            $agenciaData = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            // Eliminar registro
            $sqlDelete = "DELETE FROM tb_agencia WHERE id_agencia = :nik";
            $stmtDelete = $con->prepare($sqlDelete);
            $stmtDelete->bindParam(':nik', $nik, PDO::PARAM_STR);

            if ($stmtDelete->execute()) {
                // Registrar en log
                error_log("Agencia eliminada: ID=" . $nik . ", Nombre=" . ($agenciaData['nombre'] ?? 'N/A') . ", por usuario=" . $user_id);
                
                MessageHandler::redirectWithSuccess("La agencia ha sido eliminada correctamente.");
            } else {
                error_log("Error al eliminar agencia ID=" . $nik);
                MessageHandler::redirectWithError("Error, no se pudo eliminar la agencia.");
            }
        }
    } catch (PDOException $e) {
        error_log("Error PDO en deleteagency.php: " . $e->getMessage());
        MessageHandler::redirectWithError("Error en la base de datos. Por favor, intente nuevamente.");
    } catch (Exception $e) {
        error_log("Error general en deleteagency.php: " . $e->getMessage());
        MessageHandler::redirectWithError("Ocurrió un error inesperado.");
    }
}

// Si no es una solicitud de eliminación, mostrar formulario HTML
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
     <link rel="icon" href="data:image/png;base64,<?php echo $icono; ?>" type="image/png" sizes="32x32">
    <link rel="shortcut icon" type="image/png " href="images/Icono.png" />
    <title>Inicio · Sives v1.1.1</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/compania.css" rel="stylesheet">
    <link href="/css/signin.css" rel="stylesheet">
    <link href="/css/formularios.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <link href="/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="/css/titulos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet">
    <link href="/css/sidebars.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sidebars.css" rel="stylesheet">
</head>
<body>
    <main class="d-flex flex-nowrap">
        <!-- ====================== MENÚ LATERAL ====================== -->
        <?php pintarMenu($perfil, $username); ?>  <!-- llamada a la función del nuevo menu -->

        <!-- ====================== CONTENIDO PRINCIPAL ====================== -->
        <div class="scrollable-active container">
            <div class="content">
                <section id="mensaje" style="display: block; margin: inherit;">
                    <center>
                        <img class="mb-4 mx-auto" src="/IMAGES/logo.png" alt="lianseguros" width="50%" height="35%">
                    </center><br>
                    <fieldset>
                        <center>
                            <h3 class="fs-7 fw-semibold">Sistema de Vencimientos de Seguros - SIVES -</h3>
                        </center>
                        <center>
                            <div class="content">
                                <h3>Eliminar Agencia de Seguros</h3>
                            </div>
                        </center>
                    </fieldset>
                </section>
                <hr />
                
                <center>
                    <div class="alert alert-warning" role="alert">
                        <h4 class="alert-heading">¡Atención!</h4>
                        <p>Esta página se utiliza para eliminar agencias mediante parámetros URL.</p>
                        <p>Para eliminar una agencia, acceda a través del listado en <a href="/agency.php">Agencias</a>.</p>
                        <hr>
                        <p class="mb-0">Si llegó aquí por error, regrese al <a href="/main.php">inicio</a>.</p>
                    </div>
                    
                    <div class="card" style="width: 18rem;">
                        <div class="card-body">
                            <h5 class="card-title">Acción requerida</h5>
                            <p class="card-text">No se ha especificado una agencia para eliminar.</p>
                            <a href="/agency.php" class="btn btn-primary">Volver a Agencias</a>
                            <a href="/main.php" class="btn btn-secondary">Ir al Inicio</a>
                        </div>
                    </div>
                </center>
            </div>
        </div>
    </main>

  <script src="/js/bootstrap.bundle.min.js"></script>
    <script src="/js/sidebars.js"></script>
    <script>
        src = "/js/bootstrap.min.js"
    </script>
    <script>
        src = "/js/jquery-1.10.2.js"
    </script>
    <script src="/js/color-modes.js"></script>
    
    <script>
        // Mostrar mensajes de sesión si existen
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['operation_success'])): ?>
                alert("<?php echo addslashes($_SESSION['operation_success']); ?>");
                <?php unset($_SESSION['operation_success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['operation_error'])): ?>
                alert("<?php echo addslashes($_SESSION['operation_error']); ?>");
                <?php unset($_SESSION['operation_error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['operation_info'])): ?>
                alert("<?php echo addslashes($_SESSION['operation_info']); ?>");
                <?php unset($_SESSION['operation_info']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>