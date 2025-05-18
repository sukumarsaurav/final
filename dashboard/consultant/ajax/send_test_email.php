<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../../logs/php_errors.log');

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log POST data for debugging
error_log("POST data: " . print_r($_POST, true));

// Configure PHPMailer
require_once '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

try {
    // Check if required files exist
    $required_files = [
        '../../../config/db_connect.php' => 'Database connection file',
        '../../../config/email_config.php' => 'Email configuration file',
        '../../../vendor/autoload.php' => 'Composer autoload file'
    ];

    foreach ($required_files as $file => $description) {
        if (!file_exists($file)) {
            throw new Exception("Required file missing: $description ($file)");
        }
    }

    // Include database connection and email config
    require_once '../../../config/db_connect.php';
    require_once '../../../config/email_config.php';

    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        throw new Exception('Not authenticated');
    }

    // Check if organization_id is set - provide a default if not
    $organization_id = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : 1;

    // Get POST data
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';

    // Log processed data for debugging
    error_log("Processed data: template_id=$template_id, email=$email, first_name=$first_name, last_name=$last_name");

    // Validate inputs
    if (!$template_id || !$email) {
        throw new Exception('Missing required fields');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
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

    // Replace variables in content
    $content = $template['content'];
    $subject = $template['subject'];

    $replacements = [
        '{first_name}' => $first_name,
        '{last_name}' => $last_name,
        '{email}' => $email,
        '{current_date}' => date('Y-m-d'),
        '{company_name}' => 'Visafy'
    ];

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);

    $mail = new PHPMailer(true);

    // Change debug level to off for production
    $mail->SMTPDebug = 0; // Changed from SMTP::DEBUG_SERVER to 0
    $mail->Debugoutput = function($str, $level) {
        error_log("PHPMailer Debug: $str");
    };

    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;

    // Set timeout
    $mail->Timeout = 30;

    // Recipients
    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    $mail->addAddress($email);
    $mail->addReplyTo(EMAIL_REPLY_TO);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $content;
    $mail->AltBody = strip_tags($content); // Plain text version

    // Send email
    $mail->send();

    // Log the email in queue
    $query = "INSERT INTO email_queue (recipient_email, subject, content, status, scheduled_time, created_by, organization_id) 
              VALUES (?, ?, ?, 'sent', NOW(), ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('sssii', $email, $subject, $content, $_SESSION['id'], $organization_id);
    $stmt->execute();

    // Return a more detailed success response
    echo json_encode([
        'success' => true, 
        'message' => 'Test email sent successfully to ' . $email,
        'details' => [
            'template' => $template['name'],
            'recipient' => $email,
            'subject' => $subject
        ]
    ]);

} catch (PHPMailerException $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
    
    // Log the failed email
    if (isset($conn) && isset($email) && isset($subject) && isset($content)) {
        $query = "INSERT INTO email_queue (recipient_email, subject, content, status, error_message, scheduled_time, created_by, organization_id) 
                  VALUES (?, ?, ?, 'failed', ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $error_message = $e->getMessage();
            $organization_id = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : 1;
            $stmt->bind_param('ssssii', $email, $subject, $content, $error_message, $_SESSION['id'], $organization_id);
            $stmt->execute();
        }
    }
    
    // Return a more detailed error response
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to send email: ' . $e->getMessage(),
        'details' => [
            'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined',
            'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 'Not defined',
            'from_email' => defined('EMAIL_FROM') ? EMAIL_FROM : 'Not defined',
            'recipient' => isset($email) ? $email : 'Not set'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'details' => [
            'error_type' => 'General Exception',
            'error_message' => $e->getMessage()
        ]
    ]);
}