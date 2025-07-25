<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = connectDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'get_faqs':
        if ($method === 'GET') {
            getFAQs($conn);
        }
        break;
        
    case 'get_guides':
        if ($method === 'GET') {
            getUserGuides($conn);
        }
        break;
        
    case 'create_ticket':
        if ($method === 'POST') {
            createSupportTicket($conn);
        }
        break;
        
    case 'get_tickets':
        if ($method === 'GET') {
            getSupportTickets($conn);
        }
        break;
        
    case 'update_ticket':
        if ($method === 'POST') {
            updateSupportTicket($conn);
        }
        break;
        
    case 'add_ticket_message':
        if ($method === 'POST') {
            addTicketMessage($conn);
        }
        break;
        
    case 'get_ticket_messages':
        if ($method === 'GET') {
            getTicketMessages($conn);
        }
        break;
        
    case 'search':
        if ($method === 'GET') {
            searchHelp($conn);
        }
        break;
        
    case 'track_view':
        if ($method === 'POST') {
            trackView($conn);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getFAQs($conn) {
    $userRole = $_SESSION['role'];
    
    $stmt = $conn->prepare("
        SELECT 
            c.id as category_id,
            c.name as category_name,
            c.description as category_description,
            f.id,
            f.question,
            f.answer,
            f.views_count
        FROM faq_categories c
        LEFT JOIN faq_items f ON c.id = f.category_id 
        WHERE c.is_active = 1 
        AND (f.is_active = 1 AND (f.role = 'all' OR f.role = ?))
        ORDER BY c.sort_order, f.sort_order
    ");
    
    $stmt->bind_param('s', $userRole);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $faqs = [];
    while ($row = $result->fetch_assoc()) {
        $categoryId = $row['category_id'];
        if (!isset($faqs[$categoryId])) {
            $faqs[$categoryId] = [
                'category_id' => $categoryId,
                'category_name' => $row['category_name'],
                'category_description' => $row['category_description'],
                'items' => []
            ];
        }
        
        if ($row['id']) {
            $faqs[$categoryId]['items'][] = [
                'id' => $row['id'],
                'question' => $row['question'],
                'answer' => $row['answer'],
                'views_count' => $row['views_count']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'data' => array_values($faqs)]);
}

function getUserGuides($conn) {
    $userRole = $_SESSION['role'];
    
    $stmt = $conn->prepare("
        SELECT id, title, content, category, views_count, updated_at
        FROM user_guides 
        WHERE is_active = 1 
        AND (role = 'all' OR role = ?)
        ORDER BY sort_order, title
    ");
    
    $stmt->bind_param('s', $userRole);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $guides = [];
    while ($row = $result->fetch_assoc()) {
        $guides[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $guides]);
}

function createSupportTicket($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $subject = $data['subject'] ?? '';
    $description = $data['description'] ?? '';
    $category = $data['category'] ?? 'general';
    $priority = $data['priority'] ?? 'medium';
    $userId = $_SESSION['user_id'];
    
    if (empty($subject) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Subject and description are required']);
        return;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO support_tickets (user_id, subject, description, category, priority)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param('issss', $userId, $subject, $description, $category, $priority);
    
    if ($stmt->execute()) {
        $ticketId = $conn->insert_id;
        
        // Add initial message
        $stmt2 = $conn->prepare("
            INSERT INTO support_ticket_messages (ticket_id, user_id, message)
            VALUES (?, ?, ?)
        ");
        $stmt2->bind_param('iis', $ticketId, $userId, $description);
        $stmt2->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Support ticket created successfully',
            'ticket_id' => $ticketId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create support ticket']);
    }
}

function getSupportTickets($conn) {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    if ($userRole === 'admin') {
        // Admins can see all tickets
        $stmt = $conn->prepare("
            SELECT 
                t.*,
                u.full_name as user_name,
                a.full_name as assigned_name
            FROM support_tickets t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN users a ON t.assigned_to = a.id
            ORDER BY t.created_at DESC
        ");
        $stmt->execute();
    } else {
        // Users can only see their own tickets
        $stmt = $conn->prepare("
            SELECT 
                t.*,
                a.full_name as assigned_name
            FROM support_tickets t
            LEFT JOIN users a ON t.assigned_to = a.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }
    
    $result = $stmt->get_result();
    $tickets = [];
    
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $tickets]);
}

function updateSupportTicket($conn) {
    if (!hasRole('admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ticketId = $data['ticket_id'] ?? 0;
    $status = $data['status'] ?? '';
    $assignedTo = $data['assigned_to'] ?? null;
    $resolution = $data['resolution'] ?? '';
    
    $stmt = $conn->prepare("
        UPDATE support_tickets 
        SET status = ?, assigned_to = ?, resolution = ?, 
            closed_at = CASE WHEN ? IN ('resolved', 'closed') THEN NOW() ELSE NULL END
        WHERE id = ?
    ");
    
    $stmt->bind_param('sissi', $status, $assignedTo, $resolution, $status, $ticketId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update ticket']);
    }
}

function addTicketMessage($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ticketId = $data['ticket_id'] ?? 0;
    $message = $data['message'] ?? '';
    $isInternal = $data['is_internal'] ?? false;
    $userId = $_SESSION['user_id'];
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        return;
    }
    
    // Check if user has access to this ticket
    $stmt = $conn->prepare("
        SELECT user_id FROM support_tickets WHERE id = ?
    ");
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    
    if (!$ticket || ($ticket['user_id'] != $userId && !hasRole('admin'))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO support_ticket_messages (ticket_id, user_id, message, is_internal)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param('iisi', $ticketId, $userId, $message, $isInternal);
    
    if ($stmt->execute()) {
        // Update ticket status if it was closed
        $conn->query("UPDATE support_tickets SET status = 'open' WHERE id = $ticketId AND status = 'closed'");
        
        echo json_encode(['success' => true, 'message' => 'Message added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add message']);
    }
}

function getTicketMessages($conn) {
    $ticketId = $_GET['ticket_id'] ?? 0;
    $userId = $_SESSION['user_id'];
    
    // Check if user has access to this ticket
    $stmt = $conn->prepare("
        SELECT user_id FROM support_tickets WHERE id = ?
    ");
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    
    if (!$ticket || ($ticket['user_id'] != $userId && !hasRole('admin'))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            u.full_name as user_name
        FROM support_ticket_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ?
        AND (m.is_internal = 0 OR ? = 'admin')
        ORDER BY m.created_at ASC
    ");
    
    $userRole = $_SESSION['role'];
    $stmt->bind_param('is', $ticketId, $userRole);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $messages]);
}

function searchHelp($conn) {
    $query = $_GET['q'] ?? '';
    $userRole = $_SESSION['role'];
    
    if (empty($query)) {
        echo json_encode(['success' => true, 'data' => ['faqs' => [], 'guides' => []]]);
        return;
    }
    
    // Search FAQs
    $stmt = $conn->prepare("
        SELECT 
            f.id,
            f.question,
            f.answer,
            c.name as category_name,
            'faq' as type
        FROM faq_items f
        JOIN faq_categories c ON f.category_id = c.id
        WHERE f.is_active = 1 
        AND (f.role = 'all' OR f.role = ?)
        AND (f.question LIKE ? OR f.answer LIKE ?)
        ORDER BY f.views_count DESC
        LIMIT 10
    ");
    
    $searchTerm = "%$query%";
    $stmt->bind_param('sss', $userRole, $searchTerm, $searchTerm);
    $stmt->execute();
    $faqResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Search User Guides
    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            content,
            category,
            'guide' as type
        FROM user_guides
        WHERE is_active = 1 
        AND (role = 'all' OR role = ?)
        AND (title LIKE ? OR content LIKE ?)
        ORDER BY views_count DESC
        LIMIT 10
    ");
    
    $stmt->bind_param('sss', $userRole, $searchTerm, $searchTerm);
    $stmt->execute();
    $guideResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'faqs' => $faqResults,
            'guides' => $guideResults
        ]
    ]);
}

function trackView($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $contentType = $data['content_type'] ?? '';
    $contentId = $data['content_id'] ?? 0;
    $userId = $_SESSION['user_id'];
    
    if (!in_array($contentType, ['faq', 'guide']) || !$contentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }
    
    // Track view
    $stmt = $conn->prepare("
        INSERT INTO help_views (user_id, content_type, content_id)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('isi', $userId, $contentType, $contentId);
    $stmt->execute();
    
    // Update view count
    if ($contentType === 'faq') {
        $stmt = $conn->prepare("UPDATE faq_items SET views_count = views_count + 1 WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE user_guides SET views_count = views_count + 1 WHERE id = ?");
    }
    $stmt->bind_param('i', $contentId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'View tracked']);
}

$conn->close();
?>