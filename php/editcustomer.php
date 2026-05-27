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

// escaping, additionally removing everything that could be (html/javascript-) code
$nik = filter_input(INPUT_GET, 'nik', FILTER_VALIDATE_INT);

if ($nik === false || $nik === null || $nik <= 0) {
    // Redirigir o mostrar error
    echo "<script>alert('ID de cliente no válido.'); window.location.href='customer.php';</script>";
    exit;
}


// ====================== 1. VALIDACIÓN Y CONSULTA DEL CLIENTE ====================== //
try {
    // Validar y obtener ID de forma segura
    $nik = filter_input(INPUT_GET, 'nik', FILTER_VALIDATE_INT);
    
    if (!$nik || $nik <= 0) {
        header("Location: ../customer.php");
        exit;
    }

    // Consulta preparada con PDO
    $sql = "SELECT * FROM tb_cliente WHERE idtb_cliente = :nik";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':nik', $nik, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        header("Location: ../customer.php");
        exit;
    } else {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Procesar fecha de nacimiento
        $fechanac = $row['date'];
        $fecha_nacimiento_obj = DateTime::createFromFormat('d/m/Y', $fechanac);
        
        if ($fecha_nacimiento_obj) {
            $fecha_nacimiento_convertida = $fecha_nacimiento_obj->format('Y-m-d');
        } else {
            $fecha_nacimiento_convertida = '';
        }

        // Determinar estado
        if ($row['estado'] == '1') {
            $estado = "Activo";
            $upcust = 1;
        } else if ($row['estado'] == '2') {
            $estado = "Desactivado";
            $upcust = 2;
        }
    }

} catch (PDOException $e) {
    error_log("Error en consulta PDO: " . $e->getMessage());
    header("Location: ../customer.php?error=db");
    exit;
}

// ====================== 2. PROCESAMIENTO DEL FORMULARIO UPDATE ====================== //
if (isset($_POST['save'])) {
    try {
        // Validar y sanitizar datos del formulario
        $codigo = filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_NUMBER_INT);
        $nombres = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $tpodoc = filter_input(INPUT_POST, 'tpodoc', FILTER_SANITIZE_STRING);
        $dcou = filter_input(INPUT_POST, 'nro_doc', FILTER_SANITIZE_STRING);
        $fecnace = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
        $telefono = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $direcc = filter_input(INPUT_POST, 'addres', FILTER_SANITIZE_STRING);
        $correo = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
        $nrocel = filter_input(INPUT_POST, 'cellphone', FILTER_SANITIZE_STRING);
        
        // Validaciones adicionales
        if (empty($nombres) || empty($dcou)) {
            throw new Exception("Nombre y documento son obligatorios.");
        }
        
        if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Correo electrónico no válido.");
        }

        // Procesar fecha
        $fecha_nacimiento_obj = DateTime::createFromFormat('Y-m-d', $fecnace);
        if ($fecha_nacimiento_obj) {
            $fechaupdate = $fecha_nacimiento_obj->format('d/m/Y');
        } else {
            $fechaupdate = $fechanac; // Mantener fecha original si no se puede convertir
        }

        // UPDATE con PDO preparado
        $updateSql = "UPDATE tb_cliente SET 
                        idtb_cliente = :codigo, 
                        name = :nombres, 
                        tpodoc = :tpodoc, 
                        nro_doc = :dcou,
                        date = :fechaupdate, 
                        phone = :telefono, 
                        addres = :direcc, 
                        mail = :correo, 
                        cellphone = :nrocel, 
                        estado = :upcust 
                      WHERE idtb_cliente = :nik";

        $updateStmt = $con->prepare($updateSql);
        
        // Bind de parámetros
        $updateStmt->bindParam(':codigo', $codigo, PDO::PARAM_INT);
        $updateStmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
        $updateStmt->bindParam(':tpodoc', $tpodoc, PDO::PARAM_STR);
        $updateStmt->bindParam(':dcou', $dcou, PDO::PARAM_STR);
        $updateStmt->bindParam(':fechaupdate', $fechaupdate, PDO::PARAM_STR);
        $updateStmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $updateStmt->bindParam(':direcc', $direcc, PDO::PARAM_STR);
        $updateStmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $updateStmt->bindParam(':nrocel', $nrocel, PDO::PARAM_STR);
        $updateStmt->bindParam(':upcust', $upcust, PDO::PARAM_INT);
        $updateStmt->bindParam(':nik', $nik, PDO::PARAM_INT);

        if ($updateStmt->execute() && $updateStmt->rowCount() > 0) {
            echo "<script>
                    alert('El cliente " . addslashes($nombres) . " ha sido Actualizado.');
                    window.location.href = '../customer.php?';
                </script>";
            exit;
        } else {
            throw new Exception("No se pudo actualizar el cliente.");
        }

    } catch (Exception $e) {
        echo "<script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                window.location.href = '../customer.php?';
            </script>";
        exit;
    }
}

// ====================== 3. MENSAJE DE CONFIRMACIÓN (si aplica) ====================== //
if (isset($_GET['pesan']) && $_GET['pesan'] == 'sukses') {
    $nombreCliente = isset($nombres) ? $nombres : (isset($row['name']) ? $row['name'] : '');
    echo "<script>
            alert('Los datos del cliente " . addslashes($nombreCliente) . " han sido guardados con éxito.');
            window.location.href = '../customer.php?';
        </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/png;base64,<?php echo $icono; ?>" type="image/png" sizes="32x32">
    <link rel="shortcut icon" type="image/png " href="images/Icono.png" />
    <title>Inicio · Sives v1.1.1</title>

    <!-- AÑADIR ESTAS DOS LÍNEAS FALTANTES PARA EL MENÚ -->
    <link href="/css/menu.css" rel="stylesheet">
    <link href="/css/offcanvas.css" rel="stylesheet">

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
</head>

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

<body>
    <main class="d-flex justify-content-center align-items-center vh-100 bg-light">
            <!-- ====================== MENÚ LATERAL ====================== -->
            <?php pintarMenu($perfil, $username); ?>
            <!-- llamada a la función del nuevo menu -->

            <!-- ====================== CONTENIDO PRINCIPAL ====================== -->

            <div class="b-example-divider b-example-vr" style="height: 850px;"></div>
            <!--Aca inicia la sección de la derecha-->
            <div class="scrollable-active container">
                <div class="content">
                    <section id="mensaje" style="display: block; margin: inherit;">
                        <center>
                            <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                        </center>
                        <fieldset>
                            <center>
                                <h2 class="main-title-modern">Sistema de Vencimientos de Seguros - SIVES -</h2>
                            </center>
                            <center>
                                <div class="main-subtitle-corporate">
                                    <h3>Datos del Cliente &raquo; Editar datos</h3>
                                </div>
                            </center>
                        </fieldset>
                    </section>
                    <hr />
                    <center>
                        <form class="form-horizontal" action="" method="post">
                            <fieldset>
                                <div class="form-rowc col-md-12">
                                    <div class="form-groupc col-md4">
                                        <p><label for="codigo" class="fs-5 fw-semibold">Código.</label></p>
                                        <input type="text" name="codigo" value="<?php echo $row ['idtb_cliente'];?>"
                                            class="form-control" placeholder="NIK" readonly>
                                        <span class="text-danger"></span>
                                    </div>
                                    <div class="form-groupc col-md-3">
                                        <p><label for="tpodoc" class="fs-5 fw-semibold">Tipo de Documento.</label></p>
                                        <input type="text" name="tpodoc" value="<?php echo $row ['tpodoc']; ?>"
                                            class="form-control" placeholder="Tipo de Documento" readonly>
                                        <span class="text-danger"></span>
                                    </div>
                                    <div class="form-groupc col-md-3">
                                        <p><label for="nro_doc" class="fs-5 fw-semibold">Nro. Documento.</label></p>
                                        <input type="text" name="nro_doc" value="<?php echo $row ['nro_doc'];?>"
                                            oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)"
                                            class="form-control" placeholder="Nro. Documento" require>
                                        <span class="text-danger"></span>
                                    </div>
                                </div>

                                <div class="form-rowc col-md-12">
                                    <div class="form-groupc col-md-4">
                                        <p><label for="name" class="fs-5 fw-semibold">Nombres.</label></p>
                                        <input type="text" name="name" value="<?php echo $row ['name']; ?>"
                                            class="form-control" placeholder="Nombres">
                                        <span class="text-danger"></span>
                                    </div>
                                    <div class="form-groupc col-md-3">
                                        <p><label for="date" class="fs-5 fw-semibold">Fecha Nacimiento.</label></p>
                                        <input type="date" name="date" value="<?php echo $fecha_nacimiento_convertida; ?>"
                                            class="form-control" placeholder="Nombres">
                                        <span class="text-danger"></span>
                                    </div>
                                    <div class="form-groupc col-md-3">
                                        <p><label for="addres" class="fs-5 fw-semibold">Dirección.</label></p>
                                        <input type="text" name="addres" value="<?php echo $row ['addres']; ?>"
                                            class="form-control" placeholder="Dirección">
                                        <span class="text-danger"></span>
                                    </div>
                                </div>

                                <div class="form-rowc col-md-12">
                                    <div class="form-groupc col-md4">
                                        <p><label for="phone" class="fs-5 fw-semibold">Teléfono.</label></p>
                                        <input type="text" name="phone" value="<?php echo $row ['phone']; ?>"
                                            oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)"
                                            class="form-control" placeholder="Teléfono">
                                        <span class="text-danger"></span>
                                    </div>
                                    <div class="form-groupc col-md-3">
                                        <p><label for="cellphone" class="fs-5 fw-semibold">Celular.</label></p>
                                        <input type="text" name="cellphone" value="<?php echo $row ['cellphone']; ?>"
                                            oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)"
                                            class="form-control" placeholder="Celular">
                                        <span class="text-danger"></span>
                                    </div>
                                    <div class="form-groupc col-md-5">
                                        <p><label for="mail" class="fs-5 fw-semibold">E-mail.</label></p>
                                        <input type="mail" name="mail" value="<?php echo $row ['mail']; ?>"
                                            class="form-control" placeholder="E-mail">
                                        <span class="text-danger"></span>
                                    </div>
                                </div>

                                <div class="form-rowc col-md-12">
                                    <div class="form-groupc col-md4">
                                        <p><label for="estado" class="fs-5 fw-semibold">Estado.</label></p>
                                        <input type="text" name="estado" value="<?php echo $estado; ?>" class="form-control"
                                            placeholder="Estado" readonly>
                                        <span class="text-danger"></span>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-rowec1 col-md-6">
                                <div class="form-group1">
                                    <input type="submit" name="save" class="btn btn-primarya" value="Guardar">
                                    <a href="/customer.php" class="btn btn-dangera">Cancelar</a>
                                </div>
                            </div>
                        </form>
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