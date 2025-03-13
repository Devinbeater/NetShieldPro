<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

// Security check
if (!isset($_SESSION['user'])) {
    header('Location: /src/auth/login.php');
    exit;
}

$currentDate = '2025-03-13 13:58:26';
$currentUser = 'Devinbeater';

// Initialize default settings
$settings = [
    'autoScan' => true,
    'scanFrequency' => 'daily',
    'scanArchives' => true,
    'heuristicAnalysis' => true,
    'realTimeProtection' => true,
    'notifications' => [
        'email' => true,
        'browser' => true,
        'desktop' => false
    ]
];

// Get current security settings from database
try {
    $db = Database::getInstance()->getConnection();

    // First, check if the user_settings table exists
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'user_settings'
    ");
    $stmt->execute();
    $tableExists = $stmt->fetchColumn() > 0;

    if (!$tableExists) {
        // Create user_settings table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                category VARCHAR(50) NOT NULL,
                settings JSON NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                UNIQUE KEY unique_user_category (user_id, category)
            )
        ");
    }

    // Try to get user's settings
    $stmt = $db->prepare('
        SELECT settings 
        FROM user_settings 
        WHERE user_id = ? 
        AND category = "security"
    ');
    $stmt->execute([$_SESSION['user']['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Merge saved settings with defaults to ensure all keys exist
        $userSettings = json_decode($result['settings'], true);
        $settings = array_merge($settings, $userSettings);
    } else {
        // Insert default settings for new users
        $stmt = $db->prepare('
            INSERT INTO user_settings (user_id, category, settings)
            VALUES (?, "security", ?)
        ');
        $stmt->execute([
            $_SESSION['user']['id'],
            json_encode($settings)
        ]);
    }

} catch (PDOException $e) {
    error_log(sprintf(
        "[%s] Security settings error: %s",
        $currentDate,
        $e->getMessage()
    ));
    // Keep using default settings if there's an error
}

// Rest of your HTML remains the same, but now $settings will always be defined
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        .glass-card {
            background: rgba(17, 25, 40, 0.75);
            backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.125);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(6, 182, 212, 0.15);
            border-color: rgba(6, 182, 212, 0.3);
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php include '../common/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Settings Header -->
            <div class="glass-card p-6 rounded-lg">
                <h1 class="text-2xl font-bold mb-2 bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                    Security Settings
                </h1>
                <p class="text-gray-400">
                    Configure your security preferences and scanner behavior
                </p>
            </div>

            <!-- Settings Form -->
            <form id="securitySettingsForm" class="space-y-6">
                <!-- Scanning Options -->
                <div class="glass-card p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4 text-cyan-300">Scanning Options</h2>
                    
                    <div class="space-y-4">
                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <div>
                                <span class="font-medium text-white">Automatic Scanning</span>
                                <p class="text-sm text-gray-400">Automatically scan your system on a schedule</p>
                            </div>
                            <input type="checkbox" name="autoScan" 
                                   class="form-checkbox h-5 w-5 text-cyan-500 rounded focus:ring-cyan-500 focus:ring-offset-gray-800"
                                   <?php echo $settings['autoScan'] ? 'checked' : ''; ?>>
                        </label>

                        <div class="p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <label class="block mb-2 font-medium text-white">Scan Frequency</label>
                            <select name="scanFrequency" 
                                    class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                                <option value="hourly" <?php echo $settings['scanFrequency'] === 'hourly' ? 'selected' : ''; ?>>Every Hour</option>
                                <option value="daily" <?php echo $settings['scanFrequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $settings['scanFrequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $settings['scanFrequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>

                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <div>
                                <span class="font-medium text-white">Scan Archives</span>
                                <p class="text-sm text-gray-400">Scan compressed files and archives</p>
                            </div>
                            <input type="checkbox" name="scanArchives" 
                                   class="form-checkbox h-5 w-5 text-cyan-500 rounded focus:ring-cyan-500 focus:ring-offset-gray-800"
                                   <?php echo $settings['scanArchives'] ? 'checked' : ''; ?>>
                        </label>

                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <div>
                                <span class="font-medium text-white">Heuristic Analysis</span>
                                <p class="text-sm text-gray-400">Use advanced detection methods for unknown threats</p>
                            </div>
                            <input type="checkbox" name="heuristicAnalysis" 
                                   class="form-checkbox h-5 w-5 text-cyan-500 rounded focus:ring-cyan-500 focus:ring-offset-gray-800"
                                   <?php echo $settings['heuristicAnalysis'] ? 'checked' : ''; ?>>
                        </label>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="glass-card p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4 text-cyan-300">Notification Preferences</h2>
                    
                    <div class="space-y-4">
                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <div>
                                <span class="font-medium text-white">Email Notifications</span>
                                <p class="text-sm text-gray-400">Receive scan results and alerts via email</p>
                            </div>
                            <input type="checkbox" name="emailNotifications" 
                                   class="form-checkbox h-5 w-5 text-cyan-500 rounded focus:ring-cyan-500 focus:ring-offset-gray-800"
                                   <?php echo $settings['notifications']['email'] ? 'checked' : ''; ?>>
                        </label>

                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <div>
                                <span class="font-medium text-white">Browser Notifications</span>
                                <p class="text-sm text-gray-400">Show alerts in your browser</p>
                            </div>
                            <input type="checkbox" name="browserNotifications" 
                                   class="form-checkbox h-5 w-5 text-cyan-500 rounded focus:ring-cyan-500 focus:ring-offset-gray-800"
                                   <?php echo $settings['notifications']['browser'] ? 'checked' : ''; ?>>
                        </label>

                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg hover:bg-gray-700/50 transition-all duration-300">
                            <div>
                                <span class="font-medium text-white">Desktop Notifications</span>
                                <p class="text-sm text-gray-400">Show system notifications on your desktop</p>
                            </div>
                            <input type="checkbox" name="desktopNotifications" 
                                   class="form-checkbox h-5 w-5 text-cyan-500 rounded focus:ring-cyan-500 focus:ring-offset-gray-800"
                                   <?php echo $settings['notifications']['desktop'] ? 'checked' : ''; ?>>
                        </label>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 
                                   text-white px-8 py-3 rounded-lg transition-all duration-300 
                                   transform hover:-translate-y-1 hover:shadow-lg hover:shadow-cyan-500/25">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('securitySettingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const settings = {
                autoScan: formData.get('autoScan') === 'on',
                scanFrequency: formData.get('scanFrequency'),
                scanArchives: formData.get('scanArchives') === 'on',
                heuristicAnalysis: formData.get('heuristicAnalysis') === 'on',
                notifications: {
                    email: formData.get('emailNotifications') === 'on',
                    browser: formData.get('browserNotifications') === 'on',
                    desktop: formData.get('desktopNotifications') === 'on'
                }
            };

            try {
                const response = await fetch('/api/settings/security', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settings)
                });

                if (!response.ok) {
                    throw new Error('Failed to save settings');
                }

                // Show success message
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg';
                notification.textContent = 'Settings saved successfully';
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);

            } catch (error) {
                // Show error message
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg';
                notification.textContent = error.message;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        });
    </script>
</body>
</html>