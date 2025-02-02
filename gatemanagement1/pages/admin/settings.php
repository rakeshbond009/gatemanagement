<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password === $confirm_password) {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Failed to update password.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Gate Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="units.php">Unit Management</a></li>
                    <li><a href="staff.php">Staff Management</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="settings.php" class="active">Settings</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Settings</h1>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert error">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h3>Change Password</h3>
                <form method="post" class="settings-form">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn">Update Password</button>
                </form>
            </div>
            
            <div class="card">
                <h3>System Settings</h3>
                <form method="post" class="settings-form">
                    <div class="form-group">
                        <label>Society Name</label>
                        <input type="text" name="society_name" value="My Society">
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" value="contact@society.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <input type="tel" name="emergency_contact" value="911">
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
