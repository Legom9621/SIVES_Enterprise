<?php
date_default_timezone_set('America/Bogota');
class Database {
    private static $instance = null;
    private $connection;

    // ⚙️ Configura aquí tus parámetros locales o del hosting
    private $host     = 'localhost';
    private $dbname   = 'db_modular'; // <-- cámbialo por el real
    private $username = 'root';              // o el usuario de hosting
    private $password = 'Tanedo69';                  // o la contraseña del hosting
    private $charset  = 'utf8mb4';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Muestra errores
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch ordenado
                PDO::ATTR_EMULATE_PREPARES => false, // Evita inyección SQL
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log y mensaje controlado
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("Error al conectar con la base de datos. Contacte al administrador.");
        }
    }

    // Patrón Singleton
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
?>
