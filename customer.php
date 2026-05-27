<?php
// customer.php
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
require_once("php/mcustomer.php"); // <- AQUI IRA EL MENÚ NUEVO

$logo = "";
$icono = "";

// OBTENER LOGO E ICONO DE LA COMPAÑIA DESDE LA BASE DE DATOS
try {
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
    error_log("Error obteniendo logo: " . $e->getMessage());
}

$customer = "SELECT * FROM tb_cliente ORDER BY name";
$stmt = $con->prepare($customer);
//$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$regcustmer = count($result);

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
$estado = '';
?>
<!doctype html>
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

    /* ESTILO ADICIONAL PARA EL MENÚ */
    .scrollable-active {
        margin-left: 280px;
        /* Espacio para el menú lateral */
        padding: 20px;
        width: calc(100% - 280px);
        transition: margin-left 0.3s ease;
    }

    @media (max-width: 768px) {
        .scrollable-active {
            margin-left: 0;
            width: 100%;
        }
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
            <section id="mensaje" style="display: block; margin: inherit;">
                <fieldset>
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                    </center>
                    <center>
                        <h2 class="main-title-modern">Sistema de Vencimientos de Seguros - SIVES -</h2>
                    </center>
                    <center>
                        <div class="content">
                            <h1 class="main-subtitle-corporate">Bienvenido</h1>
                            <p class="main-subtitle-minimal">A la sección Gestión de Clientes.</p>
                        </div>
                    </center>
                </fieldset>
            </section>

            <!--Formulario de registro de clientes y subir mediante Excel-->
            <section id="manage" style="display: none; margin: inherit;">
                <fieldset class="">
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                    </center>
                    <center>
                        <legend>
                            <h1 class="main-subtitle-corporate">Registro de clientes.</h1>
                        </legend>
                    </center>
                    <br>
                    <div class="cargando">
                        <div class="loader-outter"></div>
                        <div class="loader-inner"></div>
                    </div>
                </fieldset>
                <br>
                <!-- Campos de registro de usuario y validación si hay registros o subir Excel -->
                <?php
                                    if(!$result || $regcustmer == 0){
                                        echo '<script class="containertbl form-signin m-auto form-signin"> alert("No hay Clientes registrados.");</script>';
                                        echo '<div class="containertbl form-signin m-auto form-signin">';
                                        echo '<div class="container">';
                                        echo '<h3>Cargar e importar archivo excel con formato <strong>CSV</strong>.</h3>';
                                        echo '<form name="importa" method="post" action="php/importcust.php" enctype="multipart/form-data">';
                                        echo '<div class="col-xs-4">';
                                        echo '<div class="form-groupr">';
                                        echo '<input id="flie" type="file" accept=".csv" class="btn btn-info dVolver ms-auto" data-buttonText="Seleccione archivo" name="file">';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '<div class="dVolver ms-auto">';
                                        echo '<input type="submit"id="submit" name="import" class="btn btn-info" value="Importarlo"  />';
                                        echo '</div>';
                                        echo '</form>';
                                        echo '</div>';
                                        echo'</div>';
                                        echo'<div id="response" class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>"><?php if(!empty($message)) { echo $message; } ?>
        </div>';
        }else{?>
        <?php if (isset($_GET['mensaje'])): ?>
        <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        <?php endif; ?>

                    <form id="activeform" class="" role="form" action="" method="POST" name="activeform" style="display: block;">
                                    <?php try {
                                        // Preparar la consulta con PDO
                                        $sqlSelect = "SELECT * FROM tb_cliente ORDER BY idtb_cliente ASC";
                                        $stmt = $con->prepare($sqlSelect);
                                        $stmt->execute();
                                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if($result && count($result) > 0){ ?>
                                            <center>
                                                <div class="search">
                                                    <label for="find">
                                                        <p class="main-subtitle-corporate"><strong>Digite nombre de usuario ó Nro. de cedula.</strong></p>
                                                    </label>
                                                    <input type="text" class="form-row1" id="searchInputseg" placeholder="Buscar" required value=" "
                                                        class="form-control">
                                                    <span class="text-danger"></span>
                                                </div>
                                            </center>
                                            <div class="table-scroll-container">
                                                <table class="tablesprod" id="movtable">
                                                    <thead>
                                                        <tr style="color:#243587; background:#fff8dc">
                                                            <th class="th-sortable">Tpo.Doc.</th>
                                                            <th class="th-sortable">Documento.</th>
                                                            <th class="th-sortable">Nombre.</th>
                                                            <th class="th-sortable">Fecha Nacimiento.</th>
                                                            <th class="th-sortable">Dirección.</th>
                                                            <th class="th-sortable">Telefono.</th>
                                                            <th class="th-sortable">Celular.</th>
                                                            <th class="th-sortable">Email.</th>
                                                            <th class="">Estado.</th>
                                                            <th class="">Acciones.</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodymov">
                                                        <?php
                                                        // USAR foreach CON EL ARRAY $result
                                                        foreach ($result as $row) {
                                                            echo '<tr>';
                                                            echo '<td style="color:#456789;">'.$row['tpodoc'].'</td>';
                                                            echo '<td>'.$row['nro_doc'].'</td>';
                                                            echo '<td><strong>'.$row['name'].'</strong></td>';
                                                            echo '<td>'.$row['date'].'</td>';
                                                            echo '<td>'.$row['addres'].'</td>';
                                                            echo '<td>'.$row['phone'].'</td>';
                                                            echo '<td>'.$row['cellphone'].'</td>';
                                                            echo '<td>'.$row['mail'].'</td>';
                                                            echo '<td>';
                                                            if($row['estado'] == '1'){
                                                                echo '<span class="label label-success">Activo</span>';
                                                            } else if ($row['estado'] == '2'){
                                                                echo '<span class="label label-info">Inactivo</span>';
                                                            }
                                                            echo '</td>';
                                                            echo '<td class="">';
                                                            echo '<a href="php/activecustomer.php?nik='.$row['idtb_cliente'].'" title="Activar/Desactivar" class="btn btn-primaryfechas">';
                                                            echo '<span><svg class="" width="15px" height="12px" role="img" aria-label="Customers">';
                                                            echo '<use xlink:href="#check2" /></svg></span></a>';
                                                            echo '<a href="php/editcustomer.php?nik='.$row['idtb_cliente'].'" title="Actualizar cliente"';
                                                            echo 'onclick="return confirm(\'Esta seguro desea actualizar los datos de '.$row['name'].'?\')"';
                                                            echo 'class="btn btn-successfechas"><span>';
                                                            echo '<svg class="" width="15px" height="12px" role="img" aria-label="Customers">';
                                                            echo '<use xlink:href="#pen-to-square" /></svg></span></a>';
                                                            echo '<a href="php/deletecustomer.php?aksi=delete&nik='.$row['idtb_cliente'].'" title="Eliminar"';
                                                            echo 'onclick="return confirm(\'Esta seguro de borrar los datos '.$row['name'].'?\')" class="btn btn-dangerfechas">';
                                                            echo '<span><svg class="" width="15px" height="12px" role="img" aria-label="Customers">';
                                                            echo '<use xlink:href="#eye" /></svg></span></a>';
                                                            echo '</td>';
                                                            echo '</tr>';
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php 
                                        } else {
                                            echo '<div class="alert alert-info">No hay clientes registrados.</div>';
                                        }
                                    } catch (PDOException $e) {
                                        error_log("Error en consulta PDO: " . $e->getMessage());
                                        echo '<div class="alert alert-danger">Error al consultar la base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    }?>;
                    </form>
                        <br>
                        <center><br>
                            <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                        </center>
                        <?php } ?>
                        </section>
                    <!-- Formulario de agregar clientes-->
                    <form id="register" class="" role="form" action="php/registercustomer.php" method="POST"
                        enctype="multipart/form-data" name="register" style="display: block;">
                        <fieldset class="">
                            <center>
                                <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                            </center>
                            <center>
                                <legend>
                                    <h1 class="main-subtitle-corporate">Aaregar nuevo cliente.</h1>
                                </legend>
                            </center>
                        </fieldset>
                        <fieldset class="form-signincus">
                            <div class="form-rowcus col-md-12">
                                <div class="form-groupc col-md-5">
                                    <label for="name" class="fs-5 fw-semibold">Nombres Completos</label>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Nombres Completos"
                                        required value="<?php if($error) echo $nombre?>" class="label" />
                                    <span class="text-danger"><?php if (isset($nombre_error)) echo $nombre_error; ?></span>
                                </div>
                                <div class="form-groupc col-md-4">
                                    <label for="filter" class="fs-5 fw-semibold">Tipo de documento</label>
                                    <select name="filter" id="filter" class="form-control" required>
                                        <option class="form-control" selected readonly value="0"> Seleccione ... </option>
                                        <?php $filter = (isset($_GET['filter']) ? strtolower($_GET['filter']) : NULL);  ?>
                                        <option class="form-control" valor="1" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>
                                            C.C</option>
                                        <option class="form-control" valor="2" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>
                                            NIT</option>
                                        <option class="form-control" valor="3" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>
                                            C.E</option>
                                        <option class="form-control" valor="4" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>
                                            PASAPORTE</option>
                                        <option class="form-control" valor="5" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>
                                            CARNET DIPLOMATICO</option>
                                    </select>
                                </div>
                                <div class="form-groupc col-md-3">
                                    <label for="doc" class="fs-5 fw-semibold">Nro. Documento</label>
                                    <input type="text" name="doc" class="form-control" oninput="validarNumero(event)"
                                        onkeydown="permitirSoloNumeros(event)" placeholder="Nro. Documento" required
                                        value="<?php if($error) echo $doc?>" class="label" />
                                    <span class="text-danger"><?php if (isset($doc_error)) echo $doc_error; ?></span>
                                </div>
                            </div>
                            <div class="form-rowcus col-md-12">
                                <div class="form-groupc col-md-4">
                                    <label for="fecnace" class="fs-5 fw-semibold">Fecha de Nacimiento</label>
                                    <input type="date" name="fecnace" class="form-control" placeholder="Fecha de Nacimiento"
                                        required value="<?php if($error) echo $fecnace?>" class="input" />
                                    <span class="text-danger"><?php if (isset($fecnace_error)) echo $fecnace_error; ?></span>
                                </div>
                                <div class="form-groupc col-md-6">
                                    <label for="direcc" class="fs-5 fw-semibold">Dirección</label>
                                    <input type="text" name="direcc" class="form-control" placeholder="Dirección" required
                                        value="<?php if($error) echo $direcc?>" class="label" />
                                    <span class="text-danger"><?php if (isset($direcc_error)) echo $direcc_error; ?></span>
                                </div>
                            </div>
                            <div class="form-rowcus col-md-12">
                                <div class="form-groupc col-md-3">
                                    <label for="telefono" class="fs-5 fw-semibold">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control" placeholder="Teléfono"
                                        oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)" required
                                        value="<?php if($error) echo $telefono?>" class="label" />
                                    <span class="text-danger"><?php if (isset($telefono_error)) echo $telefono_error; ?></span>
                                </div>
                                <div class="form-groupc col-md-6">
                                    <label for="correo" class="fs-5 fw-semibold">E-mail</label>
                                    <input type="email" name="correo" class="form-control" placeholder="Correo Electrónico" required
                                        value="<?php if($error) echo $correo ?>" class="label" />
                                    <span class="text-danger"><?php if (isset($correo_error)) echo $correo_error; ?></span>
                                </div>
                                <div class="form-groupc col-md-3">
                                    <label for="movil" class="fs-5 fw-semibold">Celular</label>
                                    <input type="text" name="movil" class="form-control" placeholder="Celular"
                                        oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)" required
                                        value="<?php if($error) echo $movil?>" class="label" />
                                    <span class="text-danger"><?php if (isset($movil_error)) echo $movil_error; ?></span>
                                </div>
                            </div>
                        </fieldset><br><br>
                        <div class="dVolver ms-auto">
                            <button title="Guardar" name="accion" value="actualizar" id="accion" formmethod="post" type="submit"
                                class="btn btn-info">
                                <i class="fas fa-save"></i>Guardar</button>
                        </div>
                        <center><br>
                            <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                        </center>
                    </form>
                    </div>
                    <hr>
                    <span class="text-success"><?php if (isset($successmsg)) {
                                                        echo $successmsg;
                                                    } ?></span>
                    <span class="text-danger"><?php if (isset($errormsg)) {
                                                        echo $errormsg;
                                                    } ?></span>
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
    // FUNCIONES DE VISIBILIDAD SIMILARES A expiration.php
    document.addEventListener('DOMContentLoaded', function() {
        // Ocultar todos los formularios excepto el mensaje inicial
        document.querySelectorAll('.scrollable-active > section, .scrollable-active > form').forEach(
        element => {
            if (element.id !== 'mensaje') {
                element.style.display = 'none';
            }
        });

        // Mostrar menú y mensaje inicial
        var menu = document.getElementById('menu');
        var registro = document.getElementById('mensaje');

        if (menu) menu.style.display = 'block';
        if (registro) registro.style.display = 'block';
    });

    function hideAllForms() {
        document.querySelectorAll('.scrollable-active > section, .scrollable-active > form').forEach(section => {
            if (section) section.style.display = 'none';
        });
    }

    function showForm(formId) {
        hideAllForms();
        var el = document.getElementById(formId);
        if (el) el.style.display = 'block';
    }

    function showMenu(formId) {
        hideAllForms();
        var el = document.getElementById(formId);
        if (el) el.style.display = 'block';
    }

    function cancelarform(formId) {
        document.getElementById(formId).style.display = 'none';
        document.getElementById("mensaje").style.display = 'block';
    }

    // Funciones para mostrar formularios (definidas en products.php)
    function showManage(formId) {
        hideAllForms();
        document.getElementById(formId).style.display = 'block';
    }

    function showRegister(formId) {
        hideAllForms();
        document.getElementById(formId).style.display = 'block';
    }

    function showUpdate(formId) {
        var menu = document.getElementById('menu');
        document.querySelectorAll('section').forEach(section => section.style.display = 'none');
        // Mostrar el formulario requerido
        document.getElementById(formId).style.display = 'block';
        document.getElementById("register").style.display = 'none';
        document.getElementById("delete").style.display = 'none';
        menu.style.display = 'block';
    }

    function showDelete(formId) {
        var menu = document.getElementById('menu');
        document.querySelectorAll('section').forEach(section => section.style.display = 'none');
        // Mostrar el formulario requerido
        document.getElementById(formId).style.display = 'block';
        document.getElementById("register").style.display = 'none';
        document.getElementById("update").style.display = 'none';
        menu.style.display = 'block';
    }

         // Evento de busqueda
        document.addEventListener("DOMContentLoaded", function () {
            var input = document.getElementById("searchInputseg");
            if (input) {
                input.addEventListener("input", function () {
                    var filter = input.value.toUpperCase();
                    var table = document.getElementById("movtable");
                    if (table) {
                        var rows = table.getElementsByTagName("tr");

                        for (var i = 0; i < rows.length; i++) {
                            var cells = rows[i].getElementsByTagName("td")[1];
                            var cells1 = rows[i].getElementsByTagName("td")[2];
                            if (cells) {
                                var txtValue1 = cells.textContent || cells.innerText;
                                var txtValue2 = cells1.textContent || cells1.innerText;

                                if (txtValue1.toUpperCase().indexOf(filter) > -1 || txtValue2.toUpperCase().indexOf(filter) > -1) {
                                    rows[i].style.display = "";
                                } else {
                                    rows[i].style.display = "none";
                                }
                            }
                        }
                    }
                });
            }
        });

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

                // Detectar números
                const n1 = parseFloat(v1.replace(/,/g, ""));
                const n2 = parseFloat(v2.replace(/,/g, ""));
                const numCheck = !isNaN(n1) && !isNaN(n2);

                // Detectar fechas
                const d1 = Date.parse(v1);
                const d2 = Date.parse(v2);
                const isDate = !isNaN(d1) && !isNaN(d2);

                if (numCheck) return n1 - n2;         // Comparar números
                if (isDate) return d1 - d2;           // Comparar fechas
                return v1.localeCompare(v2);          // Comparar texto
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