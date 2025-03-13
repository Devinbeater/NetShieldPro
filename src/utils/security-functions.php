<?php

class SecurityFunctions {
    private const HASH_ALGO = PASSWORD_ARGON2ID;
    private const ENCRYPTION_ALGO = 'aes-256-gcm';
    private const PEPPER = 'NetShieldPro_v1'; // Change in production
    private const TOKEN_LENGTH = 32;
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (PDOException $e) {
            error_log(sprintf(
                "[%s] Security initialization failed: %s",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            ));
            throw new Exception("Security service unavailable");
        }
    }

    public function hashPassword(string $password): string {
        return password_hash(
            $password . self::PEPPER,
            self::HASH_ALGO,
            ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]
        );
    }

    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password . self::PEPPER, $hash);
    }

    public function generateTwoFactorSecret(): string {
        return bin2hex(random_bytes(16));
    }

    public function encryptData(string $data, string $key): array {
        $iv = random_bytes(16);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            self::ENCRYPTION_ALGO,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return [
            'data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }

    public function decryptData(string $encryptedData, string $key, string $iv, string $tag): string {
        return openssl_decrypt(
            base64_decode($encryptedData),
            self::ENCRYPTION_ALGO,
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($iv),
            base64_decode($tag)
        );
    }

    public function generateToken(): string {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    public function validateToken(string $token, string $purpose): bool {
        $stmt = $this->db->prepare(
            "SELECT * FROM tokens WHERE token = ? AND purpose = ? AND expires_at > NOW()"
        );
        $stmt->execute([$token, $purpose]);
        return $stmt->rowCount() > 0;
    }

    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if the given password meets the strength requirements
     * 
     * @param string $password The password to validate
     * @return bool Returns true if password meets all requirements, false otherwise
     */
    public function isPasswordStrong(string $password): bool {
        // Minimum length of 12 characters
        if (strlen($password) < 12) {
            return false;
        }

        // Must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Must contain at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Must contain at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        // Check for repeated characters (more than 2 times)
        if (preg_match('/(.)\\1{2,}/', $password)) {
            return false;
        }

        return true;
    }
}