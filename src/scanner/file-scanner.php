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

class FileScanner {
    private $db;
    private $allowedExtensions = ['exe', 'dll', 'doc', 'docx', 'pdf', 'zip', 'rar'];
    private $maxFileSize = 104857600; // 100MB

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function scan($file) {
        $scanResult = [
            'status' => 'scanning',
            'threats' => [],
            'message' => '',
            'started_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Validate file
            $this->validateFile($file);

            // Perform virus scan
            $threats = $this->performScan($file);

            if (count($threats) > 0) {
                $scanResult['status'] = 'threats_found';
                $scanResult['threats'] = $threats;
                $scanResult['message'] = 'Threats detected in file';
            } else {
                $scanResult['status'] = 'clean';
                $scanResult['message'] = 'No threats detected';
            }

            // Log scan results
            $this->logScanResult($file['name'], $scanResult);

            return $scanResult;

        } catch (Exception $e) {
            $scanResult['status'] = 'error';
            $scanResult['message'] = $e->getMessage();
            return $scanResult;
        }
    }

    private function validateFile($file) {
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of 100MB');
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('File type not supported');
        }
    }

    private function performScan($file) {
        $threats = [];
        $fileContent = file_get_contents($file['tmp_name']);
        
        // Check file signature
        $fileHash = hash('sha256', $fileContent);
        
        // Check against known malware signatures
        $stmt = $this->db->prepare('
            SELECT * FROM malware_signatures 
            WHERE file_hash = ? OR signature = ?
        ');
        $stmt->execute([$fileHash, $fileHash]);
        
        if ($stmt->rowCount() > 0) {
            $threats[] = [
                'type' => 'malware',
                'name' => 'Known malware signature detected',
                'severity' => 'high'
            ];
        }

        // Perform heuristic analysis
        $heuristicThreats = $this->heuristicAnalysis($fileContent);
        $threats = array_merge($threats, $heuristicThreats);

        return $threats;
    }

    private function heuristicAnalysis($content) {
        $threats = [];
        
        // Define suspicious patterns
        $suspiciousPatterns = [
            '/eval\s*\(.*\$.*\)/' => 'Potential malicious code execution',
            '/base64_decode\s*\(/' => 'Encoded content detected',
            '/shell_exec|system\s*\(/' => 'System command execution detected'
        ];

        foreach ($suspiciousPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $threats[] = [
                    'type' => 'suspicious_code',
                    'name' => $description,
                    'severity' => 'medium'
                ];
            }
        }

        return $threats;
    }

    private function logScanResult($filename, $result) {
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
            'file',
            $filename,
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
    <title>File Scanner - NetShield Pro</title>
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
                    <h1 class="text-2xl font-bold text-white mb-6">File Scanner</h1>

                    <div class="mb-8">
                        <div class="border-2 border-dashed border-gray-700 rounded-lg p-8 text-center" 
                             id="dropZone">
                            <div class="space-y-4">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <div class="text-gray-400">
                                    <p class="text-lg">Drag and drop files here</p>
                                    <p class="text-sm">or</p>
                                </div>
                                <button onclick="document.getElementById('fileInput').click()" 
                                        class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg transition duration-300">
                                    Select Files
                                </button>
                                <input type="file" id="fileInput" class="hidden" multiple>
                            </div>
                        </div>
                    </div>

                    <div id="scanProgress" class="hidden">
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-400 mb-1">
                                <span>Scanning in progress...</span>
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