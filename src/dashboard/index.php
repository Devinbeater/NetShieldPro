<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

// Security check
if (!isset($_SESSION['user'])) {
    header('Location: /src/auth/login.php');  // Updated path
    exit;
}

$currentDate = '2025-03-13 13:33:27';
$currentUser = 'Devinbeater';

$db = Database::getInstance()->getConnection();

// Initialize default values in case tables don't exist
$stats = [
    'total_scans' => 0,
    'threats_found' => 0,
    'last_scan' => $currentDate
];

try {
    // Check if security_scans table exists
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'security_scans'
    ");
    $stmt->execute();
    $tableExists = $stmt->fetchColumn() > 0;

    if ($tableExists) {
        // Fetch security statistics with correct column names
        $stmt = $db->prepare('
            SELECT 
                COUNT(*) as total_scans,
                SUM(CASE WHEN status = "malicious" THEN 1 ELSE 0 END) as threats_found,
                MAX(scan_date) as last_scan
            FROM security_scans 
            WHERE user_id = ? 
            AND scan_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ');
        $stmt->execute([$_SESSION['user']['id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Initialize empty array for recent threats
    $recentThreats = [];

    // Check if threats table exists
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'threats'
    ");
    $stmt->execute();
    $threatsTableExists = $stmt->fetchColumn() > 0;

    if ($threatsTableExists) {
        // Fetch recent threats with proper error handling
        $stmt = $db->prepare('
            SELECT t.*, s.scan_type 
            FROM threats t 
            JOIN security_scans s ON t.scan_id = s.id 
            WHERE s.user_id = ? 
            ORDER BY t.detected_at DESC 
            LIMIT 5
        ');
        $stmt->execute([$_SESSION['user']['id']]);
        $recentThreats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Log the error
    error_log(sprintf(
        "[%s] Dashboard database error: %s",
        $currentDate,
        $e->getMessage()
    ));
    
    // Set default values if there's an error
    $stats = [
        'total_scans' => 0,
        'threats_found' => 0,
        'last_scan' => $currentDate
    ];
    $recentThreats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NetShield Pro</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            'bg': '#1A1A2E',
                            'card': '#16213E',
                            'nav': '#0F3460',
                            'border': '#534B62',
                            'hover': '#1E2746'
                        }
                    },
                    animation: {
                        'pulse-soft': 'pulse-soft 2s infinite',
                        'glow': 'glow 2s infinite'
                    }
                }
            }
        }
    </script>
    
    <style type="text/tailwindcss">
        @layer components {
            .glass-card {
                @apply bg-dark-card rounded-lg border border-dark-border/20 
                       transition-all duration-300 hover:border-cyan-500/30
                       hover:shadow-[0_8px_32px_-5px_rgba(49,151,149,0.3)]
                       hover:-translate-y-1;
            }
            
            .stat-value {
                @apply text-3xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 
                       bg-clip-text text-transparent;
            }
            
            .stat-label {
                @apply text-sm font-medium text-gray-400;
            }
            
            .stat-trend-up {
                @apply text-emerald-400 flex items-center text-sm font-medium;
            }
            
            .stat-trend-down {
                @apply text-rose-400 flex items-center text-sm font-medium;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-dark-bg text-gray-100 antialiased" x-data="{ sidebarOpen: false }">
    <!-- Mobile menu button -->
    <button @click="sidebarOpen = true" 
            class="lg:hidden fixed top-4 right-4 z-50 p-2 rounded-md text-gray-400 hover:text-white hover:bg-dark-hover">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Sidebar -->
    <div class="flex h-screen overflow-hidden">
        <!-- Mobile sidebar -->
        <div x-show="sidebarOpen" 
             class="fixed inset-0 z-40 lg:hidden"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <!-- Overlay -->
            <div class="fixed inset-0 bg-dark-bg/80" @click="sidebarOpen = false"></div>
            
            <!-- Sidebar panel -->
            <div class="fixed inset-y-0 left-0 w-64 bg-dark-card">
                <?php include __DIR__ . '/sidebar.php'; ?>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:flex lg:flex-shrink-0">
            <div class="w-64 bg-dark-card">
                <?php include __DIR__ . '/sidebar.php'; ?>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex-1 overflow-auto">
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Welcome Section -->
                    <div class="glass-card p-6 mb-8">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h1 class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                                    Welcome back, <?php echo htmlspecialchars($currentUser); ?>
                                </h1>
                                <p class="text-gray-400 mt-1">System Time: <?php echo $currentDate; ?> UTC</p>
                            </div>
                            <button onclick="location.href='/scanner/file-scanner.php'" 
                                    class="bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 
                                           text-white px-6 py-2.5 rounded-lg transition-all duration-300 
                                           transform hover:-translate-y-1 hover:shadow-lg hover:shadow-cyan-500/25">
                                Quick Scan
                            </button>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Total Scans -->
                        <div class="glass-card p-6">
                            <h3 class="stat-label mb-2">Total Scans (30 days)</h3>
                            <p class="stat-value"><?php echo number_format($stats['total_scans']); ?></p>
                            <div class="mt-2">
                                <span class="stat-trend-up">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    12% increase
                                </span>
                            </div>
                        </div>

                        <!-- Similar pattern for other stat cards... -->
                    </div>

                    <!-- Recent Threats & Activity -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Recent Threats -->
                        <div class="glass-card p-6">
                            <h2 class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent mb-6">
                                Recent Threats
                            </h2>
                            <!-- Threats list content... -->
                        </div>

                        <!-- Security Score -->
                        <div class="glass-card p-6">
                            <h2 class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent mb-6">
                                Security Score
                            </h2>
                            <div class="relative aspect-square max-w-md mx-auto">
                                <canvas id="securityScoreChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Chart configuration with updated styling
        const ctx = document.getElementById('securityScoreChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Protected', 'At Risk', 'Critical'],
                datasets: [{
                    data: [85, 10, 5],
                    backgroundColor: [
                        'rgba(6, 182, 212, 0.8)',   // Cyan
                        'rgba(245, 158, 11, 0.8)',  // Amber
                        'rgba(239, 68, 68, 0.8)'    // Red
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94A3B8',
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>