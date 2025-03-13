/**
 * NetShield Pro - Parental Controls Module
 * Current Date: 2025-03-03 17:21:25
 * Current User: Devinbeater
 */

class ParentalControls {
    constructor() {
        this.API_ENDPOINT = '/api/parental-controls';
        this.initialized = false;
        this.settings = {};
    }

    async initialize() {
        try {
            const response = await fetch(`${this.API_ENDPOINT}/settings`);
            this.settings = await response.json();
            this.initialized = true;
            this.setupEventListeners();
            this.updateUI();
        } catch (error) {
            console.error('Failed to initialize parental controls:', error);
            this.showError('Failed to load parental control settings');
        }
    }

    setupEventListeners() {
        // Screen Time Controls
        document.getElementById('screenTimeForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateScreenTimeSettings();
        });

        // Content Filtering
        document.getElementById('contentFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateContentFilters();
        });

        // App Blocking
        document.getElementById('appBlockingForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateAppBlocking();
        });

        // Schedule Controls
        document.getElementById('scheduleForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateSchedule();
        });
    }

    async updateScreenTimeSettings() {
        const limits = {
            weekday: document.getElementById('weekdayLimit').value,
            weekend: document.getElementById('weekendLimit').value,
            breakDuration: document.getElementById('breakDuration').value,
            breakInterval: document.getElementById('breakInterval').value
        };

        try {
            const response = await fetch(`${this.API_ENDPOINT}/screen-time`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(limits)
            });

            if (response.ok) {
                this.showSuccess('Screen time limits updated successfully');
                this.settings.screenTime = limits;
                this.updateUI();
            } else {
                throw new Error('Failed to update screen time limits');
            }
        } catch (error) {
            this.showError(error.message);
        }
    }

    async updateContentFilters() {
        const filters = {
            blockAdult: document.getElementById('blockAdult').checked,
            blockViolence: document.getElementById('blockViolence').checked,
            blockSocialMedia: document.getElementById('blockSocialMedia').checked,
            safeSearch: document.getElementById('safeSearch').checked,
            customBlockedWords: document.getElementById('customBlockedWords').value.split(',')
        };

        try {
            const response = await fetch(`${this.API_ENDPOINT}/content-filters`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(filters)
            });

            if (response.ok) {
                this.showSuccess('Content filters updated successfully');
                this.settings.contentFilters = filters;
            } else {
                throw new Error('Failed to update content filters');
            }
        } catch (error) {
            this.showError(error.message);
        }
    }

    async updateAppBlocking() {
        const blockedApps = Array.from(document.querySelectorAll('.app-checkbox:checked'))
            .map(checkbox => checkbox.value);

        try {
            const response = await fetch(`${this.API_ENDPOINT}/app-blocking`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ blockedApps })
            });

            if (response.ok) {
                this.showSuccess('App blocking settings updated successfully');
                this.settings.blockedApps = blockedApps;
            } else {
                throw new Error('Failed to update app blocking settings');
            }
        } catch (error) {
            this.showError(error.message);
        }
    }

    async updateSchedule() {
        const schedule = {
            bedtime: document.getElementById('bedtime').value,
            wakeTime: document.getElementById('wakeTime').value,
            homeworkTime: {
                start: document.getElementById('homeworkStart').value,
                end: document.getElementById('homeworkEnd').value
            },
            offlineDays: Array.from(document.querySelectorAll('.offline-day:checked'))
                .map(checkbox => checkbox.value)
        };

        try {
            const response = await fetch(`${this.API_ENDPOINT}/schedule`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(schedule)
            });

            if (response.ok) {
                this.showSuccess('Schedule updated successfully');
                this.settings.schedule = schedule;
            } else {
                throw new Error('Failed to update schedule');
            }
        } catch (error) {
            this.showError(error.message);
        }
    }

    updateUI() {
        if (!this.initialized) return;

        // Update screen time displays
        this.updateScreenTimeUI();
        this.updateContentFiltersUI();
        this.updateAppBlockingUI();
        this.updateScheduleUI();
    }

    showSuccess(message) {
        const notification = document.createElement('div');
        notification.className = 'notification notification-success';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    showError(message) {
        const notification = document.createElement('div');
        notification.className = 'notification notification-error';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
}

// Initialize parental controls
const parentalControls = new ParentalControls();
document.addEventListener('DOMContentLoaded', () => parentalControls.initialize());