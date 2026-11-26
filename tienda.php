<?php
// 1. Verificación estricta de la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

// 2. Traer los servicios activos
try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->query("SELECT id_servicios, nombre, descripcion, precio, categoria_requerida FROM servicios WHERE estado = 'Activo' ORDER BY id_servicios ASC");
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar el catálogo: " . $e->getMessage());
}

// 3. Verificamos si hay un usuario logueado
$isAdmin = isset($_SESSION['user_id']);
$nombreUsuario = $_SESSION['user_name'] ?? 'Cliente';

// 4. Contar los items del carrito para mostrarlos al cargar la página
$totalItemsCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $totalItemsCarrito += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Catálogo de Servicios - Control Internacional Del Ecuador</title>
    
    <!-- CSS de Bootstrap y SB Admin -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    <!-- CSS PERSONALIZADO PARA ANIMACIONES -->
    <style>
        .card-servicio {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-servicio:hover {
            transform: translateY(-10px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.175) !important;
            border-bottom: 3px solid #0d6efd;
        }
        .animacion-entrada {
            animation: aparecer 0.6s ease-out forwards;
            opacity: 0;
        }
        @keyframes aparecer {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Navbar Pública -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container d-flex justify-content-between align-items-center">
            
            <!-- Lado Izquierdo: Botón Volver + Logo -->
            <div class="d-flex align-items-center gap-3">
                <?php if ($isAdmin): ?>
                    <a href="panel.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Panel
                    </a>
                <?php endif; ?>

                <a class="navbar-brand mb-0 ms-2" href="tienda.php">
                    Control Internacional Del Ecuador
                </a>
            </div>

            <!-- Lado Derecho: Sesión y Carrito -->
            <div class="d-flex align-items-center gap-3">
                <?php if ($isAdmin): ?>
                    <span class="text-white small d-none d-md-block">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nombreUsuario) ?>
                    </span>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-user-lock me-1"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>

                <!-- Carrito de Compras -->
                <a href="carrito.php" class="btn btn-primary btn-sm position-relative">
                    <i class="fas fa-shopping-cart"></i> Carrito
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contadorCarrito">
                        <?= $totalItemsCarrito ?>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="bg-primary text-white text-center py-5 mb-5">
        <div class="container">
            <h1 class="fw-bold"><i class="fas fa-hard-hat me-2"></i> Servicios Técnicos Certificados</h1>
            <p class="lead">Soluciones de inspección, calibración y ensayos no destructivos para la industria.</p>
        </div>
    </header>

    <!-- Catálogo de Servicios -->
    <div class="container mb-5 flex-grow-1">
        <div class="row">
            <?php if (!empty($servicios)): ?>
                <?php $delay = 0.1; foreach ($servicios as $srv): ?>
                    <div class="col-lg-4 col-md-6 mb-4 animacion-entrada" style="animation-delay: <?= $delay ?>s;">
                        <div class="card h-100 shadow-sm border-0 card-servicio">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start"><?= htmlspecialchars($srv['categoria_requerida']) ?></span>
                                <h5 class="card-title fw-bold"><?= htmlspecialchars($srv['nombre']) ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($srv['descripcion']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <h4 class="text-success fw-bold mb-0">$<?= number_format($srv['precio'], 2) ?></h4>
                                    <button class="btn btn-primary btn-sm btnAgregar" data-id="<?= $srv['id_servicios'] ?>">
                                        <i class="fas fa-cart-plus me-1"></i> Añadir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php $delay += 0.15; endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-warning">No hay servicios disponibles por el momento.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white text-center mt-auto">
        <div class="container">
            <small>Copyright &copy; Control Internacional Del Ecuador - UAE 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    <!-- SCRIPT DEL CARRITO EXTERNO -->
    <script src="public/js/tienda.js"></script>

</body>
</html>