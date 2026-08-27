<?php
$host   = getenv('DB_HOST')   ?: 'localhost';
$user   = getenv('DB_USER')   ?: 'root';
$pass   = getenv('DB_PASS')   ?: '';
$dbname = getenv('DB_NAME')   ?: 'nmuc_portal';
$port   = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();

// Enable SSL when connecting to external cloud hosts like Aiven
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
}

if (!$conn->real_connect($host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT)) {
    die("Connection failed: " . mysqli_connect_error());
}
?>