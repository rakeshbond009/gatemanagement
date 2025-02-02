<?php
require_once '../../config/config.php';

// Check if user is logged in and has security role
if (!isLoggedIn() || !hasRole('security')) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$conn = connectDB();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    if ($current_password === $user['password']) {
        $update_password = !empty($new_password);
        
        if ($update_password) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->bind_param('sssi', $full_name, $phone, $new_password, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->bind_param('ssi', $full_name, $phone, $_SESSION['user_id']);
        }
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully";
        } else {
            $error = "Error updating profile";
        }
    } else {
        $error = "Current password is incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Security Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <button class="mobile-menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i> Menu
        </button>
        <div class="sidebar" id="sidebar">
            <h2>Security Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="all_visitors.php">All Visitors</a></li>
                    <li><a href="profile.php" class="active">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Profile</h1>
            </div>
            
            <div class="section">
                <div class="card">
                    <?php if (isset($success)): ?>
                        <div class="alert success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password (leave blank to keep current)</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <button type="submit" class="btn">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
