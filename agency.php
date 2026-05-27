<?php
// agency.php vv
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
require_once("php/magency.php"); // <- AQUI IRA EL MENÚ NUEVO

$logo = "";
$icono = "";

// --- CONSULTA PRINCIPAL ---
$sqlSelect = "SELECT * FROM tb_agencia ORDER BY id_agencia ASC";
$stmta = $con->prepare($sqlSelect);
$stmta->execute();
$resulta = $stmta->fetchAll(PDO::FETCH_ASSOC); // array con los datos

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

    <!-- Custom styles for this template -->
    <link href="css/sidebars.css" rel="stylesheet">
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

            <form id="manage" name="manage" role="form" action="" method="POST" enctype="multipart/form-data" style="display: none;">
                <fieldset>
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="main-subtitle-corporate">Gestión de Agencias.</h1>
                        </legend>
                    </center>
                </fieldset>
                <br>

                <?php                     
                if(empty($resulta)){?>
                    <script class="containertbl form-signin m-auto form-signin"> alert("No hay Agencias de Seguros registradas.");</script>
                    <div class="containertbl form-signin m-auto form-signin">
                    <div class="container">
                    <h3>Cargar e importar archivo excel con formato <strong>CSV</strong>.</h3>
                    <form name="importa" method="post" action="php/importagency.php" enctype="multipart/form-data">
                    <div class="col-xs-4">
                    <div class="form-groupr">
                    <input id="flie" type="file" accept=".csv" class="btn btn-info dVolver ms-auto" data-buttonText="Seleccione archivo" name="file">
                    </div>
                    </div>
                    <div class="dVolver ms-auto">
                    <input type="submit" id="submit" name="import" class="btn btn-info" value="Importarlo" />
                    </div>
                    </form>
                    </div>
                    </div>
                    <div id="response" class="<?php echo !empty($type) ? $type . " display-block" : ""; ?>"><?php echo !empty($message) ? $message : ""; ?></div>
                <?php } else {?>
        <center>
                <div class="form-rowag col-md-12">
                    <div class="form-rowag">
                        <!--//SI HAY REGISTROS  table table-striped table-hover-->
                        <?php
                        if (isset($_GET['mensaje'])) {
                            echo "<p>" . htmlspecialchars($_GET['mensaje']) . "</p>";
                        }
                        ?>
                        <table class="tablesprod" id="movtable">
                            <thead>
                                <tr style="color:#243587; background:#fff8dc">
                                    <th class="th-sortable">NIT.</th>
                                    <th class="th-sortable">Agencia</th>
                                    <th class="th-sortable">Contacto</th>
                                    <th class="th-sortable">Cargo</th>
                                    <th class="th-sortable">Nro. Fijo</th>
                                    <th class="th-sortable th-hide-mobile">Nro. Celular</th>
                                    <th class="th-sortable">E-mail</th>
                                    <th class="th-sortable">Estado</th>
                                    <th class="">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodymov">
                            <?php foreach ($resulta as $row) { ?>
                                <tr>
                                    <td style="color:#456789;"><?php echo $row['nro_doc']; ?></td>
                                    <td><a href="php/profileagen.php?nik=<?php echo $row['id_agencia']; ?>" title="Ver información">
                                        <span class="glyphicon glyphicon-user"></span> <?php echo $row['nombre']; ?>
                                        </a>
                                    </td>
                                    <td><strong><?php echo $row['contacto']; ?></strong></td>
                                    <td><?php echo $row['cargo']; ?></td>
                                    <td><?php echo $row['nro_fijo']; ?></td>
                                    <td><?php echo $row['nro_cel']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td>
                                        <?php
                                            if ($row['estado'] == '1') {
                                                echo '<span class="label label-success">Activo</span>';
                                            } else {
                                                echo '<span class="label label-info">Inactivo</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="col-estado">
                                        <a href="php/activeagency.php?nik=<?php echo $row['id_agencia']; ?>" title="Activar/Desactivar" class="btn btn-primaryfechas">
                                        <span><svg width="12px" height="12px"><use xlink:href="#check2" /></svg></span>
                                        </a>
                                        <a href="php/editagency.php?nik=<?php echo $row['id_agencia']; ?>" 
                                        onclick="return confirm('Esta seguro desea actualizar los datos de <?php echo $row['nombre']; ?>?')" 
                                        class="btn btn-successfechas" title="Actualizar agencia">
                                        <span><svg width="12px" height="12px"><use xlink:href="#pen-to-square" /></svg></span>
                                        </a>
                                        <a href="php/deleteagency.php?aksi=delete&nik=<?php echo $row['id_agencia']; ?>"
                                        onclick="return confirm('Esta seguro de borrar los datos <?php echo $row['nombre']; ?>?')" 
                                        class="btn btn-dangerfechas" title="Eliminar">
                                        <span><svg width="12px" height="12px"><use xlink:href="#eye" /></svg></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </center>
                <?php } ?>
            </form>

            <!-- Formulario de modificación de datos de la compañia-->
            <form id="register" name="register"  role="form" action="php/registeragency.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <fieldset>
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="main-subtitle-corporate">Agregar Agencia de Seguros.</h1>
                        </legend>
                    </center>
                </fieldset>
                <br>
                <fieldset>
                            <div class="form-rowc col-md-12">
                                <div class="form-groupc col-md-3">
                                    <p><label for="filter" class = "fs-5 fw-semibold" >Tipo de Documento.</label></p>
                                    <select name="filter" id="filter" class="form-control" required>
                                        <option class="form-control" selected readonly value="0" required> Seleccione ... </option>
                                        <?php $filter = (isset($_GET['filter']) ? strtolower($_GET['filter']) : NULL);  ?>
                                        <option class="form-control" valor="1" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>C.C</option>
                                        <option class="form-control" valor="2" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>NIT</option>
                                        <option class="form-control" valor="3" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>C.E</option>
                                        <option class="form-control" valor="4" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>PASAPORTE</option>
                                        <option class="form-control" valor="5" <?php if($filter == 'Tetap'){ echo 'selected'; } ?>>CARNET DIPLOMATICO</option>
                                    </select>
                                </div>
                                <div class="form-groupc col-md-3">
                                    <p><label for="nro_doc" class="fs-5 fw-semibold">Número de Documento.</label></p>
                                    <input type="text" name="nro_doc"  id="nro_doc" oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)" class="form-control" placeholder="Número de Documento" required>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-4">
                                    <p><label for="nombre" class="fs-5 fw-semibold">Agencia.</label></p>
                                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Agencia" required>
                                    <span class="text-danger"></span>
                                </div>
                            </div>

                            <div class="form-rowc col-md-12">
                                <div class="form-groupc col-md-4">
                                    <p><label for="contacto" class="fs-5 fw-semibold">Contacto.</label></p>
                                    <input type="text" name="contacto" id="contacto" class="form-control" placeholder="Contacto" required>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-2">
                                    <p><label for="cargo" class="fs-5 fw-semibold">Cargo.</label></p>
                                    <input type="text" name="cargo" id="cargo" class="form-control" placeholder="Cargo" required>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-2">
                                    <p><label for="nro_fijo" class="fs-5 fw-semibold">Número Fijo.</label></p>
                                    <input type="text" name="nro_fijo" id="cargo" oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)"  class="form-control" placeholder="Telefono" required>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupc col-md-2">
                                    <p><label for="nro_cel" class="fs-5 fw-semibold">Número Celular.</label></p>
                                    <input type="text" name="nro_cel" id="nro_cel" oninput="validarNumero(event)" onkeydown="permitirSoloNumeros(event)" class="form-control" placeholder="Número Celular" required>
                                    <span class="text-danger"></span>
                                </div>
                            </div>

                            <div class="form-rowc col-md-12">
                                <div class="form-groupc col-md-5">
                                    <p><label for="email" class="fs-5 fw-semibold">Email.</label></p>
                                    <input type="mail" name="email" id="email" class="form-control" placeholder="Email" 
                                    pattern="[a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*@[a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{1,5}" required>
                                    <span class="text-danger"></span>
                                </div>
                            </div>
                        </fieldset>
                <br><br>
                <div class="dVolver ms-auto">
                    <button title="Guardar" name="accion" value="actualizar" id="accion" formmethod="post" type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i>Guardar</button>
                </div>
                <center><br>
                    <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                </center>
            </form>
        </div>
        <hr>
            <span class="text-success"><?php if (isset($successmsg)) {echo $successmsg;} ?></span>
            <span class="text-danger"><?php if (isset($errormsg)) {echo $errormsg;} ?></span>
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
        // Obtener el elemento del menú por su ID
        var menu = document.getElementById('menu');
        var registro = document.getElementById('mensaje')
        // Mostrar el menú al cargar la página
        window.onload = function() {
            menu.style.display = 'block'; // O 'flex', 'inline-block', etc., dependiendo de tu diseño
            registro.style.display = 'block';
        };

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
                var menu = document.getElementById("menu");
                document.querySelectorAll("section").forEach(section => section.style.display = 'block');
                document.getElementById("mensaje").style.display = 'none';
                // Mostrar el formulario requerido
                document.getElementById("manage").style.display = 'block';
                document.getElementById("movtable").style.display = 'block';
                document.getElementById("register").style.display = 'none';
                menu.style.display = 'block';
            }

            function showRegister(formId) {
                var menu = document.getElementById('menu');
                document.querySelectorAll('section').forEach(section => section.style.display = 'block');
                document.getElementById("mensaje").style.display = 'none';
                // Mostrar el formulario requerido
                document.getElementById("register").style.display = 'block';
                document.getElementById("manage").style.display = 'none';
                menu.style.display = 'block';
            }

        function showUpdate(formId) {
            var menu = document.getElementById('menu');
            // Ocultar todas las secciones (comportamiento previo)
            document.querySelectorAll('section').forEach(section => section.style.display = 'none');

            // Mostrar solo el formulario solicitado y ocultar el 'register' si existe (comportamiento conservado)
            ['manage', 'register', 'movtable'].forEach(id => {
                var el = document.getElementById(id);
                if (el) {
                    el.style.display = (id === formId) ? 'block' : 'none';
                }
            });

            var target = document.getElementById(formId);
            if (target) target.style.display = 'block';
            if (menu) menu.style.display = 'block';
        }

        // Evento de busqueda
        document.addEventListener("DOMContentLoaded", function() {
            var input = document.getElementById("searchInputagen");
            input.addEventListener("input", function() {
                var filter = input.value.toUpperCase();
                var table = document.getElementById("movtable");
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
            });
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