<?php

class Subject {
    private $conn;
    private $table = "subjects";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getSubjects() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY subject_name ASC";
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("getSubjects error: " . $e->getMessage());
            return false;
        }
    }
}
?>
