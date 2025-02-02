<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    
    // Validate input
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($phone) || empty($role) || empty($status)) {
        $error = "All fields are required";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $username, $hashed_password, $full_name, $email, $phone, $role, $status);
            
            if ($stmt->execute()) {
                $success = "User created successfully";
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Gate Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            margin: 10px 0;
            background: #ffebee;
            border-radius: 4px;
        }
        .success-message {
            color: #4caf50;
            padding: 10px;
            margin: 10px 0;
            background: #e8f5e9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php" class="active">User Management</a></li>
                    <li><a href="units.php">Unit Management</a></li>
                    <li><a href="staff.php">Staff Management</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Add New User</h1>
                <a href="users.php" class="btn">Back to Users</a>
            </div>
            
            <div class="card">
                <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="resident">Resident</option>
                            <option value="staff">Staff</option>
                            <option value="security">Security</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn primary">Create User</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
