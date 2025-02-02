<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Handle user approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id']) && isset($_POST['action'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        // Validate user exists and is in pending state
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'inactive'");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            header('Location: pending_approvals.php?error=invalid_user');
            exit;
        }
        
        // Set the new status based on action
        $new_status = ($action === 'approve') ? 'active' : 'rejected';
        $status_message = ($action === 'approve') 
            ? "Your account has been approved. You can now login."
            : "Your registration has been rejected.";
        
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            header('Location: pending_approvals.php?error=db_error');
            exit;
        }
        
        $stmt->bind_param("si", $new_status, $user_id);
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            header('Location: pending_approvals.php?error=update_failed');
            exit;
        }
        
        if ($stmt->affected_rows > 0) {
            // Send notification to user
            $notify_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, 'Account Status', ?, 'announcement')
            ");
            $notify_stmt->bind_param("is", $user_id, $status_message);
            $notify_stmt->execute();
            
            header('Location: pending_approvals.php?success=true&action=' . $action);
            exit;
        } else {
            error_log("No rows affected for user_id: $user_id, action: $action");
            header('Location: pending_approvals.php?error=no_update');
            exit;
        }
    }
}

// Fetch pending users
$result = $conn->query("
    SELECT id, username, full_name, email, phone, role, created_at as entry_time 
    FROM users 
    WHERE status = 'inactive' 
    ORDER BY created_at DESC
");

if (!$result) {
    die("Error fetching pending users: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Gate Management System</title>
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
                <h1>Pending Approvals</h1>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert error">
                <?php
                switch ($_GET['error']) {
                    case 'invalid_user':
                        echo 'Invalid user or user already processed.';
                        break;
                    case 'db_error':
                        echo 'Database error occurred. Please try again.';
                        break;
                    case 'update_failed':
                        echo 'Failed to update user status. Please try again.';
                        break;
                    case 'no_update':
                        echo 'No changes were made. User may have already been processed.';
                        break;
                    default:
                        echo 'An error occurred. Please try again.';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert success">
                User successfully <?php echo $_GET['action'] === 'approve' ? 'approved' : 'rejected'; ?>!
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
                                        <button type="submit" name="action" value="approve" class="btn-small success">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-small error">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>No pending approvals at this time.</p>
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
