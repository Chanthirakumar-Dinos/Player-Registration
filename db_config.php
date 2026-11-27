<?php

$host = "sql210.infinityfree.com";
$username = "if0_41931298";
$password = "Lr8NxEoe4R";
$database = "if0_41931298_sports_club";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

?>
