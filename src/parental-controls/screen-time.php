<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /src/auth/login.php');
    exit;
}

$currentDate = '2025-03-13 14:11:25';
$currentUser = 'Devinbeater';

class ScreenTimeManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTablesExist();
    }
    
    private function ensureTablesExist() {
        try {
            // Create parental_controls table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS parental_controls (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    screen_time_limit INT DEFAULT 120,
                    schedule JSON,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ");

            // Create screen_time_logs table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS screen_time_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    date DATE NOT NULL,
                    usage_time INT NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    UNIQUE KEY unique_user_date (user_id, date)
                )
            ");
        } catch (PDOException $e) {
            error_log("Failed to create tables: " . $e->getMessage());
        }
    }

    public function getSettings($userId) {
        try {
            $stmt = $this->db->prepare('
                SELECT screen_time_limit, schedule 
                FROM parental_controls 
                WHERE user_id = ?
            ');
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Insert default settings if none exist
                $defaultSchedule = json_encode([
                    'Monday' => ['start' => '09:00', 'end' => '17:00'],
                    'Tuesday' => ['start' => '09:00', 'end' => '17:00'],
                    'Wednesday' => ['start' => '09:00', 'end' => '17:00'],
                    'Thursday' => ['start' => '09:00', 'end' => '17:00'],
                    'Friday' => ['start' => '09:00', 'end' => '17:00'],
                    'Saturday' => ['start' => '10:00', 'end' => '16:00'],
                    'Sunday' => ['start' => '10:00', 'end' => '16:00']
                ]);

                $stmt = $this->db->prepare('
                    INSERT INTO parental_controls (user_id, screen_time_limit, schedule)
                    VALUES (?, 120, ?)
                ');
                $stmt->execute([$userId, $defaultSchedule]);

                return [
                    'screen_time_limit' => 120,
                    'schedule' => $defaultSchedule
                ];
            }

            return $settings;
        } catch (PDOException $e) {
            error_log("Failed to get settings: " . $e->getMessage());
            return [
                'screen_time_limit' => 120,
                'schedule' => '{}',
                'error' => true
            ];
        }
    }

    public function getUsageStats($userId, $days = 7) {
        try {
            $stmt = $this->db->prepare('
                SELECT date, usage_time 
                FROM screen_time_logs 
                WHERE user_id = ? 
                AND date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
                ORDER BY date ASC
            ');
            $stmt->execute([$userId, $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get usage stats: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize the manager and get data
$manager = new ScreenTimeManager();
$settings = $manager->getSettings($_SESSION['user']['id']);
$usageStats = $manager->getUsageStats($_SESSION['user']['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Time - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900">
    <?php include '../common/header.php'; ?>

    <div class="flex min-h-screen">
        <?php include '../dashboard/sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="container mx-auto">
                <div class="glass-card p-6">
                    <h1 class="text-2xl font-bold text-white mb-6">Screen Time Management</h1>

                    <!-- Daily Limits -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Daily Time Limit</h2>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <label for="timeLimit" class="text-gray-400">Hours per day</label>
                                    <span class="text-gray-400" id="timeLimitValue">
                                        <?php echo ($settings['screen_time_limit'] ?? 0) / 60; ?>
                                    </span>
                                </div>
                                <input type="range" 
                                       id="timeLimit" 
                                       min="0" 
                                       max="12" 
                                       step="0.5" 
                                       value="<?php echo ($settings['screen_time_limit'] ?? 0) / 60; ?>" 
                                       class="w-full">
                                <button id="saveTimeLimit" 
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg transition duration-300">
                                    Save Time Limit
                                </button>
                            </div>
                        </div>

                        <!-- Schedule -->
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Usage Schedule</h2>
                            <div class="space-y-4">
                                <?php
                                $schedule = json_decode($settings['schedule'] ?? '{}', true);
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day):
                                ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400"><?php echo $day; ?></span>
                                    <div class="flex space-x-2">
                                        <input type="time" 
                                               class="bg-gray-700 text-white rounded px-2 py-1"
                                               value="<?php echo $schedule[$day]['start'] ?? '09:00'; ?>"
                                               data-day="<?php echo $day; ?>"
                                               data-type="start">
                                        <span class="text-gray-400">to</span>
                                        <input type="time" 
                                               class="bg-gray-700 text-white rounded px-2 py-1"
                                               value="<?php echo $schedule[$day]['end'] ?? '17:00'; ?>"
                                               data-day="<?php echo $day; ?>"
                                               data-type="end">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <button id="saveSchedule" 
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg transition duration-300">
                                    Save Schedule
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Statistics -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Usage Statistics</h2>
                        <canvas id="usageChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize usage statistics chart
        const ctx = document.getElementById('usageChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($usageStats, 'date')); ?>,
                datasets: [{
                    label: 'Daily Usage (hours)',
                    data: <?php echo json_encode(array_map(function($stat) {
                        return $stat['usage_time'] / 60;
                    }, $usageStats)); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e2e8f0'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#e2e8f0'
                        }
                    }
                }
            }
        });

        // Time limit slider
        const timeLimit = document.getElementById('timeLimit');
        const timeLimitValue = document.getElementById('timeLimitValue');
        
        timeLimit.addEventListener('input', () => {
            timeLimitValue.textContent = timeLimit.value;
        });

        // Save time limit
        document.getElementById('saveTimeLimit').addEventListener('click', () => {
            fetch('/api/parental-controls/screen-time', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    limit: timeLimit.value * 60
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Time limit updated successfully', 'success');
                } else {
                    showNotification('Failed to update time limit', 'error');
                }
            });
        });

        // Save schedule
        document.getElementById('saveSchedule').addEventListener('click', () => {
            const schedule = {};
            document.querySelectorAll('input[type="time"]').forEach(input => {
                const day = input.dataset.day;
                const type = input.dataset.type;
                
                if (!schedule[day]) schedule[day] = {};
                schedule[day][type] = input.value;
            });

            fetch('/api/parental-controls/schedule', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ schedule })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Schedule updated successfully', 'success');
                } else {
                    showNotification('Failed to update schedule', 'error');
                }
            });
        });
    </script>

    <script src="/assets/js/main.js"></script>
</body>
</html>