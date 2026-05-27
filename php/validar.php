<?php
session_start();
include_once("dbconnect.php");
include_once("SED.php");
//declaro el uso horario
date_default_timezone_set('America/Bogota');

$servername = "localhost";
$username = "root";
$password = "Tanedo69";
$dbname = "db_modular";
$charset  = 'utf8mb4';

// Create connection
$con = mysqli_connect($servername, $username, $password, $dbname);
mysqli_set_charset($con, $charset);
// Check if connection was successful
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

//Validamos si check esta activado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['user']);
    $password = trim($_POST['password']);
    $recordar = $_POST['checkbox'];  // 1 si está marcado, 0 si no
    // Validamos si existe el usuario
    $queryusuario   =  "SELECT * FROM users WHERE user = ?";
    $stmt = mysqli_prepare($con, $queryusuario);
    // Bind parameters and execute
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $nr = mysqli_num_rows($result);
    if ($nr == 1) {
        $mostrar    = mysqli_fetch_array($result);
        $password1   = $mostrar['password'];
        $passworde = SED::decryption($password1);
        $passworden = SED::encryption($passworde);
        //Se valida que el password conicida con la BD
        if ($password == $passworde) {
            //Se valida si el usuario esta activo
            $estado1 = $mostrar['estado'];

        if ($estado1 == 1) {
                $_SESSION['usr_id'] = $mostrar['id'];
                $_SESSION['usr_name'] = $mostrar['name'];
                $_SESSION['username'] = $mostrar['user'];
                $_SESSION['usr_perfil'] = $mostrar['perfil'];
                $_SESSION['foto'] = $mostrar['foto'];
                $cookieName = "session_" . $mostrar['user'];
                $cookieUserName = "session_" . $mostrar['name'];
                $cookieValue = bin2hex(random_bytes(16));
                $cookieExpire = time() + 1200 ; // 20 Min 1200 seg
                $cookieExpireMySQL = date('Y-m-d H:i:s', $cookieExpire);
                // Guardar la sesión de tiempo de inicio
                $_SESSION['login_time'] = time();
                $user = $mostrar['name'];
                $username = $mostrar['user'];
                $user_id = $mostrar['id'];

            //Se llama función que cierra las sesiones que quedaron abiertas
            cerrarSesionesInactivas($username, $con);

            //Se llama la función de guardar cookies
            guardarCookies($user_id, $cookieName, $cookieValue, $cookieExpireMySQL, $username, $con);

            //Se llama la función de guardar sesión
            guardarSesion($user, $username, $con);



            echo "El Usuario: $user se encuantra activo";
          }else {
            echo  "<script> alert('Los cuenta del Usuario: $user, No esta activada. Comuniquese con el Administrador');window.location= '/php/logout.php' </script>";
          }

        }else {
          echo  "<script> alert('La contraseña digitada no coincide...');window.location= '/php/logout.php' </script>";
        }

    }else {
      echo "<script> alert('El Usuario: $user, No Existe..Comuniquese con el Administrador');window.location= '/php/logout.php' </script>";
    }

}else {
  //
  echo "<script> alert('No ha seleccionado RECORDARME, vuelva a loguearse');window.location= '/php/logout.php'</script>";
}

//Sección de Funciones
//Cierra las conexiones que quedaron abiertas
function cerrarSesionesInactivas($username, $con)
{
  // Suponiendo que tienes una tabla de sesiones con una columna "estado" y "user_id"
  $query = "UPDATE tb_sesion SET fechaout = NOW(), sesion = 1 WHERE username = ? AND  sesion = 0";
  $stmt = $con->prepare($query);
  $stmt->bind_param("s", $username);
  $stmt->execute();
}
//Guarda las Cookies nuevas
function guardarCookies($user_id, $cookieName, $cookieValue, $cookieExpireMySQL, $username, $con) {
    // Crear una cookie que dure 20 min
    setcookie("user_id", $user_id, time() + 1200, "/");
    setcookie("username", $username, time() + 1200, "/");
    $sql = "INSERT INTO tb_cookies (usuario_id, cookie_name, cookie_value, cookie_expire) VALUES (?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->execute([$user_id, $cookieName, $cookieValue, $cookieExpireMySQL]);
}
//Guarda las sesión del usuario
function guardarSesion($user, $username, $con) {
  $check = 1;
  $fechaout = '';
  $queryregistrar = "INSERT INTO tb_sesion(usuario, username, fechaing, fechaout, recordar, sesion)
  values ('$user', '$username', NOW(), '$fechaout', '$check', 0)";
  $result = mysqli_query($con, $queryregistrar) or die(mysqli_error($con));

      // Ejecutar la consulta y verificar si fue exitosa
      if ($result) {
        // Si la inserción es exitosa, redirige a otra página
        echo  "<script> alert('Bienvenido al sistema SIVES usuario: $user');window.location= '/php/main.php'</script>";
        //header("Location: /main.php"); // Redirige a la página siguiente
        exit; // Asegura que la redirección ocurra inmediatamente
      } else {
          echo "Error: " . $result . "<br>" . $con->error;
      }
    // Cerrar la conexión
    $con->close();
}
?>