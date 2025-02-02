<?php
require_once '../../config/config.php';

// Check if user is logged in and has resident role
if (!isLoggedIn() || !hasRole('resident')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Get all visitors with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM visitors v
    JOIN units u ON v.unit_id = u.id
    JOIN user_units uu ON u.id = uu.unit_id
    WHERE uu.user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_visitors = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_visitors / $limit);

// Get visitors for current page
$stmt = $conn->prepare("
    SELECT 
        v.*,
        u.unit_number,
        u.block_number
    FROM visitors v
    JOIN units u ON v.unit_id = u.id
    JOIN user_units uu ON u.id = uu.unit_id
    WHERE uu.user_id = ?
    ORDER BY v.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $user_id, $limit, $offset);
$stmt->execute();
$visitors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Visitors - Resident Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <button class="mobile-menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i> Menu
        </button>
        <div class="sidebar" id="sidebar">
            <h2>Resident Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="visitors.php" class="active">My Visitors</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Visitors</h1>
            </div>
            
            <div class="section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Visitor Name</th>
                                <th>Phone</th>
                                <th>Purpose</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitors as $visitor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                    <td><?php echo $visitor['entry_time'] ? date('Y-m-d H:i', strtotime($visitor['entry_time'])) : '-'; ?></td>
                                    <td><?php echo $visitor['exit_time'] ? date('Y-m-d H:i', strtotime($visitor['exit_time'])) : '-'; ?></td>
                                    <td><?php echo ucfirst($visitor['status']); ?></td>
                                    <td class="actions">
                                        <?php if ($visitor['status'] === 'pending'): ?>
                                            <button class="btn-small success" onclick="handleVisitor(<?php echo $visitor['id']; ?>, 'approve')">
                                                Approve
                                            </button>
                                            <button class="btn-small danger" onclick="handleVisitor(<?php echo $visitor['id']; ?>, 'reject')">
                                                Reject
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
    function handleVisitor(visitorId, action) {
        if (!confirm(`Are you sure you want to ${action} this visitor?`)) {
            return;
        }
        
        fetch('../../api/visitors.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                visitor_id: visitorId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Visitor ${action}ed successfully`);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your request');
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
