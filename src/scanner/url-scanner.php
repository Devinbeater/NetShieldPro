<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$currentDate = '2025-03-03 16:02:47';
$currentUser = 'Devinbeater';

class URLScanner {
    private $db;
    private $blacklist;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadBlacklist();
    }

    private function loadBlacklist() {
        $stmt = $this->db->query('SELECT url_pattern FROM malicious_urls');
        $this->blacklist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function scan($url) {
        $scanResult = [
            'status' => 'scanning',
            'threats' => [],
            'message' => '',
            'started_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL format');
            }

            // Check against blacklist
            if ($this->isBlacklisted($url)) {
                $scanResult['status'] = 'threats_found';
                $scanResult['threats'][] = [
                    'type' => 'malicious_url',
                    'name' => 'Known malicious URL',
                    'severity' => 'high'
                ];
            }

            // Perform additional checks
            $urlThreats = $this->analyzeURL($url);
            $scanResult['threats'] = array_merge($scanResult['threats'], $urlThreats);

            if (count($scanResult['threats']) > 0) {
                $scanResult['status'] = 'threats_found';
                $scanResult['message'] = 'Threats detected in URL';
            } else {
                $scanResult['status'] = 'clean';
                $scanResult['message'] = 'URL appears safe';
            }

            // Log scan results
            $this->logScanResult($url, $scanResult);

            return $scanResult;

        } catch (Exception $e) {
            $scanResult['status'] = 'error';
            $scanResult['message'] = $e->getMessage();
            return $scanResult;
        }
    }

    private function isBlacklisted($url) {
        foreach ($this->blacklist as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    private function analyzeURL($url) {
        $threats = [];
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/' => 'IP address in URL',
            '/[a-zA-Z0-9]+\.(exe|dll|bat|cmd)$/' => 'Executable file download',
            '/password|login|signin/' => 'Potential phishing page'
        ];

        foreach ($suspiciousPatterns as $pattern => $description) {
            if (preg_match($pattern, $url)) {
                $threats[] = [
                    'type' => 'suspicious_url',
                    'name' => $description,
                    'severity' => 'medium'
                ];
            }
        }

        return $threats;
    }

    private function logScanResult($url, $result) {
        $stmt = $this->db->prepare('
            INSERT INTO security_scans (
                user_id, 
                scan_type, 
                target_path, 
                status, 
                threats_found, 
                details,
                started_at,
                completed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');

        $stmt->execute([
            $_SESSION['user']['id'],
            'url',
            $url,
            $result['status'],
            count($result['threats']),
            json_encode($result),
            $result['started_at']
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Scanner - NetShield Pro</title>
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
                    <h1 class="text-2xl font-bold text-white mb-6">URL Scanner</h1>

                    <div class="mb-8">
                        <form id="urlScanForm" class="space-y-4">
                            <div>
                                <label for="url" class="block text-gray-400 mb-2">Enter URL to scan</label>
                                <input type="url" 
                                       id="url" 
                                       name="url" 
                                       class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                       placeholder="https://example.com"
                                       required>
                            </div>

                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-lg transition duration-300">
                                Scan URL
                            </button>
                        </form>
                    </div>

                    <div id="scanProgress" class="hidden">
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-400 mb-1">
                                <span>Analyzing URL...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="bg-gray-700 rounded-full h-2">
                                <div class="scan-progress h-full rounded-full" id="progressBar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <div id="scanResults" class="space-y-4"></div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/scanner.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>