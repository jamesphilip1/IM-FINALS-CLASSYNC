<?php
/**
 * Database connection class.
 * Uses PDO so all queries in Attendance.php can use prepared statements.
 */
class Database {
    
    private $host = "sql301.infinityfree.com";
    private $db_name = "if0_43287168_classync_db";
    private $username = "if0_43287168";
    private $password = "pE0NnaMNUj"; 

    public $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection error: " . $e->getMessage());
        }
        return $this->conn;
    }
}
?>
