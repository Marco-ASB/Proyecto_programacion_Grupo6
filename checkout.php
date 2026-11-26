<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

// Si el carrito está vacío, no dejamos entrar (a menos que vengamos de un pedido exitoso)
if (empty($_SESSION['carrito']) && !isset($_GET['exito'])) {
    header("Location: tienda.php");
    exit;
}

$pdo = Database::getInstance()->getConnection();
$totalConIva = 0;
$mensaje = "";
$pedidoExitoso = false;
$metodo_pago = '';
$pedidoId = 0;

// Calculamos el total si el carrito no está vacío
if (!empty($_SESSION['carrito'])) {
    $totalPagar = 0;
    $ids = array_keys($_SESSION['carrito']);
    $inQuery = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id_servicios, precio FROM servicios WHERE id_servicios IN ($inQuery)");
    $stmt->execute($ids);
    $serviciosDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($serviciosDB as $srv) {
        $id = $srv['id_servicios'];
        $totalPagar += $srv['precio'] * $_SESSION['carrito'][$id]['cantidad'];
    }
    $totalConIva = $totalPagar * 1.15; // 15% de IVA
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturamos los datos
    $ruc              = trim($_POST['cedulaoruc'] ?? '');
    $contacto         = trim($_POST['contacto'] ?? '');
    $razon_social     = trim($_POST['razon_social'] ?? '');
    $persona_contacto = trim($_POST['personacontacto'] ?? '');
    $direccion_fiscal = trim($_POST['direccion_fiscal'] ?? '');
    $direccion_entrega= trim($_POST['direccion_entrega'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $metodo_pago      = trim($_POST['metodo_pago'] ?? '');
    $observaciones    = trim($_POST['observaciones'] ?? '');

    if ($ruc && $razon_social && $email && $contacto && $direccion_fiscal && $metodo_pago) {
        try {
            $pdo->beginTransaction();

            // 1. Buscar cliente por cédula/RUC
            $stmtCliente = $pdo->prepare("SELECT id FROM clientes WHERE cedula = :cedula");
            $stmtCliente->execute([':cedula' => $ruc]);
            $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

            $dirEntregaFinal = $direccion_entrega !== '' ? $direccion_entrega : $direccion_fiscal;

            if ($cliente) {
                $clienteId = $cliente['id'];
                $stmtUpdate = $pdo->prepare("UPDATE clientes SET 
                    razon_social = :razon, 
                    persona_contacto = :persona, 
                    telefono = :tel, 
                    email = :email, 
                    direccion_fiscal = :dir_fiscal, 
                    direccion_entrega = :dir_entrega 
                    WHERE id = :id");
                $stmtUpdate->execute([
                    ':razon'       => $razon_social,
                    ':persona'     => $persona_contacto,
                    ':tel'         => $contacto,
                    ':email'       => $email,
                    ':dir_fiscal'  => $direccion_fiscal,
                    ':dir_entrega' => $dirEntregaFinal,
                    ':id'          => $clienteId
                ]);
            } else {
                $stmtInsertCliente = $pdo->prepare("INSERT INTO clientes 
                    (nombre, apellido, cedula, razon_social, persona_contacto, telefono, email, direccion_fiscal, direccion_entrega) 
                    VALUES (:nom, :ape, :ced, :razon, :persona, :tel, :email, :dir_fiscal, :dir_entrega)");
                $stmtInsertCliente->execute([
                    ':nom'         => $razon_social,
                    ':ape'         => '',
                    ':ced'         => $ruc,
                    ':razon'       => $razon_social,
                    ':persona'     => $persona_contacto,
                    ':tel'         => $contacto,
                    ':email'       => $email,
                    ':dir_fiscal'  => $direccion_fiscal,
                    ':dir_entrega' => $dirEntregaFinal
                ]);
                $clienteId = $pdo->lastInsertId();
            }

            // 2. Cabecera del pedido
            $stmtPedido = $pdo->prepare("INSERT INTO pedidos 
                (cliente_id, total, metodo_pago, estado, estado_pago, observaciones_cliente, direccion_entrega_pedido) 
                VALUES (:cliente_id, :total, :metodo_pago, 'Pendiente', 'No Pagado', :obs, :dir_entrega)");
            
            $stmtPedido->execute([
                ':cliente_id'  => $clienteId,
                ':total'       => $totalConIva,
                ':metodo_pago' => $metodo_pago,
                ':obs'         => $observaciones,
                ':dir_entrega' => $dirEntregaFinal
            ]);
            $pedidoId = $pdo->lastInsertId();

            // 3. Detalle del pedido
            $stmtDetalle = $pdo->prepare("INSERT INTO pedido_detalle 
                (id_pedidos, id_servicios, cantidad, precio_unitario, subtotal) 
                VALUES (:pedido, :servicio, :cant, :precio, :subtotal)");
            
            foreach ($serviciosDB as $srv) {
                $id = $srv['id_servicios'];
                $cantidad = $_SESSION['carrito'][$id]['cantidad'];
                $subtotal = $srv['precio'] * $cantidad;
                
                $stmtDetalle->execute([
                    ':pedido'   => $pedidoId,
                    ':servicio' => $id,
                    ':cant'     => $cantidad,
                    ':precio'   => $srv['precio'],
                    ':subtotal' => $subtotal
                ]);
            }

            $pdo->commit();
            unset($_SESSION['carrito']);
            
            // Bandera para mostrar la pantalla de éxito sin redirigir a otro archivo
            $pedidoExitoso = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = '<div class="alert alert-danger">Error al procesar el pedido: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-warning">Por favor, complete todos los campos obligatorios.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Finalizar Compra - Control Internacional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="public/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="tienda.php"><i class="fas fa-store me-2"></i>Ir a la Tienda</a>
        </div>
    </nav>

    <div class="container my-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?= $mensaje ?>
                
                <?php if ($pedidoExitoso): ?>
                    <!-- ========================================== -->
                    <!-- PANTALLA DE ÉXITO (Si el pedido se guardó) -->
                    <!-- ========================================== -->
                    <div class="card shadow border-0 text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-check-circle text-success mb-4" style="font-size: 5rem;"></i>
                            <h2 class="mb-3">¡Pedido Confirmado Exitosamente!</h2>
                            <p class="lead text-muted mb-4">Tu orden <strong>#<?= $pedidoId ?></strong> ha sido registrada.</p>
                            
                            <div class="alert alert-info d-inline-block text-start mb-4">
                                <p class="mb-1"><strong>Método de pago:</strong> <?= htmlspecialchars($metodo_pago) ?></p>
                                <p class="mb-0"><strong>Total:</strong> $<?= number_format($totalConIva, 2) ?></p>
                            </div>
                            
                            <div class="mt-4">
                                <a href="tienda.php" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-store me-2"></i>Volver a la Tienda
                                </a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- ========================================== -->
                    <!-- FORMULARIO DE CHECKOUT                     -->
                    <!-- ========================================== -->
                    <div class="card shadow border-0">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0"><i class="fas fa-building me-2"></i>Datos de la Empresa (Checkout)</h4>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="" id="checkoutForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">RUC / Cédula *</label>
                                        <input type="text" name="cedulaoruc" id="rucInput" class="form-control" maxlength="13" required>
                                        <span id="rucFeedback" class="small mt-1 d-block"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contacto (Teléfono) *</label>
                                        <input type="text" name="contacto" class="form-control" maxlength="10" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Razón Social / Nombre de la Empresa *</label>
                                        <input type="text" name="razon_social" id="razonSocialInput" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Persona de Contacto</label>
                                        <input type="text" name="personacontacto" class="form-control">
                                    </div>
                                </div>

                                <!-- Direcciones separadas -->
                                <div class="mb-3">
                                    <label class="form-label">Dirección Fiscal *</label>
                                    <input type="text" name="direccion_fiscal" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dirección de Entrega (opcional)</label>
                                    <input type="text" name="direccion_entrega" class="form-control" placeholder="Dejar en blanco si es la misma que la fiscal">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Correo Electrónico *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Método de Pago *</label>
                                    <select name="metodo_pago" class="form-select form-select-lg" required>
                                        <option value="" disabled selected>Seleccione cómo desea pagar...</option>
                                        <option value="Tarjeta de Crédito/Débito"> Tarjeta de Crédito/Débito</option>
                                        <option value="Transferencia Bancaria"> Transferencia Bancaria</option>
                                        <option value="Pago en Efectivo Directo"> Pago en Efectivo Directo</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Observaciones del Proyecto</label>
                                    <textarea name="observaciones" class="form-control" rows="3" placeholder="Ej: Favor enviar los equipos a la planta el día lunes..."></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center border-top pt-4">
                                    <div>
                                        <h5 class="mb-0">Total a Pagar:</h5>
                                        <h3 class="text-success fw-bold mb-0">$<?= number_format($totalConIva, 2) ?></h3>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-lock me-2"></i> Confirmar Pedido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <footer class="py-4 bg-dark text-white text-center mt-auto">
        <div class="container"><small>Copyright &copy; Control Internacional Del Ecuador - UAE 2026</small></div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="public/js/validar_ruc.js"></script>
</body>
</html>