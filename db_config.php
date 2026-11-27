
<?php

$host = getenv('DB_HOST');
$port = (int) getenv('DB_PORT');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME');

$conn = mysqli_init();

$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);

// TiDB Cloud requires TLS
$conn->ssl_set(null, null, __DIR__ . '/certs/ca.pem', null, null);

if (!$conn->real_connect(
    $host,
    $username,
    $password,
    $database,
    $port,
    null,
    MYSQLI_CLIENT_SSL
)) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
