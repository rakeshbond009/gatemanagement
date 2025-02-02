<?php
$config_path = dirname(__DIR__) . '/config/config.php';
require_once $config_path;

// This script should only be run once to set up the initial admin account
$conn = connectDB();

// Check if admin already exists
$result = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($result->num_rows > 0) {
    die("Admin account already exists!");
}

// Create admin account
$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$full_name = 'System Administrator';
$email = 'admin@gatemanagement.com';
$phone = '1234567890';
$role = 'admin';

$stmt = $conn->prepare("
    INSERT INTO users (username, password, full_name, email, phone, role) 
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssssss", $username, $password, $full_name, $email, $phone, $role);

if ($stmt->execute()) {
    echo "Admin account created successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "Please change these credentials after first login.";
} else {
    echo "Failed to create admin account: " . $conn->error;
}

$conn->close();
?>
