<?php
$host = "localhost";
$user = "root"; 
$pass = "Parcival"; 
$dbname = "pixelwizard";  // CHANGE THIS

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Error: " . $conn->connect_error);
}
?>
