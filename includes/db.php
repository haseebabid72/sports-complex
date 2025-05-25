<?php
// Database connection using MySQL (port 3307)
$host = 'localhost';
$user = 'root';
$pass = 'admin';
$db = 'sports_complex';
$port = 3307;
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>
