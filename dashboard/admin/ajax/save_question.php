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

// Get and validate input data
$question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
$question_text = filter_input(INPUT_POST, 'question_text', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validate required fields
if (empty($question_text)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Question text is required']);
    exit;
}

try {
    if ($question_id) {
        // Update existing question
        $query = "UPDATE decision_tree_questions 
                 SET question_text = ?, 
                     description = ?, 
                     category_id = ?, 
                     is_active = ?,
                     updated_at = NOW()
                 WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssiii', $question_text, $description, $category_id, $is_active, $question_id);
    } else {
        // Insert new question
        $query = "INSERT INTO decision_tree_questions 
                 (question_text, description, category_id, is_active, created_by, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssiii', $question_text, $description, $category_id, $is_active, $_SESSION['user_id']);
    }
    
    if ($stmt->execute()) {
        $new_question_id = $question_id ?: $stmt->insert_id;
        
        // Create activity log
        $activity_query = "INSERT INTO activity_logs 
                          (user_id, activity_type, entity_type, entity_id, description) 
                          VALUES (?, ?, 'question', ?, ?)";
        
        $activity_type = $question_id ? 'update' : 'create';
        $activity_desc = $question_id ? 'Updated question' : 'Created new question';
        
        $activity_stmt = $conn->prepare($activity_query);
        $activity_stmt->bind_param('isii', $_SESSION['user_id'], $activity_type, $new_question_id, $activity_desc);
        $activity_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => $question_id ? 'Question updated successfully' : 'Question created successfully',
            'question_id' => $new_question_id
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}