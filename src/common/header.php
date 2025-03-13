<?php
/**
 * NetShield Pro - Header Component
 * Current Date: 2025-03-03 17:43:42
 * Current User: Devinbeater
 */

// Initialize variables
$notifications = [];
$unreadNotificationsCount = 0;

// Get user notifications if logged in
if (isset($_SESSION['user'])) {
    require_once __DIR__ . '/../utils/notifications.php';
    $notificationManager = new NotificationManager();
    $notifications = $notificationManager->getRecentNotifications($_SESSION['user']['id'], 5);
    $unreadNotificationsCount = $notificationManager->getUnreadCount($_SESSION['user']['id']);
}

// Get current theme preference
$darkMode = $_COOKIE['theme'] ?? 'dark';
?>

<!DOCTYPE html>
<html lang="en" class="<?php echo $darkMode === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="NetShield Pro - Advanced Cybersecurity Platform">
    <meta name="author" content="NetShield Security">
    
    <title><?php echo $pageTitle ?? 'NetShield Pro'; ?> | Cybersecurity Platform</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="manifest" href="/assets/site.webmanifest">
    
    <!-- Preload Critical Assets -->
    <link rel="preload" href="/assets/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/css/custom.css" as="style">
    <link rel="preload" href="/assets/js/main.js" as="script">
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
    
    <!-- Scripts -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="/assets/js/main.js" defer></script>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    
    <!-- Security Headers -->
    <?php
    header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:;");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    ?>
</head>
<body class="bg-gray-900 text-white antialiased" x-data="{ sidebarOpen: false }">
    <!-- Skip to main content -->
    <a href="#main-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded">
        Skip to main content
    </a>

    <!-- Top Navigation Bar -->
    <header class="fixed w-full top-0 z-50 bg-gray-900 border-b border-gray-800">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Left side - Logo and Toggle -->
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <a href="/" class="flex items-center space-x-3">
                        <img src="/assets/images/logo.svg" alt="NetShield Pro Logo" class="h-8 w-auto">
                        <span class="text-xl font-bold text-white">NetShield Pro</span>
                    </a>
                </div>

                <!-- Right side - User menu and notifications -->
                <?php if (isset($_SESSION['user'])): ?>
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block">
                        <div class="relative">
                            <input type="text" 
                                   placeholder="Search..."
                                   class="w-64 bg-gray-800 text-white rounded-full px-4 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="absolute left-3 top-2.5">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="p-2 rounded-full text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                            <span class="sr-only">View notifications</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <?php if ($unreadNotificationsCount > 0): ?>
                            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-gray-900"></span>
                            <?php endif; ?>
                        </button>

                        <!-- Notifications dropdown -->
                        <div x-show="open" 
                             @click.away="open = false"
                             class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-gray-800 ring-1 ring-black ring-opacity-5"
                             role="menu"
                             style="display: none;">
                            <div class="py-1">
                                <div class="px-4 py-2 border-b border-gray-700">
                                    <h3 class="text-lg font-medium">Notifications</h3>
                                </div>
                                <?php if (empty($notifications)): ?>
                                <div class="px-4 py-3 text-sm text-gray-400">
                                    No new notifications
                                </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo htmlspecialchars($notification['link']); ?>" 
                                       class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 <?php echo $notification['read'] ? '' : 'bg-gray-750'; ?>">
                                        <p class="font-medium"><?php echo htmlspecialchars($notification['title']); ?></p>
                                        <p class="text-gray-400"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($notification['created_at']); ?></p>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <a href="/notifications" class="block px-4 py-2 text-sm text-blue-400 border-t border-gray-700 hover:bg-gray-700">
                                    View all notifications
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Profile dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center space-x-3 focus:outline-none">
                            <img src="/assets/images/avatars/<?php echo htmlspecialchars($_SESSION['user']['avatar'] ?? 'default.png'); ?>"
                                 alt="Profile"
                                 class="h-8 w-8 rounded-full object-cover">
                            <span class="hidden md:block text-sm font-medium text-white">
                                <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                            </span>
                        </button>

                        <!-- Profile dropdown panel -->
                        <div x-show="open"
                             @click.away="open = false"
                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-gray-800 ring-1 ring-black ring-opacity-5"
                             role="menu"
                             style="display: none;">
                            <div class="py-1">
                                <a href="/profile" 
                                   class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                    Your Profile
                                </a>
                                <a href="/settings" 
                                   class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                    Settings
                                </a>
                                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                <a href="/admin" 
                                   class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                    Admin Panel
                                </a>
                                <?php endif; ?>
                                <div class="border-t border-gray-700"></div>
                                <form action="src/auth/logout.php" method="POST">
                                    <button type="submit"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Mobile sidebar -->
    <div x-show="sidebarOpen"
         class="fixed inset-0 z-40 lg:hidden"
         role="dialog"
         aria-modal="true">
        
        <!-- Sidebar backdrop -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75"
             @click="sidebarOpen = false"
             aria-hidden="true"></div>

        <!-- Sidebar panel -->
        <nav class="fixed inset-0 flex flex-col w-80 h-full bg-gray-900 border-r border-gray-800 overflow-y-auto">
            <?php include 'navigation.php'; ?>
        </nav>
    </div>

    <main id="main-content" class="pt-16">
        <!-- Page content will be inserted here -->