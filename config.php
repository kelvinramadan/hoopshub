<!-- config.php -->
<?php
session_start();
$host = 'localhost';
$dbname = 'hoopshub';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname); // Ubah $mysqli menjadi $conn
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die("Connection failed: " . $e->getMessage());
}