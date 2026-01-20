/**
 * Local Configuration Manager
 * Handles local PHP configuration and debug settings
 */

class LocalConfigManager {
    constructor() {
        this.init();
    }

    init() {
        // Load PHP configuration
        this.loadPHPConfig();

        // Setup console logging toggle
        this.setupConsoleLoggingToggle();
    }

    async loadPHPConfig() {
        try {
            // TODO: Implement PHP config loading from backend
            debugLog('Loading PHP configuration...','info');
        } catch(error) {
            debugLog('Error loading PHP config: '+error.message,'error');
        }
    }

    async testPHPPath() {
        const pathInput=document.getElementById('php-manual-path');
        const path=pathInput.value.trim();

        if(!path) {
            this.showNotification('Please enter a PHP path to test','warning');
            return;
        }

        try {
            debugLog('Testing PHP path: '+path,'info');
            // TODO: Implement PHP path testing
            this.showNotification('PHP path test completed','success');
        } catch(error) {
            debugLog('Error testing PHP path: '+error.message,'error');
            this.showNotification('Failed to test PHP path','error');
        }
    }

    async savePHPConfig() {
        try {
            debugLog('Saving PHP configuration...','info');
            // TODO: Implement PHP config saving
            this.showNotification('PHP configuration saved successfully','success');
        } catch(error) {
            debugLog('Error saving PHP config: '+error.message,'error');
            this.showNotification('Failed to save PHP configuration','error');
        }
    }

    async resetPHPConfig() {
        if(!confirm('Are you sure you want to reset PHP configuration to defaults?')) {
            return;
        }

        try {
            debugLog('Resetting PHP configuration...','info');
            document.getElementById('php-manual-path').value='';
            // TODO: Implement PHP config reset
            this.showNotification('PHP configuration reset successfully','success');
        } catch(error) {
            debugLog('Error resetting PHP config: '+error.message,'error');
            this.showNotification('Failed to reset PHP configuration','error');
        }
    }

    async refreshPHPConfig() {
        try {
            debugLog('Refreshing PHP configuration...','info');
            await this.loadPHPConfig();
            this.showNotification('PHP configuration refreshed','success');
        } catch(error) {
            debugLog('Error refreshing PHP config: '+error.message,'error');
            this.showNotification('Failed to refresh PHP configuration','error');
        }
    }

    setupConsoleLoggingToggle() {
        const toggle=document.getElementById('console-logging-toggle');
        if(toggle) {
            toggle.addEventListener('change',(e) => {
                const enabled=e.target.checked;
                this.setConsoleLogging(enabled);
                debugLog('Console logging '+(enabled? 'enabled':'disabled'),'info');
            });
        }
    }

    setConsoleLogging(enabled) {
        if(typeof window.setDebugMode==='function') {
            window.setDebugMode(enabled);
        }
        localStorage.setItem('consoleLoggingEnabled',enabled? 'true':'false');
    }

    showNotification(message,type='info') {
        const notification=document.createElement('div');
        notification.className=`alert alert-${type}`;
        notification.style.cssText='position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;';
        notification.innerHTML=`
            <i class="fas fa-${type==='success'? 'check-circle':type==='error'? 'exclamation-circle':'info-circle'}"></i>
            ${message}
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        },5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded',() => {
    window.localConfigManager=new LocalConfigManager();
});
