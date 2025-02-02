<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Get date range from query parameters or default to last 7 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $end_date;

// Fetch visitor statistics
$visitor_stats = $conn->query("
    SELECT 
        DATE(entry_time) as date,
        COUNT(*) as total_visitors,
        SUM(CASE WHEN status = 'inside' THEN 1 ELSE 0 END) as currently_inside
    FROM visitors 
    WHERE DATE(entry_time) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(entry_time)
    ORDER BY date DESC
");

// Fetch staff attendance summary
$staff_stats = $conn->query("
    SELECT 
        u.full_name,
        COUNT(sa.id) as total_shifts,
        AVG(TIMESTAMPDIFF(HOUR, sa.check_in, COALESCE(sa.check_out, NOW()))) as avg_hours
    FROM users u
    LEFT JOIN staff_attendance sa ON u.id = sa.staff_id
    WHERE u.role = 'security'
    AND DATE(sa.check_in) BETWEEN '$start_date' AND '$end_date'
    GROUP BY u.id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Gate Management System</title>
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
                    <li><a href="reports.php" class="active">Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Reports</h1>
                <div class="date-filter">
                    <form method="get">
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                        <button type="submit" class="btn">Filter</button>
                    </form>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="card">
                    <h3>Visitor Statistics</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Visitors</th>
                                <th>Currently Inside</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($stat = $visitor_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($stat['date'])); ?></td>
                                <td><?php echo $stat['total_visitors']; ?></td>
                                <td><?php echo $stat['currently_inside']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <h3>Staff Attendance Summary</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Total Shifts</th>
                                <th>Average Hours/Shift</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($stat = $staff_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['full_name']); ?></td>
                                <td><?php echo $stat['total_shifts']; ?></td>
                                <td><?php echo number_format($stat['avg_hours'], 1); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <h3>Export Options</h3>
                <div class="action-buttons">
                    <button onclick="exportReport('visitors')" class="btn">Export Visitor Report</button>
                    <button onclick="exportReport('staff')" class="btn">Export Staff Report</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
