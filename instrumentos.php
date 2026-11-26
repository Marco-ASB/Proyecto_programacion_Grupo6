<?php
session_start();

require_once __DIR__ . '/models/verificar_sesion.php';
require_once __DIR__ . '/models/Instrumento.php'; // Traemos el modelo

$mensaje = "";

// 🔹 PROCESAR REGISTRO (POST CREAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    try {
        $pdo = Database::getInstance()->getConnection();
        $fecha_calibracion = !empty($_POST['vencimiento']) ? $_POST['vencimiento'] : null;
        $fecha_devolucion  = !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null;
        $fecha_disponible  = !empty($_POST['fecha_disponible']) ? $_POST['fecha_disponible'] : null;

        $stmt = $pdo->prepare("
            INSERT INTO instrumentos (serie, nombre, categoria, estado, fecha_vencimiento_calibracion, fecha_devolucion, fecha_disponible) 
            VALUES (:serie, :nombre, :categoria, :estado, :calibracion, :devolucion, :disponible)
        ");
        $stmt->execute([
            ':serie'       => trim($_POST['serie']),
            ':nombre'      => trim($_POST['nombre']),
            ':categoria'   => trim($_POST['categoria']),
            ':estado'      => trim($_POST['estado']),
            ':calibracion' => $fecha_calibracion,
            ':devolucion'  => $fecha_devolucion,
            ':disponible'  => $fecha_disponible
        ]);
        $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Instrumento registrado correctamente<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $mensaje = '<div class="alert alert-warning alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-triangle me-2"></i><strong>¡Serie duplicada!</strong> La serie <b>' . htmlspecialchars($_POST['serie']) . '</b> ya existe.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            $mensaje = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// 🔹 PROCESAR EDITAR (POST ACTUALIZAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE instrumentos SET nombre = :nombre, categoria = :categoria, estado = :estado WHERE id = :id");
        $stmt->execute([
            ':nombre' => trim($_POST['nombre']), ':categoria' => trim($_POST['categoria']), 
            ':estado' => trim($_POST['estado']), ':id' => $_POST['id']
        ]);
        $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Instrumento actualizado correctamente<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (PDOException $e) {
        $mensaje = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Error al actualizar: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// 🔹 MENSAJES DE ELIMINACIÓN (Vienen por la URL GET)
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">🗑️ Instrumento eliminado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
if (isset($_GET['error'])) {
    $mensaje = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ ' . htmlspecialchars($_GET['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// 🔹 OBTENER INSTRUMENTOS USANDO EL MODELO
$modeloInstrumento = new Instrumento();
$instrumentos = $modeloInstrumento->obtenerTodos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Tablas De Instrumentos - Control Internacional Del Ecuador</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    <!-- CSS PERSONALIZADO (Aplicado de panel.php) -->
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
                        <a class="nav-link active" href="instrumentos.php"><div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>Inventario de Instrumentos</a>
                        <?php endif; ?>
                        
                        <?php if (tienePermiso('asignaciones')): ?>
                        <a class="nav-link" href="asignaciones.php"><div class="sb-nav-link-icon"><i class="fas fa-clipboard-check"></i></div>Asignaciones y Concurrencia</a>
                        <?php endif; ?>
                        
                        <?php if (tienePermiso('calibracion_reportes')): ?>
                        <a class="nav-link" href="calibracion_reportes.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Calibración y Reportes</a>
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
                    <h1 class="mt-4">Tablas De Instrumentos</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="panel.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Tablas De Instrumentos</li>
                    </ol>

                    <?= $mensaje ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-table me-1"></i>Listado de Instrumentos</div>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="fas fa-plus"></i> Nuevo Instrumento</button>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Serie</th><th>Nombre</th><th>Categoría</th><th>Estado</th><th>Vencimiento</th><th>Acciones</th>
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
                                                        'Disponible' => 'bg-success', 'En Uso' => 'bg-warning text-dark', 'En Calibracion' => 'bg-info text-dark', default => 'bg-secondary'
                                                    } ?>"><?= htmlspecialchars($inst['estado']) ?></span>
                                                </td>
                                                <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary btn-editar" 
                                                            data-id="<?= $inst['id'] ?>"
                                                            data-serie="<?= htmlspecialchars($inst['serie']) ?>"
                                                            data-nombre="<?= htmlspecialchars($inst['nombre']) ?>"
                                                            data-categoria="<?= htmlspecialchars($inst['categoria']) ?>"
                                                            data-estado="<?= htmlspecialchars($inst['estado']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger btn-eliminar"
                                                            data-id="<?= $inst['id'] ?>"
                                                            data-serie="<?= htmlspecialchars($inst['serie']) ?>">
                                                        <i class="fas fa-trash"></i>
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

    <!-- MODAL NUEVO INSTRUMENTO -->
    <div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="formNuevoInstrumento">
                    <input type="hidden" name="accion" value="crear">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Registrar Nuevo Instrumento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" id="selectCategoria" class="form-select" required onchange="actualizarPrefijo()">
                                    <option value="">Seleccione...</option>
                                    <option value="Medidores">Medidores</option>
                                    <option value="Detectores">Detectores</option>
                                    <option value="Calibradores">Calibradores</option>
                                    <option value="END">Equipos END</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="serie" id="inputSerie" class="form-control" placeholder="Ej: MD-001013" required>
                                <small class="text-muted" id="ayudaSerie">Seleccione una categoría para ver el formato.</small>
                                <small class="text-danger fw-bold d-none" id="errorSerieRepetida"><i class="fas fa-exclamation-circle me-1"></i> Esta serie ya está registrada.</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre del Equipo</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado Inicial</label>
                                <select name="estado" id="selectEstado" class="form-select" onchange="cambiarFecha()">
                                    <option value="Disponible">Disponible</option>
                                    <option value="En Uso">En Uso</option>
                                    <option value="En Calibracion">En Calibración</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="divFechaCalibracion" style="display: none;">
                                <label class="form-label text-primary fw-bold">Fecha Calibración</label>
                                <input type="date" name="vencimiento" class="form-control" id="inputFechaCalibracion">
                            </div>
                            <div class="col-md-6 mb-3" id="divFechaUso" style="display: none;">
                                <label class="form-label text-warning fw-bold">Fecha Devolución</label>
                                <input type="date" name="fecha_devolucion" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3" id="divFechaDisponible" style="display: none;">
                                <label class="form-label text-success fw-bold">Fecha Disponible</label>
                                <input type="date" name="fecha_disponible" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
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

    <!-- MODAL EDITAR INSTRUMENTO -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Editar Instrumento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Número de Serie (No editable)</label>
                            <input type="text" id="edit_serie" class="form-control" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre del Equipo</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" id="edit_categoria" class="form-select" required>
                                <option value="Medidores">Medidores</option>
                                <option value="Detectores">Detectores</option>
                                <option value="Calibradores">Calibradores</option>
                                <option value="END">Equipos END</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="edit_estado" class="form-select" required>
                                <option value="Disponible">Disponible</option>
                                <option value="En Uso">En Uso</option>
                                <option value="En Calibracion">En Calibración</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="public/js/instrumentos.js"></script>

    <!-- SCRIPT PARA EL TOGGLE DEL MENÚ DESDE EL LOGO -->
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
