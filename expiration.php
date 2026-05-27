<?php
// expiration.php
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
require_once("php/dbconnect.php");
require_once("php/SED.php");
require_once("php/cockies.php");
require_once("php/userstate.php");
require_once("php/userlist.php");
require_once("php/mexpiration.php"); // <- AQUI IRA EL MENÚ NUEVO

$logo = "";
$icono = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["fech1"], $_POST["fech2"])){
    $fechainicio = $_POST["fech1"];
    $fechafin = $_POST["fech2"];
    echo "<script> alert('Las fechas a buscar son:  $fechainicio y $fechafin');</script>";
    $style_mensaje = "display: none;"; 
    $style1 = "display: block;"; // Mantener esta para mostrar #fechasfilter
    $is_post_dates = true;
} else {
    $is_post_dates = false;
    // (Asegúrate de que $style1 también esté definido si no lo está)
    if (!isset($style1)) {
        $style1 = "display: none;";// Asegurar que #fechasfilter esté oculto por defecto
        $style_mensaje = "display: block;";
    }
}

// Valida si hay productos en la tabla (consulta general)
$qryproduct = "SELECT ts.*, c.name as cliente, a.nombre, a.id_agencia,  s.seguro, t.tposeguro  FROM tb_productos ts INNER JOIN tb_agencia a ON
            ts.nrodoc_agencia = a.nro_doc INNER JOIN tb_seguro s ON ts.idtb_seguro = s.idtb_seguro  INNER JOIN tb_tposeguro t ON
            ts.idtb_tposeguro = t.idtb_tposeguro INNER JOIN tb_cliente c ON ts.nrodoc_cliente = c.nro_doc
            ORDER BY idtb_productos ASC";
$stmtq = $con->prepare($qryproduct);
$stmtq->execute();
$row = $stmtq->fetch(PDO::FETCH_ASSOC);

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
        <?php pintarMenu($perfil, $username); ?>  <!-- llamada a la función del nuevo menu -->

        <!-- ====================== CONTENIDO PRINCIPAL ====================== -->
        <div class="scrollable-active">
            <section id="mensaje" class="" style="<?php echo $style_mensaje; ?>; margin: inherit;">
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
                            <p class="main-subtitle-minimal">A la sección Gestión de Vencimientos.</p>
                        </div>
                    </center>
                </fieldset>

                <?php
                // Si no hay resultados en la consulta general, mostramos upload CSV
                if (!$stmtq || $row == 0) {
                    ?>
                    <script class="containertbl form-signin m-auto form-signin"> alert("No hay información registrada.");</script>
                    <div class="containertbl form-signin m-auto form-signin">
                        <div class="container">
                            <h3>Cargar e importar archivo excel con formato <strong>CSV</strong>.</h3>
                            <form name="importa" method="post" action="php/importprod.php" enctype="multipart/form-data">
                                <div class="col-xs-4">
                                    <div class="form-groupr">
                                        <input id="flie" type="file" accept=".csv" class="btn btn-info dVolver ms-auto" data-buttonText="Seleccione archivo" name="file">
                                    </div>
                                </div>
                                <div class="dVolver ms-auto">
                                    <input type="submit" id="submit" name="import" class="btn btn-info" value="Importarlo"/>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="response" class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>"><?php if(!empty($message)) { echo $message; } ?></div>
                    <?php
                } else {
                    // Si hay datos en la consulta general, mostramos una nota y las demás opciones
                    // IMPORTANTE: no mostramos tablas por defecto (se ocultarán por JS)
                    // Mantengo tu lógica de impresión de filas en los formularios concretos (Frmproximos, Frmvencidos, etc.)
                }
                ?>
            </section>

            <!-- FORM: Próximos a vencer (oculto por defecto) -->
            <form id="Frmproximos" role="form" name="Frmproximos" action="/expiration.php" enctype="multipart/form-data" method="POST" style="display: none;">
                <fieldset>
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center><br><br>
                    <center><legend><h1 class="main-subtitle-corporate">Seguros próximos a vencer.</h1></legend></center>
                </fieldset>
                <br>
                <fieldset>
                    <center>
                        <div class="table-scroll-container ">
                            <div class="">
                                <table class="tablesprod" id=""><!-- movtable_vencidos-->
                                    <thead>
                                        <tr>
                                            <th class='th-sortable text-left'>Cliente</th>
                                            <th class='th-sortable text-left'>Aseguradora</th>
                                            <th class='th-sortable text-left'>Seguro</th>
                                            <th class='th-sortable text-left'>Tipo Seguro</th>
                                            <th class='th-sortable text-left'>Nro. Poliza</th>
                                            <th class='th-sortable text-left'>Fecha Inicio</th>
                                            <th class='th-sortable text-left'>Fecha Fin</th>
                                            <th class="th-sortable text-left">Ciudad</th>
                                            <th class="th-sortable text-left">Prima</th>
                                            <th class='th-sortable text-left'>Estado</th>
                                            <th class='text-left' scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                            <?php
                                            $filter = 1;
                                            // Consulta de próximos a vencer en PDO
                                                    $sqlSelect = "SELECT  
                                                                    tb_cliente.name AS cliente, 
                                                                    tb_agencia.nombre AS agencia, 
                                                                    tb_seguro.seguro AS seguro,
                                                                    tb_tposeguro.tposeguro AS tipo, 
                                                                    tb_productos.f_inicio,
                                                                    tb_productos.f_fin, 
                                                                    tb_productos.estado AS stado,
                                                                    tb_productos.nro_poliza AS poliza, 
                                                                    tb_productos.ciudad AS ciudad, 
                                                                    tb_productos.prima AS prima, 
                                                                    tb_productos.nrodoc_cliente AS documento
                                                                FROM tb_productos
                                                                INNER JOIN tb_cliente ON tb_productos.nrodoc_cliente = tb_cliente.nro_doc
                                                                INNER JOIN tb_agencia ON tb_productos.nrodoc_agencia = tb_agencia.nro_doc
                                                                INNER JOIN tb_seguro ON tb_productos.idtb_seguro = tb_seguro.idtb_seguro
                                                                INNER JOIN tb_tposeguro ON tb_productos.idtb_tposeguro = tb_tposeguro.idtb_tposeguro
                                                                WHERE DATEDIFF(f_fin, CURDATE()) >= 0
                                                                ORDER BY tb_productos.f_fin ASC;";

                                                    $stmtpv = $con->prepare($sqlSelect);
                                                    $stmtpv->execute();
                                                    $resultpv = $stmtpv->fetchAll(PDO::FETCH_ASSOC);

                                                    $fecha_actual = new DateTime(date('Y-m-d'));?>
                                                    <tbody id="tbodymov">
                                                    <!-- ⭐️ Validación: si NO hay registros -->
                                                    <?php if (empty($resultpv)) {?>
                                                    <center>
                                                            <tr>
                                                                <td class="no-records" colspan="12">
                                                                    No hay seguros próximos a vencer.
                                                                </td>
                                                            </tr>
                                                    </center>
                                                    <?php }else{?>
                                                        <!-- ⭐️ Si hay registros, recorrerlos normalmente -->
                                                            <?php
                                                                foreach ($resultpv as $rowpv) {
                                                                    echo "<tr>";
                                                                    echo "<td>".$rowpv['cliente']."</td>";
                                                                    echo "<td>".$rowpv['agencia']."</td>";
                                                                    echo "<td>".$rowpv['seguro']."</td>";
                                                                    echo "<td>".$rowpv['tipo']."</td>";
                                                                    echo "<td>".$rowpv['poliza']."</td>";
                                                                    echo "<td>".$rowpv['f_inicio']."</td>";
                                                                    echo "<td>".$rowpv['f_fin']."</td>";
                                                                    echo "<td>".$rowpv['ciudad']."</td>";
                                                                    echo "<td>".$rowpv['prima']."</td>";

                                                                    $fecha_final = new DateTime($rowpv['f_fin']);
                                                                    $dias = $fecha_actual->diff($fecha_final)->format('%r%a');

                                                                    echo "<td>";
                                                                    if ($dias <= 0) {
                                                                        echo '<span class="label label-info">Seguro Vencido</span>';
                                                                    } else {
                                                                        echo '<span class="label label-success">A '. $dias .' días de vencer</span>';
                                                                    }
                                                                    echo "</td>";

                                                                    echo "</td>"; 
                                                                    echo "<td class='col-estado'>"; 
                                                                    echo '<a href="php/sendnotificacion.php?nik='.$rowpv['documento'].'&filter1='.$filter.'" 
                                                                    title="Enviar Correo" onclick="return confirm(\'Esta seguro desea enviar el correo al cliente: '.addslashes($row['cliente']).'?\')" 
                                                                    class="btn btn-successfechas">
                                                                    <span><svg width="12px" height="12px" role="img" aria-label="Expiration"><use xlink:href="#pen-to-square"/></svg></span></a>'; 
                                                                    echo '<a href="agency.php?aksi=delete&nik='.urlencode($rowpv['cliente']).'" title="Eliminar" onclick="return confirm(\'Esta seguro de borrar los datos '.addslashes($row['cliente']).'?\')"
                                                                    class="btn btn-dangerfechas">
                                                                    <span><svg width="12px" height="12px" role="img" aria-label="Expiration"><use xlink:href="#eye"/></svg></span></a>'; 
                                                                    echo "</td>";

                                                                    echo "</tr>";
                                                                }
                                                            }
                                                            ?>
                                                    </tbody>
                                </table>
                            </div>
                    </center>
                </fieldset>
                <center><h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5></center>
            </form>

            <!-- FORM: Vencidos (oculto por defecto) -->
            <form id="Frmvencidos" name="Frmvencidos" role="form" action="/expiration.php" enctype="multipart/form-data" method="POST" style="display: none;">
                <fieldset>
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center><br>
                    <center><legend><h1 class="main-subtitle-corporate">Seguros vencidos.</h1></legend></center>
                </fieldset>
                <br>
                <fieldset>
                    <center>
                        <div class="table-scroll-container">
                            <?php $filter = 1; ?>
                            <div class="">
                                <table class="tablesprod" id="movtable_vencidos">
                                    <thead>
                                        <tr>
                                            <th class='th-sortable text-left'>Cliente</th>
                                            <th class='th-sortable text-left'>Aseguradora</th>
                                            <th class='th-sortable text-left'>Seguro</th>
                                            <th class='th-sortable text-left'>Tipo Seguro</th>
                                            <th class='th-sortable text-left'>Nro. Poliza</th>
                                            <th class='th-sortable text-left'>Fecha Inicio</th>
                                            <th class='th-sortable text-left'>Fecha Fin</th>
                                            <th class='th-sortable text-left'>Ciudad</th>
                                            <th class='th-sortable text-left'>Prima</th>
                                            <th class='th-sortable text-left'>Estado</th>
                                            <th class='text-left' scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <?php
                                            // Consulta de vencidos en PDO
                                            $sqlSelectv = "SELECT  
                                                                tb_cliente.name AS cliente, 
                                                                tb_agencia.nombre AS agencia, 
                                                                tb_seguro.seguro AS seguro,
                                                                tb_tposeguro.tposeguro AS tipo, 
                                                                tb_productos.f_inicio,
                                                                tb_productos.f_fin, 
                                                                tb_productos.ciudad, 
                                                                tb_productos.prima, 
                                                                tb_productos.estado AS stado,
                                                                tb_productos.nro_poliza AS poliza, 
                                                                tb_productos.nrodoc_cliente AS documento
                                                        FROM tb_productos
                                                        INNER JOIN tb_cliente ON tb_productos.nrodoc_cliente = tb_cliente.nro_doc
                                                        INNER JOIN tb_agencia ON tb_productos.nrodoc_agencia = tb_agencia.nro_doc
                                                        INNER JOIN tb_seguro ON tb_productos.idtb_seguro = tb_seguro.idtb_seguro
                                                        INNER JOIN tb_tposeguro ON tb_productos.idtb_tposeguro = tb_tposeguro.idtb_tposeguro
                                                        WHERE DATEDIFF(f_fin, CURDATE()) <= 0
                                                        GROUP BY idtb_productos;";

                                            $stmtv = $con->prepare($sqlSelectv);
                                            $stmtv->execute();
                                            $resultv = $stmtv->fetchAll(PDO::FETCH_ASSOC);

                                            $fecha_actual = new DateTime(date('Y-m-d'));?>

                                            <tbody id="tbodymov">
                                                <?php
                                                foreach ($resultv as $row) {
                                                    echo "<tr>";
                                                    echo "<td>".$row['cliente']."</td>";
                                                    echo "<td>".$row['agencia']."</td>";
                                                    echo "<td>".$row['seguro']."</td>";
                                                    echo "<td>".$row['tipo']."</td>";
                                                    echo "<td>".$row['poliza']."</td>";
                                                    echo "<td>".$row['f_inicio']."</td>";
                                                    echo "<td>".$row['f_fin']."</td>";
                                                    echo "<td>".$row['ciudad']."</td>";
                                                    echo "<td>".$row['prima']."</td>";

                                                    $fecha_final = new DateTime($row['f_fin']);
                                                    $dias = $fecha_actual->diff($fecha_final)->format('%r%a');

                                                    echo "<td>";
                                                    if ($dias <= 0) {
                                                        echo '<span class="label label-info">Vencido</span>';
                                                    } else {
                                                        echo '<span class="label label-success">A '. $dias .' días de vencer</span>';
                                                    }
                                                    echo "</td>";

                                                    echo "</td>"; 
                                                    echo "<td class='col-estado'>"; 
                                                    echo '<a href="php/sendnotificacion.php?nik='.$row['documento'].'&filter1='.$filter.'" title="Enviar Correo" onclick="return confirm(\'Esta seguro desea enviar el correo al cliente: '.addslashes($row['cliente']).'?\')" 
                                                    class="btn btn-successfechas">
                                                    <span><svg width="12px" height="12px" role="img" aria-label="Expiration"><use xlink:href="#pen-to-square"/></svg></span></a>'; 
                                                    echo '<a href="agency.php?aksi=delete&nik='.urlencode($row['cliente']).'" title="Eliminar" onclick="return confirm(\'Esta seguro de borrar los datos '.addslashes($row['cliente']).'?\')"
                                                    class="btn btn-dangerfechas">
                                                    <span><svg width="12px" height="12px" role="img" aria-label="Expiration"><use xlink:href="#eye"/></svg></span></a>'; 
                                                    echo "</td>";

                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                </table>
                            </div>
                    </center>
                </fieldset>
                <center><h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5></center>
            </form>

            <!-- FORM: Vencidos por rango de fechas  onclick="RangeDate('fechasfilter');" -->
            <form id="Frmvencidosfec" name="Frmvencidosfec" role="form" action="/expiration.php" enctype="multipart/form-data" method="POST" style="display: none;">
                <fieldset>
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center>
                    <center><legend><h1 class="main-subtitle-corporate">Seguros vencidos por rango de fechas.</h1></legend></center>
                </fieldset><br><br>
                <fieldset>
                    <center>
                        <form action="" role="form" method="POST" id="fechas" name="fechas" style="display: block;">
                            <div class="card shadow p-4 login-card">
                                <fieldset>
                                    <div class="form-rowcus col-md-12">
                                        <label class="fs-5 fw-semibold" for="fecha_inicio">Fecha de Inicio:</label>
                                        <input type="date" id="fech1" name="fech1" class="form-control" required />
                                    </div>
                                    <br>
                                    <div class="form-rowcus col-md-12">
                                        <label class="fs-5 fw-semibold" for="fecha_inicio">Fecha Final:</label>
                                        <input type="date" id="fech2" name="fech2" class="form-control" required />
                                    </div>
                                </fieldset>
                                <div class="dVolver ms-auto">
                                    <button class="btn btn-info" type="submit" id="signup" name="signup" value="Validar">Validar.</button>
                                </div>
                            </div>
                            <br>
                            <center><h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5></center>
                        </form>
                    </center>
                </fieldset>

                <fieldset>
                    <form action="" role="form" method="post" id="fechasfilter" name="fechasfilter" style="<?php echo $style1; ?>; margin: inherit;">
                        <fieldset>
                            <center> <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%"></center><br><br>
                            <center><legend><h1 class="main-subtitle-corporate">Seguros vencidos por rango de fechas.</h1></legend></center>
                        </fieldset>
                        <?php
                        // Si se enviaron fechas, procesar consulta (mantenemos tu lógica)
                        if (isset($fechainicio) && isset($fechafin)) {
                            if ($fechafin < $fechainicio) {
                                echo '<div class="alert alert-success alert-dismissable"> <a href="/expiration.php"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></a>
                                La Fecha Final --> '.$fechafin.' no debe ser menor a la Fecha Inicial '.$fechainicio.'.</div>';
                            } else {
                                    echo "<div class='table-scroll-container'>
                                        <table class='tablesprod'>
                                            <thead><tr>";
                                            echo "<th class='th-sortable text-left'>Cliente</th>
                                                <th class='th-sortable text-left'>Aseguradora</th>
                                                <th class='th-sortable text-left'>Seguro</th>
                                                <th class='th-sortable text-left'>Tipo Seguro</th>
                                                <th class='th-sortable text-left'>Nro. Poliza</th>
                                                <th class='th-sortable text-left'>Fecha Inicio</th>
                                                <th class='th-sortable text-left'>Fecha Fin</th>
                                                <th class='th-sortable text-left'>Ciudad</th>
                                                <th class='th-sortable text-left'>Prima</th>
                                                <th class='th-sortable text-left'>Estado</th>
                                                <th class='text-left'>Acciones</th>
                                            </tr></thead>";

                                            // Consulta usando PDO con parámetros
                                            $query = "SELECT  
                                                        tb_cliente.name AS cliente, 
                                                        tb_agencia.nombre AS agencia, 
                                                        tb_seguro.seguro AS seguro,
                                                        tb_tposeguro.tposeguro AS tipo, 
                                                        tb_productos.f_inicio, 
                                                        tb_productos.f_fin, 
                                                        tb_productos.ciudad AS ciudad, 
                                                        tb_productos.prima AS prima,
                                                        tb_productos.estado AS stado,
                                                        tb_productos.nro_poliza AS poliza, 
                                                        tb_productos.nrodoc_cliente AS documento
                                                    FROM tb_productos
                                                    INNER JOIN tb_cliente ON tb_productos.nrodoc_cliente = tb_cliente.nro_doc
                                                    INNER JOIN tb_agencia ON tb_productos.nrodoc_agencia = tb_agencia.nro_doc
                                                    INNER JOIN tb_seguro ON tb_productos.idtb_seguro = tb_seguro.idtb_seguro
                                                    INNER JOIN tb_tposeguro ON tb_productos.idtb_tposeguro = tb_tposeguro.idtb_tposeguro
                                                    WHERE f_fin BETWEEN :fechainicio AND :fechafin
                                                    ORDER BY idtb_productos";

                                            // Preparar sentencia
                                            $stmt = $con->prepare($query);

                                            // Ejecutar con parámetros
                                            $stmt->execute([
                                                ':fechainicio' => $fechainicio,
                                                ':fechafin'    => $fechafin
                                            ]);
                                            if( $stmt->rowCount() > 0 ){
                                            $fecha_actual = new DateTime(date('Y-m-d'));
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<tr>";
                                                echo "<td>".$row['cliente']."</td>
                                                <td>".$row['agencia']."</td>
                                                <td>".$row['seguro']."</td>
                                                <td>".$row['tipo']."</td>
                                                <td>".$row['poliza']."</td>
                                                <td>".$row['f_inicio']."</td>
                                                <td>".$row['f_fin']."</td>
                                                <td>".$row['ciudad']."</td>
                                                <td>".$row['prima']."</td>";
                                                $fecha_final = new DateTime($row['f_fin']);
                                                $dias = $fecha_actual->diff($fecha_final)->format('%r%a');
                                                echo "<td>";
                                                if ($dias <= 0) {
                                                    echo '<span class="label label-success">Vencido</span>';
                                                } elseif ($dias >= 2) {
                                                    echo '<span class="label label-info">Está a ' . $dias . ' días de vencer</span>';
                                                }
                                                echo "</td>";
                                                echo "<td>";
                                                echo "<a href=\"php/sendnotificacion.php?nik=".$row['documento']."&filter1=".$filter."\" 
                                                title=\"Enviar Correo\" onclick=\"return confirm('Esta seguro desea enviar el correo al cliente:  ".addslashes($row['cliente'])."?')\" 
                                                class=\"btn btn-successfechas\"><span><svg width=\"12px\" height=\"12px\" role=\"img\" aria-label=\"Customers\"><use xlink:href=\"#pen-to-square\"/></svg></span></a>";
                                                echo "<a href=\"agency.php?aksi=delete&nik=".$row['documento']."\" title=\"Eliminar\" onclick=\"return confirm('Esta seguro de borrar los datos ".addslashes($row['cliente'])."?')\"
                                                class=\"btn btn-dangerfechas\"><span><svg width=\"12px\" height=\"12px\" role=\"img\" aria-label=\"Customers\"><use xlink:href=\"#eye\"/></svg></span></a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                            }else{
                                                echo'<center>
                                                    <tr>
                                                        <td class="no-records" colspan="12">
                                                            No hay seguros próximos a vencer en ese periodo de fechas.
                                                        </td>
                                                    </tr>
                                                </center>';
                                            }
                                        echo "</table>";
                                    echo "</div>";
                                }
                        }
                        ?>
                        <center><h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5></center>
                    </form>
                </fieldset>
            </form>

        </div>

        <hr>
        <span class="text-success"><?php if (isset($successmsg)) { echo $successmsg; } ?></span>
        <span class="text-danger"><?php if (isset($errormsg)) { echo $errormsg; } ?></span>
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
        // ... código anterior de funciones JS ...

        // ⭐️ INYECTAR LA BANDERA PHP PARA SABER SI EL FORMULARIO DE FECHAS FUE ENVIADO
        const isPostDates = <?php echo json_encode($is_post_dates ?? false); ?>;

        // Seguridad: seleccionar elementos de forma defensiva
        document.addEventListener('DOMContentLoaded', function() {
            // Asegurarse de que los formularios / secciones centrales inicien ocultos
            document.querySelectorAll('.scrollable-active > section, .scrollable-active > form').forEach(section => {
                if (section) section.style.display = 'none';
            });

            // Ocultar tablas (si alguna quedó suelta fuera de forms) para evitar mostrar datos al cargar
            document.querySelectorAll('#movtable, #movtable_proximos, #movtable_vencidos').forEach(tbl => {
                if (tbl) tbl.style.display = 'none';
            });

            // Asegurar que el menu y mensaje estén visibles si existen
            var menu = document.getElementById('menu');
            var registro = document.getElementById('mensaje');
            
            if (menu) menu.style.display = 'block';
            
            // ⭐️ MODIFICACIÓN CLAVE: Solo hacemos visible #mensaje si NO se enviaron las fechas
            if (registro) {
                if (!isPostDates) {
                    registro.style.display = 'block';
                } else {
                    // Si es POST de fechas, nos aseguramos que esté oculto
                    registro.style.display = 'none'; 
                }
            }
            
            // ⭐️ Si es POST de fechas, debemos asegurarnos de mostrar el resultado
            if (isPostDates) {
                var fechasFilter = document.getElementById('fechasfilter');
                if (fechasFilter) fechasFilter.style.display = 'block';
            }
        });

                // Oculta solo los formularios hijos directos del área central
                function hideAllForms() {
                    document.querySelectorAll('.scrollable-active > section, .scrollable-active > form').forEach(section => {
                        if (section) section.style.display = 'none';
                    });
                }

                function showForm(formId) {
                    // Ocultar todos los formularios (form y secciones centrales)
                    document.querySelectorAll('form').forEach(form => { if (form) form.style.display = 'none'; });
                    hideAllForms();

                    // Mostrar el formulario requerido (si existe)
                    var el = document.getElementById(formId);
                    if (el) el.style.display = 'block';
                }

                function showMenu(formId) {
                    // Ocultar todos los formularios
                    document.querySelectorAll('form').forEach(form => { if (form) form.style.display = 'none'; });
                    // Mostrar el formulario requerido
                    var el = document.getElementById(formId);
                    if (el) el.style.display = 'block';
                    hideAllForms();
                }

                function cancelarform(formId) {
                    var el = document.getElementById(formId);
                    if (el) el.style.display = 'none';
                    var msg = document.getElementById("mensaje");
                    if (msg) msg.style.display = 'block';
                }

                function showNext(formId) {
                    var menu = document.getElementById('menu');
                    hideAllForms();

                    // ocultaciones específicas (si existen)
                    var elVencidos = document.getElementById("Frmvencidos");
                    if (elVencidos) elVencidos.style.display = 'none';
                    var elVencidosFec = document.getElementById("Frmvencidosfec");
                    if (elVencidosFec) elVencidosFec.style.display = 'none';
                    var elFechasFilter = document.getElementById("fechasfilter");
                    if (elFechasFilter) elFechasFilter.style.display = 'none';

                    // Mostrar el formulario requerido (Próximos)
                    var target = document.getElementById(formId === 'Frmproximos' ? 'Frmproximos' : formId);
                    if (target) {
                        target.style.display = 'block';
                        // Mostrar la tabla dentro del formulario (si existe)
                        var tbl = target.querySelector('#movtable_proximos');
                        if (tbl) tbl.style.display = 'table';
                    }
                    if (menu) menu.style.display = 'block';
                }

                function showExpirate(formId) {
                    var menu = document.getElementById('menu');
                    hideAllForms();

                    var elProximos = document.getElementById("Frmproximos");
                    if (elProximos) elProximos.style.display = 'none';
                    var elVencidosFec = document.getElementById("Frmvencidosfec");
                    if (elVencidosFec) elVencidosFec.style.display = 'none';
                    var elFechasFilter = document.getElementById("fechasfilter");
                    if (elFechasFilter) elFechasFilter.style.display = 'none';

                    var target = document.getElementById(formId);
                    if (target) {
                        target.style.display = 'block';
                        var tbl = target.querySelector('#movtable_vencidos');
                        if (tbl) tbl.style.display = 'table';
                    }
                    if (menu) menu.style.display = 'block';
                }

                function showDate(formId) {
                    var menu = document.getElementById('menu');
                    hideAllForms();

                    var elVencidos = document.getElementById("Frmvencidos");
                    if (elVencidos) elVencidos.style.display = 'none';
                    var elProximos = document.getElementById("Frmproximos");
                    if (elProximos) elProximos.style.display = 'none';
                    var elFechasFilter = document.getElementById("fechasfilter");
                    if (elFechasFilter) elFechasFilter.style.display = 'none';

                    var target = document.getElementById(formId);
                    if (target) target.style.display = 'block';
                    if (menu) menu.style.display = 'block';
                }

            function RangeDate(formId) {
                var menu = document.getElementById("menu");
                document.querySelectorAll("table").forEach(section => section.style.display = 'none');
                document.getElementById("movtable").style.display = 'none';
                document.getElementById("Frmvencidos").style.display = 'none';
                document.getElementById("Frmproximos").style.display = 'none';
                document.getElementById("fechas").style.display = 'none';
                document.getElementById("Frmvencidosfec").style.display = 'none'; 
                document.getElementById("mensaje").style.display = 'none'; 
                // Mostrar el formulario requerido fechasfilter
                document.getElementById(formId).style.display = 'block';
                menu.style.display = 'block';
            }

                // Funciones auxiliares
                function toggleMobileMenu() {
                    var sidebar = document.getElementById('sidebar');
                    if (sidebar) sidebar.classList.toggle('open');
                }

                function toggleUserMenu() {
                    var menu = document.getElementById('userDropdownMenu');
                    if (menu) menu.classList.toggle('show');
                }

                function closeUserMenu() {
                    var menu = document.getElementById('userDropdownMenu');
                    if (menu) menu.classList.remove('show');
                }

                /* =============================================================
                ORDENAMIENTO DE TABLAS POR COLUMNA
                Funciona con clases: th-sortable, th-asc, th-desc
                ============================================================= */

                document.addEventListener("DOMContentLoaded", function () {
                    const getCellValue = (row, index) => 
                        row.children[index].innerText || row.children[index].textContent;

                    const compare = (index, asc) => (a, b) => {
                        const v1 = getCellValue(asc ? a : b, index).trim();
                        const v2 = getCellValue(asc ? b : a, index).trim();

                        // =============================
                        // PARSEAR FECHAS EN VARIOS FORMATOS
                        // =============================
                        const parseDate = (value) => {
                            // Formato 2025-01-15 o 2025/01/15
                            if (/^\d{4}[-\/]\d{2}[-\/]\d{2}$/.test(value)) {
                                return new Date(value);
                            }

                            // Formato 15/01/2025 o 15-01-2025
                            if (/^\d{2}[-\/]\d{2}[-\/]\d{4}$/.test(value)) {
                                const [d, m, y] = value.split(/[-\/]/);
                                return new Date(`${y}-${m}-${d}`);
                            }

                            return null;
                        };

                        const d1 = parseDate(v1);
                        const d2 = parseDate(v2);

                        if (d1 && d2) return d1 - d2;

                        // Detectar números normales
                        const n1 = parseFloat(v1.replace(/,/g, ""));
                        const n2 = parseFloat(v2.replace(/,/g, ""));
                        if (!isNaN(n1) && !isNaN(n2)) return n1 - n2;

                        // Comparar texto
                        return v1.localeCompare(v2);        // Comparar texto
                    };

                    document.querySelectorAll(".th-sortable").forEach(th => {
                        th.addEventListener("click", function () {
                            const table = th.closest("table");
                            const tbody = table.querySelector("tbody");
                            const index = Array.from(th.parentNode.children).indexOf(th);
                            const asc = !th.classList.contains("th-asc");

                            // Resetear estado de otros encabezados
                            table.querySelectorAll(".th-sortable").forEach(el => {
                                el.classList.remove("th-asc", "th-desc");
                            });

                            // Agregar clase según el orden actual
                            th.classList.toggle("th-asc", asc);
                            th.classList.toggle("th-desc", !asc);

                            // Ordenar filas
                            const rows = Array.from(tbody.querySelectorAll("tr"))
                                .sort(compare(index, asc));

                            // Insertar filas ordenadas
                            rows.forEach(row => tbody.appendChild(row));
                        });
                    });
                });
    </script>
</body>
</html>
