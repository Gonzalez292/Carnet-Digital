<?php
session_start();
include 'conexion/conex.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $consulta = mysqli_prepare($conn, 'SELECT * FROM usuarios WHERE usuario = ? AND rol = \'Admin\' LIMIT 1');
    mysqli_stmt_bind_param($consulta, 's', $usuario);
    mysqli_stmt_execute($consulta);
    $resultado = mysqli_stmt_get_result($consulta);
    $user_data = mysqli_fetch_assoc($resultado);

    if ($user_data && password_verify($password, $user_data['password'])) {
        $_SESSION['usuario'] = $user_data['usuario'];
        $_SESSION['rol'] = 'Admin';
        $_SESSION['id_estudiante'] = null;

        $id_u = (int) $user_data['id_usuario'];
        mysqli_query($conn, "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = $id_u");

        header('Location: index.php');
        exit;
    }

    $identificacion_login = preg_replace('/[^0-9]/', '', $password);
    if (preg_match('/^[0-9]{10,20}$/', $identificacion_login)) {
        $consulta = mysqli_prepare(
            $conn,
            "SELECT id_estudiante, nombres, apellidos FROM estudiantes
             WHERE REGEXP_REPLACE(numero_identificacion, '[^0-9]', '') = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($consulta, 's', $identificacion_login);
        mysqli_stmt_execute($consulta);
        $resultado = mysqli_stmt_get_result($consulta);
        $estudiante = mysqli_fetch_assoc($resultado);

        $normalizar_nombre = static function ($nombre) {
            $nombre = trim((string) $nombre);
            $nombre_normalizado = preg_replace('/\s+/u', ' ', $nombre);
            if ($nombre_normalizado !== null) {
                $nombre = $nombre_normalizado;
            }
            return function_exists('mb_strtolower') ? mb_strtolower($nombre, 'UTF-8') : strtolower($nombre);
        };

        if ($estudiante && $normalizar_nombre($estudiante['nombres'] . ' ' . $estudiante['apellidos']) === $normalizar_nombre($usuario)) {
            $_SESSION['usuario'] = $estudiante['nombres'] . ' ' . $estudiante['apellidos'];
            $_SESSION['rol'] = 'Estudiante';
            $_SESSION['id_estudiante'] = $estudiante['id_estudiante'];
            header('Location: carnet.php');
            exit;
        }
    }

    $error = "Usuario o contraseña incorrectos. Si eres estudiante, verifica que tu identificación tenga entre 10 y 20 dígitos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - Carnet Promoción Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background-color: #eef3ee;
        }
        .login-layout { position: relative; width: 24rem; max-width: calc(100% - 32px); }
        .escudo-login { position: absolute; top: 50%; left: calc(100% + 24px); width: 250px; height: 250px; transform: translateY(-50%); object-fit: contain; padding: 4px; border: 1px solid #1e4d2b; border-radius: 6px; background-color: #ffffff; }
        .card { background-color: rgba(255, 255, 255, 0.96); border: 2px solid #1e4d2b; border-radius: 8px; }
        .login-card { width: 100%; }
        .card-header { border-bottom: 3px solid #ffcc00; }
        .bg-institucional-verde { background-color: #1e4d2b; color: white; }
        .btn-institucional-amarillo { background-color: #ffcc00; color: #1e4d2b; font-weight: bold; border: 1px solid #1e4d2b; }
        .btn-institucional-amarillo:hover { background-color: #e6b800; color: #1e4d2b; }
        .form-control { border: 1px solid #1e4d2b; }
        .form-control:focus { border-color: #ffcc00; box-shadow: 0 0 0 0.2rem rgba(255, 204, 0, 0.25); }
        @media (max-width: 991.98px) {
            .login-layout { display: flex; flex-direction: column; align-items: center; gap: 16px; }
            .escudo-login { position: static; order: -1; width: 170px; height: 170px; transform: none; }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="login-layout">
        <div class="card shadow login-card">
            <div class="card-header bg-institucional-verde text-center py-4">
                <h4 class="mb-0">Carnet Promoción Social</h4>
                <small class="text-warning">Acceso al Sistema</small>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuario</label>
                        <input type="text" name="usuario" class="form-control" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contraseña</label>
                        <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-institucional-amarillo w-100">Iniciar sesión</button>
                </form>
                <small class="text-muted d-block mt-3">Estudiante: escribe tu nombre completo como usuario y tu identificación de 10 a 20 dígitos como contraseña.</small>
            </div>
            </div>
            <img src="img/escudo.jpg" alt="Escudo institucional" class="escudo-login">
    </div>
</body>
</html>