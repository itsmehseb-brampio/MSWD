<?php
$host = "localhost";
$dbname = "mapayanan_db";
$user = "root";
$pass = ""; // your MySQL password

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>