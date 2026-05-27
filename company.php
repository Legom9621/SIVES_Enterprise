<?php
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
require_once("php/mcompany.php"); // <- AQUI IRA EL MENÚ NUEVO

$tipodoc = '';
$nrodocumento = '';
$nomcompany = '';
$website = '';
$dircompany = '';
$telcompany = '';
$mailcompany = '';
$logo = '';
$icono = '';


try {
        // Consultar los datos
        $stmt = $con->prepare("SELECT idtb_company,tipodoc, nrodocumento, nomcompany, website, dircompany, telcompany, mailcompany, logo, icono FROM tb_company");
        $stmt->execute();

        // En PDO se usa fetch() o fetchAll()
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $idcompany = htmlspecialchars($row['idtb_company']);
            $tipodoc = htmlspecialchars($row['tipodoc']);
            $nrodocumento = htmlspecialchars($row['nrodocumento']);
            $nomcompany = htmlspecialchars($row['nomcompany']);
            $website = htmlspecialchars($row['website']);
            $dircompany = htmlspecialchars($row['dircompany']);
            $telcompany = htmlspecialchars($row['telcompany']);
            $mailcompany = htmlspecialchars($row['mailcompany']);

            if ($row && !empty($row['logo'])) {
                $logo = base64_encode($row['logo']);
            }            
            // Si no hay foto, usar placeholder
            if (empty($logo)) {
                $logo = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
            }

            if ($row && !empty($row['icono'])) {
                $icono = base64_encode($row['icono']);
            }            
            // Si no hay foto, usar placeholder
            if (empty($icono)) {
                $icono = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
            }
        }
} catch (PDOException $e) {
    // Manejo de errores más robusto
    error_log("Error en consulta de company.php: " . $e->getMessage());
    die("Error al cargar los datos de la compañía.");
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

<!doctype html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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
    <link href="https://fonts.googleapis.comcss?family=Lobster" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/css/sidebars.css" rel="stylesheet">
</head>

<body>
    <main class="d-flex justify-content-center align-items-center vh-100 bg-light">

        <!-- ====================== MENÚ LATERAL ====================== -->
            
          <?php pintarMenu($perfil, $username); ?>  <!-- llamada a la función del nuevo menu -->

            <div class="b-example-divider b-example-vr" style="height: 850px;"></div>
        <!-- ====================== CONTENIDO PRINCIPAL ====================== -->

        <div class="scrollable-active">
            <section id="mensaje" style="display: block; margin: inherit;">
                <center>
                    <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                </center><br>
                <fieldset>
                    <center>
                        <h2 class="main-title-modern">Sistema de Vencimientos de Seguros - SIVES -</h2>
                    </center>
                    <center>
                        <div class="content">
                            <h1 class="main-subtitle-corporate">Bienvenido</h1>
                            <p class="main-subtitle-minimal">A la sección Gestión de la compañia.</p>
                        </div>
                    </center>
                </fieldset>
            </section>

            <!--Formulario de registro de compañia-->
            <form id="registerc" class="" role="form" action="php/registercompany.php" enctype="multipart/form-data" method="POST" name="signupform" style="display: none;">
                <fieldset class="">
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="fs-2 fw-semibold">Registro de la compañia.</h1>
                        </legend>
                    </center>
                </fieldset>
                <br>
                <fieldset class="">
                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md4">
                            <p><label for="tpodoc" class="fs-5 fw-semibold">Tipo Documento.</label></p>
                            <select class="form-control custom-select" name="tpodoc" id="tpodoc" required>
                                <option name=config value="0" selected DISABLED="disabled">Seleccione tipo de documento</option>
                                <option name=config value="NIT">NIT.</option>
                                <option name=config value="CC">Cédula Ciudadanía.</option>
                                <option name=config value="CE">Cédula Extranjeria.</option>
                            </select>
                            <span class="text-danger"><?php if (isset($name_error)) echo $name_error; ?></span>
                        </div>
                        <div class="form-groupc col-md-3">
                            <p><label for="doc" class="fs-5 fw-semibold">Número de Documento.</label></p>
                            <input type="int" name="doc" placeholder="Nro . Documento" required value="" class="form-control" />
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-5">
                            <p><label for="namecom" class="fs-5 fw-semibold">Nombre Compañia.</label></p>
                            <input type="text" name="namecom" id="namecom" placeholder="Nombre Compañia" required value="" class="form-control" />
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md-5">
                            <p><label for="web" class="fs-5 fw-semibold">Website Compañia.</label></p>
                            <input type="text" name="web" placeholder="Web Compañia" required value="" class="form-control" />
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-5">
                            <p><label for="email" class="fs-5 fw-semibold">Email Compañia.</label></p>
                            <input type="email" name="email" placeholder="Correo Electrónico" required value="" class="form-control" />
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md-5">
                            <p><label for="address" class="fs-5 fw-semibold">Dirección Compañia.</label></p>
                            <input type="text" name="address" placeholder="Dirección Compañia" required value="" class="form-control" />
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-5">
                            <p><label for="phone" class="fs-5 fw-semibold">Teléfono Compañia.</label></p>
                            <input type="int" name="phone" placeholder="Teléfono Compañia" required value="" class="form-control" />
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowci col-md-12">
                        <div class="form-groupc col-md-5">
                            <b><label for="logo" class="fs-5 fw-semibold">Logo Compañia.</label></b>
                            <input type="file" class="form-control" accept="image/png" name="logo" id="logo">
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowci col-md-12">
                        <div class="form-groupc col-md-5">
                            <b><label for="icono" class="fs-5 fw-semibold">Icono Compañia.</label></b>
                            <input type="file" class="form-control" accept="image/png" name="icono" id="icono">
                            <span class="text-danger"></span>
                        </div>
                    </div>
                </fieldset>
                <br>
                <div class="dVolver ms-auto">
                    <button title="Guardar" name="accion" value="actualizar" id="accion" formmethod="post" type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i>Guardar</button>
                </div>
                <center><br>
                    <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                </center>
            </form>
            
            <!-- Formulario de modificación de datos de la compañia -->
            <form id="update" class="" role="form" action="php/updatecompany.php" method="POST" enctype="multipart/form-data" name="update" style="display: none;">
                <fieldset class="">
                    <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                    </center><br><br>
                    <center>
                        <legend>
                            <h1 class="fs-2 fw-semibold">Actualizar datos de la compañia.</h1>
                        </legend>
                    </center>
                </fieldset>
                <fieldset class="">
                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md4">
                            <input type="hidden" name="idtb_company" value="<?php echo $idcompany; ?>">
                            <p><label for="tpodoc" class="fs-5 fw-semibold">Tipo Documento.</label></p>
                            <input type="text" name="tpodoc" value="<?php echo $tipodoc; ?>" class="form-control" readonly>
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-3">
                            <p><label for="doc" class="fs-5 fw-semibold">Número de Documento.</label></p>
                            <input type="text" name="doc" value="<?php echo $nrodocumento; ?>" class="form-control" readonly>
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-5">
                            <p><label for="namecom" class="fs-5 fw-semibold">Nombre Compañia.</label></p>
                            <input type="text" name="namecom" value="<?php echo $nomcompany; ?>" class="form-control" require>
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md-5">
                            <p><label for="web" class="fs-5 fw-semibold">Website Compañia.</label></p>
                            <input type="text" name="web" value="<?php echo $website; ?>" class="form-control" require>
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-5">
                            <p><label for="email" class="fs-5 fw-semibold">Email Compañia.</label></p>
                            <input type="email" name="email" value="<?php echo $mailcompany; ?>" class="form-control" require>
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowc col-md-12">
                        <div class="form-groupc col-md-5">
                            <p><label for="address" class="fs-5 fw-semibold">Dirección Compañia.</label></p>
                            <input type="text" name="address" value="<?php echo $dircompany; ?>" class="form-control" require>
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-5">
                            <p><label for="phone" class="fs-5 fw-semibold">Teléfono Compañia.</label></p>
                            <input type="text" name="phone" value="<?php echo $telcompany; ?>" class="form-control" require>
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowci col-md-12">
                        <div class="form-groupc col-md-5">
                            <b><label for="logo" class="fs-5 fw-semibold">Logo Compañia.</label></b>
                            <input type="file" name="logo" class="form-control" accept="image/png" name="logo" id="logo">
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-2">
                            <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="100%">
                            <span class="text-danger"></span>
                        </div>
                    </div>

                    <div class="form-rowci col-md-12">
                        <div class="form-groupc col-md-5">
                            <b><label for="icono" class="fs-5 fw-semibold">Icono Compañia.</label></b>
                            <input type="file" name="icono" class="form-control" accept="image/png" name="icono" id="icono">
                            <span class="text-danger"></span>
                        </div>
                        <div class="form-groupc col-md-2">
                            <img src="data:image/png;base64,<?php echo $icono; ?>" alt="Icono" width="45%">
                            <span class="text-danger"></span>
                        </div>
                    </div>
                </fieldset><br><br>
                <div class="dVolver ms-auto">
                    <button title="Guardar" name="accion" value="actualizar" id="accion" formmethod="post" type="submit" class="btn btn-info" onclick="return validarguardar()">
                        <i class="fas fa-save"></i>Actualizar</button>
            
                </div>
                <center><br>
                    <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                </center>
            </form>
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


        function showRegister(formId) {
            var menu = document.getElementById('menu');
            document.querySelectorAll('section').forEach(section => section.style.display = 'block');
            document.getElementById("mensaje").style.display = 'none';
            // Mostrar el formulario requerido
            document.getElementById(formId).style.display = 'block';
            document.getElementById("update").style.display = 'none';
            menu.style.display = 'block';
        }

        function showRegisterc(formId) {
            var menu = document.getElementById('menu');
            hideAllForms();

            // ocultaciones específicas (si existen) 
            var elUpdate = document.getElementById("update");
            if (elUpdate) elUpdate.style.display = 'none';
                // Mostrar el formulario requerido (Próximos)
                var target = document.getElementById(formId === 'registerc' ? 'registerc' : formId);
                    if (target) {
                        target.style.display = 'block';
                    }
                    if (menu) menu.style.display = 'block';
        }
        
        function showUpdate(formId) {
            var menu = document.getElementById('menu');
            document.querySelectorAll('section').forEach(section => section.style.display = 'none');
            // Mostrar el formulario requerido
            document.getElementById(formId).style.display = 'block';
            document.getElementById("register").style.display = 'none';
            menu.style.display = 'block';
        }

        // Evento de busqueda
        document.addEventListener("DOMContentLoaded", function() {
            var input = document.getElementById("searchInputmail");
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

        // Evento de busqueda
        document.addEventListener("DOMContentLoaded", function() {
            var input = document.getElementById("searchInputmaila");
            input.addEventListener("input", function() {
                var filter = input.value.toUpperCase();
                var table = document.getElementById("movtable1");
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
    </script>
</body>

</html>