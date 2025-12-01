<?php
// File: DB.php - Class Koneksi Database (OOP)

class DB {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbName = "gudang_db";
    protected $conn;

    public function __construct() {
        // Coba koneksi
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbName);

        if ($this->conn->connect_error) {
            die("Koneksi gagal: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    // Fungsi fetchAll untuk AJAX
    public function fetchAll($query) {
        $result = $this->conn->query($query);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // Fungsi untuk menjalankan prepared statement (INSERT, UPDATE, DELETE)
    public function executeStatement($query, $types, $params) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false; // Error prepare
        }
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>