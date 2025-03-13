<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../utils/db-connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['limit'])) {
        $stmt = $db->prepare('
            UPDATE parental_controls 
            SET screen_time_limit = ? 
            WHERE user_id = ?
        ');
        $stmt->execute([$input['limit'], $_SESSION['user']['id']]);
    } elseif (isset($input['schedule'])) {
        $stmt = $db->prepare('
            UPDATE parental_controls 
            SET schedule = ? 
            WHERE user_id = ?
        ');
        $stmt->execute([json_encode($input['schedule']), $_SESSION['user']['id']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Screen time update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update settings']);
}