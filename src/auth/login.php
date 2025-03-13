<?php
session_start();

// Use absolute paths since directories exist
define('BASE_PATH', 'C:/xampp/htdocs/src');

// Simple requires using the base path
require_once BASE_PATH . '/utils/db-connect.php';
require_once BASE_PATH . '/auth/auth-handlers.php';
require_once BASE_PATH . '/utils/security-functions.php';

// Updated timestamp and user info
$currentDate = '2025-03-13 13:12:03';
$currentUser = 'Devinbeater';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: src/dashboard/index.php');
    exit;
}

$error = '';
$security = new SecurityFunctions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (!$security->validateEmail($email)) {
            throw new Exception('Invalid email format');
        }

        if (empty($password)) {
            throw new Exception('Password is required');
        }

        // Get database connection
        $db = Database::getInstance()->getConnection();

        // Check user credentials
        $stmt = $db->prepare('
            SELECT 
                id,
                username,
                email,
                password_hash,
                role,
                account_status,
                created_at
            FROM users 
            WHERE email = ?
        ');

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('Invalid email or password');
        }

        // Use SecurityFunctions to verify password
        if (!$security->verifyPassword($password, $user['password_hash'])) {
            // Log failed attempt
            $stmt = $db->prepare('
                INSERT INTO login_attempts (
                    user_id,
                    ip_address,
                    attempt_time,
                    success
                ) VALUES (?, ?, NOW(), FALSE)
            ');
            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
            
            throw new Exception('Invalid email or password');
        }

        // Check account status
        if ($user['account_status'] === 'pending') {
            // Get time difference
            $createdTime = strtotime($user['created_at']);
            $currentTime = strtotime($currentDate);
            $hoursDiff = round(($currentTime - $createdTime) / 3600, 1);
            
            throw new Exception(
                'Your account is pending activation. ' .
                'Please check your email for verification instructions. ' .
                'Account created ' . $hoursDiff . ' hours ago. ' .
                'If you did not receive the verification email, you can ' .
                '<a href="/src/auth/resend-verification.php" class="text-blue-400 hover:text-blue-300">request a new one</a>.'
            );
        } elseif ($user['account_status'] !== 'active') {
            throw new Exception('Account is ' . $user['account_status']);
        }

        // Create session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // Update last login
        $stmt = $db->prepare('
            UPDATE users 
            SET last_login = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$user['id']]);

         // Log successful login
        $stmt = $db->prepare('
            INSERT INTO activity_logs (
                user_id,
                action_type,
                description,
                ip_address,
                user_agent,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user['id'],
            'login',
            'Successful login attempt',
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $currentDate
        ]);

        // Redirect to dashboard with correct path
        header('Location: /src/dashboard/index.php');
        exit;

    } catch (Exception $e) {
        error_log(sprintf(
            "[%s] Login error: %s",
            $currentDate,
            $e->getMessage()
        ));
        $error = $e->getMessage();
    }
}
?>

<!-- Modified error display in the HTML form -->
<?php if ($error): ?>
    <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-100 px-4 py-2 rounded animate-fade-in">
        <?php 
        if (strpos($error, 'pending activation') !== false) {
            // Display the error message with HTML formatting
            echo $error;
        } else {
            // Regular error messages
            echo htmlspecialchars($error);
        }
        ?>
    </div>
<?php endif;
 ?>

<!-- Rest of your HTML form remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to NetShield Pro - Advanced Security Suite">
    <meta name="author" content="<?php echo htmlspecialchars($currentUser); ?>">
    <meta name="last-modified" content="<?php echo htmlspecialchars($currentDate); ?>">
    <title>Login - NetShield Pro</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'netshield': {
                            900: '#1a1f35',
                            800: '#0f172a',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in',
                    },
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
    <style>
        .glass-card {
            background: rgba(17, 25, 40, 0.75);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.125);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .form-input {
            background: rgba(17, 25, 40, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.125);
            color: white;
        }
        .form-input:focus {
            border-color: #3b82f6;
            outline: none;
            ring: 1px solid #3b82f6;
        }
        .button-effect {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .button-effect:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        .button-effect:hover:before {
            width: 300px;
            height: 300px;
        }
        body {
            background: linear-gradient(135deg, #1a1f35 0%, #0f172a 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-netshield-900">
    <div class="min-h-screen flex items-center justify-center">
        <div class="glass-card p-8 w-96 space-y-6 rounded-lg">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-white mb-2">NetShield Pro</h1>
                <p class="text-gray-400">Secure your digital world</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-100 px-4 py-2 rounded animate-fade-in">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-gray-300 mb-1" for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-input w-full rounded-lg px-3 py-2"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required
                           autocomplete="email">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1" for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-input w-full rounded-lg px-3 py-2"
                           required
                           autocomplete="current-password">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center text-gray-400 hover:text-gray-300 cursor-pointer">
                        <input type="checkbox" 
                               name="remember" 
                               class="mr-2 rounded border-gray-600 text-blue-500"
                               <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <a href="/src/auth/reset-password.php" 
                       class="text-blue-400 hover:text-blue-300 transition duration-300">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" 
                        class="button-effect w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                    Sign In
                </button>
            </form>
            <div class="text-center text-gray-400">
    <p>Don't have an account? 
        <a href="/src/auth/register.php" 
           class="text-blue-400 hover:text-blue-300 transition duration-300">
            Sign up
        </a>
    </p>
</div>
            <div class="text-center text-xs text-gray-500">
                <p>System Time: <?php echo htmlspecialchars($currentDate); ?> UTC</p>
                <p class="mt-1">Last Updated: <?php echo htmlspecialchars($currentDate); ?> UTC</p>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    </script>
</body>
</html>
