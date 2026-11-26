<?php
// ajax_carrito.php - Procesa las peticiones del carrito (Agregar / Eliminar)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

header('Content-Type: application/json');

// Solo permitimos peticiones POST por seguridad
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_servicio = filter_input(INPUT_POST, 'id_servicio', FILTER_VALIDATE_INT);

    // Si el carrito no existe en la sesión, lo creamos como un arreglo vacío
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    try {
        $pdo = Database::getInstance()->getConnection();

        if ($accion === 'agregar' && $id_servicio) {
            // Buscamos el servicio en la BD para obtener su precio real
            $stmt = $pdo->prepare("SELECT id_servicios, precio FROM servicios WHERE id_servicios = :id AND estado = 'Activo'");
            $stmt->execute([':id' => $id_servicio]);
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($servicio) {
                // Si el servicio ya está en el carrito, sumamos 1 a la cantidad
                if (isset($_SESSION['carrito'][$id_servicio])) {
                    $_SESSION['carrito'][$id_servicio]['cantidad']++;
                } else {
                    // Si es nuevo, lo agregamos con cantidad 1
                    $_SESSION['carrito'][$id_servicio] = [
                        'id' => $servicio['id_servicios'],
                        'precio' => $servicio['precio'],
                        'cantidad' => 1
                    ];
                }
            }
        } 
        elseif ($accion === 'eliminar' && $id_servicio) {
            // Eliminamos el servicio del carrito
            if (isset($_SESSION['carrito'][$id_servicio])) {
                unset($_SESSION['carrito'][$id_servicio]);
            }
        }

        // Calculamos el total de items (sumando las cantidades) para el badge rojo
        $totalItems = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $totalItems += $item['cantidad'];
        }

        // Devolvemos la respuesta a JavaScript en formato JSON
        echo json_encode([
            'status' => 'success', 
            'totalItems' => $totalItems
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'mensaje' => 'Error al conectar con la base de datos'
        ]);
        exit;
    }
} else {
    // Si alguien intenta entrar directo al archivo por la URL, lo bloqueamos
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido']);
    exit;
}
?>