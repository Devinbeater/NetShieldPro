<?php
/**
 * NetShield Pro - System Monitoring
 * Current Date: 2025-03-03 17:33:00
 * Current User: Devinbeater
 */

session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/auth-check.php';

// Ensure admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

class SystemMonitor {
    private $db;
    private $currentUser;
    private $currentDate;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->currentUser = 'Devinbeater';
        $this->currentDate = '2025-03-03 17:33:00';
    }

    public function getSystemMetrics($timeframe = '24h') {
        $stmt = $this->db->prepare('
            SELECT 
                cpu_usage,
                memory_usage,
                disk_usage,
                network_traffic,
                active_connections,
                timestamp
            FROM system_metrics
            WHERE timestamp >= NOW() - INTERVAL ? HOUR
            ORDER BY timestamp DESC
        ');
        
        $hours = match($timeframe) {
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };
        
        $stmt->execute([$hours]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveAlerts() {
        $stmt = $this->db->prepare('
            SELECT *
            FROM system_alerts
            WHERE resolved = 0
            ORDER BY severity DESC, created_at DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSystemHealth() {
        $stmt = $this->db->prepare('
            SELECT 
                service_name,
                status,
                last_check,
                uptime,
                error_count
            FROM service_status
            ORDER BY status ASC, service_name ASC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acknowledgeAlert($alertId) {
        $stmt = $this->db->prepare('
            UPDATE system_alerts
            SET 
                acknowledged = 1,
                acknowledged_by = ?,
                acknowledged_at = ?
            WHERE id = ?
        ');
        return $stmt->execute([$this->currentUser, $this->currentDate, $alertId]);
    }

    public function resolveAlert($alertId, $resolution) {
        $stmt = $this->db->prepare('
            UPDATE system_alerts
            SET 
                resolved = 1,
                resolved_by = ?,
                resolved_at = ?,
                resolution = ?
            WHERE id = ?
        ');
        return $stmt->execute([
            $this->currentUser,
            $this->currentDate,
            $resolution,
            $alertId
        ]);
    }

    public function updateAlertThresholds($thresholds) {
        $stmt = $this->db->prepare('
            UPDATE alert_thresholds
            SET 
                cpu_threshold = ?,
                memory_threshold = ?,
                disk_threshold = ?,
                network_threshold = ?,
                updated_by = ?,
                updated_at = ?
        ');
        return $stmt->execute([
            $thresholds['cpu'],
            $thresholds['memory'],
            $thresholds['disk'],
            $thresholds['network'],
            $this->currentUser,
            $this->currentDate
        ]);
    }
}

$monitor = new SystemMonitor();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'acknowledge_alert':
                if (isset($_POST['alert_id'])) {
                    $success = $monitor->acknowledgeAlert($_POST['alert_id']);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Alert acknowledged' : 'Failed to acknowledge alert'
                    ];
                }
                break;

            case 'resolve_alert':
                if (isset($_POST['alert_id']) && isset($_POST['resolution'])) {
                    $success = $monitor->resolveAlert(
                        $_POST['alert_id'],
                        $_POST['resolution']
                    );
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Alert resolved' : 'Failed to resolve alert'
                    ];
                }
                break;

            case 'update_thresholds':
                if (isset($_POST['thresholds'])) {
                    $success = $monitor->updateAlertThresholds($_POST['thresholds']);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Thresholds updated' : 'Failed to update thresholds'
                    ];
                }
                break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$timeframe = $_GET['timeframe'] ?? '24h';
$metrics = $monitor->getSystemMetrics($timeframe);
$activeAlerts = $monitor->getActiveAlerts();
$systemHealth = $monitor->getSystemHealth();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900">
    <?php include '../common/header.php'; ?>

    <div class="flex min-h-screen">
        <?php include '../common/admin-sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="container mx-auto">
                <!-- System Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- CPU Usage -->
                    <div class="glass-card p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">CPU Usage</h3>
                        <canvas id="cpuChart"></canvas>
                    </div>

                    <!-- Memory Usage -->
                    <div class="glass-card p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Memory Usage</h3>
                        <canvas id="memoryChart"></canvas>
                    </div>

                    <!-- Disk Usage -->
                    <div class="glass-card p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Disk Usage</h3>
                        <canvas id="diskChart"></canvas>
                    </div>
                </div>

                <!-- Active Alerts -->
                <div class="glass-card p-6 mb-6">
                    <h2 class="text-2xl font-bold text-white mb-4">Active Alerts</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-800 rounded-lg">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Severity
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Message
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($activeAlerts as $alert): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            <?php echo match($alert['severity']) {
                                                'critical' => 'bg-red-100 text-red-800',
                                                'high' => 'bg-orange-100 text-orange-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'low' => 'bg-green-100 text-green-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            }; ?>">
                                            <?php echo htmlspecialchars($alert['severity']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <?php echo htmlspecialchars($alert['message']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($alert['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if (!$alert['acknowledged']): ?>
                                        <button onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)"
                                                class="text-blue-400 hover:text-blue-300 mr-3">
                                            Acknowledge
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="resolveAlert(<?php echo $alert['id']; ?>)"
                                                class="text-green-400 hover:text-green-300">
                                            Resolve
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Health -->
                <div class="glass-card p-6">
                    <h2 class="text-2xl font-bold text-white mb-4">System Health</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($systemHealth as $service): ?>
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-white">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    <?php echo $service['status'] === 'running' ? 
                                        'bg-green-100 text-green-800' : 
                                        'bg-red-100 text-red-800'; ?>">
                                    <?php echo htmlspecialchars($service['status']); ?>
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-400">
                                <p>Uptime: <?php echo htmlspecialchars($service['uptime']); ?></p>
                                <p>Last Check: <?php echo htmlspecialchars($service['last_check']); ?></p>
                                <p>Errors: <?php echo htmlspecialchars($service['error_count']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize charts
        function initializeCharts() {
            const metrics = <?php echo json_encode($metrics); ?>;
            
            // CPU Chart
            new Chart(document.getElementById('cpuChart'), {
                type: 'line',
                data: {
                    labels: metrics.map(m => new Date(m.timestamp).toLocaleTimeString()),
                    datasets: [{
                        label: 'CPU Usage %',
                        data: metrics.map(m => m.cpu_usage),
                        borderColor: '#3B82F6',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Memory Chart
            new Chart(document.getElementById('memoryChart'), {
                type: 'line',
                data: {
                    labels: metrics.map(m => new Date(m.timestamp).toLocaleTimeString()),
                    datasets: [{
                        label: 'Memory Usage %',
                        data: metrics.map(m => m.memory_usage),
                        borderColor: '#10B981',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Disk Chart
            new Chart(document.getElementById('diskChart'), {
                type: 'line',
                data: {
                    labels: metrics.map(m => new Date(m.timestamp).toLocaleTimeString()),
                    datasets: [{
                        label: 'Disk Usage %',
                        data: metrics.map(m => m.disk_usage),
                        borderColor: '#F59E0B',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Alert management functions
        async function acknowledgeAlert(alertId) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'acknowledge_alert',
                        alert_id: alertId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        }

        async function resolveAlert(alertId) {
            const resolution = prompt('Enter resolution details:');
            if (!resolution) return;

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/}