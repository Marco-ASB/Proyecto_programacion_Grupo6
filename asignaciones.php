<?php
session_start();
require_once __DIR__ . '/config/Database.php'; // Ajusta la ruta si tu config está en otro lugar

$mensaje = "";
$historial = [];
$instrumentosDisponibles = [];

// 1. OBTENER DATOS INICIALES (Select de instrumentos y Tabla de historial)
try {
    $pdo = Database::getInstance()->getConnection();

    // Instrumentos disponibles para el dropdown
    $stmtInst = $pdo->query("SELECT id, serie, nombre FROM instrumentos WHERE estado = 'Disponible' ORDER BY serie ASC");
    $instrumentosDisponibles = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

    // Historial de asignaciones (con JOIN para mostrar nombre del instrumento)
    $stmtHist = $pdo->query("
        SELECT a.id, i.serie, i.nombre as instrumento_nombre, a.tecnico, a.fecha_salida, a.fecha_devolucion_est, a.estado
        FROM asignaciones a
        JOIN instrumentos i ON a.instrumento_id = i.id
        ORDER BY a.id DESC
    ");
    $historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En caso de error, las variables quedan vacías y la tabla mostrará "Sin registros"
}

// 2. PROCESAR FORMULARIO DE ASIGNACIÓN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instrumentoId = filter_input(INPUT_POST, 'instrumento_id', FILTER_VALIDATE_INT);
    $tecnico = trim($_POST['tecnico'] ?? '');
    $proyecto = trim($_POST['proyecto'] ?? '');
    $fechaSalida = $_POST['fecha_salida'] ?? '';
    $fechaDevolucion = $_POST['fecha_devolucion'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');

    if ($instrumentoId && $tecnico && $proyecto && $fechaSalida && $fechaDevolucion) {
        try {
            $pdo = Database::getInstance()->getConnection();
            $pdo->beginTransaction(); // Inicio de transacción ACID

            // Bloqueo de fila para evitar concurrencia real
            $stmtCheck = $pdo->prepare("SELECT estado FROM instrumentos WHERE id = :id FOR UPDATE");
            $stmtCheck->execute([':id' => $instrumentoId]);
            $instActual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$instActual || $instActual['estado'] !== 'Disponible') {
                throw new Exception("Conflicto: Este instrumento acaba de ser asignado por otro usuario. Intente nuevamente.");
            }

            // Registrar asignación
            $stmtInsert = $pdo->prepare("
                INSERT INTO asignaciones (instrumento_id, tecnico, proyecto, fecha_salida, fecha_devolucion_est, observaciones, estado, creado_en)
                VALUES (:instId, :tecnico, :proyecto, :salida, :devolucion, :obs, 'Activa', NOW())
            ");
            $stmtInsert->execute([
                ':instId' => $instrumentoId,
                ':tecnico' => $tecnico,
                ':proyecto' => $proyecto,
                ':salida' => $fechaSalida,
                ':devolucion' => $fechaDevolucion,
                ':obs' => $observaciones
            ]);

            // Actualizar estado del instrumento a "En Uso"
            $stmtUpdate = $pdo->prepare("UPDATE instrumentos SET estado = 'En Uso' WHERE id = :id");
            $stmtUpdate->execute([':id' => $instrumentoId]);

            $pdo->commit(); // Confirmar cambios
            $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Asignación registrada correctamente. El instrumento ha sido bloqueado.</div>';

            // Redirigir para limpiar POST y recargar datos actualizados
            header("Location: asignaciones.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack(); // Deshacer cambios si falla
            $mensaje = '<div class="alert alert-danger alert-dismissible fade show" role="alert">️ ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-warning">Por favor, complete todos los campos obligatorios.</div>';
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Asignaciones y Concurrencia - Control Internacional Del Ecuador</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">


    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">Control Internacional <br />Del Ecuador</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Buscar" />
                <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#!">Configuración</a></li>
                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="#!">Salir</a></li>
                </ul>
            </li>
        </ul>
    </nav>


    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Panel Principal
                        </a>

                        <div class="sb-sidenav-menu-heading">Gestión Operativa</div>
                        <a class="nav-link " href="instrumentos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>
                            Inventario de Instrumentos
                        </a>

                        <a class="nav-link active" href="asignaciones.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-clipboard-check"></i></div>
                            Asignaciones y Concurrencia
                        </a>

                        <a class="nav-link" href="calibracion_reportes.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>
                            Calibración y Reportes
                        </a>

                        <div class="sb-sidenav-menu-heading">Administración</div>
                        <a class="nav-link" href="login.html">
                            <div class="sb-nav-link-icon"><i class="fas fa-sign-in-alt"></i></div>
                            Iniciar Sesión (Demo)
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Proyecto Academico:</div>
                    Grupo 6
                </div>
            </nav>
        </div>




        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Gestión de Asignaciones</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Asignaciones</li>
                    </ol>

                    <!-- Mensajes de éxito/error -->
                    <?= $mensaje ?>

                    <div class="row">
                        <!-- Columna Izquierda: Formulario -->
                        <div class="col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-hand-holding me-2"></i>Nueva Asignación
                                </div>
                                <div class="card-body">
                                    <form id="formAsignacion" method="POST" action="">
                                        <div class="mb-3">
                                            <label class="form-label">Instrumento a Asignar</label>
                                            <select class="form-select" id="selectInstrumento" name="instrumento_id" required>
                                                <option value="">Seleccione un instrumento disponible...</option>
                                                <?php foreach ($instrumentosDisponibles as $inst): ?>
                                                    <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['serie']) ?> - <?= htmlspecialchars($inst['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted" id="infoInstrumento"></small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Técnico Responsable</label>
                                            <input type="text" class="form-control" name="tecnico" placeholder="Nombre o Cédula" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Proyecto / Cliente</label>
                                            <input type="text" class="form-control" name="proyecto" placeholder="Ej: Inspección Refinería Esmeraldas" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label class="form-label">Fecha Salida</label>
                                                <input type="datetime-local" class="form-control" name="fecha_salida" id="fechaSalida" required>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="form-label">Fecha Devolución Est.</label>
                                                <input type="datetime-local" class="form-control" name="fecha_devolucion" id="fechaDevolucion" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Observaciones de Estado</label>
                                            <textarea class="form-control" name="observaciones" rows="3" placeholder="Estado físico al retirar..."></textarea>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check-circle me-2"></i>Confirmar Asignación
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Historial -->
                        <div class="col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-history me-2"></i>Historial de Movimientos
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="tablaHistorial">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Instrumento</th>
                                                    <th>Técnico</th>
                                                    <th>Salida</th>
                                                    <th>Devolución Est.</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($historial)): ?>
                                                    <?php foreach ($historial as $h): ?>
                                                        <tr>
                                                            <td><strong>#<?= $h['id'] ?></strong></td>
                                                            <td><?= htmlspecialchars($h['instrumento_nombre']) ?></td>
                                                            <td><?= htmlspecialchars($h['tecnico']) ?></td>
                                                            <td><?= $h['fecha_salida'] ?></td>
                                                            <td><?= $h['fecha_devolucion_est'] ?></td>
                                                            <td><span class="badge <?= $h['estado'] === 'Activa' ? 'bg-warning text-dark' : 'bg-success' ?>"><?= htmlspecialchars($h['estado']) ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No hay asignaciones registradas</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; UAE 2026</div>
                        <div>
                            <a href="#">Politica de Privacidad</a>
                            &middot;
                            <a href="#">Terminos &amp; Condiciones</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>

    <script>
        // Validación de fechas (Frontend UX)
        document.getElementById('fechaSalida').addEventListener('change', function() {
            document.getElementById('fechaDevolucion').min = this.value;
        });
    </script>
</body>

</html>