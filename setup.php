<?php
// First, create the database if it doesn't exist
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS gate_management";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->close();

// Now import the schema
$conn = new mysqli('localhost', 'root', '', 'gate_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql_file = file_get_contents(__DIR__ . '/database/gate_management.sql');

// Split the SQL file into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql_file)));

// Execute each query separately
$success = true;
foreach ($queries as $query) {
    if (!empty($query)) {
        if (!$conn->query($query)) {
            echo "Error executing query: " . $conn->error . "<br>";
            $success = false;
            break;
        }
    }
}

if ($success) {
    echo "Database schema imported successfully<br>";
} else {
    echo "Error importing schema<br>";
}

$conn->close();

// Finally, create the admin account
require_once __DIR__ . '/api/setup_admin.php';
?>
