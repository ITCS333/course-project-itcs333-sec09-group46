<?php
$host = "localhost";
$user = "admin"; 
$pass = "passowrd123";
$db   = "course";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection error: " . mysqli_connect_error());
}
?>
