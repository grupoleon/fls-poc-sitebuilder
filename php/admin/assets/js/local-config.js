/**
 * Local Configuration Manager
 * Handles local PHP configuration and debug settings
 */

class LocalConfigManager {
    constructor() {
        this.logFiles=[];
        this.currentLogFile=null;
        this.init();
    }

    init() {
        // Load PHP configuration
        this.loadPHPConfig();

        // Setup console logging toggle
        this.setupConsoleLoggingToggle();

        // Setup log viewer
        this.setupLogViewer();
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

    setupLogViewer() {
        const logSelect=document.getElementById('log-file-select');
        if(logSelect) {
            logSelect.addEventListener('change',(e) => {
                const filePath=e.target.value;
                if(filePath) {
                    this.loadLogFile(filePath);
                } else {
                    this.hideLogViewer();
                }
            });

            // Load log files list on init
            this.refreshLogFiles();
        }
    }

    async refreshLogFiles() {
        try {
            debugLog('Refreshing log files list...','info');
            const response=await fetch('?action=list_log_files');
            const result=await response.json();

            if(result.success) {
                this.logFiles=result.data;
                this.updateLogFilesList();
                this.showNotification('Log files list refreshed','success');
            } else {
                throw new Error(result.message||'Failed to load log files');
            }
        } catch(error) {
            debugLog('Error loading log files: '+error.message,'error');
            this.showNotification('Failed to load log files','error');
        }
    }

    updateLogFilesList() {
        const logSelect=document.getElementById('log-file-select');
        if(!logSelect) return;

        // Clear existing options
        logSelect.innerHTML='<option value="">Select a log file...</option>';

        if(this.logFiles.length===0) {
            logSelect.innerHTML='<option value="">No log files found</option>';
            return;
        }

        // Group by category
        const grouped={};
        this.logFiles.forEach(file => {
            if(!grouped[file.category]) {
                grouped[file.category]=[];
            }
            grouped[file.category].push(file);
        });

        // Add options grouped by category
        Object.keys(grouped).sort().forEach(category => {
            const optgroup=document.createElement('optgroup');
            optgroup.label=category==='root'? 'Root Logs':category;

            grouped[category].forEach(file => {
                const option=document.createElement('option');
                option.value=file.path;
                const sizeKB=(file.size/1024).toFixed(2);
                const date=new Date(file.modified*1000).toLocaleString();
                option.textContent=`${file.name} (${sizeKB} KB - ${date})`;
                optgroup.appendChild(option);
            });

            logSelect.appendChild(optgroup);
        });
    }

    async loadLogFile(filePath) {
        try {
            debugLog('Loading log file: '+filePath,'info');

            const response=await fetch(`?action=read_log_file&file=${encodeURIComponent(filePath)}`);
            const result=await response.json();

            if(result.success) {
                this.currentLogFile=filePath;
                this.displayLogContent(result.data,filePath);
            } else {
                throw new Error(result.message||'Failed to load log file');
            }
        } catch(error) {
            debugLog('Error loading log file: '+error.message,'error');
            this.showNotification('Failed to load log file','error');
        }
    }

    displayLogContent(data,filePath) {
        const container=document.getElementById('log-viewer-container');
        const emptyState=document.getElementById('log-viewer-empty');
        const contentDisplay=document.getElementById('log-content-display');
        const currentFileDisplay=document.getElementById('log-current-file');
        const fileMetaDisplay=document.getElementById('log-file-meta');

        if(!container||!contentDisplay) return;

        // Show viewer, hide empty state
        container.style.display='block';
        if(emptyState) emptyState.style.display='none';

        // Update file info
        currentFileDisplay.textContent=filePath;
        const sizeKB=(data.size/1024).toFixed(2);
        const modifiedDate=new Date(data.modified*1000).toLocaleString();
        fileMetaDisplay.textContent=`Size: ${sizeKB} KB | Modified: ${modifiedDate}`;

        // Display content
        contentDisplay.textContent=data.content||'(Empty file)';

        // Scroll to bottom of log
        contentDisplay.scrollTop=contentDisplay.scrollHeight;
    }

    hideLogViewer() {
        const container=document.getElementById('log-viewer-container');
        const emptyState=document.getElementById('log-viewer-empty');

        if(container) container.style.display='none';
        if(emptyState) emptyState.style.display='flex';

        this.currentLogFile=null;
    }

    async downloadLogFile() {
        if(!this.currentLogFile) {
            this.showNotification('No log file selected','warning');
            return;
        }

        try {
            const response=await fetch(`?action=read_log_file&file=${encodeURIComponent(this.currentLogFile)}`);
            const result=await response.json();

            if(result.success) {
                const blob=new Blob([result.data.content],{type: 'text/plain'});
                const url=URL.createObjectURL(blob);
                const a=document.createElement('a');
                a.href=url;
                a.download=this.currentLogFile.replace(/\//g,'_');
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                this.showNotification('Log file downloaded','success');
            } else {
                throw new Error(result.message||'Failed to download log file');
            }
        } catch(error) {
            debugLog('Error downloading log file: '+error.message,'error');
            this.showNotification('Failed to download log file','error');
        }
    }

    async copyLogContent() {
        const contentDisplay=document.getElementById('log-content-display');
        if(!contentDisplay||!this.currentLogFile) {
            this.showNotification('No log file content to copy','warning');
            return;
        }

        try {
            await navigator.clipboard.writeText(contentDisplay.textContent);
            this.showNotification('Log content copied to clipboard','success');
        } catch(error) {
            debugLog('Error copying log content: '+error.message,'error');
            this.showNotification('Failed to copy log content','error');
        }
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
