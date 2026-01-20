/**
 * Raw Configuration Manager
 * Handles viewing and importing configuration files
 */

class RawConfigManager {
    constructor() {
        this.currentFile=null;
        this.init();
    }

    init() {
        // Initialize file selector
        const fileSelect=document.getElementById('config-file-select');
        if(fileSelect) {
            fileSelect.addEventListener('change',(e) => {
                this.loadConfigFile(e.target.value);
            });
        }

        // Initialize file import
        const importInput=document.getElementById('config-import-input');
        if(importInput) {
            importInput.addEventListener('change',(e) => {
                this.handleFileImport(e.target.files[0]);
            });
        }

        // Load local config if on that tab
        this.refreshLocalConfig();
    }

    /**
     * Load and display a config file
     */
    async loadConfigFile(filename) {
        if(!filename) {
            document.getElementById('raw-config-viewer').textContent='Select a file to view its contents';
            this.updateMetadata(null);
            this.currentFile=null;
            return;
        }

        try {
            const response=await fetch(`/php/bootstrap.php?action=get_raw_config&file=${encodeURIComponent(filename)}`);
            const result=await response.json();

            if(result.success) {
                this.currentFile=filename;
                this.displayConfig(result.content,result.metadata);
            } else {
                throw new Error(result.message||'Failed to load config file');
            }
        } catch(error) {
            console.error('Error loading config file:',error);
            this.showError('Failed to load configuration file: '+error.message);
            document.getElementById('raw-config-viewer').textContent='Error loading file';
        }
    }

    /**
     * Display configuration content
     */
    displayConfig(content,metadata) {
        const viewer=document.getElementById('raw-config-viewer');
        if(viewer) {
            // Format JSON for better readability
            try {
                const jsonData=JSON.parse(content);
                viewer.textContent=JSON.stringify(jsonData,null,2);
            } catch(e) {
                // If not valid JSON, display as-is
                viewer.textContent=content;
            }
        }

        this.updateMetadata(metadata);
    }

    /**
     * Update file metadata display
     */
    updateMetadata(metadata) {
        if(!metadata) {
            document.getElementById('file-size').textContent='-';
            document.getElementById('file-lines').textContent='-';
            document.getElementById('file-modified').textContent='-';
            return;
        }

        // Format file size
        const formatSize=(bytes) => {
            if(bytes<1024) return bytes+' bytes';
            if(bytes<1024*1024) return (bytes/1024).toFixed(2)+' KB';
            return (bytes/(1024*1024)).toFixed(2)+' MB';
        };

        // Format date
        const formatDate=(timestamp) => {
            const date=new Date(timestamp*1000);
            return date.toLocaleString();
        };

        document.getElementById('file-size').textContent=formatSize(metadata.size);
        document.getElementById('file-lines').textContent=metadata.lines;
        document.getElementById('file-modified').textContent=formatDate(metadata.modified);
    }

    /**
     * Handle file import
     */
    async handleFileImport(file) {
        if(!file) return;

        // Validate file type
        if(!file.name.endsWith('.json')) {
            this.showError('Only JSON files are allowed');
            return;
        }

        // Show confirmation dialog
        const filename=file.name;
        const confirmMsg=`Are you sure you want to import "${filename}"?\n\n`+
            `This will override the existing configuration file if it exists.\n`+
            `A backup will be created automatically.`;

        if(!confirm(confirmMsg)) {
            // Reset the file input
            document.getElementById('config-import-input').value='';
            return;
        }

        try {
            // Read and validate JSON before uploading
            const content=await this.readFileContent(file);

            try {
                JSON.parse(content);
            } catch(e) {
                throw new Error('Invalid JSON format: '+e.message);
            }

            // Create form data and upload
            const formData=new FormData();
            formData.append('config_file',file);
            formData.append('action','import_config');

            const response=await fetch('/php/bootstrap.php',{
                method: 'POST',
                body: formData
            });

            const result=await response.json();

            if(result.success) {
                this.showSuccess(result.message);

                // Refresh the file list and reload if it was the current file
                await this.refreshFileList();

                if(this.currentFile===filename) {
                    this.loadConfigFile(filename);
                }
            } else {
                throw new Error(result.message||'Failed to import config file');
            }
        } catch(error) {
            console.error('Error importing config file:',error);
            this.showError('Failed to import configuration file: '+error.message);
        } finally {
            // Reset the file input
            document.getElementById('config-import-input').value='';
        }
    }

    /**
     * Read file content as text
     */
    readFileContent(file) {
        return new Promise((resolve,reject) => {
            const reader=new FileReader();
            reader.onload=(e) => resolve(e.target.result);
            reader.onerror=(e) => reject(new Error('Failed to read file'));
            reader.readAsText(file);
        });
    }

    /**
     * Refresh config file list
     */
    async refreshFileList() {
        try {
            const response=await fetch('/php/bootstrap.php?action=list_config_files');
            const result=await response.json();

            if(result.success) {
                this.updateFileList(result.files);
            } else {
                throw new Error(result.message||'Failed to load file list');
            }
        } catch(error) {
            console.error('Error refreshing file list:',error);
            this.showError('Failed to refresh file list: '+error.message);
        }
    }

    /**
     * Update the file selector dropdown
     */
    updateFileList(files) {
        const select=document.getElementById('config-file-select');
        if(!select) return;

        const currentValue=select.value;

        // Clear existing options except the first one
        select.innerHTML='<option value="">-- Choose a configuration file --</option>';

        // Add file options
        files.forEach(file => {
            const option=document.createElement('option');
            option.value=file.name;
            option.textContent=file.name;
            select.appendChild(option);
        });

        // Restore selection if it still exists
        if(currentValue&&files.some(f => f.name===currentValue)) {
            select.value=currentValue;
        }
    }

    /**
     * Copy current config to clipboard
     */
    async copyToClipboard() {
        const viewer=document.getElementById('raw-config-viewer');
        if(!viewer||!this.currentFile) {
            this.showError('No file selected');
            return;
        }

        try {
            await navigator.clipboard.writeText(viewer.textContent);
            this.showSuccess('Configuration copied to clipboard');
        } catch(error) {
            console.error('Error copying to clipboard:',error);
            this.showError('Failed to copy to clipboard');
        }
    }

    /**
     * Download current config file
     */
    downloadCurrentConfig() {
        if(!this.currentFile) {
            this.showError('No file selected');
            return;
        }

        this.downloadConfig(this.currentFile);
    }

    /**
     * Download a config file
     */
    downloadConfig(filename) {
        const url=`/php/bootstrap.php?action=get_raw_config&file=${encodeURIComponent(filename)}`;

        fetch(url)
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    const blob=new Blob([result.content],{type: 'application/json'});
                    const downloadUrl=URL.createObjectURL(blob);
                    const a=document.createElement('a');
                    a.href=downloadUrl;
                    a.download=filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(downloadUrl);
                } else {
                    throw new Error(result.message||'Failed to download file');
                }
            })
            .catch(error => {
                console.error('Error downloading file:',error);
                this.showError('Failed to download file: '+error.message);
            });
    }

    /**
     * Refresh local config display
     */
    async refreshLocalConfig() {
        const viewer=document.getElementById('local-config-viewer');
        if(!viewer) return;

        try {
            const response=await fetch('/php/bootstrap.php?action=get_raw_config&file=local-config.json');
            const result=await response.json();

            if(result.success) {
                try {
                    const jsonData=JSON.parse(result.content);
                    viewer.textContent=JSON.stringify(jsonData,null,2);
                } catch(e) {
                    viewer.textContent=result.content;
                }
            } else {
                viewer.textContent='File not found or error loading';
            }
        } catch(error) {
            console.error('Error loading local config:',error);
            viewer.textContent='Error loading local-config.json';
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // Create a temporary success notification
        const notification=document.createElement('div');
        notification.className='alert alert-success';
        notification.style.cssText='position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;';
        notification.innerHTML=`
            <i class="fas fa-check-circle"></i>
            <strong>Success:</strong> ${message}
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        },5000);
    }

    /**
     * Show error message
     */
    showError(message) {
        // Create a temporary error notification
        const notification=document.createElement('div');
        notification.className='alert alert-danger';
        notification.style.cssText='position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;';
        notification.innerHTML=`
            <i class="fas fa-exclamation-circle"></i>
            <strong>Error:</strong> ${message}
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        },5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded',() => {
    window.rawConfigManager=new RawConfigManager();
});
