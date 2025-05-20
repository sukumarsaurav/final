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
$option_id = filter_input(INPUT_POST, 'option_id', FILTER_VALIDATE_INT);
$question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
$option_text = filter_input(INPUT_POST, 'option_text', FILTER_SANITIZE_STRING);
$is_endpoint = isset($_POST['is_endpoint']) ? 1 : 0;
$next_question_id = filter_input(INPUT_POST, 'next_question_id', FILTER_VALIDATE_INT);
$endpoint_result = filter_input(INPUT_POST, 'endpoint_result', FILTER_SANITIZE_STRING);
$endpoint_eligible = isset($_POST['endpoint_eligible']) ? 1 : 0;

// Validate required fields
if (empty($option_text) || empty($question_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Option text and question ID are required']);
    exit;
}

// Validate endpoint data
if ($is_endpoint && empty($endpoint_result)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Endpoint result is required for endpoint options']);
    exit;
}

if (!$is_endpoint && empty($next_question_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Next question is required for non-endpoint options']);
    exit;
}

try {
    if ($option_id) {
        // Update existing option
        $query = "UPDATE decision_tree_options 
                 SET option_text = ?, 
                     is_endpoint = ?, 
                     next_question_id = ?, 
                     endpoint_result = ?, 
                     endpoint_eligible = ?,
                     updated_at = NOW()
                 WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('siisii', 
            $option_text, 
            $is_endpoint, 
            $next_question_id, 
            $endpoint_result, 
            $endpoint_eligible, 
            $option_id
        );
    } else {
        // Insert new option
        $query = "INSERT INTO decision_tree_options 
                 (question_id, option_text, is_endpoint, next_question_id, endpoint_result, endpoint_eligible, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isissi', 
            $question_id, 
            $option_text, 
            $is_endpoint, 
            $next_question_id, 
            $endpoint_result, 
            $endpoint_eligible
        );
    }
    
    if ($stmt->execute()) {
        $new_option_id = $option_id ?: $stmt->insert_id;
        
        // Create activity log
        $activity_query = "INSERT INTO activity_logs 
                          (user_id, activity_type, entity_type, entity_id, description) 
                          VALUES (?, ?, 'option', ?, ?)";
        
        $activity_type = $option_id ? 'update' : 'create';
        $activity_desc = $option_id ? 'Updated option' : 'Created new option';
        
        $activity_stmt = $conn->prepare($activity_query);
        $activity_stmt->bind_param('isii', $_SESSION['user_id'], $activity_type, $new_option_id, $activity_desc);
        $activity_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => $option_id ? 'Option updated successfully' : 'Option created successfully',
            'option_id' => $new_option_id
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
