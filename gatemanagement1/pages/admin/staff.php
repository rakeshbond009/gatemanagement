<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Fetch all security staff
$staff = $conn->query("
    SELECT u.*, 
           CASE 
               WHEN sa.check_out IS NULL AND sa.check_in IS NOT NULL THEN 'On Duty'
               ELSE 'Off Duty'
           END as duty_status
    FROM users u 
    LEFT JOIN staff_attendance sa ON u.id = sa.staff_id 
    AND DATE(sa.check_in) = CURDATE()
    WHERE u.role = 'security'
    ORDER BY u.full_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Gate Management System</title>
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
                    <li><a href="staff.php" class="active">Staff Management</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Staff Management</h1>
                <button class="btn" onclick="location.href='add_staff.php'">Add New Staff</button>
            </div>
            
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Duty Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $staff->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $member['status']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $member['duty_status'])); ?>">
                                    <?php echo htmlspecialchars($member['duty_status']); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="location.href='edit_staff.php?id=<?php echo $member['id']; ?>'" class="btn-small">Edit</button>
                                <button onclick="location.href='attendance.php?staff_id=<?php echo $member['id']; ?>'" class="btn-small">View Attendance</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
