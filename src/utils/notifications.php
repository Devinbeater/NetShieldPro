<?php
class NotificationManager {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (PDOException $e) {
            error_log(sprintf(
                "[%s] NotificationManager initialization failed: %s",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            ));
        }
    }

    public function getRecentNotifications($userId, $limit = 5) {
        try {
            // Check if notifications table exists
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'notifications'
            ");
            $stmt->execute();
            $tableExists = $stmt->fetchColumn() > 0;

            if (!$tableExists) {
                return [];
            }

            $stmt = $this->db->prepare('
                SELECT 
                    id,
                    title,
                    message,
                    link,
                    read_at IS NOT NULL as `read`,
                    created_at
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ');
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log(sprintf(
                "[%s] Error getting notifications: %s",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            ));
            return [];
        }
    }

    public function getUnreadCount($userId) {
        try {
            // Check if notifications table exists
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'notifications'
            ");
            $stmt->execute();
            $tableExists = $stmt->fetchColumn() > 0;

            if (!$tableExists) {
                return 0;
            }

            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = ? AND read_at IS NULL
            ');
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log(sprintf(
                "[%s] Error getting unread count: %s",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            ));
            return 0;
        }
    }
}