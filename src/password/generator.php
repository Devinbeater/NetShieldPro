<?php
session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/security-functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$currentDate = '2025-03-03 16:10:23';
$currentUser = 'Devinbeater';

class PasswordGenerator {
    private $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    private $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private $numbers = '0123456789';
    private $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    public function generate($length = 16, $options = []) {
        $chars = '';
        $password = '';
        
        // Add character sets based on options
        if ($options['lowercase'] ?? true) $chars .= $this->lowercase;
        if ($options['uppercase'] ?? true) $chars .= $this->uppercase;
        if ($options['numbers'] ?? true) $chars .= $this->numbers;
        if ($options['special'] ?? true) $chars .= $this->special;
        
        // Ensure at least one character from each selected set
        if ($options['lowercase'] ?? true) 
            $password .= $this->lowercase[random_int(0, strlen($this->lowercase) - 1)];
        if ($options['uppercase'] ?? true) 
            $password .= $this->uppercase[random_int(0, strlen($this->uppercase) - 1)];
        if ($options['numbers'] ?? true) 
            $password .= $this->numbers[random_int(0, strlen($this->numbers) - 1)];
        if ($options['special'] ?? true) 
            $password .= $this->special[random_int(0, strlen($this->special) - 1)];
        
        // Fill remaining length with random characters
        while (strlen($password) < $length) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }

    public function calculateStrength($password) {
        $score = 0;
        
        // Length check
        $score += strlen($password) * 4;
        
        // Character variety checks
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 15;
        
        // Deductions for patterns
        if (preg_match('/(.)\1+/', $password)) $score -= 10; // Repeated characters
        if (preg_match('/[0-9]{3,}/', $password)) $score -= 5; // Sequential numbers
        
        return min(100, max(0, $score));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Generator - NetShield Pro</title>
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
                    <h1 class="text-2xl font-bold text-white mb-6">Password Generator</h1>

                    <div class="space-y-6">
                        <!-- Password Generation Form -->
                        <div class="bg-gray-800 rounded-lg p-6">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <label for="passwordLength" class="text-gray-400">Password Length</label>
                                    <span class="text-gray-400" id="lengthValue">16</span>
                                </div>
                                <input type="range" 
                                       id="passwordLength" 
                                       min="8" 
                                       max="64" 
                                       value="16" 
                                       class="w-full">

                                <div class="grid grid-cols-2 gap-4">
                                    <label class="flex items-center text-gray-400">
                                        <input type="checkbox" 
                                               id="lowercase" 
                                               checked 
                                               class="mr-2">
                                        Lowercase Letters
                                    </label>
                                    <label class="flex items-center text-gray-400">
                                        <input type="checkbox" 
                                               id="uppercase" 
                                               checked 
                                               class="mr-2">
                                        Uppercase Letters
                                    </label>
                                    <label class="flex items-center text-gray-400">
                                        <input type="checkbox" 
                                               id="numbers" 
                                               checked 
                                               class="mr-2">
                                        Numbers
                                    </label>
                                    <label class="flex items-center text-gray-400">
                                        <input type="checkbox" 
                                               id="special" 
                                               checked 
                                               class="mr-2">
                                        Special Characters
                                    </label>
                                </div>

                                <button id="generateBtn" 
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg transition duration-300">
                                    Generate Password
                                </button>
                            </div>
                        </div>

                        <!-- Generated Password Display -->
                        <div class="bg-gray-800 rounded-lg p-6">
                            <div class="relative">
                                <input type="text" 
                                       id="generatedPassword" 
                                       readonly 
                                       class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg pr-20"
                                       placeholder="Generated password will appear here">
                                <button id="copyBtn" 
                                        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-400 hover:text-blue-300">
                                    Copy
                                </button>
                            </div>

                            <!-- Password Strength Meter -->
                            <div class="mt-4">
                                <div class="flex justify-between text-sm text-gray-400 mb-1">
                                    <span>Password Strength</span>
                                    <span id="strengthText">None</span>
                                </div>
                                <div class="bg-gray-700 rounded-full h-2">
                                    <div id="strengthMeter" 
                                         class="h-full rounded-full transition-all duration-300"
                                         style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Password History -->
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Recently Generated</h3>
                            <div id="passwordHistory" class="space-y-2">
                                <!-- Password history items will be added here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordLength = document.getElementById('passwordLength');
            const lengthValue = document.getElementById('lengthValue');
            const generateBtn = document.getElementById('generateBtn');
            const generatedPassword = document.getElementById('generatedPassword');
            const copyBtn = document.getElementById('copyBtn');
            const strengthMeter = document.getElementById('strengthMeter');
            const strengthText = document.getElementById('strengthText');
            const passwordHistory = document.getElementById('passwordHistory');

            // Update length value display
            passwordLength.addEventListener('input', () => {
                lengthValue.textContent = passwordLength.value;
            });

            // Generate password
            generateBtn.addEventListener('click', () => {
                const options = {
                    lowercase: document.getElementById('lowercase').checked,
                    uppercase: document.getElementById('uppercase').checked,
                    numbers: document.getElementById('numbers').checked,
                    special: document.getElementById('special').checked
                };

                fetch('/api/password/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        length: parseInt(passwordLength.value),
                        options: options
                    })
                })
                .then(response => response.json())
                .then(data => {
                    generatedPassword.value = data.password;
                    updateStrengthMeter(data.strength);
                    addToHistory(data.password);
                });
            });

            // Copy password
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(generatedPassword.value)
                    .then(() => {
                        copyBtn.textContent = 'Copied!';
                        setTimeout(() => {
                            copyBtn.textContent = 'Copy';
                        }, 2000);
                    });
            });

            function updateStrengthMeter(strength) {
                strengthMeter.style.width = `${strength}%`;
                strengthMeter.className = `h-full rounded-full transition-all duration-300 ${getStrengthClass(strength)}`;
                strengthText.textContent = getStrengthText(strength);
            }

            function getStrengthClass(strength) {
                if (strength >= 80) return 'bg-green-500';
                if (strength >= 60) return 'bg-yellow-500';
                if (strength >= 40) return 'bg-orange-500';
                return 'bg-red-500';
            }

            function getStrengthText(strength) {
                if (strength >= 80) return 'Very Strong';
                if (strength >= 60) return 'Strong';
                if (strength >= 40) return 'Medium';
                return 'Weak';
            }

            function addToHistory(password) {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between bg-gray-700 rounded px-4 py-2';
                item.innerHTML = `
                    <span class="text-gray-400">${'*'.repeat(password.length)}</span>
                    <button class="text-blue-400 hover:text-blue-300 text-sm" 
                            onclick="navigator.clipboard.writeText('${password}')">
                        Copy
                    </button>
                `;
                
                passwordHistory.insertBefore(item, passwordHistory.firstChild);
                
                // Keep only last 5 items
                if (passwordHistory.children.length > 5) {
                    passwordHistory.removeChild(passwordHistory.lastChild);
                }
            }
        });
    </script>

    <script src="/assets/js/main.js"></script>
</body>
</html>