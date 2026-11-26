<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

$isAdmin = isset($_SESSION['user_id']);
$nombreUsuario = $_SESSION['user_name'] ?? 'Cliente';

$pdo = Database::getInstance()->getConnection();
$itemsCarrito = [];
$totalPagar = 0;

// Si hay items en el carrito, buscamos su info actual en la base de datos
if (!empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    // Creamos los placeholders (?, ?, ?) para la consulta preparada
    $inQuery = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT id_servicios, nombre, precio FROM servicios WHERE id_servicios IN ($inQuery)");
    $stmt->execute($ids);
    $serviciosDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($serviciosDB as $srv) {
        $id = $srv['id_servicios'];
        $cantidad = $_SESSION['carrito'][$id]['cantidad'];
        $subtotal = $srv['precio'] * $cantidad;
        
        $itemsCarrito[] = [
            'id' => $id,
            'nombre' => $srv['nombre'],
            'precio' => $srv['precio'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal
        ];
        $totalPagar += $subtotal;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Carrito de Compras - Control Internacional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="tienda.php"><i class="fas fa-store me-2"></i>Volver a la Tienda</a>
            <div class="ms-auto text-white">
                <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nombreUsuario) ?>
            </div>
        </div>
    </nav>

    <div class="container my-5 flex-grow-1">
        <h2 class="mb-4 fw-bold"><i class="fas fa-shopping-cart me-2"></i>Tu Carrito de Servicios</h2>

        <?php if (empty($itemsCarrito)): ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                <h4>Tu carrito está vacío</h4>
                <p>Vuelve al catálogo para añadir servicios técnicos.</p>
                <a href="tienda.php" class="btn btn-primary mt-3">Ver Catálogo</a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Tabla de productos -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Servicio</th>
                                        <th class="text-center">Precio</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itemsCarrito as $item): ?>
                                        <tr>
                                            <td class="align-middle fw-bold"><?= htmlspecialchars($item['nombre']) ?></td>
                                            <td class="text-center align-middle">$<?= number_format($item['precio'], 2) ?></td>
                                            <td class="text-center align-middle"><?= $item['cantidad'] ?></td>
                                            <td class="text-end align-middle fw-bold text-success">$<?= number_format($item['subtotal'], 2) ?></td>
                                            <td class="text-end align-middle">
                                                <!-- Botón para eliminar (lo programaremos luego con JS) -->
                                                <button class="btn btn-sm btn-outline-danger btnEliminar" data-id="<?= $item['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resumen y Checkout -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Resumen del Pedido</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($totalPagar, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 text-muted">
                                <span>IVA (15%):</span>
                                <span>$<?= number_format($totalPagar * 0.15, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <h4 class="fw-bold">Total:</h4>
                                <h4 class="fw-bold text-success">$<?= number_format($totalPagar * 1.15, 2) ?></h4>
                            </div>
                            
                            <!-- Pasamos el total oculto al checkout -->
                            <form action="checkout.php" method="POST">
                                <input type="hidden" name="total_pagar" value="<?= $totalPagar * 1.15 ?>">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        Proceder al Pago <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="py-4 bg-dark text-white text-center mt-auto">
        <div class="container"><small>Copyright &copy; Control Internacional Del Ecuador - UAE 2026</small></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
          <script src="public/js/carrito.js"></script>
</body>
</html>