<?php
require_once '../../config/config.php';

// Check if user is logged in and has security role
if (!isLoggedIn() || !hasRole('security')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();
$error = '';

// Fetch all residents
$query = "
    SELECT u.id, u.full_name, u.phone, un.unit_number, un.block_number
    FROM users u 
    JOIN user_units uu ON u.id = uu.user_id
    JOIN units un ON uu.unit_id = un.id
    WHERE u.status = 'active' AND u.role = 'resident'
    ORDER BY un.block_number, un.unit_number
";

$result = $conn->query($query);

if (!$result) {
    $error = "Database error: " . $conn->error;
    error_log("Query error in check_in.php: " . $conn->error);
}

$residents = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $residents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Check-in - Gate Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        #camera-container {
            width: 320px;
            height: 240px;
            border: 1px solid #ccc;
            margin: 10px 0;
        }
        #video {
            width: 320px;
            height: 240px;
            object-fit: cover;
        }
        #photo-preview {
            display: none;
            width: 320px;
            height: 240px;
            object-fit: cover;
            margin: 10px 0;
        }
        .camera-buttons {
            margin: 10px 0;
        }
        .user-select {
            width: 100% !important;
            padding: 8px;
            margin: 10px 0;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            margin: 10px 0;
            background: #ffebee;
            border-radius: 4px;
        }
        .select2-container {
            width: 100% !important;
        }
        .select2-selection {
            height: 38px !important;
            padding: 4px !important;
        }
        .select2-selection__arrow {
            height: 36px !important;
        }
        .user-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
        }
        .user-info {
            display: flex;
            flex-direction: column;
        }
        .user-name {
            font-weight: bold;
        }
        .user-details {
            font-size: 0.8em;
            color: #666;
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
                    <li><a href="check_in.php" class="active">Visitor Check-in</a></li>
                    <li><a href="check_out.php">Visitor Check-out</a></li>
                    <li><a href="visitors.php">All Visitors</a></li>
                    <li><a href="pre_approved.php">Pre-approved List</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>New Visitor Check-in</h1>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <form id="check-in-form" onsubmit="return handleCheckIn(event)">
                    <div class="form-group">
                        <label for="visitor_name">Visitor Name *</label>
                        <input type="text" id="visitor_name" name="visitor_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobile">Mobile Number *</label>
                        <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="host_id">Resident to Meet *</label>
                        <?php if (count($residents) > 0): ?>
                        <select id="host_id" name="host_id" class="user-select" required>
                            <option value="">Search by name, unit, or phone number</option>
                            <?php foreach ($residents as $resident): ?>
                            <option value="<?php echo $resident['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($resident['full_name']); ?>"
                                    data-phone="<?php echo htmlspecialchars($resident['phone']); ?>"
                                    data-unit="<?php echo htmlspecialchars($resident['block_number'] . '-' . $resident['unit_number']); ?>">
                                <?php echo htmlspecialchars($resident['full_name']); ?> - 
                                <?php echo htmlspecialchars($resident['block_number'] . '-' . $resident['unit_number']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <div class="error-message">No residents found in the system.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="purpose">Purpose of Visit *</label>
                        <input type="text" id="purpose" name="purpose" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Visitor Photo *</label>
                        <div id="camera-container">
                            <video id="video" autoplay playsinline></video>
                        </div>
                        <img id="photo-preview" alt="Captured photo">
                        <input type="hidden" id="photo_data" name="photo_data">
                        <div class="camera-buttons">
                            <button type="button" class="btn" id="capture-btn">Capture Photo</button>
                            <button type="button" class="btn" id="retake-btn" style="display: none;">Retake Photo</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn primary">Submit Check-in</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    // Initialize Select2
    $(document).ready(function() {
        $('.user-select').select2({
            placeholder: 'Search by name, unit, or phone number',
            templateResult: formatUser,
            templateSelection: formatUserSelection
        });
        
        // Initialize camera
        initCamera();
    });
    
    function formatUser(user) {
        if (!user.id) return user.text;
        
        const $user = $(user.element);
        const name = $user.data('name');
        const phone = $user.data('phone');
        const unit = $user.data('unit');
        
        return $(`
            <div class="user-option">
                <div class="user-info">
                    <div class="user-name">${name}</div>
                    <div class="user-details">Unit: ${unit} | Phone: ${phone}</div>
                </div>
            </div>
        `);
    }
    
    function formatUserSelection(user) {
        if (!user.id) return user.text;
        
        const $user = $(user.element);
        const name = $user.data('name');
        const unit = $user.data('unit');
        
        return `${name} (${unit})`;
    }
    
    // Camera handling
    let stream;
    let video;
    let canvas;
    
    async function initCamera() {
        try {
            video = document.getElementById('video');
            canvas = document.createElement('canvas');
            canvas.width = 320;
            canvas.height = 240;
            
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
        } catch (err) {
            console.error('Error accessing camera:', err);
            alert('Failed to access camera. Please make sure you have granted camera permissions.');
        }
    }
    
    document.getElementById('capture-btn').addEventListener('click', function() {
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const photo = canvas.toDataURL('image/jpeg');
        document.getElementById('photo_data').value = photo;
        document.getElementById('photo-preview').src = photo;
        
        document.getElementById('video').style.display = 'none';
        document.getElementById('photo-preview').style.display = 'block';
        document.getElementById('capture-btn').style.display = 'none';
        document.getElementById('retake-btn').style.display = 'inline-block';
    });
    
    document.getElementById('retake-btn').addEventListener('click', function() {
        document.getElementById('video').style.display = 'block';
        document.getElementById('photo-preview').style.display = 'none';
        document.getElementById('capture-btn').style.display = 'inline-block';
        document.getElementById('retake-btn').style.display = 'none';
        document.getElementById('photo_data').value = '';
    });
    
    async function handleCheckIn(event) {
        event.preventDefault();
        
        const formData = {
            action: 'check_in',
            visitor_name: document.getElementById('visitor_name').value,
            mobile: document.getElementById('mobile').value,
            host_id: document.getElementById('host_id').value,
            purpose: document.getElementById('purpose').value,
            photo_data: document.getElementById('photo_data').value
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
                alert('Visitor checked in successfully!');
                window.location.href = 'dashboard.php';
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while checking in the visitor');
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
