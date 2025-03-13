<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', 'C:/xampp/htdocs/src');

// Include class loader first
require_once __DIR__ . '/../utils/class-loader.php';

// Then include required files
require_once __DIR__ . '/../utils/security-functions.php';
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/auth-handlers.php';

// Current timestamp and user info
$currentDate = '2025-03-06 08:23:34';
$currentUser = 'Devinbeater';

if (isset($_SESSION['user'])) {
    header('Location: /dashboard/');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthHandler();
    $result = $auth->register(
        $_POST['username'],
        $_POST['email'],
        $_POST['password'],
        $_POST['password_confirm']
    );
    
    if ($result['status'] === 'success') {
        $success = 'Registration successful! You can now login.';
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="glass-card p-8 w-96 space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-white mb-2">Create Account</h1>
                <p class="text-gray-400">Join NetShield Pro today</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-100 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-100 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-gray-300 mb-1" for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-4 py-2 focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label class="block text-gray-300 mb-1" for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-4 py-2 focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label class="block text-gray-300 mb-1" for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-4 py-2 focus:outline-none focus:border-blue-500"
                           required>
                    <div class="password-strength mt-1"></div>
                </div>

                <div>
                    <label class="block text-gray-300 mb-1" for="password_confirm">Confirm Password</label>
                    <input type="password" 
                           id="password_confirm" 
                           name="password_confirm" 
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-4 py-2 focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <div class="text-sm text-gray-400">
                    <p>Password must contain:</p>
                    <ul class="list-disc list-inside">
                        <li>At least 12 characters</li>
                        <li>Upper and lowercase letters</li>
                        <li>Numbers and special characters</li>
                    </ul>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="terms" 
                           name="terms" 
                           class="mr-2" 
                           required>
                    <label for="terms" class="text-sm text-gray-400">
                        I agree to the 
                        <a href="/terms" class="text-blue-400 hover:text-blue-300">Terms of Service</a>
                        and
                        <a href="/privacy" class="text-blue-400 hover:text-blue-300">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded transition duration-300">
                    Create Account
                </button>
            </form>

            <div class="text-center text-gray-400">
                <p>Already have an account? 
                    <a href="/src/auth/login.php" class="text-blue-400 hover:text-blue-300">Sign in</a>
                </p>
            </div>

            <div class="text-center text-xs text-gray-500">
                <p>System Time: <?php echo $currentDate; ?> UTC</p>
            </div>
        </div>
    </div>

    <script src="C:\assets\js\main.js"></script>
    <script src="C:\assets\js\password-strength.js"></script>
</body>
</html>