<?php
session_start();
require_once __DIR__ . '/models/verificar_sesion.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: panel.php"); 
    exit;
}

$pedidoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pedidoId) { header("Location: pedidos.php"); exit; }

$pdo = Database::getInstance()->getConnection();

// Obtener datos del pedido y cliente (Soporta Empresa o Persona Natural)
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.nombre, c.apellido, c.cedula, c.email, c.telefono, 
           c.razon_social, c.persona_contacto, c.direccion_fiscal, c.direccion_entrega 
    FROM pedidos p 
    JOIN clientes c ON p.cliente_id = c.id 
    WHERE p.id_pedidos = :id
");
$stmt->execute([':id' => $pedidoId]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) { die("Pedido no encontrado."); }

// Obtener el detalle
$stmtDet = $pdo->prepare("
    SELECT d.*, s.nombre AS servicio_nombre, s.categoria_requerida 
    FROM pedido_detalle d 
    JOIN servicios s ON d.id_servicios = s.id_servicios 
    WHERE d.id_pedidos = :id
");
$stmtDet->execute([':id' => $pedidoId]);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

// Verificar si ya se procesó
$procesado = ($pedido['estado'] !== 'Pendiente');

// Definir nombre a mostrar
$nombreCliente = !empty($pedido['razon_social']) ? $pedido['razon_social'] : trim($pedido['nombre'] . ' ' . $pedido['apellido']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Detalle del Pedido #<?= $pedidoId ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        /* Tus estilos intactos */
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
            <nav class="sb-sidenav accordion sb-sidenav-dark">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">E-commerce</div>
                        <a class="nav-link" href="pedidos.php"><div class="sb-nav-link-icon"><i class="fas fa-arrow-left"></i></div>Volver a Pedidos</a>
                    </div>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Pedido #<?= $pedidoId ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="pedidos.php">Pedidos</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>

                    <div class="row">
                        <!-- Datos del Cliente y Pago -->
                        <div class="col-lg-5 mb-4">
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-dark text-white"><i class="fas fa-building me-2"></i>Datos del Cliente / Empresa</div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Razón Social:</strong> <?= htmlspecialchars($nombreCliente) ?></p>
                                    <?php if(!empty($pedido['persona_contacto'])): ?>
                                        <p class="mb-1"><strong>Contacto:</strong> <?= htmlspecialchars($pedido['persona_contacto']) ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1"><strong>RUC/Cédula:</strong> <?= htmlspecialchars($pedido['cedula']) ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
                                    <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono']) ?></p>
                                    <hr>
                                    <p class="mb-1"><strong>Dirección Fiscal:</strong><br> <span class="text-muted"><?= htmlspecialchars($pedido['direccion_fiscal'] ?? 'No registrada') ?></span></p>
                                    <p class="mb-0"><strong>Dirección de Entrega:</strong><br> <span class="text-muted"><?= htmlspecialchars($pedido['direccion_entrega_pedido'] ?? $pedido['direccion_entrega'] ?? 'Misma que fiscal') ?></span></p>
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-header bg-info text-white"><i class="fas fa-money-check-alt me-2"></i>Información de Pago</div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Método de Pago:</strong> <?= htmlspecialchars($pedido['metodo_pago'] ?? 'No especificado') ?></p>
                                    <p class="mb-0"><strong>Estado del Pago:</strong>
                                        <?php 
                                            $estadoPago = $pedido['estado_pago'] ?? 'No Pagado';
                                            $badgeClass = ($estadoPago === 'Pagado') ? 'bg-success' : (($estadoPago === 'Reembolsado') ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $badgeClass ?> fs-6"><?= $estadoPago ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Detalle de Servicios -->
                        <div class="col-lg-7 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white"><i class="fas fa-tools me-2"></i>Servicios Solicitados</div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Servicio</th>
                                                <th>Categoría</th>
                                                <th>Cant.</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($detalles as $det): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($det['servicio_nombre']) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($det['categoria_requerida']) ?></span></td>
                                                    <td><?= $det['cantidad'] ?></td>
                                                    <td>$<?= number_format($det['subtotal'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">TOTAL (con IVA):</td>
                                                <td class="fw-bold text-success fs-5">$<?= number_format($pedido['total'], 2) ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <?php if(!empty($pedido['observaciones_cliente'])): ?>
                                        <div class="alert alert-secondary mt-3">
                                            <strong><i class="fas fa-comment-dots me-1"></i> Observaciones del Proyecto:</strong><br>
                                            <?= nl2br(htmlspecialchars($pedido['observaciones_cliente'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$procesado): ?>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-2"></i> Al aprobar este pedido, el sistema buscará automáticamente instrumentos <strong>Disponibles</strong> de las categorías requeridas y generará las asignaciones.
                                        </div>
                                        <form action="procesar_pedido.php" method="POST">
                                            <input type="hidden" name="id_pedido" value="<?= $pedidoId ?>">
                                            <button type="submit" class="btn btn-success btn-lg w-100">
                                                <i class="fas fa-check-circle me-2"></i> Aprobar Pedido y Asignar Instrumentos
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-success mt-3 text-center">
                                            <h5><i class="fas fa-check-double me-2"></i> Pedido <?= $pedido['estado'] ?></h5>
                                            <p class="mb-0">Los instrumentos ya han sido asignados en el módulo de asignaciones.</p>
                                            <a href="asignaciones.php" class="btn btn-outline-success btn-sm mt-2">Ir a Asignaciones</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="public/js/scripts.js"></script>
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