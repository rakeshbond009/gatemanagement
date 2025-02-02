<?php
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$conn = connectDB();

// Handle GET request - fetch notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            AND is_read = 0
            ORDER BY created_at DESC
        ");
        
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        echo json_encode(['success' => true, 'notifications' => $notifications]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
// Handle POST request - mark notifications as read
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['notification_id'])) {
        try {
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? 
                AND user_id = ?
            ");
            
            $stmt->bind_param('ii', $data['notification_id'], $_SESSION['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update notification');
            }
            
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    }
}
else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
