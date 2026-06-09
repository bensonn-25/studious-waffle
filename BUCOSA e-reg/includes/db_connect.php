<?php
$host = 'localhost';
$username = 'root';
$password = ''; // Default laragon root password is empty
$database = 'bucosa_ereg';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
