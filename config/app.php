<?php
return [
    'app' => [
        'name' => 'NetShield Pro',
        'version' => '1.0.0',
        'environment' => 'production',
        'debug' => false,
        'timezone' => 'UTC',
        'current_user' => 'Devinbeater',
        'last_update' => '2025-03-03 15:49:44'
    ],
    'security' => [
        'session_lifetime' => 3600,
        'password_min_length' => 12,
        'require_special_chars' => true,
        'require_numbers' => true,
        'require_uppercase' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'two_factor_enabled' => true
    ],
    'scan' => [
        'max_file_size' => 104857600, // 100MB
        'allowed_file_types' => ['exe', 'dll', 'pdf', 'doc', 'docx', 'zip', 'rar'],
        'quarantine_path' => '/var/quarantine',
        'scan_timeout' => 300, // 5 minutes
        'signature_update_interval' => 86400 // 24 hours
    ]
];