/**
 * NetShield Pro - Security Scanner Module
 * Current Date: 2025-03-03 17:24:50
 * Current User: Devinbeater
 */

class SecurityScanner {
    constructor() {
        this.API_ENDPOINT = '/api/scanner';
        this.scanInProgress = false;
        this.scanResults = null;
        this.scanTimeout = null;
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('startScan')?.addEventListener('click', () => {
            this.startScan();
        });

        document.getElementById('stopScan')?.addEventListener('click', () => {
            this.stopScan();
        });

        document.getElementById('exportResults')?.addEventListener('click', () => {
            this.exportResults();
        });

        // File upload handler
        document.getElementById('fileUpload')?.addEventListener('change', (e) => {
            this.handleFileUpload(e.target.files);
        });

        // URL scan handler
        document.getElementById('urlScanForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.scanURL(document.getElementById('urlInput').value);
        });
    }

    async startScan() {
        if (this.scanInProgress) {
            this.showNotification('A scan is already in progress', 'warning');
            return;
        }

        const scanType = document.querySelector('input[name="scanType"]:checked').value;
        const scanOptions = {
            type: scanType,
            deepScan: document.getElementById('deepScan').checked,
            scanArchives: document.getElementById('scanArchives').checked,
            heuristicAnalysis: document.getElementById('heuristicAnalysis').checked
        };

        try {
            this.scanInProgress = true;
            this.updateUI('scanning');

            const response = await fetch(`${this.API_ENDPOINT}/start`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(scanOptions)
            });

            if (!response.ok) {
                throw new Error('Failed to start scan');
            }

            const scanId = await response.json();
            this.monitorScanProgress(scanId);
        } catch (error) {
            this.showNotification(error.message, 'error');
            this.scanInProgress = false;
            this.updateUI('idle');
        }
    }

    async stopScan() {
        if (!this.scanInProgress) return;

        try {
            const response = await fetch(`${this.API_ENDPOINT}/stop`, {
                method: 'POST'
            });

            if (!response.ok) {
                throw new Error('Failed to stop scan');
            }

            this.scanInProgress = false;
            clearTimeout(this.scanTimeout);
            this.updateUI('idle');
            this.showNotification('Scan stopped', 'info');
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    async monitorScanProgress(scanId) {
        try {
            const response = await fetch(`${this.API_ENDPOINT}/progress/${scanId}`);
            const progress = await response.json();

            this.updateProgressBar(progress.percentage);
            this.updateStatusText(progress.currentTask);

            if (progress.status === 'completed') {
                await this.handleScanCompletion(scanId);
            } else if (progress.status === 'error') {
                throw new Error(progress.error);
            } else {
                this.scanTimeout = setTimeout(() => this.monitorScanProgress(scanId), 1000);
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
            this.scanInProgress = false;
            this.updateUI('idle');
        }
    }

    async handleFileUpload(files) {
        if (!files.length) return;

        const formData = new FormData();
        Array.from(files).forEach(file => {
            formData.append('files[]', file);
        });

        try {
            const response = await fetch(`${this.API_ENDPOINT}/upload`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('File upload failed');
            }

            const result = await response.json();
            this.startFileAnalysis(result.uploadId);
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    async scanURL(url) {
        if (!url) {
            this.showNotification('Please enter a valid URL', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.API_ENDPOINT}/url-scan`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url })
            });

            if (!response.ok) {
                throw new Error('URL scan failed');
            }

            const result = await response.json();
            this.displayURLScanResults(result);
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    async exportResults() {
        if (!this.scanResults) {
            this.showNotification('No scan results to export', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.API_ENDPOINT}/export`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.scanResults)
            });

            if (!response.ok) {
                throw new Error('Failed to export results');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `scan-report-${new Date().toISOString()}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    updateUI(state) {
        const startButton = document.getElementById('startScan');
        const stopButton = document.getElementById('stopScan');
        const exportButton = document.getElementById('exportResults');
        const progressBar = document.getElementById('scanProgress');
        const statusText = document.getElementById('scanStatus');

        switch (state) {
            case 'scanning':
                startButton.disabled = true;
                stopButton.disabled = false;
                exportButton.disabled = true;
                progressBar.classList.remove('hidden');
                break;
            case 'idle':
                startButton.disabled = false;
                stopButton.disabled = true;
                exportButton.disabled = !this.scanResults;
                progressBar.classList.add('hidden');
                statusText.textContent = 'Ready to scan';
                break;
            case 'completed':
                startButton.disabled = false;
                stopButton.disabled = true;
                exportButton.disabled = false;
                progressBar.classList.add('hidden');
                statusText.textContent = 'Scan completed';
                break;
        }
    }

    updateProgressBar(percentage) {
        const progressBar = document.getElementById('scanProgress');
        progressBar.style.width = `${percentage}%`;
        progressBar.setAttribute('aria-valuenow', percentage);
    }

    updateStatusText(text) {
        const statusText = document.getElementById('scanStatus');
        statusText.textContent = text;
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    displayURLScanResults(results) {
        const resultsContainer = document.getElementById('urlScanResults');
        resultsContainer.innerHTML = '';

        const template = `
            <div class="scan-result ${results.threat_level}">
                <h3>Scan Results for ${results.url}</h3>
                <div class="threat-level">Threat Level: ${results.threat_level}</div>
                <div class="details">
                    <p>SSL Certificate: ${results.ssl_valid ? 'Valid' : 'Invalid'}</p>
                    <p>Malware Detection: ${results.malware_detected ? 'Detected' : 'None'}</p>
                    <p>Phishing Risk: ${results.phishing_score}%</p>
                </div>
                ${results.warnings.length ? `
                    <div class="warnings">
                        <h4>Warnings</h4>
                        <ul>
                            ${results.warnings.map(w => `<li>${w}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `;

        resultsContainer.innerHTML = template;
    }
}

// Initialize scanner
const securityScanner = new SecurityScanner();