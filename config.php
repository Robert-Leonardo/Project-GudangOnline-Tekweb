<?php
// File: config.php
$host     = "localhost";
$username = "root";
$password = "";
$database = "gudang_db";

$connect = new mysqli($host, $username, $password, $database);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}
?>