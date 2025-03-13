// NetShield Pro Main JavaScript
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

const initializeApp = () => {
    // Initialize dark mode
    setupDarkMode();
    
    // Initialize real-time security monitoring
    initSecurityMonitoring();
    
    // Setup navigation and sidebar
    setupNavigation();
    
    // Initialize notifications
    initNotifications();
};

// Dark Mode Management
const setupDarkMode = () => {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('theme', 
                document.body.classList.contains('light-mode') ? 'light' : 'dark'
            );
        });
    }
    
    // Check saved preference
    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
    }
};

// Real-time Security Monitoring
const initSecurityMonitoring = () => {
    const updateSecurityStatus = async () => {
        try {
            const response = await fetch('/api/security/status');
            const data = await response.json();
            
            updateStatusIndicators(data);
            updateThreatCounter(data.threats);
            
            if (data.alerts.length > 0) {
                showSecurityAlerts(data.alerts);
            }
        } catch (error) {
            console.error('Failed to update security status:', error);
        }
    };
    
    // Update every 30 seconds
    setInterval(updateSecurityStatus, 30000);
    updateSecurityStatus();
};

// Navigation and Sidebar
const setupNavigation = () => {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar', 
                sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded'
            );
        });
    }
    
    // Add active state to current page
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
};

// Notifications System
const initNotifications = () => {
    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} glass-card`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close')
            .addEventListener('click', () => notification.remove());
    };
    
    // Expose to global scope for use in other scripts
    window.showNotification = showNotification;
};

// Update security status indicators
const updateStatusIndicators = (data) => {
    const indicators = {
        realtime: document.getElementById('realtime-protection'),
        firewall: document.getElementById('firewall-status'),
        updates: document.getElementById('updates-status')
    };
    
    for (const [key, element] of Object.entries(indicators)) {
        if (element && data[key]) {
            element.className = `status-indicator status-${data[key].status}`;
            element.setAttribute('title', data[key].message);
        }
    }
};

// Update threat counter
const updateThreatCounter = (threats) => {
    const counter = document.getElementById('threat-counter');
    if (counter) {
        counter.textContent = threats.length;
        counter.className = `threat-counter ${threats.length > 0 ? 'has-threats' : ''}`;
    }
};

// Show security alerts
const showSecurityAlerts = (alerts) => {
    alerts.forEach(alert => {
        showNotification(alert.message, alert.severity);
    });
};