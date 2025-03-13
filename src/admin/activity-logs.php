<?php
/**
 * NetShield Pro - Activity Logs
 * Current Date: 2025-03-03 17:30:08
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

class ActivityLogger {
    private $db;
    private $currentUser;
    private $currentDate;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->currentUser = 'Devinbeater';
        $this->currentDate = '2025-03-03 17:30:08';
    }

    public function getLogs($filters = [], $page = 1, $perPage = 50) {
        $query = "SELECT l.*, u.username 
                 FROM activity_logs l 
                 LEFT JOIN users u ON l.user_id = u.id 
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $query .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action_type'])) {
            $query .= " AND l.action_type = ?";
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND l.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND l.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['severity'])) {
            $query .= " AND l.severity = ?";
            $params[] = $filters['severity'];
        }

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $query .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLogCount($filters = []) {
        $query = "SELECT COUNT(*) FROM activity_logs l WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $query .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action_type'])) {
            $query .= " AND l.action_type = ?";
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND l.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND l.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['severity'])) {
            $query .= " AND l.severity = ?";
            $params[] = $filters['severity'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function exportLogs($filters = []) {
        $logs = $this->getLogs($filters, 1, PHP_INT_MAX);
        
        $filename = "activity_logs_" . date('Y-m-d_His') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Timestamp', 'User', 'Action', 'Description', 'IP Address', 'Severity']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['username'],
                $log['action_type'],
                $log['description'],
                $log['ip_address'],
                $log['severity']
            ]);
        }

        fclose($output);
        exit;
    }
}

$logger = new ActivityLogger();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'export':
                $filters = json_decode($_POST['filters'] ?? '{}', true);
                $logger->exportLogs($filters);
                break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get filters from request
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'action_type' => $_GET['action_type'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'severity' => $_GET['severity'] ?? null
];

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

$logs = $logger->getLogs($filters, $page, $perPage);
$totalLogs = $logger->getLogCount($filters);
$totalPages = ceil($totalLogs / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-gray-900">
    <?php include '../common/header.php'; ?>

    <div class="flex min-h-screen">
        <?php include '../common/admin-sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="container mx-auto">
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-white">Activity Logs</h1>
                        <button onclick="exportLogs()" 
                                class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded">
                            Export Logs
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-800 rounded-lg p-4 mb-6">
                        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-400 mb-2">Date Range</label>
                                <input type="date" name="start_date" 
                                       value="<?php echo $filters['start_date'] ?? ''; ?>"
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                                <input type="date" name="end_date" 
                                       value="<?php echo $filters['end_date'] ?? ''; ?>"
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2 mt-2">
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Action Type</label>
                                <select name="action_type" 
                                        class="w-full bg-gray-700 text-white rounded px-4 py-2">
                                    <option value="">All Actions</option>
                                    <option value="login" <?php echo $filters['action_type'] === 'login' ? 'selected' : ''; ?>>Login</option>
                                    <option value="logout" <?php echo $filters['action_type'] === 'logout' ? 'selected' : ''; ?>>Logout</option>
                                    <option value="settings_change" <?php echo $filters['action_type'] === 'settings_change' ? 'selected' : ''; ?>>Settings Change</option>
                                    <option value="security_alert" <?php echo $filters['action_type'] === 'security_alert' ? 'selected' : ''; ?>>Security Alert</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 mb-2">Severity</label>
                                <select name="severity" 
                                        class="w-full bg-gray-700 text-white rounded px-4 py-2">
                                    <option value="">All Severities</option>
                                    <option value="low" <?php echo $filters['severity'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $filters['severity'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $filters['severity'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="critical" <?php echo $filters['severity'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-3 flex justify-end">
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded">
                                    Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Logs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-800 rounded-lg">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Timestamp
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Action
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        IP Address
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Severity
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($log['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($log['username']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($log['action_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                   <?php echo match($log['severity']) {
                                                       'low' => 'bg-green-100 text-green-800',
                                                       'medium' => 'bg-yellow-100 text-yellow-800',
                                                       'high' => 'bg-orange-100 text-orange-800',
                                                       'critical' => 'bg-red-100 text-red-800',
                                                       default => 'bg-gray-100 text-gray-800'
                                                   }; ?>">
                                            <?php echo htmlspecialchars($log['severity']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex justify-between items-center mt-6">
                        <div class="text-gray-400">
                            Showing <?php echo ($page - 1) * $perPage + 1; ?> to 
                            <?php echo min($page * $perPage, $totalLogs); ?> of 
                            <?php echo $totalLogs; ?> entries
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" 
                               class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700">
                                Previous
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" 
                               class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Filter form handling
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = '?' + params.toString();
        });

        // Export functionality
        function exportLogs() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const actionInput = document.