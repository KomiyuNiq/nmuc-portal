<?php
$host   = getenv('DB_HOST')   ?: 'localhost';
$user   = getenv('DB_USER')   ?: 'root';
$pass   = getenv('DB_PASS')   ?: '';
$dbname = getenv('DB_NAME')   ?: 'nmuc_portal';
$port   = getenv('DB_PORT')   ?: '3306';

$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>