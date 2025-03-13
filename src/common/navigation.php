<?php
/**
 * NetShield Pro - Navigation Component
 * Current Date: 2025-03-03 17:40:48
 * Current User: Devinbeater
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-gray-900 border-b border-gray-800" x-data="{ mobileMenuOpen: false }">
    <div class="container mx-auto px-6">
        <div class="relative flex items-center justify-between h-16">
            <!-- Mobile menu button -->
            <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" x-show="!mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" x-show="mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Desktop Navigation -->
            <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                <div class="hidden sm:block sm:ml-6">
                    <div class="flex space-x-4">
                        <a href="/dashboard" 
                           class="<?php echo $currentPage === 'dashboard.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            Dashboard
                        </a>

                        <a href="/security" 
                           class="<?php echo $currentPage === 'security.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            Security Center
                        </a>

                        <a href="/monitoring" 
                           class="<?php echo $currentPage === 'monitoring.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            Monitoring
                        </a>

                        <a href="/reports" 
                           class="<?php echo $currentPage === 'reports.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            Reports
                        </a>

                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <a href="/admin" 
                           class="<?php echo strpos($currentPage, 'admin') === 0 ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            Admin
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right side menu -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                <!-- Notifications -->
                <button class="bg-gray-800 p-1 rounded-full text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                    <span class="sr-only">View notifications</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>

                <!-- Profile dropdown -->
                <div class="ml-3 relative" x-data="{ open: false }">
                    <div>
                        <button @click="open = !open" 
                                class="bg-gray-800 flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                            <span class="sr-only">Open user menu</span>
                            <img class="h-8 w-8 rounded-full" 
                                 src="/assets/images/avatars/<?php echo htmlspecialchars($_SESSION['user']['avatar'] ?? 'default.png'); ?>" 
                                 alt="User avatar">
                        </button>
                    </div>
                    <div x-show="open" 
                         @click.away="open = false"
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-gray-800 ring-1 ring-black ring-opacity-5">
                        <a href="/profile" 
                           class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                            Your Profile
                        </a>
                        <a href="/settings" 
                           class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                            Settings
                        </a>
                        <form action="/auth/logout.php" method="POST" class="block">
                            <button type="submit" 
                                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" class="sm:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/dashboard" 
               class="<?php echo $currentPage === 'dashboard.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                Dashboard
            </a>

            <a href="/security" 
               class="<?php echo $currentPage === 'security.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                Security Center
            </a>

            <a href="/monitoring" 
               class="<?php echo $currentPage === 'monitoring.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                Monitoring
            </a>

            <a href="/reports" 
               class="<?php echo $currentPage === 'reports.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                Reports
            </a>

            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a href="/admin" 
               class="<?php echo strpos($currentPage, 'admin') === 0 ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                Admin
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>