<?php
/**
 * NetShield Pro - Two Factor Authentication
 * Current Date: 2025-03-03 17:46:32
 * Current User: Devinbeater
 */

session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

class TwoFactorAuth {
    private $db;
    private $security;
    private $currentUser;
    private $currentDate;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new SecurityFunctions();
        $this->currentUser = 'Devinbeater';
        $this->currentDate = '2025-03-03 17:46:32';
    }

    public function enableTwoFactor($userId) {
        try {
            // Generate secret key
            $secret = $this->security->generateTOTPSecret();
            
            // Generate QR code URI
            $qrCodeUri = $this->generateQRCodeUri($userId, $secret);
            
            // Store secret in database
            $stmt = $this->db->prepare('
                UPDATE users 
                SET two_factor_secret = ?,
                    two_factor_enabled = 1,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ');
            
            $success = $stmt->execute([
                $secret,
                $this->currentDate,
                $this->currentUser,
                $userId
            ]);

            if ($success) {
                $this->logActivity($userId, '2FA Enabled', 'Two-factor authentication enabled successfully');
                return [
                    'status' => 'success',
                    'secret' => $secret,
                    'qr_code' => $qrCodeUri
                ];
            }

            throw new Exception('Failed to enable two-factor authentication');

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function disableTwoFactor($userId, $code) {
        try {
            // Get user's current 2FA secret
            $stmt = $this->db->prepare('
                SELECT two_factor_secret 
                FROM users 
                WHERE id = ? AND two_factor_enabled = 1
            ');
            $stmt->execute([$userId]);
            $secret = $stmt->fetchColumn();

            if (!$secret) {
                throw new Exception('Two-factor authentication is not enabled');
            }

            // Verify the provided code
            if (!$this->security->verifyTOTPCode($code, $secret)) {
                throw new Exception('Invalid verification code');
            }

            // Disable 2FA
            $stmt = $this->db->prepare('
                UPDATE users 
                SET two_factor_secret = NULL,
                    two_factor_enabled = 0,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ');

            $success = $stmt->execute([
                $this->currentDate,
                $this->currentUser,
                $userId
            ]);

            if ($success) {
                $this->logActivity($userId, '2FA Disabled', 'Two-factor authentication disabled');
                return [
                    'status' => 'success',
                    'message' => 'Two-factor authentication disabled successfully'
                ];
            }

            throw new Exception('Failed to disable two-factor authentication');

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function verifyCode($userId, $code) {
        try {
            // Get user's 2FA secret
            $stmt = $this->db->prepare('
                SELECT two_factor_secret 
                FROM users 
                WHERE id = ? AND two_factor_enabled = 1
            ');
            $stmt->execute([$userId]);
            $secret = $stmt->fetchColumn();

            if (!$secret) {
                throw new Exception('Two-factor authentication is not enabled');
            }

            // Verify the code
            if (!$this->security->verifyTOTPCode($code, $secret)) {
                $this->logFailedVerification($userId);
                throw new Exception('Invalid verification code');
            }

            // Log successful verification
            $this->logActivity($userId, '2FA Verification', 'Two-factor authentication code verified successfully');

            return [
                'status' => 'success',
                'message' => 'Code verified successfully'
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function generateBackupCodes($userId) {
        try {
            // Generate backup codes
            $backupCodes = [];
            for ($i = 0; $i < 10; $i++) {
                $backupCodes[] = bin2hex(random_bytes(4));
            }

            // Hash the backup codes
            $hashedCodes = array_map(function($code) {
                return password_hash($code, PASSWORD_BCRYPT);
            }, $backupCodes);

            // Store hashed backup codes
            $stmt = $this->db->prepare('
                UPDATE users 
                SET backup_codes = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ');

            $success = $stmt->execute([
                json_encode($hashedCodes),
                $this->currentDate,
                $this->currentUser,
                $userId
            ]);

            if ($success) {
                $this->logActivity($userId, '2FA Backup', 'Generated new backup codes');
                return [
                    'status' => 'success',
                    'codes' => $backupCodes
                ];
            }

            throw new Exception('Failed to generate backup codes');

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function verifyBackupCode($userId, $code) {
        try {
            // Get user's backup codes
            $stmt = $this->db->prepare('
                SELECT backup_codes 
                FROM users 
                WHERE id = ?
            ');
            $stmt->execute([$userId]);
            $backupCodes = json_decode($stmt->fetchColumn(), true);

            if (!$backupCodes) {
                throw new Exception('No backup codes available');
            }

            // Check if the provided code matches any backup code
            foreach ($backupCodes as $index => $hashedCode) {
                if (password_verify($code, $hashedCode)) {
                    // Remove used backup code
                    unset($backupCodes[$index]);
                    
                    // Update backup codes in database
                    $stmt = $this->db->prepare('
                        UPDATE users 
                        SET backup_codes = ?,
                            updated_at = ?,
                            updated_by = ?
                        WHERE id = ?
                    ');
                    
                    $stmt->execute([
                        json_encode(array_values($backupCodes)),
                        $this->currentDate,
                        $this->currentUser,
                        $userId
                    ]);

                    $this->logActivity($userId, '2FA Backup Used', 'Backup code used for authentication');
                    
                    return [
                        'status' => 'success',
                        'message' => 'Backup code verified successfully'
                    ];
                }
            }

            throw new Exception('Invalid backup code');

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function generateQRCodeUri($userId, $secret) {
        $stmt = $this->db->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn();

        $appName = 'NetShield Pro';
        $issuer = urlencode('NetShield Security');
        $account = urlencode($email);
        
        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}";
    }

    private function logActivity($userId, $action, $description) {
        $stmt = $this->db->prepare('
            INSERT INTO activity_logs (
                user_id, action_type, description, ip_address, 
                user_agent, created_at, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $this->currentDate,
            $this->currentUser
        ]);
    }

    private function logFailedVerification($userId) {
        $stmt = $this->db->prepare('
            INSERT INTO security_logs (
                user_id, event_type, details, ip_address,
                created_at, severity
            ) VALUES (?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $userId,
            'Failed 2FA Verification',
            'Failed attempt to verify two-factor authentication code',
            $_SERVER['REMOTE_ADDR'],
            $this->currentDate,
            'medium'
        ]);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $twoFactorAuth = new TwoFactorAuth();
    $response = ['status' => 'error', 'message' => 'Invalid request'];

    if (isset($_POST['action']) && isset($_SESSION['user'])) {
        $userId = $_SESSION['user']['id'];

        switch ($_POST['action']) {
            case 'enable':
                $response = $twoFactorAuth->enableTwoFactor($userId);
                break;

            case 'disable':
                if (isset($_POST['code'])) {
                    $response = $twoFactorAuth->disableTwoFactor($userId, $_POST['code']);
                }
                break;

            case 'verify':
                if (isset($_POST['code'])) {
                    $response = $twoFactorAuth->verifyCode($userId, $_POST['code']);
                }
                break;

            case 'generate_backup':
                $response = $twoFactorAuth->generateBackupCodes($userId);
                break;

            case 'verify_backup':
                if (isset($_POST['code'])) {
                    $response = $twoFactorAuth->verifyBackupCode($userId, $_POST['code']);
                }
                break;
        }
    }

    echo json_encode($response);
    exit;
}
?>