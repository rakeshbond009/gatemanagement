<?php
require_once '../../config/config.php';

// Check if user is logged in and has security role
if (!isLoggedIn() || !hasRole('security')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();

// Fetch active visitors
$result = $conn->query("
    SELECT 
        v.*,
        u.full_name as host_name,
        un.unit_number,
        un.block_number
    FROM visitors v
    JOIN users u ON v.host_id = u.id
    JOIN units un ON v.unit_id = un.id
    WHERE v.exit_time IS NULL 
    AND v.status IN ('approved', 'inside')
    ORDER BY v.entry_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Check-out - Gate Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .search-container {
            margin-bottom: 20px;
        }
        .search-container input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .photo-preview {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80%;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>Security Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="check_in.php">Visitor Check-in</a></li>
                    <li><a href="check_out.php" class="active">Visitor Check-out</a></li>
                    <li><a href="visitors.php">All Visitors</a></li>
                    <li><a href="pre_approved.php">Pre-approved List</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Process Visitor Check-out</h1>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
            
            <div class="card">
                <div class="search-container">
                    <input type="text" id="visitorSearch" onkeyup="filterVisitors()" placeholder="Search by name, phone, or unit...">
                </div>
                
                <div class="table-container">
                    <?php if ($result && $result->num_rows > 0): ?>
                    <table id="visitorsTable">
                        <thead>
                            <tr>
                                <th>Visitor Name</th>
                                <th>Phone</th>
                                <th>Host</th>
                                <th>Unit</th>
                                <th>Entry Time</th>
                                <th>Purpose</th>
                                <th>Photo</th>
                                <th class="actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($visitor = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['host_name']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['block_number'] . '-' . $visitor['unit_number']); ?></td>
                                <td><?php echo date('H:i', strtotime($visitor['entry_time'])); ?></td>
                                <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                <td>
                                    <?php if ($visitor['photo_url']): ?>
                                    <img src="../../<?php echo htmlspecialchars($visitor['photo_url']); ?>" 
                                         alt="Visitor photo" 
                                         class="photo-preview"
                                         onclick="showFullImage(this.src)">
                                    <?php else: ?>
                                    No photo
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button class="btn-small primary" 
                                            onclick="processCheckout(<?php echo $visitor['id']; ?>)">
                                        Check-out
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>No active visitors to check out</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="this.style.display='none'">
        <img id="fullImage" class="modal-content">
    </div>
    
    <script>
    function filterVisitors() {
        const input = document.getElementById('visitorSearch');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('visitorsTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length - 2; j++) {
                const cell = cells[j];
                if (cell) {
                    const text = cell.textContent || cell.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    }
    
    function showFullImage(src) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('fullImage');
        modal.style.display = 'block';
        modalImg.src = src;
    }
    
    async function processCheckout(visitorId) {
        if (confirm('Are you sure you want to check out this visitor?')) {
            try {
                const response = await fetch('../../api/visitors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        visitor_id: visitorId,
                        status: 'left'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Visitor checked out successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while checking out the visitor');
            }
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
