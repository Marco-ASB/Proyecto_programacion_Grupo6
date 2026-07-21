<?php
session_start();
require_once __DIR__ . '/config/Database.php';

// Inicializar variables
$totalInstrumentos = 0;
$disponibles = 0;
$enUso = 0;
$calibrados = 0;
$porVencer = 0;
$alertas = [];
$ultimosInstrumentos = [];

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Total de instrumentos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrumentos");
    $totalInstrumentos = $stmt->fetch()['total'];
    
    // Instrumentos disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrumentos WHERE estado = 'Disponible'");
    $disponibles = $stmt->fetch()['total'];
    
    // Instrumentos en uso
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrumentos WHERE estado = 'En Uso'");
    $enUso = $stmt->fetch()['total'];
    
    // Instrumentos calibrados (OK - mayor a 30 días)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM instrumentos 
        WHERE DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) > 30
    ");
    $calibrados = $stmt->fetch()['total'];
    
    // Por vencer (≤30 días)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM instrumentos 
        WHERE DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) BETWEEN 1 AND 30
    ");
    $porVencer = $stmt->fetch()['total'];
    
    // Alertas de calibración (críticos - ≤7 días o vencidos)
    $stmt = $pdo->query("
        SELECT serie, nombre, fecha_vencimiento_calibracion,
               DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) as dias_restantes
        FROM instrumentos
        WHERE DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) <= 7
        ORDER BY dias_restantes ASC
        LIMIT 5
    ");
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimos instrumentos registrados
    $stmt = $pdo->query("
        SELECT serie, nombre, categoria, estado, fecha_vencimiento_calibracion
        FROM instrumentos
        ORDER BY id DESC
        LIMIT 5
    ");
    $ultimosInstrumentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Si hay error, las variables quedan en 0
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
    <title>Inicio - Control Internacional Del Ecuador</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <a class="navbar-brand ps-3" href="index.php">Control Internacional <br />
            Del Ecuador
        </a>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Navbar Search-->
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Buscar" aria-label="Search for..."
                    aria-describedby="btnNavbarSearch" />
                <button class="btn btn-primary" id="btnNavbarSearch" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
        <!-- Navbar-->
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"
                    aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
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
                        <!-- Módulo Principal -->
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link active" href="index.php">
                            <div class="sb-nav-link-icon">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            Panel Principal
                        </a>

                        <!-- Módulo 2: Inventario de Instrumentos -->
                        <div class="sb-sidenav-menu-heading">Gestión Operativa</div>
                        <a class="nav-link" href="instrumentos.php">
                            <div class="sb-nav-link-icon">
                                <i class="fas fa-toolbox"></i>
                            </div>
                            Inventario de Instrumentos
                        </a>

                        <!-- Módulo 3: Asignaciones -->
                        <a class="nav-link" href="asignaciones.php">
                            <div class="sb-nav-link-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            Asignaciones y Concurrencia
                        </a>


                        <a class="nav-link" href="calibracion_reportes.php">
                            <div class="sb-nav-link-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            Calibración y Reportes
                        </a>


                        <div class="sb-sidenav-menu-heading">Administración</div>
                        <a class="nav-link" href="login.php">
                            <div class="sb-nav-link-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
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
                 <div class="container-fluid px-4">
                    <h1 class="mt-4">Panel de Gestión de Instrumentos</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Panel Principal</li>
                    </ol>

                    <!-- Métricas Principales -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"> Disponibles</h5>
                                            <h2 class="mb-0"><?= $disponibles ?></h2>
                                        </div>
                                        <i class="fas fa-check-circle fa-3x opacity-25"></i>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="instrumentos.php">Ver Inventario</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"> En Uso</h5>
                                            <h2 class="mb-0"><?= $enUso ?></h2>
                                        </div>
                                        <i class="fas fa-tools fa-3x opacity-25"></i>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="asignaciones.php">Ver Asignaciones</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">✅ Calibrados</h5>
                                            <h2 class="mb-0"><?= $calibrados ?></h2>
                                        </div>
                                        <i class="fas fa-certificate fa-3x opacity-25"></i>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="calibracion_reportes.php">Ver Certificados</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">⚠️ Por Vencer</h5>
                                            <h2 class="mb-0"><?= $porVencer ?></h2>
                                        </div>
                                        <i class="fas fa-exclamation-triangle fa-3x opacity-25"></i>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="calibracion_reportes.php">Ver Alertas</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas de Calibración -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-danger text-white">
                                    <i class="fas fa-bell me-2"></i>Alertas de Calibración Próximas
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($alertas)): ?>
                                        <div class="alert-list">
                                            <?php foreach ($alertas as $alerta): ?>
                                                <div class="alert alert-<?= $alerta['dias_restantes'] <= 0 ? 'danger' : 'warning' ?> alert-dismissible fade show mb-2" role="alert">
                                                    <strong><?= htmlspecialchars($alerta['serie']) ?></strong> - <?= htmlspecialchars($alerta['nombre']) ?>
                                                    <br>
                                                    <small>Vence: <?= $alerta['fecha_vencimiento_calibracion'] ?> (<?= $alerta['dias_restantes'] ?> días)</small>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i>No hay alertas de calibración pendientes
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Últimos Instrumentos -->
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-clock me-2"></i>Últimos Instrumentos Registrados
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Serie</th>
                                                    <th>Nombre</th>
                                                    <th>Categoría</th>
                                                    <th>Estado</th>
                                                    <th>Vencimiento</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($ultimosInstrumentos)): ?>
                                                    <?php foreach ($ultimosInstrumentos as $inst): ?>
                                                        <tr>
                                                            <td><code><?= htmlspecialchars($inst['serie']) ?></code></td>
                                                            <td><?= htmlspecialchars($inst['nombre']) ?></td>
                                                            <td><?= htmlspecialchars($inst['categoria']) ?></td>
                                                            <td>
                                                                <span class="badge <?= match($inst['estado']) {
                                                                    'Disponible' => 'bg-success',
                                                                    'En Uso' => 'bg-warning text-dark',
                                                                    'En Calibracion' => 'bg-info text-dark',
                                                                    default => 'bg-secondary'
                                                                } ?>">
                                                                    <?= htmlspecialchars($inst['estado']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="5" class="text-center text-muted">No hay instrumentos registrados</td></tr>
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
    <script src="public/js/scripts.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
</body>

</html>