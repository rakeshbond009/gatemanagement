<?php
require_once '../../config/config.php';

// Debug session data
error_log("Session data: " . print_r($_SESSION, true));
error_log("Is logged in: " . (isLoggedIn() ? 'yes' : 'no'));
error_log("Has security role: " . (hasRole('security') ? 'yes' : 'no'));

// Check if user is logged in and has security role
if (!isLoggedIn() || !hasRole('security')) {
    error_log("Access denied - User not logged in or not security role");
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$conn = connectDB();

// Get today's statistics
$today = date('Y-m-d');
$stats = [
    'total_today' => 0,
    'active_visitors' => 0,
    'rejected_visitors' => 0
];

// Total visitors today
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM visitors 
    WHERE DATE(created_at) = ?
");
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_today'] = $result->fetch_assoc()['count'];

// Active visitors (approved but not left)
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM visitors 
    WHERE status = 'approved' 
    AND exit_time IS NULL
");
$stmt->execute();
$result = $stmt->get_result();
$stats['active_visitors'] = $result->fetch_assoc()['count'];

// Rejected visitors today
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM visitors 
    WHERE status = 'rejected'
    AND DATE(created_at) = ?
");
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();
$stats['rejected_visitors'] = $result->fetch_assoc()['count'];

// Get all active users for the check-in form
$stmt = $conn->prepare("
    SELECT id, full_name 
    FROM users 
    WHERE status = 'active'
    ORDER BY full_name
");
$stmt->execute();
$hosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent visitors
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
    LIMIT 10
");
$stmt->execute();
$recent_visitors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>Security Dashboard - Gate Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin: 0;
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        .stat-card p {
            font-size: 2em;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
        .check-in-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .camera-container {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
        }
        #videoElement {
            width: 100%;
            border-radius: 8px;
        }
        #photoCanvas {
            display: none;
        }
        .preview-container {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            border: 2px dashed #ccc;
            border-radius: 8px;
            overflow: hidden;
        }
        #photoPreview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        .camera-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-approved { background: #e8f5e9; color: #388e3c; }
        .status-rejected { background: #fbe9e7; color: #d32f2f; }
        .status-inside { background: #e3f2fd; color: #1976d2; }
        .status-left { background: #f5f5f5; color: #616161; }
        #notificationSound {
            display: none;
        }
        .camera-section {
            margin: 1rem 0;
        }
        .camera-container {
            max-width: 400px;
            margin: 1rem 0;
        }
        #camera {
            width: 100%;
            height: auto;
            background: #f0f0f0;
            margin-bottom: 1rem;
        }
        .camera-controls {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
        }
        .photo-preview {
            width: 100%;
            height: 300px;
            background: #f0f0f0;
            display: none;
            margin: 1rem 0;
            background-size: cover;
            background-position: center;
        }
        .camera-error {
            color: #dc3545;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8d7da;
            border-radius: 4px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <button class="mobile-menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar" id="sidebar">
            <h2>Security Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="all_visitors.php">All Visitors</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Today's Visitors</h3>
                    <p><?php echo $stats['total_today']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3>Active Visitors</h3>
                    <p><?php echo $stats['active_visitors']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-ban"></i>
                    <h3>Rejected Today</h3>
                    <p><?php echo $stats['rejected_visitors']; ?></p>
                </div>
            </div>

            <div class="section">
                <h2>Pending Checkouts</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Host</th>
                                <th>Unit</th>
                                <th>Entry Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get pending checkouts (approved visitors who haven't left)
                            $stmt = $conn->prepare("
                                SELECT 
                                    v.*,
                                    u.full_name as host_name,
                                    un.unit_number,
                                    un.block_number
                                FROM visitors v
                                JOIN users u ON v.host_id = u.id
                                JOIN units un ON v.unit_id = un.id
                                WHERE v.status = 'approved' 
                                AND v.exit_time IS NULL
                                ORDER BY v.created_at ASC
                            ");
                            $stmt->execute();
                            $pending_checkouts = $stmt->get_result();
                            
                            while ($visitor = $pending_checkouts->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['host_name']); ?></td>
                                <td>Block <?php echo htmlspecialchars($visitor['block_number']); ?> - <?php echo htmlspecialchars($visitor['unit_number']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($visitor['created_at'])); ?></td>
                                <td>
                                    <button onclick="checkoutVisitor(<?php echo $visitor['id']; ?>)" class="btn-action checkout">
                                        <i class="fas fa-sign-out-alt"></i> Checkout
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="section">
                <h2>Check-in New Visitor</h2>
                <form id="visitorForm" class="check-in-form">
                    <div class="form-group">
                        <label for="visitorName">Visitor Name</label>
                        <input type="text" id="visitorName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="visitorPhone">Phone Number</label>
                        <input type="tel" id="visitorPhone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="hostId">Select Host</label>
                        <select id="hostId" name="host_id" class="searchable-select" required>
                            <option value="">Select Host</option>
                            <?php foreach ($hosts as $host): ?>
                                <option value="<?php echo $host['id']; ?>">
                                    <?php echo htmlspecialchars($host['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="purpose">Purpose of Visit</label>
                        <input type="text" id="purpose" name="purpose" required>
                    </div>

                    <div class="camera-section">
                        <label>Visitor Photo</label>
                        <div class="camera-container">
                            <video id="camera" autoplay playsinline style="width: 100%; height: auto;"></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                            <div id="photoPreview" class="photo-preview"></div>
                            <div class="camera-controls">
                                <button type="button" id="capturePhoto" class="btn-action">
                                    <i class="fas fa-camera"></i> Take Photo
                                </button>
                                <button type="button" id="retakePhoto" class="btn-action" style="display: none;">
                                    <i class="fas fa-redo"></i> Retake
                                </button>
                                <button type="button" id="startCamera" class="btn-action" style="display: none;">
                                    <i class="fas fa-video"></i> Start Camera
                                </button>
                            </div>
                            <div class="camera-error" style="display: none;"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Check-in Visitor</button>
                </form>
            </div>
            
            <div class="section">
                <h2>Recent Visitors</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Visitor Name</th>
                                <th>Host</th>
                                <th>Purpose</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_visitors as $visitor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($visitor['host_name']); ?><br>
                                    <small>Block <?php echo $visitor['block_number']; ?> - 
                                           Unit <?php echo $visitor['unit_number']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                <td><?php echo date('H:i', strtotime($visitor['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($visitor['status']); ?>">
                                        <?php echo ucfirst($visitor['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="section">
                <h2>Rejected Visitors</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Host</th>
                                <th>Purpose</th>
                                <th>Rejection Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get rejected visitors
                            $query = "
                                SELECT 
                                    v.*,
                                    u.full_name as host_name
                                FROM visitors v
                                LEFT JOIN users u ON v.host_id = u.id
                                WHERE v.status = 'rejected'
                                ORDER BY v.created_at DESC
                                LIMIT 10
                            ";
                            
                            try {
                                $stmt = $conn->prepare($query);
                                if ($stmt === false) {
                                    throw new Exception("Failed to prepare statement: " . $conn->error);
                                }
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Failed to execute query: " . $stmt->error);
                                }
                                
                                $rejected_visitors = $stmt->get_result();
                                
                                if ($rejected_visitors->num_rows === 0) {
                                    echo "<tr><td colspan='6'>No rejected visitors found</td></tr>";
                                } else {
                                    while ($visitor = $rejected_visitors->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['host_name']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($visitor['created_at'])); ?></td>
                                            <td>
                                                <button onclick="handleRejectedVisitor(<?php echo $visitor['id']; ?>)" class="btn-action">
                                                    <i class="fas fa-exclamation-circle"></i> Handle
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                            } catch (Exception $e) {
                                error_log("Error in rejected visitors query: " . $e->getMessage());
                                echo "<tr><td colspan='6'>Error loading rejected visitors: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification sound -->
    <audio id="notificationSound" preload="auto">
        <source src="../../assets/sounds/notification.mp3" type="audio/mpeg">
    </audio>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize Select2 for searchable dropdown
        $('.searchable-select').select2({
            placeholder: 'Search for a host...',
            allowClear: true,
            width: '100%'
        });
    });
    </script>
    <script src="../../assets/js/main.js"></script>
    <script>
    // Camera handling
    document.addEventListener('DOMContentLoaded', function() {
        const camera = document.getElementById('camera');
        const canvas = document.getElementById('canvas');
        const startButton = document.getElementById('startCamera');
        const captureButton = document.getElementById('capturePhoto');
        const retakeButton = document.getElementById('retakePhoto');
        const photoPreview = document.getElementById('photoPreview');
        let stream = null;

        // Start camera when button is clicked
        startButton.addEventListener('click', async function() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'user'
                    } 
                });
                camera.srcObject = stream;
                startButton.style.display = 'none';
                captureButton.style.display = 'inline-block';
                camera.style.display = 'block';
                photoPreview.style.display = 'none';
            } catch (err) {
                console.error('Camera error:', err);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'camera-error';
                errorDiv.textContent = 'Could not access camera. Please check your permissions and try again.';
                camera.parentNode.insertBefore(errorDiv, camera);
            }
        });

        // Capture photo
        captureButton.addEventListener('click', function() {
            canvas.width = camera.videoWidth;
            canvas.height = camera.videoHeight;
            canvas.getContext('2d').drawImage(camera, 0, 0);
            
            // Display preview
            const photoData = canvas.toDataURL('image/jpeg');
            photoPreview.style.backgroundImage = `url(${photoData})`;
            photoPreview.style.display = 'block';
            
            // Hide camera, show retake
            camera.style.display = 'none';
            captureButton.style.display = 'none';
            retakeButton.style.display = 'inline-block';
            
            // Store photo data
            document.getElementById('visitorForm').dataset.photo = photoData;
        });

        // Retake photo
        retakeButton.addEventListener('click', function() {
            camera.style.display = 'block';
            photoPreview.style.display = 'none';
            captureButton.style.display = 'inline-block';
            retakeButton.style.display = 'none';
            delete document.getElementById('visitorForm').dataset.photo;
        });

        // Form submission
        document.getElementById('visitorForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const photoData = this.dataset.photo;
            if (!photoData) {
                alert('Please take a photo of the visitor');
                return;
            }

            const formData = {
                name: document.getElementById('visitorName').value,
                phone: document.getElementById('visitorPhone').value,
                host_id: document.getElementById('hostId').value,
                purpose: document.getElementById('purpose').value,
                photo: photoData
            };

            try {
                const response = await fetch('../../api/visitors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.success) {
                    alert('Visitor checked in successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error checking in visitor. Please try again.');
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    });
    </script>
    <script>
    // Check for new notifications
    let lastNotificationCount = <?php echo count($notifications); ?>;
    
    async function checkNotifications() {
        try {
            const response = await fetch('../../api/notifications.php');
            const data = await response.json();
            
            if (data.success && data.notifications.length > lastNotificationCount) {
                // Play notification sound
                const audio = document.getElementById('notificationSound');
                audio.play();
                
                // Update notification count
                lastNotificationCount = data.notifications.length;
                
                // Reload page to show new notifications
                location.reload();
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }
    
    // Check for notifications every 30 seconds
    setInterval(checkNotifications, 30000);
    
    function checkoutVisitor(visitorId) {
        if (confirm('Are you sure you want to checkout this visitor?')) {
            fetch('../../api/visitors.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: visitorId,
                    action: 'checkout'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error checking out visitor: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking out visitor. Please try again.');
            });
        }
    }
    
    function handleRejectedVisitor(visitorId) {
        const action = confirm('What action would you like to take?\n\nOK - Allow visitor to re-register\nCancel - Mark as handled');
        
        fetch('../../api/visitors.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: visitorId,
                action: action ? 're_register' : 'mark_handled',
                status: action ? 'pending' : 'handled'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error handling rejected visitor. Please try again.');
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
