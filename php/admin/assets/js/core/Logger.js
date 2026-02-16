/**
 * Logger - Centralized logging utility
 */
class Logger {
    constructor() {
        this.debugMode=localStorage.getItem('consoleLoggingEnabled')!=='false';
    }

    /**
     * Set debug mode
     */
    setDebugMode(enabled) {
        this.debugMode=enabled;
        localStorage.setItem('consoleLoggingEnabled',enabled? 'true':'false');
        this.info(`Console logging ${enabled? 'ENABLED':'DISABLED'}`);
    }

    /**
     * Get timestamp for logging
     */
    getTimestamp() {
        return new Date().toLocaleTimeString('en-IN',{
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    /**
     * Log message
     */
    log(msg,type='info') {
        if(!this.debugMode) return;

        const timestamp=this.getTimestamp();
        const prefix=`[${timestamp}]`;

        switch(type) {
            case 'error':
                console.error(prefix,msg);
                break;
            case 'warn':
                console.warn(prefix,msg);
                break;
            case 'info':
                console.info(prefix,msg);
                break;
            case 'debug':
            case 'log':
            default:
                console.log(prefix,msg);
                break;
        }
    }

    /**
     * Log info message
     */
    info(msg) {
        this.log(msg,'info');
    }

    /**
     * Log warning message
     */
    warn(msg) {
        this.log(msg,'warn');
    }

    /**
     * Log error message
     */
    error(msg) {
        this.log(msg,'error');
    }

    /**
     * Log debug message
     */
    debug(msg) {
        this.log(msg,'debug');
    }
}

// Create global instance
window.logger=new Logger();

// Backward compatibility helpers
window.debugLog=(msg,type='info') => window.logger.log(msg,type);
window.setDebugMode=(enabled) => window.logger.setDebugMode(enabled);
