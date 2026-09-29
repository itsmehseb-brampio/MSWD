<?php

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT') ?: 3306;
$dbname = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

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