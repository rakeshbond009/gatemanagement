<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($phone) || empty($role)) {
        header('Location: ../register.php?error=invalid_input');
        exit;
    }
    
    // Validate password match
    if ($password !== $confirm_password) {
        header('Location: ../register.php?error=password_mismatch');
        exit;
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        header('Location: ../register.php?error=password_length');
        exit;
    }
    
    // Validate phone number
    if (!preg_match('/^\d{10}$/', $phone)) {
        header('Location: ../register.php?error=invalid_phone');
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../register.php?error=invalid_email');
        exit;
    }
    
    $conn = connectDB();
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ../register.php?error=username_exists');
        exit;
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ../register.php?error=email_exists');
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, full_name, email, phone, role, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'inactive')
    ");
    
    $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $phone, $role);
    
    if ($stmt->execute()) {
        // Send notification to admin about new registration
        $admin_notification = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            SELECT id, 'New Registration', ?, 'announcement'
            FROM users WHERE role = 'admin'
        ");
        $notification_msg = "New {$role} registration: {$full_name}";
        $admin_notification->bind_param("s", $notification_msg);
        $admin_notification->execute();
        
        header('Location: ../index.php?success=registration');
        exit;
    } else {
        header('Location: ../register.php?error=registration_failed');
        exit;
    }
    
    $conn->close();
}

// If not POST request, redirect to registration page
header('Location: ../register.php');
exit;
?>
