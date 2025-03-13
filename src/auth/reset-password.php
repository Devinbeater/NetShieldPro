<?php
/**
 * NetShield Pro - Password Reset
 * Current Date: 2025-03-03 17:52:56
 * Current User: Devinbeater
 */

session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

class PasswordReset {
    private $db;
    private $security;
    private $currentDate;
    private $currentUser;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new SecurityFunctions();
        $this->currentDate = '2025-03-03 17:52:56';
        $this->currentUser = 'Devinbeater';
    }
    
    public function generateResetToken($email) {
        $user = $this->getUserByEmail($email);
        if (!$user) {
            return false;
        }
        
        // Delete any existing unused tokens for this user
        $stmt = $this->db->prepare('
            DELETE FROM password_resets 
            WHERE user_id = ? AND used = 0
        ');
        $stmt->execute([$user['id']]);
        
        $token = bin2hex(random_bytes(32));
        $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($this->currentDate)));
        
        $stmt = $this->db->prepare('
            INSERT INTO password_resets (
                user_id, token, expires_at, created_at
            ) VALUES (?, ?, ?, ?)
        ');
        
        if ($stmt->execute([$user['id'], $token, $expiryTime, $this->currentDate])) {
            $this->logPasswordResetRequest($user['id']);
            return $token;
        }
        
        return false;
    }
    
    public function validateResetToken($token) {
        if (empty($token) || strlen($token) !== 64) {
            return false;
        }

        $stmt = $this->db->prepare('
            SELECT pr.user_id, u.email 
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token = ? 
            AND pr.expires_at > ? 
            AND pr.used = 0
        ');
        $stmt->execute([$token, $this->currentDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function resetPassword($token, $newPassword) {
        $resetInfo = $this->validateResetToken($token);
        if (!$resetInfo) {
            return false;
        }
        
        // Validate password strength
        if (!$this->security->isPasswordStrong($newPassword)) {
            return false;
        }
        
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $this->db->beginTransaction();
        try {
            // Update password
            $stmt = $this->db->prepare('
                UPDATE users 
                SET password_hash = ?,
                    password_changed_at = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $passwordHash, 
                $this->currentDate, 
                $this->currentDate,
                $this->currentUser,
                $resetInfo['user_id']
            ]);
            
            // Mark token as used
            $stmt = $this->db->prepare('
                UPDATE password_resets 
                SET used = 1,
                    used_at = ?
                WHERE token = ?
            ');
            $stmt->execute([$this->currentDate, $token]);
            
            // Log password reset
            $this->logPasswordReset($resetInfo['user_id']);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Password reset failed for user ID {$resetInfo['user_id']}: " . $e->getMessage());
            return false;
        }
    }
    
    private function getUserByEmail($email) {
        $stmt = $this->db->prepare('
            SELECT id, email, username 
            FROM users 
            WHERE email = ? 
            AND account_status = ?
        ');
        $stmt->execute([$email, 'active']);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function logPasswordResetRequest($userId) {
        $stmt = $this->db->prepare('
            INSERT INTO security_logs (
                user_id, event_type, ip_address, user_agent,
                created_at, severity
            ) VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userId,
            'password_reset_requested',
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $this->currentDate,
            'medium'
        ]);
    }
    
    private function logPasswordReset($userId) {
        $stmt = $this->db->prepare('
            INSERT INTO security_logs (
                user_id, event_type, ip_address, user_agent,
                created_at, severity
            ) VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userId,
            'password_reset_completed',
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $this->currentDate,
            'high'
        ]);
    }
}

$passwordReset = new PasswordReset();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $response = ['success' => false, 'message' => ''];
        
        if (!isset($_POST['action'])) {
            throw new Exception('Invalid request');
        }
        
        switch ($_POST['action']) {
            case 'request_reset':
                if (empty($_POST['email'])) {
                    throw new Exception('Email address is required');
                }
                
                $token = $passwordReset->generateResetToken($_POST['email']);
                if ($token) {
                    // In production, send email with reset link
                    $response = [
                        'success' => true,
                        'message' => 'Password reset instructions have been sent to your email',
                        'debug_token' => $token // Remove in production
                    ];
                } else {
                    throw new Exception('Email address not found or account is inactive');
                }
                break;
                
            case 'reset_password':
                if (empty($_POST['token']) || empty($_POST['password'])) {
                    throw new Exception('All fields are required');
                }
                
                if ($passwordReset->resetPassword($_POST['token'], $_POST['password'])) {
                    $response = [
                        'success' => true,
                        'message' => 'Password has been reset successfully'
                    ];
                } else {
                    throw new Exception('Invalid or expired reset token');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if there's a reset token in the URL
$resetToken = $_GET['token'] ?? null;
$validToken = $resetToken ? $passwordReset->validateResetToken($resetToken) : false;

// Set proper content security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <img src="/assets/images/logo.svg" alt="NetShield Pro" class="h-12 mx-auto mb-4">
                <h1 class="text-3xl font-bold text-white">Reset Password</h1>
                <p class="text-gray-400 mt-2">
                    <?php echo $resetToken ? 
                        'Enter your new password below' : 
                        'Enter your email to receive reset instructions'; ?>
                </p>
            </div>

            <?php if (!$resetToken): ?>
            <!-- Request Reset Form -->
            <form id="requestResetForm" class="glass-card p-6" novalidate>
                <input type="hidden" name="action" value="request_reset">
                
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2" for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           autocomplete="email"
                           class="w-full bg-gray-700 text-white rounded px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded transition duration-300">
                    Send Reset Instructions
                </button>
            </form>
            <?php else: ?>
            <!-- Reset Password Form -->
            <form id="resetPasswordForm" class="glass-card p-6" novalidate>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($resetToken); ?>">
                
                <?php if ($validToken): ?>
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2" for="password">New Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="new-password"
                           class="w-full bg-gray-700 text-white rounded px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2" for="confirm_password">Confirm Password</label>
                    <input type="password" 
                           id="confirm_password" 
                           required 
                           autocomplete="new-password"
                           class="w-full bg-gray-700 text-white rounded px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="password-strength hidden mb-4">
                    <label class="block text-gray-400 mb-2">Password Strength</label>
                    <div class="h-2 bg-gray-700 rounded overflow-hidden">
                        <div class="password-strength-meter h-full rounded transition-all duration-300"></div>
                    </div>
                    <ul class="password-requirements text-sm text-gray-400 mt-2"></ul>
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded transition duration-300">
                    Reset Password
                </button>
                <?php else: ?>
                <div class="text-red-400 text-center p-4">
                    This reset link has expired or is invalid.
                    <a href="/auth/reset-password.php" class="block mt-2 text-blue-400 hover:text-blue-300">
                        Request a new one
                    </a>
                </div>
                <?php endif; ?>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="/src/auth/login.php" class="text-blue-400 hover:text-blue-300">
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Notification System
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded shadow-lg transition-opacity duration-300 ${
                type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' :
                'bg-blue-600'
            } text-white`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Fade out and remove
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Form Handlers
        document.getElementById('requestResetForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                showNotification(data.message, data.success ? 'success' : 'error');
                
                if (data.success) {
                    this.reset();
                }
            } catch (error) {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });

        document.getElementById('resetPasswordForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = this.querySelector('#password').value;
            const confirmPassword = this.querySelector('#confirm_password').value;
            
            if (password !== confirmPassword) {
                showNotification('Passwords do not match', 'error');
                return;
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                showNotification(data.message, data.success ? 'success' : 'error');
                
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '/auth/login.php';
                    }, 2000);
                }
            } catch (error) {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });

                // Password Strength Checker
                const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', async function() {
                const password = this.value;
                const strengthContainer = document.querySelector('.password-strength');
                const strengthMeter = document.querySelector('.password-strength-meter');
                const requirementsList = document.querySelector('.password-requirements');
                
                if (password.length === 0) {
                    strengthContainer.classList.add('hidden');
                    return;
                }
                
                strengthContainer.classList.remove('hidden');
                
                // Password requirements
                const requirements = [
                    {
                        test: password.length >= 12,
                        message: 'At least 12 characters long'
                    },
                    {
                        test: /[A-Z]/.test(password),
                        message: 'Contains uppercase letter'
                    },
                    {
                        test: /[a-z]/.test(password),
                        message: 'Contains lowercase letter'
                    },
                    {
                        test: /[0-9]/.test(password),
                        message: 'Contains number'
                    },
                    {
                        test: /[^A-Za-z0-9]/.test(password),
                        message: 'Contains special character'
                    },
                    {
                        test: !/(.)\1{2,}/.test(password),
                        message: 'No repeated characters'
                    }
                ];
                
                // Calculate strength score
                const score = requirements.reduce((acc, req) => acc + (req.test ? 1 : 0), 0);
                const percentage = (score / requirements.length) * 100;
                
                // Update strength meter
                strengthMeter.style.width = `${percentage}%`;
                strengthMeter.className = `password-strength-meter h-full rounded transition-all duration-300 ${
                    percentage >= 80 ? 'bg-green-500' :
                    percentage >= 60 ? 'bg-yellow-500' :
                    percentage >= 40 ? 'bg-orange-500' :
                    'bg-red-500'
                }`;
                
                // Update requirements list
                requirementsList.innerHTML = requirements.map(req => `
                    <li class="flex items-center space-x-2">
                        <span class="${req.test ? 'text-green-400' : 'text-red-400'}">
                            ${req.test ? '✓' : '✗'}
                        </span>
                        <span>${req.message}</span>
                    </li>
                `).join('');
                
                // Optional: Check password against compromised database
                try {
                    const response = await fetch('/api/security/check-password', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            password: password
                        })
                    });
                    
                    const data = await response.json();
                    if (data.compromised) {
                        requirementsList.insertAdjacentHTML('beforeend', `
                            <li class="flex items-center space-x-2 text-red-400">
                                <span>✗</span>
                                <span>This password has been exposed in data breaches</span>
                            </li>
                        `);
                    }
                } catch (error) {
                    console.error('Failed to check password security:', error);
                }
            });
        }
    </script>
</body>
</html>