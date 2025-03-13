<?php
/**
 * Global helper functions for NetShield Pro
 * Current Date: 2025-03-03 16:32:06
 * Current User: Devinbeater
 */

if (!function_exists('formatBytes')) {
    /**
     * Format bytes to human readable format
     */
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('getCurrentDateTime')) {
    /**
     * Get current UTC datetime
     */
    function getCurrentDateTime() {
        return '2025-03-03 16:32:06';
    }
}

if (!function_exists('getCurrentUser')) {
    /**
     * Get current user's login
     */
    function getCurrentUser() {
        return 'Devinbeater';
    }
}

if (!function_exists('formatDuration')) {
    /**
     * Format seconds into human readable duration
     */
    function formatDuration($seconds) {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . 
                   ($minutes > 0 ? ' ' . $minutes . ' minute' . ($minutes != 1 ? 's' : '') : '');
        }
    }
}

if (!function_exists('generateUuid')) {
    /**
     * Generate UUID v4
     */
    function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('isJson')) {
    /**
     * Check if string is valid JSON
     */
    function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('sanitizeFileName')) {
    /**
     * Sanitize file name
     */
    function sanitizeFileName($fileName) {
        // Remove any path components
        $fileName = basename($fileName);
        
        // Replace any non-alphanumeric characters except dots and dashes
        $fileName = preg_replace('/[^a-zA-Z0-9.-]/', '_', $fileName);
        
        // Remove any leading/trailing dots or spaces
        $fileName = trim($fileName, '. ');
        
        return $fileName;
    }
}

if (!function_exists('getFileExtension')) {
    /**
     * Get file extension
     */
    function getFileExtension($fileName) {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }
}

if (!function_exists('formatTimestamp')) {
    /**
     * Format timestamp for display
     */
    function formatTimestamp($timestamp, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('getElapsedTime')) {
    /**
     * Get elapsed time in human readable format
     */
    function getElapsedTime($timestamp) {
        $seconds = strtotime(getCurrentDateTime()) - strtotime($timestamp);
        
        $intervals = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1
        ];
        
        foreach ($intervals as $name => $duration) {
            $count = floor($seconds / $duration);
            if ($count > 0) {
                return $count . ' ' . $name . ($count > 1 ? 's' : '') . ' ago';
            }
        }
        
        return 'just now';
    }
}

if (!function_exists('validateDate')) {
    /**
     * Validate date format
     */
    function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('arrayToObject')) {
    /**
     * Convert array to object recursively
     */
    function arrayToObject($array) {
        if (!is_array($array)) {
            return $array;
        }
        
        $object = new stdClass();
        foreach ($array as $key => $value) {
            $object->$key = is_array($value) ? arrayToObject($value) : $value;
        }
        
        return $object;
    }
}

if (!function_exists('getClientIp')) {
    /**
     * Get client IP address
     */
    function getClientIp() {
        $ipAddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ipAddress;
    }
}