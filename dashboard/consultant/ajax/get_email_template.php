<?php
// Prevent PHP errors from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Include database connection
    require_once '../../includes/db_connect.php';

    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        throw new Exception('Not authenticated');
    }

    // Get template ID from request
    $template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$template_id) {
        throw new Exception('Invalid template ID');
    }

    // Get template details
    $query = "SELECT * FROM email_templates WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();

    if (!$template) {
        throw new Exception('Template not found');
    }

    // Return template data
    echo json_encode([
        'success' => true,
        'template' => [
            'id' => $template['id'],
            'name' => $template['name'],
            'subject' => $template['subject'],
            'content' => $template['content'],
            'template_type' => $template['template_type']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 