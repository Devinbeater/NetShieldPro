<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$currentDate = '2025-03-03 16:15:55';
$currentUser = 'Devinbeater';

class ContentFilter {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getFilterSettings($userId) {
        $stmt = $this->db->prepare('
            SELECT content_filter_level, 
                   blocked_categories,
                   custom_blocked_keywords
            FROM parental_controls 
            WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function updateFilterLevel($userId, $level) {
        $stmt = $this->db->prepare('
            UPDATE parental_controls 
            SET content_filter_level = ? 
            WHERE user_id = ?
        ');
        return $stmt->execute([$level, $userId]);
    }
    
    public function updateBlockedCategories($userId, $categories) {
        $stmt = $this->db->prepare('
            UPDATE parental_controls 
            SET blocked_categories = ? 
            WHERE user_id = ?
        ');
        return $stmt->execute([json_encode($categories), $userId]);
    }
    
    public function getFilterStats($userId) {
        $stmt = $this->db->prepare('
            SELECT category, COUNT(*) as count 
            FROM content_blocks 
            WHERE user_id = ? 
            AND timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            GROUP BY category
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

$contentFilter = new ContentFilter();
$settings = $contentFilter->getFilterSettings($_SESSION['user']['id']);
$stats = $contentFilter->getFilterStats($_SESSION['user']['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Filtering - NetShield Pro</title>
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
                    <h1 class="text-2xl font-bold text-white mb-6">Content Filtering</h1>

                    <!-- Filter Level Selection -->
                    <div class="bg-gray-800 rounded-lg p-6 mb-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Filter Level</h2>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <select id="filterLevel" 
                                        class="bg-gray-700 text-white rounded px-4 py-2 flex-1">
                                    <option value="low" <?php echo ($settings['content_filter_level'] === 'low') ? 'selected' : ''; ?>>
                                        Low - Block explicit content only
                                    </option>
                                    <option value="medium" <?php echo ($settings['content_filter_level'] === 'medium') ? 'selected' : ''; ?>>
                                        Medium - Block explicit and mature content
                                    </option>
                                    <option value="high" <?php echo ($settings['content_filter_level'] === 'high') ? 'selected' : ''; ?>>
                                        High - Strict filtering (recommended for children)
                                    </option>
                                </select>
                                <button id="saveFilterLevel" 
                                        class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg transition duration-300">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Content Categories -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Blocked Categories</h2>
                            <div class="space-y-3">
                                <?php
                                $categories = [
                                    'violence' => 'Violence',
                                    'gambling' => 'Gambling',
                                    'adult' => 'Adult Content',
                                    'drugs' => 'Drugs',
                                    'weapons' => 'Weapons',
                                    'hate' => 'Hate Speech',
                                    'social' => 'Social Media',
                                    'gaming' => 'Gaming'
                                ];
                                
                                $blockedCategories = json_decode($settings['blocked_categories'] ?? '[]', true);
                                
                                foreach ($categories as $key => $label):
                                ?>
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" 
                                           class="category-checkbox" 
                                           value="<?php echo $key; ?>"
                                           <?php echo in_array($key, $blockedCategories) ? 'checked' : ''; ?>>
                                    <span class="text-gray-400"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <button id="saveCategories" 
                                    class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg transition duration-300 mt-4">
                                Save Categories
                            </button>
                        </div>

                        <!-- Custom Keywords -->
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Custom Blocked Keywords</h2>
                            <div class="space-y-4">
                                <textarea id="customKeywords" 
                                          class="w-full bg-gray-700 text-white rounded-lg p-3 h-40" 
                                          placeholder="Enter keywords or phrases to block (one per line)"><?php 
                                    echo htmlspecialchars($settings['custom_blocked_keywords'] ?? ''); 
                                ?></textarea>
                                <button id="saveKeywords" 
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg transition duration-300">
                                    Save Keywords
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filtering Statistics -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Filtering Statistics (Last 30 Days)</h2>
                        <canvas id="filteringStats" height="200"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize filtering statistics chart
        const ctx = document.getElementById('filteringStats').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($stats, 'category')); ?>,
                datasets: [{
                    label: 'Blocked Content',
                    data: <?php echo json_encode(array_column($stats, 'count')); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgba(239, 68, 68, 1)',
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

        // Save filter level
        document.getElementById('saveFilterLevel').addEventListener('click', () => {
            const level = document.getElementById('filterLevel').value;
            
            fetch('/api/parental-controls/filter-level', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ level })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Filter level updated successfully', 'success');
                } else {
                    showNotification('Failed to update filter level', 'error');
                }
            });
        });

        // Save categories
        document.getElementById('saveCategories').addEventListener('click', () => {
            const categories = Array.from(document.querySelectorAll('.category-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            fetch('/api/parental-controls/categories', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ categories })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Categories updated successfully', 'success');
                } else {
                    showNotification('Failed to update categories', 'error');
                }
            });
        });

        // Save keywords
        document.getElementById('saveKeywords').addEventListener('click', () => {
            const keywords = document.getElementById('customKeywords').value;
            
            fetch('/api/parental-controls/keywords', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ keywords })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Keywords updated successfully', 'success');
                } else {
                    showNotification('Failed to update keywords', 'error');
                }
            });
        });
    </script>

    <script src="/assets/js/main.js"></script>
</body>
</html>