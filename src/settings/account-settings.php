<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$currentDate = '2025-03-03 16:39:41';
$currentUser = 'Devinbeater';

class AccountSettings {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new SecurityFunctions();
    }
    
    public function getUserProfile($userId) {
        $stmt = $this->db->prepare('
            SELECT id, username, email, created_at, last_login,
                   notification_preferences, theme_preference
            FROM users 
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function updateProfile($userId, $data) {
        try {
            $stmt = $this->db->prepare('
                UPDATE users 
                SET username = ?,
                    email = ?,
                    notification_preferences = ?,
                    theme_preference = ?
                WHERE id = ?
            ');
            
            return $stmt->execute([
                $data['username'],
                $data['email'],
                json_encode($data['notifications']),
                $data['theme'],
                $userId
            ]);
        } catch (PDOException $e) {
            error_log("Profile update failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $currentHash = $stmt->fetchColumn();
            
            if (!$this->security->verifyPassword($currentPassword, $currentHash)) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Check password strength
            $strengthCheck = $this->security->checkPasswordStrength($newPassword);
            if ($strengthCheck['score'] < 60) {
                return ['success' => false, 'message' => 'New password is too weak'];
            }
            
            // Update password
            $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([
                $this->security->hashPassword($newPassword),
                $userId
            ]);
            
            return ['success' => true, 'message' => 'Password updated successfully'];
        } catch (Exception $e) {
            error_log("Password update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update password'];
        }
    }
}

$accountSettings = new AccountSettings();
$userProfile = $accountSettings->getUserProfile($_SESSION['user']['id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $success = $accountSettings->updateProfile($_SESSION['user']['id'], [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'notifications' => [
                        'email_alerts' => isset($_POST['email_alerts']),
                        'security_updates' => isset($_POST['security_updates']),
                        'new_features' => isset($_POST['new_features'])
                    ],
                    'theme' => $_POST['theme']
                ]);
                
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Profile updated successfully' : 'Failed to update profile'
                ];
                break;
                
            case 'update_password':
                $response = $accountSettings->updatePassword(
                    $_SESSION['user']['id'],
                    $_POST['current_password'],
                    $_POST['new_password']
                );
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-gray-900">
    <?php include '../common/header.php'; ?>

    <div class="flex min-h-screen">
        <?php include '../dashboard/sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="container mx-auto">
                <div class="glass-card p-6">
                    <h1 class="text-2xl font-bold text-white mb-6">Account Settings</h1>

                    <!-- Profile Settings -->
                    <div class="bg-gray-800 rounded-lg p-6 mb-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Profile Settings</h2>
                        <form id="profileForm" class="space-y-4">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Username</label>
                                <input type="text" 
                                       name="username" 
                                       value="<?php echo htmlspecialchars($userProfile['username']); ?>"
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Email</label>
                                <input type="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($userProfile['email']); ?>"
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Theme</label>
                                <select name="theme" class="w-full bg-gray-700 text-white rounded px-4 py-2">
                                    <option value="dark" <?php echo $userProfile['theme_preference'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                    <option value="light" <?php echo $userProfile['theme_preference'] === 'light' ? 'selected' : ''; ?>>Light</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Notifications</label>
                                <?php $notifications = json_decode($userProfile['notification_preferences'], true); ?>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="email_alerts"
                                               <?php echo ($notifications['email_alerts'] ?? false) ? 'checked' : ''; ?>
                                               class="mr-2">
                                        <span class="text-gray-400">Email Alerts</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="security_updates"
                                               <?php echo ($notifications['security_updates'] ?? false) ? 'checked' : ''; ?>
                                               class="mr-2">
                                        <span class="text-gray-400">Security Updates</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="new_features"
                                               <?php echo ($notifications['new_features'] ?? false) ? 'checked' : ''; ?>
                                               class="mr-2">
                                        <span class="text-gray-400">New Features</span>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded transition duration-300">
                                Save Profile Changes
                            </button>
                        </form>
                    </div>

                    <!-- Password Change -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Change Password</h2>
                        <form id="passwordForm" class="space-y-4">
                            <input type="hidden" name="action" value="update_password">
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Current Password</label>
                                <input type="password" 
                                       name="current_password" 
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">New Password</label>
                                <input type="password" 
                                       name="new_password" 
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Confirm New Password</label>
                                <input type="password" 
                                       name="confirm_password" 
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                            
                            <div class="password-strength hidden">
                                <label class="block text-gray-400 mb-2">Password Strength</label>
                                <div class="h-2 bg-gray-700 rounded">
                                    <div class="password-strength-meter h-full rounded transition-all duration-300"></div>
                                </div>
                                <ul class="password-requirements text-sm text-gray-400 mt-2"></ul>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded transition duration-300">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('/api/settings/profile', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            });
        });

        // Password form submission
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = this.querySelector('[name="new_password"]').value;
            const confirmPassword = this.querySelector('[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('Passwords do not match', 'error');
                return;
            }
            
            fetch('/api/settings/password', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.reset();
                    document.querySelector('.password-strength').classList.add('hidden');
                }
            });
        });

        // Password strength checker
        document.querySelector('[name="new_password"]').addEventListener('input', function() {
            const password = this.value;
            const strengthContainer = document.querySelector('.password-strength');
            const strengthMeter = document.querySelector('.password-strength-meter');
            const requirementsList = document.querySelector('.password-requirements');
            
            if (password.length > 0) {
                strengthContainer.classList.remove('hidden');
                
                fetch('/api/security/password-strength', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password })
                })
                .then(response => response.json())
                .then(data => {
                    strengthMeter.style.width = data.score + '%';
                    strengthMeter.className = 'password-strength-meter h-full rounded transition-all duration-300 ' + 
                        (data.score >= 80 ? 'bg-green-500' : 
                         data.score >= 60 ? 'bg-yellow-500' : 
                         'bg-red-500');
                    
                    requirementsList.innerHTML = data.issues.map(issue => 
                        `<li class="flex items-center">
                            <svg class="w-4 h-4 mr-2 ${issue.met ? 'text-green-400' : 'text-red-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                ${issue.met ? 
                                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'}
                            </svg>
                            ${issue.message}
                        </li>`
                    ).join('');
                });
            } else {
                strengthContainer.classList.add('hidden');
            }
        });
    </script>

    <script src="/assets/js/main.js"></script>
</body>
</html>