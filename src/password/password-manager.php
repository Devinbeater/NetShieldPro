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

class PasswordManager {
    private $db;
    private $encryption;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->encryption = new Encryption();
    }

    public function savePassword($data) {
        try {
            $encryptedPassword = $this->encryption->encrypt($data['password']);
            
            $stmt = $this->db->prepare('
                INSERT INTO password_vault (
                    user_id, 
                    site_name, 
                    username, 
                    encrypted_password,
                    notes
                ) VALUES (?, ?, ?, ?, ?)
            ');

            return $stmt->execute([
                $_SESSION['user']['id'],
                $data['site_name'],
                $data['username'],
                $encryptedPassword,
                $data['notes'] ?? null
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to save password: ' . $e->getMessage());
        }
    }

    public function getPasswords() {
        $stmt = $this->db->prepare('
            SELECT id, site_name, username, notes, last_updated
            FROM password_vault
            WHERE user_id = ?
            ORDER BY site_name ASC
        ');
        
        $stmt->execute([$_SESSION['user']['id']]);
        return $stmt->fetchAll();
    }

    public function getPassword($id) {
        $stmt = $this->db->prepare('
            SELECT *
            FROM password_vault
            WHERE id = ? AND user_id = ?
        ');
        
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $entry = $stmt->fetch();
        
        if ($entry) {
            $entry['password'] = $this->encryption->decrypt($entry['encrypted_password']);
            unset($entry['encrypted_password']);
        }
        
        return $entry;
    }

    public function updatePassword($id, $data) {
        try {
            $encryptedPassword = $this->encryption->encrypt($data['password']);
            
            $stmt = $this->db->prepare('
                UPDATE password_vault 
                SET site_name = ?,
                    username = ?,
                    encrypted_password = ?,
                    notes = ?
                WHERE id = ? AND user_id = ?
            ');

            return $stmt->execute([
                $data['site_name'],
                $data['username'],
                $encryptedPassword,
                $data['notes'] ?? null,
                $id,
                $_SESSION['user']['id']
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to update password: ' . $e->getMessage());
        }
    }

    public function deletePassword($id) {
        $stmt = $this->db->prepare('
            DELETE FROM password_vault
            WHERE id = ? AND user_id = ?
        ');
        
        return $stmt->execute([$id, $_SESSION['user']['id']]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Manager - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-gray-900">
    <?php include '../common/header.php'; ?>