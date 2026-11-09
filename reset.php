<?php
include 'conexion/conex.php';

$usuario = 'admin';
$password_plana = 'admin123';
$password_encriptada = password_hash($password_plana, PASSWORD_DEFAULT);

$sql = "UPDATE usuarios SET password = '$password_encriptada' WHERE usuario = '$usuario'";

if (mysqli_query($conn, $sql)) {
    echo "<h3>Contraseña actualizada con éxito.</h3>";
    echo "<p>Usuario: <b>$usuario</b></p>";
    echo "<p>Nueva contraseña: <b>$password_plana</b></p>";
    echo "<a href='login.php'>Ir al Login</a>";
} else {
    echo "Error actualizando la contraseña: " . mysqli_error($conn);
}
?>