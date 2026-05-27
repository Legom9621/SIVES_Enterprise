<?php
function backDb($host, $user, $pass, $dbname, $outputFile)
{
	// Conectar a la base de datos
    $mysqli = new mysqli($host, $user, $pass, $dbname);

    if ($mysqli->connect_error) {
        die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    // Obtener todas las tablas
    $tables = [];
    $result = $mysqli->query('SHOW TABLES');
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $sqlScript = "";

    // Recorrer todas las tablas
    foreach ($tables as $table) {
        // Obtener la estructura de la tabla
        $result = $mysqli->query('SHOW CREATE TABLE ' . $table);
        $row = $result->fetch_row();
        $sqlScript .= "\n\n" . $row[1] . ";\n\n";

        // Obtener los datos de la tabla
        $result = $mysqli->query('SELECT * FROM ' . $table);
        $columnCount = $result->field_count;
        // Obtener los nombres de las columnas
        $fields = $result->fetch_fields();

        // Insertar los datos de cada fila
        while ($row = $result->fetch_row()) {
            $sqlScript .= 'INSERT INTO ' . $table . ' VALUES(';
            for ($i = 0; $i < $columnCount; $i++) {
                $row[$i] = $row[$i] ?? 'NULL'; // Manejar valores NULL
                $row[$i] = $mysqli->real_escape_string($row[$i]);

                // Identificar si el campo es BLOB
                if ($fields[$i]->type == MYSQLI_TYPE_BLOB) {
                    $row[$i] = base64_encode($row[$i]); // Codificar el campo BLOB en base64
                    $sqlScript .= '"' . $row[$i] . '"';
                } else {
                    $row[$i] = $row[$i] ?? 'NULL'; // Manejar valores NULL
                    $row[$i] = $mysqli->real_escape_string($row[$i]);

                    if (is_numeric($row[$i])) {
                        $sqlScript .= $row[$i];
                    } else {
                        $sqlScript .= '"' . $row[$i] . '"';
                    }
                }

                if ($i < ($columnCount - 1)) {
                    $sqlScript .= ', ';
                }
            }
            $sqlScript .= ");\n";
        }

        $sqlScript .= "\n";
    }

    // Guardar en el archivo especificado
    if (!file_put_contents($outputFile, $sqlScript)) {
        return false;
    }

    return true;

	// Save the SQL script to a backup file
	$backup_file_name = $dbname . '_backup.sql';
	$fileHandler = fopen($backup_file_name, 'w+');
	fwrite($fileHandler, $outsql);
	fclose($fileHandler);

	// Download the SQL backup file to the browser
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($backup_file_name));
	ob_clean();
	flush();
	readfile($backup_file_name);
	exec('rm ' . $backup_file_name);
}

$host = 'localhost';
$user = 'root';
$pass = 'Tanedo69';
$dbname = 'db_modular';
$outputFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

if (backDb($host, $user, $pass, $dbname, $outputFile)) {
    echo "<script> alert('Respaldo completado exitosamente:  . $outputFile'); ;window.location= '/php/main.php' </script>";
} else {
    echo "<script> alert('No hay conectividad con la Base de Datos...' . $con->connect_error.'); ;window.location= '/php/main.php' </script>";
}
?>