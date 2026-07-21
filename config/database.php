<?php
// config/Database.php
// Clase Database con patrón Singleton y configuración segura PDO

class Database {
    
    // Atributos PRIVADOS — encapsulamiento total
    private string $host = "localhost";
    private string $db_name = "gestion_instrumentos";  // Tu BD
    private string $username = "root";                  // XAMPP por defecto
    private string $password = "";                      // XAMPP vacío
    private string $charset = "utf8mb4";
    
    // Patrón Singleton
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    
    // Constructor privado — evita new Database()
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         
                PDO::ATTR_EMULATE_PREPARES   => false,                   
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
           
            die("❌ Error de conexión: " . $e->getMessage());
        }
    }
    
    // Método estático para obtener la única instancia
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Obtener conexión PDO
    public function getConnection(): PDO {
        return $this->pdo;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>