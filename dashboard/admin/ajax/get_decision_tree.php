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

try {
    // Get all questions
    $questions_query = "SELECT id, question_text, is_active FROM decision_tree_questions ORDER BY id";
    $questions_result = $conn->query($questions_query);
    
    $nodes = [];
    $edges = [];
    $node_colors = [
        'active' => '#1cc88a',    // Green for active questions
        'inactive' => '#858796',  // Gray for inactive questions
        'endpoint' => '#e74a3b'   // Red for endpoints
    ];
    
    // Process questions into nodes
    while ($question = $questions_result->fetch_assoc()) {
        $nodes[] = [
            'id' => 'q' . $question['id'],
            'label' => $question['question_text'],
            'title' => $question['question_text'],
            'color' => $question['is_active'] ? $node_colors['active'] : $node_colors['inactive'],
            'shape' => 'box',
            'margin' => 10,
            'font' => [
                'size' => 14,
                'face' => 'Arial'
            ]
        ];
    }
    
    // Get all options
    $options_query = "SELECT o.*, q.question_text as next_question_text 
                     FROM decision_tree_options o
                     LEFT JOIN decision_tree_questions q ON o.next_question_id = q.id
                     ORDER BY o.question_id, o.id";
    $options_result = $conn->query($options_query);
    
    // Process options into edges
    while ($option = $options_result->fetch_assoc()) {
        if ($option['is_endpoint']) {
            // Create endpoint node
            $endpoint_id = 'e' . $option['id'];
            $nodes[] = [
                'id' => $endpoint_id,
                'label' => $option['endpoint_result'],
                'title' => $option['endpoint_result'],
                'color' => $node_colors['endpoint'],
                'shape' => 'diamond',
                'margin' => 10,
                'font' => [
                    'size' => 12,
                    'face' => 'Arial'
                ]
            ];
            
            // Create edge to endpoint
            $edges[] = [
                'from' => 'q' . $option['question_id'],
                'to' => $endpoint_id,
                'label' => $option['option_text'],
                'arrows' => 'to',
                'smooth' => [
                    'type' => 'cubicBezier',
                    'forceDirection' => 'vertical'
                ]
            ];
        } else {
            // Create edge to next question
            $edges[] = [
                'from' => 'q' . $option['question_id'],
                'to' => 'q' . $option['next_question_id'],
                'label' => $option['option_text'],
                'arrows' => 'to',
                'smooth' => [
                    'type' => 'cubicBezier',
                    'forceDirection' => 'vertical'
                ]
            ];
        }
    }
    
    // Return the tree data
    echo json_encode([
        'success' => true,
        'nodes' => $nodes,
        'edges' => $edges
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
