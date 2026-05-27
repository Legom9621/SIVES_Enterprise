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
require_once("userstate.php");
require_once("userlist.php");
require_once("menuppal.php"); // <- AQUI IRA EL MENÚ NUEVO

// Llamar a la función solo si se accede directamente
$usuarios_Inactivos = obtenerUsuariosInactivos($con); // Llamar a la función
$usuarios_Activos = obtenerUsuariosActivos($con); // Llamar a la función

    // OBTENER LOGO E ICONO DE LA COMPAÑIA DESDE LA BASE DE DATOS
    $imagen = "SELECT logo, icono FROM tb_company";
    $stmt = $con->prepare($imagen);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['logo'])) {
            $logo = base64_encode($result['logo']);
        }// Si no hay foto, usar placeholder
        else{
            $logo = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
        }
                
        if ($result && !empty($result['icono'])) {
                $icono = base64_encode($result['icono']);
        }// Si no hay foto, usar placeholder
        else{
                $icono = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
        }
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta http-equiv="Content-Type" content="text/html;" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="data:image/png;base64,<?php echo $icono; ?>" type="image/png" sizes="32x32">
  <link rel="shortcut icon" type="image/png " href="images/Icono.png" />
  <title>Inicio · Sives v1.1.1</title>

  <link href="/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/bootstrap.css" rel="stylesheet">
  <link href="/css/menu.css" rel="stylesheet">
  <link href="/css/signin.css" rel="stylesheet">
  <link href="/css/formularios.css" rel="stylesheet">
  <link href="/css/styles.css" rel="stylesheet">
  <link href="/css/bootstrap-theme.min.css" rel="stylesheet">
  <link href="/css/titulos.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet">

  <!-- Custom styles for this template -->
  <link href="/css/sidebars.css" rel="stylesheet">
</head>


<body>
    <main id="principal" class="d-flex flex-nowrap">
            <!-- ====================== MENÚ LATERAL ====================== -->
                
            <?php pintarMenu($perfil, $username); ?>  <!-- llamada a la función del nuevo menu -->

                <div class="b-example-divider b-example-vr" style="height: 850px;"></div>
            <!-- ====================== CONTENIDO PRINCIPAL ====================== -->
                <div class="formulariocaja">
                    <section id="mensaje" style="display: block; margin: inherit;">
                                    <center>
                                    <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo Lianseguros" width="15%">
                                    </center>
                                    <br>
                                    <fieldset>
                                    <center>
                                    <h1 class="main-title-modern">Sistema de Vencimientos de Seguros - SIVES -</h1>
                                    </center>
                                    <br>
                                    <center>
                                    <h4 class="main-subtitle">Ya se encuentra iniciada su sesión. Ahora puede gestionar desde el menú de la izquierda..</h4>
                                    </center>
                                    </fieldset>
                    </section>

                            <!--Formulario de registro de usuario-->
                    <form id="register" class="containerFrmr scrollable-div" role="form" action="php/userstate.php" method="POST" name="signupform" style="display: none;">
                                    <fieldset class="fieldsetreg">
                                    <center>
                                    <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                                    </center><br><br>
                                    <center>
                                    <legend>
                                        <h1 class="fs-2 fw-semibold">Registro de Usuario.</h1>
                                    </legend>
                                    </center>
                                    </fieldset>
                                    <br>
                                <fieldset class="fieldsetregdata">
                                <div class="form-groupr">
                                    <p><label for="name" class="fs-5 fw-semibold">Nombres y Apellidos.</label></p>
                                    <input type="text" name="name" placeholder="Nombres Completos" required value="<?php if ($error) echo $name ?>" class="form-control" />
                                    <span class="text-danger"><?php if (isset($name_error)) echo $name_error; ?></span>
                                </div>
                                <div class="form-groupr">
                                    <p><label for="user" class="fs-5 fw-semibold">Usuario</label></p>
                                    <input type="text" name="user" placeholder="Usuario" required value="" class="form-control" />
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupr">
                                    <p><label for="email" class="fs-5 fw-semibold">Email.</label></p>
                                    <input type="email" name="email" placeholder="Correo Electrónico" required value="" class="form-control" />
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupr">
                                    <p><label for="password" class="fs-5 fw-semibold">Contraseña.</label></p>
                                    <input type="password" name="password" placeholder="Contraseña" required class="form-control" />
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupr">
                                    <p><label for="cpassword" class="fs-5 fw-semibold">Repita Contraseña.</label></p>
                                    <input type="password" name="cpassword" placeholder="Confirmar Contraseña" required class="form-control" />
                                    <span class="text-danger"><?php if (isset($cpassword_error)) echo $cpassword_error; ?></span>
                                </div>
                                <div class="form-groupr">
                                    <p><label for="perfil" class="fs-5 fw-semibold">Perfil de usuario.</label></p>
                                    <select class="form-control custom-select" name="perfil" id="perfil" required>
                                    <option name=config value="0" selected DISABLED="disabled">Seleccione un perfil</option>
                                    <?php
                                    if ($resultp->num_rows > 0) {
                                        while($row = $resultp->fetch_assoc()) {
                                        echo "<option value='" . $row["idtb_perfil"] . "'>" . $row["perfil"] . "</option>";
                                        }
                                    } else {
                                            echo "<option>No hay perfiles disponibles</option>";
                                        }
                                        ?>
                                    </select>
                                    <span class="text-danger"></span>
                                </div>
                                <div class="form-groupr">
                                        <p><label for="foto" class="fs-5 fw-semibold">Foto.</label></p>
                                        <input type="file" class="form-control" accept="image/png" name="foto" id="foto">
                                        <span class="text-danger"></span>
                                </div>
                                </fieldset>
                                    <br>
                                    <div class="dVolver ms-auto">
                                        <button title="Guardar" name="accion" value="actualizar" id="accion" formmethod="post" type="submit" class="btn btn-info" onclick="return validarguardar()">
                                        <i class="fas fa-save"></i>Guardar</button>
                                    </div>
                                <center>
                                    <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                                </center>
                    </form>

                            <!-- Formulario de activación de usuario formulariocajas-->
                    <form id="active" class="scrollable-active" role="form" action="php/activar_usuario.php" method="POST" name="activeform" style="display: none;">
                        <center>
                            <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                        </center><br>
                        <fieldset>
                            <center>
                            <legend>
                                <h1 class="fs-2 fw-semibold" style="margin-top:-5%; margin-bottom:-15%;">Activar Usuario.</h1>
                            </legend>
                            </center>
                        </fieldset>

                        <div class="search">
                            <label for="find"><p class="fs-6 flex-wrap"><strong>Digite nombre de usuario a buscar.</strong></p></label>
                            <input type="text" class="form-row1" id="searchInputmail" style="width:40%"
                            placeholder="Buscar"  value="<?php if ($error) echo $name ?>" class="form-control">
                            <span class="text-danger"><?php if (isset($name_error)) echo $name_error; ?></span>
                        </div>

                                <?php if (isset($_GET['mensaje'])): ?>
                                <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
                                <?php endif; ?>


                        <div class="container">
                            <table class="tables " id="movtable">
                                    <thead>
                                    <tr style="color:#456789;">
                                        <th scope="col">Id.</th>
                                        <th scope="col">Nombre Usuario.</th>
                                        <th scope="col">Usuario.</th>
                                        <th scope="col">Correo Electrónico</th>
                                        <th scope="col">Estado.</th>
                                        <th scope="col">Perfil.</th>
                                        <th scope="col">Acción.</th>
                                    </tr>
                                    </thead>
                                    <?php if (empty($usuarios_Inactivos)): ?>
                                    <tr>
                                        <td colspan="4">No hay usuarios inactivos.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($usuarios_Inactivos as $usuario): ?>
                                    <tbody id="tbodymov">
                                                <td style="color:#456789;"><?php echo htmlspecialchars($usuario['id']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['name']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['user']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['estado']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['perfil']); ?></td>
                                                <td>
                                                    <input type="hidden" name="Iduser" value="<?php echo htmlspecialchars($usuario['id']); ?>">
                                                    <button type="submit" class="fcc-btn" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">Activar</button>
                                                </td>
                                    </tbody>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                            </table>
                        </div>
                        <center><br>
                                <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                        </center>
                    </form>

                            <!-- Formulario desactivar usuario formulariocajas-->
                    <form id="desactive" class="scrollable-active" role="form" action="php/desactivar_usuario.php" method="POST" name="activeform" style="display: none;">
                        <center>
                        <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="15%">
                        </center>
                        <br>
                        <fieldset>
                        <center>
                        <legend>
                            <h1 class="fs-2 fw-semibold" style="margin-top:-5%; margin-bottom:-15%;">Desactivar Usuario.</h1>
                        </legend>
                        </center>
                        </fieldset>
                            <div class="search">
                                <label for="find"><p class="fs-6 flex-wrap"><strong>Digite nombre de usuario a buscar.</strong></p></label>
                                <input type="text" class="form-row1" id="searchInputmaila" style="width:40%"
                                placeholder="Buscar" required value="<?php if ($error) echo $name ?>" class="form-control">
                                <span class="text-danger"><?php if (isset($name_error)) echo $name_error; ?></span>
                                </div>

                                <?php if (isset($_GET['mensaje'])): ?>
                                <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
                                <?php endif; ?>
                        <div class="container">
                            <table class="tables" id="movtable1">
                                    <thead>
                                    <tr style="color:#456789;">
                                        <th scope="col">Id.</th>
                                        <th scope="col">Nombre Usuario.</th>
                                        <th scope="col">Usuario.</th>
                                        <th scope="col">Correo Electrónico</th>
                                        <th scope="col">Estado.</th>
                                        <th scope="col">Perfil.</th>
                                        <th scope="col">Acción.</th>
                                    </tr>
                                    </thead>
                                    <?php if (empty($usuarios_Activos)): ?>
                                        <tr>
                                            <td colspan="4">No hay usuarios Desactivados.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios_Activos as $usuarioa): ?>
                                            <tbody id="tbodymov">
                                                <td style="color:#456789;"><?php echo htmlspecialchars($usuarioa['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuarioa['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuarioa['user']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuarioa['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuarioa['estado']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuarioa['perfil']); ?></td>
                                                    <td>
                                                    <!--<form method="POST" action="" style="display: inline">-->
                                                    <input type="hidden" name="Iduser" value="<?php echo htmlspecialchars($usuarioa['id']); ?>">
                                                    <button type="submit" class="fcc-btn" name="id" value="<?php echo htmlspecialchars($usuarioa['id']); ?>">Desactivar</button>
                                                    </td>
                                            </tbody>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                            </table>
                        </div>
                        <center>
                                <h5 class="mt-3 mb-3 text-body-primary">&copy; Sives 2024</h5>
                        </center>
                    </form>
                            <span class="text-success"><?php if (isset($successmsg)) {echo $successmsg;} ?></span>
                            <span class="text-danger"><?php if (isset($errormsg)) { echo $errormsg; } ?></span>

                            <!-- Carga company.php-->
                            <section id="contenido"></section>
                </div>
    </main>

</body>
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
            // Función manual para el dropdown
        function toggleUserMenu() {
            var dropdownMenu = document.getElementById('userDropdownMenu');
            var dropdownLink = document.getElementById('dropdownUserLink');
            
            if (dropdownMenu && dropdownLink) {
                var isOpening = !dropdownMenu.classList.contains('show');
                
                dropdownMenu.classList.toggle('show');
                dropdownLink.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
                
                // Si se está abriendo el menú, hacer scroll para que sea visible
                if (isOpening) {
                    setTimeout(function() {
                        scrollToVisible(dropdownMenu);
                    }, 10);
                }
            }
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            var dropdown = document.getElementById('userDropdown');
            var dropdownMenu = document.getElementById('userDropdownMenu');
            var dropdownLink = document.getElementById('dropdownUserLink');
            
            if (dropdown && dropdownMenu && dropdownLink && 
                !dropdown.contains(event.target) && 
                dropdownMenu.classList.contains('show')) {
                closeUserMenu();
            }
        });

        // Cerrar dropdown al hacer scroll
        window.addEventListener('scroll', function() {
            closeUserMenu();
        });

        // Cerrar dropdown al redimensionar la ventana
        window.addEventListener('resize', function() {
            closeUserMenu();
        });

                // Función para hacer scroll y que el elemento sea visible
        function scrollToVisible(element) {
            if (!element) return;
            
            var rect = element.getBoundingClientRect();
            var isInViewport = (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
            
            // Si el elemento no está completamente visible, hacer scroll
            if (!isInViewport) {
                var offset = 20; // Margen adicional
                var targetScroll = window.pageYOffset + rect.top - offset;
                
                window.scrollTo({
                    top: targetScroll,
                    behavior: 'smooth'
                });
            }
        }

                // Inicializar dropdowns de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar todos los dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            console.log('Dropdowns inicializados:', dropdownList.length);
        });

        // Función alternativa si Bootstrap no carga
        function toggleUserMenu() {
            var dropdown = document.querySelector('.dropdown .dropdown-menu');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }
        // Función para el menú móvil
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('open');
            }
        }

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

    function showRegister(formId) {
      var menu = document.getElementById('menu');
      // Ocultar todos los formularios
      //document.querySelectorAll('form').forEach(form => form.style.display = 'none');
      document.querySelectorAll('section').forEach(section => section.style.display = 'block');
      document.getElementById("mensaje").style.display = 'none';
      // Mostrar el formulario requerido
      document.getElementById(formId).style.display = 'block';
      document.getElementById("active").style.display = 'none';
      menu.style.display = 'block';
    }

    function showActive(formId) {
      var menu = document.getElementById('menu');
      // Ocultar todos los formularios
      //document.querySelectorAll('form').forEach(form => form.style.display = 'none');
      document.querySelectorAll('section').forEach(section => section.style.display = 'none');
      // Mostrar el formulario requerido
      document.getElementById(formId).style.display = 'block';
      document.getElementById("register").style.display = 'none';
      menu.style.display = 'block';
    }

    function showDesactive(formId) {
      var menu = document.getElementById('menu');
      // Ocultar todos los formularios
      //document.querySelectorAll('form').forEach(form => form.style.display = 'none');
      document.querySelectorAll('section').forEach(section => section.style.display = 'none');
      // Mostrar el formulario requerido
      document.getElementById(formId).style.display = 'block';
      document.getElementById("active").style.display = 'none';
      document.getElementById("register").style.display = 'none';
      menu.style.display = 'block';
    }
        // Cerrar menú móvil al hacer clic en un enlace
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (e.target.tagName === 'A' && e.target.getAttribute('href') !== '#') {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('open');
                    }
                }
            }
        });

</script>


</html>
