<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion/conex.php';

if ($_SESSION['rol'] === 'Estudiante') {
    $id_estudiante = (int) ($_SESSION['id_estudiante'] ?? 0);
} elseif ($_SESSION['rol'] === 'Admin') {
    $id_estudiante = (int) ($_GET['id'] ?? 0);
} else {
    http_response_code(403);
    exit('No tienes permiso para ver este carnet.');
}
$vista_previa_admin = $_SESSION['rol'] === 'Admin' && ($_GET['vista'] ?? '') === 'previa';

if ($id_estudiante < 1) {
    http_response_code(404);
    exit('Esta cuenta no tiene un estudiante asociado. Contacta al administrador.');
}

$consulta = mysqli_prepare($conn, 'SELECT * FROM estudiantes WHERE id_estudiante = ? LIMIT 1');
mysqli_stmt_bind_param($consulta, 'i', $id_estudiante);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
$estudiante = mysqli_fetch_assoc($resultado);

if (!$estudiante) {
    http_response_code(404);
    exit('Estudiante no encontrado.');
}

$qr_token = trim((string) $estudiante['qr_token']);
$qr_nombre = hash('sha256', $qr_token) . '.png';
$qr_archivo = __DIR__ . '/qr/' . $qr_nombre;
if ($qr_token !== '' && !is_file($qr_archivo)) {
    require_once __DIR__ . '/assets/phpqrcode/qrlib.php';
    QRcode::png($qr_token, $qr_archivo, QR_ECLEVEL_M, 5, 2);
}

$nombre_completo = htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos'], ENT_QUOTES, 'UTF-8');
$ruta_foto = trim((string) $estudiante['ruta_fotografia']);
if ($ruta_foto === '') {
    $foto = 'https://via.placeholder.com/110x130?text=Sin+Foto';
} elseif (preg_match('/^https?:\/\//i', $ruta_foto)) {
    $foto = $ruta_foto;
} else {
    $foto = 'img/' . ltrim($ruta_foto, '/\\');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnet Estudiantil - <?= $nombre_completo ?></title>
    <!-- Incluimos Bootstrap local -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        :root {
            --verde-institucional: #1e4d2b;
            --verde-oscuro: #14351d;
            --amarillo-institucional: #ffcc00;
        }

        body {
            background-color: #ffffff;
        }

        /* Contenedor tipo tarjeta/carnet */
        .carnet-card {
            width: 350px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            background: #ffffff;
            border: 1px solid var(--verde-institucional);
            margin: 30px auto;
            position: relative;
        }

        /* Encabezado del Colegio */
        .carnet-header {
            background: var(--verde-institucional);
            color: white;
            padding: 15px;
            text-align: center;
        }

        .carnet-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .carnet-header p {
            margin: 0;
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .imagenes-carnet {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 15px auto 10px;
        }

        /* Foto del Estudiante */
        .foto-contenedor {
            width: 110px;
            height: 130px;
            margin: 0;
            border-radius: 10px;
            overflow: hidden;
            border: 3px solid var(--verde-institucional);
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .foto-estudiante {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .escudo-carnet {
            width: 95px;
            height: 110px;
            object-fit: contain;
            padding: 4px;
            border: 1px solid var(--verde-institucional);
            border-radius: 6px;
            background-color: #ffffff;
        }

        /* Cuerpo de información */
        .carnet-body {
            padding: 0 20px 15px 20px;
        }

        .nombre-estudiante {
            font-size: 1.15rem;
            font-weight: bold;
            color: #212529;
            text-align: center;
            margin-bottom: 2px;
        }

        .codigo-estudiante {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .info-grid {
            background-color: #ffffff;
            border: 1px solid var(--amarillo-institucional);
            border-radius: 8px;
            padding: 10px;
            font-size: 0.85rem;
        }

        .qr-estudiante {
            display: block;
            width: 110px;
            height: 110px;
            margin: 12px auto 0;
            image-rendering: pixelated;
        }

        .info-item {
            margin-bottom: 4px;
        }

        .info-label {
            font-weight: bold;
            color: #495057;
        }

        /* Pie del Carnet */
        .carnet-footer {
            background-color: var(--amarillo-institucional);
            padding: 10px 15px;
            border-top: 1px solid var(--verde-institucional);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge-estado {
            font-size: 0.75rem;
            padding: 4px 8px;
        }

        .estado-activo,
        .btn-carnet {
            background-color: var(--verde-institucional);
            color: #ffffff;
        }

        .estado-inactivo,
        .btn-salir {
            background-color: var(--amarillo-institucional);
            color: var(--verde-oscuro);
        }

        .btn-carnet,
        .btn-salir {
            border: 1px solid var(--verde-institucional);
        }

        .btn-carnet:hover {
            background-color: var(--verde-oscuro);
            color: #ffffff;
        }

        .btn-salir:hover {
            background-color: #e6b800;
            color: var(--verde-oscuro);
        }

        /* Estilos de impresión */
        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .carnet-card {
                box-shadow: none;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="container my-4">

    <!-- Botón para imprimir el carnet -->
    <div class="d-flex justify-content-center gap-2 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-carnet">Imprimir / Guardar Carnet</button>
        <?php if (!$vista_previa_admin): ?>
            <a href="logout.php" class="btn btn-salir">Cerrar sesión</a>
        <?php endif; ?>
    </div>

    <!-- TARJETA DEL CARNET ESTUDIANTIL -->
    <div class="carnet-card">
        
        <!-- Encabezado Institucional -->
        <div class="carnet-header">
            <h5>Promoción Social</h5>
            <p>Carnet Estudiantil Versión <?= htmlspecialchars((string) $estudiante['vigencia'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <!-- Fotografía del Estudiante -->
        <div class="imagenes-carnet">
            <div class="foto-contenedor">
                <img src="<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>" alt="Foto Estudiante" class="foto-estudiante" onerror="this.src='https://via.placeholder.com/110x130?text=Sin+Foto';">
            </div>
            <img src="img/escudo.jpg" alt="Escudo institucional" class="escudo-carnet">
        </div>

        <!-- Información Principal -->
        <div class="carnet-body">
            <div class="nombre-estudiante">
                <?= $nombre_completo ?>
            </div>
            <div class="codigo-estudiante">
                CÓDIGO: <?= htmlspecialchars((string) $estudiante['codigo_unico'], ENT_QUOTES, 'UTF-8') ?>
            </div>

            <!-- Datos de la Tabla Estudiantes -->
            <div class="info-grid">
                <div class="row info-item">
                    <div class="col-6"><span class="info-label">Identificación:</span></div>
                    <div class="col-6 text-end"><?= htmlspecialchars((string) $estudiante['numero_identificacion'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="row info-item">
                    <div class="col-6"><span class="info-label">Grado / Curso:</span></div>
                    <div class="col-6 text-end"><?= htmlspecialchars($estudiante['grado'] . ' - ' . $estudiante['curso'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="row info-item">
                    <div class="col-6"><span class="info-label">Factor RH:</span></div>
                    <div class="col-6 text-end"><span class="badge text-dark" style="background-color: var(--amarillo-institucional);"><?= htmlspecialchars((string) $estudiante['rh'], ENT_QUOTES, 'UTF-8') ?></span></div>
                </div>
            </div>
            <?php if ($qr_token !== '' && is_file($qr_archivo)): ?>
                <img src="<?= htmlspecialchars('qr/' . $qr_nombre, ENT_QUOTES, 'UTF-8') ?>" alt="Código QR de <?= $nombre_completo ?>" class="qr-estudiante">
            <?php endif; ?>
        </div>

        <!-- Pie de página y Estado -->
        <div class="carnet-footer">
            <div>
                <small class="text-muted d-block">Vigencia: <strong><?= htmlspecialchars((string) $estudiante['vigencia'], ENT_QUOTES, 'UTF-8') ?></strong></small>
            </div>
            <div>
                <?php if ($estudiante['estado'] == 'Activo'): ?>
                    <span class="badge estado-activo badge-estado">Activo</span>
                <?php else: ?>
                    <span class="badge estado-inactivo badge-estado">Inactivo</span>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

</body>
</html>