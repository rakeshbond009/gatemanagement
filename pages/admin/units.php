<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Fetch units with owner information
$units = $conn->query("
    SELECT u.*, COALESCE(usr.full_name, 'Unassigned') as owner_name 
    FROM units u 
    LEFT JOIN users usr ON u.owner_id = usr.id 
    ORDER BY u.block_number, u.unit_number
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Management - Gate Management System</title>
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
                    <li><a href="units.php" class="active">Unit Management</a></li>
                    <li><a href="staff.php">Staff Management</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Unit Management</h1>
                <button class="btn" onclick="location.href='add_unit.php'">Add New Unit</button>
            </div>
            
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Block</th>
                            <th>Unit Number</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($unit = $units->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unit['block_number']); ?></td>
                            <td><?php echo htmlspecialchars($unit['unit_number']); ?></td>
                            <td><?php echo htmlspecialchars($unit['owner_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $unit['status']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($unit['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="location.href='edit_unit.php?id=<?php echo $unit['id']; ?>'" class="btn-small">Edit</button>
                                <button onclick="location.href='assign_owner.php?id=<?php echo $unit['id']; ?>'" class="btn-small">Assign Owner</button>
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
