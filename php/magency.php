<?php
// php/magency.php
// Define la función pintarMenu(...) que imprime solo el sidebar.
// No debe imprimir <html> ni <head> ni <body> completos cuando se incluye.
date_default_timezone_set('America/Bogota');
if (!function_exists('pintarMenu')) {
    function pintarMenu($perfil, $username) {
        // Usamos $con como global (tu conexión PDO/MySQLi)
        global $con;

        $fotolog = "";
        $logo = "";
        $icono = "";

        // obtener foto usuario (defensivo)
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            if ($user_id && isset($con)) {
                $sql_foto = "SELECT foto FROM users WHERE id = :user_id";
                $stmt = $con->prepare($sql_foto);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && !empty($result['foto'])) {
                    $fotolog = base64_encode($result['foto']);
                }
            }
        } catch (Exception $e) {
            // si falla, dejamos placeholder
            error_log("Error al obtener foto: " . $e->getMessage());
        }

        if (empty($fotolog)) {
            $fotolog = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
        }

        // obtener logo e icono (defensivo)
        if (isset($con)) {
            try {
                $imagen = "SELECT logo, icono FROM tb_company LIMIT 1";
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
            } catch (Exception $e) {
                $logo = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
                $icono = $logo;
            }
        } else {
            $logo = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
            $icono = $logo;
        }

        // ahora imprimimos solo el sidebar (HTML del menú)
        ?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta http-equiv="Content-Type" content="text/html;" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" type="image/png" href="/images/Icono.png" />
        <title>Inicio · Sives v1.1.1</title>

        <link href="/css/bootstrap.min.css" rel="stylesheet">
        <link href="/css/bootstrap.css" rel="stylesheet">
        <link href="/css/menu.css" rel="stylesheet">
        <link href="/css/signin.css" rel="stylesheet">
        <link href="/css/formularios.css" rel="stylesheet">
        <link href="/css/styles.css" rel="stylesheet">
        <link href="/css/bootstrap-theme.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet">
        <link href="/css/sidebars.css" rel="stylesheet">
        
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
                width: 1.5rem;
                height: 100vh;
            }

            .bi {
                vertical-align: -.125em;
                fill: currentColor;
            }

            /* Estilos para el botón de menú móvil */
            .btn-menu-movil {
                display: none;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1000;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 10px 15px;
                cursor: pointer;
                font-size: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            
            /* Sidebar styles */
            #sidebar {
                width: 280px;
                min-height: 100vh;
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
                overflow-x: auto;
            }
            
            #sidebar.open {
                transform: translateX(0);
            }
            
            /* Asegurar que el sombreado sea del mismo tamaño */
            .dropdown-menu {
                min-width: 200px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(0, 0, 0, 0.15);
            }
            
            .btn-toggle {
                width: 100%;
                text-align: left;
                padding: 0.5rem 1rem;
            }
            
            .btn-toggle-nav li {
                margin: 0.25rem 0;
            }
            
            .btn-toggle-nav a {
                padding: 0.375rem 1rem;
                display: block;
                width: 100%;
            }
            /* Botón principal */
            .btn-rounded {
                display: block !important;      /* fuerza a comportarse como bloque */
                width: 80% !important;          /* asegura el tamaño al 50% */
                padding: 7px 10px !important;
                border-radius: 10px;
                background-color: #ffffffff;
                color: #636363ff;
                font-size: 12px;
                font-weight: 500;
                text-align: left;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            }

            .btn-rounded:hover {
                background-color: #d8d4caff;
                color: #000;
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(126, 116, 70, 0.15);
            }

            @media (max-width: 768px) {
                .btn-menu-movil {
                    display: block;
                }
            }
            /* Estilos mejorados para el dropdown */
            #userDropdown {
                z-index: 1000;
            }
            #userDropdownMenu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                z-index: 1001;
                background: white;
                border: 1px solid rgba(0,0,0,0.15);
                border-radius: 0.375rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                min-width: 220px;
            }
            #userDropdownMenu.show {
                display: block;
                animation: fadeIn 0.2s ease-in-out;
            }

                /* Asegurar que el dropdown no se salga del viewport */
                @media (max-height: 600px) {
                    #userDropdownMenu {
                        max-height: 300px;
                        overflow-y: auto;
                    }
                }

                /* Animación suave */
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                /* Mejorar espaciado de los items */
                .dropdown-item {
                    padding: 0.5rem 1rem;
                    transition: background-color 0.15s ease;
                }

                .dropdown-item:hover {
                    background-color: #f8f9fa;

                }
        </style>
    </head>
    <body>
        <!-- BOTÓN HAMBURGUESA PARA MÓVIL -->
        <div id="btn-menu-movil" class="btn-menu-movil" onclick="toggleMobileMenu()">
            ☰
        </div>
            <!-- SIDEBAR DEL MENÚ -->
        <main class="d-flex flex-nowrap">
            <div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-body-tertiary">
                <a href="/php/main.php" class="d-flex align-items-center pb-3 mb-3 link-body-emphasis text-decoration-none border-bottom">
                    <img src="data:image/png;base64,<?php echo $icono; ?>" alt="Logo" width="40" height="40" class="rounded-circle me-2">
                    <span class="fs-5 fw-semibold">MENU - SIVES -</span>
                </a>

                <section id="menu" style="display: block;">
                    <ul class="list-unstyled ps-0 nav nav-pills flex-column mb-auto">
                        <li class="mb-1 nav-item">
                            <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed" data-bs-toggle="collapse" data-bs-target="#home-collapse" aria-expanded="false">
                                <svg class="bi pe-none me-2" width="16" height="16">
                                    <use xlink:href="#house-door"/>
                                </svg>
                                Empresa
                            </button>
                            <div class="collapse" id="home-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                    <li style="display: block;"><a href="/company.php" class="btn btn-rounded">Gestión de Empresa</a></li>
                                </ul>
                            </div>
                        </li>

                        <li class="mb-1 nav-item">
                            <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed" data-bs-toggle="collapse" data-bs-target="#dashboard-collapse" aria-expanded="false">
                                <svg class="bi pe-none me-2" width="16" height="16">
                                    <use xlink:href="#calendar-check"/>
                                </svg>
                                Vencimientos
                            </button>
                            <div class="collapse" id="dashboard-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                    <li style="display: block;"><a href="/expiration.php" class="btn btn-rounded">Gestión de Vencimientos</a></li>
                                </ul>
                            </div>
                        </li>

                        <li class="mb-1 nav-item">
                            <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed" data-bs-toggle="collapse" data-bs-target="#orders-collapse" aria-expanded="false">
                                <svg class="bi pe-none me-2" width="16" height="16">
                                    <use xlink:href="#building"/>
                                </svg>
                                Agencias
                            </button>
                            <div class="collapse" id="orders-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                <li style="display: block;"><a class="btn btn-rounded" href="#" onclick="showManage('manage')">Gestionar Agencia.</a></li>
                                <li style="display: block;"><a class="btn btn-rounded" href="#" onclick="showRegister('register')">Agregar Agencia.</a></li>
                                <li style="display: block;"><a href="/php/main.php" class="btn btn-rounded">Volver.</a></li>
                                </ul>
                            </div>
                        </li>

                        <li class="mb-1 nav-item">
                            <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed" data-bs-toggle="collapse" data-bs-target="#products-collapse" aria-expanded="false">
                                <svg class="bi pe-none me-2" width="16" height="16">
                                    <use xlink:href="#box-seam"/>
                                </svg>
                                Productos
                            </button>
                            <div class="collapse" id="products-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                    <li><a href="/products.php" class="btn btn-rounded">Gestión de Productos</a></li>
                                </ul>
                            </div>
                        </li>

                        <li class="mb-1 nav-item">
                            <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed" data-bs-toggle="collapse" data-bs-target="#account-collapse" aria-expanded="false">
                                <svg class="bi pe-none me-2" width="16" height="16">
                                    <use xlink:href="#people"/>
                                </svg>
                                Clientes
                            </button>
                            <div class="collapse" id="account-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                    <li><a href="/customer.php" class="btn btn-rounded">Gestión de Clientes</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </section>

                <hr>

                <div class="dropdown position-relative" id="userDropdown">
                    <a href="javascript:void(0)" class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle" 
                    onclick="toggleUserMenu()" id="dropdownUserLink">
                        <img src="data:image/png;base64,<?php echo $fotolog; ?>" alt="Logo" width="40" height="40" class="rounded-circle me-2">
                        <strong style="font-size:large">Usuario: <br><?php echo htmlspecialchars($username); ?></strong>
                    </a>
                    <ul class="dropdown-menu text-small shadow" id="userDropdownMenu" style="min-width: 220px;">
                        <?php if (isset($_SESSION['user_id'])) {
                            $user_id = $_SESSION['user_id'];
                        ?>
                            <li class="dropdown-item-text text-center px-3 py-2">
                                <small class="text-muted">Sesión como:</small><br>
                                <strong><?php echo htmlspecialchars($username); ?></strong>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li style="display: none;" ><a class="dropdown-item py-2" href="#" onclick="showRegister('register'); closeUserMenu();">Nuevo usuario</a></li>
                            <li style="display: none;"><a class="dropdown-item py-2" href="#" onclick="showActive('active'); closeUserMenu();">Activar usuario</a></li>
                            <li style="display: none;"><a class="dropdown-item py-2" href="#" onclick="showDesactive('desactive'); closeUserMenu();">Desactivar usuario</a></li>
                            <li style="display: none;"><a class="dropdown-item py-2" href="/php/function.php" onclick="closeUserMenu();">Crear Backup</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item bg-danger text-center py-2" href="/php/logout.php?nik=<?php echo $user_id; ?>" style="border-radius: 15px;">
                                    <strong>Salir</strong>
                                </a>
                            </li>
                        <?php } else { ?>
                            <li><a class="dropdown-item py-2" href="#" onclick="showForm('signup'); closeUserMenu();">Iniciar Sesión</a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="formrecuperar('formrecuperar'); closeUserMenu();">Recordar contraseña</a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="formcambiarpw('formcambiarpw'); closeUserMenu();">Cambiar contraseña</a></li>
                        <?php } ?>
                    </ul>
                </div>
                <hr>
                <footer class="mt-auto">
                    <a href="https://www.gti-sas.com" class="nav-link text-center" target="_blank">
                        <small><strong>Power By GTI-SAS</strong></small>
                    </a>
                </footer>
            </div>
            <div class="b-example-divider b-example-vr"></div>
        </main>

            <!-- SVG ICONS -->
        <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
            <symbol id="house-door" viewBox="0 0 16 16">
                <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>
            </symbol>
            <symbol id="calendar-check" viewBox="0 0 16 16">
                <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
            </symbol>
            <symbol id="building" viewBox="0 0 16 16">
                <path d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022zM6 8.694 1 10.36V15h5V8.694zM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15z"/>
                <path d="M2 11h1v1H2v-1zm2 0h1v1H4v-1zm-2 2h1v1H2v-1zm2 0h1v1H4v-1zm4-4h1v1H8V9zm2 0h1v1h-1V9zm-2 2h1v1H8v-1zm2 0h1v1h-1v-1zm2-2h1v1h-1V9zm0 2h1v1h-1v-1zM8 7h1v1H8V7zm2 0h1v1h-1V7zm2 0h1v1h-1V7zM8 5h1v1H8V5zm2 0h1v1h-1V5zm2 0h1v1h-1V5zm0-2h1v1h-1V3z"/>
            </symbol>
            <symbol id="box-seam" viewBox="0 0 16 16">
                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
            </symbol>
            <symbol id="people" viewBox="0 0 16 16">
                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0 4z"/>
            </symbol>
            <symbol id="eye" viewBox="0 0 512 512">
            <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-.7 0-1.3 0-2 0c1.3 5.1 2 10.5 2 16c0 35.3-28.7 64-64 64c-5.5 0-10.9-.7-16-2c0 .7 0 1.3 0 2c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"/>
            </symbol>
            <symbol id="check2" viewBox="0 0 16 16">
            <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z" />
            </symbol>
            <symbol id="pen-to-square" viewBox="0 0 512 512">
            <path d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152V424c0 48.6 39.4 88 88 88H360c48.6 0 88-39.4 88-88V312c0-13.3-10.7-24-24-24s-24 10.7-24 24V424c0 22.1-17.9 40-40 40H88c-22.1 0-40-17.9-40-40V152c0-22.1 17.9-40 40-40H200c13.3 0 24-10.7 24-24s-10.7-24-24-24H88z"/></svg>
            </symbol>
        </svg>


        <script src="/js/bootstrap.bundle.min.js"></script>
        <script src="/js/sidebars.js"></script>
        <script src="/js/bootstrap.min.js"></script>
        <script src="/js/jquery-1.10.2.js"></script>
        <script src="/js/color-modes.js"></script>

        <script>
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
            if (registro) registro.style.display = 'block';
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

            function showManage(formId) {
                var menu = document.getElementById("menu");
                document.querySelectorAll("section").forEach(section => section.style.display = 'block');
                document.getElementById("mensaje").style.display = 'none';
                // Mostrar el formulario requerido
                document.getElementById("manage").style.display = 'block';
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
        </script>
    </body>
</html>
        <?php
        // end function
    } // end if function_exists
}
?>
