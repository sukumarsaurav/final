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

// Get option ID from request
$option_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$option_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid option ID']);
    exit;
}

try {
    // Get option details
    $query = "SELECT o.*, q.question_text as next_question_text
              FROM decision_tree_options o
              LEFT JOIN decision_tree_questions q ON o.next_question_id = q.id
              WHERE o.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $option_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Option not found']);
        exit;
    }
    
    $option = $result->fetch_assoc();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'id' => $option['id'],
        'question_id' => $option['question_id'],
        'option_text' => $option['option_text'],
        'next_question_id' => $option['next_question_id'],
        'next_question_text' => $option['next_question_text'],
        'is_endpoint' => $option['is_endpoint'],
        'endpoint_result' => $option['endpoint_result'],
        'endpoint_eligible' => $option['endpoint_eligible'],
        'created_at' => $option['created_at'],
        'updated_at' => $option['updated_at']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
