<?php
$host   = getenv('DB_HOST')   ?: 'localhost';
$user   = getenv('DB_USER')   ?: 'root';
$pass   = getenv('DB_PASS')   ?: 'Aniqfan';
$dbname = getenv('DB_NAME')   ?: 'nmuc_portal';
$port   = (int)(getenv('DB_PORT') ?: 3306);

// 1. PDO Connection (Used by index.php)
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Fallback attempt without strict SSL if local environment fails
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass);
    } catch (PDOException $e2) {
        die("Database PDO Connection Failed: " . $e2->getMessage());
    }
}

// 2. MySQLi Connection (Used by legacy system files)
$conn = mysqli_init();
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
}

if (!@$conn->real_connect($host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT)) {
    // Fallback for local XAMPP
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
}
?>