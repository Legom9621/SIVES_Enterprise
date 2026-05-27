<?php
session_start();

// ====================== INCLUDES Y CONFIGURACIÓN ====================== //
require_once("dbconnect.php");
require_once __DIR__ . '/../class/class.phpmailer.php';
require_once __DIR__ . '/../class/class.smtp.php';

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// ====================== CLASE PARA ENVÍO DE CUMPLEAÑOS ====================== //
class MailCumpleanos {
    private $con;
    private $mail;
    private $config = [
        'host' => 'mail.liansegurosltda.com',
        'port' => 465,
        'username' => 'notificaciones@liansegurosltda.com',
        'password' => 'Lian.seguros1992',
        'secure' => 'ssl',
        'from_email' => 'notificaciones@liansegurosltda.com',
        'from_name' => 'LIANSEGUROS LTDA'
    ];
    
    private $estadisticas = [
        'total_clientes' => 0,
        'enviados' => 0,
        'errores' => 0,
        'detalles' => []
    ];
    
    public function __construct() {
        try {
            // Obtener conexión PDO
            $database = Database::getInstance();
            $this->con = $database->getConnection();
            
            // Inicializar PHPMailer
            $this->mail = new PHPMailer(true);
            
        } catch (Exception $e) {
            error_log("Error inicializando MailCumpleanos: " . $e->getMessage());
            throw new Exception("Error al inicializar el sistema de correo de cumpleaños");
        }
    }
    
    private function configurarMail($destinatarioEmail, $destinatarioNombre) {
        $this->mail->clearAllRecipients();
        $this->mail->clearAttachments();
        $this->mail->clearCustomHeaders();
        
        $this->mail->CharSet = 'UTF-8';
        $this->mail->SMTPDebug = 0;
        $this->mail->isSMTP();
        $this->mail->Host = $this->config['host'];
        $this->mail->Port = $this->config['port'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->config['username'];
        $this->mail->Password = $this->config['password'];
        $this->mail->SMTPSecure = $this->config['secure'];
        $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
        $this->mail->addAddress($destinatarioEmail, $destinatarioNombre);
        $this->mail->isHTML(true);
    }
    
    public function obtenerClientesCumpleaneros() {
        try {
            // Consulta optimizada para obtener clientes que cumplen años hoy
            $sql = "
SELECT 
    idtb_cliente as id,
    name as cliente,
    mail as correo,
    date as fecha_nacimiento,
    -- Convertir a fecha real
    STR_TO_DATE(date, '%d/%m/%Y') as fecha_nacimiento_real,
    -- Calcular edad
    TIMESTAMPDIFF(YEAR, STR_TO_DATE(date, '%d/%m/%Y'), CURDATE()) as edad_actual,
    -- Calcular próximo cumpleaños
    DATE_ADD(
        STR_TO_DATE(CONCAT(
            YEAR(CURDATE()), 
            '-', 
            DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%m'), 
            '-', 
            DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%d')
        ), '%Y-%m-%d'),
        INTERVAL 
            CASE 
                WHEN DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')
                THEN 1 
                ELSE 0 
            END 
        YEAR
    ) as proximo_cumple,
    -- Días para el próximo cumpleaños
    DATEDIFF(
        DATE_ADD(
            STR_TO_DATE(CONCAT(
                YEAR(CURDATE()), 
                '-', 
                DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%m'), 
                '-', 
                DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%d')
            ), '%Y-%m-%d'),
            INTERVAL 
                CASE 
                    WHEN DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')
                    THEN 1 
                    ELSE 0 
                END 
            YEAR
        ),
        CURDATE()
    ) as dias_para_cumple
FROM tb_cliente 
WHERE 
    -- Validar formato de fecha
    date REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
    -- Clientes que cumplen en los próximos 7 días (incluyendo hoy)
    AND DATEDIFF(
        DATE_ADD(
            STR_TO_DATE(CONCAT(
                YEAR(CURDATE()), 
                '-', 
                DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%m'), 
                '-', 
                DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%d')
            ), '%Y-%m-%d'),
            INTERVAL 
                CASE 
                    WHEN DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')
                    THEN 1 
                    ELSE 0 
                END 
            YEAR
        ),
        CURDATE()
    ) = 0
    AND mail IS NOT NULL 
    AND mail != ''
    AND mail LIKE '%@%.%'
    -- Validación básica de email
    AND mail REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'
ORDER BY 
    dias_para_cumple ASC,
    name;
            ";
            // <!--BETWEEN 0 AND 15-->
            $stmt = $this->con->prepare($sql);
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->estadisticas['total_clientes'] = count($clientes);
            
            return $clientes;
            
        } catch (PDOException $e) {
            error_log("Error al obtener clientes cumpleañeros: " . $e->getMessage());
            throw new Exception("Error al consultar la base de datos de clientes");
        }
    }
    
    private function generarHTMLCumpleanos($cliente) {
        $edad = isset($cliente['edad']) && $cliente['edad'] > 0 ? $cliente['edad'] : '';
        $edadTexto = $edad ? "cumpliendo $edad años" : "en tu día especial";
        
               // OBTENER LOGO E ICONO DE LA COMPAÑIA DESDE LA BASE DE DATOS

    $database = Database::getInstance();
    $con = $database->getConnection();

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

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body {
                    font-family: "Arial", "Helvetica", sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    padding: 20px 0;
                    background: linear-gradient(135deg, #1f1857 0%, #4a3f97 100%);
                    border-radius: 10px 10px 0 0;
                    margin: -20px -20px 30px -20px;
                }
                .header img {
                    max-width: 150px;
                    height: auto;
                }
                .header h1 {
                    color: white;
                    margin: 15px 0 0 0;
                    font-size: 24px;
                }
                .celebrations {
                    text-align: center;
                    margin: 30px 0;
                }
                .celebrations img {
                    max-width: 250px;
                    height: auto;
                    border-radius: 15px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                }
                .message {
                    background-color: #fff9e6;
                    border-left: 5px solid #ffd700;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 5px;
                }
                .saludo {
                    font-family: "Brush Script MT", cursive;
                    font-size: 28px;
                    color: #1f1857;
                    text-align: center;
                    margin: 20px 0;
                }
                .nombre-cliente {
                    color: #d32f2f;
                    font-weight: bold;
                    font-size: 20px;
                }
                .empresa-nombre {
                    font-family: "Impact", "Arial", sans-serif;
                    font-size: 24px;
                    color: #1f1857;
                    text-align: center;
                    margin: 20px 0;
                }
                .wishes {
                    text-align: center;
                    font-size: 18px;
                    color: #1f1857;
                    margin: 25px 0;
                    padding: 15px;
                    background-color: #e8f4fd;
                    border-radius: 8px;
                }
                .firma {
                    border-top: 2px dashed #ddd;
                    padding-top: 20px;
                    margin-top: 30px;
                    text-align: right;
                }
                .firma strong {
                    color: #1f1857;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 14px;
                }
                .footer a {
                    color: #1f1857;
                    text-decoration: none;
                    font-weight: bold;
                }
                .footer a:hover {
                    text-decoration: underline;
                }
                .badge {
                    display: inline-block;
                    background-color: #ff6b6b;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 20px;
                    font-size: 14px;
                    margin-left: 10px;
                }
                @media (max-width: 640px) {
                    .container {
                        margin: 10px;
                        padding: 15px;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .saludo {
                        font-size: 24px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <span class="image right"><img src="data:image/png;base64,'.$logo.'" alt="Logo" width="25%"/></a></span>
                    <h1>¡Feliz Cumpleaños! 🎉</h1>
                </div>
                
                <div class="saludo">
                    <h2>¡FELIZ CUMPLEAÑOS, <span class="nombre-cliente">' . htmlspecialchars($cliente['cliente']) . '</span>! 🎂</h2>
                </div>
                
                <div class="celebrations">
                    <img src="cid:cumple_header" alt="¡Feliz Cumpleaños!">
                </div>
                
                <div class="message">
                    <p>Querido/a <strong>' . htmlspecialchars($cliente['cliente']) . '</strong>,</p>
                    
                    <p>En este día tan especial en el que estás ' . $edadTexto . ', todo el equipo de 
                    <span class="empresa-nombre">LIANSEGUROS LTDA</span> quiere enviarte nuestras más 
                    sinceras y cálidas felicitaciones. 🎈</p>
                    
                    <p>Tu confianza en nosotros es el mayor regalo que podríamos recibir, y estamos 
                    profundamente agradecidos por permitirnos ser parte de tu vida y proteger lo que 
                    más valoras.</p>
                    
                    <p>Que este nuevo año de vida esté lleno de bendiciones, alegrías compartidas, 
                    momentos inolvidables junto a tus seres queridos y, por supuesto, mucha salud 
                    y prosperidad.</p>
                </div>
                
                <div class="wishes">
                    <p>✨ <strong>¡Que todos tus sueños y metas se hagan realidad!</strong> ✨</p>
                    <p>🌟 <strong>¡Que la felicidad te acompañe hoy y siempre!</strong> 🌟</p>
                </div>
                
                <div class="firma">
                    <p>Con cariño y admiración,</p>
                    <p><strong>Joan Sebastian Gomez González</strong><br>
                    <em>CEO - Lianseguros Ltda.</em></p>
                </div>
                
                <div class="footer">
                    <p><strong>Este mensaje fue enviado automáticamente por el Sistema SIVES</strong></p>
                    <p>¿Deseas actualizar tus datos o preferencias de contacto?<br>
                    Visítanos en: <a href="https://liansegurosltda.com/">https://liansegurosltda.com/</a></p>
                    <p><strong>Lianseguros Ltda.</strong> &copy; ' . date('Y') . ' - Vive tu Seguro</p>
                    <p><small>Si no deseas recibir estos mensajes, puedes contactar a nuestro equipo de atención al cliente.</small></p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function generarTextoAlternativo($cliente) {
        $edad = isset($cliente['edad']) && $cliente['edad'] > 0 ? $cliente['edad'] . ' años' : 'tu día especial';
        
        $texto = "¡FELIZ CUMPLEAÑOS!\n\n";
        $texto .= "Estimado/a " . $cliente['cliente'] . ",\n\n";
        $texto .= "En este día tan especial en el que estás cumpliendo " . $edad . ", todo el equipo de LIANSEGUROS LTDA ";
        $texto .= "quiere enviarte nuestras más sinceras felicitaciones.\n\n";
        $texto .= "Tu confianza en nosotros es el mayor regalo que podríamos recibir, y estamos profundamente ";
        $texto .= "agradecidos por permitirnos ser parte de tu vida y proteger lo que más valoras.\n\n";
        $texto .= "Que este nuevo año de vida esté lleno de bendiciones, alegrías compartidas, momentos ";
        $texto .= "inolvidables junto a tus seres queridos y, por supuesto, mucha salud y prosperidad.\n\n";
        $texto .= "¡Que todos tus sueños y metas se hagan realidad!\n";
        $texto .= "¡Que la felicidad te acompañe hoy y siempre!\n\n";
        $texto .= "Atentamente,\n";
        $texto .= "Joan Sebastian Gomez González\n";
        $texto .= "CEO - Lianseguros Ltda.\n\n";
        $texto .= "---\n";
        $texto .= "Este mensaje fue enviado automáticamente por el Sistema SIVES\n";
        $texto .= "Lianseguros Ltda. © " . date('Y') . " - Vive tu Seguro";
        
        return $texto;
    }
    
    private function agregarImagenes() {
        try {
            // Logo de la empresa
            $rutaLogo = __DIR__ . '/images/logo.png';
            if (file_exists($rutaLogo)) {
                $this->mail->addEmbeddedImage($rutaLogo, 'logo_header');
            } else {
                error_log("Logo no encontrado en: " . $rutaLogo);
            }
            
            // Imagen de cumpleaños
            $rutaCumple = __DIR__ . '/images/happy.jpg';
            if (file_exists($rutaCumple)) {
                $this->mail->addEmbeddedImage($rutaCumple, 'cumple_header');
            } else {
                // Usar imagen alternativa si no existe
                $rutaCumpleAlt = __DIR__ . '/images/cumpleanos.jpg';
                if (file_exists($rutaCumpleAlt)) {
                    $this->mail->addEmbeddedImage($rutaCumpleAlt, 'cumple_header');
                } else {
                    error_log("Imagen de cumpleaños no encontrada");
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al agregar imágenes: " . $e->getMessage());
            return false;
        }
    }
    
    public function enviarFelicitaciones() {
        try {
            // Obtener clientes que cumplen años hoy
            $clientes = $this->obtenerClientesCumpleaneros();
            
            if (empty($clientes)) {
                $this->estadisticas['detalles'][] = [
                    'tipo' => 'info',
                    'mensaje' => 'No hay clientes que cumplan años hoy'
                ];
                return $this->estadisticas;
            }
            
            foreach ($clientes as $cliente) {
                try {
                    // Configurar mail para cada cliente
                    $this->configurarMail($cliente['correo'], $cliente['cliente']);
                    
                    // Asunto personalizado
                    $asunto = "¡Feliz Cumpleaños, " . $cliente['cliente'] . "! 🎉 - Lianseguros Ltda.";
                    $this->mail->Subject = $asunto;
                    
                    // Generar contenido
                    $htmlContent = $this->generarHTMLCumpleanos($cliente);
                    $textoAlternativo = $this->generarTextoAlternativo($cliente);
                    
                    $this->mail->Body = $htmlContent;
                    $this->mail->AltBody = $textoAlternativo;
                    
                    // Agregar imágenes
                    $this->agregarImagenes();
                    
                    // Enviar correo
                    if ($this->mail->send()) {
                        $this->estadisticas['enviados']++;
                        $this->estadisticas['detalles'][] = [
                            'tipo' => 'success',
                            'cliente' => $cliente['cliente'],
                            'email' => $cliente['correo'],
                            'mensaje' => 'Correo enviado exitosamente',
                            'fecha_nacimiento' => $cliente['fecha_nacimiento'],
                            'edad' => $cliente['edad'] ?? 'N/A'
                        ];
                        
                        error_log("Correo de cumpleaños enviado a: " . $cliente['cliente'] . " (" . $cliente['correo'] . ")");
                        
                    } else {
                        $this->estadisticas['errores']++;
                        $this->estadisticas['detalles'][] = [
                            'tipo' => 'error',
                            'cliente' => $cliente['cliente'],
                            'email' => $cliente['correo'],
                            'mensaje' => 'Error al enviar: ' . $this->mail->ErrorInfo
                        ];
                        
                        error_log("Error enviando correo a " . $cliente['correo'] . ": " . $this->mail->ErrorInfo);
                    }
                    
                } catch (Exception $e) {
                    $this->estadisticas['errores']++;
                    $this->estadisticas['detalles'][] = [
                        'tipo' => 'exception',
                        'cliente' => $cliente['cliente'],
                        'email' => $cliente['correo'],
                        'mensaje' => 'Excepción: ' . $e->getMessage()
                    ];
                    
                    error_log("Excepción enviando a " . $cliente['correo'] . ": " . $e->getMessage());
                }
                
                // Pequeña pausa entre envíos para no sobrecargar el servidor SMTP
                usleep(100000); // 0.1 segundo
            }
            
            return $this->estadisticas;
            
        } catch (Exception $e) {
            error_log("Error en enviarFelicitaciones: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getResumenHTML() {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Resumen Envío Cumpleaños</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .container { max-width: 800px; margin: 0 auto; }
                .header { background: #1f1857; color: white; padding: 20px; border-radius: 5px; }
                .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
                .stat-card { background: #f5f5f5; padding: 15px; border-radius: 5px; text-align: center; }
                .stat-number { font-size: 24px; font-weight: bold; }
                .success { color: #28a745; }
                .error { color: #dc3545; }
                .info { color: #17a2b8; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                .table th { background: #f8f9fa; }
                .success-row { background-color: #d4edda; }
                .error-row { background-color: #f8d7da; }
                .info-row { background-color: #d1ecf1; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📧 Resumen de Envío de Felicitaciones de Cumpleaños</h1>
                    <p>Fecha: ' . date('d/m/Y H:i:s') . '</p>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number">' . $this->estadisticas['total_clientes'] . '</div>
                        <div>Clientes encontrados</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-number">' . $this->estadisticas['enviados'] . '</div>
                        <div>Correos enviados</div>
                    </div>
                    <div class="stat-card error">
                        <div class="stat-number">' . $this->estadisticas['errores'] . '</div>
                        <div>Errores</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-number">' . date('d/m/Y') . '</div>
                        <div>Fecha de envío</div>
                    </div>
                </div>';
        
        if (!empty($this->estadisticas['detalles'])) {
            $html .= '
                <h2>📋 Detalle de Envíos</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Fecha Nac.</th>
                            <th>Mensaje</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($this->estadisticas['detalles'] as $index => $detalle) {
                $claseFila = '';
                if ($detalle['tipo'] === 'success') $claseFila = 'success-row';
                elseif ($detalle['tipo'] === 'error') $claseFila = 'error-row';
                elseif ($detalle['tipo'] === 'info') $claseFila = 'info-row';
                
                $html .= '
                        <tr class="' . $claseFila . '">
                            <td>' . ($index + 1) . '</td>
                            <td>' . htmlspecialchars($detalle['cliente'] ?? 'N/A') . '</td>
                            <td>' . htmlspecialchars($detalle['email'] ?? 'N/A') . '</td>
                            <td>' . ($detalle['tipo'] === 'success' ? '✅ Enviado' : 
                                    ($detalle['tipo'] === 'error' ? '❌ Error' : 
                                    ($detalle['tipo'] === 'exception' ? '⚠️ Excepción' : 'ℹ️ Info'))) . '</td>
                            <td>' . ($detalle['fecha_nacimiento'] ?? 'N/A') . '</td>
                            <td>' . htmlspecialchars($detalle['mensaje']) . '</td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>';
        }
        
        $html .= '
                <div style="margin-top: 30px; padding: 15px; background: #e8f4fd; border-radius: 5px;">
                    <h3>📝 Notas</h3>
                    <p>• Este proceso se ejecutó automáticamente desde el Sistema SIVES</p>
                    <p>• Los correos se envían diariamente a las 8:00 AM</p>
                    <p>• Para actualizar datos de clientes, acceda al sistema SIVES</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}

// ====================== EJECUCIÓN PRINCIPAL ====================== //
try {
    // Verificar modo de ejecución
    $esEjecucionManual = isset($_GET['manual']) && $_GET['manual'] == 'true';
    $mostrarResumen = isset($_GET['resumen']) && $_GET['resumen'] == 'true';
    
    // Inicializar y enviar felicitaciones
    $mailCumpleanos = new MailCumpleanos();
    $resultado = $mailCumpleanos->enviarFelicitaciones();
    
    if ($esEjecucionManual || $mostrarResumen) {
        if ($mostrarResumen) {
            // Mostrar resumen HTML detallado
            echo $mailCumpleanos->getResumenHTML();
        } else {
            // Mostrar resultado JSON para ejecución manual
            echo json_encode([
                'status' => 'success',
                'data' => $resultado,
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Proceso de felicitaciones completado'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    } else {
        // Para ejecución automática (cron), solo log
        error_log("Felicitaciones de cumpleaños enviadas: " . 
                 "Total: {$resultado['total_clientes']}, " .
                 "Enviados: {$resultado['enviados']}, " .
                 "Errores: {$resultado['errores']}");
        
        // Si no hay clientes, mostrar mensaje amigable
        if ($resultado['total_clientes'] == 0) {
            if ($esEjecucionManual) {
                echo '<script type="text/javascript">
                    alert("No hay clientes que cumplan años hoy.");
                    window.location.href = "/main.php";
                </script>';
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error en mailhappy.php: " . $e->getMessage());
    
    if (isset($esEjecucionManual) && $esEjecucionManual) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al enviar felicitaciones: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    } else {
        echo '<script type="text/javascript">
            alert("Error en el sistema de felicitaciones. Por favor, contacte al administrador.");
        </script>';
    }
}
?>