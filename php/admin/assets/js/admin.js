// Global debug mode flag
let DEBUG_MODE=localStorage.getItem('consoleLoggingEnabled')!=='false';

/**
 * Centralized logging function
 * All console logging should go through this function
 * @param {string|object} msg - Message to log
 * @param {string} type - Log type: 'log', 'info', 'warn', 'error'
 */
function debugLog(msg,type='info') {
    if(!DEBUG_MODE) return;

    const timestamp=new Date().toLocaleTimeString('en-IN',{
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

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
        case 'log':
        default:
            console.log(prefix,msg);
            break;
    }
}

/**
 * Set debug mode globally
 * @param {boolean} enabled - Enable or disable console logging
 */
function setDebugMode(enabled) {
    DEBUG_MODE=enabled;
    localStorage.setItem('consoleLoggingEnabled',enabled? 'true':'false');
    debugLog(`Console logging ${enabled? 'ENABLED':'DISABLED'}`,'info');
}

class AdminInterface {
    constructor() {
        this.currentTab=localStorage.getItem('activeTab')||'deployment';
        this.currentSubTab=localStorage.getItem('activeSubTab')||'';
        this.deploymentPollInterval=null;
        this.githubActionsPollInterval=null;
        this.githubActionsCompleted=false;
        this.githubActionsManuallyUpdated=false;
        this.systemRecentlyReset=false;
        this.finalStatusShown=localStorage.getItem('finalStatusShown')||null;
        this.imageUploads=new Map();
        this.currentTheme=localStorage.getItem('activeTheme')||'';
        this.currentPage=localStorage.getItem('activePage')||'';
        // Persist last read timestamp across reloads so we can resume log tailing
        this.lastLogReadTime=Number(localStorage.getItem('lastLogReadTime'))||0;
        this.siteTitleDebounceTimer=null;
        this.pendingMarkers=null;
        this.currentMap=null;
        this.mapMarkers=[];
        this.ckEditorInstances=new Map();

        // Enhanced timing tracking
        this.deploymentStartTime=null;
        this.buttonClickTime=null;
        this.stepStartTimes=new Map();
        this.stepDurations=new Map();
        this.realTimeTimers=new Map();

        // Real-time logs toggle state
        this.realtimeLogsEnabled=false;
        this.logPollInterval=null;

        // Store current ClickUp task data for prefilling
        this.currentTaskData=null;
        this.kinstaRegionFallbackOptions=null;

        this.init();
    }

    // Enhanced timing utility functions
    getISTTime() {
        const now=new Date();
        // Convert to IST (UTC+5:30)
        const istOffset=5.5*60*60*1000; // 5.5 hours in milliseconds
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

    formatDuration(startTime,endTime=null) {
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

    startStepTimer(stepId,customStartTime=null) {
        debugLog(`startStepTimer called for: ${stepId}`);
        const startTime=customStartTime||new Date();
        this.stepStartTimes.set(stepId,startTime);

        // Clear any existing timer for this step
        if(this.realTimeTimers.has(stepId)) {
            debugLog(`Clearing existing timer for: ${stepId}`);
            clearInterval(this.realTimeTimers.get(stepId));
        }

        // Start real-time timer for this step
        const timerId=setInterval(() => {
            this.updateStepDurationDisplay(stepId);
        },1000);

        this.realTimeTimers.set(stepId,timerId);
        debugLog(`Timer started for: ${stepId}, timerId: ${timerId}`);
    }

    stopStepTimer(stepId) {
        debugLog(`stopStepTimer called for: ${stepId}`);
        if(this.realTimeTimers.has(stepId)) {
            clearInterval(this.realTimeTimers.get(stepId));
            this.realTimeTimers.delete(stepId);
            debugLog(`Timer stopped and removed for: ${stepId}`);
        } else {
            debugLog(`No active timer found for: ${stepId}`);
        }

        if(this.stepStartTimes.has(stepId)) {
            const startTime=this.stepStartTimes.get(stepId);
            const endTime=new Date();
            const durationMs=endTime-startTime;
            const seconds=Math.floor(durationMs/1000)%60;
            const minutes=Math.floor(durationMs/(1000*60))%60;
            const hours=Math.floor(durationMs/(1000*60*60));

            let duration;
            if(hours>0) {
                duration=`${hours}h ${minutes}m ${seconds}s`;
            } else if(minutes>0) {
                duration=`${minutes}m ${seconds}s`;
            } else {
                duration=`${seconds}s`;
            }

            this.stepDurations.set(stepId,duration);
        }
    }

    updateStepDurationDisplay(stepId) {
        if(!this.stepStartTimes.has(stepId)) return;

        // Check if timer should still be running based on stored state
        if(!this.realTimeTimers.has(stepId)) {
            // Timer was stopped, don't continue updating
            return;
        }

        const stepCard=document.querySelector(`[data-step="${stepId}"]`);
        if(stepCard) {
            // Additional check - if step is marked as completed, stop the timer
            const stepStatus=stepCard.getAttribute('data-status');
            if(stepStatus==='completed'||stepStatus==='failed') {
                debugLog(`Step ${stepId} detected as ${stepStatus}, stopping timer`);
                this.stopStepTimer(stepId);
                return;
            }

            const timeElement=stepCard.querySelector('.status-step-time');
            if(timeElement&&timeElement.textContent!=='Waiting...'&&timeElement.textContent!=='Failed') {
                const stepStartTime=this.stepStartTimes.get(stepId);
                const now=new Date();
                const durationMs=now-stepStartTime;
                const seconds=Math.floor(durationMs/1000)%60;
                const minutes=Math.floor(durationMs/(1000*60))%60;
                const hours=Math.floor(durationMs/(1000*60*60));

                let currentDuration;
                if(hours>0) {
                    currentDuration=`${hours}h ${minutes}m ${seconds}s`;
                } else if(minutes>0) {
                    currentDuration=`${minutes}m ${seconds}s`;
                } else {
                    currentDuration=`${seconds}s`;
                }

                // Show only the step duration (independent timing)
                const baseText=timeElement.getAttribute('data-base-text')||'In Progress';
                timeElement.innerHTML=`
                    <div>${baseText}</div>
                    <div class="text-xs opacity-75">Step duration: ${currentDuration}</div>
                `;
            }
        }
    }

    // Stop timers for completed steps based on backend status
    manageStepTimersBasedOnStatus(status) {
        const deploymentSteps=[
            {id: 'create-site'},
            {id: 'get-cred'},
            {id: 'trigger-deploy'},
            {id: 'github-actions'}
        ];

        const currentStep=status.current_step||'config';
        const currentStepIndex=deploymentSteps.findIndex(s => s.id===currentStep);

        deploymentSteps.forEach((step,index) => {
            // Stop timers for completed steps (steps before current step when running, or all steps when completed)
            if(status.status==='completed'||
                (status.status==='running'&&index<currentStepIndex)||
                (status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].end_time)) {
                if(this.realTimeTimers.has(step.id)) {
                    debugLog(`Stopping timer for completed step: ${step.id}`);
                    this.stopStepTimer(step.id);
                }
            }
            // Also stop timer if step failed
            else if(step.id===currentStep&&status.status==='failed') {
                if(this.realTimeTimers.has(step.id)) {
                    debugLog(`Stopping timer for failed step: ${step.id}`);
                    this.stopStepTimer(step.id);
                }
            }
        });
    }

    // Ensure proper step timing coordination
    updateStepTimingFromBackend(status) {
        if(!status.step_timings) return;

        // Update step start times from backend data for accuracy
        Object.keys(status.step_timings).forEach(stepId => {
            const stepTiming=status.step_timings[stepId];
            if(stepTiming.start_time&&!this.stepStartTimes.has(stepId)) {
                const backendStartTime=new Date(stepTiming.start_time*1000);
                this.stepStartTimes.set(stepId,backendStartTime);
            }
        });
    }

    init() {
        this.setupEventListeners();
        this.setupTabs();
        this.setupImageUploads();
        this.setupLogoUpload();
        this.setupDeploymentMonitoring();
        this.setupDynamicComponents();
        this.loadInitialData();
        this.loadDeploymentLogs(false);
    }

    setupEventListeners() {
        // Navigation
        document.addEventListener('click',(e) => {
            debugLog('Click detected on:',e.target,'classes:',e.target.className);

            if(e.target.classList.contains('nav-link')) {
                e.preventDefault();
                this.switchTab(e.target.dataset.tab);
            }

            // Tab switching (subtabs only)
            if(e.target.classList.contains('tab-link')&&e.target.hasAttribute('data-subtab')) {
                debugLog('Subtab clicked:',e.target.dataset.subtab);
                e.preventDefault();
                this.switchSubTab(e.target.dataset.subtab);
            }

            // Content tab switching
            if(e.target.hasAttribute('data-content-tab')) {
                debugLog('Content tab clicked:',e.target,'tab:',e.target.dataset.contentTab);
                e.preventDefault();
                this.switchContentTab(e.target.dataset.contentTab);
            } else if(e.target.closest('[data-content-tab]')) {
                // Handle clicks on child elements (like icons)
                const tabElement=e.target.closest('[data-content-tab]');
                debugLog('Content tab child clicked:',e.target,'parent tab:',tabElement.dataset.contentTab);
                e.preventDefault();
                this.switchContentTab(tabElement.dataset.contentTab);
            }

            // Deployment buttons
            if(e.target.classList.contains('deploy-btn')) {
                e.preventDefault();
                this.handleDeployment(e.target.dataset.action,e.target.dataset.step);
            }

            // Form submissions
            if(e.target.classList.contains('save-config-btn')) {
                e.preventDefault();
                this.saveConfiguration(e.target.dataset.type);
            }

            // Page content save
            if(e.target.classList.contains('save-page-btn')) {
                e.preventDefault();
                this.savePageContent();
            }

            // Reset button
            if(e.target.classList.contains('reset-btn')) {
                e.preventDefault();
                this.handleReset();
            }

            // Copy logs button
            if(e.target.classList.contains('copy-logs-btn')) {
                e.preventDefault();
                this.copyLogsToClipboard();
            }

            // Edit field buttons
            if(e.target.classList.contains('edit-field-btn')||e.target.closest('.edit-field-btn')) {
                e.preventDefault();
                const button=e.target.classList.contains('edit-field-btn')? e.target:e.target.closest('.edit-field-btn');
                this.handleEditField(button);
            }

            // Dynamic list remove buttons - handle button or icon clicks
            if(e.target.classList.contains('remove-menu-item')||
                e.target.closest('.remove-menu-item')) {
                e.preventDefault();
                const button=e.target.classList.contains('remove-menu-item')? e.target:e.target.closest('.remove-menu-item');
                const menuItem=button.closest('[data-item-id]');
                if(menuItem) {
                    menuItem.remove();
                    // Mark form as dirty to indicate unsaved changes
                    const form=button.closest('form');
                    if(form) this.markFormDirty(form);
                }
            }

            // Custom collapse toggle for advanced options
            if(e.target.hasAttribute('data-bs-toggle')&&e.target.getAttribute('data-bs-toggle')==='collapse') {
                e.preventDefault();
                this.handleCollapse(e.target);
            }

            if(e.target.classList.contains('remove-keep-plugin')||
                e.target.closest('.remove-keep-plugin')) {
                e.preventDefault();
                const button=e.target.classList.contains('remove-keep-plugin')? e.target:e.target.closest('.remove-keep-plugin');
                const pluginItem=button.closest('[data-plugin-id]');
                if(pluginItem) {
                    pluginItem.remove();
                    // Mark form as dirty to indicate unsaved changes
                    const form=button.closest('form');
                    if(form) this.markFormDirty(form);
                }
            }

            if(e.target.classList.contains('remove-install-plugin')||
                e.target.closest('.remove-install-plugin')) {
                e.preventDefault();
                const button=e.target.classList.contains('remove-install-plugin')? e.target:e.target.closest('.remove-install-plugin');
                const pluginItem=button.closest('[data-plugin-id]');
                if(pluginItem) {
                    pluginItem.remove();
                    // Mark form as dirty to indicate unsaved changes
                    const form=button.closest('form');
                    if(form) this.markFormDirty(form);
                }
            }

            if(e.target.classList.contains('remove-marker')||
                e.target.closest('.remove-marker')) {
                e.preventDefault();
                const button=e.target.classList.contains('remove-marker')? e.target:e.target.closest('.remove-marker');
                const markerItem=button.closest('[data-marker-id]');
                if(markerItem) {
                    markerItem.remove();
                    // Mark form as dirty to indicate unsaved changes
                    const form=button.closest('form');
                    if(form) this.markFormDirty(form);
                }
            }

            // Generic dynamic list remove button - handle button or icon clicks
            if(e.target.classList.contains('dynamic-list-remove-btn')||
                e.target.closest('.dynamic-list-remove-btn')) {
                e.preventDefault();
                const button=e.target.classList.contains('dynamic-list-remove-btn')? e.target:e.target.closest('.dynamic-list-remove-btn');
                const listItem=button.closest('.dynamic-list-item')||
                    button.closest('[data-id]')||
                    button.closest('.list-item')||
                    button.parentElement;
                if(listItem) {
                    listItem.remove();
                    // Mark form as dirty to indicate unsaved changes
                    const form=button.closest('form');
                    if(form) this.markFormDirty(form);
                }
            }
        });

        // Form changes
        document.addEventListener('input',(e) => {
            if(e.target.classList.contains('config-input')) {
                this.markFormDirty(e.target.closest('form'));
            }

            // Site title in deployment tab
            if(e.target.id==='deployment-site-title') {
                this.handleSiteTitleChange(e);
                // Also debounce the existence check on input
                this.debouncedCheckSiteExistence(e.target.value.trim());
            }
        });

        // Change event for site title - check immediately when value changes
        document.addEventListener('change',(e) => {
            if(e.target.id==='deployment-site-title') {
                const siteTitle=e.target.value.trim();
                if(siteTitle) {
                    this.checkSiteExistence(siteTitle);
                }
            }

            // Company ID input changed - clear any previous validation state (validation removed)
            if(e.target.id==='company-id-input') {
                this.clearCompanyValidation();
                this.updateKinstaTokenLink();
                this.loadKinstaRegions(e.target.value||'');
            }
        });

        // Blur event for site title - check for conflicts when user leaves the field
        document.addEventListener('blur',(e) => {
            if(e.target.id==='deployment-site-title') {
                const siteTitle=e.target.value.trim();
                if(siteTitle) {
                    this.checkSiteExistence(siteTitle);
                }
            }
        },true);

        // Click handlers (toggle switches now handled by initializeToggleSwitches())
        document.addEventListener('click',(e) => {
            // Refresh theme list button
            if(e.target.id==='refresh-theme-list-btn'||e.target.closest('#refresh-theme-list-btn')) {
                e.preventDefault();
                this.refreshThemeList();
            }
        });

        // File uploads
        document.addEventListener('change',(e) => {
            if(e.target.type==='file'&&e.target.classList.contains('image-input')) {
                this.handleImageUpload(e.target);
            }

            // Theme selection change
            if(e.target.id==='page-theme-select') {
                const selectedTheme=e.target.value;
                if(selectedTheme) {
                    this.saveActiveTheme(selectedTheme);
                    this.loadThemePages(selectedTheme);
                }
            }

            // Page selection change  
            if(e.target.id==='page-select') {
                const selectedTheme=document.getElementById('page-theme-select').value;
                const selectedPage=e.target.value;
                if(selectedTheme&&selectedPage) {
                    this.loadPageContent(selectedTheme,selectedPage);
                }
            }

            // Deployment theme selection change - save to localStorage, backend, and update page options
            if(e.target.id==='deployment-theme-select') {
                const selectedTheme=e.target.value;
                if(selectedTheme) {
                    localStorage.setItem('deploymentTheme',selectedTheme);
                    // Save to backend to persist across sessions
                    this.saveActiveTheme(selectedTheme);
                    // Update page options for forms and maps when theme changes
                    this.updatePageOptionsForTheme(selectedTheme);
                    // Update page editor theme select to keep them in sync
                    this.updatePageThemeSelect(selectedTheme);
                } else {
                    localStorage.removeItem('deploymentTheme');
                }
            }

            // Add new content buttons
            if(e.target.classList.contains('add-issue-btn')) {
                e.preventDefault();
                this.addNewContent('issues');
            }

            if(e.target.classList.contains('add-endorsement-btn')) {
                e.preventDefault();
                this.addNewContent('endorsements');
            }

            if(e.target.classList.contains('add-news-btn')) {
                e.preventDefault();
                this.addNewContent('news');
            }

            if(e.target.classList.contains('add-post-btn')) {
                e.preventDefault();
                this.addNewContent('posts');
            }

            if(e.target.classList.contains('add-testimonial-btn')) {
                e.preventDefault();
                this.addNewContent('testimonials');
            }

            // Save all contents button
            if(e.target.classList.contains('save-contents-btn')) {
                e.preventDefault();
                this.saveAllContents();
            }

            // Manual ClickUp task fetch button
            if(e.target.id==='fetch-manual-task-btn'||e.target.closest('#fetch-manual-task-btn')) {
                e.preventDefault();
                debugLog('Fetch manual task button clicked');
                this.fetchManualTask();
            }
        });

        // Allow Enter key to trigger manual task fetch
        document.addEventListener('keypress',(e) => {
            if(e.target.id==='manual-task-id-input'&&e.key==='Enter') {
                e.preventDefault();
                debugLog('Enter key pressed on manual task input');
                this.fetchManualTask();
            }
        });
    }

    setupTabs() {
        // Remove any inline styles and let CSS classes handle visibility
        const tabContents=document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.style.display='';
            content.classList.remove('active');
        });

        // Initialize subtabs - ensure only the first one in each tab is active
        const subtabContents=document.querySelectorAll('.subtab-content');
        subtabContents.forEach(content => {
            content.classList.remove('active');
        });

        // Restore the previously active tab or use default
        this.switchTab(this.currentTab);

        // If we have a stored subtab, switch to it
        if(this.currentSubTab) {
            setTimeout(() => {
                this.switchSubTab(this.currentSubTab);
            },100);
        } else {
            // Initialize default subtabs for each main tab
            this.initializeDefaultSubtabs();
        }
    }

    initializeDefaultSubtabs() {
        // Initialize the git-config subtab as default for configuration tab
        const gitConfigTab=document.querySelector('#git-config-tab');
        if(gitConfigTab) {
            gitConfigTab.classList.add('active');
            // Only set as current subtab if we're on configuration tab
            if(this.currentTab==='configuration') {
                this.currentSubTab='git-config';
                localStorage.setItem('activeSubTab','git-config');
            }
        }
    }

    // Compact view management
    initializeCompactView() {
        const compactViewEnabled=localStorage.getItem('compactViewEnabled');
        // Default to compact view if no preference is set, or if preference is 'true'
        if(compactViewEnabled===null||compactViewEnabled==='true') {
            this.showCompactView();
        } else {
            this.hideCompactView();
        }
    }

    toggleCompactView() {
        const compactContainer=document.getElementById('deployment-status-compact');
        const regularContainer=document.getElementById('deployment-status-list');
        const toggleBtn=document.getElementById('toggle-compact-view');

        debugLog('Toggle button clicked',{compactContainer,regularContainer,toggleBtn});

        // Check computed style instead of inline style
        const compactStyle=window.getComputedStyle(compactContainer);
        const isCompactHidden=compactStyle.display==='none';

        debugLog('Current compact view state:',{compactDisplay: compactStyle.display,isCompactHidden});

        if(isCompactHidden) {
            debugLog('Showing compact view');
            this.showCompactView();
            localStorage.setItem('compactViewEnabled','true');
        } else {
            debugLog('Hiding compact view');
            this.hideCompactView();
            localStorage.setItem('compactViewEnabled','false');
        }
    }

    showCompactView() {
        const compactContainer=document.getElementById('deployment-status-compact');
        const regularContainer=document.getElementById('deployment-status-list');
        const toggleBtn=document.getElementById('toggle-compact-view');

        debugLog('showCompactView called',{compactContainer,regularContainer,toggleBtn});

        if(compactContainer&&regularContainer&&toggleBtn) {
            compactContainer.style.display='block';
            regularContainer.style.display='none';
            toggleBtn.innerHTML='<i class="fas fa-list-ul"></i>';
            toggleBtn.title='Show Detailed View';
            debugLog('Compact view shown successfully');
        } else {
            debugLog('Failed to show compact view - missing elements','error');
        }
    }

    hideCompactView() {
        const compactContainer=document.getElementById('deployment-status-compact');
        const regularContainer=document.getElementById('deployment-status-list');
        const toggleBtn=document.getElementById('toggle-compact-view');

        debugLog('hideCompactView called',{compactContainer,regularContainer,toggleBtn});

        if(compactContainer&&regularContainer&&toggleBtn) {
            compactContainer.style.display='none';
            regularContainer.style.display='block';
            toggleBtn.innerHTML='<i class="fas fa-th"></i>';
            toggleBtn.title='Show Compact View';
            debugLog('Compact view hidden successfully');
        } else {
            debugLog('Failed to hide compact view - missing elements','error');
        }
    }

    // Deployment state management - Hide section instead of overlay
    setDeploymentInProgress(inProgress) {
        debugLog(`setDeploymentInProgress called with: ${inProgress}`);

        const quickDeployCard=document.getElementById('quick-deploy-card');
        const deploymentProgressCard=document.getElementById('deployment-progress-card');
        const deploymentLogsCard=document.getElementById('deployment-logs-card');
        const progressNotice=document.getElementById('deployment-progress-notice');
        const deployBtn=document.querySelector('.deploy-btn');
        const resetBtn=document.querySelector('.reset-btn');

        if(quickDeployCard) {
            if(inProgress) {
                // Hide the entire quick deploy section
                quickDeployCard.style.display='none';
                debugLog('Hidden deployment form');
            } else {
                // Show the quick deploy section
                quickDeployCard.style.display='block';
                debugLog('Shown deployment form');
            }
        }

        // Show/hide the deployment progress card
        if(deploymentProgressCard) {
            if(inProgress) {
                // Show the deployment progress card
                deploymentProgressCard.style.display='block';
                debugLog('Shown deployment progress card');
            } else {
                // Hide the deployment progress card
                deploymentProgressCard.style.display='none';
                debugLog('Hidden deployment progress card');
            }
        }

        if(progressNotice) {
            if(inProgress) {
                // Show the progress notice
                progressNotice.classList.add('show');
            } else {
                // Hide the progress notice
                progressNotice.classList.remove('show');
            }
        }

        if(deployBtn) {
            deployBtn.disabled=inProgress;
            deployBtn.classList.toggle('btn-disabled',inProgress);
        }

        if(resetBtn) {
            resetBtn.disabled=inProgress;
            resetBtn.classList.toggle('btn-disabled',inProgress);
        }

        // Control visibility of deployment logs card
        if(deploymentLogsCard) {
            deploymentLogsCard.style.display=inProgress? 'block':'none';
            debugLog(`${inProgress? 'Shown':'Hidden'} deployment logs card`);
        }
    }

    updateCompactStepStatus(stepId,status,label=null) {
        debugLog(`updateCompactStepStatus called for ${stepId} with status ${status}`);

        const compactStep=document.querySelector(`.compact-step[data-step="${stepId}"]`);
        debugLog(`Found compact step element for ${stepId}:`,compactStep);

        if(!compactStep) {
            debugLog(`No compact step element found for stepId: ${stepId}`,'warn');
            return;
        }

        // Remove all status classes
        compactStep.classList.remove('pending','in-progress','completed','error');

        // Add new status class
        compactStep.classList.add(status);
        debugLog(`Added status class '${status}' to step ${stepId}`);

        // Update status text
        const statusElement=compactStep.querySelector('.step-status');
        if(statusElement) {
            const statusText={
                'pending': 'pending',
                'in-progress': 'running',
                'completed': 'done',
                'error': 'error'
            };
            statusElement.textContent=statusText[status]||status;
            debugLog(`Updated status text for ${stepId} to: ${statusText[status]||status}`);
        } else {
            debugLog(`No status element found in step ${stepId}`,'warn');
        }

        // Update label if provided
        if(label) {
            const labelElement=compactStep.querySelector('.step-label');
            if(labelElement) {
                labelElement.textContent=label;
                debugLog(`Updated label for ${stepId} to: ${label}`);
            } else {
                debugLog(`No label element found in step ${stepId}`,'warn');
            }
        }

        // Update connectors
        this.updateCompactConnectors();
    }

    updateDetailedStepStatus(stepId,status,label=null) {
        const detailedStep=document.querySelector(`#deployment-status-list .status-step-card[data-step="${stepId}"]`);
        if(!detailedStep) return;

        // Remove all status classes
        detailedStep.classList.remove('pending','in-progress','completed','error','running');

        // Status class mappings for detailed view
        const statusMapping={
            'pending': 'pending',
            'in-progress': 'in-progress',
            'completed': 'completed',
            'error': 'failed',
            'running': 'running'
        };

        const mappedStatus=statusMapping[status]||status;
        detailedStep.classList.add(mappedStatus);

        // Update background and border classes
        const bgClasses={
            'pending': 'bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 opacity-60',
            'in-progress': 'bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200',
            'running': 'bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200',
            'completed': 'bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200',
            'failed': 'bg-gradient-to-r from-red-50 to-pink-50 border border-red-200'
        };

        // Remove old background classes
        detailedStep.className=detailedStep.className.replace(/bg-gradient-to-r.*?rounded-xl/g,'');

        // Add new classes
        const newBgClass=bgClasses[mappedStatus]||bgClasses['pending'];
        detailedStep.className=`status-step-card ${mappedStatus} ${newBgClass} rounded-xl p-4 shadow-sm`;

        // Update icon
        const iconContainer=detailedStep.querySelector('.status-icon-large');
        const icon=detailedStep.querySelector('.status-icon-large i');

        if(iconContainer&&icon) {
            // Remove old icon classes
            iconContainer.className='status-icon-large w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg';

            const iconClasses={
                'pending': 'bg-gradient-to-r from-gray-400 to-slate-400 text-white',
                'in-progress': 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white animate-spin',
                'running': 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white animate-spin',
                'completed': 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white',
                'failed': 'bg-gradient-to-r from-red-500 to-pink-500 text-white'
            };

            iconContainer.className+=' '+(iconClasses[mappedStatus]||iconClasses['pending']);

            // Update icon based on status
            const iconTypes={
                'completed': 'fas fa-check',
                'in-progress': 'fas fa-sync-alt',
                'running': 'fas fa-sync-alt',
                'failed': 'fas fa-times'
            };

            if(iconTypes[mappedStatus]) {
                icon.className=iconTypes[mappedStatus];
            }
        }

        // Update status text and colors
        const title=detailedStep.querySelector('.status-step-title');
        const desc=detailedStep.querySelector('.status-step-desc');
        const time=detailedStep.querySelector('.status-step-time');
        const statusIndicator=detailedStep.querySelector('.status-check-mark, .status-pending-icon, .status-spinner');

        const textColors={
            'pending': {title: 'text-gray-600',desc: 'text-gray-500',time: 'text-gray-400 bg-gray-100'},
            'in-progress': {title: 'text-blue-800',desc: 'text-blue-600',time: 'text-blue-500 bg-blue-100'},
            'running': {title: 'text-blue-800',desc: 'text-blue-600',time: 'text-blue-500 bg-blue-100'},
            'completed': {title: 'text-emerald-800',desc: 'text-emerald-600',time: 'text-emerald-500 bg-emerald-100'},
            'failed': {title: 'text-red-800',desc: 'text-red-600',time: 'text-red-500 bg-red-100'}
        };

        const colors=textColors[mappedStatus]||textColors['pending'];

        if(title) {
            title.className=`status-step-title text-lg font-semibold ${colors.title} mb-1`;
            if(label) title.textContent=label;
        }

        if(desc) {
            desc.className=`status-step-desc ${colors.desc} text-sm mb-2`;
        }

        if(time) {
            time.className=`status-step-time text-xs font-mono px-2 py-1 rounded ${colors.time}`;
        }

        // Update status indicator
        if(statusIndicator) {
            const indicators={
                'pending': '<div class="status-pending-icon text-gray-400 text-xl">WAIT</div>',
                'in-progress': '<div class="status-spinner"><div class="animate-pulse bg-blue-500 w-4 h-4 rounded-full"></div></div>',
                'running': '<div class="status-spinner"><div class="animate-pulse bg-blue-500 w-4 h-4 rounded-full"></div></div>',
                'completed': '<div class="status-check-mark text-emerald-500 text-2xl">DONE</div>',
                'failed': '<div class="status-error-mark text-red-500 text-2xl">ERROR</div>'
            };

            statusIndicator.outerHTML=indicators[mappedStatus]||indicators['pending'];
        }
    }

    updateCompactConnectors() {
        const steps=document.querySelectorAll('.compact-step');
        const connectors=document.querySelectorAll('.step-connector');

        connectors.forEach((connector,index) => {
            const currentStep=steps[index];
            const nextStep=steps[index+1];

            connector.classList.remove('completed','in-progress');

            if(currentStep&&currentStep.classList.contains('completed')) {
                if(nextStep&&(nextStep.classList.contains('in-progress')||nextStep.classList.contains('completed'))) {
                    connector.classList.add('completed');
                }
            } else if(currentStep&&currentStep.classList.contains('in-progress')) {
                connector.classList.add('in-progress');
            }
        });
    }

    updateCompactViewStatus(status) {
        debugLog('updateCompactViewStatus called with status:',status);

        const deploymentSteps=[
            {id: 'create-site',name: 'Setup Kinsta'},
            {id: 'get-cred',name: 'Credentials'},
            {id: 'trigger-deploy',name: 'Deploy'},
            {id: 'github-actions',name: 'Actions'}
        ];

        const currentStep=status.current_step||'config';
        const currentStepIndex=deploymentSteps.findIndex(s => s.id===currentStep);

        debugLog('Current step:',currentStep,'Current step index:',currentStepIndex);

        // If deployment is idle, completed, or failed, handle appropriately
        if(status.status==='idle'||status.status==='completed') {
            debugLog('Deployment is not running, resetting all steps to pending');
            deploymentSteps.forEach((step) => {
                this.updateCompactStepStatus(step.id,'pending',step.name);
            });

            // Clear any GitHub Actions specific state and stop polling
            this.githubActionsManuallyUpdated=false;
            this.githubActionsCompleted=false;

            // Stop any active GitHub Actions polling
            if(this.githubActionsPollInterval) {
                clearTimeout(this.githubActionsPollInterval);
                this.githubActionsPollInterval=null;
                debugLog('Stopped GitHub Actions polling due to idle deployment');
            }

            // Update connectors after all steps are updated
            this.updateCompactConnectors();
            return;
        }

        // Handle failed deployments - show which step failed
        if(status.status==='failed'||status.status==='error') {
            debugLog('Deployment failed, showing failed step');
            deploymentSteps.forEach((step,index) => {
                let stepStatus='pending';

                if(index<currentStepIndex) {
                    stepStatus='completed';
                } else if(index===currentStepIndex) {
                    stepStatus='error';
                } else {
                    stepStatus='pending';
                }

                debugLog(`Failed deployment - Step ${step.id} (index ${index}): status = ${stepStatus}`);
                this.updateCompactStepStatus(step.id,stepStatus,step.name);
            });

            // Clear any GitHub Actions specific state and stop polling
            this.githubActionsManuallyUpdated=false;
            this.githubActionsCompleted=false;

            // Stop any active GitHub Actions polling
            if(this.githubActionsPollInterval) {
                clearTimeout(this.githubActionsPollInterval);
                this.githubActionsPollInterval=null;
                debugLog('Stopped GitHub Actions polling due to failed deployment');
            }

            // Update connectors after all steps are updated
            this.updateCompactConnectors();
            return;
        }

        deploymentSteps.forEach((step,index) => {
            let stepStatus='pending';

            if(status.status==='completed'||(status.status==='running'&&index<currentStepIndex)) {
                stepStatus='completed';
            } else if(index===currentStepIndex&&status.status==='running') {
                stepStatus='in-progress';
            } else if(status.status==='error'&&index<=currentStepIndex) {
                stepStatus='error';
            }

            debugLog(`Step ${step.id} (index ${index}): status = ${stepStatus}`);
            this.updateCompactStepStatus(step.id,stepStatus,step.name);
        });

        // Update connectors after all steps are updated
        this.updateCompactConnectors();
    }

    setupImageUploads() {
        const imageUploads=document.querySelectorAll('.image-upload');
        imageUploads.forEach(upload => {
            this.makeImageUploadClickable(upload);

            upload.addEventListener('dragover',(e) => {
                e.preventDefault();
                upload.classList.add('dragover');
            });

            upload.addEventListener('dragleave',() => {
                upload.classList.remove('dragover');
            });

            upload.addEventListener('drop',(e) => {
                e.preventDefault();
                upload.classList.remove('dragover');

                const files=e.dataTransfer.files;
                if(files.length>0) {
                    const fileInput=upload.querySelector('.file-input');
                    fileInput.files=files;
                    this.handleImageUpload(fileInput);
                }
            });
        });
    }

    makeImageUploadClickable(upload) {
        // Check if upload is valid
        if(!upload) {
            debugLog('Cannot make null upload clickable','error');
            return;
        }

        // Remove any existing click handler to avoid duplicates
        const existingHandler=upload._clickHandler;
        if(existingHandler) {
            upload.removeEventListener('click',existingHandler);
        }

        // Create a new click handler
        const clickHandler=() => {
            const fileInput=upload.querySelector('.file-input');
            if(fileInput) {
                fileInput.click();
            }
        };

        // Store the handler reference for later removal if needed
        upload._clickHandler=clickHandler;

        // Add the click handler
        upload.addEventListener('click',clickHandler);
    }

    setupDeploymentMonitoring() {
        // Check deployment status every 3 seconds when a deployment is running
        this.pollDeploymentStatus();

        // Force check GitHub Actions status after a delay to fix any stuck states
        // Only if system wasn't recently reset
        setTimeout(() => {
            if(!this.systemRecentlyReset) {
                this.checkAndFixGitHubActionsStatus();
            }
        },3000);
    }

    async checkAndFixGitHubActionsStatus() {
        try {
            debugLog('Checking and fixing GitHub Actions status...');

            // Don't check if system was recently reset
            if(this.systemRecentlyReset) {
                debugLog('System recently reset, skipping GitHub Actions check');
                return;
            }

            // Get current deployment status
            const deployResponse=await fetch('?action=deployment_status');
            const deployData=await deployResponse.json();

            if(deployData.success&&deployData.data.current_step==='github-actions') {
                debugLog('Current step is github-actions, checking GitHub status...');

                // Force check GitHub Actions status
                await this.pollGitHubActionsStatus();
            }
        } catch(error) {
            debugLog('Error checking GitHub Actions status:',error,'error');
        }
    }

    async switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        const targetContent=document.querySelector(`#${tab}-content`);
        if(targetContent) {
            targetContent.classList.add('active');
        }

        const targetLink=document.querySelector(`.nav-link[data-tab="${tab}"]`);
        if(targetLink) {
            targetLink.classList.add('active');
        }

        // Save active tab to localStorage
        this.currentTab=tab;
        localStorage.setItem('activeTab',tab);

        // Load data for specific tabs
        switch(tab) {
            case 'configuration':
                this.loadConfiguration();
                // Initialize the first subtab if no subtab is active
                setTimeout(() => {
                    if(!document.querySelector('.subtab-content.active')) {
                        this.switchSubTab('git-config');
                    }
                    // Apply task configs if available
                    if(this.currentTaskData) {
                        debugLog('Applying task configs after configuration tab load');
                        this.prefillServicesAndConfigs(this.currentTaskData);
                    }
                },500);
                break;
            case 'contents':
                this.loadOtherContents();
                break;
            case 'pages':
                await this.loadThemes();
                this.loadPageEditor();
                break;
            case 'deployment':
                await this.loadThemes();
                this.loadDeploymentStatus();
                // Force check GitHub Actions status once after loading deployment tab
                setTimeout(() => {
                    debugLog('🔍 Force checking GitHub Actions status on deployment tab load...');
                    this.pollGitHubActionsStatus();
                },2000);
                break;
        }
    }

    switchSubTab(subtabName) {
        // Find the currently active tab content
        const parentTab=document.querySelector('.tab-content.active');

        if(!parentTab) {
            debugLog('No active parent tab found','warn');
            return;
        }

        // Update subtab links
        const tabLinks=parentTab.querySelectorAll('.tab-link');
        tabLinks.forEach(link => {
            link.classList.remove('active');
        });

        const targetLink=parentTab.querySelector(`[data-subtab="${subtabName}"]`);
        if(targetLink) {
            targetLink.classList.add('active');
        }

        // Update subtab content
        const subtabContents=parentTab.querySelectorAll('.subtab-content');
        subtabContents.forEach(content => {
            content.classList.remove('active');
        });

        const targetContent=parentTab.querySelector(`#${subtabName}-tab`);
        if(targetContent) {
            targetContent.classList.add('active');
        }

        // Save active subtab to localStorage
        this.currentSubTab=subtabName;
        localStorage.setItem('activeSubTab',subtabName);

        // Check if we're switching to integrations tab and have pending markers
        if(subtabName==='integrations-config'&&this.pendingMarkers) {
            debugLog('Integrations tab activated, loading pending markers');
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                this.loadMapMarkers(this.pendingMarkers);
            },100);
        }
    }

    async loadDashboard() {
        try {
            const response=await fetch('?action=dashboard');
            const data=await response.json();

            if(data.success) {
                this.updateDashboardData(data.data);
            }
        } catch(error) {
            debugLog('Failed to load dashboard:',error,'error');
        }
    }

    updateDashboardData(data) {
        // Update system status
        const statusContainer=document.getElementById('system-status');
        if(statusContainer&&data.system_status) {
            statusContainer.innerHTML=this.renderSystemStatus(data.system_status);
        }

        // Update configuration summary
        const configSummary=document.getElementById('config-summary');
        if(configSummary&&data.config_summary) {
            configSummary.innerHTML=this.renderConfigSummary(data.config_summary);
        }

        // Update deployment status
        const deploymentStatus=document.getElementById('deployment-status');
        if(deploymentStatus&&data.deployment_status) {
            deploymentStatus.innerHTML=this.renderDeploymentStatus(data.deployment_status);
        }
    }

    renderSystemStatus(status) {
        return `
            <div class="grid grid-cols-2">
                <div class="status-item">
                    <span class="font-medium">Configuration</span>
                    <span class="status ${status.configs_valid? 'status-success':'status-error'}">
                        ${status.configs_valid? 'Valid':'Issues Found'}
                    </span>
                </div>
                <div class="status-item">
                    <span class="font-medium">Scripts</span>
                    <span class="status ${status.scripts_ready? 'status-success':'status-error'}">
                        ${status.scripts_ready? 'Ready':'Issues Found'}
                    </span>
                </div>
                <div class="status-item">
                    <span class="font-medium">Uploads</span>
                    <span class="status status-info">
                        ${status.image_count} images
                    </span>
                </div>
                <div class="status-item">
                    <span class="font-medium">Last Deployment</span>
                    <span class="status ${status.last_deployment_status==='completed'? 'status-success':'status-warning'}">
                        ${status.last_deployment_time||'Never'}
                    </span>
                </div>
            </div>
        `;
    }

    renderConfigSummary(config) {
        return `
            <div class="config-summary-grid">
                <div class="config-item">
                    <label>Active Theme</label>
                    <span class="font-semibold">${config.active_theme}</span>
                </div>
                <div class="config-item">
                    <label>Site Title</label>
                    <span class="font-semibold">${config.site_title}</span>
                </div>
                <div class="config-item">
                    <label>Repository</label>
                    <span class="font-semibold">${config.repo}</span>
                </div>
                <div class="config-item">
                    <label>Admin Email</label>
                    <span class="font-semibold">${config.admin_email}</span>
                </div>
            </div>
        `;
    }

    renderDeploymentStatus(status) {
        const statusClass=status.status==='running'? 'status-warning':
            status.status==='completed'? 'status-success':'status-info';

        return `
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">Deployment Status</div>
                    <div class="text-sm text-secondary">${status.message||'No active deployment'}</div>
                </div>
                <div class="status ${statusClass}">
                    ${status.status||'idle'}
                </div>
            </div>
        `;
    }

    async loadConfiguration() {
        try {
            const response=await fetch('?action=get_configs');
            const data=await response.json();

            if(data.success) {
                this.currentConfig=data.data; // Store config for later use
                this.populateConfigForms(data.data);
                await this.loadKinstaRegions();
                // Load page options for forms and maps dropdowns
                await this.loadPageOptionsForForms();
                // Load Git data from GitHub (orgs, repos, branches)
                await this.loadGitOrganizations();
            }
        } catch(error) {
            debugLog('Failed to load configuration:',error,'error');
        }
    }

    async loadGitOrganizations() {
        const orgSelect=document.getElementById('git-org-select');
        if(!orgSelect) return;

        const gitConfig=this.currentConfig?.git;
        if(!gitConfig||!gitConfig.token) {
            orgSelect.innerHTML='<option value="">Configure GitHub token first</option>';
            // Show saved value even without token
            if(gitConfig?.org) {
                orgSelect.innerHTML+=`<option value="${gitConfig.org}" selected>${gitConfig.org}</option>`;
            }
            return;
        }

        try {
            // Show saved value immediately before fetching from GitHub
            if(gitConfig.org) {
                orgSelect.innerHTML=`<option value="${gitConfig.org}" selected>${gitConfig.org} (Loading...)</option>`;
            } else {
                orgSelect.innerHTML='<option value="">Loading organizations...</option>';
            }

            // Fetch user info to get username
            const userResponse=await fetch('https://api.github.com/user',{
                headers: {
                    'Authorization': `Bearer ${gitConfig.token}`,
                    'Accept': 'application/vnd.github.v3+json'
                }
            });

            if(!userResponse.ok) {
                throw new Error(`GitHub API error: ${userResponse.status}`);
            }

            const user=await userResponse.json();

            // Fetch organizations
            const orgsResponse=await fetch('https://api.github.com/user/orgs',{
                headers: {
                    'Authorization': `Bearer ${gitConfig.token}`,
                    'Accept': 'application/vnd.github.v3+json'
                }
            });

            if(!orgsResponse.ok) {
                throw new Error(`GitHub API error: ${orgsResponse.status}`);
            }

            const orgs=await orgsResponse.json();
            const currentOrg=gitConfig.org||'';

            // Build options: user account + organizations
            const options=[
                {login: user.login,type: 'User'},
                ...orgs.map(org => ({login: org.login,type: 'Organization'}))
            ];

            orgSelect.innerHTML=options.map(item =>
                `<option value="${item.login}" data-type="${item.type}" ${item.login===currentOrg? 'selected':''}>
                    ${item.login} ${item.type==='User'? '(Personal)':'(Org)'}
                </option>`
            ).join('');

            debugLog(`Loaded ${options.length} organizations from GitHub`);

            // Load repos for current org and set up change listener
            if(currentOrg) {
                await this.loadGitRepositories();
            }

            // Add change listener to load repos when org changes
            orgSelect.removeEventListener('change',this.handleOrgChange);
            this.handleOrgChange=async () => {
                await this.loadGitRepositories();
                // Clear branch selection when org changes
                const branchSelect=document.getElementById('git-branch-select');
                if(branchSelect) {
                    branchSelect.innerHTML='<option value="">Select repository first...</option>';
                }
            };
            orgSelect.addEventListener('change',this.handleOrgChange);

        } catch(error) {
            debugLog('Failed to load Git organizations:',error,'error');
            orgSelect.innerHTML='<option value="">Failed to load organizations</option>';
            // Add manual entry option
            if(gitConfig.org) {
                orgSelect.innerHTML+=`<option value="${gitConfig.org}" selected>${gitConfig.org}</option>`;
                // Try to load repos and branches even if org fetch failed
                await this.loadGitRepositories();
            }
        }
    }

    getKinstaRegionSelect() {
        return document.querySelector('select[data-path="region"]');
    }

    cacheKinstaRegionFallbackOptions() {
        if(this.kinstaRegionFallbackOptions) return;
        const regionSelect=this.getKinstaRegionSelect();
        if(!regionSelect) return;

        this.kinstaRegionFallbackOptions=Array.from(regionSelect.options)
            .filter(option => option.value!=='')
            .map(option => ({
                value: option.value,
                label: option.textContent
            }));
    }

    populateKinstaRegionSelect(regions,selectedValue,placeholderText=null) {
        const regionSelect=this.getKinstaRegionSelect();
        if(!regionSelect) return;

        regionSelect.innerHTML='';

        if(placeholderText) {
            const placeholder=document.createElement('option');
            placeholder.value='';
            placeholder.textContent=placeholderText;
            placeholder.disabled=true;
            placeholder.selected=!selectedValue;
            regionSelect.appendChild(placeholder);
        }

        if(Array.isArray(regions)&&regions.length) {
            regions.forEach(region => {
                const value=region?.value||region?.region||region?.id||region?.code||region?.name||'';
                const label=region?.label||region?.name||region?.location||value;
                if(!value) return;
                const option=document.createElement('option');
                option.value=value;
                option.textContent=label;
                regionSelect.appendChild(option);
            });
        }

        if(selectedValue&&!Array.from(regionSelect.options).some(option => option.value===selectedValue)) {
            const option=document.createElement('option');
            option.value=selectedValue;
            option.textContent=`${selectedValue} (Unavailable)`;
            regionSelect.appendChild(option);
        }

        if(selectedValue) {
            regionSelect.value=selectedValue;
        }
    }

    async loadKinstaRegions(companyId=null) {
        const regionSelect=this.getKinstaRegionSelect();
        if(!regionSelect) return;

        this.cacheKinstaRegionFallbackOptions();

        const selectedValue=regionSelect.value||
            this.currentConfig?.site?.region||
            this.currentConfig?.main?.region||
            this.currentConfig?.config?.region||
            '';

        const resolvedCompanyId=(companyId&&companyId.trim())||
            document.getElementById('company-id-input')?.value?.trim()||
            this.currentConfig?.site?.company||
            this.currentConfig?.main?.company||
            this.currentConfig?.config?.company||
            '';

        if(!resolvedCompanyId) {
            this.populateKinstaRegionSelect(this.kinstaRegionFallbackOptions,selectedValue,'Enter Company ID to load regions');
            return;
        }

        regionSelect.disabled=true;
        this.populateKinstaRegionSelect([],'','Loading regions...');

        try {
            const response=await fetch(`?action=get_available_regions&company_id=${encodeURIComponent(resolvedCompanyId)}`);
            const result=await response.json();

            if(!result.success) {
                throw new Error(result.message||'Failed to load regions');
            }

            const regions=result.data?.regions||[];
            if(!Array.isArray(regions)||regions.length===0) {
                this.populateKinstaRegionSelect(this.kinstaRegionFallbackOptions,selectedValue,'No regions available');
                return;
            }

            this.populateKinstaRegionSelect(regions,selectedValue);
        } catch(error) {
            debugLog(`Failed to load Kinsta regions: ${error.message}`,'error');
            this.populateKinstaRegionSelect(this.kinstaRegionFallbackOptions,selectedValue,'Unable to load regions');
        } finally {
            regionSelect.disabled=false;
        }
    }

    async loadGitRepositories() {
        const repoSelect=document.getElementById('git-repo-select');
        const orgSelect=document.getElementById('git-org-select');
        if(!repoSelect||!orgSelect) return;

        const selectedOrg=orgSelect.value;
        if(!selectedOrg) {
            repoSelect.innerHTML='<option value="">Select organization first...</option>';
            // Show saved value even without org selection
            const gitConfig=this.currentConfig?.git;
            if(gitConfig?.repo) {
                repoSelect.innerHTML+=`<option value="${gitConfig.repo}" selected>${gitConfig.repo}</option>`;
            }
            return;
        }

        const gitConfig=this.currentConfig?.git;
        if(!gitConfig||!gitConfig.token) {
            repoSelect.innerHTML='<option value="">Configure GitHub token first</option>';
            // Show saved value even without token
            if(gitConfig?.repo) {
                repoSelect.innerHTML+=`<option value="${gitConfig.repo}" selected>${gitConfig.repo}</option>`;
            }
            return;
        }

        try {
            // Show saved value immediately before fetching from GitHub
            if(gitConfig.repo) {
                repoSelect.innerHTML=`<option value="${gitConfig.repo}" selected>${gitConfig.repo} (Loading...)</option>`;
            } else {
                repoSelect.innerHTML='<option value="">Loading repositories...</option>';
            }

            // Check if selected org is a User or Organization
            const selectedOption=orgSelect.options[orgSelect.selectedIndex];
            const orgType=selectedOption?.getAttribute('data-type')||'Organization';

            // Use appropriate endpoint based on type
            let apiUrl;
            let isAuthenticatedUser=false;

            if(orgType==='User') {
                // Personal account - use /user/repos (authenticated user) which includes private repos
                apiUrl=`https://api.github.com/user/repos?per_page=100&sort=updated&affiliation=owner`;
                isAuthenticatedUser=true;
            } else {
                // Organization - use /orgs/{org}/repos
                apiUrl=`https://api.github.com/orgs/${selectedOrg}/repos?per_page=100&sort=updated`;
            }

            debugLog(`Fetching repos for ${selectedOrg} (${orgType}) from: ${apiUrl}`);

            const response=await fetch(apiUrl,{
                headers: {
                    'Authorization': `Bearer ${gitConfig.token}`,
                    'Accept': 'application/vnd.github.v3+json'
                }
            });

            if(!response.ok) {
                throw new Error(`GitHub API error: ${response.status}`);
            }

            const repos=await response.json();
            const currentRepo=gitConfig.repo||'';

            if(repos.length===0) {
                repoSelect.innerHTML='<option value="">No repositories found</option>';
                return;
            }

            repoSelect.innerHTML=repos.map(repo =>
                `<option value="${repo.name}" ${repo.name===currentRepo? 'selected':''}>
                    ${repo.name}${repo.private? ' 🔒':''}
                </option>`
            ).join('');

            debugLog(`Loaded ${repos.length} repositories for ${selectedOrg}`);

            // Add change listener to load branches when repo changes
            repoSelect.removeEventListener('change',this.handleRepoChange);
            this.handleRepoChange=async () => {
                await this.loadGitBranches();
            };
            repoSelect.addEventListener('change',this.handleRepoChange);

            // Manually trigger change event to load branches for the currently selected repo
            // This is needed because programmatically setting innerHTML doesn't trigger change event
            const selectedRepoValue=repoSelect.value;
            if(selectedRepoValue) {
                debugLog(`Triggering branch load for selected repo: ${selectedRepoValue}`);
                // Dispatch change event to trigger the handler
                repoSelect.dispatchEvent(new Event('change'));
            }

        } catch(error) {
            debugLog('Failed to load Git repositories:',error,'error');
            repoSelect.innerHTML='<option value="">Failed to load repositories</option>';
            // Add manual entry option
            if(gitConfig.repo) {
                repoSelect.innerHTML+=`<option value="${gitConfig.repo}" selected>${gitConfig.repo}</option>`;
                // Trigger branches load even on error if repo is configured
                await this.loadGitBranches();
            }
        }
    }

    async loadGitBranches() {
        const branchSelect=document.getElementById('git-branch-select');
        const orgSelect=document.getElementById('git-org-select');
        const repoSelect=document.getElementById('git-repo-select');
        if(!branchSelect) return;

        // Get current selections from dropdowns (or fallback to config)
        const gitConfig=this.currentConfig?.git;
        const org=orgSelect?.value||gitConfig?.org;
        const repo=repoSelect?.value||gitConfig?.repo;
        const token=gitConfig?.token;

        if(!org||!repo) {
            branchSelect.innerHTML='<option value="">Select org and repo first</option>';
            // Show saved value even without org/repo selection
            if(gitConfig?.branch) {
                branchSelect.innerHTML+=`<option value="${gitConfig.branch}" selected>${gitConfig.branch}</option>`;
            }
            return;
        }

        if(!token) {
            branchSelect.innerHTML='<option value="">Configure GitHub token first</option>';
            // Show saved value even without token
            if(gitConfig?.branch) {
                branchSelect.innerHTML+=`<option value="${gitConfig.branch}" selected>${gitConfig.branch}</option>`;
            }
            return;
        }

        try {
            // Show saved value immediately before fetching from GitHub
            if(gitConfig?.branch) {
                branchSelect.innerHTML=`<option value="${gitConfig.branch}" selected>${gitConfig.branch} (Loading...)</option>`;
            } else {
                branchSelect.innerHTML='<option value="">Loading branches...</option>';
            }

            const response=await fetch(`https://api.github.com/repos/${org}/${repo}/branches`,{
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/vnd.github.v3+json'
                }
            });

            if(!response.ok) {
                throw new Error(`GitHub API error: ${response.status}`);
            }

            const branches=await response.json();
            const currentBranch=gitConfig?.branch||'content_automation';

            // Populate dropdown with branches
            branchSelect.innerHTML=branches.map(branch =>
                `<option value="${branch.name}" ${branch.name===currentBranch? 'selected':''}>
                    ${branch.name}
                </option>`
            ).join('');

            debugLog(`Loaded ${branches.length} branches from GitHub`);
        } catch(error) {
            debugLog('Failed to load Git branches:',error,'error');
            branchSelect.innerHTML=`
                <option value="">Failed to load branches</option>
                <option value="content_automation">content_automation (default)</option>
                <option value="main">main</option>
                <option value="master">master</option>
            `;
            // Select current branch if available
            if(gitConfig?.branch) {
                const option=Array.from(branchSelect.options).find(opt => opt.value===gitConfig.branch);
                if(!option) {
                    branchSelect.innerHTML+=`<option value="${gitConfig.branch}" selected>${gitConfig.branch}</option>`;
                } else {
                    branchSelect.value=gitConfig.branch;
                }
            }
        }
    }

    async refreshGitOrgs() {
        const orgSelect=document.getElementById('git-org-select');
        if(!orgSelect) return;

        const currentValue=orgSelect.value;
        orgSelect.innerHTML='<option value="">Refreshing organizations...</option>';

        // Reload configuration to get latest git settings
        try {
            const response=await fetch('?action=get_configs');
            const data=await response.json();
            if(data.success) {
                this.currentConfig=data.data;
            }
        } catch(error) {
            debugLog('Failed to reload config:',error,'error');
        }

        // Load orgs from GitHub
        await this.loadGitOrganizations();

        // Try to restore previous selection if it still exists
        if(currentValue&&Array.from(orgSelect.options).some(opt => opt.value===currentValue)) {
            orgSelect.value=currentValue;
        }
    }

    async refreshGitRepos() {
        const repoSelect=document.getElementById('git-repo-select');
        if(!repoSelect) return;

        const currentValue=repoSelect.value;
        repoSelect.innerHTML='<option value="">Refreshing repositories...</option>';

        // Load repos from GitHub
        await this.loadGitRepositories();

        // Try to restore previous selection if it still exists
        if(currentValue&&Array.from(repoSelect.options).some(opt => opt.value===currentValue)) {
            repoSelect.value=currentValue;
        }
    }

    async refreshGitBranches() {
        // Show loading indicator
        const branchSelect=document.getElementById('git-branch-select');
        if(!branchSelect) return;

        const currentValue=branchSelect.value;
        branchSelect.innerHTML='<option value="">Refreshing branches...</option>';

        // Reload configuration to get latest git settings
        try {
            const response=await fetch('?action=get_configs');
            const data=await response.json();
            if(data.success) {
                this.currentConfig=data.data;
            }
        } catch(error) {
            debugLog('Failed to reload config:',error,'error');
        }

        // Load branches from GitHub
        await this.loadGitBranches();

        // Try to restore previous selection if it still exists
        if(currentValue&&Array.from(branchSelect.options).some(opt => opt.value===currentValue)) {
            branchSelect.value=currentValue;
        }
    }

    async loadThemes() {
        try {
            debugLog('Loading themes...');
            const response=await fetch('?action=get_themes');
            const data=await response.json();
            debugLog('Themes API response:',data);

            if(data.success) {
                await this.populateThemeSelect(data.data);
            } else {
                debugLog('Theme loading failed:',data.message,'error');
            }
        } catch(error) {
            debugLog('Failed to load themes:',error,'error');
        }
    }

    async loadClickUpTasks() {
        try {
            debugLog('Loading ClickUp tasks...');
            const response=await fetch('/php/api/clickup-tasks.php?action=list');
            const data=await response.json();
            debugLog('ClickUp tasks API response:',data);

            if(data.success&&data.tasks) {
                this.populateClickUpTasksSelect(data.tasks);
            } else {
                debugLog('No ClickUp tasks found or API error');
            }
        } catch(error) {
            debugLog('Failed to load ClickUp tasks:',error,'error');
        }
    }

    populateClickUpTasksSelect(tasks) {
        const taskSelect=document.getElementById('clickup-task-select');
        if(!taskSelect) return;

        // Keep the default option
        taskSelect.innerHTML='<option value="">-- Select a ClickUp task to prefill --</option>';

        if(tasks&&tasks.length>0) {
            tasks.forEach(task => {
                const option=document.createElement('option');
                option.value=task.task_id;
                option.textContent=`${task.task_name} ${task.website_url? '('+task.website_url+')':''}`;
                taskSelect.appendChild(option);
            });

            // Add change event listener
            taskSelect.addEventListener('change',async (e) => {
                const taskId=e.target.value;
                if(taskId) {
                    await this.loadTaskDataAndPrefill(taskId);
                }
            });
        }
    }

    async loadTaskDataAndPrefill(taskId) {
        try {
            debugLog(`Loading task data for: ${taskId}`);
            const response=await fetch(`/php/api/clickup-tasks.php?action=get&id=${taskId}`);
            const data=await response.json();
            console.log('Raw API Response:',JSON.stringify(data,null,2));

            if(data.success&&data.task) {
                console.log('Task object:',JSON.stringify(data.task,null,2));
                this.prefillDeploymentForm(data.task);
            } else {
                debugLog('Failed to load task data - API returned:',JSON.stringify(data),'error');
            }
        } catch(error) {
            debugLog('Failed to load task data:',error,'error');
        }
    }

    prefillDeploymentForm(taskData) {
        console.log('=== PREFILLING FORM ===');
        console.log('Full task data received:',JSON.stringify(taskData,null,2));

        // Store task data for later use
        this.currentTaskData=taskData;

        // Prefill site title with task_name
        const siteTitleInput=document.getElementById('deployment-site-title');
        console.log('Site title input element exists:',!!siteTitleInput);
        console.log('task_name value:',taskData.task_name);
        console.log('typeof task_name:',typeof taskData.task_name);

        if(siteTitleInput&&taskData.task_name) {
            siteTitleInput.value=taskData.task_name;
            console.log('✅ Site title set to:',siteTitleInput.value);

            // Watch for any changes to the input value
            const observer=new MutationObserver(() => {
                if(siteTitleInput.value!==taskData.task_name) {
                    console.warn('⚠️ Site title was changed from:',taskData.task_name,'to:',siteTitleInput.value);
                    console.trace('Stack trace of who changed it:');
                }
            });
            observer.observe(siteTitleInput,{
                attributes: true,
                attributeFilter: ['value']
            });

            // Also watch for direct value changes
            const originalValue=siteTitleInput.value;
            setTimeout(() => {
                if(siteTitleInput.value!==originalValue) {
                    console.warn('⚠️ Site title was cleared/changed after 50ms. Original:',originalValue,'Current:',siteTitleInput.value);
                }
            },50);
            setTimeout(() => {
                if(siteTitleInput.value!==originalValue) {
                    console.warn('⚠️ Site title was cleared/changed after 200ms. Original:',originalValue,'Current:',siteTitleInput.value);
                }
            },200);

            // Trigger site existence check
            this.checkSiteExistence(taskData.task_name);
        } else {
            console.error('❌ Could not set site title:',{
                hasInput: !!siteTitleInput,
                hasTaskName: !!taskData.task_name,
                taskNameValue: taskData.task_name
            });
        }

        // Prefill theme
        const themeSelect=document.getElementById('deployment-theme-select');
        if(themeSelect&&taskData.theme) {
            // Map theme names (handle variations)
            const themeMapping={
                'Political WP': 'Political',
                'Candidates': 'Candidate',
                // Add more mappings as needed
            };
            const themeName=themeMapping[taskData.theme]||taskData.theme;

            // Try to select the theme
            const option=Array.from(themeSelect.options).find(opt => opt.value===themeName);
            if(option) {
                themeSelect.value=themeName;
                localStorage.setItem('deploymentTheme',themeName);
                this.saveActiveTheme(themeName);
                this.updatePageOptionsForTheme(themeName);
                this.updatePageThemeSelect(themeName);

                // Re-apply site title after theme operations (they may have cleared it)
                setTimeout(() => {
                    if(siteTitleInput&&taskData.task_name) {
                        siteTitleInput.value=taskData.task_name;
                        console.log('🔄 Site title re-applied after theme operations:',taskData.task_name);
                    }
                },100);
            }
        }

        // Enable services based on selected_services and prefill API keys
        this.prefillServicesAndConfigs(taskData);

        // Show notification about prefilled data
        this.showNotification('Form prefilled with ClickUp task data','success');
    }

    prefillServicesAndConfigs(taskData) {
        debugLog('Prefilling services and configs with task data');
        debugLog('Current config before merge:',this.currentConfig);

        // Service name to config toggle mapping
        const serviceToggleMap={
            'Google Analytics': 'analytics-toggle',
            'Map View': 'maps-toggle',
            'Captcha': null, // reCAPTCHA is handled per-form
            'Contact Form': 'forms-integration-toggle',
            'Document Upload': 'forms-integration-toggle',
            'Social Links': 'social-links-toggle',
            'Donation Key/API': null, // No specific toggle
            'Custom Privacy Policy': null,
            'Comprehensive Security': null,
            'Email Template': null,
        };

        // Enable service toggles based on selected_services
        if(taskData.selected_services&&Array.isArray(taskData.selected_services)) {
            debugLog('Selected services:',taskData.selected_services);
            taskData.selected_services.forEach(service => {
                const toggleId=serviceToggleMap[service];
                if(toggleId) {
                    const toggle=document.getElementById(toggleId);
                    debugLog(`Looking for toggle ${toggleId}:`,toggle);
                    if(toggle&&!toggle.checked) {
                        toggle.checked=true;
                        debugLog(`Enabled toggle: ${toggleId}`);
                        // Trigger change event to update dependent UI
                        toggle.dispatchEvent(new Event('change',{bubbles: true}));
                    } else if(!toggle) {
                        debugLog(`Toggle ${toggleId} not found in DOM yet`,'warn');
                    }
                }
            });
        }

        // Prefill Google Analytics Token - Only if ClickUp has a value
        if(taskData.google_analytics_token) {
            const analyticsInput=document.querySelector('[data-path="authentication.api_keys.google_analytics"]');
            debugLog('Analytics input element:',analyticsInput);
            if(analyticsInput) {
                // Only overwrite if ClickUp value is different from existing OR existing is empty
                const existingValue=analyticsInput.value||'';
                if(!existingValue||existingValue!==taskData.google_analytics_token) {
                    analyticsInput.value=taskData.google_analytics_token;
                    debugLog('Set analytics token:',taskData.google_analytics_token);
                } else {
                    debugLog('Analytics token already set, preserving existing value:',existingValue);
                }
            } else {
                debugLog('Analytics input not found in DOM yet','warn');
            }
        }

        // Prefill Google Maps API Key - Only if ClickUp has a value
        if(taskData.google_map_key) {
            const mapsInput=document.querySelector('[data-path="authentication.api_keys.google_maps"]');
            debugLog('Maps input element:',mapsInput);
            if(mapsInput) {
                // Only overwrite if ClickUp value is different from existing OR existing is empty
                const existingValue=mapsInput.value||'';
                if(!existingValue||existingValue!==taskData.google_map_key) {
                    mapsInput.value=taskData.google_map_key;
                    debugLog('Set maps API key:',taskData.google_map_key);
                    // Trigger change event to potentially load map preview
                    mapsInput.dispatchEvent(new Event('input',{bubbles: true}));
                } else {
                    debugLog('Maps API key already set, preserving existing value:',existingValue);
                }
            } else {
                debugLog('Maps input not found in DOM yet','warn');
            }
        }

        // Store reCAPTCHA keys for later form configuration - Only if ClickUp has values
        // These will be available when user configures forms
        if(taskData.recaptcha_site_key||taskData.recaptcha_secret) {
            // Check if we already have values in sessionStorage
            const existingSiteKey=sessionStorage.getItem('clickup_recaptcha_site_key')||'';
            const existingSecret=sessionStorage.getItem('clickup_recaptcha_secret')||'';

            // Only update if ClickUp has a value and (existing is empty OR different)
            if(taskData.recaptcha_site_key&&(!existingSiteKey||existingSiteKey!==taskData.recaptcha_site_key)) {
                sessionStorage.setItem('clickup_recaptcha_site_key',taskData.recaptcha_site_key);
                debugLog('Updated reCAPTCHA site key from ClickUp');
            } else if(existingSiteKey) {
                debugLog('Preserving existing reCAPTCHA site key:',existingSiteKey);
            }

            if(taskData.recaptcha_secret&&(!existingSecret||existingSecret!==taskData.recaptcha_secret)) {
                sessionStorage.setItem('clickup_recaptcha_secret',taskData.recaptcha_secret);
                debugLog('Updated reCAPTCHA secret from ClickUp');
            } else if(existingSecret) {
                debugLog('Preserving existing reCAPTCHA secret');
            }
        }

        // Store email for later use - Only if ClickUp has a value
        if(taskData.email) {
            const existingEmail=sessionStorage.getItem('clickup_email')||'';
            if(!existingEmail||existingEmail!==taskData.email) {
                sessionStorage.setItem('clickup_email',taskData.email);
                debugLog('Updated email from ClickUp:',taskData.email);
            } else {
                debugLog('Preserving existing email:',existingEmail);
            }
        }

        // Store privacy policy info - Only if ClickUp has a value
        if(taskData.privacy_policy_info) {
            const existingPolicy=sessionStorage.getItem('clickup_privacy_policy')||'';
            if(!existingPolicy||existingPolicy!==taskData.privacy_policy_info) {
                sessionStorage.setItem('clickup_privacy_policy',taskData.privacy_policy_info);
                debugLog('Updated privacy policy from ClickUp');
            } else {
                debugLog('Preserving existing privacy policy');
            }
        }

        // Store Google Drive info - Only if ClickUp has a value
        if(taskData.google_drive) {
            const existingDrive=sessionStorage.getItem('clickup_google_drive')||'';
            if(!existingDrive||existingDrive!==taskData.google_drive) {
                sessionStorage.setItem('clickup_google_drive',taskData.google_drive);
                debugLog('Updated Google Drive from ClickUp');
            } else {
                debugLog('Preserving existing Google Drive');
            }
        }

        // Store social media links - Only if ClickUp has values
        const socialFields={
            'facebook_link': {
                storageKey: 'clickup_facebook_link',
                inputPath: 'integrations.social_links.facebook'
            },
            'instagram_link': {
                storageKey: 'clickup_instagram_link',
                inputPath: 'integrations.social_links.instagram'
            },
            'twitter_link': {
                storageKey: 'clickup_twitter_link',
                inputPath: 'integrations.social_links.twitter'
            },
            'youtube_link': {
                storageKey: 'clickup_youtube_link',
                inputPath: 'integrations.social_links.youtube'
            },
            'winred_link': {
                storageKey: 'clickup_winred_link',
                inputPath: 'integrations.social_links.winred'
            }
        };

        Object.keys(socialFields).forEach(fieldKey => {
            if(taskData[fieldKey]) {
                const config=socialFields[fieldKey];
                const storageKey=config.storageKey;
                const inputPath=config.inputPath;

                // Update sessionStorage
                const existingValue=sessionStorage.getItem(storageKey)||'';
                if(!existingValue||existingValue!==taskData[fieldKey]) {
                    sessionStorage.setItem(storageKey,taskData[fieldKey]);
                    debugLog(`Updated ${fieldKey} from ClickUp:`,taskData[fieldKey]);
                } else {
                    debugLog(`Preserving existing ${fieldKey}:`,existingValue);
                }

                // Also fill the actual form input if it exists
                const input=document.querySelector(`[data-path="${inputPath}"]`);
                if(input) {
                    const currentValue=input.value||'';
                    // Only overwrite if ClickUp value is different from existing OR existing is empty
                    if(!currentValue||currentValue!==taskData[fieldKey]) {
                        input.value=taskData[fieldKey];
                        debugLog(`Filled ${inputPath} input with:`,taskData[fieldKey]);
                    } else {
                        debugLog(`Preserving existing value in ${inputPath}:`,currentValue);
                    }
                } else {
                    debugLog(`Input for ${inputPath} not found in DOM yet`,'warn');
                }
            }
        });

        debugLog('✅ Config merge complete - existing values preserved, ClickUp values applied where available');
    }

    async fetchManualTask() {
        debugLog('fetchManualTask() called');
        const taskIdInput=document.getElementById('manual-task-id-input');
        const fetchBtn=document.getElementById('fetch-manual-task-btn');
        const statusDiv=document.getElementById('manual-task-status');
        const alertDiv=document.getElementById('manual-task-alert');
        const taskSelect=document.getElementById('clickup-task-select');

        debugLog('Elements found:',{
            taskIdInput: !!taskIdInput,
            fetchBtn: !!fetchBtn,
            statusDiv: !!statusDiv,
            alertDiv: !!alertDiv,
            taskSelect: !!taskSelect
        });

        if(!taskIdInput||!fetchBtn) {
            debugLog('Required elements not found!','error');
            return;
        }

        const taskId=taskIdInput.value.trim();
        debugLog('Task ID entered:',taskId);

        if(!taskId) {
            this.showManualTaskStatus('Please enter a task ID','error');
            return;
        }

        // Validate task ID format
        if(!/^[a-z0-9\-]+$/i.test(taskId)) {
            this.showManualTaskStatus('Invalid task ID format. Use only letters, numbers, and hyphens.','error');
            return;
        }

        // Show loading state
        fetchBtn.disabled=true;
        fetchBtn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Fetching...';
        this.showManualTaskStatus('Fetching task from ClickUp API...','info');

        try {
            debugLog(`Fetching manual task: ${taskId}`);
            const response=await fetch(`/php/api/fetch-clickup-task.php?task_id=${encodeURIComponent(taskId)}`);
            debugLog('Response status:',response.status);
            const data=await response.json();
            debugLog('Response data:',data);

            if(data.success&&data.task) {
                debugLog('Manual task fetched successfully:',data.task);

                // Add task to select dropdown
                const existingOption=Array.from(taskSelect.options).find(opt => opt.value===taskId);
                if(!existingOption) {
                    const option=document.createElement('option');
                    option.value=data.task.task_id;
                    option.textContent=`${data.task.task_name} ${data.task.website_url? '('+data.task.website_url+')':''}`;
                    taskSelect.appendChild(option);
                }

                // Select the task in dropdown
                taskSelect.value=taskId;

                // Prefill form with task data but guard against prefill errors so they don't surface as network errors
                try {
                    this.prefillDeploymentForm(data.task);
                    // Show success message only if prefill succeeded
                    this.showManualTaskStatus(`Task "${data.task.task_name}" fetched successfully!`,'success');
                } catch(prefillError) {
                    debugLog('Error during prefillDeploymentForm:',prefillError,'error');
                    // Inform user the fetch succeeded but prefill had issues
                    this.showManualTaskStatus('Task fetched, but failed to prefill some form fields. See console for details.','warning');
                }

                // Clear input after successful fetch
                taskIdInput.value='';

            } else {
                debugLog('Failed to fetch manual task:',data,'error');
                const errorMessage=data.message||'Failed to fetch task from ClickUp';
                this.showManualTaskStatus(errorMessage,'error');
            }

        } catch(error) {
            debugLog('Error fetching manual task:',error,'error');
            // Better classification of error messages
            let message='Network error. Please check your connection and try again.';
            if(error&&error.message&&!error.message.toLowerCase().includes('failed to fetch')) {
                message=`Error fetching task: ${error.message}`;
            }
            this.showManualTaskStatus(message,'error');
        } finally {
            // Reset button state
            fetchBtn.disabled=false;
            fetchBtn.innerHTML='<i class="fas fa-download me-1"></i>Fetch Task';

            // Auto-hide success message after 5 seconds
            setTimeout(() => {
                if(statusDiv&&alertDiv&&alertDiv.classList.contains('alert-success')) {
                    statusDiv.style.display='none';
                }
            },5000);
        }
    }

    showManualTaskStatus(message,type) {
        const statusDiv=document.getElementById('manual-task-status');
        const alertDiv=document.getElementById('manual-task-alert');

        if(!statusDiv||!alertDiv) return;

        // Remove all alert type classes
        alertDiv.className='alert';

        // Add appropriate alert class
        switch(type) {
            case 'success':
                alertDiv.classList.add('alert-success');
                break;
            case 'error':
                alertDiv.classList.add('alert-danger');
                break;
            case 'info':
                alertDiv.classList.add('alert-info');
                break;
            default:
                alertDiv.classList.add('alert-secondary');
        }

        alertDiv.textContent=message;
        statusDiv.style.display='block';
    }

    async populateThemeSelect(themes) {
        const pageThemeSelect=document.getElementById('page-theme-select');
        const deploymentThemeSelect=document.getElementById('deployment-theme-select');

        if(!themes||themes.length===0) {
            if(pageThemeSelect) pageThemeSelect.innerHTML='<option value="">No themes found</option>';
            if(deploymentThemeSelect) deploymentThemeSelect.innerHTML='<option value="">No themes found</option>';
            return;
        }

        // Get the active theme from backend
        let activeTheme=null;
        try {
            const pagesResponse=await fetch('?action=get_pages');
            const pagesData=await pagesResponse.json();
            if(pagesData.success) {
                activeTheme=pagesData.data.active_theme;
            }
        } catch(error) {
            debugLog('Failed to get active theme:',error,'error');
        }

        // Populate Page Editor theme select
        if(pageThemeSelect) {
            pageThemeSelect.innerHTML='<option value="">Select a theme...</option>';
            themes.forEach((theme) => {
                const option=document.createElement('option');
                option.value=theme;
                option.textContent=theme;
                if(activeTheme&&activeTheme===theme) {
                    option.selected=true;
                }
                pageThemeSelect.appendChild(option);
            });

            // If we have an active theme, load its pages
            if(activeTheme) {
                this.loadThemePages(activeTheme);
            }
        }

        // Populate Deployment theme select
        if(deploymentThemeSelect) {
            deploymentThemeSelect.innerHTML='<option value="">Select a theme...</option>';
            themes.forEach((theme) => {
                const option=document.createElement('option');
                option.value=theme;
                option.textContent=theme;
                if(activeTheme&&activeTheme===theme) {
                    option.selected=true;
                }
                deploymentThemeSelect.appendChild(option);
            });
        }
    }

    async loadPageOptionsForForms() {
        try {
            // First get the active theme from current configuration
            const configResponse=await fetch('?action=get_configs');
            const configData=await configResponse.json();

            let activeTheme=null;
            if(configData.success&&configData.data) {
                // Try to find active theme from different config paths
                activeTheme=configData.data.site?.active_theme||
                    configData.data.main?.site?.active_theme||
                    configData.data.config?.site?.active_theme||
                    configData.data.active_theme;
            }

            // If no active theme found in config, try to get from localStorage (deployment theme)
            if(!activeTheme) {
                activeTheme=localStorage.getItem('deploymentTheme');
            }

            // If still no theme, try to get available themes and use the first one
            if(!activeTheme) {
                const themesResponse=await fetch('?action=get_themes');
                const themesData=await themesResponse.json();
                if(themesData.success&&themesData.data&&themesData.data.length>0) {
                    activeTheme=themesData.data[0];
                }
            }

            if(!activeTheme) {
                debugLog('No theme found to load page options','warn');
                return;
            }

            await this.updatePageOptionsForTheme(activeTheme);
        } catch(error) {
            debugLog('Failed to load page options for forms:',error,'error');
        }
    }

    populatePageDropdowns(pages) {
        // Find all form placement dropdowns
        const formPlacementSelectors=[
            '[data-path="integrations.forms.contact_form.placement"]',
            '[data-path="integrations.forms.volunteer_form.placement"]',
            '[data-path="integrations.forms.document_upload_form.placement"]',
            '[data-path="integrations.maps.placement"]',
            '[data-path="integrations.social_links.placement"]'
        ];

        formPlacementSelectors.forEach(selector => {
            const dropdown=document.querySelector(selector);
            if(dropdown) {
                // Store current value
                const currentValue=dropdown.value;

                // Clear existing options
                dropdown.innerHTML='';

                // Add page options from JSON files
                pages.forEach(page => {
                    const option=document.createElement('option');
                    option.value=page.id;
                    option.textContent=page.name;
                    dropdown.appendChild(option);
                });

                // Add some common additional options for specific dropdowns
                if(selector.includes('social_links')) {
                    // Social links have footer, header, sidebar options in addition to pages
                    const additionalOptions=[
                        {value: 'footer',name: 'Footer'},
                        {value: 'header',name: 'Header'},
                        {value: 'sidebar',name: 'Sidebar'}
                    ];
                    additionalOptions.forEach(opt => {
                        const option=document.createElement('option');
                        option.value=opt.value;
                        option.textContent=opt.name;
                        dropdown.appendChild(option);
                    });
                } else if(selector.includes('maps')) {
                    // Maps might have footer option
                    const option=document.createElement('option');
                    option.value='footer';
                    option.textContent='Footer';
                    dropdown.appendChild(option);
                }

                // Restore current value if it still exists
                if(currentValue) {
                    dropdown.value=currentValue;
                }
            }
        });
    }

    async updatePageOptionsForTheme(theme) {
        try {
            // Get pages with names for the specified theme
            const pagesResponse=await fetch(`?action=get_theme_pages_with_names&theme=${encodeURIComponent(theme)}`);
            const pagesData=await pagesResponse.json();

            if(pagesData.success&&pagesData.data.pages) {
                this.populatePageDropdowns(pagesData.data.pages);
                // Also update dynamic forms if they exist
                await this.loadDynamicForms(pagesData.data.pages);
            } else {
                debugLog('Failed to load page options for theme:',theme,pagesData.message,'error');
            }
        } catch(error) {
            debugLog('Failed to update page options for theme:',theme,error,'error');
        }
    }

    async loadDynamicForms(pages=null) {
        try {
            const container=document.getElementById('dynamic-forms-container');
            if(!container) return;

            // Load forms from the API
            const response=await fetch('?action=get_other_contents&type=forms');
            const data=await response.json();

            if(!data.success) {
                container.innerHTML=`<div class="text-center py-4 text-red-500">
                    <i class="fas fa-exclamation-circle"></i> Failed to load forms
                </div>`;
                return;
            }

            const forms=data.data||[];

            if(forms.length===0) {
                container.innerHTML=`<div class="text-center py-4 text-gray-500">
                    <i class="fas fa-info-circle"></i> No forms available. Create forms in the Forms Manager tab.
                </div>`;
                return;
            }

            // If pages not provided, fetch them
            if(!pages) {
                try {
                    const configResponse=await fetch('?action=get_configs');
                    const configData=await configResponse.json();
                    let activeTheme=configData.data?.site?.active_theme||localStorage.getItem('deploymentTheme');

                    if(activeTheme) {
                        const pagesResponse=await fetch(`?action=get_theme_pages_with_names&theme=${encodeURIComponent(activeTheme)}`);
                        const pagesData=await pagesResponse.json();
                        if(pagesData.success) {
                            pages=pagesData.data.pages||[];
                        }
                    }
                } catch(error) {
                    debugLog('Failed to fetch pages for forms:',error,'error');
                }
            }

            pages=pages||[];

            // Render forms
            let html='';
            forms.forEach(form => {
                const formId=form.id.replace('.json','');
                const formName=form.name||formId;
                const formKey=formId.replace(/-/g,'_');

                html+=`
                    <div class="card mt-4" data-form-id="${formId}">
                        <div class="card-header">
                            <h4 class="card-title">${this.escapeHtml(formName)}</h4>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="form-group">
                                    <div class="toggle-container">
                                        <div class="toggle-wrapper">
                                            <input type="checkbox" class="config-input toggle-input"
                                                id="${formKey}-toggle"
                                                data-path="integrations.forms.${formKey}.enabled">
                                            <div class="toggle-switch"></div>
                                        </div>
                                        <label class="toggle-label" for="${formKey}-toggle">
                                            <i class="fas fa-file-alt toggle-icon"></i>
                                            Enable ${this.escapeHtml(formName)}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Placement</label>
                                    <select class="form-select config-input"
                                        data-path="integrations.forms.${formKey}.placement">
                                        <option value="">Select placement...</option>
                                        <option value="new">Create New Page</option>
                                        ${pages.map(page => `<option value="${page.id}">${this.escapeHtml(page.name)}</option>`).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML=html;

            // Re-populate config values if they exist
            this.populateConfigForms(this.currentConfig);
        } catch(error) {
            debugLog('Failed to load dynamic forms:',error,'error');
            const container=document.getElementById('dynamic-forms-container');
            if(container) {
                container.innerHTML=`<div class="text-center py-4 text-red-500">
                    <i class="fas fa-exclamation-circle"></i> Error loading forms
                </div>`;
            }
        }
    }

    escapeHtml(text) {
        const div=document.createElement('div');
        div.textContent=text;
        return div.innerHTML;
    }


    async loadDeploymentStatus() {
        try {
            const response=await fetch('?action=deployment_status');
            const data=await response.json();

            if(data.success) {
                this.updateDeploymentStatusDisplay(data.data);

                // Load site configuration to get the site title if not in the response
                if(!data.data.site_title) {
                    const configResponse=await fetch('?action=get_configs');
                    const configData=await configResponse.json();

                    if(configData.success&&configData.data.site) {
                        // Update the site title in the header
                        const siteTitle=document.getElementById('site-title');
                        if(siteTitle) {
                            siteTitle.textContent=configData.data.site.site_title||'Site Title';
                        }

                        // Update the site title input field
                        const siteTitleInput=document.getElementById('deployment-site-title');
                        if(siteTitleInput) {
                            siteTitleInput.value=configData.data.site.site_title||'';
                            // Check if this site already exists in Kinsta on page load
                            if(configData.data.site.site_title) {
                                this.checkSiteExistence(configData.data.site.site_title);
                            }
                        }
                    }
                }

                // Load themes for the deployment theme dropdown
                try {
                    const [themesResponse,pagesResponse]=await Promise.all([
                        fetch('?action=get_themes'),
                        fetch('?action=get_pages')
                    ]);

                    const themesData=await themesResponse.json();
                    const pagesData=await pagesResponse.json();

                    if(themesData.success&&pagesData.success) {
                        const activeTheme=pagesData.data.active_theme;

                        // Update the theme dropdown in deployment tab
                        const deploymentThemeSelect=document.getElementById('deployment-theme-select');
                        if(deploymentThemeSelect) {
                            deploymentThemeSelect.innerHTML='<option value="">Select a theme...</option>';
                            themesData.data.forEach(theme => {
                                const option=document.createElement('option');
                                option.value=theme;
                                option.textContent=theme;
                                // Auto-select active theme
                                if(activeTheme&&activeTheme===theme) {
                                    option.selected=true;
                                }
                                deploymentThemeSelect.appendChild(option);
                            });
                        }
                    }
                } catch(themeError) {
                    debugLog('Failed to load themes for deployment:',themeError,'error');
                }
                // Resume monitoring/log tailing if the deployment is currently running
                if(data.data&&data.data.status==='running') {
                    debugLog('Detected running deployment on load - resuming monitoring');
                    // Ensure we resume log polling using persisted lastLogReadTime
                    this.startDeploymentMonitoring();
                }
            }
        } catch(error) {
            debugLog('Failed to load deployment status:',error,'error');
        }
    }

    updateDeploymentStatusDisplay(status) {
        debugLog('Updating deployment status:',status);

        // Update deployment state management
        // Keep deployment form hidden after completion - only show it on manual reset/reload
        const isDeploymentRunning=status.status==='running'||status.status==='starting'||status.status==='started'||
            status.status==='pending'||status.current_step||
            (status.status!=='idle'&&status.status!=='completed'&&status.status!=='failed'&&status.status!=='cancelled');
        const hasDeploymentCompleted=status.status==='completed'||status.status==='failed'||status.status==='cancelled';

        debugLog(`Deployment state analysis: status="${status.status}", current_step="${status.current_step}", isRunning=${isDeploymentRunning}, hasCompleted=${hasDeploymentCompleted}`);

        // Show form ONLY if deployment is truly idle (status='idle' and no current_step)
        // Hide form if deployment is running/pending OR has completed
        const shouldShowForm=!isDeploymentRunning&&!hasDeploymentCompleted&&status.status==='idle';

        this.setDeploymentInProgress(!shouldShowForm);
        debugLog(`Should show form: ${shouldShowForm}, Setting deployment in progress: ${!shouldShowForm}`);

        // Update compact view based on current status
        this.updateCompactViewStatus(status);

        // Sync step timing with backend data
        this.updateStepTimingFromBackend(status);

        // Stop timers for completed steps based on backend data
        this.manageStepTimersBasedOnStatus(status);

        const statusContainer=document.getElementById('deployment-status-list');
        if(!statusContainer) {
            debugLog('Status container not found');
            return;
        }
        debugLog('Status container found, updating display');

        // Define deployment steps with descriptions (matching actual backend steps)
        const deploymentSteps=[
            {id: 'create-site',name: 'Initiate Site Creation',description: 'Creating WordPress site on Kinsta platform'},
            {id: 'get-cred',name: 'Get Credentials',description: 'Retrieving site access credentials'},
            {id: 'trigger-deploy',name: 'Trigger Deployment',description: 'Deploying theme and content to site'},
            {id: 'github-actions',name: 'GitHub Actions',description: 'Monitoring GitHub Actions deployment status'}
        ];

        const currentTime=this.getISTTime();
        const currentStep=status.current_step||'config';

        // Calculate time since deployment started
        let timeSinceStart='';
        if(status.status==='running') {
            if(status.deployment_start_time) {
                // Use backend timing if available
                const startTime=new Date(status.deployment_start_time*1000);
                timeSinceStart=this.formatDuration(startTime);
            } else if(this.deploymentStartTime) {
                // Fallback to frontend timing
                timeSinceStart=this.formatDuration(this.deploymentStartTime);
            }
        }

        // Generate status cards with modern structure
        const statusCards=deploymentSteps.map(step => {
            let stepStatus='pending';
            let icon='<i class="fas fa-clock"></i>';
            let timestamp='Waiting...';
            let bgClass='from-gray-50 to-slate-50 border-gray-200';
            let iconClass='from-gray-400 to-slate-400';
            let textClass='text-gray-600';
            let descClass='text-gray-500';
            let timeClass='text-gray-400 bg-gray-100';
            let statusIcon='<i class="fas fa-hourglass-start text-gray-400"></i>';
            let progressBar='';
            let timingInfo='';

            // Determine if this step is completed based on the current step and status
            const stepIndex=deploymentSteps.findIndex(s => s.id===step.id);
            const currentStepIndex=deploymentSteps.findIndex(s => s.id===currentStep);

            // Special handling for github-actions step
            if(step.id==='github-actions') {
                // GitHub Actions step status is managed separately via pollGitHubActionsStatus
                // Only show initial state here if not manually updated
                if(!this.githubActionsManuallyUpdated&&(currentStep==='github-actions'||currentStepIndex>=stepIndex)) {
                    // GitHub Actions step is active or we've reached it
                    stepStatus='in-progress';
                    icon='<i class="fas fa-spinner fa-spin"></i>';
                    timestamp=`Started: ${currentTime}`;
                    bgClass='from-blue-50 to-indigo-50 border-blue-200';
                    iconClass='from-blue-500 to-indigo-500';
                    textClass='text-blue-800';
                    descClass='text-blue-600';
                    timeClass='text-blue-500 bg-blue-100';
                    statusIcon='<i class="fas fa-spinner fa-spin text-blue-500"></i>';

                    // Start timer for this step if not already started and step isn't completed
                    const isStepCompleted=status.status==='completed'||
                        stepIndex<currentStepIndex||
                        (status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].end_time);

                    if(!this.stepStartTimes.has(step.id)&&!isStepCompleted) {
                        debugLog(`Starting timer for GitHub Actions step: ${step.id}`);
                        // Use backend start time if available for accurate individual step timing
                        let stepStartTime=null;
                        if(status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].start_time) {
                            stepStartTime=new Date(status.step_timings[step.id].start_time*1000);
                        }
                        this.startStepTimer(step.id,stepStartTime);
                    } else if(isStepCompleted) {
                        debugLog(`GitHub Actions step ${step.id} is completed, not starting timer`);
                    }
                } else if(this.githubActionsManuallyUpdated) {
                    // Skip updating this step - it's being managed by updateGitHubActionsDisplay
                    const existingCard=document.querySelector('[data-step="github-actions"]');
                    if(existingCard) {
                        // Return existing card HTML to preserve current state
                        return existingCard.outerHTML;
                    }
                }
                // If we haven't reached github-actions step yet, leave it as pending
            } else if(status.status==='completed'||(status.status==='running'&&stepIndex<currentStepIndex)) {
                stepStatus='completed';
                icon='<i class="fas fa-check"></i>';

                // Use backend timing data if available
                if(status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].end_time_formatted) {
                    // Convert UTC to IST and format
                    const endTimeUTC=new Date(status.step_timings[step.id].end_time_formatted+'Z');
                    const endTimeIST=new Date(endTimeUTC.getTime()+(5.5*60*60*1000));
                    timestamp=`Completed: ${endTimeIST.toLocaleString('en-IN',{
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    })} IST`;

                    if(status.step_timings[step.id].duration) {
                        const duration=status.step_timings[step.id].duration;
                        const minutes=Math.floor(duration/60);
                        const seconds=duration%60;
                        timingInfo=`<div class="text-xs text-emerald-600 mt-1">Duration: ${minutes>0? minutes+'m ':''}${seconds}s</div>`;
                    }
                } else {
                    timestamp=`Completed: ${currentTime}`;
                }

                bgClass='from-emerald-50 to-teal-50 border-emerald-200';
                iconClass='from-emerald-500 to-teal-500';
                textClass='text-emerald-800';
                descClass='text-emerald-600';
                timeClass='text-emerald-500 bg-emerald-100';
                statusIcon='<i class="fas fa-check-circle text-emerald-500"></i>';

                // Stop timer and show duration if available
                if(this.realTimeTimers.has(step.id)) {
                    debugLog(`Stopping timer for completed step: ${step.id}`);
                    this.stopStepTimer(step.id);
                }
                if(!timingInfo&&this.stepDurations.has(step.id)) {
                    timingInfo=`<div class="text-xs text-emerald-600 mt-1">Duration: ${this.stepDurations.get(step.id)}</div>`;
                }
            } else if(step.id===currentStep&&status.status==='running') {
                stepStatus='in-progress';
                icon='<i class="fas fa-cog fa-spin"></i>';

                // Use backend timing data if available
                if(status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].start_time_formatted) {
                    // Convert UTC to IST and format
                    const startTimeUTC=new Date(status.step_timings[step.id].start_time_formatted+'Z');
                    const startTimeIST=new Date(startTimeUTC.getTime()+(5.5*60*60*1000));
                    timestamp=`Started: ${startTimeIST.toLocaleString('en-IN',{
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    })} IST`;
                } else {
                    timestamp=`Started: ${currentTime}`;
                }

                bgClass='from-blue-50 to-indigo-50 border-blue-200';
                iconClass='from-blue-500 to-indigo-500';
                textClass='text-blue-800';
                descClass='text-blue-600';
                timeClass='text-blue-500 bg-blue-100';
                statusIcon='<div class="animate-pulse bg-blue-500 w-4 h-4 rounded-full"></div>';
                progressBar=`
                    <div class="progress-bar mt-3 bg-blue-200 rounded-full h-2 overflow-hidden">
                        <div class="progress-fill bg-gradient-to-r from-blue-500 to-indigo-500 h-full rounded-full animate-pulse" style="width: 60%"></div>
                    </div>
                `;

                // Start timer for this step if not already started and step isn't completed
                const isStepCompleted=status.status==='completed'||
                    stepIndex<currentStepIndex||
                    (status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].end_time);

                if(!this.stepStartTimes.has(step.id)&&!isStepCompleted) {
                    debugLog(`Starting timer for step: ${step.id}`);
                    // Use backend start time if available for accurate individual step timing
                    let stepStartTime=null;
                    if(status.step_timings&&status.step_timings[step.id]&&status.step_timings[step.id].start_time) {
                        stepStartTime=new Date(status.step_timings[step.id].start_time*1000);
                    }
                    this.startStepTimer(step.id,stepStartTime);
                } else if(isStepCompleted) {
                    debugLog(`Step ${step.id} is completed, not starting timer`);
                }
            } else if(step.id===currentStep&&status.status==='failed') {
                stepStatus='failed';
                icon='<i class="fas fa-times"></i>';
                timestamp=`Failed: ${currentTime}`;
                bgClass='from-red-50 to-rose-50 border-red-200';
                iconClass='from-red-500 to-rose-500';
                textClass='text-red-800';
                descClass='text-red-600';
                timeClass='text-red-500 bg-red-100';
                statusIcon='<i class="fas fa-times-circle text-red-500"></i>';

                // Stop timer on failure
                this.stopStepTimer(step.id);
            }

            return `
                <div class="status-step-card ${stepStatus} bg-gradient-to-r ${bgClass} border rounded-xl p-4 shadow-sm ${stepStatus==='pending'? 'opacity-60':''}" data-step="${step.id}" data-status="${stepStatus}">
                    <div class="flex items-center space-x-4">
                        <div class="status-icon-large bg-gradient-to-r ${iconClass} text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                            ${icon}
                        </div>
                        <div class="flex-1">
                            <h3 class="status-step-title text-lg font-semibold ${textClass} mb-1">${step.name}</h3>
                            <p class="status-step-desc ${descClass} text-sm mb-2">${step.description}</p>
                            <div class="status-step-time timestamp-badge text-xs font-mono px-2 py-1 rounded ${timeClass}" data-base-text="${stepStatus==='in-progress'? 'In Progress':''}">${timestamp}</div>
                            ${timingInfo}
                        </div>
                        <div class="status-check-mark text-2xl">${statusIcon}</div>
                    </div>
                    ${progressBar}
                </div>
            `;
        }).join('');

        // Add deployment summary at the top if deployment is active
        let deploymentSummary='';
        if(status.status==='running') {
            let clickTimeIST='';
            let deploymentStartTimeFormatted='';

            // Use backend deployment start time if available
            if(status.deployment_start_time_formatted) {
                const startTimeUTC=new Date(status.deployment_start_time_formatted+'Z');
                const startTimeIST=new Date(startTimeUTC.getTime()+(5.5*60*60*1000));
                deploymentStartTimeFormatted=startTimeIST.toLocaleString('en-IN',{
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                clickTimeIST=`Deployment started: ${deploymentStartTimeFormatted} IST`;
            } else if(this.buttonClickTime) {
                const clickTime=new Date(this.buttonClickTime.getTime()+(5.5*60*60*1000));
                clickTimeIST=`Button clicked: ${clickTime.toLocaleString('en-IN',{
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                })} IST`;
            }

            if(clickTimeIST&&timeSinceStart) {
                // Check if summary already exists to avoid re-animation
                const existingSummary=statusContainer.querySelector('.deployment-summary');
                if(existingSummary) {
                    // Update only the duration part without changing structure
                    const durationElement=existingSummary.querySelector('.text-indigo-600.font-mono');
                    if(durationElement) {
                        durationElement.textContent=timeSinceStart;
                    }
                    deploymentSummary=existingSummary.outerHTML;
                } else {
                    // Create new summary without animation
                    deploymentSummary=`
                        <div class="deployment-summary bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl p-4 mb-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-indigo-800 mb-1">
                                        <i class="fas fa-rocket mr-2"></i>Deployment in Progress
                                    </h3>
                                    <p class="text-indigo-600 text-sm">${clickTimeIST}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-indigo-800 font-semibold">Total Duration</div>
                                    <div class="text-indigo-600 font-mono text-lg">${timeSinceStart}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }
        }

        // Update the status badge
        const statusBadge=document.querySelector('.status-badge');
        if(statusBadge) {
            const badgeText=status.status==='running'? 'DEPLOYING':
                status.status==='completed'? 'COMPLETE':
                    (status.status==='error'||status.status==='failed')? 'FAILED':
                        status.current_step? 'IN PROGRESS':'READY';
            const badgeClass=status.status==='running'? 'bg-orange-500':
                status.status==='completed'? 'bg-green-500':
                    (status.status==='error'||status.status==='failed')? 'bg-red-500':
                        status.current_step? 'bg-blue-500':'bg-gray-500';
            statusBadge.textContent=badgeText;
            statusBadge.className=`status-badge ${badgeClass} text-white px-3 py-1 rounded-full text-sm font-semibold`;
        }

        // Update the status cards container
        statusContainer.innerHTML=deploymentSummary+statusCards;

        // Remove deployment summary if deployment is not running
        if(status.status!=='running') {
            const existingSummary=statusContainer.querySelector('.deployment-summary');
            if(existingSummary) {
                existingSummary.remove();
            }

            // Clean up all timers when deployment completes or fails
            if(status.status==='completed'||status.status==='failed') {
                this.realTimeTimers.forEach((timerId,stepId) => {
                    clearInterval(timerId);
                });
                this.realTimeTimers.clear();
            }
        }        // Update site title in the header and input field
        if(status.site_title) {
            const siteTitleHeader=document.getElementById('site-title');
            if(siteTitleHeader) {
                siteTitleHeader.textContent=status.site_title;
            }

            const siteTitleInput=document.getElementById('deployment-site-title');
            if(siteTitleInput&&!siteTitleInput.value) {
                siteTitleInput.value=status.site_title;
            }
        }

        // Restore deployment theme from localStorage if available
        const savedDeploymentTheme=localStorage.getItem('deploymentTheme');
        if(savedDeploymentTheme) {
            const deploymentThemeSelect=document.getElementById('deployment-theme-select');
            if(deploymentThemeSelect) {
                deploymentThemeSelect.value=savedDeploymentTheme;
            }
        }
    }

    populateConfigForms(configs) {
        debugLog('Config data loaded:',configs);

        // Populate git config
        if(configs.git) {
            this.populateForm('git-config-form',configs.git);
        }

        // Populate site config
        if(configs.site) {
            this.populateForm('site-config-form',configs.site);
            // Company ID validation removed; do not call server-side validation endpoint
            // Update Kinsta token link after config is populated
            setTimeout(() => this.updateKinstaTokenLink(),100);
        }

        // Get the main config data (could be configs.main or just configs if it's already the main config)
        const mainConfig=configs.main||configs;
        debugLog('Main config data:',mainConfig);

        // Populate all forms with main config data using data-path attributes
        const allForms=document.querySelectorAll('form[id$="-config-form"]');
        allForms.forEach(form => {
            const inputs=form.querySelectorAll('[data-path]');
            inputs.forEach(input => {
                const path=input.dataset.path;
                debugLog('Looking for path:',path,'in configs:',configs);

                // Try multiple sources for the value
                let value=this.getNestedValue(mainConfig,path);
                if(value===undefined&&configs.main) {
                    value=this.getNestedValue(configs.main,path);
                }
                if(value===undefined&&configs.config) {
                    value=this.getNestedValue(configs.config,path);
                }
                if(value===undefined) {
                    value=this.getNestedValue(configs,path);
                }

                debugLog('Found value for',path,':',value);

                if(value!==undefined&&value!==null) {
                    if(input.type==='checkbox') {
                        input.checked=Boolean(value);
                    } else if(input.type==='number') {
                        input.value=Number(value);
                    } else {
                        input.value=value;
                    }
                }
            });
        });

        // Load dynamic configuration data for new components
        // Pass the full configs object to handle all config sections
        this.loadConfigData(configs);

        // Load dynamic lists with config data
        this.loadDynamicLists(configs);

        // Update map preview if API key exists - with longer delay to ensure markers are loaded
        setTimeout(() => {
            this.updateMapPreview();
        },500);
    }

    populateForm(formId,data) {
        const form=document.getElementById(formId);
        if(!form) return;

        const inputs=form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const path=input.dataset.path;
            if(path) {
                const value=this.getNestedValue(data,path);
                if(value!==undefined) {
                    if(input.type==='checkbox') {
                        input.checked=Boolean(value);
                    } else {
                        input.value=value;
                    }
                }
            }
        });
    }

    getNestedValue(obj,path) {
        return path.split('.').reduce((current,key) => current&&current[key],obj);
    }

    setNestedValue(obj,path,value) {
        const keys=path.split('.');
        const lastKey=keys.pop();
        const target=keys.reduce((current,key) => {
            if(!current[key]) current[key]={};
            return current[key];
        },obj);
        target[lastKey]=value;
    }

    async saveActiveTheme(theme) {
        try {
            if(!theme) {
                this.showAlert('No theme selected','error');
                return;
            }

            const response=await fetch('?action=save_active_theme',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    theme: theme
                })
            });

            // Check if response is OK and content-type is JSON
            if(!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType=response.headers.get('content-type');
            if(!contentType||!contentType.includes('application/json')) {
                const textResponse=await response.text();
                debugLog('Server returned non-JSON response:',textResponse,'error');
                throw new Error('Server returned an error. Check browser console for details.');
            }

            const result=await response.json();

            if(result.success) {
                debugLog('Active theme saved successfully:',theme);
                // Update the deployment theme select to reflect the change
                this.updateDeploymentThemeSelect(theme);
                // Show success message
                this.showAlert('Theme saved successfully!','success');
            } else {
                debugLog('Failed to save active theme:',result.message,'error');
                this.showAlert(result.message||'Failed to save theme selection','error');
            }
        } catch(error) {
            debugLog('Error saving active theme:',error,'error');
            this.showAlert('Error saving theme: '+error.message,'error');
        }
    }

    updateDeploymentThemeSelect(activeTheme) {
        const deploymentThemeSelect=document.getElementById('deployment-theme-select');
        if(deploymentThemeSelect) {
            // Update the selected option
            Array.from(deploymentThemeSelect.options).forEach(option => {
                option.selected=option.value===activeTheme;
            });
        }
    }

    updatePageThemeSelect(activeTheme) {
        const pageThemeSelect=document.getElementById('page-theme-select');
        if(pageThemeSelect) {
            // Update the selected option
            Array.from(pageThemeSelect.options).forEach(option => {
                option.selected=option.value===activeTheme;
            });
            // Trigger theme pages loading if the value actually changed
            if(pageThemeSelect.value===activeTheme) {
                this.loadThemePages(activeTheme);
            }
        }
    }

    async refreshThemeList() {
        try {
            const refreshBtn=document.getElementById('refresh-theme-list-btn');
            const icon=refreshBtn?.querySelector('i');

            // Add spinning animation
            if(icon) {
                icon.classList.add('fa-spin');
            }
            if(refreshBtn) {
                refreshBtn.disabled=true;
            }

            const response=await fetch('?action=refresh_theme_list',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if(!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType=response.headers.get('content-type');
            if(!contentType||!contentType.includes('application/json')) {
                const textResponse=await response.text();
                debugLog('Server returned non-JSON response:',textResponse,'error');
                throw new Error('Server returned an error. Check browser console for details.');
            }

            const result=await response.json();

            if(result.success&&result.data) {
                // Update theme selector with new theme list
                const themeSelect=document.getElementById('page-theme-select');
                if(themeSelect&&result.data.themes&&result.data.themes.length>0) {
                    themeSelect.innerHTML=result.data.themes.map(theme =>
                        `<option value="${theme}" ${theme===result.data.active_theme? 'selected':''}>${theme}</option>`
                    ).join('');

                    // Load pages for active theme
                    if(result.data.active_theme) {
                        this.loadThemePages(result.data.active_theme);
                    }
                }

                // Also update deployment theme select if it exists
                const deploymentThemeSelect=document.getElementById('deployment-theme-select');
                if(deploymentThemeSelect&&result.data.themes&&result.data.themes.length>0) {
                    deploymentThemeSelect.innerHTML=result.data.themes.map(theme =>
                        `<option value="${theme}" ${theme===result.data.active_theme? 'selected':''}>${theme}</option>`
                    ).join('');
                }

                this.showAlert(result.message||'Theme list refreshed successfully!','success');
            } else {
                debugLog('Failed to refresh theme list:',result.message,'error');
                this.showAlert(result.message||'Failed to refresh theme list','error');
            }
        } catch(error) {
            debugLog('Error refreshing theme list:',error,'error');
            this.showAlert('Error refreshing theme list: '+error.message,'error');
        } finally {
            // Remove spinning animation
            const refreshBtn=document.getElementById('refresh-theme-list-btn');
            const icon=refreshBtn?.querySelector('i');
            if(icon) {
                icon.classList.remove('fa-spin');
            }
            if(refreshBtn) {
                refreshBtn.disabled=false;
            }
        }
    }

    async saveConfiguration(configType) {
        const form=document.getElementById(`${configType}-config-form`);
        if(!form) return;

        // Special handling for git config form which has mixed save types
        if(configType==='git') {
            await this.saveGitConfigMixed(form);
            return;
        }

        const formData=new FormData(form);
        const config={};

        // Build nested config object
        const inputs=form.querySelectorAll('input, select, textarea');
        debugLog('Found inputs:',inputs.length);

        inputs.forEach(input => {
            const path=input.dataset.path;
            if(path) {
                let value=input.value;

                if(input.type==='checkbox') {
                    value=input.checked;
                } else if(input.type==='number') {
                    value=Number(value);
                }

                debugLog(`Setting ${path} = ${value}`);
                this.setNestedValue(config,path,value);
            } else {
                debugLog('Input without data-path:',input);
            }
        });

        // Handle checkbox arrays (for user roles, etc.)
        const checkboxArrays=form.querySelectorAll('.config-checkbox[data-path]');
        const checkboxGroups={};

        checkboxArrays.forEach(checkbox => {
            const path=checkbox.dataset.path;
            const value=checkbox.dataset.value;

            if(!checkboxGroups[path]) {
                checkboxGroups[path]=[];
            }

            if(checkbox.checked) {
                checkboxGroups[path].push(value);
            }
        });

        // Set checkbox array values
        Object.keys(checkboxGroups).forEach(path => {
            this.setNestedValue(config,path,checkboxGroups[path]);
        });

        // Collect dynamic component data relevant to this config type
        const dynamicData=this.collectDynamicConfigData(configType);
        debugLog('DEBUG: Dynamic data collected for',configType,':',dynamicData);
        Object.keys(dynamicData).forEach(path => {
            this.setNestedValue(config,path,dynamicData[path]);
        });

        debugLog('DEBUG: Saving config type:',configType);
        debugLog('DEBUG: Final config data being sent:',config);
        debugLog('DEBUG: Config data JSON:',JSON.stringify(config,null,2));

        try {
            const response=await fetch('?action=save_config',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: configType,
                    data: config
                })
            });

            const result=await response.json();
            debugLog('Server response:',result);

            if(result.success) {
                this.showAlert('Configuration saved successfully!','success');
                this.markFormClean(form);

                // If site config was updated, update the site title in the deployment tab
                if(configType==='site'&&config.site_title) {
                    const siteTitleHeader=document.getElementById('site-title');
                    if(siteTitleHeader) {
                        siteTitleHeader.textContent=config.site_title;
                    }

                    const siteTitleInput=document.getElementById('deployment-site-title');
                    if(siteTitleInput) {
                        siteTitleInput.value=config.site_title;
                    }
                }
            } else {
                this.showAlert(result.message||'Failed to save configuration','error');
            }
        } catch(error) {
            debugLog('Failed to save configuration:',error,'error');
            this.showAlert('Failed to save configuration','error');
        }
    }

    async loadPageEditor() {
        try {
            const response=await fetch('?action=get_pages');
            const data=await response.json();

            if(data.success&&data.data) {
                this.populatePageEditor(data.data);
            } else {
                debugLog('Failed to load page editor:',data.message||'Unknown error','error');
                this.showAlert('Failed to load page editor','error');
            }
        } catch(error) {
            debugLog('Failed to load page editor:',error,'error');
            this.showAlert('Error loading page editor: '+error.message,'error');
        }
    }

    populatePageEditor(data) {
        // Populate theme selector
        const themeSelect=document.getElementById('page-theme-select');
        if(themeSelect&&data.themes&&data.themes.length>0) {
            themeSelect.innerHTML=data.themes.map(theme =>
                `<option value="${theme}" ${theme===data.active_theme? 'selected':''}>${theme}</option>`
            ).join('');

            // Load pages for active theme if available
            if(data.active_theme) {
                this.loadThemePages(data.active_theme);
            }
        } else {
            debugLog('No themes available','error');
            this.showAlert('No themes found','warning');
        }
    }

    async loadThemePages(theme) {
        try {
            if(!theme) {
                debugLog('No theme specified for loading pages','error');
                return;
            }

            const response=await fetch(`?action=get_theme_pages&theme=${encodeURIComponent(theme)}`);
            const data=await response.json();

            if(data.success&&data.data) {
                this.renderPageTabs(data.data.pages,theme);

                // Determine which page to load
                let pageToLoad;

                // First check if we have a saved page for this theme
                if(this.currentTheme===theme&&this.currentPage&&data.data.pages.includes(this.currentPage)) {
                    pageToLoad=this.currentPage;
                } else {
                    // Otherwise load the first page
                    pageToLoad=data.data.pages[0];
                }

                // Update current theme and page
                this.currentTheme=theme;
                this.currentPage=pageToLoad;
                localStorage.setItem('activeTheme',theme);
                localStorage.setItem('activePage',pageToLoad);

                // Load the selected page
                if(pageToLoad) {
                    this.loadPageContent(theme,pageToLoad);
                }
            } else {
                debugLog('Failed to load theme pages:',data.message||'Unknown error','error');
                this.showAlert('Failed to load pages for '+theme,'error');
            }
        } catch(error) {
            debugLog('Failed to load theme pages:',error,'error');
            this.showAlert('Error loading theme pages: '+error.message,'error');
        }
    }

    renderPageTabs(pages,theme) {
        const tabList=document.getElementById('page-tabs');
        if(!tabList) return;

        // Check if we have a previously selected page for this theme
        const previouslyActivePage=(theme===this.currentTheme)? this.currentPage:'';

        tabList.innerHTML=pages.map((page) => {
            // Check if this is the previously active page, or default to first page if none stored
            const isActive=(previouslyActivePage===page)||(!previouslyActivePage&&pages.indexOf(page)===0);

            return `<li class="tab-item">
                <a href="#" class="tab-link ${isActive? 'active page-selected':''}" 
                   data-page="${page}" data-theme="${theme}">${this.formatPageName(page)}</a>
            </li>`;
        }).join('');

        // Add click handlers for page tabs
        tabList.addEventListener('click',(e) => {
            if(e.target.classList.contains('tab-link')) {
                e.preventDefault();

                // Update active tab and highlight
                tabList.querySelectorAll('.tab-link').forEach(link => {
                    link.classList.remove('active');
                    link.classList.remove('page-selected');
                });
                e.target.classList.add('active');
                e.target.classList.add('page-selected');

                // Store the selected page and theme
                this.currentTheme=e.target.dataset.theme;
                this.currentPage=e.target.dataset.page;
                localStorage.setItem('activeTheme',this.currentTheme);
                localStorage.setItem('activePage',this.currentPage);

                // Load page content
                this.loadPageContent(e.target.dataset.theme,e.target.dataset.page);
            }
        });
    }

    async loadPageContent(theme,page) {
        try {
            if(!theme||!page) {
                debugLog('Theme or page not specified','error');
                return;
            }

            // Update tab classes to ensure the selected tab has both 'active' and 'page-selected' classes
            const pageTabs=document.querySelectorAll('#page-tabs .tab-link');
            pageTabs.forEach(tab => {
                if(tab.dataset.theme===theme&&tab.dataset.page===page) {
                    tab.classList.add('active');
                    tab.classList.add('page-selected');
                } else {
                    tab.classList.remove('active');
                    tab.classList.remove('page-selected');
                }
            });

            const response=await fetch(`?action=get_page_content&theme=${encodeURIComponent(theme)}&page=${encodeURIComponent(page)}`);
            const data=await response.json();

            if(data.success&&data.data) {
                this.renderPageSections(data.data,theme,page);
            } else {
                debugLog('Failed to load page content:',data.message||'Unknown error','error');
                this.showAlert('Failed to load page content','error');
            }
        } catch(error) {
            debugLog('Failed to load page content:',error,'error');
            this.showAlert('Error loading page: '+error.message,'error');
        }
    }

    renderPageSections(pageData,theme,page) {
        const container=document.getElementById('page-sections');
        if(!container) return;

        // Clear existing image uploads for this page
        this.imageUploads.clear();

        // Destroy existing CKEditor instances before rendering new content
        this.destroyCKEditor();

        // Store the full page data for access during saving
        this.currentPageData=pageData;

        // Debug: Log the page data structure
        debugLog('Page data structure:',{
            hasWidgets: !!pageData.widgets,
            widgetsLength: pageData.widgets?.length||0,
            hasGrids: !!pageData.grids,
            gridsLength: pageData.grids?.length||0,
            hasSections: !!pageData.sections,
            sectionsLength: pageData.sections?.length||0
        });

        // Handle both old format (sections) and new format (widgets/grids)
        if(pageData.widgets||pageData.grids) {
            // New format with widgets and/or grids
            debugLog('Rendering widgets and grids format');
            this.renderWidgetsAndGrids(pageData,theme,page);
        } else if(pageData.sections&&pageData.sections.length>0) {
            // Old format with sections
            debugLog('Rendering legacy sections format');
            this.renderLegacySections(pageData,theme,page);
        } else {
            // No content available
            debugLog('Rendering empty state');
            this.renderEmptyState(page);
        }

        // Setup image previews for existing images
        this.setupImagePreviews();

        // Store existing image URLs in imageUploads Map
        document.querySelectorAll('.image-input').forEach(input => {
            const upload=input.closest('.image-upload');
            if(upload) {
                const existingImg=upload.querySelector('.image-preview');
                if(existingImg&&existingImg.src&&!existingImg.src.startsWith('data:')) {
                    const uploadKey=input.dataset.section+'.'+input.dataset.field;
                    debugLog(`Loading existing image for key ${uploadKey}:`,existingImg.src);
                    this.imageUploads.set(uploadKey,existingImg.src);
                }
            }
        });
        debugLog('Final imageUploads Map after loading:',Array.from(this.imageUploads.entries()));

        // Make all image uploads clickable
        document.querySelectorAll('.image-upload').forEach(upload => {
            this.makeImageUploadClickable(upload);
        });

        // Setup range input value updates
        this.setupRangeInputs();

        // Initialize CKEditor instances
        setTimeout(() => {
            this.initializeCKEditor();
        },100);
    }

    setupRangeInputs() {
        // Update range value displays
        document.querySelectorAll('.form-range').forEach(range => {
            const valueDisplay=range.parentElement.querySelector('.range-value');
            if(valueDisplay) {
                // Update on input
                range.addEventListener('input',(e) => {
                    valueDisplay.textContent=e.target.value+'%';
                });
            }
        });
    }

    renderWidgetsAndGrids(pageData,theme,page) {
        const container=document.getElementById('page-sections');
        let html='';

        debugLog('renderWidgetsAndGrids called with:',{
            gridsCount: pageData.grids?.length||0,
            widgetsCount: pageData.widgets?.length||0
        });

        // Render widgets section (content) - First
        if(pageData.widgets&&pageData.widgets.length>0) {
            debugLog('Rendering widgets section with',pageData.widgets.length,'widgets');
            html+=`
                <div class="card mb-6 collapsed">
                    <div class="card-header accordion-header" style="cursor: pointer;" onclick="this.parentElement.classList.toggle('collapsed')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-1">
                                    <i class="fas fa-edit me-2"></i>
                                    Content Widgets
                                </h3>
                                <p class="text-muted mb-0 small">Edit text content, images, and other widget elements</p>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                    </div>
                    <div class="card-body accordion-content" style="display: none;">
                        <div class="widgets-editor">
                            ${pageData.widgets.map((widget,index) => this.renderWidgetSection(widget,index)).join('')}
                        </div>
                    </div>
                </div>
            `;
        } else {
            debugLog('No widgets to render or widgets array is empty');
        }

        // Render grids section (layout/background) - Second
        if(pageData.grids&&pageData.grids.length>0) {
            debugLog('Rendering grids section with',pageData.grids.length,'grids');
            html+=`
                <div class="card mb-6 collapsed">
                    <div class="card-header accordion-header" style="cursor: pointer;" onclick="this.parentElement.classList.toggle('collapsed')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-1">
                                    <i class="fas fa-th-large me-2"></i>
                                    Layout & Background Settings
                                </h3>
                                <p class="text-muted mb-0 small">Configure section backgrounds, colors, and spacing</p>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                    </div>
                    <div class="card-body accordion-content" style="display: none;">
                        <div class="grid-editor">
                            ${pageData.grids.map((grid,index) => this.renderGridSection(grid,index)).join('')}
                        </div>
                    </div>
                </div>
            `;
        } else {
            debugLog('No grids to render or grids array is empty');
        }

        debugLog('Setting container innerHTML with html length:',html.length);
        container.innerHTML=html;
    }

    renderLegacySections(pageData,theme,page) {
        const container=document.getElementById('page-sections');
        container.innerHTML=pageData.sections.map((section,index) =>
            this.renderPageSection(section,index,theme,page)
        ).join('');
    }

    renderEmptyState(page) {
        const container=document.getElementById('page-sections');
        const pageDisplayName=page.charAt(0).toUpperCase()+page.slice(1).replace(/-/g,' ');
        let messageContent='';

        if(page==='get-involved') {
            messageContent=`
                <div class="text-center py-8">
                    <i class="fas fa-hands-helping fa-3x text-muted mb-4"></i>
                    <h3 class="text-muted mb-2">Get Involved Page</h3>
                    <p class="text-muted mb-4">This page is configured for dynamic form deployment.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Contact forms, volunteer forms, and other interactive elements will be automatically added during the deployment process.
                    </div>
                </div>
            `;
        } else {
            messageContent=`
                <div class="text-center py-8">
                    <i class="fas fa-layer-group fa-3x text-muted mb-4"></i>
                    <h3 class="text-muted mb-2">No Sections Available</h3>
                    <p class="text-muted mb-4">The "${pageDisplayName}" page currently has no content sections to edit.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Dynamic content and forms will be added automatically during deployment if configured.
                    </div>
                </div>
            `;
        }

        container.innerHTML=`
            <div class="empty-sections-message">
                ${messageContent}
            </div>
        `;
    }

    renderPageSection(section,index,theme,page) {
        let sectionHtml=`
            <div class="card section-card" data-section="${index}">
                <div class="card-header">
                    <h3 class="card-title">Section ${index+1} - ${this.formatSectionType(section.type)}</h3>
                </div>
                <div class="card-body">
        `;

        switch(section.type) {
            case 'text_editor':
                sectionHtml+=this.renderTextSection(section,index);
                break;
            case 'image':
                sectionHtml+=this.renderImageSection(section,index);
                break;
            case 'features':
                sectionHtml+=this.renderFeaturesSection(section,index);
                break;
            case 'hero':
                sectionHtml+=this.renderHeroSection(section,index);
                break;
            default:
                sectionHtml+=`<p class="text-muted">Unsupported section type: ${section.type}</p>`;
        }

        sectionHtml+=`
                </div>
            </div>
        `;

        return sectionHtml;
    }

    renderTextSection(section,index) {
        const editable=section.editable||{};

        // Use raw_text if available to preserve original HTML formatting, 
        // otherwise fall back to cleaned text
        const textContent=editable.raw_text||editable.text||'';

        let html=`
            <div class="form-group">
                <label class="form-label">Content</label>
                <textarea class="form-textarea ckeditor" rows="6" 
                    id="editor-${index}-text"
                    data-section="${index}" data-field="text">${textContent}</textarea>
            </div>
        `;

        // Add button fields if buttons exist
        if(editable.buttons&&editable.buttons.length>0) {
            html+=`<h4 class="mt-4 mb-2">Buttons</h4>`;
            editable.buttons.forEach((button,btnIndex) => {
                html+=`
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Button Text</label>
                            <input type="text" class="form-input" 
                                data-section="${index}" data-field="buttons.${btnIndex}.text" 
                                value="${button.text||''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Button URL</label>
                            <input type="text" class="form-input" 
                                data-section="${index}" data-field="buttons.${btnIndex}.url" 
                                value="${button.url||''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Button Style</label>
                            <select class="form-select" 
                                data-section="${index}" data-field="buttons.${btnIndex}.style">
                                <option value="primary" ${button.style==='primary'? 'selected':''}>Primary</option>
                                <option value="secondary" ${button.style==='secondary'? 'selected':''}>Secondary</option>
                            </select>
                        </div>
                    </div>
                `;
            });
        }

        return html;
    }

    renderImageSection(section,index) {
        const editable=section.editable||{};

        return `
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Image Upload</label>
                    <div class="image-upload ${editable.image_fallback? 'has-image':''}" 
                         data-section="${index}" data-field="image">
                        <input type="file" class="file-input image-input" accept="image/*"
                               data-section="${index}" data-field="image">
                        ${editable.image_fallback?
                `<img src="${editable.image_fallback}" class="image-preview" alt="Preview">`:
                `<div class="image-upload-text">Click to upload or drag image here</div>`
            }
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label class="form-label">Alt Text</label>
                        <input type="text" class="form-input" 
                            data-section="${index}" data-field="alt_text" 
                            value="${editable.alt_text||''}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-input" 
                            data-section="${index}" data-field="title" 
                            value="${editable.title||''}">
                    </div>
                </div>
            </div>
        `;
    }

    renderFeaturesSection(section,index) {
        const features=section.editable?.features||[];

        let html='<div class="features-container">';

        features.forEach((feature,featureIndex) => {
            html+=`
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Feature ${featureIndex+1}</h4>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-input" 
                                        data-section="${index}" data-field="features.${featureIndex}.title" 
                                        value="${feature.title||''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-textarea ckeditor" rows="3" 
                                        id="editor-${index}-features-${featureIndex}-text"
                                        data-section="${index}" data-field="features.${featureIndex}.text">${feature.raw_text||feature.text||''}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Link URL</label>
                                    <input type="text" class="form-input" 
                                        data-section="${index}" data-field="features.${featureIndex}.url" 
                                        value="${feature.url||''}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Feature Image</label>
                                <div class="image-upload ${feature.image? 'has-image':''}" 
                                     data-section="${index}" data-field="features.${featureIndex}.image">
                                    <input type="file" class="file-input image-input" accept="image/*"
                                           data-section="${index}" data-field="features.${featureIndex}.image">
                                    ${feature.image?
                    `<img src="${feature.image}" class="image-preview" alt="Preview">`:
                    `<div class="image-upload-text">Click to upload feature image</div>`
                }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html+='</div>';
        return html;
    }

    renderHeroSection(section,index) {
        const frames=section.editable?.frames||[];
        let html='<div class="hero-container">';

        frames.forEach((frame,frameIndex) => {
            html+=`
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Hero Slide ${frameIndex+1}</h4>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label class="form-label">Background Image</label>
                                <div class="image-upload ${frame.background_image_fallback? 'has-image':''}" 
                                     data-section="${index}" data-field="frames.${frameIndex}.background_image_fallback">
                                    <input type="file" class="file-input image-input" accept="image/*"
                                           data-section="${index}" data-field="frames.${frameIndex}.background_image_fallback">
                                    ${frame.background_image_fallback?
                    `<img src="${frame.background_image_fallback}" class="image-preview" alt="Hero Background">`:
                    `<div class="image-upload-text">Click to upload hero background image</div>`
                }
                                </div>
                            </div>
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-input" 
                                        data-section="${index}" data-field="frames.${frameIndex}.title" 
                                        value="${frame.title||''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Content</label>
                                    <textarea class="form-textarea ckeditor" rows="4" 
                                        id="editor-${index}-hero-${frameIndex}-text"
                                        data-section="${index}" data-field="frames.${frameIndex}.text">${frame.raw_text||frame.text||''}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        ${frame.buttons&&frame.buttons.length>0? `
                            <h4 class="mt-4 mb-2">Buttons</h4>
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                ${frame.buttons.map((button,btnIndex) => `
                                    <div class="form-group">
                                        <label class="form-label">Button Text</label>
                                        <input type="text" class="form-input" 
                                            data-section="${index}" data-field="frames.${frameIndex}.buttons.${btnIndex}.text" 
                                            value="${button.text||''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Button URL</label>
                                        <input type="text" class="form-input" 
                                            data-section="${index}" data-field="frames.${frameIndex}.buttons.${btnIndex}.url" 
                                            value="${button.url||''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Button Style</label>
                                        <select class="form-select" 
                                            data-section="${index}" data-field="frames.${frameIndex}.buttons.${btnIndex}.style">
                                            <option value="primary" ${button.style==='primary'? 'selected':''}>Primary</option>
                                            <option value="secondary" ${button.style==='secondary'? 'selected':''}>Secondary</option>
                                        </select>
                                    </div>
                                `).join('')}
                            </div>
                        ` :''}
                    </div>
                </div>
            `;
        });

        html+='</div>';
        return html;
    }

    renderGridSection(grid,index) {
        const style=grid.style||{};
        const gridDisplayName=this.getGridDisplayName(index);

        return `
            <div class="grid-section card mb-4" data-grid="${index}">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        ${gridDisplayName}
                    </h4>
                    <small class="text-muted">Cells: ${grid.cells||1}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Background Image -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-image me-1"></i>
                                Background Image
                            </label>
                            <div class="image-upload ${style.background_image_attachment_fallback? 'has-image':''}" 
                                 data-section="grid" data-field="grids.${index}.style.background_image_attachment_fallback">
                                <input type="file" class="file-input image-input" accept="image/*"
                                       data-section="grid" data-field="grids.${index}.style.background_image_attachment_fallback">
                                ${style.background_image_attachment_fallback?
                `<img src="${style.background_image_attachment_fallback}" class="image-preview" alt="Background">`:
                `<div class="image-upload-text">
                                        <i class="fas fa-cloud-upload-alt mb-2"></i>
                                        <div>Click to upload background image</div>
                                        <small class="text-muted">JPG, PNG, WebP recommended</small>
                                    </div>`
            }
                            </div>
                        </div>

                        <!-- Background Color & Opacity -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Background Color</label>
                            <input type="color" class="form-control form-control-color" 
                                   data-section="grid" data-field="grids.${index}.style.background" 
                                   value="${style.background||'#ffffff'}">
                            
                            <label class="form-label mt-2">Opacity (%)</label>
                            <input type="range" class="form-range" min="0" max="100" 
                                   data-section="grid" data-field="grids.${index}.style.background_image_opacity" 
                                   value="${style.background_image_opacity||100}">
                            <span class="range-value">${style.background_image_opacity||100}%</span>
                        </div>

                        <!-- Display Mode & Image Size -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Display Mode</label>
                            <select class="form-select" 
                                    data-section="grid" data-field="grids.${index}.style.background_display">
                                <option value="cover" ${style.background_display==='cover'? 'selected':''}>Cover</option>
                                <option value="contain" ${style.background_display==='contain'? 'selected':''}>Contain</option>
                                <option value="tile" ${style.background_display==='tile'? 'selected':''}>Tile</option>
                                <option value="center" ${style.background_display==='center'? 'selected':''}>Center</option>
                            </select>
                            
                            <label class="form-label mt-2">Image Size</label>
                            <select class="form-select" 
                                    data-section="grid" data-field="grids.${index}.style.background_image_size">
                                <option value="full" ${style.background_image_size==='full'? 'selected':''}>Full</option>
                                <option value="large" ${style.background_image_size==='large'? 'selected':''}>Large</option>
                                <option value="medium" ${style.background_image_size==='medium'? 'selected':''}>Medium</option>
                                <option value="thumbnail" ${style.background_image_size==='thumbnail'? 'selected':''}>Thumbnail</option>
                            </select>
                        </div>
                    </div>

                    <!-- Spacing and Layout -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-expand-arrows-alt me-1"></i>
                                Padding
                            </label>
                            <input type="text" class="form-control" 
                                   data-section="grid" data-field="grids.${index}.style.padding" 
                                   value="${style.padding||''}"
                                   placeholder="e.g., 8% 0% 8% 0%">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-mobile-alt me-1"></i>
                                Mobile Padding
                            </label>
                            <input type="text" class="form-control" 
                                   data-section="grid" data-field="grids.${index}.style.mobile_padding" 
                                   value="${style.mobile_padding||''}"
                                   placeholder="e.g., 50vh 0vh 15vh 0vh">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gutter</label>
                            <input type="text" class="form-control" 
                                   data-section="grid" data-field="grids.${index}.style.gutter" 
                                   value="${style.gutter||''}"
                                   placeholder="e.g., 8%">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bottom Margin</label>
                            <input type="text" class="form-control" 
                                   data-section="grid" data-field="grids.${index}.style.bottom_margin" 
                                   value="${style.bottom_margin||''}"
                                   placeholder="e.g., 0px">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Row Stretch</label>
                            <select class="form-select" 
                                    data-section="grid" data-field="grids.${index}.style.row_stretch">
                                <option value="full" ${style.row_stretch==='full'? 'selected':''}>Full</option>
                                <option value="full-width-stretch" ${style.row_stretch==='full-width-stretch'? 'selected':''}>Full Width Stretch</option>
                                <option value="contained" ${style.row_stretch==='contained'? 'selected':''}>Contained</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <!-- Spacer for alignment -->
                        </div>
                    </div>

                    <!-- Advanced Options (Collapsible) -->
                    <div class="advanced-options mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" 
                                data-bs-toggle="collapse" data-bs-target="#advanced-grid-${index}">
                            <i class="fas fa-cog me-1"></i>Advanced Options
                        </button>
                        <div class="collapse" id="advanced-grid-${index}">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cell Alignment</label>
                                    <select class="form-select" 
                                            data-section="grid" data-field="grids.${index}.style.cell_alignment">
                                        <option value="flex-start" ${style.cell_alignment==='flex-start'? 'selected':''}>Start</option>
                                        <option value="flex-end" ${style.cell_alignment==='flex-end'? 'selected':''}>End</option>
                                        <option value="center" ${style.cell_alignment==='center'? 'selected':''}>Center</option>
                                        <option value="stretch" ${style.cell_alignment==='stretch'? 'selected':''}>Stretch</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" 
                                               data-section="grid" data-field="grids.${index}.style.full_height" 
                                               ${style.full_height? 'checked':''}>
                                        <label class="form-check-label">Full Height</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <!-- Spacer for alignment -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderWidgetSection(widget,index) {
        const widgetType=this.getWidgetType(widget);
        const widgetDisplayName=this.getWidgetDisplayName(widget,index);

        return `
            <div class="widget-section card mb-4" data-widget="${index}">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-puzzle-piece me-2"></i>
                        ${widgetDisplayName}
                    </h4>
                    <span class="badge bg-secondary">${widgetType}</span>
                </div>
                <div class="card-body">
                    ${this.renderWidgetContent(widget,index)}
                </div>
            </div>
        `;
    }

    renderWidgetContent(widget,index) {
        const widgetClass=widget.panels_info?.class||'';

        if(widgetClass.includes('Editor_Widget')) {
            return this.renderEditorWidget(widget,index);
        } else if(widgetClass.includes('Image_Widget')) {
            return this.renderImageWidget(widget,index);
        } else if(widgetClass.includes('Features_Widget')) {
            return this.renderFeaturesWidget(widget,index);
        } else {
            return this.renderGenericWidget(widget,index);
        }
    }

    renderEditorWidget(widget,index) {
        return `
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-edit me-1"></i>
                    Content
                </label>
                <textarea class="form-textarea ckeditor" rows="6" 
                          id="editor-widget-${index}"
                          data-section="widget" data-field="widgets.${index}.text">${widget.text||''}</textarea>
            </div>
            ${widget.title? `
                <div class="form-group mt-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" 
                           data-section="widget" data-field="widgets.${index}.title" 
                           value="${widget.title||''}">
                </div>
            ` :''}
        `;
    }

    renderImageWidget(widget,index) {
        return `
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-image me-1"></i>
                        Widget Image
                    </label>
                    <div class="image-upload ${widget.image_fallback? 'has-image':''}" 
                         data-section="widget" data-field="widgets.${index}.image_fallback">
                        <input type="file" class="file-input image-input" accept="image/*"
                               data-section="widget" data-field="widgets.${index}.image_fallback">
                        ${widget.image_fallback?
                `<img src="${widget.image_fallback}" class="image-preview" alt="Widget Image">`:
                `<div class="image-upload-text">
                                <i class="fas fa-cloud-upload-alt mb-2"></i>
                                <div>Click to upload image</div>
                            </div>`
            }
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Alt Text</label>
                        <input type="text" class="form-control" 
                               data-section="widget" data-field="widgets.${index}.alt" 
                               value="${widget.alt||''}">
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" 
                               data-section="widget" data-field="widgets.${index}.title" 
                               value="${widget.title||''}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Link URL</label>
                        <input type="text" class="form-control" 
                               data-section="widget" data-field="widgets.${index}.url" 
                               value="${widget.url||''}">
                    </div>
                </div>
            </div>
        `;
    }

    renderFeaturesWidget(widget,index) {
        const features=widget.features||[];

        return `
            <div class="features-widget">
                <div class="form-group mb-4">
                    <label class="form-label">Per Row</label>
                    <select class="form-select" 
                            data-section="widget" data-field="widgets.${index}.per_row">
                        <option value="1" ${widget.per_row==1? 'selected':''}>1</option>
                        <option value="2" ${widget.per_row==2? 'selected':''}>2</option>
                        <option value="3" ${widget.per_row==3? 'selected':''}>3</option>
                        <option value="4" ${widget.per_row==4? 'selected':''}>4</option>
                    </select>
                </div>
                
                <h5 class="mb-3">Features</h5>
                ${features.map((feature,featureIndex) => `
                    <div class="feature-item card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Feature ${featureIndex+1}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Feature Image</label>
                                    <div class="image-upload ${feature.icon_image_fallback? 'has-image':''}" 
                                         data-section="widget" data-field="widgets.${index}.features.${featureIndex}.icon_image_fallback">
                                        <input type="file" class="file-input image-input" accept="image/*"
                                               data-section="widget" data-field="widgets.${index}.features.${featureIndex}.icon_image_fallback">
                                        ${feature.icon_image_fallback?
                `<img src="${feature.icon_image_fallback}" class="image-preview" alt="Feature Image">`:
                `<div class="image-upload-text">
                                                <i class="fas fa-cloud-upload-alt mb-2"></i>
                                                <div>Click to upload</div>
                                            </div>`
            }
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" 
                                               data-section="widget" data-field="widgets.${index}.features.${featureIndex}.title" 
                                               value="${feature.title||''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-textarea" rows="3" 
                                                  data-section="widget" data-field="widgets.${index}.features.${featureIndex}.text">${feature.text||''}</textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">Link Text</label>
                                            <input type="text" class="form-control" 
                                                   data-section="widget" data-field="widgets.${index}.features.${featureIndex}.more_text" 
                                                   value="${feature.more_text||''}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Link URL</label>
                                            <input type="text" class="form-control" 
                                                   data-section="widget" data-field="widgets.${index}.features.${featureIndex}.more_url" 
                                                   value="${feature.more_url||''}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderGenericWidget(widget,index) {
        return `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Widget Type:</strong> ${widget.panels_info?.class||'Unknown'}
                <br>
                <small class="text-muted">This widget type is not yet supported in the visual editor. You can edit it directly in the JSON file if needed.</small>
            </div>
            <details class="mt-3">
                <summary class="btn btn-sm btn-outline-secondary">View Raw Data</summary>
                <pre class="mt-2 p-3 bg-light border rounded" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(widget,null,2)}</pre>
            </details>
        `;
    }

    getGridDisplayName(index) {
        const gridNames=[
            'Hero Section',
            'About Section',
            'Services Section',
            'News Section',
            'CTA Section',
            'Stats Section',
            'Footer Section'
        ];
        return gridNames[index]||`Section ${index+1}`;
    }

    getWidgetType(widget) {
        const className=widget.panels_info?.class||'';
        if(className.includes('Editor_Widget')) return 'Text Editor';
        if(className.includes('Image_Widget')) return 'Image';
        if(className.includes('Features_Widget')) return 'Features';
        return 'Unknown';
    }

    getWidgetDisplayName(widget,index) {
        const type=this.getWidgetType(widget);
        if(widget.title) {
            return `${widget.title} (${type})`;
        }
        return `Widget ${index+1} - ${type}`;
    }

    initializeCKEditor() {
        // Check if ClassicEditor is available
        if(typeof ClassicEditor==='undefined') {
            debugLog('CKEditor ClassicEditor not available, falling back to plain textareas','warn');
            return;
        }

        // Destroy existing editors first to prevent conflicts
        this.destroyCKEditor();

        // Initialize CKEditor for all textarea elements with ckeditor class
        const textareas=document.querySelectorAll('.ckeditor');

        textareas.forEach(async (textarea) => {
            // Skip if already has an editor
            if(this.ckEditorInstances.has(textarea.id)) {
                return;
            }

            try {
                // Decode HTML entities in the textarea content before creating editor
                const content=this.decodeHtmlEntities(textarea.value);
                textarea.value=content;

                // Create CKEditor instance
                const editor=await ClassicEditor.create(textarea,{
                    toolbar: [
                        'heading','|',
                        'bold','italic','underline','|',
                        'link','bulletedList','numberedList','|',
                        'outdent','indent','|',
                        'blockQuote','insertTable','|',
                        'sourceEditing','|',
                        'undo','redo'
                    ],
                    heading: {
                        options: [
                            {model: 'paragraph',title: 'Paragraph',class: 'ck-heading_paragraph'},
                            {model: 'heading1',view: 'h1',title: 'Heading 1',class: 'ck-heading_heading1'},
                            {model: 'heading2',view: 'h2',title: 'Heading 2',class: 'ck-heading_heading2'},
                            {model: 'heading3',view: 'h3',title: 'Heading 3',class: 'ck-heading_heading3'}
                        ]
                    },
                    link: {
                        addTargetToExternalLinks: true
                    },
                    table: {
                        contentToolbar: ['tableColumn','tableRow','mergeTableCells']
                    },
                    htmlSupport: {
                        allow: [
                            {
                                name: 'img',
                                attributes: {
                                    style: true,
                                    class: true,
                                    width: true,
                                    height: true,
                                    'data-*': true,
                                    src: true,
                                    alt: true
                                }
                            },
                            {
                                name: 'figure',
                                attributes: {
                                    style: true,
                                    class: true
                                }
                            },
                            {
                                name: 'div',
                                attributes: {
                                    style: true,
                                    class: true
                                }
                            },
                            {
                                name: 'p',
                                attributes: {
                                    style: true,
                                    class: true
                                }
                            },
                            {
                                name: 'span',
                                attributes: {
                                    style: true,
                                    class: true
                                }
                            }
                        ]
                    },
                    sourceEditing: {
                        allowCollaborationFeatures: true
                    }
                });

                // Store editor instance
                this.ckEditorInstances.set(textarea.id,editor);

                // Auto-save content when editor changes
                editor.model.document.on('change:data',() => {
                    const formElement=textarea.closest('form');
                    if(formElement) {
                        this.markFormDirty(formElement);
                    }
                });

                debugLog('CKEditor initialized for',textarea.id);

            } catch(error) {
                debugLog('Error initializing CKEditor for',textarea.id,error,'error');
                // Show the textarea as fallback
                textarea.style.display='block';
            }
        });
    }

    // Helper function to decode HTML entities
    decodeHtmlEntities(str) {
        const textarea=document.createElement('textarea');
        textarea.innerHTML=str;
        return textarea.value;
    }

    destroyCKEditor() {
        // Destroy all existing CKEditor instances
        for(const [id,editor] of this.ckEditorInstances) {
            try {
                if(editor&&typeof editor.destroy==='function') {
                    editor.destroy();
                }
            } catch(error) {
                debugLog('Error destroying CKEditor instance:',id,error,'error');
            }
        }
        this.ckEditorInstances.clear();
    }

    setupImagePreviews() {
        const imageInputs=document.querySelectorAll('.image-input');
        imageInputs.forEach(input => {
            input.addEventListener('change',(e) => {
                this.handleImagePreview(e.target);
            });
        });
    }

    handleImagePreview(input) {
        const file=input.files[0];
        if(!file) return;

        // Store reference to upload container before starting FileReader
        const upload=input.closest('.image-upload');
        if(!upload) {
            debugLog('Could not find parent .image-upload element','error');
            return;
        }

        const reader=new FileReader();
        reader.onload=(e) => {
            // Use the stored upload reference instead of trying to find it again
            upload.classList.add('has-image');

            upload.innerHTML=`
                <input type="file" class="file-input image-input" accept="image/*"
                       data-section="${input.dataset.section}" data-field="${input.dataset.field}">
                <img src="${e.target.result}" class="image-preview" alt="Preview">
            `;

            // Re-attach event listeners
            const newInput=upload.querySelector('.image-input');
            if(newInput) {
                newInput.addEventListener('change',(e) => {
                    this.handleImagePreview(e.target);
                });
            }

            // Make the container clickable again using our dedicated method
            this.makeImageUploadClickable(upload);
        };

        reader.readAsDataURL(file);
    }

    async handleImageUpload(input) {
        const file=input.files[0];
        if(!file) return;

        // First update the preview immediately for better user experience
        this.handleImagePreview(input);

        const formData=new FormData();
        formData.append('image',file);
        formData.append('action','upload_image');

        // Store reference to upload container
        const upload=input.closest('.image-upload');
        if(!upload) {
            debugLog('Could not find parent .image-upload element for file input','error');
        }

        try {
            // Don't show loading state to avoid "Uploading..." text
            // We already have the preview, so user experience is good without it

            const response=await fetch('',{
                method: 'POST',
                body: formData
            });

            const result=await response.json();

            if(result.success) {
                // Update the image field with the uploaded image URL
                const uploadKey=input.dataset.section+'.'+input.dataset.field;
                debugLog(`Image upload successful. Storing URL for key ${uploadKey}:`,result.data.url);
                this.imageUploads.set(uploadKey,result.data.url);

                // Update the preview image with the actual URL instead of the data URL
                if(upload) {
                    const previewImg=upload.querySelector('.image-preview');
                    if(previewImg) {
                        previewImg.src=result.data.url;
                        debugLog(`Updated preview image src to:`,result.data.url);
                    }
                }

                this.showAlert('Image uploaded successfully!','success');
            } else {
                this.showAlert(result.message||'Failed to upload image','error');
            }
        } catch(error) {
            debugLog('Failed to upload image:',error,'error');
            this.showAlert('Failed to upload image','error');
        } finally {
            // Ensure any 'uploading' class is removed
            if(upload) {
                upload.classList.remove('uploading');
            }
        }
    }

    async savePageContent() {
        // Get theme from the theme select element
        const themeSelect=document.getElementById('page-theme-select');
        if(!themeSelect||!themeSelect.value) {
            this.showAlert('Please select a theme','error');
            return;
        }

        // Get active page tab (which has been selected by user)
        // First try to find tab with page-selected class, then fall back to active class
        const activeTab=document.querySelector('#page-tabs .tab-link.page-selected')||
            document.querySelector('#page-tabs .tab-link.active');
        if(!activeTab) {
            this.showAlert('Please select a page','error');
            return;
        }

        const theme=themeSelect.value;
        const page=activeTab.dataset.page;

        // Start with the current page data structure
        let pageData=JSON.parse(JSON.stringify(this.currentPageData||{}));

        // Handle new format (widgets and grids) vs old format (sections)
        if(document.querySelector('.grid-section')||document.querySelector('.widget-section')) {
            // New format: collect grids and widgets
            pageData=this.collectNewFormatData(pageData);
        } else {
            // Legacy format: collect sections
            pageData=this.collectLegacyFormatData(pageData);
        }

        try {
            const response=await fetch('?action=save_page_content',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    theme: theme,
                    page: page,
                    data: pageData
                })
            });

            const result=await response.json();

            if(result.success) {
                this.showAlert('Page content saved successfully!','success');
            } else {
                this.showAlert(result.message||'Failed to save page content','error');
            }
        } catch(error) {
            debugLog('Failed to save page content:',error,'error');
            this.showAlert('Failed to save page content','error');
        }
    }

    collectNewFormatData(pageData) {
        // Collect grid data
        if(pageData.grids) {
            const gridSections=document.querySelectorAll('.grid-section');
            gridSections.forEach((gridSection,index) => {
                if(pageData.grids[index]) {
                    const inputs=gridSection.querySelectorAll('input, textarea, select');

                    inputs.forEach(input => {
                        const field=input.dataset.field;
                        if(field&&field.startsWith('grids.')) {
                            let value=this.getInputValue(input);
                            this.setNestedValue(pageData,field,value);
                        }
                    });
                }
            });
        }

        // Collect widget data
        if(pageData.widgets) {
            const widgetSections=document.querySelectorAll('.widget-section');
            widgetSections.forEach((widgetSection,index) => {
                if(pageData.widgets[index]) {
                    const inputs=widgetSection.querySelectorAll('input, textarea, select');

                    inputs.forEach(input => {
                        const field=input.dataset.field;
                        if(field&&field.startsWith('widgets.')) {
                            let value=this.getInputValue(input);
                            this.setNestedValue(pageData,field,value);
                        }
                    });
                }
            });
        }

        return pageData;
    }

    collectLegacyFormatData(pageData) {
        // Legacy format: collect sections
        const sections=[];
        const sectionCards=document.querySelectorAll('.section-card');

        sectionCards.forEach((card,index) => {
            const sectionData={};
            const inputs=card.querySelectorAll('input, textarea, select');

            inputs.forEach(input => {
                const field=input.dataset.field;
                if(field) {
                    let value=this.getInputValue(input);
                    this.setNestedValue(sectionData,field,value);
                }
            });

            sections.push(sectionData);
        });

        return {sections};
    }

    getInputValue(input) {
        let value=input.value;

        // Get content from CKEditor if it exists
        if(input.classList.contains('ckeditor')&&this.ckEditorInstances.has(input.id)) {
            const editor=this.ckEditorInstances.get(input.id);
            if(editor) {
                value=editor.getData();
            }
        }

        // Handle checkboxes
        if(input.type==='checkbox') {
            value=input.checked;
        }

        // Handle file inputs
        if(input.type==='file') {
            // Use uploaded image URL if available, regardless of whether files are selected
            const uploadKey=input.dataset.section+'.'+input.dataset.field;
            debugLog(`Checking file input for field: ${input.dataset.field}, uploadKey: ${uploadKey}`);
            debugLog('Available imageUploads:',Array.from(this.imageUploads.entries()));

            if(this.imageUploads.has(uploadKey)) {
                value=this.imageUploads.get(uploadKey);
                debugLog(`Found uploaded image for ${uploadKey}:`,value);
            }
            // If no uploaded image is available and no file is selected, keep existing value or set empty
            else if(!input.files[0]) {
                // Check if there's an existing image preview we can extract the URL from
                const upload=input.closest('.image-upload');
                if(upload) {
                    const existingImg=upload.querySelector('.image-preview');
                    if(existingImg&&existingImg.src&&!existingImg.src.startsWith('data:')) {
                        value=existingImg.src;
                        debugLog(`Extracted image URL from preview for ${uploadKey}:`,value);
                    } else {
                        value=''; // No image selected or uploaded
                        debugLog(`No valid image found for ${uploadKey}, setting empty`);
                    }
                }
            }
        }

        return value;
    }

    async loadDeployment() {
        this.pollDeploymentStatus();
        await this.loadDeploymentHistory();
        await this.loadDeploymentLogs();
    }

    async handleDeployment(action,step=null) {
        // Check if ClickUp integration is enabled
        const clickUpCheckbox=document.getElementById('clickup-integration-checkbox');
        const isClickUpEnabled=clickUpCheckbox? clickUpCheckbox.checked:true; // Default to true if checkbox not found

        // Validate ClickUp task selection only if integration is enabled
        const taskSelect=document.getElementById('clickup-task-select');
        const selectedTaskId=taskSelect? taskSelect.value:'';

        if(isClickUpEnabled&&!selectedTaskId) {
            this.showAlert('Please select a ClickUp task or disable ClickUp integration to proceed.','error');
            return;
        }

        // Store ClickUp status for later use
        sessionStorage.setItem('clickup_integration_enabled',isClickUpEnabled? 'true':'false');
        if(!isClickUpEnabled) {
            sessionStorage.setItem('clickup_integration_skipped','true');
        } else {
            sessionStorage.removeItem('clickup_integration_skipped');
        }

        // Check if user wants to delete existing site first
        const deleteCheckbox=document.getElementById('delete-existing-site-checkbox');
        const shouldDeleteExisting=deleteCheckbox&&deleteCheckbox.checked;

        if(shouldDeleteExisting&&this.existingSiteIds&&this.existingSiteIds.length>0) {
            // Ensure a company ID is configured (presence check only) before allowing deletion
            const companyInput=document.getElementById('company-id-input');
            const configuredCompanyId=(companyInput&&companyInput.value&&companyInput.value.trim())? companyInput.value.trim():(this.siteConfig?.company||'');
            if(!configuredCompanyId) {
                this.showAlert('Company ID is not set. Please configure the Kinsta Company ID in site settings before deleting existing sites.','error');
                return;
            }
            // Show confirmation dialog
            const siteTitleInput=document.getElementById('deployment-site-title');
            const siteTitle=siteTitleInput? siteTitleInput.value:'the existing site';

            const confirmed=confirm(
                `⚠️ CRITICAL WARNING ⚠️\n\n`+
                `You are about to PERMANENTLY DELETE the existing site "${siteTitle}" from Kinsta.\n\n`+
                `This will:\n`+
                `• Delete all website data\n`+
                `• Remove all files and databases\n`+
                `• Cannot be undone\n\n`+
                `Are you absolutely sure you want to proceed?`
            );

            if(!confirmed) {
                this.showAlert('Deployment cancelled','info');
                return;
            }

            // Second confirmation with detailed site preview
            let confirmationMessage=`╔═══════════════════════════════════════════════════════════╗\n`;
            confirmationMessage+=`║   FINAL CONFIRMATION - PERMANENT SITE DELETION           ║\n`;
            confirmationMessage+=`╚═══════════════════════════════════════════════════════════╝\n\n`;

            // Get company ID and name from config and current validation display
            const companyId=this.siteConfig?.company||'Unknown';
            const companyNameElement=document.getElementById('company-name-text');
            const companyName=companyNameElement?.textContent||null;

            if(companyName&&companyName!=='Unable to validate Company ID'&&companyName!=='Invalid Company ID') {
                confirmationMessage+=`Company: ${companyName}\n`;
                confirmationMessage+=`Company ID: ${companyId}\n\n`;
            } else {
                confirmationMessage+=`Company ID: ${companyId}\n\n`;
            }

            confirmationMessage+=`You are about to PERMANENTLY DELETE:\n\n`;

            // Add details for each site that will be deleted
            if(this.existingSiteDetails&&this.existingSiteDetails.length>0) {
                this.existingSiteDetails.forEach((site,index) => {
                    confirmationMessage+=`┌─────────────────────────────────────────────────────────┐\n`;
                    confirmationMessage+=`│ Site #${index+1}\n`;
                    confirmationMessage+=`├─────────────────────────────────────────────────────────┤\n`;
                    confirmationMessage+=`│ Site ID:       ${site.id||'N/A'}\n`;
                    confirmationMessage+=`│ Name:          ${site.name||'N/A'}\n`;
                    confirmationMessage+=`│ Display Name:  ${site.display_name||'N/A'}\n`;
                    confirmationMessage+=`└─────────────────────────────────────────────────────────┘\n\n`;
                });
            } else {
                confirmationMessage+=`┌─────────────────────────────────────────────────────────┐\n`;
                confirmationMessage+=`│ Site: "${siteTitle}"\n`;
                confirmationMessage+=`└─────────────────────────────────────────────────────────┘\n\n`;
            }

            confirmationMessage+=`⚠️  THIS ACTION CANNOT BE UNDONE!\n`;
            confirmationMessage+=`⚠️  ALL DATA WILL BE PERMANENTLY LOST!\n\n`;
            confirmationMessage+=`─────────────────────────────────────────────────────────────\n`;
            confirmationMessage+=`Click OK to proceed with PERMANENT DELETION\n`;
            confirmationMessage+=`Click Cancel to abort deployment\n`;
            confirmationMessage+=`─────────────────────────────────────────────────────────────`;

            const doubleConfirmed=confirm(confirmationMessage);

            if(!doubleConfirmed) {
                this.showAlert('Deployment cancelled','info');
                return;
            }

            // Proceed with deletion
            try {
                this.showAlert('Deleting existing site from Kinsta...','warning');

                for(const siteId of this.existingSiteIds) {
                    const deleteResponse=await fetch('?action=delete_kinsta_site',{
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            site_id: siteId
                        })
                    });

                    const deleteResult=await deleteResponse.json();

                    if(!deleteResult.success) {
                        throw new Error(deleteResult.message||'Failed to delete existing site');
                    }

                    this.showAlert(`Site ${siteId} deleted successfully. Now starting deployment...`,'success');
                }

                // Clear the checkbox and hide the option after successful deletion
                if(deleteCheckbox) deleteCheckbox.checked=false;
                const deleteOption=document.getElementById('delete-existing-site-option');
                if(deleteOption) deleteOption.style.display='none';

                // Wait a bit for Kinsta to process the deletion, then show deployment starting
                this.showAlert('Site deleted. Starting deployment in 2 seconds...','info');
                await new Promise(resolve => setTimeout(resolve,2000));
                this.showAlert('Starting deployment now...','info');

            } catch(error) {
                this.showAlert(`Failed to delete existing site: ${error.message}`,'error');
                return;
            }
        }

        // Enable deployment overlay immediately
        this.setDeploymentInProgress(true);

        // Track button click time
        this.buttonClickTime=new Date();
        this.deploymentStartTime=this.buttonClickTime;

        // Clear previous timing data and ensure clean state
        this.stepStartTimes.clear();
        this.stepDurations.clear();
        this.realTimeTimers.forEach(timerId => {
            clearInterval(timerId);
            debugLog('Clearing existing timer:',timerId);
        });
        this.realTimeTimers.clear();

        // Reset GitHub Actions flags for new deployment
        this.githubActionsCompleted=false;
        this.githubActionsManuallyUpdated=false;

        debugLog(`Deployment button clicked at: ${this.getISTTime()}`);
        debugLog('All timers cleared for new deployment');

        // Auto-clear logs and temp files before starting deployment
        try {
            this.showAlert('Preparing deployment...','info');

            // Clear logs display first for immediate feedback
            const logOutput=document.getElementById('deployment-logs');
            if(logOutput) {
                logOutput.innerHTML='<div class="text-muted">Starting deployment...</div>';
            }

            // Call reset system in background (without confirmation prompt)
            const resetResponse=await fetch('?action=reset_system',{
                method: 'POST'
            });

            if(!resetResponse.ok) {
                debugLog('Failed to auto-clear logs before deployment','warn');
            }
            else {
                await this.loadDeploymentLogs();
                this.showAlert('Logs cleared','success');
            }

        } catch(error) {
            debugLog('Auto-clear failed, continuing with deployment:',error,'warn');
        }

        let endpoint='?action=deploy';
        let data={};

        if(step) {
            data.step=step;
        }

        // Get selected theme from deployment tab if available
        const deploymentThemeSelect=document.getElementById('deployment-theme-select');
        if(deploymentThemeSelect&&deploymentThemeSelect.value) {
            data.theme=deploymentThemeSelect.value;
        }

        // Get site title from deployment tab if available
        const siteTitleInput=document.getElementById('deployment-site-title');
        if(siteTitleInput&&siteTitleInput.value) {
            data.site_title=siteTitleInput.value;
            data.display_name=this.slugify(siteTitleInput.value);
        }

        // Save ClickUp task ID for tracking and updates
        if(selectedTaskId) {
            data.clickup_task_id=selectedTaskId;
        }

        // Save ClickUp integration status for backend to know whether to post comment
        data.clickup_integration_enabled=isClickUpEnabled;

        try {
            debugLog('Sending deployment request:',data);

            const response=await fetch(endpoint,{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            debugLog('Response status:',response.status);

            if(!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result=await response.json();
            debugLog('Deployment result:',result);

            if(result.success) {
                this.showAlert('Deployment started successfully!','success');
                this.startDeploymentMonitoring();

                // Force immediate status check
                setTimeout(() => {
                    this.pollDeploymentStatus();
                },1000);

            } else {
                this.showAlert(result.message||'Failed to start deployment','error');
                debugLog('Deployment failed:',result,'error');
            }
        } catch(error) {
            debugLog('Deployment request failed:',error,'error');
            this.showAlert(`Failed to start deployment: ${error.message}`,'error');
        }
    }

    async runDeployAgain() {
        // Enable deployment overlay immediately
        this.setDeploymentInProgress(true);

        // Track button click time
        this.buttonClickTime=new Date();
        this.deploymentStartTime=this.buttonClickTime;

        // Clear previous timing data and ensure clean state
        this.stepStartTimes.clear();
        this.stepDurations.clear();
        this.realTimeTimers.forEach(timerId => {
            clearInterval(timerId);
            debugLog('Clearing existing timer:',timerId);
        });
        this.realTimeTimers.clear();

        // Reset GitHub Actions flags for new deployment
        this.githubActionsCompleted=false;
        this.githubActionsManuallyUpdated=false;

        debugLog(`Deploy Again button clicked at: ${this.getISTTime()}`);
        debugLog('All timers cleared for deploy again');

        try {
            debugLog('Sending deploy again request...');

            // Call the deploy-only endpoint - this will only run deploy.sh
            const response=await fetch('?action=deploy_again',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            });

            debugLog('Deploy Again response status:',response.status);

            if(!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result=await response.json();
            debugLog('Deploy Again result:',result);

            if(result.success) {
                this.showAlert('Deployment started with existing credentials!','success');
                this.startDeploymentMonitoring();

                // Force immediate status check
                setTimeout(() => {
                    this.pollDeploymentStatus();
                },1000);

            } else {
                this.showAlert(result.message||'Failed to start deployment','error');
                debugLog('Deploy Again failed:',result,'error');
                this.setDeploymentInProgress(false);
            }
        } catch(error) {
            debugLog('Deploy Again request failed:',error,'error');
            this.showAlert(`Failed to start deployment: ${error.message}`,'error');
            this.setDeploymentInProgress(false);
        }
    }

    async pollDeploymentStatus() {
        try {
            const response=await fetch('?action=deployment_status');
            const data=await response.json();

            if(data.success) {
                this.updateDeploymentStatusDisplay(data.data);

                // If we're on the github-actions step, also check GitHub Actions status
                if(data.data.current_step==='github-actions'||data.data.current_step==='trigger-deploy') {
                    this.startGitHubActionsPolling();
                }

                // If deployment is completed but we're still on github-actions step, check GitHub status once more
                if(data.data.status==='completed'&&data.data.current_step==='github-actions'&&!this.githubActionsCompleted) {
                    debugLog('Deployment completed but GitHub Actions not yet completed, polling GitHub status');
                    this.pollGitHubActionsStatus();
                }

                // Force check GitHub Actions status if we're on that step
                if(data.data.current_step==='github-actions'&&!this.githubActionsCompleted) {
                    debugLog('Currently on GitHub Actions step, ensuring polling is active');
                    this.startGitHubActionsPolling();
                }

                // Continue polling if deployment is running OR if GitHub Actions is still being monitored
                if((data.data.status==='running'&&!this.githubActionsCompleted)||
                    (data.data.current_step==='github-actions'&&!this.githubActionsCompleted)) {
                    this.deploymentPollInterval=setTimeout(() => this.pollDeploymentStatus(),3000);
                } else {
                    // Deployment finished AND GitHub Actions completed (or not applicable), stop polling
                    this.stopAllPolling();
                }
            }
        } catch(error) {
            debugLog('Failed to poll deployment status:',error,'error');
        }
    }

    startGitHubActionsPolling() {
        debugLog('startGitHubActionsPolling called. Current state:',{
            hasInterval: !!this.githubActionsPollInterval,
            isCompleted: this.githubActionsCompleted
        });

        // Don't start multiple polling intervals
        if(this.githubActionsPollInterval||this.githubActionsCompleted) {
            debugLog('Skipping GitHub Actions polling - already running or completed');
            return;
        }

        debugLog('Starting GitHub Actions status polling...');
        // Start polling GitHub Actions status
        this.pollGitHubActionsStatus();
    }

    async pollGitHubActionsStatus() {
        // Don't poll if already completed
        if(this.githubActionsCompleted) {
            debugLog('GitHub Actions already completed, skipping poll');
            return;
        }

        // Don't poll if system was recently reset
        if(this.systemRecentlyReset) {
            debugLog('System recently reset, skipping GitHub Actions poll');
            return;
        }

        debugLog('🔄 Polling GitHub Actions status...');

        try {
            const response=await fetch('?action=github_actions_status');
            const data=await response.json();

            if(data.success) {
                // Enhanced logging for debugging
                debugLog('📡 GitHub Actions Status Response:',{
                    status: data.data.status,
                    github_status: data.data.github_status,
                    github_conclusion: data.data.github_conclusion,
                    run_id: data.data.run_id,
                    message: data.data.message,
                    url: data.data.url,
                    created_at: data.data.created_at
                });

                // Update the UI with GitHub Actions specific information
                this.updateGitHubActionsDisplay(data.data);

                // Check if GitHub Actions has completed (successfully, failed, or cancelled)
                const isCompleted=data.data.status==='completed'||
                    data.data.status==='success'||
                    data.data.status==='failed'||
                    data.data.status==='failure'||
                    data.data.status==='cancelled'||
                    data.data.status==='canceled'||
                    data.data.conclusion==='success'||
                    data.data.conclusion==='failure'||
                    data.data.conclusion==='cancelled'||
                    data.data.github_conclusion==='success'||
                    data.data.github_conclusion==='failure'||
                    data.data.github_conclusion==='cancelled';

                debugLog('🎯 Completion Check:',{
                    status: data.data.status,
                    github_status: data.data.github_status,
                    github_conclusion: data.data.github_conclusion,
                    isCompleted: isCompleted,
                    completedFlag: this.githubActionsCompleted
                });

                if(isCompleted) {
                    debugLog('✅ GitHub Actions completed! Calling handleGitHubActionsCompletion');
                    this.handleGitHubActionsCompletion(data.data);
                } else {
                    // Continue polling if still running/pending
                    debugLog('⏳ GitHub Actions still running, scheduling next poll in 5s');
                    this.githubActionsPollInterval=setTimeout(() => this.pollGitHubActionsStatus(),5000);
                }
            }
        } catch(error) {
            debugLog('❌ Failed to poll GitHub Actions status:',error,'error');
            // Retry on error
            this.githubActionsPollInterval=setTimeout(() => this.pollGitHubActionsStatus(),10000);
        }
    }

    handleGitHubActionsCompletion(githubData) {
        debugLog('handleGitHubActionsCompletion called with data:',githubData);

        // Mark as completed to stop all polling
        this.githubActionsCompleted=true;

        // Clear any pending intervals
        this.stopAllPolling();

        // Show final status popup (only once per deployment session)
        debugLog('About to call showGitHubActionsFinalStatus...');
        this.showGitHubActionsFinalStatus(githubData);

        debugLog('GitHub Actions completed with status:',githubData.status);
    }

    stopAllPolling() {
        // Clear deployment polling
        if(this.deploymentPollInterval) {
            clearTimeout(this.deploymentPollInterval);
            this.deploymentPollInterval=null;
        }

        // Clear GitHub Actions polling
        if(this.githubActionsPollInterval) {
            clearTimeout(this.githubActionsPollInterval);
            this.githubActionsPollInterval=null;
        }

        // Clear real-time log polling
        this.stopRealtimeLogPolling();
    }

    async forceRefreshGitHubStatus() {
        debugLog('🔧 Force refreshing GitHub Actions status...');

        // Show loading indicator
        const refreshBtn=document.querySelector('.github-refresh-btn');
        if(refreshBtn) {
            refreshBtn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled=true;
        }

        // Temporarily disable all protection flags to force checking
        const originalResetFlag=this.systemRecentlyReset;
        const originalCompletedFlag=this.githubActionsCompleted;

        this.systemRecentlyReset=false;
        this.githubActionsCompleted=false;

        try {
            debugLog('🔍 Bypassing protection flags to force GitHub Actions check...');

            // First, let's see what the raw API response looks like
            const response=await fetch('?action=github_actions_status');
            const data=await response.json();

            debugLog('🐛 RAW API RESPONSE:',JSON.stringify(data,null,2));

            if(data.success&&data.data) {
                debugLog('🐛 DETAILED DATA ANALYSIS:',{
                    status: data.data.status,
                    github_status: data.data.github_status,
                    github_conclusion: data.data.github_conclusion,
                    conclusion: data.data.conclusion,
                    message: data.data.message,
                    url: data.data.url,
                    run_id: data.data.run_id,
                    created_at: data.data.created_at
                });

                // Force the display update with the data we got
                this.updateGitHubActionsDisplay(data.data);

                // Manually trigger completion check with more comprehensive criteria
                const isCompleted=data.data.status==='completed'||
                    data.data.status==='success'||
                    data.data.status==='failed'||
                    data.data.status==='failure'||
                    data.data.status==='cancelled'||
                    data.data.status==='canceled'||
                    data.data.conclusion==='success'||
                    data.data.conclusion==='failure'||
                    data.data.conclusion==='cancelled'||
                    data.data.github_status==='completed'||
                    data.data.github_conclusion==='success'||
                    data.data.github_conclusion==='failure'||
                    data.data.github_conclusion==='cancelled';

                debugLog('🐛 COMPLETION CHECK RESULT:',isCompleted);
                debugLog('🐛 INDIVIDUAL CHECKS:',{
                    'status===completed': data.data.status==='completed',
                    'status===success': data.data.status==='success',
                    'github_status===completed': data.data.github_status==='completed',
                    'github_conclusion===success': data.data.github_conclusion==='success',
                    'conclusion===success': data.data.conclusion==='success'
                });

                if(isCompleted) {
                    debugLog('✅ Forcing completion handling...');
                    // Mark as completed before calling the handler
                    this.githubActionsCompleted=true;
                    this.handleGitHubActionsCompletion(data.data);
                } else {
                    debugLog('❌ Not detected as completed. Manual override...');
                    // If all deployment steps are done but GitHub Actions not detected as complete,
                    // let's check if we should force completion
                    if(data.data.run_id) {
                        debugLog('🔧 Forcing completion due to existing run_id');
                        data.data.status='completed';
                        data.data.github_conclusion='success';
                        this.githubActionsCompleted=true;
                        this.handleGitHubActionsCompletion(data.data);
                    }
                }
            }

            // Show success message
            this.showAlert('GitHub Actions status refreshed and analyzed','success');
        } catch(error) {
            debugLog('Failed to refresh GitHub status:',error,'error');
            this.showAlert('Failed to refresh GitHub Actions status','error');

            // Restore original flags on error
            this.systemRecentlyReset=originalResetFlag;
            this.githubActionsCompleted=originalCompletedFlag;
        } finally {
            // Restore refresh button
            if(refreshBtn) {
                refreshBtn.innerHTML='<i class="fas fa-sync-alt"></i>';
                refreshBtn.disabled=false;
            }
        }
    }

    /**
     * Clean uploads: find unused image/video files in uploads and delete them (with confirmation)
     */
    async cleanUnusedUploads() {
        const proceed=confirm('Are you sure you want to delete unused images/videos from the uploads folder? This action is irreversible.');
        if(!proceed) return;

        const btn=document.querySelector('#clean-uploads-btn');
        const originalHtml=btn? btn.innerHTML:null;
        try {
            if(btn) {
                btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Cleaning...';
                btn.disabled=true;
            }

            const response=await fetch('?action=clean_uploads&confirmed=true');
            const data=await response.json();

            if(data.success) {
                const deletedCount=(data.data&&data.data.deleted)? data.data.deleted.length:0;
                const toDeleteCount=(data.data&&data.data.to_delete)? data.data.to_delete.length:0;
                this.showToast(`Clean complete. ${deletedCount} files deleted; ${toDeleteCount} files matched as unused.`,'success');
                debugLog('Clean uploads result',data);
            } else {
                debugLog('Failed to clean uploads',data,'error');
                this.showToast('Failed to clean uploads. Check console for more details.','error');
            }
        } catch(err) {
            debugLog('Clean uploads error',err,'error');
            this.showToast('Error while cleaning uploads','error');
        } finally {
            if(btn) {
                btn.disabled=false;
                if(originalHtml) btn.innerHTML=originalHtml;
            }
        }
    }

    // Debug function to check what's wrong with the run ID
    forceCheckRunId() {
        debugLog('🔧 DEBUG: Checking GitHub run ID issues...');

        fetch('?action=github_actions_status')
            .then(response => response.json())
            .then(data => {
                debugLog('🔧 DEBUG: GitHub Actions API Response:',data);

                if(data.data&&data.data.run_id) {
                    debugLog('🔧 DEBUG: Current run ID being monitored:',data.data.run_id);
                    debugLog('🔧 DEBUG: Run URL:',data.data.url);
                    debugLog('🔧 DEBUG: Is this an old run?',data.data.is_monitoring);

                    // Check if this run is really old
                    if(data.data.created_at) {
                        const runTime=new Date(data.data.created_at);
                        const now=new Date();
                        const minutesAgo=(now-runTime)/(1000*60);
                        debugLog(`🔧 DEBUG: Run created ${minutesAgo.toFixed(1)} minutes ago`);

                        if(minutesAgo>10) {
                            debugLog('🔧 DEBUG: ⚠️ This run is very old! It might be completed but stuck.');
                            debugLog('🔧 DEBUG: Checking recent workflows instead...');

                            // Try to get more recent workflows
                            fetch(`?action=github_actions_logs&run_id=${data.data.run_id}`)
                                .then(response => response.json())
                                .then(logData => {
                                    debugLog('🔧 DEBUG: Workflow logs:',logData);
                                })
                                .catch(error => {
                                    debugLog('🔧 DEBUG: Error fetching logs:',error,'error');
                                });
                        }
                    }
                } else {
                    debugLog('🔧 DEBUG: No run ID found in response');
                }
            })
            .catch(error => {
                debugLog('🔧 DEBUG: Error checking run ID:',error,'error');
            });
    }

    // Function to force clear old GitHub run ID and check fresh
    forceClearOldRunId() {
        debugLog('🔧 CLEAR: Clearing old GitHub run ID and forcing fresh check...');

        // First clear the stored run ID
        fetch('?action=clear_github_run_id')
            .then(response => response.json())
            .then(data => {
                debugLog('🔧 CLEAR: Clear run ID response:',data);

                // Now force a fresh GitHub Actions status check
                return fetch('?action=github_actions_status');
            })
            .then(response => response.json())
            .then(data => {
                debugLog('🔧 CLEAR: Fresh GitHub Actions status:',data);

                if(data.success) {
                    this.updateGitHubActionsDisplay(data.data);

                    // Check if this fresh data shows completion
                    const isCompleted=data.data.status==='completed'||
                        data.data.status==='success'||
                        data.data.github_conclusion==='success';

                    if(isCompleted) {
                        debugLog('🔧 CLEAR: Fresh data shows completion, handling...');
                        this.githubActionsCompleted=true;
                        this.handleGitHubActionsCompletion(data.data);
                    }
                }
            })
            .catch(error => {
                debugLog('🔧 CLEAR: Error:',error,'error');
            });
    }

    showGitHubActionsFinalStatus(githubData) {
        debugLog('showGitHubActionsFinalStatus called with data:',githubData);

        // Check if system was recently reset (prevent modal after reset)
        if(this.systemRecentlyReset) {
            debugLog('System was recently reset, skipping completion modal');
            return;
        }

        // Create a unique identifier for this deployment session
        const currentRunId=githubData.run_id||`session_${Date.now()}`;
        const statusKey=`github_actions_final_${currentRunId}`;

        debugLog('Run ID:',currentRunId,'Status Key:',statusKey);

        // Check if we already showed the final status for this run
        const alreadyShown=localStorage.getItem(statusKey)==='shown';
        if(alreadyShown&&!currentRunId.startsWith('test_')) {
            debugLog('Final status already shown for run',currentRunId);
            return;
        }

        // Mark as shown to prevent duplicate popups
        if(!currentRunId.startsWith('test_')) {
            localStorage.setItem(statusKey,'shown');
        }

        // Create the modern completion modal
        this.showDeploymentCompletionModal(githubData);
    }

    showDeploymentCompletionModal(githubData) {
        // Final safety check - don't show modal if system was recently reset
        if(this.systemRecentlyReset) {
            debugLog('System recently reset, blocking completion modal display');
            return;
        }

        // Remove any existing modal
        const existingModal=document.getElementById('deployment-completion-modal');
        if(existingModal) {
            existingModal.remove();
        }

        // Determine status details with modern design
        let statusIcon,headerClass,statusTitle,statusMessage,actionButtons,statusBadge;
        const totalDuration=this.deploymentStartTime? this.formatDuration(this.deploymentStartTime):null;
        const completedAt=githubData.created_at? new Date(githubData.created_at).toLocaleString():new Date().toLocaleString();

        // Check ClickUp integration status
        const clickUpEnabled=sessionStorage.getItem('clickup_integration_enabled')==='true';
        const clickUpSkipped=sessionStorage.getItem('clickup_integration_skipped')==='true';
        let clickUpStatusHTML='';

        if(clickUpSkipped) {
            clickUpStatusHTML=`
                <div class="alert alert-info" style="margin: 16px 0; padding: 12px 16px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 6px;">
                    <i class="fas fa-info-circle" style="margin-right: 8px; color: #3b82f6;"></i>
                    <strong>ClickUp Integration:</strong> Skipped - No task comment was posted. You can manually update your ClickUp task if needed.
                </div>
            `;
        } else if(clickUpEnabled) {
            clickUpStatusHTML=`
                <div class="alert alert-success" style="margin: 16px 0; padding: 12px 16px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 6px;">
                    <i class="fas fa-check-circle" style="margin-right: 8px; color: #10b981;"></i>
                    <strong>ClickUp Integration:</strong> Deployment details posted to ClickUp task
                </div>
            `;
        }

        switch(githubData.status) {
            case 'completed':
                statusIcon='fas fa-check-circle';
                headerClass='modal-header-green';
                statusTitle='Deployment Successful!';
                statusMessage='Your WordPress site has been deployed successfully and is now live.';
                statusBadge='COMPLETED';
                actionButtons=`
                    <button onclick="window.adminInterface.openSiteUrl('${githubData.site_url||''}')" class="modal-btn modal-btn-primary">
                        <i class="fas fa-external-link-alt"></i>View Live Site
                    </button>
                    <button onclick="window.open('${githubData.url||'#'}', '_blank')" class="modal-btn modal-btn-secondary">
                        <i class="fab fa-github"></i>View Deployment
                    </button>
                    <button onclick="window.adminInterface.deployAgain()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-redo"></i>Deploy Again
                    </button>
                    <button onclick="window.adminInterface.deployNewSite()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-rocket"></i>Deploy New Site
                    </button>
                `;
                break;
            case 'failed':
                statusIcon='fas fa-times-circle';
                headerClass='modal-header-red';
                statusTitle='Deployment Failed';
                statusMessage='The deployment encountered an error. Please check the logs for more details.';
                statusBadge='FAILED';
                actionButtons=`
                    <button onclick="window.open('${githubData.url||'#'}', '_blank')" class="modal-btn modal-btn-primary">
                        <i class="fab fa-github"></i>View Error Details
                    </button>
                    <button onclick="window.adminInterface.deployAgain()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-redo"></i>Deploy Again
                    </button>
                    <button onclick="document.querySelector('.nav-link[data-tab=\"deployment\"]').click(); document.getElementById('deployment-completion-modal').remove();" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-edit"></i>Edit & Retry
                    </button>
                    <button onclick="window.adminInterface.deployNewSite()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-rocket"></i>Deploy New Site
                    </button>
                `;
                break;
            case 'cancelled':
                statusIcon='fas fa-exclamation-triangle';
                headerClass='modal-header-yellow';
                statusTitle='Deployment Cancelled';
                statusMessage='The deployment was cancelled and did not complete.';
                statusBadge='CANCELLED';
                actionButtons=`
                    <button onclick="window.adminInterface.deployAgain()" class="modal-btn modal-btn-primary">
                        <i class="fas fa-redo"></i>Deploy Again
                    </button>
                    <button onclick="window.adminInterface.deployNewSite()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-rocket"></i>Deploy New Site
                    </button>
                `;
                break;
            default:
                statusIcon='fas fa-info-circle';
                headerClass='modal-header-blue';
                statusTitle='Deployment Status Update';
                statusMessage=`Deployment status: ${githubData.status}`;
                statusBadge=githubData.status.toUpperCase();
                actionButtons=`
                    <button onclick="document.getElementById('deployment-completion-modal').remove();" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-times"></i>Close
                    </button>
                `;
        }

        // Create modern modal HTML
        const modalHTML=`
            <div id="deployment-completion-modal" class="modern-modal-overlay">
                <div class="modern-modal-container">
                    <!-- Close Button -->
                    <button class="modal-close-btn" onclick="document.getElementById('deployment-completion-modal').remove();">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <!-- Header -->
                    <div class="modal-header ${headerClass}">
                        <div class="modal-icon-ring">
                            <i class="${statusIcon} modal-status-icon"></i>
                        </div>
                        <h2 class="modal-title">${statusTitle}</h2>
                        <div class="modal-status-badge">${statusBadge}</div>
                    </div>
                    
                    <!-- Content -->
                    <div class="modal-content">
                        <!-- Stats Grid -->
                        <div class="modal-stats">
                            ${totalDuration? `
                                <div class="stat-item">
                                    <div class="stat-icon stat-icon-blue">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-value">${totalDuration}</div>
                                        <div class="stat-label">Total Duration</div>
                                    </div>
                                </div>
                            ` :''}
                            ${githubData.run_id? `
                                <div class="stat-item">
                                    <div class="stat-icon stat-icon-purple">
                                        <i class="fab fa-github"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-value">#${githubData.run_id}</div>
                                        <div class="stat-label">GitHub Run ID</div>
                                    </div>
                                </div>
                            ` :''}
                        </div>
                        
                        <!-- Completion Time -->
                        ${completedAt? `
                            <div class="completion-time">
                                <i class="fas fa-calendar-check"></i>
                                Completed at ${completedAt}
                            </div>
                        ` :''}
                        
                        <!-- ClickUp Integration Status -->
                        ${clickUpStatusHTML}
                        
                        <div class="modal-actions">
                            ${actionButtons}
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="modal-footer">
                        <i class="fas fa-heart"></i>
                        Deployment managed by WordPress Automation Framework
                    </div>
                </div>
            </div>
        `;

        // Add modal to DOM
        document.body.insertAdjacentHTML('beforeend',modalHTML);

        // Attach copy-to-clipboard behavior for run ID button (if present)
        const copyBtn=document.getElementById('copy-run-id-btn');
        if(copyBtn) {
            copyBtn.addEventListener('click',() => {
                const runId=copyBtn.getAttribute('data-run-id')||copyBtn.dataset.runId;
                if(!runId) return;

                // Try modern clipboard API first
                if(navigator.clipboard&&navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(runId).then(() => {
                        const oldHtml=copyBtn.innerHTML;
                        copyBtn.innerHTML='<i class="fas fa-check"></i>Copied';
                        copyBtn.disabled=true;
                        setTimeout(() => {
                            copyBtn.innerHTML=oldHtml;
                            copyBtn.disabled=false;
                        },2500);
                    }).catch(err => {
                        debugLog('Failed to copy run id to clipboard',err,'error');
                        copyBtn.innerHTML='<i class="fas fa-exclamation-triangle"></i>Copy Failed';
                        setTimeout(() => {copyBtn.innerHTML='<i class="fas fa-clipboard"></i>Copy Run ID';},2500);
                    });
                    return;
                }

                // Fallback for older browsers using execCommand
                const tmp=document.createElement('input');
                tmp.value=runId;
                document.body.appendChild(tmp);
                tmp.select();
                try {
                    document.execCommand('copy');
                    const oldHtml=copyBtn.innerHTML;
                    copyBtn.innerHTML='<i class="fas fa-check"></i>Copied';
                    setTimeout(() => {copyBtn.innerHTML=oldHtml;},2500);
                } catch(e) {
                    debugLog('Fallback clipboard copy failed',e,'error');
                    copyBtn.innerHTML='<i class="fas fa-exclamation-triangle"></i>Copy Failed';
                    setTimeout(() => {copyBtn.innerHTML='<i class="fas fa-clipboard"></i>Copy Run ID';},2500);
                } finally {
                    document.body.removeChild(tmp);
                }
            });
        }

        // NOTE: Modal will stay open until user manually closes it
        // No auto-close timeout for better user experience

        debugLog('Modern completion modal displayed for status:',githubData.status);
    }

    // Test GitHub Actions completion popup
    testGitHubCompletion(status='completed') {
        debugLog('Testing GitHub Actions completion modal...');
        const testData={
            status: status,
            run_id: `test_${Date.now()}`,
            message: `Test ${status} message`,
            url: 'https://github.com/test/repo/actions/runs/123',
            owner: 'test',
            repo: 'repo',
            site_url: 'https://example.com',
            created_at: new Date().toISOString()
        };
        this.showGitHubActionsFinalStatus(testData);
    }

    // Test fallback behavior when the URL is missing but owner/repo + run_id exist
    testGitHubCompletionFallback(status='completed') {
        debugLog('Testing GitHub Actions completion modal fallback (no url provided)...');

        const testData={
            status: status,
            run_id: `test_${Date.now()}`,
            message: `Test ${status} message`,
            url: null,
            owner: 'frontlinestrategies',
            repo: 'WebsiteBuild',
            site_url: 'https://example.com',
            created_at: new Date().toISOString()
        };

        this.showGitHubActionsFinalStatus(testData);
    }

    // Test timing display
    testTimingDisplay() {
        debugLog('Testing individual step timing display...');

        // Set test timing data for individual steps
        const now=Date.now();
        this.buttonClickTime=new Date(now-120000); // 2 minutes ago
        this.deploymentStartTime=this.buttonClickTime;

        // Simulate individual step timings
        const step1Start=now-120000; // Started 2 minutes ago
        const step1End=now-75000;   // Ended 1m 15s ago (45s duration)
        const step2Start=now-75000;  // Started when step1 ended

        // Simulate a deployment in progress
        const testStatus={
            status: 'running',
            current_step: 'get-cred',
            deployment_start_time: Math.floor(this.deploymentStartTime.getTime()/1000),
            deployment_start_time_formatted: this.deploymentStartTime.toISOString().replace('T',' ').substring(0,19),
            step_timings: {
                'create-site': {
                    start_time: Math.floor(step1Start/1000),
                    start_time_formatted: new Date(step1Start).toISOString().replace('T',' ').substring(0,19),
                    end_time: Math.floor(step1End/1000),
                    end_time_formatted: new Date(step1End).toISOString().replace('T',' ').substring(0,19),
                    duration: 45, // 45 seconds
                    status: 'completed'
                },
                'get-cred': {
                    start_time: Math.floor(step2Start/1000),
                    start_time_formatted: new Date(step2Start).toISOString().replace('T',' ').substring(0,19),
                    status: 'running'
                }
            }
        };

        this.updateDeploymentStatusDisplay(testStatus);
        this.showAlert('<i class="fas fa-clock mr-2"></i>Individual step timing test started','info');
    }

    showPersistentAlert(title,message,type='info',duration=10000) {
        debugLog('showPersistentAlert called:',{title,message,type,duration});

        // Create alert container if it doesn't exist
        let alertContainer=document.getElementById('persistent-alerts');
        if(!alertContainer) {
            debugLog('Creating new alert container');
            alertContainer=document.createElement('div');
            alertContainer.id='persistent-alerts';
            alertContainer.className='fixed top-4 right-4 max-w-md';
            alertContainer.style.zIndex='9999'; // Ensure it's above everything
            document.body.appendChild(alertContainer);
        } else {
            debugLog('Using existing alert container');
        }

        // Create alert element
        const alert=document.createElement('div');
        const alertId=`alert-${Date.now()}`;
        alert.id=alertId;

        const bgColor=type==='success'? 'bg-green-500':
            type==='error'? 'bg-red-500':
                type==='warning'? 'bg-yellow-500':'bg-blue-500';

        alert.className=`${bgColor} text-white p-4 rounded-lg shadow-lg mb-3 animate-slideIn transition-all duration-500`;
        alert.style.pointerEvents='auto'; // Ensure the alert is clickable
        alert.innerHTML=`
            <div class="flex items-start">
                <div class="flex-1">
                    <h4 class="font-bold text-lg mb-2">${title}</h4>
                    <div class="text-sm mt-1">${message}</div>
                </div>
                <button onclick="document.getElementById('${alertId}').remove()" class="ml-3 text-white hover:text-gray-200 font-bold text-xl leading-none transition-colors duration-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Add to container
        debugLog('Adding alert to container:',alert);
        alertContainer.appendChild(alert);
        debugLog('Alert added successfully. Container now has',alertContainer.children.length,'alerts');

        // Auto-remove after duration
        setTimeout(() => {
            if(document.getElementById(alertId)) {
                debugLog('Auto-removing alert:',alertId);
                document.getElementById(alertId).remove();
            }
        },duration);
    }

    resetDeploymentState() {
        // Reset polling state - stops all active polling including GitHub Actions
        this.stopAllPolling();

        // Set flag to prevent completion modal after reset
        this.systemRecentlyReset=true;
        setTimeout(() => {
            this.systemRecentlyReset=false;
            debugLog('Reset flag cleared - completion modals can show again');
        },5000); // Clear flag after 5 seconds

        // Close any existing deployment completion modals
        const existingModal=document.getElementById('deployment-completion-modal');
        if(existingModal) {
            existingModal.remove();
            debugLog('Closed deployment completion modal during reset');
        }

        // Show deployment section again
        this.setDeploymentInProgress(false);

        // Reset GitHub Actions state flags
        this.githubActionsCompleted=false;
        this.githubActionsManuallyUpdated=false;

        // Clear all localStorage entries for GitHub Actions final status to prevent modal reappearance
        const githubKeys=Object.keys(localStorage).filter(key => key.startsWith('github_actions_final_'));
        githubKeys.forEach(key => {
            localStorage.removeItem(key);
            debugLog('Cleared localStorage key:',key);
        });

        // Clear timing data
        this.deploymentStartTime=null;
        this.buttonClickTime=null;
        this.stepStartTimes.clear();
        this.stepDurations.clear();
        this.realTimeTimers.forEach(timerId => clearInterval(timerId));
        this.realTimeTimers.clear();

        // Clear old final status records (keep only last 5 to prevent localStorage bloat)
        const keys=Object.keys(localStorage).filter(key => key.startsWith('github_actions_final_'));
        if(keys.length>5) {
            keys.slice(0,keys.length-5).forEach(key => localStorage.removeItem(key));
        }

        // Clear persisted log read timestamp so we don't resume old logs after reset
        try {
            localStorage.removeItem('lastLogReadTime');
        } catch(e) {
            debugLog('Unable to remove lastLogReadTime from localStorage',e,'warn');
        }

        this.lastLogReadTime=0;

        // Reset GitHub Actions visual state by forcing a refresh of the deployment status display
        // This will clear any manually updated GitHub Actions status and reset all steps to pending
        setTimeout(() => {
            // Force reset all step states to pending
            const statusContainer=document.getElementById('deployment-status-list');
            if(statusContainer) {
                statusContainer.innerHTML='';
            }

            this.updateDeploymentStatusDisplay({
                status: 'idle',
                current_step: '',
                message: 'System reset - ready for new deployment'
            });
        },100);

        debugLog('Deployment state reset - GitHub Actions status and timing data cleared');
    }

    updateGitHubActionsDisplay(githubData) {
        debugLog('Updating GitHub Actions display with data:',githubData);

        // Find the GitHub Actions step card
        const githubStepCard=document.querySelector('[data-step="github-actions"]');
        if(!githubStepCard) {
            debugLog('GitHub Actions step card not found');
            return;
        }

        // Mark that we've manually updated the GitHub Actions status
        this.githubActionsManuallyUpdated=true;

        const statusMessage=githubStepCard.querySelector('.status-step-desc');
        const timestampElement=githubStepCard.querySelector('.timestamp-badge');
        const statusIcon=githubStepCard.querySelector('.status-check-mark');
        const stepIcon=githubStepCard.querySelector('.status-icon-large');

        // Update message
        if(statusMessage) {
            let message=githubData.message||'Checking GitHub Actions status...';
            if(githubData.url) {
                message+=` <a href="${githubData.url}" target="_blank" class="text-blue-500 underline">View on GitHub</a>`;
            }
            statusMessage.innerHTML=message;
        }

        // Update timestamp
        if(timestampElement) {
            if(githubData.created_at) {
                const createdDate=new Date(githubData.created_at);
                timestampElement.textContent=`Started: ${createdDate.toLocaleTimeString()}`;
                timestampElement.className='timestamp-badge text-xs font-mono px-2 py-1 rounded text-blue-500 bg-blue-100';
            } else {
                timestampElement.textContent='Waiting...';
                timestampElement.className='timestamp-badge text-xs font-mono px-2 py-1 rounded text-gray-400 bg-gray-100';
            }
        }

        // Update visual status based on GitHub Actions status
        const refreshBtn=githubStepCard.querySelector('.github-refresh-btn');

        if(githubData.status==='pending') {
            // Still waiting for GitHub Actions to start
            if(statusIcon) statusIcon.innerHTML='<i class="fas fa-hourglass-start text-gray-500"></i>';
            if(stepIcon) {
                stepIcon.className='status-icon-large bg-gradient-to-r from-gray-400 to-slate-400 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg';
                stepIcon.innerHTML='○';
            }
            githubStepCard.className='status-step-card pending bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-4 shadow-sm opacity-60';

            // Show refresh button for pending status
            if(refreshBtn) refreshBtn.style.display='block';
        } else if(githubData.status==='running'||githubData.github_status==='queued'||githubData.github_status==='in_progress') {
            // GitHub Actions is running
            if(statusIcon) statusIcon.innerHTML='<i class="fas fa-spinner fa-spin text-blue-500"></i>';
            if(stepIcon) {
                stepIcon.className='status-icon-large bg-gradient-to-r from-blue-500 to-indigo-500 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg animate-spin';
                stepIcon.innerHTML='⟳';
            }
            githubStepCard.className='status-step-card in-progress bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 shadow-sm';

            // Show refresh button for running status too (in case it gets stuck)
            if(refreshBtn) refreshBtn.style.display='block';
        } else if(githubData.status==='completed') {
            // GitHub Actions completed successfully
            if(statusIcon) statusIcon.innerHTML='<i class="fas fa-check-circle text-emerald-500"></i>';
            if(stepIcon) {
                stepIcon.className='status-icon-large bg-gradient-to-r from-emerald-500 to-teal-500 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg';
                stepIcon.innerHTML='<i class="fas fa-check"></i>';
            }
            githubStepCard.className='status-step-card completed bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 shadow-sm';

            // Hide refresh button when completed
            if(refreshBtn) refreshBtn.style.display='none';
        } else if(githubData.status==='failed'||githubData.status==='cancelled') {
            // GitHub Actions failed
            if(statusIcon) statusIcon.innerHTML='<i class="fas fa-times-circle text-red-500"></i>';
            if(stepIcon) {
                stepIcon.className='status-icon-large bg-gradient-to-r from-red-500 to-pink-500 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg';
                stepIcon.innerHTML='<i class="fas fa-times"></i>';
            }
            githubStepCard.className='status-step-card failed bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl p-4 shadow-sm';

            // Show refresh button for failed status in case user wants to check again
            if(refreshBtn) refreshBtn.style.display='block';
        }
    }

    /**
     * Check if site exists in Kinsta without saving (debounced version)
     */
    debouncedCheckSiteExistence(siteTitle) {
        // Clear existing timer
        if(this.siteCheckDebounceTimer) {
            clearTimeout(this.siteCheckDebounceTimer);
        }

        // Debounce the check (wait 800ms after user stops typing)
        this.siteCheckDebounceTimer=setTimeout(async () => {
            await this.checkSiteExistence(siteTitle);
        },800);
    }

    /**
     * Check if site exists in Kinsta without saving
     */
    async checkSiteExistence(siteTitle) {
        if(!siteTitle||!siteTitle.trim()) {
            return;
        }

        try {
            const existsResponse=await fetch('?action=check_site_exists',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    site_title: siteTitle
                })
            });

            const existsData=await existsResponse.json();

            // Get warning elements
            const siteTitleInput=document.getElementById('deployment-site-title');
            const warningDiv=document.getElementById('site-title-warning');
            const warningText=document.getElementById('site-title-warning-text');
            const deleteOption=document.getElementById('delete-existing-site-option');
            const deleteCheckbox=document.getElementById('delete-existing-site-checkbox');

            if(existsData.success&&existsData.data.exists) {
                // Site exists - show warning
                const matchingSites=existsData.data.matching_sites||[];
                const siteNames=matchingSites.map(s => s.display_name||s.name).join(', ');

                // Store the site IDs and full details for potential deletion
                this.existingSiteIds=matchingSites.map(s => s.id);
                this.existingSiteDetails=matchingSites; // Store full site details for confirmation

                // Add visual indicator to the input field
                if(siteTitleInput) {
                    siteTitleInput.classList.add('input-warning');
                    siteTitleInput.style.borderColor='#f59e0b';
                    siteTitleInput.style.backgroundColor='#fffbeb';
                }

                // Show warning div below input
                if(warningDiv&&warningText) {
                    warningText.textContent=`A site named "${siteNames}" already exists in Kinsta. Using this name will overwrite the existing site.`;
                    warningDiv.style.display='block';
                }

                // Show delete option
                if(deleteOption) {
                    deleteOption.style.display='block';
                    // Reset checkbox state
                    if(deleteCheckbox) {
                        deleteCheckbox.checked=false;
                    }
                }
                else {
                    debugLog('Delete option element not found in DOM','warn');
                }

                debugLog('Site title conflicts with existing Kinsta site:',siteNames,'warn');
            } else {
                // No conflict - clear any previous warnings
                this.existingSiteIds=[];
                this.existingSiteDetails=[];

                if(siteTitleInput) {
                    siteTitleInput.classList.remove('input-warning');
                    siteTitleInput.style.borderColor='';
                    siteTitleInput.style.backgroundColor='';
                }

                // Hide warning div
                if(warningDiv) {
                    warningDiv.style.display='none';
                }

                // Hide delete option
                if(deleteOption) {
                    deleteOption.style.display='none';
                }
            }
        } catch(error) {
            debugLog('Failed to check site existence:',error.message,'error');
        }
    }

    // Company ID validation removed: client-side validation endpoint has been removed from the server.

    /**
     * Update Kinsta API token generation link with current company ID
     */
    updateKinstaTokenLink() {
        const companyInput=document.getElementById('company-id-input');
        const tokenLink=document.getElementById('kinsta-token-link');

        if(!tokenLink) return;

        const companyId=companyInput&&companyInput.value? companyInput.value.trim():'';

        if(companyId) {
            tokenLink.href=`https://my.kinsta.com/company/apiKeys?idCompany=${encodeURIComponent(companyId)}`;
        } else {
            tokenLink.href='https://my.kinsta.com/company/apiKeys';
        }
    }

    /**
     * Clear company validation display
     */
    clearCompanyValidation() {
        const validationIcon=document.getElementById('company-validation-icon');
        const companyNameDisplay=document.getElementById('company-name-display');

        if(validationIcon) {
            validationIcon.style.display='none';
            validationIcon.innerHTML='';
        }

        if(companyNameDisplay) {
            companyNameDisplay.style.display='none';
        }
    }

    /**
     * Handle site title change in the deployment tab
     * Updates both site_title and display_name (as a slug)
     */
    async handleSiteTitleChange(e) {
        const siteTitle=e.target.value;

        // Update site title in the header immediately (for visual feedback)
        const siteTitleHeader=document.getElementById('site-title');
        if(siteTitleHeader) {
            siteTitleHeader.textContent=siteTitle||'Site Title';
        }

        // Clear existing timer
        if(this.siteTitleDebounceTimer) {
            clearTimeout(this.siteTitleDebounceTimer);
        }

        // Debounce the actual save operation (wait 1 second after user stops typing)
        this.siteTitleDebounceTimer=setTimeout(async () => {
            await this.saveSiteTitle(siteTitle);
        },1000);
    }

    async saveSiteTitle(siteTitle) {
        // Generate a slug from the site title for display_name
        const displayName=this.slugify(siteTitle);

        try {
            // Check if site exists (this will show/hide warnings)
            await this.checkSiteExistence(siteTitle);

            // First, get the existing site configuration
            const configResponse=await fetch('?action=get_configs');
            const configData=await configResponse.json();

            if(!configData.success) {
                throw new Error('Failed to fetch configuration: '+(configData.message||'Unknown error'));
            }

            if(!configData.data||!configData.data.site) {
                throw new Error('Site configuration not found in response');
            }

            // Merge with existing configuration, only updating site_title and display_name
            const updatedConfig={
                ...configData.data.site,
                site_title: siteTitle,
                display_name: displayName
            };

            debugLog('Updating site config:',{site_title: siteTitle,display_name: displayName});

            // Save the updated configuration
            const response=await fetch('?action=save_config',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'site',
                    data: updatedConfig
                })
            });

            const result=await response.json();

            if(result.success) {
                debugLog('Site title updated successfully');
                this.showAlert&&this.showAlert('Site title updated successfully','success');
            } else {
                const errorMsg='Failed to update site title: '+(result.message||'Unknown error');
                debugLog(errorMsg,'error');
                this.showAlert&&this.showAlert(errorMsg,'error');
            }
        } catch(error) {
            const errorMsg='Failed to save site title: '+error.message;
            debugLog(errorMsg,'error');
            this.showAlert&&this.showAlert(errorMsg,'error');
        }
    }

    /**
     * Convert string to slug format
     */
    slugify(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g,'-')        // Replace spaces with -
            .replace(/[^\w\-]+/g,'')    // Remove all non-word chars
            .replace(/\-\-+/g,'-')      // Replace multiple - with single -
            .replace(/^-+/,'')          // Trim - from start of text
            .replace(/-+$/,'');         // Trim - from end of text
    }

    updateDeploymentStatus(status) {
        const statusElement=document.getElementById('current-deployment-status');
        if(statusElement) {
            const statusClass=status.status==='running'? 'status-warning':
                status.status==='completed'? 'status-success':
                    status.status==='failed'? 'status-error':'status-info';

            statusElement.innerHTML=`
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold">Current Status</div>
                        <div class="text-sm text-secondary">${status.current_step||'No active step'}</div>
                    </div>
                    <div class="status ${statusClass}">
                        ${status.status||'idle'}
                    </div>
                </div>
            `;
        }
    }

    async loadDeploymentHistory() {
        try {
            const response=await fetch('?action=deployment_history');
            const data=await response.json();

            if(data.success) {
                this.renderDeploymentHistory(data.data);
            }
        } catch(error) {
            debugLog('Failed to load deployment history:',error,'error');
        }
    }

    renderDeploymentHistory(history) {
        const container=document.getElementById('deployment-history');
        if(!container) return;

        if(history.length===0) {
            container.innerHTML='<p class="text-muted text-center">No deployment history found.</p>';
            return;
        }

        container.innerHTML=history.map(deployment => `
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold">${deployment.start_time}</div>
                        <div class="status ${deployment.status==='completed'? 'status-success':
                deployment.status==='failed'? 'status-error':'status-warning'}">
                            ${deployment.status}
                        </div>
                    </div>
                    ${deployment.end_time?
                `<div class="text-sm text-secondary">Completed: ${deployment.end_time}</div>`:
                '<div class="text-sm text-secondary">Still running...</div>'
            }
                    ${deployment.error?
                `<div class="text-sm text-error mt-2">${deployment.error}</div>`:''
            }
                </div>
            </div>
        `).join('');
    }

    async loadDeploymentLogs(realtime=false) {
        try {
            let url='?action=deployment_logs';
            if(realtime&&this.lastLogReadTime>0) {
                url+=`&last_read=${this.lastLogReadTime}`;
            }

            const response=await fetch(url);
            const data=await response.json();

            if(data.success) {
                if(realtime&&this.lastLogReadTime>0) {
                    // Append new logs instead of replacing
                    this.appendDeploymentLogs(data.data);
                } else {
                    // Full reload
                    this.renderDeploymentLogs(data.data);
                }

                // Update last read time and persist it so we can resume after reload
                if(data.timestamp) {
                    this.lastLogReadTime=data.timestamp;
                    try {
                        localStorage.setItem('lastLogReadTime',String(data.timestamp));
                    } catch(e) {
                        debugLog('Unable to persist lastLogReadTime to localStorage',e,'warn');
                    }
                }
            }
        } catch(error) {
            debugLog('Failed to load deployment logs:',error,'error');
        }
    }

    renderDeploymentLogs(logs) {
        const container=document.getElementById('deployment-logs');
        if(!container) return;

        if(logs.length===0) {
            container.innerHTML='<div class="text-center text-muted">No logs found.</div>';
            return;
        }

        container.innerHTML=logs.map(log => this.formatLogEntry(log)).join('');
        this.scrollLogsToBottom();
    }

    appendDeploymentLogs(newLogs) {
        const container=document.getElementById('deployment-logs');
        if(!container||newLogs.length===0) return;

        // Remove "No logs found" message if present
        const noLogsMsg=container.querySelector('.text-center.text-muted');
        if(noLogsMsg) {
            noLogsMsg.remove();
        }

        // Append new logs with animation
        newLogs.forEach(log => {
            const logHtml=this.formatLogEntry(log,true); // Mark as new
            container.insertAdjacentHTML('beforeend',logHtml);
        });

        // Remove "new" class after animation
        setTimeout(() => {
            const newEntries=container.querySelectorAll('.log-entry.new');
            newEntries.forEach(entry => entry.classList.remove('new'));
        },500);

        this.scrollLogsToBottom();
    }

    formatLogEntry(log,isNew=false) {
        const newClass=isNew? ' new':'';
        return `
            <div class="log-entry${newClass}">
                <span class="log-timestamp">[${log.timestamp}]</span>
                <span class="log-level-${log.level}">${log.level}</span>
                ${log.step? `<span class="log-step">${log.step}</span>`:''}
                <span class="log-message">${log.message}</span>
            </div>
        `;
    }

    scrollLogsToBottom() {
        const container=document.getElementById('deployment-logs');
        if(container) {
            container.scrollTop=container.scrollHeight;
        }
    }

    startDeploymentMonitoring() {
        // Reset state for new deployment - but preserve timing data if set
        if(!this.deploymentStartTime) {
            this.deploymentStartTime=new Date();
        }

        // Stop existing timers
        this.stopAllPolling();

        // Clear GitHub Actions state flags
        this.githubActionsCompleted=false;
        this.githubActionsManuallyUpdated=false;

        if(this.deploymentPollInterval) {
            clearInterval(this.deploymentPollInterval);
        }

        // Load initial logs. If we have a persisted lastLogReadTime, resume tailing (realtime)
        if(this.lastLogReadTime&&Number(this.lastLogReadTime)>0) {
            debugLog('Resuming log tail from timestamp:',this.lastLogReadTime);
            this.loadDeploymentLogs(true);
        } else {
            this.loadDeploymentLogs(false);
        }

        this.deploymentPollInterval=setInterval(() => {
            this.pollDeploymentStatus();
            this.loadDeploymentLogs(true); // Real-time update
        },2000); // More frequent polling for better real-time experience

        // Auto-enable real-time logs toggle when deployment starts
        this.realtimeLogsEnabled=true;
        this.updateRealtimeToggleButton();
    }

    stopDeploymentMonitoring() {
        if(this.deploymentPollInterval) {
            clearInterval(this.deploymentPollInterval);
            this.deploymentPollInterval=null;
        }

        // Auto-disable real-time logs when deployment stops
        this.realtimeLogsEnabled=false;
        this.updateRealtimeToggleButton();
    }

    toggleRealtimeLogs() {
        this.realtimeLogsEnabled=!this.realtimeLogsEnabled;

        if(this.realtimeLogsEnabled) {
            // Start real-time log polling
            this.startRealtimeLogPolling();
            this.showAlert('✅ Real-time logs enabled','success',null,false,2000);
        } else {
            // Stop real-time log polling
            this.stopRealtimeLogPolling();
            this.showAlert('⏸️ Real-time logs disabled','info',null,false,2000);
        }

        // Update button appearance
        this.updateRealtimeToggleButton();
    }

    startRealtimeLogPolling() {
        // Clear any existing interval
        this.stopRealtimeLogPolling();

        // Start polling every 2 seconds
        this.logPollInterval=setInterval(() => {
            this.loadDeploymentLogs(true);
        },2000);

        // Load logs immediately
        this.loadDeploymentLogs(true);
    }

    stopRealtimeLogPolling() {
        if(this.logPollInterval) {
            clearInterval(this.logPollInterval);
            this.logPollInterval=null;
        }
    }

    updateRealtimeToggleButton() {
        const toggleBtn=document.getElementById('realtime-toggle-btn');
        const toggleIcon=toggleBtn?.querySelector('i');
        const toggleText=toggleBtn?.querySelector('.toggle-text');

        if(!toggleBtn) return;

        if(this.realtimeLogsEnabled) {
            toggleBtn.className='btn btn-success btn-sm realtime-toggle-btn';
            if(toggleIcon) toggleIcon.className='fas fa-stop me-1';
            if(toggleText) toggleText.textContent='Stop Real-time';
        } else {
            toggleBtn.className='btn btn-outline-primary btn-sm realtime-toggle-btn';
            if(toggleIcon) toggleIcon.className='fas fa-play me-1';
            if(toggleText) toggleText.textContent='Start Real-time';
        }
    }

    markFormDirty(form) {
        if(!form) return; // Add null check

        form.classList.add('form-dirty');

        // Add save indicator
        const saveBtn=form.querySelector('.save-config-btn');
        if(saveBtn&&!saveBtn.textContent.includes('*')) {
            saveBtn.textContent=saveBtn.textContent+' *';
        }
    }

    markFormClean(form) {
        if(!form) return; // Add null check

        form.classList.remove('form-dirty');

        // Remove save indicator
        const saveBtn=form.querySelector('.save-config-btn');
        if(saveBtn) {
            saveBtn.textContent=saveBtn.textContent.replace(' *','');
        }
    }

    formatPageName(page) {
        // Remove numbers and hyphens/spaces for clean display names
        let cleanName=page.replace(/[-\s]*\d+$/,'');

        return cleanName.split('-').map(word =>
            word.charAt(0).toUpperCase()+word.slice(1)
        ).join(' ');
    }

    formatSectionType(type) {
        const types={
            'text_editor': 'Text Content',
            'image': 'Image',
            'features': 'Features Block',
            'hero': 'Hero Banner'
        };
        return types[type]||type;
    }

    showAlert(message,type='info',title=null,dismissible=true,timeout=5000,showProgress=false) {
        // Icon mapping for different alert types
        const iconMap={
            success: 'fas fa-check',
            warning: 'fas fa-exclamation-triangle',
            danger: 'fas fa-times-circle',
            error: 'fas fa-times-circle',
            info: 'fas fa-info-circle'
        };

        // Title mapping for different alert types
        const titleMap={
            success: 'Success',
            warning: 'Warning',
            danger: 'Error',
            error: 'Error',
            info: 'Information'
        };

        // Normalize type (handle 'error' as 'danger')
        const alertType=type==='error'? 'danger':type;

        // Create main alert container
        const alert=document.createElement('div');
        alert.className=`alert alert-${alertType}${dismissible? ' alert-dismissible':''}${showProgress&&timeout>0? ' loading':''}`;

        // Create alert icon
        const iconElement=document.createElement('div');
        iconElement.className='alert-icon';
        iconElement.innerHTML=`<i class="${iconMap[alertType]||iconMap.info}"></i>`;

        // Create alert content container
        const contentElement=document.createElement('div');
        contentElement.className='alert-content';

        // Create alert title if provided
        if(title||titleMap[alertType]) {
            const titleElement=document.createElement('div');
            titleElement.className='alert-title';
            titleElement.textContent=title||titleMap[alertType];
            contentElement.appendChild(titleElement);
        }

        // Create alert message
        const messageElement=document.createElement('div');
        messageElement.className='alert-message';
        messageElement.textContent=message;
        contentElement.appendChild(messageElement);

        // Assemble alert
        alert.appendChild(iconElement);
        alert.appendChild(contentElement);

        // Add dismiss button if dismissible
        if(dismissible) {
            const dismissBtn=document.createElement('button');
            dismissBtn.className='alert-dismiss';
            dismissBtn.innerHTML='<i class="fas fa-times"></i>';
            dismissBtn.setAttribute('aria-label','Close alert');
            dismissBtn.addEventListener('click',() => this.dismissAlert(alert));
            alert.appendChild(dismissBtn);
        }

        // Find container or create one
        let container=document.getElementById('alert-container');
        if(!container) {
            container=document.createElement('div');
            container.id='alert-container';
            container.style.cssText=`
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 9999; 
                max-width: 420px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }

        // Make alert container interactive
        alert.style.pointerEvents='auto';

        // Add to container
        container.appendChild(alert);

        // Auto-remove after specified timeout
        if(timeout>0) {
            setTimeout(() => {
                this.dismissAlert(alert);
            },timeout);
        }

        return alert;
    }

    dismissAlert(alert) {
        if(alert&&alert.parentNode) {
            alert.classList.add('alert-dismissing');
            setTimeout(() => {
                if(alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            },300);
        }
    }

    // Alert helper methods for common use cases
    showSuccessAlert(message,title=null,dismissible=true) {
        return this.showAlert(message,'success',title,dismissible);
    }

    showErrorAlert(message,title=null,dismissible=true) {
        return this.showAlert(message,'danger',title,dismissible);
    }

    showWarningAlert(message,title=null,dismissible=true) {
        return this.showAlert(message,'warning',title,dismissible);
    }

    showInfoAlert(message,title=null,dismissible=true) {
        return this.showAlert(message,'info',title,dismissible);
    }

    // Show loading alert with progress indicator
    showLoadingAlert(message,title='Loading...',timeout=10000) {
        return this.showAlert(message,'info',title,false,timeout,true);
    }

    // Show temporary alert that auto-dismisses quickly
    showTempAlert(message,type='success',timeout=2000) {
        return this.showAlert(message,type,null,false,timeout);
    }

    // Clear all alerts
    clearAlerts() {
        const container=document.getElementById('alert-container');
        if(container) {
            const alerts=container.querySelectorAll('.alert');
            alerts.forEach(alert => this.dismissAlert(alert));
        }
    }

    setupLogoUpload() {
        const logoUpload=document.getElementById('logo-upload');
        const logoFileInput=document.getElementById('logo-file-input');
        const logoPreviewImg=document.getElementById('logo-preview-img');
        const logoUploadText=document.getElementById('logo-upload-text');
        const logoActions=document.getElementById('logo-actions');
        const removeLogoBtn=document.getElementById('remove-logo-btn');
        const saveLogoBtn=document.getElementById('save-logo-btn');

        if(!logoUpload||!logoFileInput) return;

        // Make logo upload area clickable
        logoUpload.addEventListener('click',() => {
            logoFileInput.click();
        });

        // Handle file selection
        logoFileInput.addEventListener('change',(e) => {
            const file=e.target.files[0];
            if(file) {
                this.handleLogoPreview(file);
            }
        });

        // Handle drag and drop
        logoUpload.addEventListener('dragover',(e) => {
            e.preventDefault();
            logoUpload.classList.add('dragover');
        });

        logoUpload.addEventListener('dragleave',() => {
            logoUpload.classList.remove('dragover');
        });

        logoUpload.addEventListener('drop',(e) => {
            e.preventDefault();
            logoUpload.classList.remove('dragover');

            const files=e.dataTransfer.files;
            if(files.length>0) {
                const file=files[0];
                if(file.type.startsWith('image/')) {
                    logoFileInput.files=files;
                    this.handleLogoPreview(file);
                }
            }
        });

        // Remove logo button
        if(removeLogoBtn) {
            removeLogoBtn.addEventListener('click',(e) => {
                e.stopPropagation();
                this.removeLogo();
            });
        }

        // Save logo button
        if(saveLogoBtn) {
            saveLogoBtn.addEventListener('click',(e) => {
                e.stopPropagation();
                this.saveLogo();
            });
        }

        // Load existing logo
        this.loadExistingLogo();
    }

    handleLogoPreview(file) {
        const logoUpload=document.getElementById('logo-upload');
        const logoPreviewImg=document.getElementById('logo-preview-img');
        const logoUploadText=document.getElementById('logo-upload-text');
        const logoActions=document.getElementById('logo-actions');

        // Validate file size (10MB max)
        if(file.size>10*1024*1024) {
            this.showAlert('Logo file size must be less than 10MB','error');
            return;
        }

        // Validate file type
        if(!file.type.match('image.*')) {
            this.showAlert('Please select a valid image file','error');
            return;
        }

        const reader=new FileReader();
        reader.onload=(e) => {
            logoPreviewImg.src=e.target.result;
            logoPreviewImg.style.display='block';
            logoUpload.classList.add('has-logo');
            logoActions.style.display='flex';

            // Store the file for uploading
            this.pendingLogo=file;
        };
        reader.readAsDataURL(file);
    }

    async saveLogo() {
        if(!this.pendingLogo) {
            this.showAlert('No logo selected to save','warning');
            return;
        }

        const logoUpload=document.getElementById('logo-upload');
        const saveLogoBtn=document.getElementById('save-logo-btn');

        try {
            logoUpload.classList.add('uploading');
            saveLogoBtn.disabled=true;
            saveLogoBtn.textContent='Saving...';

            const formData=new FormData();
            formData.append('logo',this.pendingLogo);
            formData.append('action','upload_logo');

            const response=await fetch('',{
                method: 'POST',
                body: formData
            });

            const result=await response.json();

            if(result.success) {
                this.showAlert('Logo saved successfully! It will be deployed with the next site deployment.','success');
                this.pendingLogo=null;
            } else {
                this.showAlert(result.message||'Failed to save logo','error');
            }
        } catch(error) {
            debugLog('Failed to save logo:',error,'error');
            this.showAlert('Failed to save logo','error');
        } finally {
            logoUpload.classList.remove('uploading');
            saveLogoBtn.disabled=false;
            saveLogoBtn.textContent='Save Logo';
        }
    }

    removeLogo() {
        const logoUpload=document.getElementById('logo-upload');
        const logoPreviewImg=document.getElementById('logo-preview-img');
        const logoFileInput=document.getElementById('logo-file-input');
        const logoActions=document.getElementById('logo-actions');

        logoPreviewImg.src='';
        logoPreviewImg.style.display='none';
        logoUpload.classList.remove('has-logo');
        logoActions.style.display='none';
        logoFileInput.value='';
        this.pendingLogo=null;
    }

    async loadExistingLogo() {
        try {
            const response=await fetch('?action=get_current_logo');
            const result=await response.json();

            if(result.success&&result.data&&result.data.logo_url) {
                const logoPreviewImg=document.getElementById('logo-preview-img');
                const logoUpload=document.getElementById('logo-upload');

                logoPreviewImg.src=result.data.logo_url;
                logoPreviewImg.style.display='block';
                logoUpload.classList.add('has-logo');
            }
        } catch(error) {
            debugLog('No existing logo found or error loading logo');
        }
    }

    /**
     * Load initial data when the page loads
     */
    async loadInitialData() {
        // Initialize deployment steps to default state first
        this.initializeDeploymentSteps();

        // Load deployment status and site configuration
        await this.loadDeploymentStatus();

        // Ensure steps remain in pending state if no active deployment
        this.ensurePendingStateIfNotDeploying();

        // Load themes for the deployment form
        await this.loadThemes();

        // Load ClickUp tasks for the deployment form
        await this.loadClickUpTasks();

        // Load configuration data
        await this.loadConfiguration();

        // Setup manual task fetch button after DOM is ready
        this.setupManualTaskFetchButton();
    }

    setupManualTaskFetchButton() {
        const fetchBtn=document.getElementById('fetch-manual-task-btn');
        const taskInput=document.getElementById('manual-task-id-input');

        if(fetchBtn) {
            debugLog('Setting up manual task fetch button - direct listener');

            // Remove any existing listeners to avoid duplicates
            fetchBtn.replaceWith(fetchBtn.cloneNode(true));
            const newFetchBtn=document.getElementById('fetch-manual-task-btn');

            newFetchBtn.addEventListener('click',(e) => {
                e.preventDefault();
                debugLog('Manual fetch button clicked - direct listener');
                this.fetchManualTask();
            });
        } else {
            debugLog('Manual task fetch button not found!','error');
        }

        if(taskInput) {
            // Remove any existing listeners to avoid duplicates
            taskInput.replaceWith(taskInput.cloneNode(true));
            const newTaskInput=document.getElementById('manual-task-id-input');

            newTaskInput.addEventListener('keypress',(e) => {
                if(e.key==='Enter') {
                    e.preventDefault();
                    debugLog('Enter key pressed - direct listener');
                    this.fetchManualTask();
                }
            });
        } else {
            debugLog('Manual task input not found!','error');
        }

        // Setup ClickUp collapsible section
        this.setupClickUpCollapsible();

        // Setup ClickUp integration checkbox
        this.setupClickUpIntegrationToggle();
    }

    setupClickUpCollapsible() {
        const header=document.getElementById('clickup-section-header');
        const content=document.getElementById('clickup-section-content');
        const icon=document.getElementById('clickup-section-icon');
        const checkbox=document.getElementById('clickup-integration-checkbox');

        if(header&&content&&icon) {
            // Initialize collapsed/expanded state based on checkbox (if present)
            const initiallyExpanded=checkbox? !!checkbox.checked:false;
            if(initiallyExpanded) {
                content.style.display='block';
                icon.style.transform='rotate(0deg)';
            } else {
                content.style.display='none';
                icon.style.transform='rotate(180deg)';
            }

            header.addEventListener('click',() => {
                const isCollapsed=content.style.display==='none';

                if(isCollapsed) {
                    content.style.display='block';
                    icon.style.transform='rotate(0deg)';
                } else {
                    content.style.display='none';
                    icon.style.transform='rotate(180deg)';
                }
            });

            debugLog('ClickUp collapsible section setup complete');
        }
    }

    setupClickUpIntegrationToggle() {
        const checkbox=document.getElementById('clickup-integration-checkbox');
        const taskSection=document.getElementById('clickup-task-section');
        const content=document.getElementById('clickup-section-content');
        const icon=document.getElementById('clickup-section-icon');
        const taskSelect=document.getElementById('clickup-task-select');

        if(checkbox&&taskSection) {
            // Update section visibility based on checkbox
            const updateVisibility=() => {
                if(checkbox.checked) {
                    taskSection.style.display='block';
                    if(taskSelect) taskSelect.removeAttribute('disabled');
                    if(content) content.style.display='block';
                    if(icon) icon.style.transform='rotate(0deg)';
                } else {
                    taskSection.style.display='none';
                    if(taskSelect) {
                        taskSelect.value='';
                        taskSelect.setAttribute('disabled','disabled');
                    }
                    if(content) content.style.display='none';
                    if(icon) icon.style.transform='rotate(180deg)';
                }
            };

            // Initial state
            updateVisibility();

            checkbox.addEventListener('change',updateVisibility);

            debugLog('ClickUp integration toggle setup complete');
        }
    }

    initializeDeploymentSteps() {
        // Initialize all steps to pending state using correct step IDs
        const steps=['create-site','get-cred','trigger-deploy','github-actions'];

        steps.forEach(stepId => {
            this.updateCompactStepStatus(stepId,'pending');
        });

        // Set initial status badge to READY
        const statusBadge=document.querySelector('.status-badge');
        if(statusBadge) {
            statusBadge.textContent='READY';
            statusBadge.className='status-badge bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-semibold';
        }

        // Initially hide deployment status and logs cards since no deployment is running
        this.setDeploymentInProgress(false);

        debugLog('Deployment steps initialized to pending state, status/logs cards hidden');
    }

    ensurePendingStateIfNotDeploying() {
        // Check if there's an active deployment
        const statusBadge=document.querySelector('.status-badge');
        const currentStatus=statusBadge? statusBadge.textContent.trim():'';

        // If not actively deploying, ensure all steps are pending
        if(!['DEPLOYING','IN PROGRESS'].includes(currentStatus)) {
            const steps=['configuration','building','deploying','github-actions','finalizing'];
            steps.forEach(stepId => {
                this.updateCompactStepStatus(stepId,'pending');
            });

            // Ensure status badge shows READY
            if(statusBadge) {
                statusBadge.textContent='READY';
                statusBadge.className='status-badge bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-semibold';
            }
        }
    }

    /**
     * Handle custom collapse functionality for advanced options
     */
    handleCollapse(button) {
        const targetSelector=button.getAttribute('data-bs-target');
        if(!targetSelector) return;

        const target=document.querySelector(targetSelector);
        if(!target) return;

        const isCollapsed=target.classList.contains('collapse');

        if(isCollapsed) {
            // Show the element
            target.classList.remove('collapse');
            target.style.display='block';
            button.setAttribute('aria-expanded','true');

            // Update button text/icon if needed
            const icon=button.querySelector('.fas');
            if(icon) {
                icon.classList.remove('fa-cog');
                icon.classList.add('fa-times');
            }

            // Update button text
            const text=button.childNodes[button.childNodes.length-1];
            if(text&&text.nodeType===Node.TEXT_NODE) {
                text.textContent='Hide Advanced';
            }
        } else {
            // Hide the element
            target.classList.add('collapse');
            target.style.display='none';
            button.setAttribute('aria-expanded','false');

            // Update button text/icon if needed
            const icon=button.querySelector('.fas');
            if(icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-cog');
            }

            // Update button text
            const text=button.childNodes[button.childNodes.length-1];
            if(text&&text.nodeType===Node.TEXT_NODE) {
                text.textContent='Advanced Options';
            }
        }
    }

    /**
     * Handle reset functionality - clear temporary files and logs
     */
    async handleReset() {
        if(!confirm('Are you sure you want to reset? This will:\n\n• Clear all temporary files (tmp folder)\n• Clear all deployment logs\n• Stop any running deployments\n• Reset deployment status\n\nThis action cannot be undone.')) {
            return;
        }

        try {
            this.showAlert('Resetting system - clearing tmp folder and logs...','info');

            const response=await fetch('?action=reset_system',{
                method: 'POST'
            });

            const result=await response.json();

            if(result.success) {
                this.showAlert('System reset successfully','success');

                // Set flag to prevent completion modal after reset
                this.systemRecentlyReset=true;
                setTimeout(() => {
                    this.systemRecentlyReset=false;
                    debugLog('Reset flag cleared - completion modals can show again');
                },5000); // Clear flag after 5 seconds

                // Clear deployment status display first
                this.updateDeploymentStatus({
                    status: 'idle',
                    step: '',
                    message: 'System reset - ready for new deployment',
                    logs: []
                });

                // Close any existing deployment completion modals
                const existingModal=document.getElementById('deployment-completion-modal');
                if(existingModal) {
                    existingModal.remove();
                    debugLog('Removed existing deployment completion modal');
                }

                // Reset deployment state (includes GitHub Actions state and visual reset)
                this.resetDeploymentState();

                // Stop polling if active (redundant but kept for safety)
                if(this.deploymentPollInterval) {
                    clearInterval(this.deploymentPollInterval);
                    this.deploymentPollInterval=null;
                }

                // Clear logs display
                const logOutput=document.getElementById('deployment-logs');
                if(logOutput) {
                    logOutput.innerHTML='<div class="text-muted"><i class="fas fa-info-circle me-2"></i>Logs cleared and tmp folder emptied - ready for new deployment</div>';
                }

            } else {
                const errorMsg=result.message||'Reset failed';
                debugLog('Reset system failed:',errorMsg,'error');
                this.showAlert(errorMsg+'. Check browser console for details.','error');
            }
        } catch(error) {
            debugLog('Reset failed with exception:',error,'error');
            this.showAlert('Failed to reset system. Please refresh the page and try again.','error');
        }
    }

    /**
     * Copy logs to clipboard for debugging
     */
    async copyLogsToClipboard() {
        try {
            const logOutput=document.getElementById('deployment-logs');
            if(!logOutput||!logOutput.textContent.trim()) {
                this.showAlert('No logs available to copy','warning');
                return;
            }

            // Get log text content - format it nicely with timestamps
            const logText=logOutput.textContent;

            // Try modern clipboard API first
            if(navigator.clipboard) {
                await navigator.clipboard.writeText(logText);
                this.showAlert('Logs copied to clipboard successfully','success');
            } else {
                // Fallback for older browsers
                const textArea=document.createElement('textarea');
                textArea.value=logText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showAlert('Logs copied to clipboard successfully','success');
            }
        } catch(error) {
            debugLog('Failed to copy logs:',error,'error');
            this.showAlert('Failed to copy logs to clipboard','error');
        }
    }

    // Dynamic Components Management
    setupDynamicComponents() {
        this.setupNavigationMenuManager();
        this.setupPluginManagers();
        this.setupMarkersManager();
        this.setupMapPreview();
    }

    setupNavigationMenuManager() {
        const addMenuItemBtn=document.getElementById('add-menu-item-btn');
        const menuContainer=document.getElementById('menu-items-container');

        if(addMenuItemBtn&&menuContainer) {
            addMenuItemBtn.addEventListener('click',() => this.addMenuItem());
            this.loadMenuItems();
        }
    }

    addMenuItem() {
        const container=document.getElementById('menu-items-container');
        const itemId=Date.now();
        const menuItemHtml=`
            <div class="menu-item border rounded p-3 mb-3" data-item-id="${itemId}">
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-input menu-title" placeholder="Menu Title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL</label>
                        <input type="text" class="form-input menu-url" placeholder="/page-url or full URL">
                    </div>
                    <div class="form-group d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-menu-item">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend',menuItemHtml);

        // Mark form as dirty to indicate unsaved changes
        const form=container.closest('form');
        if(form) this.markFormDirty(form);
    }

    removeMenuItem(itemId) {
        const item=document.querySelector(`[data-item-id="${itemId}"]`);
        if(item) item.remove();
    }

    loadMenuItems() {
        // This would load from config and populate the menu items
        // For now, we'll assume it's handled by the existing config loading
    }

    setupPluginManagers() {
        // Keep plugins manager
        const addKeepBtn=document.getElementById('add-keep-plugin-btn');
        const keepContainer=document.getElementById('keep-plugins-container');

        if(addKeepBtn&&keepContainer) {
            addKeepBtn.addEventListener('click',() => this.addKeepPlugin());
        }

        // Install plugins manager
        const addInstallBtn=document.getElementById('add-install-plugin-btn');
        const installContainer=document.getElementById('install-plugins-container');

        if(addInstallBtn&&installContainer) {
            addInstallBtn.addEventListener('click',() => this.addInstallPlugin());
        }

        this.loadPlugins();
    }

    addKeepPlugin() {
        const container=document.getElementById('keep-plugins-container');
        const itemId=Date.now();
        const pluginHtml=`
            <div class="plugin-item d-flex align-items-center gap-3 mb-2 p-2 border rounded" data-plugin-id="${itemId}">
                <input type="text" class="form-input flex-grow-1 keep-plugin-name" placeholder="Plugin slug (e.g., yoast-seo)">
                <button type="button" class="btn btn-danger btn-sm remove-keep-plugin">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend',pluginHtml);

        // Mark form as dirty to indicate unsaved changes
        const form=container.closest('form');
        if(form) this.markFormDirty(form);
    }

    addInstallPlugin() {
        const container=document.getElementById('install-plugins-container');
        const itemId=Date.now();
        const pluginHtml=`
            <div class="plugin-item d-flex align-items-center gap-3 mb-2 p-2 border rounded" data-plugin-id="${itemId}">
                <input type="text" class="form-input flex-grow-1 install-plugin-name" placeholder="Plugin slug (e.g., forminator)">
                <button type="button" class="btn btn-danger btn-sm remove-install-plugin">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend',pluginHtml);

        // Mark form as dirty to indicate unsaved changes
        const form=container.closest('form');
        if(form) this.markFormDirty(form);
    }

    removeKeepPlugin(pluginId) {
        const plugin=document.querySelector(`[data-plugin-id="${pluginId}"]`);
        if(plugin) plugin.remove();
    }

    removeInstallPlugin(pluginId) {
        const plugin=document.querySelector(`[data-plugin-id="${pluginId}"]`);
        if(plugin) plugin.remove();
    }

    loadPlugins() {
        // Load existing plugins from config - handled by existing config loading
    }

    setupMarkersManager() {
        const addMarkerBtn=document.getElementById('add-marker-btn');
        const markersContainer=document.getElementById('markers-container');

        if(addMarkerBtn&&markersContainer) {
            addMarkerBtn.addEventListener('click',() => this.addMarker());
            this.loadMarkers();

            // Check if we have pending markers to load
            if(this.pendingMarkers) {
                debugLog('setupMarkersManager: Loading pending markers');
                this.loadMapMarkers(this.pendingMarkers);
            }
        }
    }

    addMarker() {
        const container=document.getElementById('markers-container');
        const markerId=Date.now();
        const markerHtml=`
            <div class="marker-item border rounded p-3 mb-3" data-marker-id="${markerId}">
                <div class="grid grid-cols-4 gap-4">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-input marker-title" placeholder="Marker Title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="number" step="any" class="form-input marker-lat" placeholder="38.8977">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="number" step="any" class="form-input marker-lng" placeholder="-77.0365">
                    </div>
                    <div class="form-group d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-marker">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend',markerHtml);

        // Mark form as dirty to indicate unsaved changes
        const form=container.closest('form');
        if(form) this.markFormDirty(form);
    }

    removeMarker(markerId) {
        const marker=document.querySelector(`[data-marker-id="${markerId}"]`);
        if(marker) marker.remove();
    }

    loadMarkers() {
        // Load existing markers from config - handled by existing config loading
    }

    setupMapPreview() {
        const apiKeyInput=document.querySelector('[data-path="authentication.api_keys.google_maps"]');
        const latInput=document.querySelector('[data-path="integrations.maps.center.lat"]');
        const lngInput=document.querySelector('[data-path="integrations.maps.center.lng"]');
        const zoomInput=document.querySelector('[data-path="integrations.maps.zoom"]');

        if(apiKeyInput) {
            apiKeyInput.addEventListener('input',() => this.updateMapPreview());
        }
        if(latInput) {
            latInput.addEventListener('input',() => {
                this.updateMapPreview();
                // Also update map center if interactive map exists
                if(this.currentMap) {
                    const lat=parseFloat(latInput.value)||38.8977;
                    const lng=parseFloat(document.querySelector('[data-path="integrations.maps.center.lng"]')?.value)||-77.0365;
                    this.currentMap.setCenter({lat,lng});
                }
            });
        }
        if(lngInput) {
            lngInput.addEventListener('input',() => {
                this.updateMapPreview();
                // Also update map center if interactive map exists
                if(this.currentMap) {
                    const lat=parseFloat(document.querySelector('[data-path="integrations.maps.center.lat"]')?.value)||38.8977;
                    const lng=parseFloat(lngInput.value)||-77.0365;
                    this.currentMap.setCenter({lat,lng});
                }
            });
        }
        if(zoomInput) {
            zoomInput.addEventListener('input',() => {
                this.updateMapPreview();
                // Also update map zoom if interactive map exists
                if(this.currentMap) {
                    const zoom=parseInt(zoomInput.value)||10;
                    this.currentMap.setZoom(zoom);
                }
            });
        }

        // Listen for marker changes to update the interactive map
        document.addEventListener('input',(e) => {
            if(e.target.matches('.marker-title, .marker-lat, .marker-lng')&&this.currentMap) {
                // Debounce the update to avoid too frequent refreshes
                clearTimeout(this.mapUpdateTimeout);
                this.mapUpdateTimeout=setTimeout(() => {
                    this.addExistingMarkersToMap(this.currentMap);
                },500);
            }
        });

        // Listen for marker removal to update the interactive map
        document.addEventListener('click',(e) => {
            if(e.target.matches('.remove-marker, .remove-marker *')&&this.currentMap) {
                // Small delay to let the DOM update
                setTimeout(() => {
                    this.addExistingMarkersToMap(this.currentMap);
                },100);
            }
        });
    }

    updateMapPreview() {
        const apiKey=document.querySelector('[data-path="authentication.api_keys.google_maps"]')?.value;
        const lat=parseFloat(document.querySelector('[data-path="integrations.maps.center.lat"]')?.value)||38.8977;
        const lng=parseFloat(document.querySelector('[data-path="integrations.maps.center.lng"]')?.value)||-77.0365;
        const zoom=parseInt(document.querySelector('[data-path="integrations.maps.zoom"]')?.value)||10;
        const mapPreview=document.getElementById('map-preview');

        debugLog('Map preview update - API Key:',apiKey? 'Present':'Missing','Center:',lat,lng,'Zoom:',zoom);

        if(!mapPreview) {
            debugLog('Map preview container not found','error');
            return;
        }

        if(!apiKey||apiKey.trim()==='') {
            mapPreview.innerHTML='<div class="text-muted d-flex align-items-center justify-content-center h-100"><i class="fas fa-map-marked-alt fa-2x mb-2"></i><br>Enter API key to load map preview</div>';
            return;
        }

        // Validate API key format (Google Maps API keys are typically 39 characters)
        if(apiKey.length<20||!/^[A-Za-z0-9_-]+$/.test(apiKey)) {
            mapPreview.innerHTML=`
                <div class="text-muted d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-exclamation-triangle"></i>&nbsp;
                    Invalid API key format. Expected alphanumeric string.
                </div>
            `;
            return;
        }

        // Check if there's an ongoing authentication failure
        if(window.googleMapsAuthFailed) {
            mapPreview.innerHTML=`
                <div class="text-muted d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-key"></i>&nbsp;
                    Previous authentication failed. Please verify your API key and try again.
                    <br><button onclick="window.resetGoogleMaps()" class="btn btn-sm btn-outline-secondary mt-2">Reset Maps</button>
                </div>
            `;
            return;
        }

        // Initialize interactive map with validation
        this.initInteractiveMap(apiKey,lat,lng,zoom,mapPreview);
    }

    initInteractiveMap(apiKey,centerLat,centerLng,zoomLevel,mapContainer) {
        // Clear existing content
        mapContainer.innerHTML=`
            <div id="interactive-map" style="height: 300px; width: 100%; border-radius: 8px;"></div>
            <div class="position-absolute top-0 end-0 m-2" style="z-index: 1000;">
                <small class="badge bg-dark">Click to add markers</small>
            </div>
        `;

        // Check if Google Maps API is already loaded and working
        if(window.google&&window.google.maps&&window.google.maps.Map) {
            debugLog('Google Maps API already loaded, creating map directly');
            try {
                this.createInteractiveMap(centerLat,centerLng,zoomLevel);
                return;
            } catch(error) {
                debugLog('Error creating map with existing API:',error,'error');
                // Continue to reload API if there's an error
            }
        }

        // Prevent multiple API loads
        if(window.googleMapsApiLoading) {
            debugLog('Google Maps API is already loading, waiting...');
            return;
        }

        // Remove any existing Google Maps scripts to prevent conflicts
        const existingScripts=document.querySelectorAll('script[src*="maps.googleapis.com"]');
        existingScripts.forEach(script => {
            debugLog('Removing existing Google Maps script');
            script.remove();
        });

        // Clear existing Google Maps objects
        if(window.google) {
            try {
                delete window.google;
            } catch(e) {
                window.google=undefined;
            }
        }

        // Set loading flag
        window.googleMapsApiLoading=true;

        // Create new script element
        const script=document.createElement('script');
        const callbackName=`initMapCallback_${Date.now()}`;
        script.src=`https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=${callbackName}&libraries=places`;
        script.async=true;
        script.defer=true;

        // Error handling for script loading
        script.onerror=() => {
            debugLog('Failed to load Google Maps API','error');
            window.googleMapsApiLoading=false;
            mapContainer.innerHTML=`
                <div class="text-muted d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-exclamation-triangle"></i>&nbsp;
                    Failed to load Google Maps. Please check your API key and network connection.
                </div>
            `;
        };

        // Define unique callback function to avoid conflicts
        window[callbackName]=() => {
            window.googleMapsApiLoading=false;
            try {
                // Check if API loaded successfully
                if(!window.google||!window.google.maps) {
                    throw new Error('Google Maps API failed to load properly');
                }

                debugLog('Google Maps API loaded successfully');
                this.createInteractiveMap(centerLat,centerLng,zoomLevel);

                // Clean up the callback
                delete window[callbackName];
            } catch(error) {
                debugLog('Error initializing map:',error,'error');
                mapContainer.innerHTML=`
                    <div class="text-muted d-flex align-items-center justify-content-center h-100">
                        <i class="fas fa-exclamation-triangle"></i>&nbsp;
                        ${error.message.includes('authentication')? 'Invalid API key or authentication failed':'Error loading map'}
                    </div>
                `;
            }
        };

        // Add global error handler for authentication failures
        window.gm_authFailure=() => {
            debugLog('Google Maps API authentication failed','error');
            window.googleMapsApiLoading=false;
            window.googleMapsAuthFailed=true; // Set flag for future checks
            mapContainer.innerHTML=`
                <div class="text-muted d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-key"></i>&nbsp;
                    Google Maps API key is invalid or has insufficient permissions.
                    <br><button onclick="window.resetGoogleMaps()" class="btn btn-sm btn-outline-secondary mt-2">Reset & Retry</button>
                </div>
            `;
        };

        document.head.appendChild(script);
    }

    createInteractiveMap(centerLat,centerLng,zoomLevel) {
        const mapDiv=document.getElementById('interactive-map');
        if(!mapDiv) {
            debugLog('Map container element not found','error');
            return;
        }

        // Validate Google Maps API availability
        if(!window.google||!window.google.maps||!window.google.maps.Map) {
            debugLog('Google Maps API not properly loaded','error');
            mapDiv.innerHTML=`
                <div class="text-muted d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-exclamation-triangle"></i>&nbsp;
                    Google Maps API not loaded properly. Please check your API key.
                </div>
            `;
            return;
        }

        // Validate coordinates
        if(isNaN(centerLat)||isNaN(centerLng)||isNaN(zoomLevel)) {
            debugLog('Invalid map coordinates or zoom level','error');
            return;
        }

        try {
            // Create map with error handling
            const map=new google.maps.Map(mapDiv,{
                center: {lat: parseFloat(centerLat),lng: parseFloat(centerLng)},
                zoom: parseInt(zoomLevel),
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                // Add additional options for better error handling
                disableDefaultUI: false,
                zoomControl: true,
                mapTypeControl: true,
                scaleControl: true,
                streetViewControl: true,
                rotateControl: true,
                fullscreenControl: true
            });

            // Wait for map to be ready before adding markers
            google.maps.event.addListenerOnce(map,'idle',() => {
                // Store map reference for later use
                this.currentMap=map;
                this.mapMarkers=[];

                // Add existing markers to the map
                this.addExistingMarkersToMap(map);

                // Add click listener for adding new markers
                map.addListener('click',(event) => {
                    this.addMarkerOnMapClick(event.latLng,map);
                });

                // Add double-click listener as alternative
                map.addListener('dblclick',(event) => {
                    // Prevent default zoom on double-click
                    event.stop();
                    this.addMarkerOnMapClick(event.latLng,map);
                });

                // Add right-click context menu for map center
                map.addListener('rightclick',(event) => {
                    const lat=event.latLng.lat();
                    const lng=event.latLng.lng();

                    if(confirm(`Set map center to this location?\nLat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`)) {
                        // Update the center input fields
                        const latInput=document.querySelector('[data-path="integrations.maps.center.lat"]');
                        const lngInput=document.querySelector('[data-path="integrations.maps.center.lng"]');

                        if(latInput) latInput.value=lat.toFixed(6);
                        if(lngInput) lngInput.value=lng.toFixed(6);

                        // Update map center
                        map.setCenter({lat,lng});

                        // Mark form as dirty
                        const form=latInput?.closest('form');
                        if(form) this.markFormDirty(form);

                        this.showToast('Map center updated','success');
                    }
                });
            });

        } catch(error) {
            debugLog('Error creating Google Maps:',error,'error');
            mapDiv.innerHTML=`
                <div class="text-muted d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-exclamation-triangle"></i>&nbsp;
                    Error creating map: ${error.message}
                </div>
            `;
        }
    }

    addExistingMarkersToMap(map) {
        try {
            // Clear existing map markers
            this.mapMarkers.forEach(marker => marker.setMap(null));
            this.mapMarkers=[];

            // Add markers from the form
            document.querySelectorAll('#markers-container .marker-item').forEach(item => {
                const markerLat=parseFloat(item.querySelector('.marker-lat').value);
                const markerLng=parseFloat(item.querySelector('.marker-lng').value);
                const markerTitle=item.querySelector('.marker-title').value||'Untitled Marker';

                if(!isNaN(markerLat)&&!isNaN(markerLng)) {
                    try {
                        const marker=new google.maps.Marker({
                            position: {lat: markerLat,lng: markerLng},
                            map: map,
                            title: markerTitle,
                            draggable: true
                        });

                        // Update form when marker is dragged
                        marker.addListener('dragend',(event) => {
                            const newLat=event.latLng.lat();
                            const newLng=event.latLng.lng();
                            item.querySelector('.marker-lat').value=newLat.toFixed(6);
                            item.querySelector('.marker-lng').value=newLng.toFixed(6);

                            // Mark form as dirty
                            const form=item.closest('form');
                            if(form) this.markFormDirty(form);
                        });

                        // Add info window
                        const infoWindow=new google.maps.InfoWindow({
                            content: `
                                <div class="map-tooltip">
                                    <div style="font-weight: 600; margin-bottom: 4px;">${markerTitle}</div>
                                    <div style="color: #666; font-size: 0.8rem; margin-bottom: 8px;">
                                        Lat: ${markerLat.toFixed(6)}<br>
                                        Lng: ${markerLng.toFixed(6)}
                                    </div>
                                    <button onclick="window.adminInterface.removeMarkerFromMap('${item.dataset.markerId}')" 
                                            style="background: var(--error); color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            `
                        });

                        marker.addListener('click',() => {
                            infoWindow.open(map,marker);
                        });

                        this.mapMarkers.push(marker);
                    } catch(markerError) {
                        debugLog('Error creating marker:',markerError,'error');
                    }
                }
            });
        } catch(error) {
            debugLog('Error adding existing markers to map:',error,'error');
        }
    }

    addMarkerOnMapClick(latLng,map) {
        const lat=latLng.lat();
        const lng=latLng.lng();

        // Add marker to the form
        this.addMarker();

        // Get the last added marker item and populate it
        const container=document.getElementById('markers-container');
        const lastItem=container.lastElementChild;

        if(lastItem) {
            lastItem.querySelector('.marker-lat').value=lat.toFixed(6);
            lastItem.querySelector('.marker-lng').value=lng.toFixed(6);

            // Try to get address from coordinates using reverse geocoding
            this.reverseGeocode(lat,lng,(address) => {
                if(address&&lastItem) {
                    lastItem.querySelector('.marker-title').value=address;
                } else {
                    lastItem.querySelector('.marker-title').value='New Marker';
                }
            });

            // Mark form as dirty
            const form=lastItem.closest('form');
            if(form) this.markFormDirty(form);
        }

        // Refresh map markers to include the new one
        this.addExistingMarkersToMap(map);

        // Show success feedback
        this.showToast('Marker added! Click on it to edit or remove.','success');
    }

    reverseGeocode(lat,lng,callback) {
        if(window.google&&window.google.maps&&window.google.maps.Geocoder) {
            const geocoder=new google.maps.Geocoder();
            const latlng={lat: lat,lng: lng};

            geocoder.geocode({location: latlng},(results,status) => {
                if(status==='OK'&&results&&results.length>0) {
                    // Get the first result's formatted address
                    const address=results[0].formatted_address;
                    callback(address);
                } else {
                    debugLog('Geocoder failed or no results found');
                    callback(null);
                }
            });
        } else {
            debugLog('Geocoder not available');
            callback(null);
        }
    }

    removeMarkerFromMap(markerId) {
        const markerItem=document.querySelector(`[data-marker-id="${markerId}"]`);
        if(markerItem) {
            markerItem.remove();

            // Mark form as dirty
            const form=markerItem.closest('form');
            if(form) this.markFormDirty(form);

            // Refresh map if it exists
            if(this.currentMap) {
                this.addExistingMarkersToMap(this.currentMap);
            }

            this.showToast('Marker removed successfully','info');
        }
    }

    showToast(message,type='info') {
        // Simple toast notification
        const toast=document.createElement('div');
        toast.className=`alert alert-${type==='success'? 'success':type==='error'? 'danger':'info'} position-fixed`;
        toast.style.cssText='top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
        toast.textContent=message;

        document.body.appendChild(toast);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.remove();
        },3000);
    }

    // Enhanced config loading to handle new dynamic components
    loadConfigData(configs) {
        debugLog('Loading config data:',configs);

        // Load navigation menu items - check multiple possible paths
        let menuItems=null;
        if(configs.site?.navigation?.menu_items) {
            menuItems=configs.site.navigation.menu_items;
        } else if(configs.main?.site?.navigation?.menu_items) {
            menuItems=configs.main.site.navigation.menu_items;
        } else if(configs.config?.site?.navigation?.menu_items) {
            menuItems=configs.config.site.navigation.menu_items;
        } else if(configs.navigation?.menu_items) {
            menuItems=configs.navigation.menu_items;
        }

        debugLog('Menu items found:',menuItems);
        if(menuItems) {
            this.loadNavigationItems(menuItems);
        }

        // Load plugin lists - check multiple possible paths
        let plugins=null;
        if(configs.plugins) {
            plugins=configs.plugins;
        } else if(configs.main?.plugins) {
            plugins=configs.main.plugins;
        } else if(configs.site?.plugins) {
            plugins=configs.site.plugins;
        }

        if(plugins) {
            this.loadPluginLists(plugins);
        }

        // Load map markers - check multiple possible paths
        let markers=null;

        if(configs.integrations?.maps?.markers) {
            markers=configs.integrations.maps.markers;
        } else if(configs.main?.integrations?.maps?.markers) {
            markers=configs.main.integrations.maps.markers;
        } else if(configs.config?.integrations?.maps?.markers) {
            markers=configs.config.integrations.maps.markers;
        } else if(configs.maps?.markers) {
            markers=configs.maps.markers;
        }

        if(markers&&markers.length>0) {
            debugLog('Loading',markers.length,'markers from configuration');
            this.loadMapMarkers(markers);
        }

        // Update map preview
        this.updateMapPreview();
    }

    loadNavigationItems(menuItems) {
        const container=document.getElementById('menu-items-container');
        if(!container) return;

        container.innerHTML='';

        menuItems.forEach((item,index) => {
            this.addMenuItem();
            const lastItem=container.lastElementChild;
            lastItem.querySelector('.menu-title').value=item.title||'';
            lastItem.querySelector('.menu-url').value=item.url||'';
        });
    }

    loadPluginLists(plugins) {
        // Load keep plugins
        if(plugins.keep) {
            const keepContainer=document.getElementById('keep-plugins-container');
            if(keepContainer) {
                keepContainer.innerHTML='';
                plugins.keep.forEach(plugin => {
                    this.addKeepPlugin();
                    const lastItem=keepContainer.lastElementChild;
                    lastItem.querySelector('.keep-plugin-name').value=plugin;
                });
            }
        }

        // Load install plugins
        if(plugins.install) {
            const installContainer=document.getElementById('install-plugins-container');
            if(installContainer) {
                installContainer.innerHTML='';
                plugins.install.forEach(plugin => {
                    this.addInstallPlugin();
                    const lastItem=installContainer.lastElementChild;
                    lastItem.querySelector('.install-plugin-name').value=plugin;
                });
            }
        }
    }

    loadMapMarkers(markers) {
        const container=document.getElementById('markers-container');
        if(!container) {
            debugLog('Markers container not ready, storing',markers.length,'markers for later loading');
            // Store markers for later when the container becomes available
            this.pendingMarkers=markers;
            return;
        }

        container.innerHTML='';

        markers.forEach((marker,index) => {
            this.addMarker();
            const lastItem=container.lastElementChild;
            if(lastItem) {
                lastItem.querySelector('.marker-title').value=marker.title||'';
                lastItem.querySelector('.marker-lat').value=marker.lat||'';
                lastItem.querySelector('.marker-lng').value=marker.lng||'';
            }
        });

        debugLog('Successfully loaded',markers.length,'markers into the form');
        // Clear pending markers since they've been loaded
        this.pendingMarkers=null;

        // Update the map preview to show the loaded markers
        setTimeout(() => this.updateMapPreview(),100);
    }

    // Enhanced config saving to handle new dynamic components
    collectDynamicConfigData(configType=null) {
        const dynamicData={};
        debugLog('DEBUG: Collecting dynamic data for configType:',configType);

        // Collect navigation menu items (only for main config)
        if(!configType||configType==='main') {
            const menuItems=[];
            const menuContainer=document.getElementById('menu-items-container');
            debugLog('DEBUG: Menu container found:',!!menuContainer);
            if(menuContainer) {
                debugLog('DEBUG: Menu items in container:',menuContainer.querySelectorAll('.menu-item').length);
            }

            document.querySelectorAll('#menu-items-container .menu-item').forEach(item => {
                const title=item.querySelector('.menu-title').value.trim();
                const url=item.querySelector('.menu-url').value.trim();
                debugLog('DEBUG: Menu item found - Title:',title,'URL:',url);
                if(title&&url) {
                    menuItems.push({title,url});
                }
            });
            // Always save menu items, even if empty array (to clear existing items)
            dynamicData['site.navigation.menu_items']=menuItems;
            debugLog('DEBUG: Final menu items to save:',menuItems);
        }

        // Collect plugins (only for plugins config)
        if(!configType||configType==='plugins') {
            // Collect keep plugins
            const keepPlugins=[];
            const keepContainer=document.getElementById('keep-plugins-container');
            debugLog('DEBUG: Keep plugins container found:',!!keepContainer);
            if(keepContainer) {
                debugLog('DEBUG: Keep plugin inputs found:',keepContainer.querySelectorAll('.keep-plugin-name').length);
            }

            document.querySelectorAll('#keep-plugins-container .keep-plugin-name').forEach(input => {
                const plugin=input.value.trim();
                debugLog('DEBUG: Keep plugin input value:',plugin);
                if(plugin) keepPlugins.push(plugin);
            });
            // Always save keep plugins, even if empty array (to clear existing items)
            dynamicData['plugins.keep']=keepPlugins;
            debugLog('DEBUG: Final keep plugins to save:',keepPlugins);

            // Collect install plugins
            const installPlugins=[];
            const installContainer=document.getElementById('install-plugins-container');
            debugLog('DEBUG: Install plugins container found:',!!installContainer);
            if(installContainer) {
                debugLog('DEBUG: Install plugin inputs found:',installContainer.querySelectorAll('.install-plugin-name').length);
            }

            document.querySelectorAll('#install-plugins-container .install-plugin-name').forEach(input => {
                const plugin=input.value.trim();
                debugLog('DEBUG: Install plugin input value:',plugin);
                if(plugin) installPlugins.push(plugin);
            });
            // Always save install plugins, even if empty array (to clear existing items)
            dynamicData['plugins.install']=installPlugins;
            debugLog('DEBUG: Final install plugins to save:',installPlugins);
        }

        // Collect map markers (only for integrations config)
        if(!configType||configType==='integrations') {
            const markers=[];
            document.querySelectorAll('#markers-container .marker-item').forEach(item => {
                const title=item.querySelector('.marker-title').value.trim();
                const lat=parseFloat(item.querySelector('.marker-lat').value);
                const lng=parseFloat(item.querySelector('.marker-lng').value);
                if(title&&!isNaN(lat)&&!isNaN(lng)) {
                    markers.push({title,lat,lng});
                }
            });
            // Always save markers, even if empty array (to clear existing items)
            dynamicData['integrations.maps.markers']=markers;
        }

        return dynamicData;
    }

    // UI Enhancement Methods
    addLoadingState(element) {
        if(element&&!element.classList.contains('loading')) {
            element.classList.add('loading');
        }
    }

    removeLoadingState(element) {
        if(element) {
            element.classList.remove('loading');
        }
    }

    validateForm(form) {
        const requiredInputs=form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid=true;

        requiredInputs.forEach(input => {
            if(!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid=false;
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });

        return isValid;
    }

    addInputValidation() {
        // Real-time validation for all inputs
        document.addEventListener('input',(e) => {
            if(e.target.classList.contains('form-input')||
                e.target.classList.contains('form-select')||
                e.target.classList.contains('form-textarea')) {

                // Remove invalid state on input
                e.target.classList.remove('is-invalid');

                // Add valid state if has value
                if(e.target.value.trim()) {
                    e.target.classList.add('is-valid');
                } else {
                    e.target.classList.remove('is-valid');
                }

                // Special validation for email inputs
                if(e.target.type==='email') {
                    const emailRegex=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if(e.target.value&&!emailRegex.test(e.target.value)) {
                        e.target.classList.add('is-invalid');
                        e.target.classList.remove('is-valid');
                    }
                }

                // Special validation for URL inputs
                if(e.target.type==='url') {
                    try {
                        if(e.target.value&&e.target.value.trim()) {
                            new URL(e.target.value);
                            e.target.classList.remove('is-invalid');
                            e.target.classList.add('is-valid');
                        }
                    } catch {
                        if(e.target.value&&e.target.value.trim()) {
                            e.target.classList.add('is-invalid');
                            e.target.classList.remove('is-valid');
                        }
                    }
                }
            }
        });
    }

    enhanceConfigSaving() {
        // Override the existing saveConfiguration method with enhanced version
        const originalSaveConfig=this.saveConfiguration.bind(this);

        this.saveConfiguration=async function(configType) {
            const form=document.getElementById(`${configType}-config-form`);
            const saveBtn=form?.querySelector('.save-config-btn');

            if(!form) return;

            // Add loading state
            this.addLoadingState(form);
            if(saveBtn) {
                saveBtn.disabled=true;
                const originalText=saveBtn.textContent;
                saveBtn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...';

                try {
                    await originalSaveConfig(configType);

                    // Success feedback
                    saveBtn.innerHTML='<i class="fas fa-check"></i> Saved!';
                    saveBtn.classList.add('btn-success');
                    saveBtn.classList.remove('btn-primary');

                    setTimeout(() => {
                        saveBtn.innerHTML=originalText;
                        saveBtn.classList.remove('btn-success');
                        saveBtn.classList.add('btn-primary');
                    },2000);

                } catch(error) {
                    // Error feedback
                    saveBtn.innerHTML='<i class="fas fa-exclamation-triangle"></i> Error';
                    saveBtn.classList.add('btn-danger');
                    saveBtn.classList.remove('btn-primary');

                    setTimeout(() => {
                        saveBtn.innerHTML=originalText;
                        saveBtn.classList.remove('btn-danger');
                        saveBtn.classList.add('btn-primary');
                    },3000);

                    throw error;
                } finally {
                    this.removeLoadingState(form);
                    saveBtn.disabled=false;
                }
            } else {
                try {
                    await originalSaveConfig(configType);
                } finally {
                    this.removeLoadingState(form);
                }
            }
        }.bind(this);
    }

    addAnimatedTransitions() {
        // Enhanced tab switching with animations
        const style=document.createElement('style');
        style.textContent=`
            .subtab-content {
                opacity: 0;
                transform: translateX(20px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: none;
            }
            
            .subtab-content.active {
                opacity: 1;
                transform: translateX(0);
                display: block;
            }
            
            .form-input, .form-select, .form-textarea {
                transition: all 0.2s ease;
            }
            
            .form-input:focus, .form-select:focus, .form-textarea:focus {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
            }
            
            .btn {
                transition: all 0.2s ease;
            }
            
            .btn:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .btn:active {
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);
    }

    addProgressIndicators() {
        // Add progress indicators for forms with multiple steps
        const configTabs=document.querySelectorAll('.tab-link[data-subtab]');

        configTabs.forEach(tab => {
            const indicator=document.createElement('span');
            indicator.className='status-indicator';
            indicator.style.marginLeft='8px';
            tab.appendChild(indicator);
        });
    }

    addTooltips() {
        // Add helpful tooltips to form elements
        const tooltips={
            '[data-path="security.login_protection.custom_login_url"]': 'Create a custom login URL to hide the default wp-login.php page',
            '[data-path="authentication.api_keys.google_maps"]': 'Required for map functionality. Get your API key from Google Cloud Console',
            '[data-path="wp_security_audit_log.pruning_date_e"]': 'Logs older than this will be automatically deleted',
            '[data-path="security.two_factor_auth.grace_period_days"]': 'Users have this many days to set up 2FA before access is blocked',
            '[data-path="integrations.maps.zoom"]': 'Map zoom level: 1 (world view) to 20 (building level)'
        };

        Object.keys(tooltips).forEach(selector => {
            const element=document.querySelector(selector);
            if(element) {
                element.setAttribute('data-tooltip',tooltips[selector]);
                element.classList.add('tooltip');
            }
        });
    }

    addSmartDefaults() {
        // Add intelligent defaults and suggestions
        document.addEventListener('focus',(e) => {
            if(e.target.classList.contains('form-input')) {
                // Auto-suggest for certain fields
                if(e.target.dataset.path==='integrations.maps.center.lat'&&!e.target.value) {
                    e.target.placeholder='38.8977 (Washington, DC)';
                }
                if(e.target.dataset.path==='integrations.maps.center.lng'&&!e.target.value) {
                    e.target.placeholder='-77.0365 (Washington, DC)';
                }
            }
        });
    }

    enhanceUserExperience() {
        this.addInputValidation();
        this.enhanceConfigSaving();
        this.addAnimatedTransitions();
        this.addProgressIndicators();
        this.addTooltips();
        this.addSmartDefaults();

        // Add auto-save indication
        let autoSaveTimer;
        document.addEventListener('input',(e) => {
            if(e.target.classList.contains('config-input')) {
                clearTimeout(autoSaveTimer);

                // Show "unsaved changes" indicator
                const indicator=document.createElement('div');
                indicator.className='unsaved-indicator';
                indicator.innerHTML='<i class="fas fa-circle text-warning"></i> Unsaved changes';
                indicator.style.cssText='position: fixed; top: 20px; right: 20px; background: white; padding: 8px 12px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000; font-size: 12px;';

                // Remove existing indicators
                document.querySelectorAll('.unsaved-indicator').forEach(el => el.remove());
                document.body.appendChild(indicator);

                // Auto-remove after delay
                autoSaveTimer=setTimeout(() => {
                    indicator.remove();
                },3000);
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown',(e) => {
            // Ctrl/Cmd + S to save current form
            if((e.ctrlKey||e.metaKey)&&e.key==='s') {
                e.preventDefault();
                const activeTab=document.querySelector('.subtab-content.active');
                if(activeTab) {
                    const saveBtn=activeTab.querySelector('.save-config-btn');
                    if(saveBtn) {
                        saveBtn.click();
                    }
                }
            }

            // Ctrl/Cmd + / to toggle help modal
            if((e.ctrlKey||e.metaKey)&&e.key==='/') {
                e.preventDefault();
                this.toggleHelpModal();
            }

            // Escape to close modals
            if(e.key==='Escape') {
                this.closeModals();
            }
        });

        // Setup help modal
        this.setupHelpModal();
    }

    setupHelpModal() {
        const helpBtn=document.getElementById('help-btn');
        const helpModal=document.getElementById('help-modal');
        const closeBtn=helpModal?.querySelector('.close-modal');

        if(helpBtn&&helpModal) {
            helpBtn.addEventListener('click',() => this.toggleHelpModal());

            if(closeBtn) {
                closeBtn.addEventListener('click',() => this.closeHelpModal());
            }

            // Close on backdrop click
            helpModal.addEventListener('click',(e) => {
                if(e.target===helpModal) {
                    this.closeHelpModal();
                }
            });
        }
    }

    toggleHelpModal() {
        const helpModal=document.getElementById('help-modal');
        if(helpModal) {
            if(helpModal.classList.contains('show')) {
                this.closeHelpModal();
            } else {
                this.openHelpModal();
            }
        }
    }

    openHelpModal() {
        const helpModal=document.getElementById('help-modal');
        if(helpModal) {
            helpModal.style.display='flex';
            // Force reflow for animation
            helpModal.offsetHeight;
            helpModal.classList.add('show');
            document.body.style.overflow='hidden';
        }
    }

    closeHelpModal() {
        const helpModal=document.getElementById('help-modal');
        if(helpModal) {
            helpModal.classList.remove('show');
            document.body.style.overflow='';
            setTimeout(() => {
                helpModal.style.display='none';
            },300);
        }
    }

    closeModals() {
        this.closeHelpModal();

        // Close completion modal if it exists
        const completionModal=document.getElementById('deployment-completion-modal');
        if(completionModal) {
            completionModal.remove();
        }

        // Add other modal closing logic here if needed
    }

    loadDynamicLists(configs) {
        debugLog('Loading dynamic lists with config data:',configs);

        const mainConfig=configs.main||configs.config||configs;

        // Load countries list
        const countriesPath='security.geo_blocking.allowed_countries';
        const countries=this.getNestedValue(mainConfig,countriesPath);
        if(countries&&Array.isArray(countries)) {
            debugLog('Loading countries:',countries);
            loadDynamicListFromConfig('countries-list',countries);
        }

        // Load IP whitelist
        const ipPath='security.ip_whitelist.ips';
        let ips=this.getNestedValue(mainConfig,ipPath);

        // Handle both array and string formats
        if(typeof ips==='string') {
            ips=ips.split('\n').filter(ip => ip.trim());
        }
        if(ips&&Array.isArray(ips)) {
            debugLog('Loading IPs:',ips);
            loadDynamicListFromConfig('ip-list',ips);
        }
    }

    handleEditField(button) {
        const targetId=button.dataset.target;
        const targetInput=document.getElementById(targetId);

        if(!targetInput) {
            debugLog('Target input not found:',targetId,'error');
            return;
        }

        const isCurrentlyDisabled=targetInput.disabled;
        const isTokenField=targetInput.classList.contains('token-field');

        if(isCurrentlyDisabled) {
            // Enable editing
            targetInput.disabled=false;
            targetInput.focus();

            // For token fields, show the actual value when editing
            if(isTokenField) {
                targetInput.type='text';
            }

            button.innerHTML='<i class="fas fa-check"></i> Save';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            button.title='Save changes';
        } else {
            // Save and disable
            targetInput.disabled=true;

            // For token fields, hide the value again
            if(isTokenField) {
                targetInput.type='password';
            }

            button.innerHTML='<i class="fas fa-edit"></i> Edit';
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');

            // Update the button title based on the field
            if(targetId.includes('token')) {
                button.title=targetId.includes('git')? 'Edit Git Token':'Edit Kinsta Token';
            } else if(targetId.includes('company')) {
                button.title='Edit Company ID';
            }

            // Handle mixed saving for git config form
            this.handleMixedConfigSave(targetInput);

            // Show notification
            showNotification('Field updated successfully','success');
        }
    }

    handleMixedConfigSave(targetInput) {
        const form=targetInput.closest('form');
        if(!form) return;

        const configType=targetInput.dataset.configType;
        const dataPath=targetInput.dataset.path;
        const value=targetInput.value;

        if(configType==='main') {
            // This field should be saved to config.json (main config)
            const pathParts=dataPath.split('.');
            const data={};

            // Build nested object based on path
            let current=data;
            for(let i=0;i<pathParts.length-1;i++) {
                current[pathParts[i]]={};
                current=current[pathParts[i]];
            }
            current[pathParts[pathParts.length-1]]=value;

            // Save to main config
            this.saveSpecificConfig('main',data);
        } else if(configType==='git') {
            // This field should be saved to git.json
            const data={};
            data[dataPath]=value;
            this.saveSpecificConfig('git',data);
        } else {
            // Default behavior - mark form as dirty for batch save
            this.markFormDirty(form);
        }
    }

    async saveSpecificConfig(type,data) {
        try {
            // First, get the existing config to merge with
            const configResponse=await fetch('?action=get_configs');
            const configResult=await configResponse.json();

            if(!configResult.success) {
                throw new Error('Failed to fetch existing config');
            }

            // Merge with existing config
            const existingConfig=configResult.data[type]||{};
            const mergedConfig={...existingConfig,...data};

            // Save the merged config
            const saveResponse=await fetch('?action=save_config',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    data: mergedConfig
                })
            });

            const result=await saveResponse.json();

            if(result.success) {
                debugLog(`${type} config saved successfully`);
            } else {
                debugLog(`Failed to save ${type} config:`,result.message,'error');
                showNotification(`Failed to save configuration: ${result.message}`,'error');
            }
        } catch(error) {
            debugLog(`Error saving ${type} config:`,error,'error');
            showNotification('Error saving configuration','error');
        }
    }

    async saveGitConfigMixed(form) {
        const inputs=form.querySelectorAll('input, select, textarea');
        const gitConfig={};
        const mainConfig={};

        inputs.forEach(input => {
            const path=input.dataset.path;
            const configType=input.dataset.configType;

            if(path) {
                let value=input.value;

                if(input.type==='checkbox') {
                    value=input.checked;
                } else if(input.type==='number') {
                    value=Number(value);
                }

                if(configType==='main') {
                    // This goes to config.json
                    this.setNestedValue(mainConfig,path,value);
                } else {
                    // This goes to git.json (default)
                    this.setNestedValue(gitConfig,path,value);
                }
            }
        });

        try {
            let success=true;
            let errorMessage='';

            // Save git config if there's data
            if(Object.keys(gitConfig).length>0) {
                const gitResponse=await fetch('?action=save_config',{
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({type: 'git',data: gitConfig})
                });
                const gitResult=await gitResponse.json();
                if(!gitResult.success) {
                    success=false;
                    errorMessage+=`Git config error: ${gitResult.message}. `;
                }
            }

            // Save main config if there's data
            if(Object.keys(mainConfig).length>0) {
                const mainResponse=await fetch('?action=save_config',{
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({type: 'main',data: mainConfig})
                });
                const mainResult=await mainResponse.json();
                if(!mainResult.success) {
                    success=false;
                    errorMessage+=`Main config error: ${mainResult.message}. `;
                }
            }

            if(success) {
                this.showAlert('Git configuration saved successfully!','success');
                this.markFormClean(form);
            } else {
                this.showAlert(`Configuration save failed: ${errorMessage}`,'error');
            }
        } catch(error) {
            debugLog('Error saving mixed git config:',error,'error');
            this.showAlert('Error saving configuration','error');
        }
    }

    // =============================================
    // OTHER CONTENTS MANAGEMENT (Issues & Endorsements)
    // =============================================

    async loadOtherContents() {
        if(this.currentTab!=='contents') return;

        await Promise.all([
            this.loadContentType('issues'),
            this.loadContentType('endorsements'),
            this.loadContentType('news'),
            this.loadContentType('posts'),
            this.loadContentType('testimonials'),
            this.loadContentType('sliders')
        ]);
    }

    async loadContentType(type) {
        try {
            const response=await fetch(`?action=get_other_contents&type=${type}`);
            const result=await response.json();

            if(result.success) {
                this.renderContentList(type,result.data);
            } else {
                debugLog(`Failed to load ${type}:`,result.message,'error');
                this.showAlert(`Failed to load ${type}`,'error');
            }
        } catch(error) {
            debugLog(`Error loading ${type}:`,error,'error');
            this.showAlert(`Failed to load ${type}`,'error');
        }
    }

    renderContentList(type,contents) {
        const listContainer=document.getElementById(`${type}-list`);
        if(!listContainer) return;

        if(!contents||contents.length===0) {
            const iconClass=type==='issues'? 'clipboard-list':
                type==='testimonials'? 'quote-left':
                    type==='sliders'? 'images':'star';
            listContainer.innerHTML=`
                <div class="content-empty">
                    <i class="fas fa-${iconClass}"></i>
                    <h4>No ${type} found</h4>
                    <p>Click "Add New ${type.slice(0,-1)}" to get started</p>
                </div>
            `;
            return;
        }

        if(type==='testimonials') {
            // Special rendering for testimonials
            listContainer.innerHTML=contents.map((item,index) => `
                <div class="content-item" data-type="${type}" data-index="${index}">
                    <div class="content-item-number">${index+1}</div>
                    <div class="content-item-body">
                        <div class="content-item-header">
                            <div class="content-item-display">
                                <h4 class="content-title">${this.escapeHtml(item.client_name||'')}</h4>
                                <span class="text-muted">${this.escapeHtml(item.client_position||'')}</span>
                            </div>
                            <div class="content-item-actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm edit-content-btn">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm delete-content-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="content-item-display">
                            <p class="content-text">${this.escapeHtml(item.client_comment||'')}</p>
                        </div>
                        
                        <div class="content-item-edit">
                            <div class="content-item-form">
                                <div class="form-group">
                                    <label class="form-label">Client Name</label>
                                    <input type="text" class="form-input content-name-input" value="${this.escapeHtml(item.client_name||'')}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Client Position</label>
                                    <input type="text" class="form-input content-position-input" value="${this.escapeHtml(item.client_position||'')}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Testimonial</label>
                                    <textarea class="form-textarea content-comment-input" rows="4">${this.escapeHtml(item.client_comment||'')}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Image (optional)</label>
                                    <input type="url" class="form-input content-image-input" value="${this.escapeHtml(item.client_img||'')}" placeholder="https://example.com/image.jpg">
                                    <div class="image-upload-section" style="margin-top: 10px;">
                                        <input type="file" class="form-input content-image-file" accept="image/*" style="margin-bottom: 5px;">
                                        <button type="button" class="btn btn-outline-primary btn-sm upload-content-image-btn"><i class="fas fa-upload"></i> Upload Image</button>
                                        ${item.client_img? `<div class="current-image" style="margin-top: 10px;"><img src="${this.escapeHtml(item.client_img)}" alt="Current image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"></div>`:''}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm save-content-btn">
                                        <i class="fas fa-check"></i> Save
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-btn">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        } else if(type==='sliders') {
            // Special rendering for sliders
            listContainer.innerHTML=contents.map((item,index) => `
                <div class="content-item" data-type="${type}" data-index="${index}">
                    <div class="content-item-number">${index+1}</div>
                    <div class="content-item-body">
                        <div class="content-item-header">
                            <div class="content-item-display">
                                <h4 class="content-title">Slide ${index+1}</h4>
                                <span class="text-muted">Slider Content</span>
                            </div>
                            <div class="content-item-actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm edit-content-btn">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm delete-content-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="content-item-display">
                            <p class="content-text">${this.escapeHtml(this.extractTextFromSlider(item))}</p>
                        </div>
                        
                        <div class="content-item-edit">
                            <div class="content-item-form">
                                <div class="form-group">
                                    <label class="form-label">Slide Name</label>
                                    <input type="text" class="form-input content-name-input" value="slide-${index+1}" readonly style="background-color: #f8f9fa; color: #6c757d;" title="Slide name is auto-generated">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Slide Title</label>
                                    <input type="text" class="form-input slide-title-input" value="${this.escapeHtml(this.parseSliderContent(item).title)}" placeholder="Enter slide title">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Slide Content</label>
                                    <textarea class="form-textarea slide-content-input" rows="4" placeholder="Enter slide description text">${this.escapeHtml(this.parseSliderContent(item).content)}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Slide Image</label>
                                    <input type="text" class="form-input slide-image-input" value="${this.escapeHtml(item.image_url||'')}" placeholder="Image URL or upload below">
                                    <div class="image-upload-section" style="margin-top: 10px;">
                                        <input type="file" class="form-input slide-image-file" accept="image/*" style="margin-bottom: 5px;">
                                        <button type="button" class="btn btn-outline-primary btn-sm upload-slide-image-btn">Upload Image</button>
                                        ${item.image_url? `<div class="current-image" style="margin-top: 10px;"><img src="${this.escapeHtml(item.image_url)}" alt="Current slide image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"></div>`:''}
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group" style="flex: 1; margin-right: 10px;">
                                        <label class="form-label">Button Text</label>
                                        <input type="text" class="form-input slide-button-text-input" value="${this.escapeHtml(this.parseSliderContent(item).buttonText)}" placeholder="Button text">
                                    </div>
                                    <div class="form-group" style="flex: 1; margin-left: 10px;">
                                        <label class="form-label">Button URL</label>
                                        <input type="text" class="form-input slide-button-url-input" value="${this.escapeHtml(this.parseSliderContent(item).buttonUrl)}" placeholder="Button URL">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group" style="flex: 1; margin-right: 10px;">
                                        <label class="form-label">Button Style</label>
                                        <select class="form-select slide-button-style-input">
                                            <option value="primary" ${this.parseSliderContent(item).buttonStyle==='primary'? 'selected':''}>Primary</option>
                                            <option value="secondary" ${this.parseSliderContent(item).buttonStyle==='secondary'? 'selected':''}>Secondary</option>
                                            <option value="outline" ${this.parseSliderContent(item).buttonStyle==='outline'? 'selected':''}>Outline</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="flex: 1; margin-left: 10px;">
                                        <label class="form-label">Button Target</label>
                                        <select class="form-select slide-button-target-input">
                                            <option value="_self" ${this.parseSliderContent(item).buttonTarget==='_self'? 'selected':''}>Same Window</option>
                                            <option value="_blank" ${this.parseSliderContent(item).buttonTarget==='_blank'? 'selected':''}>New Window</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="content-item-actions">
                                    <button type="button" class="btn btn-outline-secondary save-content-btn">Save</button>
                                    <button type="button" class="btn btn-outline-secondary cancel-content-btn">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            // Standard rendering for other content types
            listContainer.innerHTML=contents.map((item,index) => `
                <div class="content-item" data-type="${type}" data-index="${index}">
                    <div class="content-item-number">${index+1}</div>
                    <div class="content-item-body">
                        <div class="content-item-header">
                            <div class="content-item-display">
                                <h4 class="content-title">${this.escapeHtml(item.title)}</h4>
                                ${item.image? `<span class="badge bg-primary"><i class="fas fa-image"></i> Has Image</span>`:''}
                            </div>
                            <div class="content-item-actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm edit-content-btn">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm delete-content-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="content-item-display">
                            <p class="content-text">${this.escapeHtml(item.content)}</p>
                        </div>
                        
                        <div class="content-item-edit">
                            <div class="content-item-form">
                                <div class="form-group">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-input content-title-input" value="${this.escapeHtml(item.title)}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Content</label>
                                    <textarea class="form-textarea content-text-input" rows="4">${this.escapeHtml(item.content)}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Image (optional)</label>
                                    <input type="url" class="form-input content-image-input" value="${this.escapeHtml(item.image||'')}" placeholder="https://example.com/image.jpg">
                                    <div class="image-upload-section" style="margin-top: 10px;">
                                        <input type="file" class="form-input content-image-file" accept="image/*" style="margin-bottom: 5px;">
                                        <button type="button" class="btn btn-outline-primary btn-sm upload-content-image-btn"><i class="fas fa-upload"></i> Upload Image</button>
                                        ${item.image? `<div class="current-image" style="margin-top: 10px;"><img src="${this.escapeHtml(item.image)}" alt="Current image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"></div>`:''}
                                    </div>
                                    <small class="text-muted">Leave empty if no image is needed. Image will only display if URL is provided.</small>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm save-content-btn">
                                        <i class="fas fa-check"></i> Save
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-btn">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Add event listeners
        this.setupContentEventListeners(type);
    }

    setupContentEventListeners(type) {
        const listContainer=document.getElementById(`${type}-list`);
        if(!listContainer) return;

        // Edit buttons
        listContainer.querySelectorAll('.edit-content-btn').forEach((btn,index) => {
            btn.addEventListener('click',() => this.editContentItem(type,index));
        });

        // Delete buttons
        listContainer.querySelectorAll('.delete-content-btn').forEach((btn,index) => {
            btn.addEventListener('click',() => this.deleteContentItem(type,index));
        });

        // Save buttons
        listContainer.querySelectorAll('.save-content-btn').forEach((btn,index) => {
            btn.addEventListener('click',() => this.saveContentItem(type,index));
        });

        // Cancel buttons
        listContainer.querySelectorAll('.cancel-edit-btn').forEach((btn,index) => {
            btn.addEventListener('click',() => this.cancelEditContentItem(type,index));
        });

        // Upload slide image buttons (for sliders only)
        if(type==='sliders') {
            listContainer.querySelectorAll('.upload-slide-image-btn').forEach((btn,index) => {
                btn.addEventListener('click',() => this.uploadSlideImage(index));
            });
        }

        // Upload content image buttons (for all other content types)
        if(type!=='sliders') {
            listContainer.querySelectorAll('.upload-content-image-btn').forEach((btn,index) => {
                btn.addEventListener('click',() => this.uploadContentImage(type,index));
            });
        }
    }

    editContentItem(type,index) {
        const item=document.querySelector(`#${type}-list .content-item[data-index="${index}"]`);
        if(item) {
            item.classList.add('editing');
        }
    }

    cancelEditContentItem(type,index) {
        const item=document.querySelector(`#${type}-list .content-item[data-index="${index}"]`);
        if(item) {
            item.classList.remove('editing');
            // Reset form values
            this.loadContentType(type);
        }
    }

    async uploadSlideImage(index) {
        const item=document.querySelector(`#sliders-list .content-item[data-index="${index}"]`);
        if(!item) return;

        const fileInput=item.querySelector('.slide-image-file');
        const imageUrlInput=item.querySelector('.slide-image-input');
        const uploadBtn=item.querySelector('.upload-slide-image-btn');

        if(!fileInput.files.length) {
            this.showAlert('Please select an image file first','error');
            return;
        }

        const file=fileInput.files[0];

        // Validate file type
        if(!file.type.startsWith('image/')) {
            this.showAlert('Please select a valid image file','error');
            return;
        }

        // Validate file size (max 10MB)
        if(file.size>10*1024*1024) {
            this.showAlert('Image file size must be less than 10MB','error');
            return;
        }

        try {
            uploadBtn.disabled=true;
            uploadBtn.textContent='Uploading...';

            const formData=new FormData();
            formData.append('image',file);
            formData.append('folder','slides');

            const response=await fetch('?action=upload_image',{
                method: 'POST',
                body: formData
            });

            const result=await response.json();

            if(!result.success) {
                throw new Error(result.message||'Failed to upload image');
            }

            // Update the URL input with the uploaded image path
            imageUrlInput.value=result.data.url;

            // Update the preview
            const currentImageDiv=item.querySelector('.current-image');
            if(currentImageDiv) {
                currentImageDiv.innerHTML=`<img src="${result.data.url}" alt="Current slide image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">`;
            } else {
                const imageUploadSection=item.querySelector('.image-upload-section');
                imageUploadSection.insertAdjacentHTML('beforeend',`<div class="current-image" style="margin-top: 10px;"><img src="${result.data.url}" alt="Current slide image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"></div>`);
            }

            // Clear the file input
            fileInput.value='';

            this.showAlert('Image uploaded successfully','success');

        } catch(error) {
            debugLog('Error uploading image:',error,'error');
            this.showAlert('Failed to upload image: '+error.message,'error');
        } finally {
            uploadBtn.disabled=false;
            uploadBtn.textContent='Upload Image';
        }
    }

    async uploadContentImage(type,index) {
        const item=document.querySelector(`#${type}-list .content-item[data-index="${index}"]`);
        if(!item) return;

        const fileInput=item.querySelector('.content-image-file');
        const imageUrlInput=item.querySelector('.content-image-input');
        const uploadBtn=item.querySelector('.upload-content-image-btn');

        if(!fileInput.files.length) {
            this.showAlert('Please select an image file first','error');
            return;
        }

        const file=fileInput.files[0];

        // Validate file type
        if(!file.type.startsWith('image/')) {
            this.showAlert('Please select a valid image file','error');
            return;
        }

        // Validate file size (max 10MB)
        if(file.size>10*1024*1024) {
            this.showAlert('Image file size must be less than 10MB','error');
            return;
        }

        try {
            uploadBtn.disabled=true;
            uploadBtn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Uploading...';

            const formData=new FormData();
            formData.append('image',file);
            // Use different folders based on content type
            const folder=type==='testimonials'? 'testimonials':type;
            formData.append('folder',folder);

            const response=await fetch('?action=upload_image',{
                method: 'POST',
                body: formData
            });

            const result=await response.json();

            if(!result.success) {
                throw new Error(result.message||'Failed to upload image');
            }

            // Update the URL input with the uploaded image path
            imageUrlInput.value=result.data.url;

            // Update the preview
            const currentImageDiv=item.querySelector('.current-image');
            if(currentImageDiv) {
                currentImageDiv.innerHTML=`<img src="${result.data.url}" alt="Current image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">`;
            } else {
                const imageUploadSection=item.querySelector('.image-upload-section');
                imageUploadSection.insertAdjacentHTML('beforeend',`<div class="current-image" style="margin-top: 10px;"><img src="${result.data.url}" alt="Current image" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"></div>`);
            }

            // Clear the file input
            fileInput.value='';

            this.showAlert('Image uploaded successfully','success');

        } catch(error) {
            debugLog('Error uploading image:',error,'error');
            this.showAlert('Failed to upload image: '+error.message,'error');
        } finally {
            uploadBtn.disabled=false;
            uploadBtn.innerHTML='<i class="fas fa-upload"></i> Upload Image';
        }
    }

    async saveContentItem(type,index) {
        const item=document.querySelector(`#${type}-list .content-item[data-index="${index}"]`);
        if(!item) return;

        if(type==='testimonials') {
            // Handle testimonials with their specific structure
            const nameInput=item.querySelector('.content-name-input');
            const positionInput=item.querySelector('.content-position-input');
            const commentInput=item.querySelector('.content-comment-input');
            const imageInput=item.querySelector('.content-image-input');

            if(!nameInput.value.trim()||!commentInput.value.trim()) {
                this.showAlert('Client name and testimonial are required','error');
                return;
            }

            try {
                // Get current contents
                const response=await fetch(`?action=get_other_contents&type=${type}`);
                const result=await response.json();

                if(!result.success) {
                    throw new Error('Failed to load current contents');
                }

                const contents=result.data||[];
                contents[index]={
                    client_name: nameInput.value.trim(),
                    client_position: positionInput.value.trim(),
                    client_comment: commentInput.value.trim(),
                    client_img: imageInput.value.trim()
                };

                await this.saveContents(type,contents);
                item.classList.remove('editing');
                this.loadContentType(type);

            } catch(error) {
                debugLog('Error saving testimonial:',error,'error');
                this.showAlert('Failed to save testimonial','error');
            }
        } else if(type==='sliders') {
            // Handle sliders with their specific structure
            const nameInput=item.querySelector('.content-name-input');
            const titleInput=item.querySelector('.slide-title-input');
            const contentInput=item.querySelector('.slide-content-input');
            const imageInput=item.querySelector('.slide-image-input');
            const buttonTextInput=item.querySelector('.slide-button-text-input');
            const buttonUrlInput=item.querySelector('.slide-button-url-input');
            const buttonStyleInput=item.querySelector('.slide-button-style-input');
            const buttonTargetInput=item.querySelector('.slide-button-target-input');

            // No validation needed for slide name since it's auto-generated

            try {
                // Get current contents
                const response=await fetch(`?action=get_other_contents&type=${type}`);
                const result=await response.json();

                if(!result.success) {
                    throw new Error('Failed to load current contents');
                }

                const contents=result.data||[];
                const originalSlider=contents[index];

                const formData={
                    name: `slide-${index+1}`,
                    title: titleInput.value.trim(),
                    content: contentInput.value.trim(),
                    imageUrl: imageInput.value.trim(),
                    buttonText: buttonTextInput.value.trim()||'Learn More',
                    buttonUrl: buttonUrlInput.value.trim()||'#',
                    buttonStyle: buttonStyleInput.value||'primary',
                    buttonTarget: buttonTargetInput.value||'_self'
                };

                contents[index]=this.buildSliderContent(formData,originalSlider);

                await this.saveContents(type,contents);
                item.classList.remove('editing');
                this.loadContentType(type);

            } catch(error) {
                debugLog('Error saving slider:',error,'error');
                this.showAlert('Failed to save slider','error');
            }
        } else {
            // Handle standard content types
            const titleInput=item.querySelector('.content-title-input');
            const contentInput=item.querySelector('.content-text-input');
            const imageInput=item.querySelector('.content-image-input');

            if(!titleInput.value.trim()||!contentInput.value.trim()) {
                this.showAlert('Title and content are required','error');
                return;
            }

            try {
                // Get current contents
                const response=await fetch(`?action=get_other_contents&type=${type}`);
                const result=await response.json();

                if(!result.success) {
                    throw new Error('Failed to load current contents');
                }

                const contents=result.data||[];
                contents[index]={
                    title: titleInput.value.trim(),
                    content: contentInput.value.trim(),
                    image: imageInput.value.trim()
                };

                await this.saveContents(type,contents);
                item.classList.remove('editing');
                this.loadContentType(type);

            } catch(error) {
                debugLog('Error saving content:',error,'error');
                this.showAlert('Failed to save content','error');
            }
        }
    }

    async deleteContentItem(type,index) {
        if(!confirm(`Are you sure you want to delete this ${type.slice(0,-1)}?`)) {
            return;
        }

        try {
            // Get current contents
            const response=await fetch(`?action=get_other_contents&type=${type}`);
            const result=await response.json();

            if(!result.success) {
                throw new Error('Failed to load current contents');
            }

            const contents=result.data||[];
            contents.splice(index,1);

            await this.saveContents(type,contents);
            this.loadContentType(type);
            this.showAlert(`${type.slice(0,-1)} deleted successfully`,'success');

        } catch(error) {
            debugLog('Error deleting content:',error,'error');
            this.showAlert('Failed to delete content','error');
        }
    }

    async addNewContent(type) {
        let newItem;

        if(type==='testimonials') {
            newItem={
                client_name: '',
                client_position: '',
                client_comment: '',
                client_img: ''
            };
        } else if(type==='sliders') {
            // Create a basic slider structure
            const slideNumber=await this.getNextSlideNumber();
            newItem={
                name: `slide-${slideNumber}`,
                widgets: [{
                    title: "",
                    text: `<h1>New Slide Title</h1>\n<p>Add your slide content here.</p>\n<p>[button text="Learn More" url="#" style="primary" target="_self"]</p>`,
                    text_selected_editor: "tinymce",
                    autop: true,
                    panels_info: {
                        class: "SiteOrigin_Widget_Editor_Widget",
                        raw: false,
                        grid: 0,
                        cell: 0,
                        id: 0,
                        widget_id: this.generateUUID(),
                        style: {
                            background_image_attachment: false,
                            background_display: "tile",
                            background_image_size: "full",
                            background_image_opacity: "100",
                            border_thickness: "1px"
                        }
                    }
                }],
                grids: [{
                    cells: 2,
                    style: {
                        background_image_attachment: false,
                        background_display: "tile",
                        background_image_size: "full",
                        background_image_opacity: "100",
                        border_thickness: "1px",
                        full_height: "",
                        bottom_margin: "0px",
                        gutter: "0px",
                        cell_alignment: "flex-start"
                    }
                }],
                grid_cells: [
                    {grid: 0,index: 0,weight: 0.5,style: []},
                    {grid: 0,index: 1,weight: 0.5,style: []}
                ],
                id: `slide-${slideNumber}`
            };
        } else {
            newItem={
                title: '',
                content: '',
                image: ''
            };
        }

        try {
            // Get current contents
            const response=await fetch(`?action=get_other_contents&type=${type}`);
            const result=await response.json();

            if(!result.success) {
                throw new Error('Failed to load current contents');
            }

            const contents=result.data||[];
            contents.push(newItem);

            this.renderContentList(type,contents);

            // Automatically edit the new item
            const newIndex=contents.length-1;
            setTimeout(() => this.editContentItem(type,newIndex),100);

        } catch(error) {
            debugLog('Error adding content:',error,'error');
            this.showAlert('Failed to add new content','error');
        }
    }

    async saveContents(type,contents) {
        try {
            const response=await fetch('?action=save_other_contents',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    contents: contents
                })
            });

            const result=await response.json();

            if(!result.success) {
                throw new Error(result.message||'Failed to save contents');
            }

            return result;
        } catch(error) {
            debugLog('Error saving contents:',error,'error');
            throw error;
        }
    } async saveAllContents() {
        try {
            // Save any currently editing items first
            const editingItems=document.querySelectorAll('.content-item.editing');

            for(const item of editingItems) {
                const type=item.dataset.type;
                const index=parseInt(item.dataset.index);
                await this.saveContentItem(type,index);
            }

            this.showAlert('All contents saved successfully','success');
        } catch(error) {
            debugLog('Error saving all contents:',error,'error');
            this.showAlert('Failed to save all contents','error');
        }
    }

    switchContentTab(tabName) {
        debugLog('switchContentTab called with:',tabName);

        // Prevent rapid successive calls (debounce)
        if(this.switchingTab) {
            debugLog('switchContentTab blocked - already in progress');
            return;
        }

        this.switchingTab=true;

        // Update tab active states
        const allTabLinks=document.querySelectorAll('#contents-tabs .tab-link');
        allTabLinks.forEach(link => {
            link.classList.remove('active');
        });

        const targetTab=document.querySelector(`#contents-tabs .tab-link[data-content-tab="${tabName}"]`);
        if(targetTab) {
            targetTab.classList.add('active');
        }

        // Update panel active states
        const allPanels=document.querySelectorAll('.content-tab-panel');
        allPanels.forEach(panel => {
            panel.classList.remove('active');
        });

        const targetPanel=document.getElementById(`${tabName}-tab`);
        if(targetPanel) {
            targetPanel.classList.add('active');
        }

        // Load content for the active tab
        this.loadContentType(tabName);

        // Reset debounce after a short delay
        setTimeout(() => {
            this.switchingTab=false;
        },100);
    }

    escapeHtml(text) {
        const div=document.createElement('div');
        div.textContent=text;
        return div.innerHTML;
    }

    extractTextFromSlider(sliderData) {
        try {
            if(sliderData.widgets&&sliderData.widgets.length>0) {
                const widget=sliderData.widgets[0];
                if(widget.text) {
                    // Extract text content and remove HTML tags
                    const tempDiv=document.createElement('div');
                    tempDiv.innerHTML=widget.text;
                    return tempDiv.textContent||tempDiv.innerText||'';
                }
            }
            return 'Slider content';
        } catch(error) {
            return 'Slider content';
        }
    }

    async getNextSlideNumber() {
        try {
            const response=await fetch(`?action=get_other_contents&type=sliders`);
            const result=await response.json();
            if(result.success&&result.data) {
                const existingSlides=result.data.length;
                return existingSlides+1;
            }
            return 1;
        } catch(error) {
            return 1;
        }
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c) {
            const r=Math.random()*16|0;
            const v=c=='x'? r:(r&0x3|0x8);
            return v.toString(16);
        });
    }

    parseSliderContent(sliderData) {
        try {
            const widget=sliderData.widgets&&sliderData.widgets[0];
            if(!widget||!widget.text) {
                return {
                    title: '',
                    content: '',
                    buttonText: 'Learn More',
                    buttonUrl: '#',
                    buttonStyle: 'primary',
                    buttonTarget: '_self'
                };
            }

            const html=widget.text;

            // Extract title from h1 tag
            const titleMatch=html.match(/<h1[^>]*>(.*?)<\/h1>/i);
            const title=titleMatch? titleMatch[1].trim():'';

            // Extract content from p tags (excluding button)
            const contentMatches=html.match(/<p[^>]*>((?:(?!<p|<\/p)[\s\S])*?)<\/p>/gi);
            let content='';
            if(contentMatches) {
                // Get paragraphs that don't contain button shortcode
                const textParagraphs=contentMatches.filter(p => !p.includes('[button'));
                content=textParagraphs.map(p => p.replace(/<\/?p[^>]*>/gi,'').trim()).join('\n');
            }

            // Extract button shortcode
            const buttonMatch=html.match(/\[button\s+([^\]]*)\]/i);
            let buttonText='Learn More',buttonUrl='#',buttonStyle='primary',buttonTarget='_self';

            if(buttonMatch) {
                const attrs=buttonMatch[1];
                const textMatch=attrs.match(/text="([^"]*)"/);
                const urlMatch=attrs.match(/url="([^"]*)"/);
                const styleMatch=attrs.match(/style="([^"]*)"/);
                const targetMatch=attrs.match(/target="([^"]*)"/);

                if(textMatch) buttonText=textMatch[1];
                if(urlMatch) buttonUrl=urlMatch[1];
                if(styleMatch) buttonStyle=styleMatch[1];
                if(targetMatch) buttonTarget=targetMatch[1];
            }

            return {
                title: title,
                content: content,
                buttonText: buttonText,
                buttonUrl: buttonUrl,
                buttonStyle: buttonStyle,
                buttonTarget: buttonTarget
            };
        } catch(error) {
            debugLog('Error parsing slider content:',error,'error');
            return {
                title: '',
                content: '',
                buttonText: 'Learn More',
                buttonUrl: '#',
                buttonStyle: 'primary',
                buttonTarget: '_self'
            };
        }
    }

    buildSliderContent(formData,originalSlider) {
        try {
            // Build the HTML content
            const html=`<h1>${formData.title}</h1>\n<p>${formData.content.replace(/\n/g,'</p>\n<p>')}</p>\n<p>[button text="${formData.buttonText}" url="${formData.buttonUrl}" style="${formData.buttonStyle}" target="${formData.buttonTarget}"]</p>`;

            // Handle case where originalSlider is undefined (new slider)
            const baseSlider=originalSlider||{};

            // Create the updated slider structure
            const updatedSlider={
                ...baseSlider,
                name: formData.name,
                title: formData.title,
                image_url: formData.imageUrl,
                widgets: [{
                    title: "",
                    text: html,
                    text_selected_editor: "tinymce",
                    autop: true,
                    _sow_form_id: originalSlider?.widgets?.[0]?._sow_form_id||"33239199768e402fb33e3d568582298",
                    _sow_form_timestamp: originalSlider?.widgets?.[0]?._sow_form_timestamp||Date.now().toString(),
                    so_sidebar_emulator_id: originalSlider?.widgets?.[0]?.so_sidebar_emulator_id||"sow-editor-3510000",
                    option_name: "widget_sow-editor",
                    panels_info: originalSlider?.widgets?.[0]?.panels_info||{
                        class: "SiteOrigin_Widget_Editor_Widget",
                        raw: false,
                        grid: 0,
                        cell: 0,
                        id: 0,
                        widget_id: this.generateUUID(),
                        style: {
                            background_image_attachment: false,
                            background_display: "tile",
                            background_image_size: "full",
                            background_image_opacity: "100",
                            border_thickness: "1px"
                        }
                    }
                }],
                grids: originalSlider?.grids||[
                    {
                        cells: 2,
                        style: {
                            background_image_attachment: false,
                            background_display: "tile",
                            background_image_size: "full",
                            background_image_opacity: "100",
                            border_thickness: "1px",
                            full_height: "",
                            bottom_margin: "0px",
                            gutter: "0px",
                            cell_alignment: "flex-start"
                        }
                    }
                ],
                grid_cells: originalSlider?.grid_cells||[
                    {
                        grid: 0,
                        index: 0,
                        weight: 0.5,
                        style: []
                    },
                    {
                        grid: 0,
                        index: 1,
                        weight: 0.5,
                        style: []
                    }
                ],
                id: formData.name
            };

            return updatedSlider;
        } catch(error) {
            debugLog('Error building slider content:',error,'error');
            return originalSlider||{
                name: formData.name,
                widgets: [],
                grids: [],
                grid_cells: []
            };
        }
    }

    /**
     * Deploy New Site - Reset deployment state and reload page to show form
     */
    deployNewSite() {
        debugLog('Deploy New Site clicked - resetting deployment state...');

        // Set flag to prevent any new modals during transition - IMMEDIATE PROTECTION
        this.systemRecentlyReset=true;

        // Close any open modals first
        this.closeModals();

        // Reset GitHub Actions completion state for fresh deployment
        this.githubActionsCompleted=false; // Allow fresh completion detection
        debugLog('🔄 GitHub Actions completion flag reset to allow fresh deployment');

        // Clear all polling intervals aggressively
        this.stopAllPolling();

        // Reset deployment state (this will also set systemRecentlyReset again)
        this.resetDeploymentState();

        // Clear backend GitHub run ID and deployment status before reload
        this.clearBackendStateSync();

        // Switch to deployment tab
        this.switchTab('deployment');

        // Reload immediately without showing any alert to minimize modal appearance window
        window.location.reload();
    }

    /**
     * Clear backend deployment status to allow fresh deployments (synchronous)
     */
    clearBackendStateSync() {
        try {
            debugLog('🧹 Clearing backend GitHub run ID and deployment state...');

            // Clear GitHub run ID
            const xhr1=new XMLHttpRequest();
            xhr1.open('POST','?action=clear_github_run_id',false); // false = synchronous
            xhr1.send();

            if(xhr1.status===200) {
                const data1=JSON.parse(xhr1.responseText);
                if(data1.success) {
                    debugLog('✅ Backend GitHub run ID cleared successfully');
                } else {
                    debugLog('⚠️ Failed to clear GitHub run ID:',data1.message,'warn');
                }
            }

            // Clear deployment status file
            const xhr2=new XMLHttpRequest();
            xhr2.open('POST','?action=clear_deployment_status',false); // false = synchronous
            xhr2.send();

            if(xhr2.status===200) {
                const data2=JSON.parse(xhr2.responseText);
                if(data2.success) {
                    debugLog('✅ Backend deployment status cleared successfully');
                } else {
                    debugLog('⚠️ Failed to clear deployment status:',data2.message,'warn');
                }
            }

            // Also reset the entire system for good measure
            const xhr3=new XMLHttpRequest();
            xhr3.open('POST','?action=reset_system',false); // false = synchronous
            xhr3.send();

            if(xhr3.status===200) {
                const data3=JSON.parse(xhr3.responseText);
                if(data3.success) {
                    debugLog('✅ Backend system reset successfully');
                } else {
                    debugLog('⚠️ Failed to reset system:',data3.message,'warn');
                }
            }

        } catch(error) {
            debugLog('❌ Error clearing backend state:',error,'error');
        }
    }

    /**
     * Deploy Again - Run only the deploy step (deploy.sh) with existing credentials
     */
    deployAgain() {
        debugLog('Deploy Again clicked - running deploy step only...');

        // Set flag to prevent modal interference during deploy again
        this.systemRecentlyReset=true;
        setTimeout(() => {
            this.systemRecentlyReset=false;
        },3000); // Clear flag after 3 seconds

        // Close any open modals first
        this.closeModals();

        // Switch to deployment tab to show progress
        this.switchTab('deployment');

        // Show alert that we're starting deploy again
        this.showAlert('Starting deployment with existing credentials...','info');

        // Call the backend to run only the deploy step
        this.runDeployAgain();
    }

    /**
     * Open Site URL - Get actual URL from Kinsta API
     */
    async openSiteUrl(fallbackUrl=null) {
        try {
            // Show loading state
            this.showAlert('Getting site URL...','info',null,false,3000);

            const response=await fetch('?action=get_site_info');
            const result=await response.json();

            if(result.success&&result.data.site_url) {
                // Open the actual site URL from Kinsta API
                window.open(result.data.site_url,'_blank');
                return;
            }

            // Fallback to provided URL (from GitHub data)
            if(fallbackUrl&&fallbackUrl!=='#') {
                debugLog('Using fallback URL:',fallbackUrl);
                window.open(fallbackUrl,'_blank');
                return;
            }

            // Last resort - show error
            const errorMessage=result.message||'Site URL not available';
            this.showAlert(`Unable to get site URL: ${errorMessage}`,'warning');
            debugLog('Site URL fetch failed:',result,'warn');

        } catch(error) {
            debugLog('Failed to get site URL:',error,'error');

            // Try fallback URL on error
            if(fallbackUrl&&fallbackUrl!=='#') {
                debugLog('API failed, using fallback URL:',fallbackUrl);
                window.open(fallbackUrl,'_blank');
            } else {
                this.showAlert('Failed to get site URL. Please check if the site is deployed.','error');
            }
        }
    }
}

// Global functions for content management
function addNewIssue() {
    if(window.adminInterface) {
        window.adminInterface.addNewContent('issues');
    }
}

function addNewEndorsement() {
    if(window.adminInterface) {
        window.adminInterface.addNewContent('endorsements');
    }
}

function addNewNews() {
    if(window.adminInterface) {
        window.adminInterface.addNewContent('news');
    }
}

function addNewPost() {
    if(window.adminInterface) {
        window.adminInterface.addNewContent('posts');
    }
}

function addNewTestimonial() {
    if(window.adminInterface) {
        window.adminInterface.addNewContent('testimonials');
    }
}

function addNewSlider() {
    if(window.adminInterface) {
        window.adminInterface.addNewContent('sliders');
    }
}

function switchContentTab(tabName) {
    if(window.adminInterface) {
        window.adminInterface.switchContentTab(tabName);
    }
}

function saveAllContents() {
    if(window.adminInterface) {
        window.adminInterface.saveAllContents();
    }
}

// Dynamic List Functions
function addCountryToList() {
    const select=document.getElementById('countries-select');
    const itemsContainer=document.getElementById('countries-items');
    const selectedValue=select.value;
    const selectedText=select.options[select.selectedIndex].text;

    if(!selectedValue) return;

    // Check if already exists
    const existing=itemsContainer.querySelector(`[data-value="${selectedValue}"]`);
    if(existing) {
        select.value='';
        return;
    }

    // Remove empty state
    const emptyState=itemsContainer.querySelector('.dynamic-list-empty');
    if(emptyState) emptyState.remove();

    // Create new item
    const item=document.createElement('div');
    item.className='dynamic-list-item';
    item.setAttribute('data-value',selectedValue);
    item.innerHTML=`
        <span class="dynamic-list-item-text">${selectedText}</span>
        <button type="button" class="dynamic-list-remove-btn" onclick="removeFromList(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

    itemsContainer.appendChild(item);
    select.value='';

    // Update hidden input for config
    updateDynamicListConfig('countries-list');
}

function addIPToList() {
    const input=document.getElementById('ip-input');
    const itemsContainer=document.getElementById('ip-items');
    const ipValue=input.value.trim();

    if(!ipValue) return;

    // Basic IP validation
    const ipPattern=/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:\/(?:3[0-2]|[12]?[0-9]))?$/;
    if(!ipPattern.test(ipValue)) {
        alert('Please enter a valid IP address or CIDR block (e.g., 192.168.1.1 or 10.0.0.0/24)');
        return;
    }

    // Check if already exists
    const existing=itemsContainer.querySelector(`[data-value="${ipValue}"]`);
    if(existing) {
        input.value='';
        return;
    }

    // Remove empty state
    const emptyState=itemsContainer.querySelector('.dynamic-list-empty');
    if(emptyState) emptyState.remove();

    // Create new item
    const item=document.createElement('div');
    item.className='dynamic-list-item';
    item.setAttribute('data-value',ipValue);
    item.innerHTML=`
        <span class="dynamic-list-item-text">${ipValue}</span>
        <button type="button" class="dynamic-list-remove-btn" onclick="removeFromList(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

    itemsContainer.appendChild(item);
    input.value='';

    // Update hidden input for config
    updateDynamicListConfig('ip-list');
}

function removeFromList(button) {
    const item=button.closest('.dynamic-list-item');
    const container=item.closest('.dynamic-list');
    const itemsContainer=container.querySelector('.dynamic-list-items');

    item.remove();

    // Add empty state if no items left
    if(itemsContainer.children.length===0) {
        const listId=container.id;
        let emptyText='No items added.';

        if(listId==='countries-list') {
            emptyText='No countries selected. Add countries using the dropdown above.';
        } else if(listId==='ip-list') {
            emptyText='No IP addresses added. Enter an IP address above and click Add.';
        }

        const emptyDiv=document.createElement('div');
        emptyDiv.className='dynamic-list-empty';
        emptyDiv.textContent=emptyText;
        itemsContainer.appendChild(emptyDiv);
    }

    // Update hidden input for config
    updateDynamicListConfig(container.id);
}

function updateDynamicListConfig(listId) {
    const container=document.getElementById(listId);
    const dataPath=container.getAttribute('data-path');
    const items=container.querySelectorAll('.dynamic-list-item');

    const values=Array.from(items).map(item => item.getAttribute('data-value'));

    // Create or update hidden input for form submission
    let hiddenInput=document.getElementById(`${listId}-hidden`);
    if(!hiddenInput) {
        hiddenInput=document.createElement('input');
        hiddenInput.type='hidden';
        hiddenInput.id=`${listId}-hidden`;
        hiddenInput.className='config-input';
        hiddenInput.setAttribute('data-path',dataPath);
        container.appendChild(hiddenInput);
    }

    hiddenInput.value=JSON.stringify(values);
}

function loadDynamicListFromConfig(listId,values) {
    const container=document.getElementById(listId);
    const itemsContainer=container.querySelector('.dynamic-list-items');

    if(!values||values.length===0) return;

    // Remove empty state
    const emptyState=itemsContainer.querySelector('.dynamic-list-empty');
    if(emptyState) emptyState.remove();

    values.forEach(value => {
        let text=value;

        // For countries, get the full name
        if(listId==='countries-list') {
            const select=document.getElementById('countries-select');
            const option=select.querySelector(`option[value="${value}"]`);
            if(option) text=option.text;
        }

        const item=document.createElement('div');
        item.className='dynamic-list-item';
        item.setAttribute('data-value',value);
        item.innerHTML=`
            <span class="dynamic-list-item-text">${text}</span>
            <button type="button" class="dynamic-list-remove-btn" onclick="removeFromList(this)">
                <i class="fas fa-times"></i>
            </button>
        `;

        itemsContainer.appendChild(item);
    });

    updateDynamicListConfig(listId);
}

// Modern Toggle Switch Functions
function toggleSwitch(checkboxId) {
    const checkbox=document.getElementById(checkboxId);
    if(!checkbox) return;

    // Toggle checkbox state
    checkbox.checked=!checkbox.checked;

    // Trigger both input and change events (bubbling) so document-level listeners pick up changes
    checkbox.dispatchEvent(new Event('input',{bubbles: true}));
    checkbox.dispatchEvent(new Event('change',{bubbles: true}));

    // Add visual feedback
    const toggleSwitch=checkbox.nextElementSibling;
    if(toggleSwitch) {
        toggleSwitch.style.transform='scale(0.95)';
        setTimeout(() => {
            toggleSwitch.style.transform='';
        },150);
    }
}

// Initialize toggle switches on page load
function initializeToggleSwitches() {
    // Set up toggle click handlers
    document.querySelectorAll('.toggle-switch').forEach(toggle => {
        toggle.addEventListener('click',(e) => {
            const checkbox=toggle.previousElementSibling;
            if(checkbox&&checkbox.type==='checkbox') {
                toggleSwitch(checkbox.id);
            }
        });
    });

    // Set up label click handlers
    // Prevent the native label toggle (which would double-toggle when combined with our handler)
    document.querySelectorAll('.toggle-label').forEach(label => {
        label.addEventListener('click',(e) => {
            e.preventDefault(); // avoid native label toggling so we only toggle once via toggleSwitch
            const checkboxId=label.getAttribute('for');
            if(checkboxId) {
                toggleSwitch(checkboxId);
            }
        });
    });
}

// Handle master toggle -> subsection visibility
// Modern Dynamic List Functions
function generateRandomLoginSlug() {
    // Generate completely random URL-safe string (lowercase letters, numbers, hyphens only)
    const chars='abcdefghijklmnopqrstuvwxyz0123456789';
    const length=12;
    let slug='';

    // Use crypto.getRandomValues for cryptographically secure randomness
    const randomValues=new Uint8Array(length);
    crypto.getRandomValues(randomValues);

    for(let i=0;i<length;i++) {
        slug+=chars[randomValues[i]%chars.length];
    }

    // Format as: xxxx-xxxx-xxxx for better readability (e.g., a7k2-m9p3-q8n4)
    const formatted=`${slug.slice(0,4)}-${slug.slice(4,8)}-${slug.slice(8,12)}`;

    const input=document.getElementById('custom-login-slug-input');
    if(input) {
        input.value=formatted;
        // Trigger change event to mark as modified
        input.dispatchEvent(new Event('input',{bubbles: true}));
        input.dispatchEvent(new Event('change',{bubbles: true}));

        // Visual feedback
        input.style.transition='background-color 0.3s ease';
        input.style.backgroundColor='#d1fae5';
        setTimeout(() => {
            input.style.backgroundColor='';
        },1000);
    }
}

function generateRandomAccessCode() {
    // Generate truly random, URL-safe, secure access code
    const chars='abcdefghijklmnopqrstuvwxyz0123456789';
    const length=16; // Long enough for security
    let code='';

    // Use crypto.getRandomValues for cryptographically secure randomness
    const randomValues=new Uint8Array(length);
    crypto.getRandomValues(randomValues);

    for(let i=0;i<length;i++) {
        code+=chars[randomValues[i]%chars.length];
    }

    // Format as: xxx-xxxx-xxxx-xxx for readability
    const formatted=`${code.slice(0,3)}-${code.slice(3,7)}-${code.slice(7,11)}-${code.slice(11,16)}`;

    const input=document.getElementById('emergency-access-code-input');
    if(input) {
        input.value=formatted;
        // Trigger change event to mark as modified
        input.dispatchEvent(new Event('input',{bubbles: true}));
        input.dispatchEvent(new Event('change',{bubbles: true}));

        // Visual feedback
        input.style.transition='background-color 0.3s ease';
        input.style.backgroundColor='#d1fae5';
        setTimeout(() => {
            input.style.backgroundColor='';
        },1000);
    }
}

function toggleCountryInput() {
    const inputGroup=document.getElementById('country-input-group');
    const isVisible=inputGroup.style.display!=='none';

    inputGroup.style.display=isVisible? 'none':'flex';

    if(!isVisible) {
        const select=document.getElementById('countries-select');
        select.focus();
    }
}

function toggleIPInput() {
    const inputGroup=document.getElementById('ip-input-group');
    const isVisible=inputGroup.style.display!=='none';

    inputGroup.style.display=isVisible? 'none':'flex';

    if(!isVisible) {
        const input=document.getElementById('ip-input');
        input.focus();
    }
}

function addCountryToList() {
    const select=document.getElementById('countries-select');
    const countryCode=select.value;
    const countryText=select.options[select.selectedIndex].text;

    if(!countryCode) {
        showNotification('Please select a country first','warning');
        return;
    }

    // Check if already added
    const existingItems=document.querySelectorAll('#countries-items .dynamic-list-item');
    for(let item of existingItems) {
        if(item.dataset.value===countryCode) {
            showNotification('Country already added','warning');
            return;
        }
    }

    // Create new item
    const itemsContainer=document.getElementById('countries-items');
    const emptyState=itemsContainer.querySelector('.dynamic-list-empty');
    if(emptyState) {
        emptyState.remove();
    }

    const item=document.createElement('div');
    item.className='dynamic-list-item';
    item.dataset.value=countryCode;

    const flag=countryText.includes('🇺🇸')? countryText.split(' ')[0]:'🌍';
    const name=countryText.includes('🇺🇸')? countryText.substring(2):countryText;

    item.innerHTML=`
        <div class="dynamic-list-item-content">
            <span class="dynamic-list-item-flag">${flag}</span>
            <span class="dynamic-list-item-text">${name}</span>
            <span class="dynamic-list-item-code">${countryCode}</span>
        </div>
        <div class="dynamic-list-item-actions">
            <button type="button" class="dynamic-list-remove" onclick="removeCountryFromList('${countryCode}')">
                <i class="fas fa-times dynamic-list-remove-icon"></i>
            </button>
        </div>
    `;

    itemsContainer.appendChild(item);

    // Reset select and hide input group
    select.value='';
    document.getElementById('country-input-group').style.display='none';

    // Update configuration
    updateDynamicListConfig('countries-list');

    showNotification(`${name} added successfully`,'success');
}

function removeCountryFromList(countryCode) {
    const item=document.querySelector(`#countries-items .dynamic-list-item[data-value="${countryCode}"]`);
    if(item) {
        item.style.animation='fadeOut 0.3s ease-out';
        setTimeout(() => {
            item.remove();

            // Show empty state if no items left
            const itemsContainer=document.getElementById('countries-items');
            if(!itemsContainer.querySelector('.dynamic-list-item')) {
                itemsContainer.innerHTML=`
                    <div class="dynamic-list-empty">
                        <div class="dynamic-list-empty-icon">🌍</div>
                        <div>No countries selected. Click "Add Country" to begin.</div>
                    </div>
                `;
            }

            updateDynamicListConfig('countries-list');
        },300);
    }
}

function addIPToList() {
    const input=document.getElementById('ip-input');
    const ipValue=input.value.trim();

    if(!ipValue) {
        showNotification('Please enter an IP address','warning');
        return;
    }

    // Basic IP validation
    const ipRegex=/^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$|^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/;
    if(!ipRegex.test(ipValue)) {
        showNotification('Please enter a valid IP address','error');
        return;
    }

    // Check if already added
    const existingItems=document.querySelectorAll('#ip-items .dynamic-list-item');
    for(let item of existingItems) {
        if(item.dataset.value===ipValue) {
            showNotification('IP address already added','warning');
            return;
        }
    }

    // Create new item
    const itemsContainer=document.getElementById('ip-items');
    const emptyState=itemsContainer.querySelector('.dynamic-list-empty');
    if(emptyState) {
        emptyState.remove();
    }

    const item=document.createElement('div');
    item.className='dynamic-list-item';
    item.dataset.value=ipValue;

    const isRange=ipValue.includes('/');
    const ipType=isRange? 'Network Range':'Single IP';

    item.innerHTML=`
        <div class="dynamic-list-item-content">
            <i class="fas ${isRange? 'fa-network-wired':'fa-desktop'}" style="color: var(--brand-blue); margin-right: 8px;"></i>
            <span class="dynamic-list-item-text">${ipValue}</span>
            <span class="dynamic-list-item-code">${ipType}</span>
        </div>
        <div class="dynamic-list-item-actions">
            <button type="button" class="dynamic-list-remove" onclick="removeIPFromList('${ipValue}')">
                <i class="fas fa-times dynamic-list-remove-icon"></i>
            </button>
        </div>
    `;

    itemsContainer.appendChild(item);

    // Reset input and hide input group
    input.value='';
    document.getElementById('ip-input-group').style.display='none';

    // Update configuration
    updateDynamicListConfig('ip-list');

    showNotification(`IP ${ipValue} added successfully`,'success');
}

function removeIPFromList(ipValue) {
    const item=document.querySelector(`#ip-items .dynamic-list-item[data-value="${ipValue}"]`);
    if(item) {
        item.style.animation='fadeOut 0.3s ease-out';
        setTimeout(() => {
            item.remove();

            // Show empty state if no items left
            const itemsContainer=document.getElementById('ip-items');
            if(!itemsContainer.querySelector('.dynamic-list-item')) {
                itemsContainer.innerHTML=`
                    <div class="dynamic-list-empty">
                        <div class="dynamic-list-empty-icon">🛡️</div>
                        <div>No IP addresses added. Click "Add IP" to begin.</div>
                    </div>
                `;
            }

            updateDynamicListConfig('ip-list');
        },300);
    }
}

// Notification system
function showNotification(message,type='info') {
    const notification=document.createElement('div');
    notification.className=`alert alert-${type} animate-fadeIn`;
    notification.style.cssText=`
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        min-width: 300px;
        max-width: 400px;
    `;
    notification.textContent=message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation='fadeOut 0.3s ease-out';
        setTimeout(() => notification.remove(),300);
    },3000);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded',() => {
    // Make adminInterface globally accessible for testing
    const adminInterface=new AdminInterface();
    window.adminInterface=adminInterface;

    // Make adminInterface globally available for callbacks
    window.adminInterface=adminInterface;

    // Expose test functions for debugging
    window.testPopup=() => adminInterface.testPopup();

    window.testGitHubCompletion=(status) => adminInterface.testGitHubCompletion(status);

    // Debug function to check GitHub run ID issues
    window.forceCheckRunId=() => adminInterface.forceCheckRunId();

    // Debug function to clear old run ID and force fresh check
    window.forceClearOldRunId=() => adminInterface.forceClearOldRunId();

    // Debug function to manually reset deployment display
    window.resetDeploymentDisplay=() => {
        debugLog('🔄 Manually resetting deployment display...');
        const mockIdleStatus={
            status: 'idle',
            current_step: '',
            message: 'Ready for deployment'
        };
        adminInterface.updateCompactViewStatus(mockIdleStatus);
        adminInterface.setDeploymentInProgress(false);
        debugLog('✅ Deployment display reset to idle state');
    };

    // Debug function to refresh deployment status from server
    window.refreshDeploymentStatus=async () => {
        debugLog('🔄 Refreshing deployment status from server...');
        try {
            const response=await fetch('?action=deployment_status');
            const data=await response.json();

            if(data.success) {
                debugLog('📡 Current deployment status:',data.data);
                adminInterface.updateDeploymentStatusDisplay(data.data);
                debugLog('✅ Deployment status refreshed');
            } else {
                debugLog('❌ Failed to fetch deployment status:',data.message,'error');
            }
        } catch(error) {
            debugLog('❌ Error fetching deployment status:',error,'error');
        }
    };

    // Debug function to manually mark deployment as failed at specific step
    window.markDeploymentFailed=(step='get-cred') => {
        debugLog(`🚫 Manually marking deployment as failed at step: ${step}`);
        const mockFailedStatus={
            status: 'failed',
            current_step: step,
            message: `Deployment failed at ${step} step`,
            timestamp: Math.floor(Date.now()/1000)
        };
        adminInterface.updateCompactViewStatus(mockFailedStatus);
        adminInterface.updateDeploymentStatusDisplay(mockFailedStatus);
        debugLog('✅ Deployment marked as failed');
    };

    // Debug function to reset Google Maps
    window.resetGoogleMaps=() => {
        debugLog('🗺️ Resetting Google Maps...');

        // Remove existing scripts
        const existingScripts=document.querySelectorAll('script[src*="maps.googleapis.com"]');
        existingScripts.forEach(script => {
            debugLog('Removing script:',script.src);
            script.remove();
        });

        // Clear Google Maps objects
        if(window.google) {
            try {
                delete window.google;
                debugLog('Cleared window.google');
            } catch(e) {
                window.google=undefined;
                debugLog('Set window.google to undefined');
            }
        }

        // Clear loading flags
        window.googleMapsApiLoading=false;
        window.googleMapsAuthFailed=false;

        // Clear auth failure handler
        if(window.gm_authFailure) {
            delete window.gm_authFailure;
        }

        // Clear map containers
        const mapPreview=document.getElementById('map-preview');
        if(mapPreview) {
            mapPreview.innerHTML='<div class="text-muted d-flex align-items-center justify-content-center h-100"><i class="fas fa-map-marked-alt fa-2x mb-2"></i><br>Google Maps reset - Enter API key to reload</div>';
        }

        debugLog('✅ Google Maps reset complete');
    };

    // Debug function to check Google Maps status
    window.checkGoogleMapsStatus=() => {
        debugLog('=== Google Maps Status ===');
        debugLog('window.google:',window.google);
        debugLog('window.google.maps:',window.google?.maps);
        debugLog('googleMapsApiLoading:',window.googleMapsApiLoading);
        debugLog('gm_authFailure:',typeof window.gm_authFailure);
        debugLog('API scripts count:',document.querySelectorAll('script[src*="maps.googleapis.com"]').length);

        const apiKey=document.querySelector('[data-path="authentication.api_keys.google_maps"]')?.value;
        debugLog('API Key present:',apiKey? 'Yes':'No');
        debugLog('API Key length:',apiKey?.length||0);

        const mapContainer=document.getElementById('interactive-map');
        debugLog('Map container exists:',mapContainer? 'Yes':'No');
    };

    // Add UI enhancements after initialization
    setTimeout(() => {
        adminInterface.enhanceUserExperience();
        initializeToggleSwitches();
    },100);

    // Global debug toggle function
    window.toggleDebug=() => adminInterface.toggleDebug();

    // Add debug functions for new features
    window.testDeployNewSite=() => adminInterface.deployNewSite();
    window.testDeployAgain=() => adminInterface.deployAgain();
    window.testOpenSiteUrl=() => adminInterface.openSiteUrl();

    // Debug functions for GitHub Actions
    window.testGitHubStatus=() => adminInterface.pollGitHubActionsStatus();

    window.forceCloseModal=() => {
        debugLog('🗂️ Force closing completion modal...');
        const modal=document.getElementById('deployment-completion-modal');
        if(modal) {
            modal.remove();
            debugLog('✅ Modal removed');
        }
        // Clear localStorage flags
        const keys=Object.keys(localStorage).filter(key => key.startsWith('github_actions_final_'));
        keys.forEach(key => localStorage.removeItem(key));
        debugLog('✅ Cleared completion flags');
        adminInterface.showAlert('Modal force closed and flags cleared','success');
    };
    window.forceCompleteDeployment=() => {
        debugLog('🎯 Force completing deployment...');
        adminInterface.githubActionsCompleted=true;
        adminInterface.setDeploymentInProgress(false);
        const modal=document.getElementById('deployment-completion-modal');
        if(modal) modal.remove();
        adminInterface.showAlert('Deployment marked as completed','success');
    };

    window.forceShowDeploymentForm=() => {
        debugLog('🔧 Force showing deployment form and hiding progress sections...');
        adminInterface.setDeploymentInProgress(false);
        adminInterface.showAlert('✅ Fixed UI state - form visible, progress hidden','success');
    };

    debugLog('Debug mode controls: Call toggleDebug() or adminInterface.toggleDebug() to toggle logging');
    debugLog('Test functions: testDeployNewSite(), testDeployAgain(), testOpenSiteUrl()');
    debugLog('GitHub debug: testGitHubStatus()');
    debugLog('Modal debug: forceCloseModal(), forceCompleteDeployment()');
});
