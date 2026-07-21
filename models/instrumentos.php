<?php
// models/Instrumento.php
require_once __DIR__ . '/../config/Database.php';

class Instrumento {
    private ?PDO $pdo;  
    private string $tabla = 'instrumentos';  // 
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function obtenerTodos(): array {  // ✅ Retorna array
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla} ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function crear(array $datos): bool { 
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tabla} (serie, nombre, categoria, estado, fecha_vencimiento_calibracion) 
            VALUES (:serie, :nombre, :categoria, :estado, :vencimiento)
        ");
        return $stmt->execute([
            ':serie' => $datos['serie'],
            ':nombre' => $datos['nombre'],
            ':categoria' => $datos['categoria'],
            ':estado' => $datos['estado'] ?? 'Disponible',
            ':vencimiento' => $datos['vencimiento']
        ]);
    }
    
    public function actualizarEstado(int $id, string $nuevoEstado): bool {  
        $stmt = $this->pdo->prepare("UPDATE {$this->tabla} SET estado = :estado WHERE id = :id");
        return $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
    }

}
?>