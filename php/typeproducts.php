<?php
// products.php
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
require_once("dbconnect.php");
require_once("SED.php");
require_once("cockies.php");
require_once("userstate.php");
require_once("userlist.php");
require_once("mtproducts.php"); // <- AQUI IRA EL MENÚ NUEVO

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

if (isset($_GET['aksi']) && $_GET['aksi'] == 'filtro') {
    $nik = isset($_GET["nik"]) ? $_GET["nik"] : '';
    // Preparar la consulta con parámetro PDO
    $stmt = $con->prepare("SELECT ts.*, a.nombre, s.seguro  
                          FROM tb_tposeguro ts 
                          INNER JOIN tb_agencia a ON ts.id_agencia = a.nro_doc 
                          INNER JOIN tb_seguro s ON ts.idtb_seguro = s.idtb_seguro  
                          WHERE ts.idtb_seguro = :nik 
                          ORDER BY idtb_seguro ASC");
    $stmt->bindParam(':nik', $nik, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $regagency = count($result);
    $style = "display: block;";
} else {
    $nik = "";
    // Consulta sin filtro usando PDO
    $stmt = $con->prepare("SELECT ts.*, a.nombre, s.seguro  
                          FROM tb_tposeguro ts 
                          INNER JOIN tb_agencia a ON ts.id_agencia = a.nro_doc 
                          INNER JOIN tb_seguro s ON ts.idtb_seguro = s.idtb_seguro  
                          ORDER BY idtb_seguro ASC");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $regagency = count($result);
    $style = "display: block;";
}

// Establece el error de validación como flag
$error = true;
$seguro = '';
$tpseguro = '';
$tpdescri = '';
$id_agencia = "";
$agent1 = "";
$id_secure = "";
$descriseg = "";

// Consulta de agencias en PDO
$sql = "SELECT nro_doc, nombre FROM tb_agencia";
$stmtag = $con->prepare($sql);
$stmtag->execute();
$resultag = $stmtag->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="scrollable-active">
            <section id="mensaje" style="display: none; margin: inherit;">
                <center>
                    <img class="mb-4 mx-auto" src="images/logo.png" alt="lianseguros" width="75%" height="45%">
                </center><br>
                <fieldset>
                    <center>
                        <h2 class="fs-12 fw-semibold">Sistema de Vencimientos de Seguros - SIVES -</h2>
                    </center>
                    <center>
                        <div class="content">
                            <h1>Bienvenido</h1>
                            <p>A la sección Gestión de Tipos de Seguros.</p>
                        </div>
                    </center>
                </fieldset>
            </section>

            <!--Formulario de registro de seguro-->
            <form id="FrmFilter" class="" role="form" action="php/importtposecure.php" enctype="multipart/form-data"
                method="POST" name="signupform" style="<?php echo $style; ?>">
                <fieldset class="search">
                    <center>
                         <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="main-title-modern">Gestión de Tipo de Seguros.</h1>
                        </legend>
                    </center>
                </fieldset>
                <br>
                <fieldset class="">
                    <div class="form-rowag col-md-12">
                        <?php
                                if (!$result || $regagency == 0) {
                                    echo "<script class='containertbl form-signin m-auto form-signin'>
                                    alert('No hay Tipos de Seguros registrados con la Agencia seleccionada.');window.location= '/typeproducts.php'</script>";
                                } else { ?>
                        <?php if (isset($_GET['mensaje'])): ?>
                        <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
                        <?php endif;
                                    if(empty($nik)){
                                        // CONSULTA PDO - Reemplazado mysqli_query
                                        $stmt = $con->prepare("SELECT ts.*, a.nombre, s.seguro FROM tb_tposeguro ts 
                                                INNER JOIN tb_agencia a ON ts.id_agencia = a.nro_doc 
                                                INNER JOIN tb_seguro s ON ts.idtb_seguro = s.idtb_seguro 
                                                ORDER BY idtb_seguro ASC");
                                        $stmt->execute();
                                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    }else{
                                        // CONSULTA PDO - Reemplazado mysqli_query
                                        $stmt = $con->prepare("SELECT ts.*, a.nombre, s.seguro FROM tb_tposeguro ts 
                                                INNER JOIN tb_agencia a ON ts.id_agencia = a.nro_doc 
                                                INNER JOIN tb_seguro s ON ts.idtb_seguro = s.idtb_seguro  
                                                WHERE ts.idtb_seguro = :nik ORDER BY idtb_seguro ASC");
                                        $stmt->bindParam(':nik', $nik, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    }
                                    
                                    if ($result && count($result) > 0) { ?>
                                        <div class="search">
                                            <label for="find">
                                                <p class="main-subtitle-corporate"><strong>Filtrar por Agencia.</strong></p>
                                            </label>
                                            <input type="text" class="form-row1" id="searchInputseg" placeholder="Buscar" required
                                                value=" " class="form-control">
                                            <span class="text-danger"></span>
                                        </div>
                                        <div class="table-scroll-container">
                                            <table class="tablesprod" id="movtable">
                                                <thead>
                                                    <tr style="color:#243587; background:#fff8dc">
                                                        <th class="th-sortable">ID.</th>
                                                        <th class="th-sortable">Agencia.</th>
                                                        <th class="th-sortable">Seguro.</th>
                                                        <th class="th-sortable">Tipo de Seguro.</th>
                                                        <th class="th-sortable">Descripción.</th>
                                                        <th class="th-sortable">Estado.</th>
                                                        <th class="">Acciones.</th>
                                                        <th class="">Seguros.</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodymov">
                                                    <?php
                                                    foreach ($result as $row) {
                                                        echo '<tr>';
                                                        echo '<td style="color:#456789;">'.$row['idtb_tposeguro'].'</td>
                                                            <td style="color:#456789;">' . $row['nombre'] . '</td>
                                                            <td style="color:#456789;">' . $row['seguro'] . '</td>
                                                            <td><strong>'.$row['tposeguro'].'</strong></td>
                                                            <td>'.$row['descr'].'</td>
                                                            <td>';
                                                        if ($row['estado'] == '1') {
                                                            echo '<span class="label label-success">Activo</span>';
                                                        } else if ($row['estado'] == '2') {
                                                            echo '<span class="label label-info">Inactivo</span>';
                                                        }
                                                        echo '</td>
                                                            <td class="">
                                                            <a href="php/activetposeguro.php?nik='.$row['idtb_tposeguro'].'" title="Activar/Desactivar"
                                                            class="btn btn-primaryfechas">
                                                            <span><svg class="" width="15px" height="12px" role="img" aria-label="Products">
                                                            <use xlink:href="#check2" /></svg></span></a>
                                                            <a href="#" title="Agregar Tipo de Seguro" onclick="showRegister(\'register\');"
                                                            class="btn btn-successfechas">
                                                            <span><svg class="" width="15px" height="12px" role="img" aria-label="Products">
                                                            <use xlink:href="#pen-to-square" /></svg></span></a>
                                                            <a href="php/deltypeproducts.php?aksi=delete&nik='.$row['idtb_tposeguro'].'" title="Eliminar"
                                                            onclick=""class="btn btn-dangerfechas"><span>
                                                            <svg class="" width="15px" height="12px" role="img" aria-label="Products">
                                                            <use xlink:href="#eye" /></svg></span></a>
                                                            </td>
                                                            <td><a href="products.php" title="Seguros"
                                                            class="btn-submit"><span><svg class="" width="25px" height="20px" role="img" aria-label="Products">
                                                            <use xlink:href="#share" /></svg></span></a></td>
                                                            </tr>';
                                                    }?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                            <br>
                            <?php } ?>
                    </div>
                </fieldset>
                <br>
                <center><br>
                    <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                </center>
            </form>

            <!-- Formulario de registrar datos de seguros-->
            <form id="register" class="" role="form" action="php/registertypeproducts.php" enctype="multipart/form-data"
                method="POST" name="register" style="display: none;">
                <fieldset class="">
                    <center>
                        <img class="" src="images/logo.png" alt="lianseguros" width="60%" height="35%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="fs-2 fw-semibold">Agregar Tipo de Seguros.</h1>
                        </legend>
                    </center>
                </fieldset>
                <fieldset>
                    <div class="form-rowcus col-md-12">
                        <div class="form-rowc col-md-4">
                            <p><label for="nro_doc" class="fs-5 fw-semibold">NIT.</label></p>
                            <span
                                class="text-danger"><?php if (isset($id_agencia_error)) echo $id_agencia_error; ?></span>
                            <select class="form-control custom-select" name="agencia" id="agencia"
                                onchange="InputAgencia(this);cargarSeguros(this)" readonly>
                                <option class="form-control" selected readonly value="0"> Seleccione ... </option>
                                <?php
                                        if (count($resultag) > 0) {
                                            // Salida de datos para cada fila usando PDO
                                            foreach($resultag as $row) {
                                                echo "<option value='" . $row["nro_doc"] . "'>" . $row["nro_doc"] . " - " . $row["nombre"] . "</option>";
                                            }
                                        } else {
                                            echo "<option>No hay agencias disponibles</option>";
                                        }
                                    ?>
                            </select>
                        </div>
                        <div class="form-rowc col-md-3">
                            <p><label for="nomagencia" class="fs-5 fw-semibold">Agencia.</label></p>
                            <span class="text-danger"><?php if (isset($agencia_error)) echo $agencia_error; ?></span>
                            <!-- Campo oculto para almacenar el ID de la agencia seleccionada -->
                            <input type="hidden" id="agenciaIdHidden" name="agencia_id">
                            <input type="text" name="nomagencia" id="nomagencia" class="form-control"
                                placeholder="Agencia." readonly value="<?php if ($error) echo $agent1 ?>"
                                class="label" />
                        </div>
                    </div>

                    <div class="form-rowcus col-md-12">
                        <div class="form-rowc col-md-2">
                            <label for="seguro" class="fs-5 fw-semibold">Id Seguro</label>
                            <span class="text-danger"><?php if (isset($seguro_error)) echo $seguro_error; ?></span>
                            <select class="form-control custom-select" id="seguros" name="seguros"
                                onchange="InputSeguro(this)">
                                <option class="form-control" selected readonly value=""> Seleccione un seguro</option>
                            </select>
                        </div>
                        <div class="form-rowc col-md-5">
                            <p><label for="nomseguro" class="fs-5 fw-semibold">Seguro.</label></p>
                            <span class="text-danger"><?php if (isset($seguro_error)) echo $seguro_error; ?></span>
                            <!-- Campo oculto para almacenar el ID del seguro seleccionada -->
                            <input type="hidden" id="seguroIdHidden" name="seguro_id">
                            <input type="text" name="nomseguro" id="nomseguro" class="form-control"
                                placeholder="Seguro." readonly value="<?php if ($error) echo $seguro ?>"
                                class="label" />
                        </div>
                    </div>

                    <div class="form-rowcus col-md-12">
                        <div class="form-rowc col-md-4">
                            <label for="tpseguro" class="fs-5 fw-semibold">Tipo Seguro</label>
                            <span class="text-danger"><?php if (isset($tpseguro_error)) echo $tpseguro_error; ?></span>
                            <input type="text" name="tpseguro" id="tpseguro" class="form-control"
                                placeholder="Tipo de Seguro" required value="<?php if ($error) echo $tpseguro ?>"
                                class="label" />
                        </div>
                        <div class="form-groupc col-md-5">
                            <label for="descri" class="fs-5 fw-semibold">Descripción</label>
                            <span class="text-danger"><?php if (isset($descri_error)) echo $descri_error; ?></span>
                            <textarea name="descri" id="descri" rows="2" cols="30"
                                value="<?php if ($error) echo $descri ?>" class="form-control1"></textarea>
                            <!--<input type="text" name="descri" placeholder="Descripción" required
                                value="<?php if ($error) echo $descri ?>" class="form-control" />-->
                        </div>
                    </div>
                </fieldset>
                <?php
                ?>
                <br><br>
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

    <script src="/JS/bootstrap.bundle.min.js"></script>
    <script src="/JS/sidebars.js"></script>
    <script>
    src = "/JS/bootstrap.min.js"
    </script>
    <script>
    src = "/JS/jquery-1.10.2.js"
    </script>
    <script src="/JS/color-modes.js"></script>

    <script>
    function showForm(formId) {
        // Ocultar todos los formularios
        document.querySelectorAll('form').forEach(form => form.style.display = 'none');
        document.querySelectorAll('section').forEach(section => section.style.display = 'none');
        // Mostrar el formulario requerido
        document.getElementById(formId).style.display = 'block';
    }

    function showMenu(formId) {
        // Ocultar todos los formularios
        document.querySelectorAll('form').forEach(form => form.style.display = 'none');
        // Mostrar el formulario requerido
        document.getElementById(formId).style.display = 'block';
        document.querySelectorAll('section').forEach(section => section.style.display = 'block');
    }

    function cancelarform(formId) {
        document.getElementById(formId).style.display = 'none';
        document.getElementById("mensaje").style.display = 'block';
    }

    function showManage(formId) {
        // Obtener la URL actual
        let url = new URL(window.location.href);
        // Obtener los parámetros de búsqueda
        let params = new URLSearchParams(url.search);
        // Eliminar los filtros específicos
        params.delete('aksi'); // Elimina el parámetro 'category'
        params.delete('nik'); // Elimina el parámetro 'price_range'
        // Construir la nueva URL sin los filtros
        let newUrl = url.origin + url.pathname;
        if (params.toString()) {
            newUrl += '?' + params.toString(); // Agregar los parámetros restantes si hay
        }
        // Redirigir a la nueva URL
        window.location.href = newUrl;

        var menu = document.getElementById('menu');
        document.querySelectorAll('section').forEach(section => section.style.display = 'block');
        document.getElementById("mensaje").style.display = 'none';
        // Mostrar el formulario requerid 
        document.getElementById(formId).style.display = 'block';
        document.getElementById("register").style.display = 'none';
        menu.style.display = 'block';
    }

    function showRegister(formId) {
        var menu = document.getElementById('menu');
        document.querySelectorAll('section').forEach(section => section.style.display = 'block');
        document.getElementById('mensaje').style.display = 'none';
        document.getElementById('FrmFilter').style.display = "none";
        // Mostrar el formulario requerido
        document.getElementById(formId).style.display = 'block';
        menu.style.display = 'block';
    }

    function showUpdate(formId) {
        function mostrarAlerta(mensaje) {
            alert(mensaje);
        }
        var menu = document.getElementById("menu");
        document.querySelectorAll("section").forEach(section => section.style.display = 'none');
        // Mostrar el formulario requerido
        document.getElementById(formId).style.display = 'block';
        document.getElementById("register").style.display = 'none';
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
                            if (cells) {
                                var txtValue1 = cells.textContent || cells.innerText;

                                if (txtValue1.toUpperCase().indexOf(filter) > -1) {
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

    let agenciaId = ""; // Variable para almacenar el ID seleccionado
    function InputAgencia(selectElement) {
        // Obtener la opción seleccionada
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        // Guardar el ID seleccionado en la variable
        agenciaId = selectedOption.value;
        // Obtener el texto seleccionado (nombre de la agencia)
        const selectedText = selectElement.options[selectElement.selectedIndex].text.split(' - ')[1];
        // Actualizar el valor del input con el nombre de la agencia
        document.getElementById("nomagencia").value = selectedText;
        // Actualizar el texto de la opción seleccionada para que muestre solo el ID
        selectedOption.text = selectedOption.value;
        // Deshabilitar el select para que no se pueda modificar
        selectElement.disabled = true;
        // Guardar el ID en un campo oculto para enviar en el formulario obtenerSeguros
        document.getElementById("agenciaIdHidden").value = agenciaId;
    }

    function cargarSeguros(selectElement) {
        var idAgencia = document.getElementById("agencia").value;
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "PHP/get_seguros.php?id_agencia=" + idAgencia, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById("seguros").innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    let seguroId = ""; // Variable para almacenar el ID seleccionado
    function InputSeguro(selectElement) {
        // Obtener la opción seleccionada
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        // Guardar el ID seleccionado en la variable
        seguroId = selectedOption.value;
        // Obtener el texto seleccionado (nombre de la agencia)
        const selectedText = selectElement.options[selectElement.selectedIndex].text.split(' - ')[1];
        // Actualizar el valor del input con el nombre de la agencia
        document.getElementById("nomseguro").value = selectedText;
        // Actualizar el texto de la opción seleccionada para que muestre solo el ID
        selectedOption.text = selectedOption.value;
        // Deshabilitar el select para que no se pueda modificar
        selectElement.disabled = true;
        // Guardar el ID en un campo oculto para enviar en el formulario obtenerSeguros
        document.getElementById("seguroIdHidden").value = seguroId;
    }

    /* =============================================================
        ORDENAMIENTO DE TABLAS POR COLUMNA
        Funciona con clases: th-sortable, th-asc, th-desc
        ============================================================= */

    document.addEventListener("DOMContentLoaded", function() {
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

            if (numCheck) return n1 - n2; // Comparar números
            if (isDate) return d1 - d2; // Comparar fechas
            return v1.localeCompare(v2); // Comparar texto
        };

        document.querySelectorAll(".th-sortable").forEach(th => {
            th.addEventListener("click", function() {
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

                // Ordenar filas - IMPORTANTE: Capturar TODOS los tr de TODOS los tbody
                const rows = Array.from(table.querySelectorAll("tbody tr"))
                    .sort(compare(index, asc));

                // Limpiar y reinsertar TODOS los tbody con las filas ordenadas
                table.querySelectorAll("tbody").forEach(tb => tb.remove());

                // Crear un nuevo tbody único
                const newTbody = document.createElement("tbody");
                rows.forEach(row => newTbody.appendChild(row));
                table.appendChild(newTbody);
            });
        });
    });
    </script>
</body>

</html>