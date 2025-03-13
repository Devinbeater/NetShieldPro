<?php
/**
 * NetShield Pro - Footer Component
 * Current Date: 2025-03-03 17:40:48
 * Current User: Devinbeater
 */
?>

<footer class="bg-gray-900 border-t border-gray-800">
    <div class="container mx-auto px-6 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div>
                <h3 class="text-white text-lg font-semibold mb-4">NetShield Pro</h3>
                <p class="text-gray-400 text-sm">
                    Advanced cybersecurity solutions for modern enterprises.
                    Protecting your digital assets with cutting-edge technology.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-white text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li>
                        <a href="/dashboard" class="text-gray-400 hover:text-white transition duration-300">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="/security" class="text-gray-400 hover:text-white transition duration-300">
                            Security Center
                        </a>
                    </li>
                    <li>
                        <a href="/reports" class="text-gray-400 hover:text-white transition duration-300">
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="/settings" class="text-gray-400 hover:text-white transition duration-300">
                            Settings
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h4 class="text-white text-lg font-semibold mb-4">Support</h4>
                <ul class="space-y-2">
                    <li>
                        <a href="/help" class="text-gray-400 hover:text-white transition duration-300">
                            Help Center
                        </a>
                    </li>
                    <li>
                        <a href="/documentation" class="text-gray-400 hover:text-white transition duration-300">
                            Documentation
                        </a>
                    </li>
                    <li>
                        <a href="/contact" class="text-gray-400 hover:text-white transition duration-300">
                            Contact Support
                        </a>
                    </li>
                    <li>
                        <a href="/status" class="text-gray-400 hover:text-white transition duration-300">
                            System Status
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-white text-lg font-semibold mb-4">Contact</h4>
                <ul class="space-y-2">
                    <li class="text-gray-400">
                        <span class="block">Email:</span>
                        <a href="mailto:support@netshieldpro.com" class="text-blue-400 hover:text-blue-300">
                            support@netshieldpro.com
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <span class="block">Phone:</span>
                        <a href="tel:+1-800-NET-SHIELD" class="hover:text-white">
                            1-800-NET-SHIELD
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> NetShield Pro. All rights reserved.
                </div>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="/privacy" class="text-gray-400 hover:text-white text-sm">
                        Privacy Policy
                    </a>
                    <a href="/terms" class="text-gray-400 hover:text-white text-sm">
                        Terms of Service
                    </a>
                    <a href="/security-policy" class="text-gray-400 hover:text-white text-sm">
                        Security Policy
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>