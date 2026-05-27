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
require_once("php/dbconnect.php");
require_once("php/SED.php");
require_once("php/cockies.php");
require_once("php/userstate.php");
require_once("php/userlist.php");
require_once("php/mproducts.php"); // <- AQUI IRA EL MENÚ NUEVO

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

// Variables para consultas
$resultSeguros = [];
$resultAgencias = [];
$regagency = 0;

try {
    // Consulta 1: Seguros - CONVERTIDO A PDO
    $sqlSeguros = "SELECT * FROM tb_seguro ORDER BY idtb_seguro, id_agencia ASC";
    $stmt1 = $con->prepare($sqlSeguros);
    
    if (!$stmt1->execute()) {
        throw new PDOException("Error ejecutando consulta de seguros");
    }
    
    // Obtener resultados
    $resultSeguros = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    $regagency = count($resultSeguros);
    
    // Consulta 2: Agencias - CONVERTIDO A PDO
    $sqlAgencias = "SELECT nro_doc, nombre FROM tb_agencia";
    $stmt2 = $con->prepare($sqlAgencias);
    
    if (!$stmt2->execute()) {
        throw new PDOException("Error ejecutando consulta de agencias");
    }
    
    $resultAgencias = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    $errormsg = "Error en la consulta. Por favor, intente más tarde.";
}

// Consulta para la tabla de gestión - CONVERTIDO A PDO
$sqlSelect = "";
$stmtResult = null;
try {
    $sqlSelect = "SELECT s.*, a.nombre FROM tb_seguro s 
                  INNER JOIN tb_agencia a ON s.id_agencia = a.nro_doc  
                  ORDER BY idtb_seguro, id_agencia ASC";
    $stmtResult = $con->prepare($sqlSelect);
    $stmtResult->execute();
} catch (PDOException $e) {
    error_log("Error en consulta de gestión: " . $e->getMessage());
}

//Establece el error de validación como flag
$error = true;
$seguro = '';
$descri = '';
$id_agencia = "";
$agent1 = "";
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
    
    /* ESTILO ADICIONAL PARA EL MENÚ */
    .scrollable-active {
        margin-left: 280px; /* Espacio para el menú lateral */
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
        <?php pintarMenu($perfil, $username); ?>  <!-- llamada a la función del nuevo menu -->

        <!-- ====================== CONTENIDO PRINCIPAL ====================== -->
        <div class="scrollable-active">
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
                            <p class="main-subtitle-minimal">A la sección Gestión de Productos.</p>
                        </div>
                    </center>
                </fieldset>
            </section>

            <!--Formulario de Gestión de seguro-->
            <form id="manage" class="" role="form" action="php/importexseg.php" enctype="multipart/form-data" method="POST" name="manage" style="display: none;">
                <fieldset class="search">
                    <center>
                         <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="main-title-modern">Gestión de Seguros.</h1>
                        </legend>
                    </center>
                </fieldset>
                <br>
                <fieldset class="">
                    <div class="form-rowag col-md-12">
                        <?php
                        if (empty($resultSeguros) || $regagency == 0) {
                            echo '<script class="containertbl form-signin m-auto form-signin"> alert("No hay Seguros registrados.");</script>';
                            echo '<div class="containertbl form-signin m-auto form-signin">';
                            echo '<div class="container">';
                            echo '<h3>Cargar e importar archivo excel con formato <strong>CSV</strong>.</h3>';
                            echo '<form name="importa" method="post" action="php/importexseg.php" enctype="multipart/form-data">';
                            echo '<div class="col-xs-4">';
                            echo '<div class="form-groupr">';
                            echo '<input id="flie" type="file" accept=".csv" class="btn btn-info dVolver ms-auto" data-buttonText="Seleccione archivo" name="file">';
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="dVolver ms-auto">';
                            echo '<input type="submit" id="submit" name="import" class="btn btn-info" value="Importarlo"  />';
                            echo '</div>';
                            echo '</form>';
                            echo '</div>';
                            echo '</div>';
                            echo '<div id="response" class="' . (isset($type) ? $type . " display-block" : "") . '">' . (isset($message) ? $message : "") . '</div>';
                        } else { ?>
                            <?php if (isset($_GET['mensaje'])): ?>
                                <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
                            <?php endif; ?>

                            <?php if ($stmtResult && $stmtResult->rowCount() > 0) { ?>
                                <center>
                                    <div class="search">
                                        <label for="find">
                                            <p class="main-subtitle-corporate"><strong>Filtrar por Agencia.</strong></p>
                                        </label>
                                        <input type="text" class="form-row1" id="searchInputseg" placeholder="Buscar" required
                                            value=" " class="form-control">
                                        <span class="text-danger"></span>
                                    </div>
                                </center>
                                <div class="table-scroll-container">
                                    <table class="tablesprod" id="movtable">
                                        <thead>
                                            <tr style="color:#243587; background:#fff8dc">
                                                <th class="th-sortable">ID.</th>
                                                <th class="th-sortable">Agencia.</th>
                                                <th class="th-sortable">Seguro.</th>
                                                <th class="th-sortable">Descripción.</th>
                                                <th class="th-sortable">Estado.</th>
                                                <th class="">Acciones.</th>
                                                <th class="">Seguros.</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodymov">
                                            <?php
                                            while ($row = $stmtResult->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<tr>';
                                                echo '<td style="color:#456789;">'.$row['idtb_seguro'].'</td>';
                                                echo '<td style="color:#456789;">' . $row['nombre'] . '</td>';
                                                echo '<td><strong>'.$row['seguro'].'</strong></td>';
                                                echo '<td>'.$row['descri'].'</td>';
                                                echo '<td>';
                                                if ($row['estado'] == '1') {
                                                    echo '<span class="label label-success">Activo</span>';
                                                } else if ($row['estado'] == '2') {
                                                    echo '<span class="label label-info">Inactivo</span>';
                                                }?>
                                                </td>
                                                <td class="">
                                                <a href="php/activeseguro.php?nik=<?php echo htmlspecialchars($row['idtb_seguro']); ?>" title="Activar/Desactivar"
                                                    class="btn btn-primaryfechas">
                                                <span><svg class="" width="15px" height="12px" role="img" aria-label="Products">
                                                <use xlink:href="#check2"/></svg></span></a>
                                                <a href="#" title="Agregar Seguro" onclick="showRegister(\'register\');"
                                                class="btn btn-successfechas"><span>
                                                <svg class="" width="15px" height="12px" role="img" aria-label="Products">
                                                <use xlink:href="#pen-to-square" /></svg></span></a>
                                                <a href="php/deleteproducts.php?aksi=delete&nik='.$row['idtb_seguro'].'" title="Eliminar"
                                                    onclick="" class="btn btn-dangerfechas"><span>
                                                <svg class="" width="15px" height="12px" role="img" aria-label="Products">
                                                <use xlink:href="#eye" /></svg></span></a>
                                                </td>
                                                <td><a href="typeproducts.php?aksi=filtro&nik='.$row['idtb_seguro'].'" title="Tipos de Seguros"
                                                    class="btn-submit"><span><svg class="" width="25px" height="20px" role="img" aria-label="Products">
                                                <use xlink:href="#share" /></svg></span></a></td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </fieldset>
                <br>
                <center><br>
                    <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                </center>
            </form>

            <!-- Formulario de registrar datos de seguros-->
            <form id="register" class="" role="form" action="php/registerproducts.php" enctype="multipart/form-data" method="POST" name="register" style="display: none;">
                <fieldset class="">
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="lianseguros" width="60%" height="35%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="fs-2 fw-semibold">Agregar Seguros.</h1>
                        </legend>
                    </center>
                </fieldset>
                <fieldset>
                    <div class="modal-content">
                        <div class="form-groupc col-md-4">
                            <p><label for="nro_doc" class="fs-5 fw-semibold">NIT.</label></p>
                            <span class="text-danger"><?php if (isset($id_agencia_error)) echo $id_agencia_error; ?></span>
                            <select class="form-control custom-select" name="agencia" id="agencia" onchange="InputAgencia(this)">
                                <option class="form-control" value="0"> Seleccione ... </option>
                                <?php
                                if (!empty($resultAgencias)) {
                                    foreach ($resultAgencias as $row) {
                                        echo "<option value='" . $row["nro_doc"] . "'>" . $row["nro_doc"] . " - " . $row["nombre"] . "</option>";
                                    }
                                } else {
                                    echo "<option>No hay agencias disponibles</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-groupc col-md-3">
                            <p><label for="nomagencia" class="fs-5 fw-semibold">Agencia.</label></p>
                            <span class="text-danger"><?php if (isset($agencia_error)) echo $agencia_error; ?></span>
                            <!-- Campo oculto para almacenar el ID de la agencia seleccionada -->
                            <input type="hidden" id="agenciaIdHidden" name="agencia_id">
                            <input type="text" name="nomagencia" id="nomagencia" class="form-control" placeholder="Agencia." readonly
                                value="<?php if ($error) echo $agent1 ?>" />
                        </div>
                    </div>

                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md-4">
                            <label for="seguro" class="fs-5 fw-semibold">Seguro</label>
                            <span class="text-danger"><?php if (isset($seguro_error)) echo $seguro_error; ?></span>
                            <input type="text" name="seguro" id="seguro" class="form-control" placeholder="Seguro" required
                                value="<?php if ($error) echo $seguro ?>" />
                        </div>
                        <div class="form-groupc col-md-7">
                            <label for="descri" class="fs-5 fw-semibold">Descripción</label>
                            <span class="text-danger"><?php if (isset($descri_error)) echo $descri_error; ?></span>
                            <textarea name="descri" id="descri" rows="2" cols="30" class="form-control1"><?php if ($error) echo $descri ?></textarea>
                        </div>
                    </div>
                </fieldset>
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
        // FUNCIONES DE VISIBILIDAD SIMILARES A expiration.php
        document.addEventListener('DOMContentLoaded', function() {
            // Ocultar todos los formularios excepto el mensaje inicial
            document.querySelectorAll('.scrollable-active > section, .scrollable-active > form').forEach(element => {
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
            var el = document.getElementById(formId);
            if (el) el.style.display = 'none';
            var msg = document.getElementById("mensaje");
            if (msg) msg.style.display = 'block';
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

        let agenciaId = "";
        function InputAgencia(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            agenciaId = selectedOption.value;
            const selectedText = selectElement.options[selectElement.selectedIndex].text.split(' - ')[1];
            document.getElementById('nomagencia').value = selectedText || '';
            document.getElementById('agenciaIdHidden').value = agenciaId;
        }

        // Funciones específicas para el menú de products
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