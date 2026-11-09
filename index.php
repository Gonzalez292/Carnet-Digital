<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['rol'] !== 'Admin') {
    header("Location: carnet.php");
    exit;
}
include 'conexion/conex.php';

// Eliminar Estudiante (Operación DELETE lógica o física)
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    // Eliminar físico para el PMV (puede cambiarse por actualización de estado)
    mysqli_query($conn, "DELETE FROM estudiantes WHERE id_estudiante = $id_eliminar");
    header("Location: index.php");
    exit;
}

// Búsqueda
$buscar = "";
if (isset($_GET['buscar'])) {
    $buscar = mysqli_real_escape_string($conn, $_GET['buscar']);
}

$grado_filtro = mysqli_real_escape_string($conn, trim($_GET['grado'] ?? ''));
$curso_filtro = mysqli_real_escape_string($conn, trim($_GET['curso'] ?? ''));

// Consulta de estudiantes
$sql = "SELECT * FROM estudiantes";
$condiciones = [];
if (!empty($buscar)) {
    $condiciones[] = "(nombres LIKE '%$buscar%' OR apellidos LIKE '%$buscar%' OR numero_identificacion LIKE '%$buscar%')";
}
if ($grado_filtro !== '') {
    $condiciones[] = "grado = '$grado_filtro'";
}
if ($curso_filtro !== '') {
    $condiciones[] = "curso = '$curso_filtro'";
}
if ($condiciones) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
$resultado = mysqli_query($conn, $sql);

$grados = mysqli_query($conn, "SELECT DISTINCT grado FROM estudiantes WHERE grado IS NOT NULL AND grado <> '' ORDER BY grado");
$sql_cursos = "SELECT DISTINCT curso FROM estudiantes WHERE curso IS NOT NULL AND curso <> ''";
if ($grado_filtro !== '') {
    $sql_cursos .= " AND grado = '$grado_filtro'";
}
$sql_cursos .= ' ORDER BY curso';
$cursos = mysqli_query($conn, $sql_cursos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Carnet Promoción Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background-color: #eef3ee;
        }
        .bg-institucional-verde { background-color: #1e4d2b !important; color: white; border-bottom: 3px solid #ffcc00; }
        .text-institucional-verde { color: #1e4d2b; }
        .bg-institucional-amarillo { background-color: #ffcc00 !important; color: #1e4d2b; font-weight: bold; }
        .btn-institucional-amarillo { background-color: #ffcc00; color: #1e4d2b; font-weight: bold; border: 1px solid #1e4d2b; }
        .btn-institucional-amarillo:hover { background-color: #e6b800; }
        .btn-institucional-verde { background-color: #1e4d2b; color: white; border: 1px solid #ffcc00; }
        .btn-institucional-verde:hover { background-color: #14351d; color: white; }
        .sidebar { background-color: rgba(248, 249, 250, 0.88); min-height: 100vh; border-right: 2px solid #1e4d2b; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        .form-control { border: 1px solid #1e4d2b; }
        .form-control:focus { border-color: #ffcc00; box-shadow: 0 0 0 0.2rem rgba(255, 204, 0, 0.25); }
        .tabla-estudiantes { border: 2px solid #1e4d2b; border-radius: 8px; overflow: hidden; background-color: rgba(255, 255, 255, 0.96); }
        .escudo-agregar { width: 150px; height: 150px; object-fit: contain; padding: 3px; border: 1px solid #1e4d2b; border-radius: 6px; background-color: #ffffff; }
        .modal-carnet .modal-content { border: 2px solid #1e4d2b; border-radius: 8px; overflow: hidden; }
        .modal-carnet .modal-header { background-color: #1e4d2b; color: #ffffff; border-bottom: 3px solid #ffcc00; }
        .modal-carnet-frame { display: block; width: 100%; height: min(78vh, 760px); border: 0; background-color: #ffffff; }
        .sidebar-section-title { margin: 18px 0 8px; color: #1e4d2b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .sidebar-filter { white-space: normal; text-align: left; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-institucional-verde py-3">
        <div class="container-fluid">
            <span class="navbar-brand text-white fw-bold fs-4">Carnet de Promoción Social</span>
            <div class="ms-auto text-white">
                Bienvenido, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong> (<?= $_SESSION['rol'] ?>) | 
                <a href="logout.php" class="text-warning text-decoration-none fw-bold ms-2">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-3 sidebar">
                <a href="index.php" class="btn btn-institucional-verde w-100 mb-2 py-2">Inicio</a>
                <div class="sidebar-section-title">Por grado</div>
                <?php if ($grados): ?>
                    <?php while ($fila_grado = mysqli_fetch_assoc($grados)): ?>
                        <a href="index.php?grado=<?= rawurlencode($fila_grado['grado']) ?>" class="btn btn-outline-dark sidebar-filter w-100 mb-2 <?= $grado_filtro === $fila_grado['grado'] && $curso_filtro === '' ? 'active' : '' ?>">
                            Grado <?= htmlspecialchars($fila_grado['grado']) ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>

                <div class="sidebar-section-title">Por curso</div>
                <?php if ($cursos): ?>
                    <?php while ($fila_curso = mysqli_fetch_assoc($cursos)): ?>
                        <a href="index.php?grado=<?= rawurlencode($grado_filtro) ?>&amp;curso=<?= rawurlencode($fila_curso['curso']) ?>" class="btn btn-outline-dark sidebar-filter w-100 mb-2 <?= $curso_filtro === $fila_curso['curso'] ? 'active' : '' ?>">
                            Curso <?= htmlspecialchars($fila_curso['curso']) ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <h2 class="fw-bold text-institucional-verde">Gestión de Estudiantes</h2>
                    <div class="d-flex align-items-center gap-2">
                        <img src="img/escudo.jpg" alt="Escudo institucional" class="escudo-agregar">
                        <a href="crear_estudiante.php" class="btn btn-institucional-amarillo py-2 px-3">+ Agregar Estudiante</a>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" action="index.php" class="d-flex gap-2">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o identificación..." value="<?= htmlspecialchars($buscar) ?>">
                            <?php if ($grado_filtro !== ''): ?>
                                <input type="hidden" name="grado" value="<?= htmlspecialchars($grado_filtro) ?>">
                            <?php endif; ?>
                            <?php if ($curso_filtro !== ''): ?>
                                <input type="hidden" name="curso" value="<?= htmlspecialchars($curso_filtro) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-institucional-verde">Buscar</button>
                            <?php if (!empty($buscar)): ?>
                                <a href="index.php" class="btn btn-secondary">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Estudiantes -->
                <div class="card shadow-sm tabla-estudiantes">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-institucional-verde">
                                <tr class="text-white">
                                    <th>ID</th>
                                    <th>Identificación</th>
                                    <th>Estudiante</th>
                                    <th>Curso / Grado</th>
                                    <th>Vigencia</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($resultado) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                                        <tr>
                                            <td class="fw-bold text-muted"><?= $row['id_estudiante'] ?></td>
                                            <td><?= htmlspecialchars($row['numero_identificacion']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php 
                                                    $foto = !empty($row['ruta_fotografia']) ? $row['ruta_fotografia'] : 'https://via.placeholder.com/40';
                                                    ?>
                                                    <img src="img/<?= $foto ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) ?></div>
                                                        <small class="text-muted">RH: <?= htmlspecialchars($row['rh']) ?> | Cód: <?= htmlspecialchars($row['codigo_unico']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Grado <?= htmlspecialchars($row['grado']) ?> - <?= htmlspecialchars($row['curso']) ?></td>
                                            <td><?= htmlspecialchars($row['vigencia']) ?></td>
                                            <td>
                                                <?php if ($row['estado'] == 'Activo'): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#carnetModal" data-carnet-url="carnet.php?id=<?= (int) $row['id_estudiante'] ?>&amp;vista=previa">Ver carnet</button>
                                                    <a href="crear_estudiante.php?editar=<?= $row['id_estudiante'] ?>" class="btn btn-institucional-verde">Editar</a>
                                                    <a href="index.php?eliminar=<?= $row['id_estudiante'] ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de eliminar este estudiante?')">Eliminar</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No se encontraron estudiantes.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal fade modal-carnet" id="carnetModal" tabindex="-1" aria-labelledby="carnetModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="carnetModalLabel">Carnet del estudiante</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body p-0">
                                <iframe id="carnetPreview" class="modal-carnet-frame" title="Vista previa del carnet"></iframe>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        const carnetModal = document.getElementById('carnetModal');
        const carnetPreview = document.getElementById('carnetPreview');

        carnetModal.addEventListener('show.bs.modal', (event) => {
            carnetPreview.src = event.relatedTarget.getAttribute('data-carnet-url');
        });

        carnetModal.addEventListener('hidden.bs.modal', () => {
            carnetPreview.src = 'about:blank';
        });
    </script>

    <!-- Footer -->
    <footer class="bg-institucional-verde text-center py-3 mt-5">
        <small class="text-white">Carnet de Promoción Social © 2026 - Todos los derechos reservados</small>
    </footer>

</body>
</html>