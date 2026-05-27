<?php
session_start();

// ====================== INCLUDES Y CONFIGURACIÓN ====================== //
require_once("dbconnect.php");
require_once __DIR__ . '/../class/class.phpmailer.php';
require_once __DIR__ . '/../class/class.smtp.php';

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// ====================== CONFIGURACIÓN DE CORREO ====================== //
class MailAutomatico {
    private $con;
    private $mail;
    private $destinatarios = [];
    private $config = [
        'host' => 'mail.liansegurosltda.com',
        'port' => 465,
        'username' => 'notificaciones@liansegurosltda.com',
        'password' => 'Lian.seguros1992',
        'secure' => 'ssl',
        'from_email' => 'notificaciones@liansegurosltda.com',
        'from_name' => 'LIANSEGUROS LTDA',
        'subject' => 'Notificación de Vencimiento - SIVES'
    ];
    
    public function __construct() {
        try {
            // Obtener conexión PDO
            $database = Database::getInstance();
            $this->con = $database->getConnection();
            
            // Inicializar PHPMailer
            $this->mail = new PHPMailer(true);
            $this->configurarMail();
            
            // Configurar destinatarios
            $this->configurarDestinatarios();
            
        } catch (Exception $e) {
            error_log("Error inicializando MailAutomatico: " . $e->getMessage());
            throw new Exception("Error al inicializar el sistema de correo");
        }
    }
    
    private function configurarMail() {
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
        $this->mail->isHTML(true);
        $this->mail->Subject = $this->config['subject'];
    }
    
    private function configurarDestinatarios() {
        // Destinatarios principales
        $this->destinatarios = [
            ['email' => 'gerencia@liansegurosltda.com', 'name' => 'Gerencia'],
            ['email' => 'vivetuseguro@liansegurosltda.com', 'name' => 'Vive tu Seguro'],
            ['email' => 'comercial.mercadeo@liansegurosltda.com', 'name' => 'Comercial y Mercadeo']
        ];
        
        // Agregar destinatarios al correo
        foreach ($this->destinatarios as $destinatario) {
            $this->mail->addAddress($destinatario['email'], $destinatario['name']);
        }
    }
    
    public function obtenerProductosPorVencer() {
        try {
            $sql = "
                SELECT 
                    tb_cliente.name as cliente,
                    tb_agencia.nombre as agencia,
                    tb_seguro.seguro as seguro,
                    tb_tposeguro.tposeguro as tipo,
                    tb_productos.f_inicio,
                    tb_productos.f_fin,
                    tb_productos.estado as stado,
                    tb_productos.nro_poliza as poliza,
                    tb_productos.nrodoc_cliente as documento
                FROM tb_productos
                INNER JOIN tb_cliente ON tb_productos.nrodoc_cliente = tb_cliente.nro_doc
                INNER JOIN tb_agencia ON tb_productos.nrodoc_agencia = tb_agencia.nro_doc
                INNER JOIN tb_seguro ON tb_productos.idtb_seguro = tb_seguro.idtb_seguro
                INNER JOIN tb_tposeguro ON tb_productos.idtb_tposeguro = tb_tposeguro.idtb_tposeguro
                WHERE f_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 10 DAY)
                   OR f_fin < CURDATE()
                GROUP BY tb_productos.idtb_productos
                ORDER BY 
                    CASE 
                        WHEN f_fin < CURDATE() THEN 0 
                        ELSE 1 
                    END,
                    f_fin ASC,
                    tb_cliente.name
            ";
            
            $stmt = $this->con->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al obtener productos por vencer: " . $e->getMessage());
            throw new Exception("Error al consultar la base de datos");
        }
    }
    
    private function calcularDiasVencimiento($fechaFin) {
        $fechaActual = new DateTime();
        $fechaVencimiento = new DateTime($fechaFin);
        
        $diferencia = $fechaActual->diff($fechaVencimiento);
        $dias = $diferencia->days;
        
        if ($diferencia->invert) {
            // Ya venció
            return -$dias;
        } else {
            // Por vencer
            return $dias;
        }
    }
    
    private function generarHTMLTabla($productos) {
        $fechaActual = date('Y-m-d');
        $fecha10Dias = date('Y-m-d', strtotime('+10 days'));

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
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 1000px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #1f1857;
                }
                .header img {
                    max-width: 200px;
                    height: auto;
                }
                .resumen {
                    background-color: #f8f9fa;
                    border-left: 4px solid #1f1857;
                    padding: 15px;
                    margin-bottom: 20px;
                    border-radius: 4px;
                }
                .resumen h3 {
                    color: #1f1857;
                    margin-top: 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                th {
                    background-color: #1f1857;
                    color: white;
                    font-weight: bold;
                    padding: 12px;
                    text-align: left;
                    border: 1px solid #ddd;
                }
                td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    vertical-align: top;
                }
                .vencido {
                    background-color: #ffebee !important;
                    border-left: 4px solid #f44336;
                }
                .por-vencer {
                    background-color: #fff3e0 !important;
                    border-left: 4px solid #ff9800;
                }
                .vigente {
                    background-color: #e8f5e9 !important;
                    border-left: 4px solid #4caf50;
                }
                .badge {
                    display: inline-block;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                    color: white;
                }
                .badge-vencido {
                    background-color: #f44336;
                }
                .badge-proximo {
                    background-color: #ff9800;
                }
                .badge-vigente {
                    background-color: #4caf50;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 14px;
                }
                .estado-col {
                    min-width: 150px;
                }
                @media (max-width: 768px) {
                    table {
                        display: block;
                        overflow-x: auto;
                    }
                    th, td {
                        white-space: nowrap;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <span class="image right"><img src="data:image/png;base64,'.$logo.'" alt="Logo" width="25%"/></a></span>
                <h1 style="color: #1f1857; margin: 20px 0 10px 0;">Sistema de Vencimientos de Seguros - SIVES</h1>
                <h2 style="color: #666; margin: 0 0 20px 0;">Notificación Automática de Vencimientos</h2>
            </div>
            
            <div class="resumen">
                <h3>📊 Resumen de Vencimientos</h3>
                <p><strong>Fecha de generación:</strong> ' . date('d/m/Y H:i:s') . '</p>
                <p><strong>Período analizado:</strong> Desde ' . date('d/m/Y', strtotime($fechaActual)) . ' hasta ' . date('d/m/Y', strtotime($fecha10Dias)) . '</p>
                <p><strong>Total de productos:</strong> ' . count($productos) . '</p>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Aseguradora</th>
                            <th>Seguro</th>
                            <th>Tipo Seguro</th>
                            <th>N° Póliza</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th class="estado-col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $contador = 0;
        $contadorVencidos = 0;
        $contadorProximos = 0;
        
        foreach ($productos as $index => $producto) {
            $contador++;
            $dias = $this->calcularDiasVencimiento($producto['f_fin']);
            
            // Determinar clase CSS y estado
            if ($dias < 0) {
                $clase = 'vencido';
                $estadoTexto = 'VENCIDO';
                $estadoClase = 'badge-vencido';
                $contadorVencidos++;
                $mensajeEstado = 'Vencido hace ' . abs($dias) . ' días';
            } elseif ($dias == 0) {
                $clase = 'por-vencer';
                $estadoTexto = 'VENCE HOY';
                $estadoClase = 'badge-proximo';
                $contadorProximos++;
                $mensajeEstado = 'Vence hoy';
            } elseif ($dias <= 3) {
                $clase = 'por-vencer';
                $estadoTexto = 'PRÓXIMO';
                $estadoClase = 'badge-proximo';
                $contadorProximos++;
                $mensajeEstado = 'Vence en ' . $dias . ' días';
            } else {
                $clase = 'vigente';
                $estadoTexto = 'VIGENTE';
                $estadoClase = 'badge-vigente';
                $mensajeEstado = 'Vence en ' . $dias . ' días';
            }
            
            $html .= '
                        <tr class="' . $clase . '">
                            <td>' . $contador . '</td>
                            <td><strong>' . htmlspecialchars($producto['cliente']) . '</strong><br>
                                <small>Doc: ' . htmlspecialchars($producto['documento']) . '</small></td>
                            <td>' . htmlspecialchars($producto['agencia']) . '</td>
                            <td>' . htmlspecialchars($producto['seguro']) . '</td>
                            <td>' . htmlspecialchars($producto['tipo']) . '</td>
                            <td><strong>' . htmlspecialchars($producto['poliza']) . '</strong></td>
                            <td>' . date('d/m/Y', strtotime($producto['f_inicio'])) . '</td>
                            <td><strong>' . date('d/m/Y', strtotime($producto['f_fin'])) . '</strong></td>
                            <td>
                                <span class="badge ' . $estadoClase . '">' . $estadoTexto . '</span><br>
                                <small>' . $mensajeEstado . '</small>
                            </td>
                        </tr>';
        }
        
        // Si no hay productos
        if (empty($productos)) {
            $html .= '
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <h3 style="color: #666;">✅ No hay productos por vencer en los próximos 10 días</h3>
                                <p>Todos los productos están vigentes o no hay productos registrados.</p>
                            </td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <div class="resumen">
                <h3>📈 Estadísticas</h3>';
        
        if (!empty($productos)) {
            $html .= '
                <p><span class="badge badge-vencido">Vencidos:</span> ' . $contadorVencidos . ' producto(s)</p>
                <p><span class="badge badge-proximo">Por vencer (≤3 días):</span> ' . $contadorProximos . ' producto(s)</p>
                <p><span class="badge badge-vigente">Vigentes:</span> ' . ($contador - $contadorVencidos - $contadorProximos) . ' producto(s)</p>';
        } else {
            $html .= '<p>✅ No se encontraron productos por vencer en el período analizado.</p>';
        }
        
        $html .= '
            </div>
            
            <div class="footer">
                <p><strong>⚠️ Este es un mensaje automático generado por el Sistema SIVES</strong></p>
                <p>Para consultas o actualizaciones, acceda al sistema en: 
                   <a href="https://liansegurosltda.com/sives">https://liansegurosltda.com/sives</a></p>
                <p><strong>Lianseguros Ltda.</strong> &copy; ' . date('Y') . ' - Vive tu Seguro</p>
                <p><small>Si no desea recibir estas notificaciones, contacte al administrador del sistema.</small></p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    public function enviarNotificacion() {
        try {
            // Obtener productos por vencer
            $productos = $this->obtenerProductosPorVencer();
            
            // Generar contenido del correo
            $htmlContent = $this->generarHTMLTabla($productos);
            
            // Configurar cuerpo del correo
            $this->mail->Body = $htmlContent;
            
            // Texto alternativo para clientes de correo sin HTML
            $textoAlternativo = "Notificación de Vencimientos - SIVES\n\n";
            $textoAlternativo .= "Fecha: " . date('d/m/Y H:i:s') . "\n";
            $textoAlternativo .= "Total productos: " . count($productos) . "\n\n";
            
            if (!empty($productos)) {
                $textoAlternativo .= "Productos por vencer/vencidos:\n";
                foreach ($productos as $index => $producto) {
                    $dias = $this->calcularDiasVencimiento($producto['f_fin']);
                    $estado = ($dias < 0) ? "VENCIDO (hace " . abs($dias) . " días)" : "Vence en " . $dias . " días";
                    $textoAlternativo .= ($index + 1) . ". " . $producto['cliente'] . " - " . $producto['poliza'] . 
                                       " (" . $producto['seguro'] . ") - " . $estado . "\n";
                }
            } else {
                $textoAlternativo .= "✅ No hay productos por vencer en los próximos 10 días.\n";
            }
            
            $this->mail->AltBody = $textoAlternativo;
            
            // Agregar imagen embebida
            $rutaLogo = __DIR__ . '/images/logo.png';
            if (file_exists($rutaLogo)) {
                $this->mail->addEmbeddedImage($rutaLogo, 'logo_header');
            }
            
            // Enviar correo
            if ($this->mail->send()) {
                // Registrar envío en log
                error_log("Correo enviado exitosamente a " . count($this->destinatarios) . " destinatarios. Productos: " . count($productos));
                
                return [
                    'success' => true,
                    'message' => 'Correo enviado exitosamente',
                    'destinatarios' => count($this->destinatarios),
                    'productos' => count($productos),
                    'vencidos' => count(array_filter($productos, function($p) {
                        return $this->calcularDiasVencimiento($p['f_fin']) < 0;
                    }))
                ];
                
            } else {
                throw new Exception("Error al enviar correo: " . $this->mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            error_log("Error en enviarNotificacion: " . $e->getMessage());
            throw $e;
        }
    }
}

// ====================== EJECUCIÓN PRINCIPAL ====================== //
try {
    // Verificar si es ejecución manual o automática
    $esEjecucionManual = isset($_GET['manual']) && $_GET['manual'] == 'true';
    
    // Inicializar y enviar correo
    $mailAutomatico = new MailAutomatico();
    $resultado = $mailAutomatico->enviarNotificacion();
    
    if ($esEjecucionManual) {
        // Mostrar resultado si es ejecución manual
        echo json_encode([
            'status' => 'success',
            'data' => $resultado,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Para ejecución automática (cron), solo log
        error_log("Notificación automática enviada: " . json_encode($resultado));
    }
    
} catch (Exception $e) {
    error_log("Error en mailautomatico.php: " . $e->getMessage());
    
    if (isset($esEjecucionManual) && $esEjecucionManual) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al enviar notificación: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
?>