<?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "carnet_promocion_social";

    $conn = mysqli_connect($host, $user, $pass, $db);

    if (!$conn) {
        die("Error de conexión: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
?>