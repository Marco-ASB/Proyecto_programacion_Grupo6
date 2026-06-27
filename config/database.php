<?php
// config/db.php

// 1. Credenciales de tu base de datos (ajusta si tu entorno local usa otras)
$host = 'localhost';
$dbname = 'gestion_instrumentos';
$username = 'root';   // Por defecto en XAMPP/Laragon
$password = '';       // Por defecto en XAMPP/Laragon

// 2. Opciones de seguridad y configuración para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve datos como $fila['email']
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa el motor real de MySQL (bloquea SQLi)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Fuerza codificación correcta
];

// 3. Intentar conectar
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // En desarrollo puedes ver el error, en producción solo usa error_log()
    die("Error de conexión a la base de datos. Verifica credenciales o si MySQL está activo.");
}
?>