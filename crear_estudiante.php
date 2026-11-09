<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION['rol'] ?? '') !== 'Admin') {
    header("Location: carnet.php");
    exit;
}
include 'conexion/conex.php';

// Conexión a la base de datos (Asegúrate de incluir tu archivo de conexión previamente, por ejemplo: include("conexion.php");)

$id_estudiante = "";
$identificacion = "";
$nombres = "";
$apellidos = "";
$rh = "";
$grado = "";
$curso = "";
$codigo_unico = "";
$ruta_fotografia = "";
$vigencia = date("Y");
$estado = "Activo";
$es_editar = false;
$mensaje_error = "";

// Cargar datos si es edición
if (isset($_GET['editar'])) {
    $es_editar = true;
    $id_estudiante = intval($_GET['editar']);
    $res = mysqli_query($conn, "SELECT * FROM estudiantes WHERE id_estudiante = $id_estudiante");
    if ($res && mysqli_num_rows($res) > 0) {
        $est = mysqli_fetch_assoc($res);
        $identificacion = $est['numero_identificacion'];
        $nombres = $est['nombres'];
        $apellidos = $est['apellidos'];
        $rh = $est['rh'];
        $grado = $est['grado'];
        $curso = $est['curso'];
        $codigo_unico = $est['codigo_unico'];
        $ruta_fotografia = $est['ruta_fotografia'] ?? '';
        $vigencia = $est['vigencia'];
        $estado = $est['estado'];
    }
}

// Envío del Formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificacion_ingresada = trim($_POST['numero_identificacion'] ?? '');
    if (!preg_match('/^[0-9]{10,20}$/', $identificacion_ingresada)) {
        $mensaje_error = "El número de identificación debe tener entre 10 y 20 dígitos.";
    }
    $identificacion = mysqli_real_escape_string($conn, $identificacion_ingresada);
    $nombres = mysqli_real_escape_string($conn, $_POST['nombres']);
    $apellidos = mysqli_real_escape_string($conn, $_POST['apellidos']);
    $rh = mysqli_real_escape_string($conn, $_POST['rh']);
    $grado = mysqli_real_escape_string($conn, $_POST['grado']);
    $curso = mysqli_real_escape_string($conn, $_POST['curso']);
    $codigo_unico = mysqli_real_escape_string($conn, $_POST['codigo_unico']);
    $vigencia = intval($_POST['vigencia']);
    $estado = mysqli_real_escape_string($conn, $_POST['estado']);

    $foto_nueva = '';
    $archivo_foto = $_FILES['fotografia'] ?? null;
    if (empty($mensaje_error) && $archivo_foto && $archivo_foto['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($archivo_foto['error'] !== UPLOAD_ERR_OK) {
            $mensaje_error = "No se pudo recibir la fotografía. Inténtalo de nuevo.";
        } elseif ($archivo_foto['size'] > 5 * 1024 * 1024) {
            $mensaje_error = "La fotografía no puede superar los 5 MB.";
        } else {
            $tipos_permitidos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $tipo_foto = $finfo->file($archivo_foto['tmp_name']);

            if (!isset($tipos_permitidos[$tipo_foto])) {
                $mensaje_error = "El archivo debe ser una imagen JPG, PNG, GIF o WebP.";
            } else {
                $nombre_foto = bin2hex(random_bytes(16)) . '.' . $tipos_permitidos[$tipo_foto];
                $destino_foto = __DIR__ . '/img/' . $nombre_foto;
                if (move_uploaded_file($archivo_foto['tmp_name'], $destino_foto)) {
                    $ruta_fotografia = $nombre_foto;
                    $foto_nueva = $destino_foto;
                } else {
                    $mensaje_error = "No se pudo guardar la fotografía en el servidor.";
                }
            }
        }
    }
    
    // Generación de un token hash único para el QR
    $qr_token = hash('sha256', $identificacion . time());
    $ruta_fotografia_sql = mysqli_real_escape_string($conn, $ruta_fotografia);

    if (empty($mensaje_error) && $es_editar) {
        $id_estudiante = intval($_POST['id_estudiante']);
        $sql = "UPDATE estudiantes SET 
                numero_identificacion = '$identificacion', 
                nombres = '$nombres', 
                apellidos = '$apellidos', 
                rh = '$rh', 
                grado = '$grado', 
                curso = '$curso', 
                codigo_unico = '$codigo_unico', 
                ruta_fotografia = '$ruta_fotografia_sql',
                vigencia = $vigencia, 
                estado = '$estado' 
                WHERE id_estudiante = $id_estudiante";
    } elseif (empty($mensaje_error)) {
        $sql = "INSERT INTO estudiantes (numero_identificacion, nombres, apellidos, rh, grado, curso, codigo_unico, ruta_fotografia, qr_token, vigencia, estado) 
                VALUES ('$identificacion', '$nombres', '$apellidos', '$rh', '$grado', '$curso', '$codigo_unico', '$ruta_fotografia_sql', '$qr_token', $vigencia, '$estado')";
    }

    if (empty($mensaje_error)) {
        if (mysqli_query($conn, $sql)) {
            header("Location: index.php");
            exit;
        }
        if ($foto_nueva !== '') {
            unlink($foto_nueva);
        }
        $mensaje_error = "Error al procesar la solicitud: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $es_editar ? 'Editar' : 'Agregar' ?> Estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bg-institucional-verde { background-color: #1e4d2b !important; color: white; }
        .btn-institucional-amarillo { background-color: #ffcc00; color: #1e4d2b; font-weight: bold; border: none; }
        .btn-institucional-amarillo:hover { background-color: #e6b800; }
        .foto-actual { display: block; width: 100px; height: 100px; margin-top: 10px; object-fit: cover; border: 2px solid #1e4d2b; border-radius: 6px; }
    </style>
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow" style="max-width: 800px; margin: auto;">
            <div class="card-header bg-institucional-verde text-center py-3">
                <h3 class="mb-0"><?= $es_editar ? 'Editar' : 'Agregar' ?> Estudiante</h3>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert alert-danger"><?= $mensaje_error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" action="crear_estudiante.php<?= $es_editar ? '?editar='.$id_estudiante : '' ?>">
                    <?php if ($es_editar): ?>
                        <input type="hidden" name="id_estudiante" value="<?= $id_estudiante ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Número Identificación</label>
                            <input type="text" name="numero_identificacion" class="form-control" value="<?= htmlspecialchars($identificacion) ?>" inputmode="numeric" minlength="10" maxlength="20" pattern="[0-9]{10,20}" title="Ingresa entre 10 y 20 dígitos" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Código Único Institucional</label>
                            <input type="text" name="codigo_unico" class="form-control" value="<?= htmlspecialchars($codigo_unico) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombres</label>
                            <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($nombres) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($apellidos) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">RH</label>
                            <input type="text" name="rh" class="form-control" value="<?= htmlspecialchars($rh) ?>" required placeholder="Ej: O+">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Grado</label>
                            <input type="text" name="grado" class="form-control" value="<?= htmlspecialchars($grado) ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Curso</label>
                            <input type="text" name="curso" class="form-control" value="<?= htmlspecialchars($curso) ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Vigencia</label>
                            <input type="number" name="vigencia" class="form-control" value="<?= htmlspecialchars($vigencia) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="fotografia" class="form-label fw-bold">Fotografía del estudiante</label>
                        <input type="file" name="fotografia" id="fotografia" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <small class="form-text text-muted">Formatos JPG, PNG, GIF o WebP. Máximo 5 MB.</small>
                        <?php if (!empty($ruta_fotografia)): ?>
                            <?php $foto_actual = preg_match('/^https?:\/\//i', $ruta_fotografia) ? $ruta_fotografia : 'img/' . ltrim($ruta_fotografia, '/\\'); ?>
                            <img src="<?= htmlspecialchars($foto_actual, ENT_QUOTES, 'UTF-8') ?>" alt="Fotografía actual del estudiante" class="foto-actual">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="Activo" <?= $estado == 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= $estado == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-institucional-amarillo">Guardar Estudiante</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>