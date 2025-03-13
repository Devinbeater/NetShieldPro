<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../utils/db-connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }

    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare('
        INSERT INTO user_settings (user_id, category, settings)
        VALUES (?, "security", ?)
        ON DUPLICATE KEY UPDATE 
        settings = VALUES(settings),
        updated_at = CURRENT_TIMESTAMP
    ');

    $stmt->execute([
        $_SESSION['user']['id'],
        json_encode($input)
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log(sprintf(
        "[%s] Settings update error: %s",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    ));
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save settings']);
}