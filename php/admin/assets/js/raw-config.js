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
                this.handleFileImport(e.target.files);
            });
        }

        // Load local config if on that tab
        this.clearImportSummary();
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
    async handleFileImport(fileList) {
        const files=Array.from(fileList||[]);
        if(files.length===0) return;

        const invalidFiles=files.filter(file => !this.isSupportedImportFile(file));
        if(invalidFiles.length) {
            this.showError('Only JSON or ZIP files are allowed');
            this.renderImportSummary({
                imported: [],
                failed: invalidFiles.map(file => ({file: file.name,message: 'Unsupported file type'})),
                message: 'Import blocked due to unsupported files.'
            });
            document.getElementById('config-import-input').value='';
            return;
        }

        const jsonFiles=files.filter(file => this.isJsonFile(file));
        const zipFiles=files.filter(file => this.isZipFile(file));
        const filenames=files.map(file => file.name).join(', ');

        const confirmMsg=`Are you sure you want to import ${files.length} file(s)?\n\n`+
            `JSON files: ${jsonFiles.length}\n`+
            `ZIP files: ${zipFiles.length}\n\n`+
            `Files: ${filenames}\n\n`+
            `Existing configuration files will be overridden if names match.\n`+
            `A backup will be created automatically.`;

        if(!confirm(confirmMsg)) {
            document.getElementById('config-import-input').value='';
            return;
        }

        try {
            await this.validateJsonFiles(jsonFiles);

            const formData=new FormData();
            files.forEach(file => formData.append('config_files[]',file));
            formData.append('action','import_config');

            const response=await fetch('/php/bootstrap.php',{
                method: 'POST',
                body: formData
            });

            const result=await response.json();

            if(result.success) {
                this.showSuccess(result.message||'Configuration files imported successfully');
            } else {
                this.showError(result.message||'Failed to import configuration files');
            }

            if(Array.isArray(result.failed)&&result.failed.length) {
                const failedList=result.failed.map(item => item.file||item.name||'Unknown').join(', ');
                this.showError(`Some files failed to import: ${failedList}`);
            }

            this.renderImportSummary(result);

            await this.refreshFileList();

            const importedFiles=(result.imported||[]).map(item => item.file||item.name||item);
            if(this.currentFile&&importedFiles.includes(this.currentFile)) {
                this.loadConfigFile(this.currentFile);
            }
        } catch(error) {
            console.error('Error importing config file:',error);
            this.showError('Failed to import configuration file: '+error.message);
            this.renderImportSummary({
                imported: [],
                failed: [{file: 'Import',message: error.message}],
                message: 'Import failed.'
            });
        } finally {
            document.getElementById('config-import-input').value='';
        }
    }

    isJsonFile(file) {
        return file.name.toLowerCase().endsWith('.json');
    }

    isZipFile(file) {
        return file.name.toLowerCase().endsWith('.zip');
    }

    isSupportedImportFile(file) {
        return this.isJsonFile(file)||this.isZipFile(file);
    }

    getImportSummaryElement() {
        return document.getElementById('config-import-summary');
    }

    clearImportSummary() {
        const summaryEl=this.getImportSummaryElement();
        if(!summaryEl) return;
        summaryEl.innerHTML='';
        summaryEl.classList.add('is-hidden');
    }

    renderImportSummary(summary) {
        const summaryEl=this.getImportSummaryElement();
        if(!summaryEl) return;

        const imported=Array.isArray(summary.imported)? summary.imported:[];
        const failed=Array.isArray(summary.failed)? summary.failed:[];
        const message=summary.message? this.escapeHtml(summary.message):'';
        const importedCount=imported.length;
        const failedCount=failed.length;

        const importedHtml=importedCount? `
            <div class="import-summary-list">
                <h4>Imported Files</h4>
                <div class="import-summary-items">
                    ${imported.map(item => this.renderImportItem(item,'success')).join('')}
                </div>
            </div>
        `:'';

        const failedHtml=failedCount? `
            <div class="import-summary-list">
                <h4>Failed Files</h4>
                <div class="import-summary-items">
                    ${failed.map(item => this.renderImportItem(item,'failed')).join('')}
                </div>
            </div>
        `:'';

        summaryEl.innerHTML=`
            <div class="import-summary-header">
                <div class="import-summary-title">
                    <i class="fas fa-file-import"></i>
                    Import Summary
                </div>
                <div class="import-summary-meta">${message}</div>
            </div>
            <div class="import-summary-stats">
                <div class="import-summary-stat success">
                    <i class="fas fa-check-circle"></i>
                    Imported: ${importedCount}
                </div>
                <div class="import-summary-stat failed">
                    <i class="fas fa-times-circle"></i>
                    Failed: ${failedCount}
                </div>
            </div>
            ${importedHtml}
            ${failedHtml}
        `;

        summaryEl.classList.remove('is-hidden');
    }

    renderImportItem(item,status) {
        const fileName=this.escapeHtml(item.file||item.name||item||'Unknown');
        const source=item.source? this.escapeHtml(item.source):'';
        const message=item.message? this.escapeHtml(item.message):'';
        const sourceLine=source&&source!==fileName? `<small>Source: ${source}</small>`:'';
        const messageLine=message? `<small>${message}</small>`:'';

        return `
            <div class="import-summary-item ${status}">
                <span>${fileName}</span>
                ${sourceLine}
                ${messageLine}
            </div>
        `;
    }

    escapeHtml(value) {
        return String(value)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/\"/g,'&quot;')
            .replace(/'/g,'&#39;');
    }

    async validateJsonFiles(files) {
        if(!files.length) return;
        const validations=files.map(async file => {
            const content=await this.readFileContent(file);
            try {
                JSON.parse(content);
            } catch(e) {
                throw new Error(`Invalid JSON format in ${file.name}: ${e.message}`);
            }
        });

        await Promise.all(validations);
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
