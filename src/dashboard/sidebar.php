<?php
$currentPage = $_SERVER['PHP_SELF'];
?>

<aside class="w-64 bg-gray-800 min-h-screen" x-data="{ open: true }">
    <div class="p-6">
        <div class="flex items-center justify-between">
            <a href="/src/dashboard/index.php" class="text-white text-xl font-bold">NetShield Pro</a>
            <button @click="open = !open" class="lg:hidden text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                </svg>
            </button>
        </div>
    </div>

    <nav class="mt-2" x-show="open">
        <div class="px-4 py-2">
            <p class="text-xs uppercase text-gray-500 tracking-wider">Security</p>
            
            <a href="/src/dashboard/security-status.php" 
               class="flex items-center px-4 py-2 mt-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition duration-200
                      <?php echo $currentPage === '/dashboard/index.php' ? 'bg-gray-700 text-white' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="/src/scanner/file-scanner.php" 
               class="flex items-center px-4 py-2 mt-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition duration-200
                      <?php echo strpos($currentPage, '/scanner/') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Virus Scanner
            </a>
        </div>

        <div class="px-4 py-2 mt-4">
            <p class="text-xs uppercase text-gray-500 tracking-wider">Protection</p>
            
            <a href="/src/password/generator.php" 
               class="flex items-center px-4 py-2 mt-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition duration-200
                      <?php echo strpos($currentPage, '/password/') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Password Manager
            </a>

            <a href="/src/parental-controls/screen-time.php" 
               class="flex items-center px-4 py-2 mt-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition duration-200
                      <?php echo strpos($currentPage, '/parental-controls/') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Parental Controls
            </a>
        </div>

        <div class="px-4 py-2 mt-4">
            <p class="text-xs uppercase text-gray-500 tracking-wider">Settings</p>
            
            <a href="/src/settings/security-settings.php" 
               class="flex items-center px-4 py-2 mt-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition duration-200
                      <?php echo strpos($currentPage, '/settings/') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1