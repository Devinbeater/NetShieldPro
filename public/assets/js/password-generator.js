/**
 * NetShield Pro - Password Generator Module
 * Current Date: 2025-03-03 17:21:25
 * Current User: Devinbeater
 */

class PasswordGenerator {
    constructor() {
        this.defaultOptions = {
            length: 16,
            includeLowercase: true,
            includeUppercase: true,
            includeNumbers: true,
            includeSymbols: true,
            excludeSimilar: false,
            excludeAmbiguous: false
        };

        this.charSets = {
            lowercase: 'abcdefghijklmnopqrstuvwxyz',
            uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            numbers: '0123456789',
            symbols: '!@#$%^&*()_+-=[]{}|;:,.<>?',
            similar: 'il1Lo0O',
            ambiguous: '{}[]()/\\\'"`~,;:.<>'
        };

        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('generatePassword')?.addEventListener('click', () => {
            this.generatePassword();
        });

        document.getElementById('copyPassword')?.addEventListener('click', () => {
            this.copyToClipboard();
        });

        document.getElementById('savePassword')?.addEventListener('click', () => {
            this.savePassword();
        });

        // Update strength meter on options change
        document.querySelectorAll('.password-option').forEach(option => {
            option.addEventListener('change', () => {
                this.updateStrengthMeter();
            });
        });
    }

    getOptions() {
        return {
            length: parseInt(document.getElementById('passwordLength').value) || this.defaultOptions.length,
            includeLowercase: document.getElementById('includeLowercase').checked,
            includeUppercase: document.getElementById('includeUppercase').checked,
            includeNumbers: document.getElementById('includeNumbers').checked,
            includeSymbols: document.getElementById('includeSymbols').checked,
            excludeSimilar: document.getElementById('excludeSimilar').checked,
            excludeAmbiguous: document.getElementById('excludeAmbiguous').checked
        };
    }

    generatePassword() {
        const options = this.getOptions();
        let charset = '';
        let password = '';

        // Build character set based on options
        if (options.includeLowercase) charset += this.charSets.lowercase;
        if (options.includeUppercase) charset += this.charSets.uppercase;
        if (options.includeNumbers) charset += this.charSets.numbers;
        if (options.includeSymbols) charset += this.charSets.symbols;

        // Remove excluded characters
        if (options.excludeSimilar) {
            charset = charset.split('')
                .filter(char => !this.charSets.similar.includes(char))
                .join('');
        }
        if (options.excludeAmbiguous) {
            charset = charset.split('')
                .filter(char => !this.charSets.ambiguous.includes(char))
                .join('');
        }

        // Generate password
        for (let i = 0; i < options.length; i++) {
            const randomIndex = crypto.getRandomValues(new Uint32Array(1))[0] % charset.length;
            password += charset[randomIndex];
        }

        // Ensure password meets minimum requirements
        if (!this.validatePassword(password, options)) {
            return this.generatePassword();
        }

        document.getElementById('passwordOutput').value = password;
        this.updateStrengthMeter(password);
    }

    validatePassword(password, options) {
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSymbol = /[!@#$%^&*()_+\-=\[\]{};:,.<>?]/.test(password);

        return (
            (!options.includeLowercase || hasLower) &&
            (!options.includeUppercase || hasUpper) &&
            (!options.includeNumbers || hasNumber) &&
            (!options.includeSymbols || hasSymbol)
        );
    }

    async copyToClipboard() {
        const password = document.getElementById('passwordOutput').value;
        try {
            await navigator.clipboard.writeText(password);
            this.showNotification('Password copied to clipboard!', 'success');
        } catch (error) {
            this.showNotification('Failed to copy password', 'error');
        }
    }

    async savePassword() {
        const password = document.getElementById('passwordOutput').value;
        const title = document.getElementById('passwordTitle').value;

        if (!title) {
            this.showNotification('Please enter a title for the password', 'error');
            return;
        }

        try {
            const response = await fetch('/api/passwords', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, password })
            });

            if (response.ok) {
                this.showNotification('Password saved successfully!', 'success');
            } else {
                throw new Error('Failed to save password');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    updateStrengthMeter(password = '') {
        const strength = this.calculatePasswordStrength(password);
        const meter = document.getElementById('strengthMeter');
        const label = document.getElementById('strengthLabel');

        meter.value = strength;
        meter.className = `strength-meter strength-${this.getStrengthLevel(strength)}`;
        label.textContent = `Password Strength: ${this.getStrengthLabel(strength)}`;
    }

    calculatePasswordStrength(password) {
        if (!password) return 0;

        let strength = 0;
        const length = password.length;

        // Length contribution (up to 40 points)
        strength += Math.min(length * 2.5, 40);

        // Character type contribution (up to 40 points)
        if (/[a-z]/.test(password)) strength += 10;
        if (/[A-Z]/.test(password)) strength += 10;
        if (/\d/.test(password)) strength += 10;
        if (/[!@#$%^&*()_+\-=\[\]{};:,.<>?]/.test(password)) strength += 10;

        // Complexity bonus (up to 20 points)
        const uniqueChars = new Set(password).size;
        strength += Math.min((uniqueChars / length) * 20, 20);

        return Math.min(strength, 100);
    }

    getStrengthLevel(strength) {
        if (strength >= 80) return 'very-strong';
        if (strength >= 60) return 'strong';
        if (strength >= 40) return 'medium';
        if (strength >= 20) return 'weak';
        return 'very-weak';
    }

    getStrengthLabel(strength) {
        if (strength >= 80) return 'Very Strong';
        if (strength >= 60) return 'Strong';
        if (strength >= 40) return 'Medium';
        if (strength >= 20) return 'Weak';
        return 'Very Weak';
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
}

// Initialize password generator
const passwordGenerator = new PasswordGenerator();