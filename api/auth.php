<?php
require_once '../config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php?success=logout');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password']) || !isset($data['role'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username, password, and role are required']);
        exit;
    }
    
    $username = $data['username'];
    $password = $data['password'];
    $role = $data['role'];
    
    error_log("Login attempt - Username: $username, Role: $role");
    
    $conn = connectDB();
    
    // Get user with role
    $stmt = $conn->prepare("
        SELECT id, username, password, full_name, role, status 
        FROM users 
        WHERE username = ? 
        AND role = ?
    ");
    
    $stmt->bind_param('ss', $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("No user found - Username: $username, Role: $role");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or role']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    error_log("User found - ID: {$user['id']}, Role: {$user['role']}, Status: {$user['status']}");
    
    // Check account status first
    if ($user['status'] !== 'active') {
        error_log("Inactive account - Username: $username, Role: $role, Status: {$user['status']}");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Your account is inactive. Please contact the administrator.']);
        exit;
    }
    
    // For testing, using direct password comparison
    if ($password === $user['password']) {
        // Clear any existing session data
        session_unset();
        session_destroy();
        session_start();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        error_log("Login successful - Session data: " . print_r($_SESSION, true));
        
        // Determine redirect URL based on role
        $base_url = '/gatemanagement';
        switch ($user['role']) {
            case 'admin':
                $redirect = $base_url . '/pages/admin/dashboard.php';
                break;
            case 'security':
                $redirect = $base_url . '/pages/security/dashboard.php';
                break;
            case 'resident':
                $redirect = $base_url . '/pages/resident/dashboard.php';
                break;
            default:
                $redirect = $base_url . '/pages/dashboard.php';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'redirect' => $redirect,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ]);
    } else {
        error_log("Password mismatch for user: $username");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
    
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
