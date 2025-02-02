<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$conn = connectDB();
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($data['action']) || !isset($data['visitor_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $visitor_id = $data['visitor_id'];
    $action = $data['action'];

    // Verify visitor exists and get their current status
    $stmt = $conn->prepare("SELECT status, unit_id FROM visitors WHERE id = ?");
    $stmt->bind_param('i', $visitor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Visitor not found']);
        exit;
    }
    
    $visitor = $result->fetch_assoc();
    
    // For residents, verify they own the unit
    if (hasRole('resident')) {
        $stmt = $conn->prepare("
            SELECT 1 FROM user_units 
            WHERE user_id = ? AND unit_id = ?
        ");
        $stmt->bind_param('ii', $_SESSION['user_id'], $visitor['unit_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Not authorized to manage this visitor']);
            exit;
        }
    }
    
    switch ($action) {
        case 'approve':
            if (!hasRole('resident')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only residents can approve visitors']);
                exit;
            }
            
            if ($visitor['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Visitor is not in pending state']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE visitors SET status = 'approved' WHERE id = ?");
            break;
            
        case 'reject':
            if (!hasRole('resident')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only residents can reject visitors']);
                exit;
            }
            
            if ($visitor['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Visitor is not in pending state']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE visitors SET status = 'rejected' WHERE id = ?");
            break;
            
        case 'checkout':
            if (!hasRole('security')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only security can checkout visitors']);
                exit;
            }
            
            if ($visitor['status'] !== 'approved') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Only approved visitors can be checked out']);
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE visitors 
                SET status = 'checked_out', 
                    exit_time = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    $stmt->bind_param('i', $visitor_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Visitor ' . $action . 'ed successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing visitor ID']);
        exit;
    }

    $visitor_id = $data['id'];
    
    // Verify visitor exists and get their current status
    $stmt = $conn->prepare("SELECT status, unit_id FROM visitors WHERE id = ?");
    $stmt->bind_param('i', $visitor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Visitor not found']);
        exit;
    }
    
    $visitor = $result->fetch_assoc();
    
    if (isset($data['action']) && $data['action'] === 'checkout') {
        if (!hasRole('security')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only security can checkout visitors']);
            exit;
        }
        
        if ($visitor['status'] !== 'approved') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Only approved visitors can be checked out']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE visitors SET status = 'checked_out', exit_time = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param('i', $visitor_id);
    } elseif (isset($data['action']) && ($data['action'] === 're_register' || $data['action'] === 'mark_handled')) {
        if (!hasRole('security')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only security can handle rejected visitors']);
            exit;
        }
        
        if ($visitor['status'] !== 'rejected') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Only rejected visitors can be handled']);
            exit;
        }
        
        $new_status = $data['action'] === 're_register' ? 'pending' : 'handled';
        $stmt = $conn->prepare("UPDATE visitors SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param('si', $new_status, $visitor_id);
    } elseif (isset($data['status'])) {
        if (!hasRole('security')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only security can update visitor status']);
            exit;
        }
        
        $allowed_statuses = ['approved', 'rejected'];
        if (!in_array($data['status'], $allowed_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE visitors SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $data['status'], $visitor_id);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
