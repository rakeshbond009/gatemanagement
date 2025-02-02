<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Handle user status toggle if requested
if (isset($_POST['toggle_status']) && isset($_POST['user_id']) && isset($_POST['new_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
}

// Handle search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Fetch users with search and pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query with search conditions
$query = "SELECT * FROM users WHERE role != 'admin'";
$params = [];
$types = "";

if (!empty($search)) {
    $search = "%$search%";
    $query .= " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Count total users for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as count", substr($query, 0, strpos($query, " LIMIT")));
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    array_splice($params, -2); // Remove LIMIT parameters
    $types = substr($types, 0, -2); // Remove 'ii' from types
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_users / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Gate Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .search-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        .search-filters input,
        .search-filters select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            height: 38px;
        }
        .search-filters .btn {
            height: 38px;
            padding: 0 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .pagination a:hover:not(.active) {
            background: #f5f5f5;
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
                <h1>User Management</h1>
                <button class="btn" onclick="location.href='add_user.php'">Add New User</button>
            </div>
            
            <div class="card">
                <form method="get" class="search-filters">
                    <input type="text" name="search" placeholder="Search by name, email, or phone" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="resident" <?php echo $role_filter === 'resident' ? 'selected' : ''; ?>>Resident</option>
                        <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="security" <?php echo $role_filter === 'security' ? 'selected' : ''; ?>>Security</option>
                    </select>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <button type="submit" class="btn">Search</button>
                    <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                        <a href="users.php" class="btn">Clear Filters</a>
                    <?php endif; ?>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($user['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php 
                                    $next_status = '';
                                    $button_text = '';
                                    $button_class = '';
                                    
                                    switch($user['status']) {
                                        case 'active':
                                            $next_status = 'inactive';
                                            $button_text = 'Deactivate';
                                            $button_class = 'danger';
                                            break;
                                        case 'inactive':
                                            $next_status = 'rejected';
                                            $button_text = 'Reject';
                                            $button_class = 'danger';
                                            break;
                                        case 'rejected':
                                        default:
                                            $next_status = 'active';
                                            $button_text = 'Activate';
                                            $button_class = 'success';
                                            break;
                                    }
                                    ?>
                                    <input type="hidden" name="new_status" value="<?php echo $next_status; ?>">
                                    <button type="submit" name="toggle_status" class="btn-small <?php echo $button_class; ?>">
                                        <?php echo $button_text; ?>
                                    </button>
                                </form>
                                <button onclick="location.href='edit_user.php?id=<?php echo $user['id']; ?>'" class="btn-small">Edit</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($users->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No users found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                           class="<?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
