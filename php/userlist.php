<?php
// userlist.php
require_once("dbconnect.php");
require_once("SED.php");

date_default_timezone_set('America/Bogota');

function obtenerUsuariosInactivos($con) {
    try {
        // Consulta SQL con PDO
        $sql = "SELECT * FROM users WHERE estado = 2";
        $stmt = $con->prepare($sql);
        $stmt->execute();

        // Verificar si hay resultados
        $usuarios = [];
        if ($stmt) {
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $usuarios;
    } catch (PDOException $e) {
        error_log("Error en obtenerUsuariosInactivos: " . $e->getMessage());
        return [];
    }
}

function obtenerUsuariosActivos($con) {
    try {
        // Consulta SQL con PDO
        $sql = "SELECT * FROM users WHERE estado = 1";
        $stmt = $con->prepare($sql);
        $stmt->execute();

        // Verificar si hay resultados
        $usuarios = [];
        if ($stmt) {
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $usuarios;
    } catch (PDOException $e) {
        error_log("Error en obtenerUsuariosActivos: " . $e->getMessage());
        return [];
    }
}
?>