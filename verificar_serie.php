<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

header('Content-Type: application/json');

if (isset($_POST['serie'])) {
    $serie = trim($_POST['serie']);
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id FROM instrumentos WHERE serie = :serie");
        $stmt->execute([':serie' => $serie]);
        
        if ($stmt->fetch()) {
            echo json_encode(['existe' => true]);
        } else {
            echo json_encode(['existe' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => true]);
    }
}