<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gate Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <button class="mobile-menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i> Menu
        </button>
        <div class="sidebar" id="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="#" class="active">Dashboard</a></li>
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
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
                <p>Admin Dashboard</p>
            </div>
            
            <div class="stats-container">
                <?php
                // Get pending approvals count
                $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'");
                $pendingApprovals = $result ? $result->fetch_assoc()['count'] : 0;
                
                // Get rejected users count
                $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'rejected'");
                $rejectedUsers = $result ? $result->fetch_assoc()['count'] : 0;
                ?>
                <div class="card pending-approvals" onclick="location.href='pending_approvals.php'">
                    <h3>Pending Approvals</h3>
                    <p class="stat"><?php echo $pendingApprovals; ?></p>
                    <p><?php echo $pendingApprovals == 0 ? 'No pending requests' : 'Click to review'; ?></p>
                </div>
                
                <div class="card rejected-users" onclick="location.href='rejected_users.php'">
                    <h3>Rejected Users</h3>
                    <p class="stat"><?php echo $rejectedUsers; ?></p>
                    <p><?php echo $rejectedUsers == 0 ? 'No rejected users' : 'Click to view'; ?></p>
                </div>
                
                <div class="card">
                    <h3>Total Users</h3>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'resident'");
                    $residents = $result->fetch_assoc()['count'];
                    ?>
                    <p class="stat"><?php echo $residents; ?></p>
                    <p>Residents</p>
                </div>
                
                <div class="card">
                    <h3>Security Staff</h3>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'security'");
                    $security = $result->fetch_assoc()['count'];
                    ?>
                    <p class="stat"><?php echo $security; ?></p>
                    <p>Active Staff</p>
                </div>
                
                <div class="card">
                    <h3>Total Units</h3>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM units");
                    $units = $result->fetch_assoc()['count'];
                    ?>
                    <p class="stat"><?php echo $units; ?></p>
                    <p>Registered Units</p>
                </div>
                
                <div class="card">
                    <h3>Today's Visitors</h3>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM visitors WHERE DATE(entry_time) = CURDATE()");
                    $visitors = $result->fetch_assoc()['count'];
                    ?>
                    <p class="stat"><?php echo $visitors; ?></p>
                    <p>Visitors Today</p>
                </div>
            </div>
            
            <div class="recent-activity card">
                <h3>Recent Activity</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>Details</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT 
                                created_at as time,
                                'New Registration' as event_type,
                                CONCAT(full_name, ' (', role, ')') as details,
                                status
                            FROM users
                            WHERE status = 'inactive'
                            UNION ALL
                            SELECT 
                                entry_time as time,
                                'Visitor' as event_type,
                                CONCAT(v.name, ' to Unit ', COALESCE(u.unit_number, 'Unknown')) as details,
                                v.status
                            FROM visitors v
                            LEFT JOIN units u ON v.unit_id = u.id
                            WHERE v.entry_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                            ORDER BY time DESC
                            LIMIT 5
                        ");
                        
                        if ($result && $result->num_rows > 0) {
                            while ($activity = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . date('H:i', strtotime($activity['time'])) . "</td>";
                                echo "<td>" . htmlspecialchars($activity['event_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($activity['details']) . "</td>";
                                echo "<td>" . htmlspecialchars($activity['status']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>No recent activity</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="quick-actions card">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <button onclick="location.href='add_user.php'" class="btn">Add New User</button>
                    <button onclick="location.href='add_unit.php'" class="btn">Add New Unit</button>
                    <button onclick="location.href='add_staff.php'" class="btn">Add Staff</button>
                    <button onclick="location.href='send_notice.php'" class="btn">Send Notice</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
