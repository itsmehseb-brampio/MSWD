<?php

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 3306;
$dbname = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

if (!$host || !$dbname || !$user || !$pass) {
    die("Database variables are missing.");
}

$conn = new mysqli(
    $host,
    $user,
    $pass,
    $dbname,
    (int) $port
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>