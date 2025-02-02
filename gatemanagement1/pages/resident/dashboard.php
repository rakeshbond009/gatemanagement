<?php
require_once '../../config/config.php';

// Check if user is logged in and has resident role
if (!isLoggedIn() || !hasRole('resident')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$error = '';
$unit = null;
$today_visitors = 0;
$pending_visitors = 0;
$recent_visitors = null;

// Get resident's unit information
$query = "
    SELECT u.* 
    FROM units u 
    JOIN user_units uu ON u.id = uu.unit_id 
    WHERE uu.user_id = ?
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unit = $stmt->get_result()->fetch_assoc();

    if ($unit) {
        // Get today's visitors count
        $query = "
            SELECT COUNT(*) as count 
            FROM visitors v
            JOIN units u ON v.unit_id = u.id
            JOIN user_units uu ON u.id = uu.unit_id
            WHERE uu.user_id = ? AND DATE(v.entry_time) = CURDATE()
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $today_visitors = $stmt->get_result()->fetch_assoc()['count'];
        }

        // Get pending visitors count
        $query = "
            SELECT COUNT(*) as count 
            FROM visitors v
            JOIN units u ON v.unit_id = u.id
            JOIN user_units uu ON u.id = uu.unit_id
            WHERE uu.user_id = ? AND v.status = 'pending'
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $pending_visitors = $stmt->get_result()->fetch_assoc()['count'];
        }

        // Get recent visitors
        $query = "
            SELECT v.*, u.unit_number
            FROM visitors v
            JOIN units u ON v.unit_id = u.id
            JOIN user_units uu ON u.id = uu.unit_id
            WHERE uu.user_id = ?
            ORDER BY v.entry_time DESC
            LIMIT 5
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $recent_visitors = $stmt->get_result();
        }
    } else {
        $error = "No unit is assigned to your account. Please contact the administrator.";
    }
} else {
    $error = "Database error. Please try again later.";
}

// Get pending visitor requests
$stmt = $conn->prepare("
    SELECT 
        v.*,
        un.unit_number,
        un.block_number
    FROM visitors v
    JOIN units un ON v.unit_id = un.id
    WHERE v.host_id = ? 
    AND v.status = 'pending'
    ORDER BY v.created_at DESC
");

$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$pending_visitors_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent visitors
$stmt = $conn->prepare("
    SELECT 
        v.*,
        un.unit_number,
        un.block_number
    FROM visitors v
    JOIN units un ON v.unit_id = un.id
    WHERE v.host_id = ? 
    AND v.status != 'pending'
    ORDER BY v.created_at DESC
    LIMIT 10
");

$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$recent_visitors_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unread notifications
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    AND is_read = 0
    ORDER BY created_at DESC
");

$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - Gate Management System</title>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="visitors.php">My Visitors</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
                <?php if ($unit): ?>
                    <p>Block <?php echo htmlspecialchars($unit['block_number']); ?> - Unit <?php echo htmlspecialchars($unit['unit_number']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="stats-container">
                    <div class="card">
                        <h3>Today's Visitors</h3>
                        <p class="stat"><?php echo $today_visitors; ?></p>
                    </div>
                    
                    <div class="card pending-approvals" onclick="window.location.href='visitors.php?filter=pending'">
                        <h3>Pending Approvals</h3>
                        <p class="stat"><?php echo $pending_visitors; ?></p>
                        <p>Click to view</p>
                    </div>
                </div>
                
                <?php if (count($pending_visitors_requests) > 0): ?>
                <div class="section">
                    <h2>Pending Visitor Requests</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Visitor Name</th>
                                    <th>Mobile</th>
                                    <th>Purpose</th>
                                    <th>Photo</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_visitors_requests as $visitor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                        <td class="photo-cell">
                                            <?php if ($visitor['photo_url']): ?>
                                                <img src="../../<?php echo htmlspecialchars($visitor['photo_url']); ?>" 
                                                     alt="Visitor Photo" 
                                                     class="visitor-photo">
                                            <?php else: ?>
                                                No photo
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <div class="action-buttons">
                                                <button class="btn-small success" onclick="handleVisitor(<?php echo $visitor['id']; ?>, 'approve')">
                                                    Approve
                                                </button>
                                                <button class="btn-small danger" onclick="handleVisitor(<?php echo $visitor['id']; ?>, 'reject')">
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="section">
                    <h2>Recent Visitors</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Visitor Name</th>
                                    <th>Mobile</th>
                                    <th>Purpose</th>
                                    <th>Entry Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($visitor = $recent_visitors->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($visitor['entry_time'])); ?></td>
                                        <td><?php echo ucfirst($visitor['status']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
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
