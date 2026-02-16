/**
 * Utils - Common utility functions
 */
class Utils {
    /**
     * Format duration
     */
    static formatDuration(startTime,endTime=null) {
        const end=endTime||new Date();
        const start=new Date(startTime);
        const duration=end-start;

        const seconds=Math.floor(duration/1000)%60;
        const minutes=Math.floor(duration/(1000*60))%60;
        const hours=Math.floor(duration/(1000*60*60));

        if(hours>0) {
            return `${hours}h ${minutes}m ${seconds}s`;
        } else if(minutes>0) {
            return `${minutes}m ${seconds}s`;
        } else {
            return `${seconds}s`;
        }
    }

    /**
     * Get IST time
     */
    static getISTTime() {
        const now=new Date();
        const istOffset=5.5*60*60*1000;
        const istTime=new Date(now.getTime()+istOffset);

        return istTime.toLocaleString('en-IN',{
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    }

    /**
     * Debounce function
     */
    static debounce(func,wait) {
        let timeout;
        return function executedFunction(...args) {
            const later=() => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout=setTimeout(later,wait);
        };
    }

    /**
     * Throttle function
     */
    static throttle(func,limit) {
        let inThrottle;
        return function(...args) {
            if(!inThrottle) {
                func.apply(this,args);
                inThrottle=true;
                setTimeout(() => inThrottle=false,limit);
            }
        };
    }

    /**
     * Deep clone object
     */
    static deepClone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    /**
     * Escape HTML
     */
    static escapeHtml(text) {
        const div=document.createElement('div');
        div.textContent=text;
        return div.innerHTML;
    }

    /**
     * Show notification
     */
    static showNotification(message,type='info',duration=3000) {
        const notification=document.createElement('div');
        notification.className=`notification notification-${type}`;
        notification.textContent=message;
        notification.style.cssText=`
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type==='error'? '#f44336':type==='success'? '#4CAF50':'#2196F3'};
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation='slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(),300);
        },duration);
    }

    /**
     * Show loading overlay
     */
    static showLoading(message='Loading...') {
        let overlay=document.getElementById('global-loading-overlay');

        if(!overlay) {
            overlay=document.createElement('div');
            overlay.id='global-loading-overlay';
            overlay.style.cssText=`
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            overlay.innerHTML=`
                <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
                    <div class="spinner" style="margin-bottom: 15px;"></div>
                    <div id="loading-message">${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        } else {
            overlay.style.display='flex';
            const messageEl=overlay.querySelector('#loading-message');
            if(messageEl) messageEl.textContent=message;
        }
    }

    /**
     * Hide loading overlay
     */
    static hideLoading() {
        const overlay=document.getElementById('global-loading-overlay');
        if(overlay) {
            overlay.style.display='none';
        }
    }

    /**
     * Confirm dialog
     */
    static async confirm(message,title='Confirm') {
        return new Promise((resolve) => {
            if(window.confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }

    /**
     * Format file size
     */
    static formatFileSize(bytes) {
        if(bytes===0) return '0 Bytes';

        const k=1024;
        const sizes=['Bytes','KB','MB','GB'];
        const i=Math.floor(Math.log(bytes)/Math.log(k));

        return Math.round(bytes/Math.pow(k,i)*100)/100+' '+sizes[i];
    }

    /**
     * Store data in localStorage with expiry
     */
    static setWithExpiry(key,value,ttl) {
        const now=new Date();
        const item={
            value: value,
            expiry: now.getTime()+ttl
        };
        localStorage.setItem(key,JSON.stringify(item));
    }

    /**
     * Get data from localStorage with expiry check
     */
    static getWithExpiry(key) {
        const itemStr=localStorage.getItem(key);

        if(!itemStr) {
            return null;
        }

        const item=JSON.parse(itemStr);
        const now=new Date();

        if(now.getTime()>item.expiry) {
            localStorage.removeItem(key);
            return null;
        }

        return item.value;
    }

    /**
     * Sanitize string for use as ID/class
     */
    static sanitize(str) {
        return str.toLowerCase()
            .replace(/[^a-z0-9]+/g,'-')
            .replace(/^-+|-+$/g,'');
    }

    /**
     * Generate unique ID
     */
    static generateId(prefix='id') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).substr(2,9)}`;
    }
}

// Export to global scope
window.Utils=Utils;
