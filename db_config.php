<?php

$host = "sql210.infinityfree.com";
$username = "if0_41931298";
$password = "Lr8NxEoe4R";
$database = "if0_41931298_sports_club";
$port = 3306;

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");

?>
