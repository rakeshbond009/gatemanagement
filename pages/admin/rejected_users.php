<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Handle reactivation of rejected users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Send notification about reactivation
        $notify_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, 'Registration Status', 'Your registration has been reactivated for review.', 'announcement')
        ");
        $notify_stmt->bind_param("i", $user_id);
        $notify_stmt->execute();
        
        header('Location: rejected_users.php?success=reactivated');
        exit;
    }
}

// Fetch rejected users
$result = $conn->query("
    SELECT id, username, full_name, email, phone, role, created_at as entry_time 
    FROM users 
    WHERE status = 'rejected' 
    ORDER BY created_at DESC
");

if (!$result) {
    die("Error fetching rejected users: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected Users - Gate Management System</title>
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
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Rejected Users</h1>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert success">
                User successfully reactivated for review!
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-container">
                    <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Registration Date</th>
                                <th class="actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($user['entry_time'])); ?></td>
                                <td class="actions">
                                    <form method="post" class="action-buttons">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-small success" onclick="return confirm('Are you sure you want to reactivate this user for review?')">
                                            Reactivate
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>No rejected users at this time.</p>
                        <a href="dashboard.php" class="btn">Back to Dashboard</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
