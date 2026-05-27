<?php
// ====================== CONTROL DE SESIÓN ====================== //
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

// Mostrar mensajes de error si existen
$errorMessage = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Mostrar mensajes de error/success de recuperación
$recoveryError = $_SESSION['recovery_error'] ?? '';
$recoverySuccess = $_SESSION['recovery_success'] ?? '';
$localRecoveryData = $_SESSION['local_recovery_data'] ?? null;
unset($_SESSION['recovery_error'], $_SESSION['recovery_success'], $_SESSION['local_recovery_data']);

// ====================== INCLUDES BASE ====================== //
require_once("php/dbconnect.php");

    $database = Database::getInstance();
    $con = $database->getConnection();

$logo = "";
$icono = "";

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/png;base64,<?php echo $icono; ?>" type="image/png" sizes="32x32">
    <title>Inicio · Sives v1.1.1</title>
    
    <!-- CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <link href="/css/login.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="">
    <div class="login-card-index">
        <!-- LOGIN FORM -->
        <div id="login-form" class="form-container active">
            <div class="text-center mb-4">
                <img src="data:image/png;base64,<?php echo $logo; ?>" alt="lianseguros" class="login-logo">
                <h4 class="mt-3">Iniciar sesión en SIVES</h4>
            </div>

            <form id="signup" action="php/validar.php" method="POST">
                <div class="mb-3 form-floating">
                    <input type="text" class="form-control" id="user" name="user" placeholder="Usuario" required>
                    <label for="user">Usuario</label>
                </div>

                <div class="mb-3 form-floating password-container">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <label for="password">Contraseña</label>
                    <span class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-label" type="checkbox" name="checkbox" value="1" id="checkbox" required>
                    <label class="form-check-label" for="checkbox">Recordarme</label>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <button type="reset" class="btn btn-outline-secondary">Borrar</button>
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </div>

                <div class="login-options text-center">
                    <a href="#" class="d-block mb-2" onclick="showForm('recover')">¿Olvidaste tu contraseña?</a>
                    <a href="#" class="d-block" onclick="showForm('change')">Cambiar contraseña</a>
                </div>
            </form>

            <div class="mt-4 text-center">
                <p class="mb-2 text-muted">&copy; Sives 2024</p>
                <footer>
                    <a href="https://www.gti-sas.com" class="text-decoration-none" target="_blank">
                        <strong>Power By GTI-SAS</strong>
                    </a>
                </footer>
            </div>
        </div>

        <!-- RECOVER PASSWORD FORM -->
        <div id="recover-form" class="form-container active">
            <div class="text-center mb-4">
                <img src="data:image/png;base64,<?php echo $logo; ?>" alt="lianseguros" class="login-logo">
            </div>

            <form id="formrecuperar" action="php/sendpw.php" method="POST">
                <div class="mb-3">
                    <label for="recover-email" class="form-label">Email Registrado</label>
                    <input type="email" id="recover-email" name="email" class="form-control" placeholder="Correo Electrónico" required>
                </div>

                <!-- CAPTCHA para recuperación -->
                <div class="mb-3 captcha-container">
                    <p>Por favor, resuelva la siguiente operación*:</p>
                    <div class="d-flex align-items-center">
                        <div><span id="captcha-question-recover" class="captcha-question me-2"></span></div>
                        <div><input type="number" id="captcha-answer-recover" class="form-control" placeholder="Respuesta" required></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="showForm('login')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </form>


            <div class="mt-4 text-center">
                <p class="mb-2 text-muted">&copy; Sives 2024</p>
                <footer>
                    <a href="https://www.gti-sas.com" class="text-decoration-none" target="_blank">
                        <strong>Power By GTI-SAS</strong>
                    </a>
                </footer>
            </div>
        </div>
        
        <!-- CHANGE PASSWORD FORM -->
        <div id="change-form" class="form-container active">
            <div class="text-center mb-4">
                <img src="data:image/png;base64,<?php echo $logo; ?>" alt="lianseguros" class="login-logo">
                <h4 class="mt-3">CAMBIAR CONTRASEÑA</h4>
            </div>

            <form id="formcambiarpw" action="php/resetpassw.php" method="POST">
                <div class="mb-3">
                    <label for="change-email" class="form-label">Email Registrado</label>
                    <input type="email" id="change-email" name="email" class="form-control" placeholder="Correo Electrónico" required>
                </div>

                <!-- CAPTCHA para cambio de contraseña -->
                <div class="mb-3 captcha-container">
                    <p>Por favor, resuelva la siguiente operación*:</p>
                    <div class="d-flex align-items-center">
                        <div><span id="captcha-question-change" class="captcha-question me-2"></span></div>
                        <div><input type="number" id="captcha-answer-change" class="form-control" placeholder="Respuesta" required></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="showForm('login')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <p class="mb-2 text-muted">&copy; Sives 2024</p>
                <footer>
                    <a href="https://www.gti-sas.com" class="text-decoration-none" target="_blank">
                        <strong>Power By GTI-SAS</strong>
                    </a>
                </footer>
            </div>
        </div>

    </div>

    <!-- Modal para mostrar errores -->
    <?php if (!empty($errorMessage)): ?>
    <div class="modal fade show" id="errorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Error de Inicio de Sesión</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal()"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($recoveryError)): ?>
        <div class="modal fade show" id="recoveryErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Error en Recuperación</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeRecoveryErrorModal()"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo htmlspecialchars($recoveryError); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeRecoveryErrorModal()">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($localRecoveryData): ?>
        <div class="modal fade show" id="localRecoveryModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Recuperación de Contraseña (Entorno Local)</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeLocalRecoveryModal()"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($localRecoveryData['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($localRecoveryData['email']); ?></p>
                        <div class="alert alert-warning">
                            <strong>Contraseña:</strong> <?php echo htmlspecialchars($localRecoveryData['password']); ?>
                        </div>
                        <p class="text-muted"><small>En entorno de producción, esta información sería enviada por correo electrónico.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="closeLocalRecoveryModal()">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($recoverySuccess)): ?>
        <div class="modal fade show" id="recoverySuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Éxito</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeRecoverySuccessModal()"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo htmlspecialchars($recoverySuccess); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="closeRecoverySuccessModal()">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="/js/bootstrap.bundle.min.js"></script>
    <script src="/js/login.js"></script>
    <script>
        function closeLocalRecoveryModal() {
            document.getElementById('localRecoveryModal').style.display = 'none';
        }
        function closeModal() {
            document.getElementById('errorModal').style.display = 'none';
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // Cerrar modal haciendo clic fuera
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('errorModal');
            if (event.target === modal) {
                closeModal();
            }
        });

                function closeRecoveryErrorModal() {
            document.getElementById('recoveryErrorModal').style.display = 'none';
        }

        function closeRecoverySuccessModal() {
            document.getElementById('recoverySuccessModal').style.display = 'none';
        }

        // Cerrar modales con ESC y clic fuera
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const errorModal = document.getElementById('recoveryErrorModal');
                const successModal = document.getElementById('recoverySuccessModal');
                if (errorModal) errorModal.style.display = 'none';
                if (successModal) successModal.style.display = 'none';
            }
        });

        document.addEventListener('click', function(event) {
            const errorModal = document.getElementById('recoveryErrorModal');
            const successModal = document.getElementById('recoverySuccessModal');
            if (errorModal && event.target === errorModal) errorModal.style.display = 'none';
            if (successModal && event.target === successModal) successModal.style.display = 'none';
        });
    </script>
</body>
</html>