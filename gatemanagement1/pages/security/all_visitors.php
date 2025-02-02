<?php
require_once '../../config/config.php';

// Check if user is logged in and has security role
if (!isLoggedIn() || !hasRole('security')) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$conn = connectDB();

// Get all visitors with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT 
        v.*,
        u.full_name as host_name,
        un.unit_number,
        un.block_number
    FROM visitors v
    JOIN users u ON v.host_id = u.id
    JOIN units un ON v.unit_id = un.id
    ORDER BY v.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$visitors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$result = $conn->query("SELECT COUNT(*) as count FROM visitors");
$total_visitors = $result->fetch_assoc()['count'];
$total_pages = ceil($total_visitors / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Visitors - Security Panel</title>
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
                    <li><a href="all_visitors.php" class="active">All Visitors</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>All Visitors</h1>
            </div>
            
            <div class="section">
                <div class="filters">
                    <select id="statusFilter" onchange="applyFilters()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="checked_out">Checked Out</option>
                    </select>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Host</th>
                                <th>Unit</th>
                                <th>Purpose</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitors as $visitor): ?>
                                <tr data-status="<?php echo htmlspecialchars($visitor['status']); ?>">
                                    <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['host_name']); ?></td>
                                    <td>Block <?php echo htmlspecialchars($visitor['block_number']); ?> - <?php echo htmlspecialchars($visitor['unit_number']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($visitor['created_at'])); ?></td>
                                    <td><?php echo $visitor['exit_time'] ? date('Y-m-d H:i', strtotime($visitor['exit_time'])) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($visitor['status']); ?>">
                                            <?php echo ucfirst($visitor['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-column">
                                        <?php if ($visitor['status'] === 'approved' && !$visitor['exit_time']): ?>
                                            <button onclick="checkoutVisitor(<?php echo $visitor['id']; ?>)" class="btn-action checkout">
                                                <i class="fas fa-sign-out-alt"></i> Checkout
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
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="prev">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?page=1">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="ellipsis">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="ellipsis">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="next">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
    function checkoutVisitor(visitorId) {
        if (confirm('Are you sure you want to checkout this visitor?')) {
            fetch('../../api/visitors.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: visitorId,
                    action: 'checkout',
                    exit_time: new Date().toISOString()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error checking out visitor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking out visitor. Please try again.');
            });
        }
    }

    function updateVisitorStatus(visitorId, status) {
        fetch('../../api/visitors.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: visitorId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating visitor status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating visitor status. Please try again.');
        });
    }

    function applyFilters() {
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (!statusFilter || status === statusFilter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
