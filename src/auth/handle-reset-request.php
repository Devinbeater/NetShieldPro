<?php
session_start();

require_once __DIR__ . '/../utils/security-functions.php';
require_once __DIR__ . '/../utils/db-connect.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'trace_id' => bin2hex(random_bytes(16))
];

try {
    // Get current context
    $currentDateTime = '2025-03-13 12:44:24';
    $currentUser = 'Devinbeater';
    
    // Log the failed attempt
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('
        INSERT INTO security_logs (
            event_type,
            event_status,
            ip_address,
            user_agent,
            created_at,
            created_by,
            severity,
            additional_info
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        'password_reset_request',
        'failed',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        $currentDateTime,
        $currentUser,
        'medium',
        json_encode([
            'error' => 'Email address not found or account is inactive',
            'trace_id' => $response['trace_id']
        ])
    ]);

    // Send response
    $response['message'] = 'If the email address exists in our system, you will receive password reset instructions shortly.';
    
    // Note: We still return a 200 status code to prevent email enumeration
    http_response_code(200);
    
} catch (Exception $e) {
    // Log the error but don't expose details
    error_log(sprintf(
        '[%s] Password reset request failed - Trace ID: %s - Error: %s',
        $currentDateTime,
        $response['trace_id'],
        $e->getMessage()
    ));
    
    $response['message'] = 'An unexpected error occurred. Please try again later.';
    http_response_code(500);
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;