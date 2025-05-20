<?php
require_once '../../../config/db_connect.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get question ID from request
$question_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$question_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
    exit;
}

try {
    // Get question details
    $query = "SELECT q.*, c.name as category_name, 
              CONCAT(u.first_name, ' ', u.last_name) as created_by_name
              FROM decision_tree_questions q
              LEFT JOIN decision_tree_categories c ON q.category_id = c.id
              LEFT JOIN users u ON q.created_by = u.id
              WHERE q.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Question not found']);
        exit;
    }
    
    $question = $result->fetch_assoc();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'id' => $question['id'],
        'question_text' => $question['question_text'],
        'description' => $question['description'],
        'category_id' => $question['category_id'],
        'category_name' => $question['category_name'],
        'is_active' => $question['is_active'],
        'created_by' => $question['created_by'],
        'created_by_name' => $question['created_by_name'],
        'created_at' => $question['created_at'],
        'updated_at' => $question['updated_at']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
