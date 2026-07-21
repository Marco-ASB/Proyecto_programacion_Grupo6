<?php
// test_conexion.php
require_once __DIR__ . '/config/Database.php';

try {
    // Obtener la conexión usando el Singleton
    $pdo = Database::getInstance()->getConnection();
    
    // Verificar que sea un objeto PDO válido
    if ($pdo instanceof PDO) {
        echo "✅ Conexión exitosa a la base de datos: <strong>gestion_instrumentos</strong>";
    } else {
        echo "❌ Error: No se retornó un objeto de conexión válido.";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>