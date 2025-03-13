<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$currentDate = '2025-03-03 16:26:01';
$currentUser = 'Devinbeater';

class SecurityStatus {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getSystemStatus() {
        $stmt = $this->db->prepare('
            SELECT * FROM system_settings
            WHERE setting_key IN (
                "real_time_protection",
                "firewall_status",
                "last_update",
                "database_version"
            )
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    public function getSecurityScore() {
        $score = 100;
        $issues = [];
        
        // Check real-time protection
        $stmt = $this->db->prepare('
            SELECT setting_value 
            FROM system_settings 
            WHERE setting_key = "real_time_protection"
        ');
        $stmt->execute();
        if ($stmt->fetchColumn() !== 'enabled') {
            $score -= 20;
            $issues[] = [
                'type' => 'warning',
                'message' => 'Real-time protection is disabled'
            ];
        }
        
        // Check last scan
        $stmt = $this->db->prepare('
            SELECT MAX(completed_at) 
            FROM security_scans 
            WHERE user_id = ?
        ');
        $stmt->execute([$_SESSION['user']['id']]);
        $lastScan = $stmt->fetchColumn();
        
        if (!$lastScan || strtotime($lastScan) < strtotime('-7 days')) {
            $score -= 15;
            $issues[] = [
                'type' => 'warning',
                'message' => 'System scan is overdue'
            ];
        }
        
        // Check for active threats
        $stmt = $this->db->prepare('
            SELECT COUNT(*) 
            FROM threats 
            WHERE scan_id IN (
                SELECT id 
                FROM security_scans 
                WHERE user_id = ?
            ) 
            AND status = "detected"
        ');
        $stmt->execute([$_SESSION['user']['id']]);
        $activeThreats = $stmt->fetchColumn();
        
        if ($activeThreats > 0) {
            $score -= ($activeThreats * 10);
            $issues[] = [
                'type' => 'danger',
                'message' => "$activeThreats active threats detected"
            ];
        }
        
        return [
            'score' => max(0, $score),
            'issues' => $issues
        ];
    }
    
    public function getRecentActivity() {
        $stmt = $this->db->prepare('
            SELECT * 
            FROM activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ');
        $stmt->execute([$_SESSION['user']['id']]);
        return $stmt->fetchAll();
    }
}

$security = new SecurityStatus();
$systemStatus = $security->getSystemStatus();
$securityScore = $security->getSecurityScore();
$recentActivity = $security->getRecentActivity();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Status - NetShield Pro</title>
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
                    <h1 class="text-2xl font-bold text-white mb-6">Security Status</h1>

                    <!-- Security Score -->
                    <div class="bg-gray-800 rounded-lg p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-white">Security Score</h2>
                            <span class="text-2xl font-bold <?php echo $securityScore['score'] >= 80 ? 'text-green-400' : ($securityScore['score'] >= 60 ? 'text-yellow-400' : 'text-red-400'); ?>">
                                <?php echo $securityScore['score']; ?>%
                            </span>
                        </div>
                        <?php if (!empty($securityScore['issues'])): ?>
                        <div class="space-y-2">
                            <?php foreach ($securityScore['issues'] as $issue): ?>
                            <div class="flex items-center space-x-2 p-2 rounded bg-<?php echo $issue['type'] === 'danger' ? 'red' : 'yellow'; ?>-500 bg-opacity-20">
                                <svg class="w-5 h-5 text-<?php echo $issue['type'] === 'danger' ? 'red' : 'yellow'; ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-<?php echo $issue['type'] === 'danger' ? 'red' : 'yellow'; ?>-400">
                                    <?php echo $issue['message']; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- System Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Protection Status</h2>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Real-time Protection</span>
                                    <span class="px-2 py-1 rounded-full text-sm <?php echo $systemStatus['real_time_protection'] === 'enabled' ? 'bg-green-500 bg-opacity-20 text-green-400' : 'bg-red-500 bg-opacity-20 text-red-400'; ?>">
                                        <?php echo ucfirst($systemStatus['real_time_protection']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Firewall</span>
                                    <span class="px-2 py-1 rounded-full text-sm <?php echo $systemStatus['firewall_status'] === 'active' ? 'bg-green-500 bg-opacity-20 text-green-400' : 'bg-red-500 bg-opacity-20 text-red-400'; ?>">
                                        <?php echo ucfirst($systemStatus['firewall_status']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Last Update</span>
                                    <span class="text-gray-300">
                                        <?php echo date('Y-m-d H:i', strtotime($systemStatus['last_update'])); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Database Version</span>
                                    <span class="text-gray-300">
                                        <?php echo $systemStatus['database_version']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Recent Activity</h2>
                            <div class="space-y-3">
                                <?php foreach ($recentActivity as $activity): ?>
                                <div class="flex items-center justify-between p-2 bg-gray-700 rounded">
                                    <div>
                                        <p class="text-white"><?php echo htmlspecialchars($activity['action_type']); ?></p>
                                        <p class="text-sm text-gray-400">
                                            <?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="text-blue-400">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button class="bg-blue-600 hover:bg-blue-500 text-white py-2 px-4 rounded transition duration-300"
                                    onclick="location.href='/scanner/file-scanner.php'">
                                Run Full Scan
                            </button>
                            <button class="bg-blue-600 hover:bg-blue-500 text-white py-2 px-4 rounded transition duration-300"
                                    onclick="updateDatabase()">
                                Update Database
                            </button>
                            <button class="bg-blue-600 hover:bg-blue-500 text-white py-2 px-4 rounded transition duration-300"
                                    onclick="location.href='/settings/security-settings.php'">
                                Security Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateDatabase() {
            fetch('/api/scanner/update-database', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Database update started', 'success');
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showNotification('Failed to update database', 'error');
                }
            });
        }
    </script>

    <script src="/assets/js/main.js"></script>
</body>
</html>