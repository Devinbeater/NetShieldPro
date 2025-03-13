<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

// Security check
if (!isset($_SESSION['user'])) {
    header('Location: /src/auth/login.php');
    exit;
}

$currentDate = '2025-03-13 14:40:03';
$currentUser = 'Devinbeater';

class ScanReports {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTablesExist();
    }

    private function ensureTablesExist() {
        try {
            // Create security_scans table if not exists
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS security_scans (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    scan_type ENUM('file', 'url', 'system') NOT NULL,
                    target_path VARCHAR(255),
                    status ENUM('pending', 'in_progress', 'completed', 'error') NOT NULL,
                    threats_found INT DEFAULT 0,
                    scan_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    completed_date DATETIME,
                    scan_options JSON,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ");

            // Create threats table if not exists
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS threats (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    scan_id INT NOT NULL,
                    threat_name VARCHAR(255) NOT NULL,
                    threat_type VARCHAR(100) NOT NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                    status ENUM('detected', 'quarantined', 'removed', 'ignored') NOT NULL,
                    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    file_path VARCHAR(255),
                    details JSON,
                    FOREIGN KEY (scan_id) REFERENCES security_scans(id)
                )
            ");
        } catch (PDOException $e) {
            error_log("Failed to create tables: " . $e->getMessage());
        }
    }
    
    public function getReports($userId, $filters = [], $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ['user_id = ?'];
            $params = [$userId];
            
            if (!empty($filters['scan_type'])) {
                $where[] = 'scan_type = ?';
                $params[] = $filters['scan_type'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = 'status = ?';
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['date_range'])) {
                $where[] = 'scan_date >= ? AND scan_date <= ?';
                $params[] = $filters['date_range']['start'];
                $params[] = $filters['date_range']['end'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Get total count
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM security_scans 
                WHERE $whereClause
            ");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get paginated results
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    scan_type,
                    target_path,
                    status,
                    threats_found,
                    scan_date,
                    completed_date
                FROM security_scans 
                WHERE $whereClause 
                ORDER BY scan_date DESC 
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            return [
                'reports' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Failed to get reports: " . $e->getMessage());
            return [
                'reports' => [],
                'total' => 0,
                'pages' => 0,
                'error' => 'Failed to fetch reports'
            ];
        }
    }
}

// Initialize reports class
$reports = new ScanReports();

// Handle filters
$filters = [
    'scan_type' => $_GET['type'] ?? null,
    'status' => $_GET['status'] ?? null,
    'date_range' => isset($_GET['start']) && isset($_GET['end']) ? [
        'start' => $_GET['start'],
        'end' => $_GET['end']
    ] : null
];

// Get current page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get reports
$results = $reports->getReports($_SESSION['user']['id'], $filters, $page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Reports - NetShield Pro</title>
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-900" x-data="{ sidebarOpen: false }">
    <?php include '../common/header.php'; ?>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="hidden lg:flex lg:flex-shrink-0">
            <?php include '../common/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <main class="py-10">
                <div class="glass-card p-6">
                    <!-- Page Header -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            Scan Reports
                        </h1>
                        <p class="text-gray-400 mt-1">View and manage your security scan reports</p>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-800/50 rounded-lg p-4 mb-6">
                        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Scan Type Filter -->
                            <div class="space-y-1">
                                <label class="block text-gray-400 text-sm">Scan Type</label>
                                <select name="type" 
                                        class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 border border-gray-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                    <option value="">All Types</option>
                                    <option value="file" <?php echo ($_GET['type'] ?? '') === 'file' ? 'selected' : ''; ?>>File Scan</option>
                                    <option value="url" <?php echo ($_GET['type'] ?? '') === 'url' ? 'selected' : ''; ?>>URL Scan</option>
                                    <option value="system" <?php echo ($_GET['type'] ?? '') === 'system' ? 'selected' : ''; ?>>System Scan</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="space-y-1">
                                <label class="block text-gray-400 text-sm">Status</label>
                                <select name="status" 
                                        class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 border border-gray-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                    <option value="">All Status</option>
                                    <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="error" <?php echo ($_GET['status'] ?? '') === 'error' ? 'selected' : ''; ?>>Error</option>
                                </select>
                            </div>

                            <!-- Date Range Filter -->
                            <div class="space-y-1">
                                <label class="block text-gray-400 text-sm">Date Range</label>
                                <div class="flex space-x-2">
                                    <input type="date" name="start" 
                                           class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 border border-gray-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                           value="<?php echo $_GET['start'] ?? ''; ?>">
                                    <input type="date" name="end" 
                                           class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 border border-gray-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                           value="<?php echo $_GET['end'] ?? ''; ?>">
                                </div>
                            </div>

                            <!-- Apply Filters Button -->
                            <div class="flex items-end">
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 
                                               hover:from-cyan-400 hover:to-blue-500 
                                               text-white px-4 py-2 rounded-lg transition-all duration-300 
                                               transform hover:-translate-y-1 hover:shadow-lg hover:shadow-cyan-500/25">
                                    Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Reports Table -->
                    <div class="bg-gray-800/50 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-700/50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Target</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Threats</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    <?php if (empty($results['reports'])): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-400">
                                            No scan reports found
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($results['reports'] as $report): ?>
                                        <tr class="hover:bg-gray-700/50 transition-colors duration-200">
                                            <td class="px-6 py-4 text-sm">
                                                <span class="text-gray-300">
                                                    <?php echo date('Y-m-d H:i', strtotime($report['scan_date'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full capitalize
                                                    <?php echo match($report['scan_type']) {
                                                        'file' => 'bg-blue-500 bg-opacity-20 text-blue-400',
                                                        'url' => 'bg-purple-500 bg-opacity-20 text-purple-400',
                                                        'system' => 'bg-green-500 bg-opacity-20 text-green-400',
                                                        default => 'bg-gray-500 bg-opacity-20 text-gray-400'
                                                    }; ?>">
                                                    <?php echo $report['scan_type']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-300">
                                                    <?php echo htmlspecialchars($report['target_path']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full
                                                    <?php echo match($report['status']) {
                                                        'completed' => 'bg-green-500 bg-opacity-20 text-green-400',
                                                        'error' => 'bg-red-500 bg-opacity-20 text-red-400',
                                                        'pending' => 'bg-yellow-500 bg-opacity-20 text-yellow-400',
                                                        default => 'bg-gray-500 bg-opacity-20 text-gray-400'
                                                    }; ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm <?php echo $report['threats_found'] > 0 ? 'text-red-400' : 'text-green-400'; ?>">
                                                    <?php echo number_format($report['threats_found']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <button onclick="showReportDetails(<?php echo $report['id']; ?>)"
                                                        class="text-cyan-400 hover:text-cyan-300 transition-colors duration-200">
                                                    View Details
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                