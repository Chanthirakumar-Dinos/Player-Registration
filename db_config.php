<?php
// Local XAMPP defaults. Set these environment variables on the server when
// deploying to a hosted MySQL database.
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'sports_club';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Unable to connect to the database. Check that MySQL is running and the database settings are correct.');
}

$conn->set_charset('utf8mb4');
?>
