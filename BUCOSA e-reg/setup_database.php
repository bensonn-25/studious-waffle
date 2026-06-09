<?php
$host = 'localhost';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sqlFile = 'database.sql';
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("Error reading SQL file");
}

// Execute multi query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Database initialized successfully.";
} else {
    echo "Error executing SQL: " . $conn->error;
}

$conn->close();
?>
