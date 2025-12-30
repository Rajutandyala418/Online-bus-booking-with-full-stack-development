<?php
$host = "localhost";
$user = "root";               
$pass = "";            
$dbname = "varahi_bus";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");
?>
