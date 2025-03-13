<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', 'C:/xampp/htdocs/src');
}

// Important: Include security-functions.php with full path
require_once __DIR__ . '/../utils/security-functions.php';
require_once __DIR__ . '/../utils/db-connect.php';

// Current timestamp and user info
define('CURRENT_TIMESTAMP', '2025-03-13 13:07:17');
define('CURRENT_USER', 'Devinbeater');

class AuthHandler {
    private $db;
    private $security;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_TIMEOUT_MINUTES = 15;
    private const PASSWORD_RESET_EXPIRY_HOURS = 1;
    private const TWO_FACTOR_EXPIRY_MINUTES = 10;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->security = new SecurityFunctions(); // Changed from Security to SecurityFunctions
        } catch (PDOException $e) {
            error_log(sprintf(
                "[%s] Database connection failed: %s",
                CURRENT_TIMESTAMP,
                $e->getMessage()
            ));
            throw new Exception("Authentication service unavailable");
        }
    }

    /**
     * Register a new user
     */
    public function register(string $username, string $email, string $password, string $confirmPassword): array {
        try {
            // Input validation
            $this->validateRegistrationInput($username, $email, $password, $confirmPassword);

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Check for existing user
                $this->checkExistingUser($username, $email);
                
                // Create user
                $userId = $this->createUser($username, $email, $password);
                
                // Initialize user settings
                $this->initializeUserSettings($userId);
                
                // Log activity
                $this->logActivity($userId, 'register', 'New user registration');
                
                $this->db->commit();
                
                return [
                    'status' => 'success',
                    'message' => 'Registration successful',
                    'userId' => $userId
                ];
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log(sprintf(
                "[%s] Registration failed for email %s: %s",
                CURRENT_TIMESTAMP,
                $email,
                $e->getMessage()
            ));
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate registration input
     */
    private function validateRegistrationInput(string $username, string $email, string $password, string $confirmPassword): void {
        // Username validation
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new Exception('Username must be between 3 and 50 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new Exception('Username can only contain letters, numbers, underscores, and hyphens');
        }

        // Email validation
        if (!$this->security->validateEmail($email)) { // Using SecurityFunctions method
            throw new Exception('Invalid email format');
        }

        // Password validation
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        // Use SecurityFunctions password strength check
        if (!$this->security->isPasswordStrong($password)) {
            throw new Exception('Password does not meet strength requirements');
        }

        // Check against common passwords
        $commonPasswords = [
            'password123',
            '12345678',
            'qwerty123',
            'letmein123',
            'admin123'
        ];

        if (in_array(strtolower($password), $commonPasswords)) {
            throw new Exception('Password is too common. Please choose a stronger password.');
        }
    }

    /**
     * Check for existing user
     */
    private function checkExistingUser(string $username, string $email): void {
        $stmt = $this->db->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['username'] === $username) {
                throw new Exception('Username already taken');
            }
            if ($existing['email'] === $email) {
                throw new Exception('Email already registered');
            }
        }
    }

    /**
     * Create new user
     */
    private function createUser(string $username, string $email, string $password): string {
        $stmt = $this->db->prepare('
            INSERT INTO users (
                username, 
                email, 
                password_hash,
                created_at,
                updated_at,
                account_status,
                role
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $username,
            $email,
            $this->security->hashPassword($password),
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP,
            'pending',
            'user'
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Initialize user settings
     */
    private function initializeUserSettings(string $userId): void {
        $defaultSettings = [
            'notifications' => true,
            'two_factor' => false,
            'theme' => 'light',
            'language' => 'en'
        ];

        $stmt = $this->db->prepare('
            INSERT INTO user_settings (
                user_id,
                settings,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?)
        ');

        $stmt->execute([
            $userId,
            json_encode($defaultSettings),
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        ]);
    }

    /**
     * Log activity
     */
    private function logActivity(string $userId, string $action, string $description): void {
        $stmt = $this->db->prepare('
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
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            CURRENT_TIMESTAMP
        ]);
    }
}