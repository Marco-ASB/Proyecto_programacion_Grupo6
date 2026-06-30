<?php
require_once 'config/db.php';
echo "✅ Conexión exitosa. Motor: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
?>