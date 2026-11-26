<?php
session_start();
require_once __DIR__ . '/models/verificar_sesion.php'; // Guardia general
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

// RESTRICCIÓN: Solo el rol Administrador (1) puede entrar aquí
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: panel.php"); 
    exit;
}

$pdo = Database::getInstance()->getConnection();

// Consultamos los pedidos. Usamos COALESCE por si es una empresa (razon_social) o persona natural (nombre).
$stmt = $pdo->prepare("
    SELECT p.*, 
           COALESCE(c.razon_social, CONCAT(c.nombre, ' ', c.apellido)) AS cliente_nombre_completo, 
           c.cedula 
    FROM pedidos p 
    JOIN clientes c ON p.cliente_id = c.id 
    ORDER BY p.id_pedidos DESC
");
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Gestión de Pedidos - Control Internacional</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        /* Tus estilos se mantienen intactos */
        .sb-topnav { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; border-bottom: 2px solid #343a40; }
        #logoAnimado { cursor: pointer; display: flex; align-items: center; padding: 0.5rem 1rem; text-decoration: none; }
        .icon-container { position: relative; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
        .logo-icon { font-size: 1.8rem; color: #ffffff; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); opacity: 1; transform: rotate(0deg) scale(1); }
        .menu-icon { position: absolute; font-size: 1.8rem; color: #ffffff; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); opacity: 0; transform: rotate(-90deg) scale(0.5); }
        #logoAnimado:hover .logo-icon { opacity: 0; transform: rotate(90deg) scale(0.5); }
        #logoAnimado:hover .menu-icon { opacity: 1; transform: rotate(0deg) scale(1); }
        .texto-logo { font-size: 1.3rem; font-weight: 600; letter-spacing: 0.5px; color: #ffffff; }
        .sb-topnav .form-control { height: 38px; font-size: 1rem; }
        .sb-topnav .btn-primary { height: 38px; width: 45px; }
        .sb-topnav .nav-link i.fa-user { font-size: 1.3rem; }
    </style>
</head>
<body class="sb-nav-fixed">
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
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Buscar..." />
                <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><span class="dropdown-item-text small text-muted">Sesión: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item text-danger" href="models/cerrar_sesion.php"><i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión</a></li>
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
                        <a class="nav-link" href="panel.php"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Panel Principal</a>
                        <div class="sb-sidenav-menu-heading">E-commerce</div>
                        <?php if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1): ?>
                            <a class="nav-link active" href="pedidos.php"><div class="sb-nav-link-icon"><i class="fas fa-shopping-bag"></i></div>Gestión de Pedidos</a>
                        <?php endif; ?>
                        <a class="nav-link" href="tienda.php"><div class="sb-nav-link-icon"><i class="fas fa-store"></i></div>Ver Tienda Pública</a>
                        <div class="sb-sidenav-menu-heading">Gestión Operativa</div>
                        <a class="nav-link" href="instrumentos.php"><div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>Inventario</a>
                        <a class="nav-link" href="asignaciones.php"><div class="sb-nav-link-icon"><i class="fas fa-clipboard-check"></i></div>Asignaciones</a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Sesión iniciada como:</div>
                    <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></strong>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Gestión de Pedidos</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="panel.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Pedidos</li>
                    </ol>

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-list me-1"></i> Lista de Órdenes de Compra
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nº Pedido</th>
                                        <th>Fecha</th>
                                        <th>Empresa / Cliente</th>
                                        <th>Total ($)</th>
                                        <th>Método Pago</th>
                                        <th>Estado Pago</th>
                                        <th>Estado Pedido</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $p): ?>
                                        <tr>
                                            <td><strong>#<?= $p['id_pedidos'] ?></strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'] ?? 'now')) ?></td>
                                            <td>
                                                <?= htmlspecialchars($p['cliente_nombre_completo']) ?><br>
                                                <small class="text-muted">RUC/CI: <?= htmlspecialchars($p['cedula']) ?></small>
                                            </td>
                                            <td class="text-success fw-bold">$<?= number_format($p['total'], 2) ?></td>
                                            <td><?= htmlspecialchars($p['metodo_pago'] ?? 'No especificado') ?></td>
                                            
                                            <!-- Badge de Estado de Pago -->
                                            <td>
                                                <?php 
                                                $estadoPago = $p['estado_pago'] ?? 'No Pagado';
                                                $badgePago = ($estadoPago === 'Pagado') ? 'bg-success' : (($estadoPago === 'Reembolsado') ? 'bg-warning text-dark' : 'bg-danger');
                                                ?>
                                                <span class="badge <?= $badgePago ?>"><?= $estadoPago ?></span>
                                            </td>
                                            
                                            <!-- Badge de Estado del Pedido -->
                                            <td>
                                                <?php 
                                                $badge = 'bg-warning text-dark';
                                                if($p['estado'] == 'Aprobado') $badge = 'bg-success';
                                                if($p['estado'] == 'Rechazado') $badge = 'bg-danger';
                                                ?>
                                                <span class="badge <?= $badge ?>"><?= $p['estado'] ?></span>
                                            </td>
                                            
                                            <!-- BOTÓN RESTAURADO -->
                                            <td>
                                                <a href="pedido_detalle.php?id=<?= $p['id_pedidos'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Ver / Gestionar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="public/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
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
            // Inicializar DataTables
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });
    </script>
</body>
</html>