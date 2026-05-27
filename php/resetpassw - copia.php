<?php
session_start();
require_once("dbconnect.php");
require_once("SED.php");

// Zona horaria
date_default_timezone_set('America/Bogota');

// Inicializar variables
$_SESSION['user'] = $_SESSION['user'] ?? "";
$_SESSION['email'] = $_SESSION['email'] ?? "";

$error = true;
$password = '';
$cpassword = '';
$nr = 0;
$user = '';
$password_error = '';
$cpassword_error = '';
$successmsg = '';
$errormsg = '';

/**
 * Valida la fortaleza de la contraseña
 */

function validar_clave($clave, &$error_clave) {
    if (strlen($clave) < 6) {
        $error_clave = "La clave debe tener al menos 6 caracteres";
        return false;
    }
    if (strlen($clave) > 16) {
        $error_clave = "La clave no puede tener más de 16 caracteres";
        return false;
    }
    if (!preg_match('`[a-z]`', $clave)) {
        $error_clave = "La clave debe tener al menos una letra minúscula";
        return false;
    }
    if (!preg_match('`[A-Z]`', $clave)) {
        $error_clave = "La clave debe tener al menos una letra mayúscula";
        return false;
    }
    if (!preg_match('`[0-9]`', $clave)) {
        $error_clave = "La clave debe tener al menos un caracter numérico";
        return false;
    }
    $error_clave = "";
    return true;
}

function validarCorreo() {
    global $con, $user;
    
    // Verificar si se envió el formulario con email
    if (isset($_POST['email'])) {
        $correo = $_POST['email'];
        
        // Validar que el correo no esté vacío
        if (empty(trim($correo))) {
            mostrarErrorYRedirigir();
        }
        
        // Validar formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            mostrarErrorYRedirigir();
        }
        
        // Verificar si el correo existe en la base de datos
        $sql_user = "SELECT user FROM users WHERE email = :correo";
        $stmt_user = $con->prepare($sql_user);
        $stmt_user->bindValue(':correo', $correo, PDO::PARAM_STR);
        $stmt_user->execute();
        
        $nr = $stmt_user->rowCount();
        
        if ($nr == 0) {
            // No se encontró el usuario - mostrar error y redirigir
            mostrarErrorYRedirigir();
        } else {
            // Usuario encontrado - asignar a variable global $user
            $mostrar = $stmt_user->fetch(PDO::FETCH_ASSOC);
            $user = $mostrar['user'];
            $_SESSION['user'] = $user;
            $_SESSION['email'] = $correo;
            return true;
        }
    }
    return false;
}

function mostrarErrorYRedirigir() {
    echo "<script>
        alert('Verrifique el correo ingresado. O pongase en contacto con el administrador. Registro no existente...');
        window.location.href = '/index.php';
    </script>";
    exit();
}


try {
    $database = Database::getInstance();
    $con = $database->getConnection();
    
    // Primero validar el correo si viene del formulario anterior
    $correoValidado = validarCorreo();
    
    // Si se validó el correo exitosamente, la variable $user ya está asignada
    // y se mostrará en el formulario

    // Procesar formulario de cambio de contraseña
    if (isset($_POST['signup'])) {
        $user = $_POST['user'] ?? '';
        $password = $_POST['password'] ?? '';
        $cpassword = $_POST['cpassword'] ?? '';
        
        // Validar que todos los campos estén presentes
        if (empty($user) || empty($password) || empty($cpassword)) {
            throw new Exception("Todos los campos son requeridos");
        }

        $error_encontrado = "";
        
        // Validar fortaleza de la contraseña
        if (!validar_clave($password, $error_encontrado)) {
            $error = false;
            $password_error = "La contraseña no cumple con seguridad: " . $error_encontrado;
            error_log("Error validación contraseña: " . $password_error);
        } 
        // Validar que las contraseñas coincidan
        elseif ($password !== $cpassword) {
            $error = false;
            $cpassword_error = "Las contraseñas no coinciden";
            error_log("Contraseñas no coinciden para usuario: " . $user);
        }

        // Si no hay errores, proceder con el cambio
        if ($error) {
            // Verificar si el usuario existe
            $sql_check = "SELECT id, user FROM users WHERE user = :user";
            $stmt_check = $con->prepare($sql_check);
            $stmt_check->bindValue(':user', $user, PDO::PARAM_STR);
            $stmt_check->execute();
            
            $nr = $stmt_check->rowCount();
            
            if ($nr == 1) {
                // Encriptar contraseña
                $passworde = SED::encryption($password);
                
                // Actualizar contraseña en la base de datos
                $sql_update = "UPDATE users SET password = :password, forgot_pass_identity = :forgot_pass WHERE user = :user";
                $stmt_update = $con->prepare($sql_update);
                $stmt_update->bindValue(':password', $passworde, PDO::PARAM_STR);
                $stmt_update->bindValue(':forgot_pass', $passworde, PDO::PARAM_STR);
                $stmt_update->bindValue(':user', $user, PDO::PARAM_STR);
                
                if ($stmt_update->execute()) {
                    $successmsg = '
                        <div class="alert alert-success alert-dismissable fade in">
                        <a href="/index.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <strong>ÉXITO!</strong> ¡Se ha cambiado la contraseña con Éxito!
                        </div>';
                    error_log("Contraseña cambiada exitosamente para usuario: " . $user);
                    
                    // Redirigir después de 2 segundos
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '/index.php';
                        }, 2000);
                    </script>";
                } else {
                    throw new Exception("Error al ejecutar la actualización en la base de datos");
                }
            } else {
                throw new Exception("No se puede cambiar la contraseña del usuario: $user. Usuario no encontrado.");
            }
        }
    } 

} catch (PDOException $e) {
    error_log("Error de base de datos en resetpassw.php: " . $e->getMessage());
    $errormsg = '
        <div class="alert alert-danger alert-dismissable fade in">
        <a href="/index.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error de base de datos!</strong> No se pudo procesar la solicitud.
        </div>';
} catch (Exception $e) {
    error_log("Error en resetpassw.php: " . $e->getMessage());
    $errormsg = '
        <div class="alert alert-danger alert-dismissable fade in">
        <a href="/index.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
}

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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/png;base64,<?php echo $icono; ?>" type="image/png" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="/images/Icono.png" />
    <title>Cambiar Contraseña · Sives v1.1.1</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/menus.css" rel="stylesheet">
    <link href="/css/signin.css" rel="stylesheet">
    <link href="/css/formularios.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <link href="/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet">
    
    <style>
        .containertbl {
            max-width: 450px;
            margin: 10px 35%;
            padding: 0% 0%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        .form-control[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin: 30px;
            margin-left: 5%;
        }
        .btn {
            padding: 8px 16px;
            border: 1;
            border-radius: 5px;
            /*font-size: 16px;*/
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            transition: all 0.2s;
            margin-left: 20px;
            padding: 8px;
            margin-right: 20px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            width: 17%;
            padding: 5px;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .text-danger {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
            display: block;
        }
        .text-success {
            color: #198754;
            font-size: 0.875em;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-width: 180px;
            height: auto;
        }
        .title {
            text-align: center;
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 600;
        }
        .copyright {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <form id="formreset" role="form" class="containertbl" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" name="signupform">
        <div id="mensajealerta" class="alert" style="display: none;"></div>
        
        <div class="title">CAMBIAR CONTRASEÑA</div>
        
        <div class="logo-container">
            <center>
                <img src="data:image/png;base64,<?php echo $logo; ?>" alt="Logo" width="25%">
            </center><br><br>
        </div>
        
        <!-- Campo Usuario -->
        <div class="form-group">
            <label for="user">Usuario:</label>
            <input type="text" name="user" id="user" placeholder="Ingrese su usuario" required 
                   value="<?php echo htmlspecialchars($user); ?>" 
                   <?php echo !empty($user) ? 'readonly' : ''; ?> 
                   class="form-control" />
        </div>
        
        <!-- Campo Nueva Contraseña -->
        <div class="form-group">
            <label for="floatingPassword">Nueva Contraseña:</label>
            <input type="password" name="password" id="floatingPassword" 
                   placeholder="Ingrese nueva contraseña" required class="form-control" />
            <?php if (!empty($password_error)): ?>
                <span class="text-danger"><?php echo htmlspecialchars($password_error); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Campo Confirmar Contraseña -->
        <div class="form-group">
            <label for="floatingCPassword">Repita Contraseña:</label>
            <input type="password" name="cpassword" id="floatingCPassword" 
                   placeholder="Confirme la contraseña" required class="form-control" />
            <?php if (!empty($cpassword_error)): ?>
                <span class="text-danger"><?php echo htmlspecialchars($cpassword_error); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Botones -->
        <div class="d-flex justify-content-between mb-3">
            <button type="button" id="cancelButton" class="btn btn-outline-secondary">Cancelar</button>
            <button type="submit" name="signup" class="btn btn-primary" onclick="return confirm('¿Desea cambiar su contraseña?');">Cambiar</button>
        </div>
        
        <div class="copyright">
            <h5 class="mt-5 mb-3 text-body-secondary">&copy; Sives 2024</h5>
        </div>
    </form>
    
    <!-- Mostrar mensajes de éxito o error -->
    <?php if (!empty($successmsg)): ?>
        <div class="container" style="max-width: 400px; margin: 20px auto;">
            <?php echo $successmsg; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errormsg)): ?>
        <div class="container" style="max-width: 400px; margin: 20px auto;">
            <?php echo $errormsg; ?>
        </div>
    <?php endif; ?>

    <script src="/js/bootstrap.bundle.min.js"></script>
    <script src="/js/sidebars.js"></script>
    <script src="/js/color-modes.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cancelButton = document.getElementById('cancelButton');
            
            cancelButton.addEventListener('click', function() {
                if (confirm('¿Está seguro de que desea cancelar? Los cambios no guardados se perderán.')) {
                    window.location.href = '/index.php';
                }
            });
            
            // Validación en tiempo real de contraseñas
            const passwordInput = document.getElementById('floatingPassword');
            const cpasswordInput = document.getElementById('floatingCPassword');
            
            function validatePasswords() {
                const password = passwordInput.value;
                const confirmPassword = cpasswordInput.value;
                
                if (confirmPassword === '') {
                    cpasswordInput.style.borderColor = '#ddd';
                    return;
                }
                
                if (password !== confirmPassword) {
                    cpasswordInput.style.borderColor = '#dc3545';
                    cpasswordInput.style.boxShadow = '0 0 5px rgba(220,53,69,0.3)';
                } else {
                    cpasswordInput.style.borderColor = '#198754';
                    cpasswordInput.style.boxShadow = '0 0 5px rgba(25,135,84,0.3)';
                }
            }
            
            passwordInput.addEventListener('input', validatePasswords);
            cpasswordInput.addEventListener('input', validatePasswords);
            
            // Validación visual de fortaleza de contraseña
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                
                const colors = ['#dc3545', '#fd7e14', '#ffc107', '#198754'];
                this.style.borderColor = colors[strength] || '#ddd';
            });
        });
    </script>
</body>
</html>