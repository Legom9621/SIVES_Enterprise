<?php
session_start();
require_once("dbconnect.php");
require_once("SED.php");

// Zona horaria
date_default_timezone_set('America/Bogota');

class PasswordRecovery {
    private $con;

    public function __construct($database) {
        $this->con = $database->getConnection();
    }

    public function processRecovery() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError("Método de solicitud no válido");
            return;
        }

        $email = $this->sanitizeEmail($_POST['email'] ?? '');
        if (!$email) {
            $this->redirectWithError("Correo electrónico no válido");
            return;
        }

        $userData = $this->findUserByEmail($email);
        if (!$userData) {
            $this->redirectWithError("No existe una cuenta con este correo electrónico");
            return;
        }

        // Enviar el correo
        if ($this->sendRecoveryEmail($userData)) {
            $this->redirectWithSuccess("Se ha enviado un correo con su contraseña");
        } else {
            $this->redirectWithError("Error al enviar el correo. Intente nuevamente");
        }
    }

    private function sanitizeEmail($email) {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    private function findUserByEmail($email) {
        try {
            $stmt = $this->con->prepare("
                SELECT id, name, email, password
                FROM users
                WHERE LOWER(email) = :email
                LIMIT 1
            ");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                error_log("No se encontró usuario con el correo: " . $email);
                return false;
            }

            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => $row['password']
            ];
        } catch (Exception $e) {
            error_log("Error DB findUserByEmail: " . $e->getMessage());
            return false;
        }
    }

    private function sendRecoveryEmail($userData) {
        try {
            // ✅ Carga PHPMailer desde /class/
            require_once __DIR__ . '/../class/class.phpmailer.php';
            require_once __DIR__ . '/../class/class.smtp.php';

                $database = Database::getInstance();
                $con = $database->getConnection();

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


            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'error_log';

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = 'mail.liansegurosltda.com';  // Cambia si tu hosting usa otro servidor
            $mail->SMTPAuth = true;
            $mail->Username = 'formularioweb@liansegurosltda.com';
            $mail->Password = 'Lian.seguros1992';
            $mail->SMTPSecure = 'ssl'; // 'tls' o 'ssl' dependiendo del hosting
            $mail->Port = 465; // 465 (SSL) o 587 (TLS)

            // Opcionales
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->setFrom('formularioweb@liansegurosltda.com', 'LIANSEGUROS LTDA');
            $mail->addAddress($userData['email'], $userData['name']);
            $mail->Subject = 'Recuperación de Contraseña - SIVES';
            //$mail->AddEmbeddedImage('../images/logo.png', 'header_jpg','attachment', 'base64', 'image/png');
            $rcss = "../css/styles.css";//ruta de archivo css
                    $fcss = fopen ($rcss, "r");//abrir archivo css
                    $scss = fread ($fcss, filesize ($rcss));//leer contenido de css
                    fclose ($fcss);//cerrar archivo css
            // Desencriptar contraseña
            $password = SED::decryption($userData['password']);
            //$password = $userData['password'];

            // Cuerpo del mensaje
            $mail->Body =  '<!DOCTYPE html>'.
        '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.
            '<link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css" />'.
            '<link rel="stylesheet" href="../css/main.css"/>'.
            '<link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet">'.
        '</head>'.
    
        '<body>
            <div class="container">'.
                '<div class="jumbotron">'.
                    '<div class="row">'.
                            '<div class="col-4 col-12-medium">'.
                                '<span class="image right"><img src="data:image/png;base64,'.$logo.'" alt="Logo" width="15%"/></a></span>'.
                            '</div>'.
                        '<h1 style="color:#1f1857;text-align: center;">Sistema de Vencimientos de Seguros</h1>'.
                        '<h2>Recuperación de Contraseña - SIVES</h2>'.
                        '<p>Hola <strong>' . htmlspecialchars($userData['name']) . '</strong>,</p>'.
                        '<br><p style="text-align: center;">Ha solicitado recordación de su passowrd. </p>'.
                        '<p style="text-align: center;"> Su password es:  <strong>' . htmlspecialchars($password) . '</strong> </p>'.
                        '<br><p>Puedes ingresar al sistema desde: <a href="https://liansegurosltda.com/sives">SIVES</a></p>'. 
                        '<p><em>Si no solicitaste este correo, ignóralo.</em></p>'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</body>'.
    '</html>'; 

            $mail->AltBody = "Hola {$userData['name']}, tu contraseña es: {$password}";

            // Enviar correo
            if ($mail->send()) {
                error_log("Correo enviado a {$userData['email']}");
                return true;
            } else {
                error_log("Error PHPMailer send(): " . $mail->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            error_log("Excepción al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    private function redirectWithError($msg) {
        $_SESSION['recovery_error'] = $msg;
        header("Location: /index.php");
        exit();
    }

    private function redirectWithSuccess($msg) {
        $_SESSION['recovery_success'] = $msg;
        header("Location: /index.php");
        exit();
    }
}

// Ejecutar
try {
    $database = Database::getInstance();
    $recovery = new PasswordRecovery($database);
    $recovery->processRecovery();
} catch (Exception $e) {
    error_log("Error general en sendpw.php: " . $e->getMessage());
    $_SESSION['recovery_error'] = "Error interno del sistema.";
    header("Location: /index.php");
    exit();
}
?>
