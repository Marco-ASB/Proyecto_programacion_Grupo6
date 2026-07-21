<?php
session_start();

require_once __DIR__ . '/config/Database.php';

$mensaje = "";
$instrumentos = [];

// 🔹 PROCESAR REGISTRO DE NUEVO INSTRUMENTO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO instrumentos (serie, nombre, categoria, estado, fecha_vencimiento_calibracion) 
            VALUES (:serie, :nombre, :categoria, :estado, :vencimiento)
        ");
        $stmt->execute([
            ':serie'       => trim($_POST['serie']),
            ':nombre'      => trim($_POST['nombre']),
            ':categoria'   => trim($_POST['categoria']),
            ':estado'      => trim($_POST['estado']),
            ':vencimiento' => $_POST['vencimiento']
        ]);
        $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Instrumento registrado correctamente</div>';
    } catch (PDOException $e) {
        $mensaje = '<div class="alert alert-danger">❌ Error al registrar: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// 🔹 OBTENER TODOS LOS INSTRUMENTOS PARA LA TABLA
try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->query("SELECT * FROM instrumentos ORDER BY id DESC");
    $instrumentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $instrumentos = [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Tablas De Instrumentos - Control Internacional Del Ecuador</title>

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
                    <li><a class="dropdown-item" href="#!">Actvidades De Resgistro</a></li>
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
                        <a class="nav-link active" href="instrumentos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>
                            Inventario de Instrumentos
                        </a>

                        <a class="nav-link" href="asignaciones.php">
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



        <!-- ================= CONTENIDO PRINCIPAL ================= -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Tablas De Instrumentos</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Tablas De Instrumentos</li>
                    </ol>

                    <?= $mensaje ?>

               

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-table me-1"></i>Listado de Instrumentos</div>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                                <i class="fas fa-plus"></i> Nuevo Instrumento
                            </button>
                            
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Serie</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Estado</th>
                                        <th>Vencimiento</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($instrumentos)): ?>
                                        <?php foreach ($instrumentos as $inst): ?>
                                            <tr>
                                                <td><?= $inst['id'] ?></td>
                                                <td><code><?= htmlspecialchars($inst['serie']) ?></code></td>
                                                <td><?= htmlspecialchars($inst['nombre']) ?></td>
                                                <td><?= htmlspecialchars($inst['categoria']) ?></td>
                                                <td>
                                                    <span class="badge <?= match ($inst['estado']) {
                                                                            'Disponible' => 'bg-success',
                                                                            'En Uso' => 'bg-warning text-dark',
                                                                            'En Calibracion' => 'bg-info text-dark',
                                                                            default => 'bg-secondary'
                                                                        } ?>">
                                                        <?= htmlspecialchars($inst['estado']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No hay instrumentos registrados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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



    <!-- ================= MODAL NUEVO INSTRUMENTO ================= -->
    <div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <form method="POST" action="">
                    <input type="hidden" name="accion" value="crear">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Registrar Nuevo Instrumento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Número de Serie</label>
                            <input type="text" name="serie" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre del Equipo</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-select" required>
                                <option value="Medidores">Medidores</option>
                                <option value="Detectores">Detectores</option>
                                <option value="Calibradores">Calibradores</option>
                                <option value="END">Equipos END</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado Inicial</label>
                            <select name="estado" class="form-select">
                                <option value="Disponible">Disponible</option>
                                <option value="En Calibracion">En Calibración</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha Vencimiento Calibración</label>
                            <input type="date" name="vencimiento" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= SCRIPTS ================= -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script>
        // ✅ Lógica del botón hamburguesa (Sidebar Toggle)
        window.addEventListener('DOMContentLoaded', event => {
            const sidebarToggle = document.body.querySelector('#sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', event => {
                    event.preventDefault();
                    document.body.classList.toggle('sb-sidenav-toggled');
                    localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
                });
            }
           

            // ✅ Inicialización de DataTables
            const dataTable = document.querySelector("#datatablesSimple");
            if (dataTable) {
                new simpleDatatables.DataTable(dataTable, {
                    searchable: true,
                    sortable: true,
                    perPage: 10,
                    perPageSelect: [5, 10, 15, 20, ["Todos", -1]],
                    labels: {
                        placeholder: "🔍 Buscar instrumento...",
                        perPage: "Registros por página",
                        noRows: "No se encontraron instrumentos",
                        info: "Mostrando {start} a {end} de {rows} registros"
                    }
                });
            }
        
            
        });
    </script>
</body>

</html>