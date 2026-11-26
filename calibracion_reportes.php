<?php
session_start();

require_once __DIR__ . '/models/verificar_sesion.php';
require_once __DIR__ . '/config/Database.php';

$pdo = Database::getInstance()->getConnection();

// 🔹 PROCESAR ACTUALIZACIÓN DE CALIBRACIÓN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_calibracion']) && $_POST['accion_calibracion'] === 'actualizar') {
    try {
        $id = filter_input(INPUT_POST, 'id_instrumento', FILTER_VALIDATE_INT);
        $nuevaFecha = $_POST['nueva_fecha'];

        $stmtUpdate = $pdo->prepare("UPDATE instrumentos SET fecha_vencimiento_calibracion = :fecha WHERE id = :id");
        $stmtUpdate->execute([':fecha' => $nuevaFecha, ':id' => $id]);

        // Recargar la página para actualizar los contadores
        header("Location: calibracion_reportes.php?calibrado=1");
        exit;

    } catch (PDOException $e) {
        $error = "Error al actualizar la fecha: " . $e->getMessage();
    }
}

$instrumentos = [];
$totalInstrumentos = 0;
$countOK = 0;
$countAlerta = 0;
$countCritico = 0;

try {
    // Obtener todos los instrumentos con cálculo de días restantes
    $stmt = $pdo->query("
        SELECT 
            id,
            serie,
            nombre,
            categoria,
            fecha_vencimiento_calibracion,
            DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) as dias_restantes,
            estado
        FROM instrumentos
        ORDER BY dias_restantes ASC
    ");
    $instrumentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalInstrumentos = count($instrumentos);

    // Calcular métricas
    foreach ($instrumentos as $inst) {
        $dias = (int)$inst['dias_restantes'];
        if ($dias <= 7) {
            $countCritico++;
        } elseif ($dias <= 30) {
            $countAlerta++;
        } else {
            $countOK++;
        }
    }
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . htmlspecialchars($e->getMessage());
}

// Exportar a CSV
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_calibracion_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['N° Serie', 'Instrumento', 'Última Calibración', 'Vencimiento', 'Días Restantes', 'Estado']);
    foreach ($instrumentos as $inst) {
        $dias = (int)$inst['dias_restantes'];
        if ($dias <= 7) $estado = 'Crítico';
        elseif ($dias <= 30) $estado = 'Alerta';
        else $estado = 'OK';

        fputcsv($output, [$inst['serie'], $inst['nombre'], $inst['fecha_vencimiento_calibracion'], $inst['fecha_vencimiento_calibracion'], $dias . ' días', $estado]);
    }
    fclose($output);
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Calibración y Reportes - Control Internacional Del Ecuador</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
     <style>
        .sb-topnav {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            border-bottom: 2px solid #343a40;
        }

        #logoAnimado {
            cursor: pointer; 
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            text-decoration: none;
        }

        .icon-container {
            position: relative;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .logo-icon {
            font-size: 1.8rem;
            color: #ffffff;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }
        
        .menu-icon {
            position: absolute;
            font-size: 1.8rem;
            color: #ffffff;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 0;
            transform: rotate(-90deg) scale(0.5);
        }

        #logoAnimado:hover .logo-icon {
            opacity: 0;
            transform: rotate(90deg) scale(0.5);
        }
        
        #logoAnimado:hover .menu-icon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        .texto-logo {
            font-size: 1.3rem; 
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        .sb-topnav .form-control {
            height: 38px;
            font-size: 1rem;
        }
        .sb-topnav .btn-primary {
            height: 38px;
            width: 45px;
        }

        .sb-topnav .nav-link i.fa-user {
            font-size: 1.3rem;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <!-- NAVBAR -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        
        <div class="d-flex align-items-center">
            <a class="navbar-brand d-flex align-items-center m-0" href="#" id="logoAnimado">
                <div class="icon-container">
                    <i class="fas fa-globe-americas logo-icon"></i>
                    <i class="fas fa-bars menu-icon"></i>
                </div>
                <span class="texto-logo">Control Internacional Del Ecuador</span>
            </a>
        </div>

        <!-- Buscador -->
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Buscar..." />
                <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
            </div>
        </form>
        
        <!-- Menú de Usuario -->
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><span class="dropdown-item-text small text-muted">Sesión: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li>
                        <a class="dropdown-item text-danger" href="models/cerrar_sesion.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <!-- SIDEBAR -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link" href="panel.php"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Panel Principal</a>
                        
                        <div class="sb-sidenav-menu-heading">Gestión Operativa</div>
                        <?php if (tienePermiso('instrumentos')): ?>
                        <a class="nav-link" href="instrumentos.php"><div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>Inventario de Instrumentos</a>
                        <?php endif; ?>
                        
                        <?php if (tienePermiso('asignaciones')): ?>
                        <a class="nav-link" href="asignaciones.php"><div class="sb-nav-link-icon"><i class="fas fa-clipboard-check"></i></div>Asignaciones y Concurrencia</a>
                        <?php endif; ?>
                        
                        <?php if (tienePermiso('calibracion_reportes')): ?>
                        <a class="nav-link active" href="calibracion_reportes.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Calibración y Reportes</a>
                        <?php endif; ?>

                        <div class="sb-sidenav-menu-heading">E-commerce </div>
                        <a class="nav-link" href="tienda.php"><div class="sb-nav-link-icon"><i class="fas fa-store"></i></div>Servicio De Inspección</a>
                            <?php if (tienePermiso('pedidos_b2b') || $_SESSION['rol_id'] == 1): ?>
                            <a class="nav-link" href="pedidos.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-shopping-bag"></i></div>
                                Gestión de Pedidos 
                            </a>
<?php endif; ?>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Sesión iniciada como:</div>
                    <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></strong>
                </div>
            </nav>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Control de Calibración y Reportes</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="panel.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Calibración y Reportes</li>
                    </ol>

                    <!-- Métricas de Calibración -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <h5>Total Instrumentos</h5>
                                    <h2 class="mb-0"><?= $totalInstrumentos ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <h5>✅ Calibración OK</h5>
                                    <h2 class="mb-0"><?= $countOK ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">
                                    <h5>⚠️ Próximos a Vencer</h5>
                                    <h2 class="mb-0"><?= $countAlerta ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">
                                    <h5>🔴 Vencidos / Críticos</h5>
                                    <h2 class="mb-0"><?= $countCritico ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Filtros y Exportación -->
                    <div class="card shadow mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="text" id="filtroBusqueda" class="form-control form-control-sm" placeholder=" Buscar por serie o nombre..." style="max-width: 250px" />
                                <select id="filtroEstadoCalibracion" class="form-select form-select-sm" style="max-width: 220px">
                                    <option value="">Todos los estados</option>
                                    <option value="OK">✅ OK (Mayor a 30 días)</option>
                                    <option value="Alerta">⚠️ Alerta (≤30 días)</option>
                                    <option value="Critico">🔴 Crítico (≤7 días o vencido)</option>
                                </select>
                            </div>
                            <a href="?exportar=csv" class="btn btn-sm btn-success">
                                <i class="fas fa-file-csv me-1"></i> Exportar Reporte CSV
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="tablaCalibracion">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>N° Serie</th>
                                            <th>Instrumento</th>
                                            <th>Última Calibración</th>
                                            <th>Vencimiento</th>
                                            <th>Días Restantes</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoTablaCalibracion">
                                        <?php if (!empty($instrumentos)): ?> 
                                            <?php foreach ($instrumentos as $inst): ?> 
                                                <?php 
                                                    $dias = (int)$inst['dias_restantes'];
                                                    if ($dias <= 7) {
                                                        $estado = 'Critico'; $textoEstado = '🔴 Crítico'; $badgeClass = 'bg-danger'; $textoDiasClass = 'text-danger fw-bold';
                                                    } elseif ($dias <= 30) {
                                                        $estado = 'Alerta'; $textoEstado = '⚠️ Alerta'; $badgeClass = 'bg-warning text-dark'; $textoDiasClass = 'text-warning fw-bold';
                                                    } else {
                                                        $estado = 'OK'; $textoEstado = '✅ OK'; $badgeClass = 'bg-success'; $textoDiasClass = '';
                                                    } 
                                                ?>
                                                <tr data-estado="<?= $estado ?>">
                                                    <td><code><?= htmlspecialchars($inst['serie']) ?></code></td>
                                                    <td><?= htmlspecialchars($inst['nombre']) ?></td>
                                                    <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                                                    <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                                                    <td class="<?= $textoDiasClass ?>"><?= $dias ?> días</td>
                                                    <td><span class="badge <?= $badgeClass ?>"><?= $textoEstado ?></span></td>
                                                    <td>
                                                        <!-- Botón con los datos para el JS -->
                                                        <button class="btn btn-sm btn-outline-primary btn-ver-calibracion" 
                                                                data-id="<?= $inst['id'] ?>"
                                                                data-serie="<?= htmlspecialchars($inst['serie']) ?>"
                                                                data-nombre="<?= htmlspecialchars($inst['nombre']) ?>"
                                                                data-fecha="<?= $inst['fecha_vencimiento_calibracion'] ?>">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?> 
                                        <?php else: ?>
                                            <tr><td colspan="7" class="text-center text-muted">No hay instrumentos registrados</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; UAE 2026</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL VER CALIBRACIÓN -->
    <div class="modal fade" id="modalCalibracion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="accion_calibracion" value="actualizar">
                    <input type="hidden" name="id_instrumento" id="calib_id">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Detalles de Calibración</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Instrumento:</label>
                            <p class="form-control-plaintext" id="calib_nombre"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Serie:</label>
                            <p class="form-control-plaintext" id="calib_serie"></p>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label text-primary fw-bold">Nueva Fecha de Vencimiento</label>
                            <input type="date" name="nueva_fecha" id="calib_fecha" class="form-control" required>
                            <small class="text-muted">Al guardar, el estado del equipo se reiniciará a "OK".</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Calibración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    
    <!-- Tu archivo JS externo -->
    <script src="public/js/calibracion_reportes.js"></script>

    <!-- Script del Sidebar y Filtros (Incrustado) -->
    <script>
        window.addEventListener("DOMContentLoaded", event => {
            const sidebarToggle = document.body.querySelector("#sidebarToggle");
            if (sidebarToggle) {
                sidebarToggle.addEventListener("click", event => {
                    event.preventDefault();
                    document.body.classList.toggle("sb-sidenav-toggled");
                });
            }
        });

        // Filtrado en tiempo real
        document.addEventListener("DOMContentLoaded", function() {
            const filtroBusqueda = document.getElementById("filtroBusqueda");
            const filtroEstado = document.getElementById("filtroEstadoCalibracion");
            const tabla = document.getElementById("tablaCalibracion");
            const filas = tabla.querySelectorAll("tbody tr[data-estado]");

            function aplicarFiltros() {
                const texto = filtroBusqueda.value.toLowerCase();
                const estado = filtroEstado.value;

                filas.forEach((fila) => {
                    const serie = fila.cells[0].textContent.toLowerCase();
                    const nombre = fila.cells[1].textContent.toLowerCase();
                    const estadoFila = fila.dataset.estado;

                    const coincideTexto = serie.includes(texto) || nombre.includes(texto);
                    const coincideEstado = estado === "" || estadoFila === estado;

                    fila.style.display = coincideTexto && coincideEstado ? "" : "none";
                });
            }
            filtroBusqueda.addEventListener("input", aplicarFiltros);
            filtroEstado.addEventListener("change", aplicarFiltros);
        });
    </script>
        <script>
        window.addEventListener("DOMContentLoaded", event => {
            const logoAnimado = document.body.querySelector("#logoAnimado");
            
            if (logoAnimado) {
                logoAnimado.addEventListener("click", function(event) {
                    event.preventDefault(); 
                    document.body.classList.toggle("sb-sidenav-toggled");
                    localStorage.setItem("sb|sidebar-toggle", document.body.classList.contains("sb-sidenav-toggled"));
                });
            }
        });
    </script>    
</body>
</html>
