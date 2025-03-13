<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$currentDate = '2025-03-03 16:20:19';
$currentUser = 'Devinbeater';

class AppBlocker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getBlockedApps($userId) {
        $stmt = $this->db->prepare('
            SELECT blocked_apps 
            FROM parental_controls 
            WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return json_decode($result['blocked_apps'] ?? '[]', true);
    }
    
    public function updateBlockedApps($userId, $apps) {
        $stmt = $this->db->prepare('
            UPDATE parental_controls 
            SET blocked_apps = ? 
            WHERE user_id = ?
        ');
        return $stmt->execute([json_encode($apps), $userId]);
    }
    
    public function getSystemApps() {
        // This would typically scan the system for installed applications
        // For demonstration, we'll return a sample list
        return [
            'browsers' => [
                'chrome' => 'Google Chrome',
                'firefox' => 'Mozilla Firefox',
                'edge' => 'Microsoft Edge',
                'safari' => 'Safari'
            ],
            'social' => [
                'discord' => 'Discord',
                'telegram' => 'Telegram',
                'whatsapp' => 'WhatsApp',
                'messenger' => 'Facebook Messenger'
            ],
            'games' => [
                'steam' => 'Steam',
                'epic' => 'Epic Games',
                'roblox' => 'Roblox',
                'minecraft' => 'Minecraft'
            ],
            'productivity' => [
                'word' => 'Microsoft Word',
                'excel' => 'Microsoft Excel',
                'powerpoint' => 'Microsoft PowerPoint',
                'notes' => 'Notes'
            ]
        ];
    }
}

$appBlocker = new AppBlocker();
$blockedApps = $appBlocker->getBlockedApps($_SESSION['user']['id']);
$systemApps = $appBlocker->getSystemApps();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Blocking - NetShield Pro</title>
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
                    <h1 class="text-2xl font-bold text-white mb-6">App Blocking</h1>

                    <!-- App Categories -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($systemApps as $category => $apps): ?>
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4 capitalize">
                                <?php echo $category; ?>
                            </h2>
                            <div class="space-y-3">
                                <?php foreach ($apps as $appId => $appName): ?>
                                <label class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                                    <span class="text-gray-300"><?php echo $appName; ?></span>
                                    <div class="relative">
                                        <input type="checkbox" 
                                               class="app-checkbox hidden" 
                                               id="app_<?php echo $appId; ?>"
                                               value="<?php echo $appId; ?>"
                                               <?php echo in_array($appId, $blockedApps) ? 'checked' : ''; ?>>
                                        <div class="toggle-switch w-12 h-6 bg-gray-600 rounded-full relative cursor-pointer">
                                            <div class="toggle-dot absolute w-4 h-4 bg-white rounded-full top-1 left-1 transition-transform duration-300"></div>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Save Changes Button -->
                    <div class="mt-6">
                        <button id="saveChanges" 
                                class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg transition duration-300">
                            Save Changes
                        </button>
                    </div>

                    <!-- Schedule Block -->
                    <div class="mt-6 bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Block Schedule</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-400 mb-2">Start Time</label>
                                <input type="time" 
                                       id="blockStartTime" 
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-2">End Time</label>
                                <input type="time" 
                                       id="blockEndTime" 
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-gray-400 mb-2">Active Days</label>
                            <div class="flex flex-wrap gap-2">
                                <?php
                                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                foreach ($days as $day):
                                ?>
                                <label class="day-toggle cursor-pointer">
                                    <input type="checkbox" class="hidden day-checkbox" value="<?php echo $day; ?>">
                                    <span class="inline-block px-3 py-1 rounded bg-gray-700 text-gray-400 hover:bg-gray-600">
                                        <?php echo $day; ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle switch functionality
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const checkbox = this.parentElement.querySelector('.app-checkbox');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('active');
                updateToggleState(this);
            });
        });

        // Update toggle state
        function updateToggleState(toggle) {
            const dot = toggle.querySelector('.toggle-dot');
            if (toggle.classList.contains('active')) {
                toggle.classList.add('bg-blue-600');
                toggle.classList.remove('bg-gray-600');
                dot.style.transform = 'translateX(24px)';
            } else {
                toggle.classList.remove('bg-blue-600');
                toggle.classList.add('bg-gray-600');
                dot.style.transform = 'translateX(0)';
            }
        }

        // Initialize toggle states
        document.querySelectorAll('.app-checkbox').forEach(checkbox => {
            const toggle = checkbox.parentElement.querySelector('.toggle-switch');
            if (checkbox.checked) {
                toggle.classList.add('active');
                updateToggleState(toggle);
            }
        });

        // Day toggle functionality
        document.querySelectorAll('.day-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const checkbox = this.querySelector('.day-checkbox');
                const span = this.querySelector('span');
                checkbox.checked = !checkbox.checked;
                
                if (checkbox.checked) {
                    span.classList.remove('bg-gray-700', 'text-gray-400');
                    span.classList.add('bg-blue-600', 'text-white');
                } else {
                    span.classList.add('bg-gray-700', 'text-gray-400');
                    span.classList.remove('bg-blue-600', 'text-white');
                }
            });
        });

        // Save changes
        document.getElementById('saveChanges').addEventListener('click', () => {
            const blockedApps = Array.from(document.querySelectorAll('.app-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            const schedule = {
                startTime: document.getElementById('blockStartTime').value,
                endTime: document.getElementById('blockEndTime').value,
                days: Array.from(document.querySelectorAll('.day-checkbox:checked'))
                    .map(checkbox => checkbox.value)
            };

            fetch('/api/parental-controls/app-blocking', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    blockedApps,
                    schedule
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('App blocking settings updated successfully', 'success');
                } else {
                    showNotification('Failed to update app blocking settings', 'error');
                }
            });
        });
    </script>

    <script src="/assets/js/main.js"></script>
</body>
</html>