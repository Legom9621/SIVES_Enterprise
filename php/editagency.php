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
require_once("magency.php"); // <- AQUI IRA EL MENÚ NUEVO

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


// Obtener y limpiar el parámetro de manera segura
$nik = isset($_GET["nik"]) ? strip_tags($_GET["nik"]) : null;

// Validar que se proporcionó el parámetro
if (!$nik) {
    echo "<script> alert('No se proporcionó identificación de la agencia.'); window.location= '/main.php'</script>";
    exit;
}

// Variables para los datos de la agencia
$row = null;
$estado = "";
$upcust = 0;

try {
    $sql = "SELECT * FROM tb_agencia WHERE id_agencia = :nik";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':nik', $nik, PDO::PARAM_STR);
    $stmt->execute();

    if($stmt->rowCount() == 0){
        echo "<script> alert('No existe la Agencia seleccionada.'); window.location= '/agency.php'</script>";
        exit;
        //header("Location: ../agency.php");
        }else{
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if($row['estado'] == '1'){
                $estado = "Activo";
                $upcust = 1;
                }
                else if ($row['estado'] == '2' ){
                $estado = "Desactivado";
                $upcust = 2;
                }
    }
}catch (PDOException $e) {
    // Manejo de errores de PDO
    error_log("Error PDO en editagency.php: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Error en la base de datos. Por favor, intente nuevamente.
          </div>';
    echo "<script>setTimeout(function(){ window.location.href = '/agency.php'; }, 3000);</script>";
    exit;
} catch (Exception $e) {
    // Manejo de otros errores
    error_log("Error general en editagency.php: " . $e->getMessage());
    echo '<div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            Ocurrió un error inesperado.
          </div>';
    echo "<script>setTimeout(function(){ window.location.href = '/agency.php'; }, 3000);</script>";
    exit;
}

if(isset($_POST['save'])){
    try {
        $nro_doc    = htmlspecialchars($_POST["nro_doc"] ?? '', ENT_QUOTES, 'UTF-8');
        $nombre     = htmlspecialchars($_POST["nombre"] ?? '', ENT_QUOTES, 'UTF-8');
        $contacto   = htmlspecialchars($_POST["contacto"] ?? '', ENT_QUOTES, 'UTF-8');
        $cargo      = htmlspecialchars($_POST["cargo"] ?? '', ENT_QUOTES, 'UTF-8');
        $nro_fijo   = htmlspecialchars($_POST["nro_fijo"] ?? '', ENT_QUOTES, 'UTF-8');
        $nro_cel    = htmlspecialchars($_POST["nro_cel"] ?? '', ENT_QUOTES, 'UTF-8');
        $email      = htmlspecialchars($_POST["email"] ?? '', ENT_QUOTES, 'UTF-8');
        
        // Usar PDO para la actualización
        $sql_update = "UPDATE tb_agencia SET contacto = :contacto, cargo = :cargo, 
                       nro_fijo = :nro_fijo, nro_cel = :nro_cel, email = :email 
                       WHERE id_agencia = :nik";
        
        $stmt_update = $con->prepare($sql_update);
        $stmt_update->bindParam(':contacto', $contacto, PDO::PARAM_STR);
        $stmt_update->bindParam(':cargo', $cargo, PDO::PARAM_STR);
        $stmt_update->bindParam(':nro_fijo', $nro_fijo, PDO::PARAM_STR);
        $stmt_update->bindParam(':nro_cel', $nro_cel, PDO::PARAM_STR);
        $stmt_update->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt_update->bindParam(':nik', $nik, PDO::PARAM_STR);
        
        if($stmt_update->execute()){
            header("Location: editagency.php?nik=" . urlencode($nik) . "&pesan=sukses");
            exit;
        } else {
            echo "<script>alert('Error, no se pudo actualizar la agencia.');window.location.href = '/agency.php';</script>";
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error al actualizar agencia: " . $e->getMessage());
        echo "<script>alert('Error en la base de datos al actualizar la agencia.');window.location.href = '/agency.php';</script>";
        exit;
    }
}

if(isset($_GET['pesan']) && $_GET['pesan'] == 'sukses'){
    echo "<script>alert('Los datos de la Agencia han sido guardados con éxito.');window.location.href = '/agency.php';</script>";
    exit;
}
?>

<style>
    .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
    }

    @media (min-width: 768px) {
        .bd-placeholder-img-lg {
            font-size: 3.5rem;
        }
    }

    .b-example-divider {
        width: 100%;
        height: 3rem;
        background-color: rgba(0, 0, 0, .1);
        border: solid rgba(0, 0, 0, .15);
        border-width: 1px 0;
        box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
    }

    .b-example-vr {
        flex-shrink: 0;
        width: 0.5rem;
        height: 100vh;
    }

    .bi {
        vertical-align: -.125em;
        fill: currentColor;
    }

    .nav-scroller {
        position: relative;
        z-index: 2;
        height: 2.75rem;
        overflow-y: hidden;
    }

    .nav-scroller .nav {
        display: flex;
        flex-wrap: nowrap;
        padding-bottom: 1rem;
        margin-top: -1px;
        overflow-x: auto;
        text-align: center;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .bd-mode-toggle {
        z-index: 1500;
    }

    .bd-mode-toggle .dropdown-menu .active .bi {
        display: block !important;
    }
</style>

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
         
        <!--Aca inicia la sección de la derecha-->
        <div class="scrollable-active container">
            <section id="mensaje" style="display: block; margin: inherit;">
                <center>
                    <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                </center>
                <fieldset>
                    <center>
                        <h2 class="main-title-modern">Sistema de Vencimientos de Seguros - SIVES -</h2>
                    </center>
                    <center>
                        <div class="content">
                            <h1 class="main-subtitle-corporate">Bienvenido</h1>
                            <p class="main-subtitle-minimal">A la sección Gestión de Agencias de Seguros.</p>
                        </div>
                    </center>
                </fieldset>
            </section>
                <center>
                    <form class="form-horizontal" action="" method="post">
                        <fieldset>
                            <div class="form-rowc col-md-12">
                                <div class="form-groupc col-md4">
                                    <p><label for="codigo" class="fs-5 fw-semibold">NIT.</label></p>
                                    <input type="text" name="codigo" value="<?php echo htmlspecialchars($row['nro_doc'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)" class="form-control" placeholder="NIK" readonly>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-3">
                                    <p><label for="nombre" class="fs-5 fw-semibold">Agencia.</label></p>
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($row['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Agencia" readonly>
                                    <span class="text-danger"></span>
                                </div>
                            </div>

                            <div class="form-rowc col-md-12">
                                <div class="form-groupc col-md-4">
                                    <p><label for="contacto" class="fs-5 fw-semibold">Contacto.</label></p>
                                    <input type="text" name="contacto" value="<?php echo htmlspecialchars($row['contacto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Contacto" required>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-2">
                                    <p><label for="cargo" class="fs-5 fw-semibold">Cargo.</label></p>
                                    <input type="text" name="cargo" value="<?php echo htmlspecialchars($row['cargo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Cargo">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-2">
                                    <p><label for="nro_fijo" class="fs-5 fw-semibold">Número Fijo.</label></p>
                                    <input type="text" name="nro_fijo" value="<?php echo htmlspecialchars($row['nro_fijo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)"  class="form-control" placeholder="Nombres">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-2">
                                    <p><label for="nro_cel" class="fs-5 fw-semibold">Número Celular.</label></p>
                                    <input type="text" name="nro_cel" value="<?php echo htmlspecialchars($row['nro_cel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)" class="form-control" placeholder="Número Celular">
                                    <span class="text-danger"></span>
                                </div>
                            </div>

                            <div class="form-rowc col-md-12">
                                <div class="form-groupc col-md-5">
                                    <p><label for="email" class="fs-5 fw-semibold">Email.</label></p>
                                    <input type="mail" name="email" value="<?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Email">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-3">
                                    <p><label for="estado" class="fs-5 fw-semibold">Estado.</label></p>
                                    <input type="text" name="estado" value="<?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Estado" readonly>
                                    <span class="text-danger"></span>
                                </div>
                            </div>
                        </fieldset>
                                <div class="form-rowec1 col-md-6">
                                    <div class="form-group1">
                                        <input type="submit" name="save" class="btn btn-primarya" value="Guardar">
                                        <a href="/agency.php" class="btn btn-dangera">Cancelar</a>
                                    </div>
                                </div>
                    </form>
                </center>
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
    $('.date').datepicker({
        format: 'dd-mm-yyyy',
    })

    function permitirSoloNumeros(event) {
            const input = event.target;
            // Permite sólo números y caracteres de control
            if (/\D/.test(event.key) && !['Backspace', 'ArrowLeft', 'ArrowRight', 'Delete'].includes(event.key)) {
                event.preventDefault();
            }
        }

    function validarNumero(event) {
            const input = event.target;
            const valor = input.value;
            // Verifica si el valor es un número
            if (isNaN(valor) || valor.trim() === '') {
                input.style.borderColor = 'red';
                input.setCustomValidity('Por favor ingrese un número válido.');
            } else {
                input.style.borderColor = 'green';
                input.setCustomValidity('');
            }
        }

    </script>
</body>