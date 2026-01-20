// Forms Management JS for Forminator JSON Editor
// Handles create, edit, delete, and UI for forms with proper Forminator JSON structure

document.addEventListener('DOMContentLoaded',function() {
    const formsListEl=document.getElementById('forms-list');
    const formEditorEl=document.getElementById('form-editor');
    const addFormBtn=document.getElementById('add-form-btn');
    const formSaveBtn=document.getElementById('form-save-btn');
    const formDeleteBtn=document.getElementById('form-delete-btn');
    const formElementsListEl=document.getElementById('form-elements-list');
    const addElementBtn=document.getElementById('add-element-btn');
    const formNameInput=document.getElementById('form-name');
    const elementTypeInput=document.getElementById('element-type');
    const elementLabelInput=document.getElementById('element-label');
    const formSuccessEl=document.getElementById('form-success');
    const formErrorEl=document.getElementById('form-error');

    let forms=[];
    let editingFormIndex=null;
    let editingForm=null;
    let loadingOverlay=null;
    let formPlaceholders=[]; // Current form's placeholders

    // Loading state functions
    function showLoading(message='Loading...') {
        if(!loadingOverlay) {
            loadingOverlay=document.createElement('div');
            loadingOverlay.className='loading-overlay';
            loadingOverlay.innerHTML=`<div class="loading-spinner"><div>${message}</div></div>`;
            document.body.appendChild(loadingOverlay);
        } else {
            loadingOverlay.querySelector('.loading-spinner div').textContent=message;
            loadingOverlay.style.display='flex';
        }
    }

    function hideLoading() {
        if(loadingOverlay) {
            loadingOverlay.style.display='none';
        }
    }

    // Helper function to generate random key for options (Forminator format)
    function generateRandomKey() {
        return Math.floor(1000+Math.random()*9000)+'-'+Math.floor(1000+Math.random()*9000);
    }

    // Forminator field structure templates (matching existing format)
    const fieldTemplates={
        text: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'text',
            options: [],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            input_type: 'line',
            limit_type: 'characters',
            field_label: '',
            placeholder: '',
            required: '0',
            'custom-class': ''
        },
        email: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'email',
            options: [],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            validation: '1',
            field_label: '',
            placeholder: '',
            required: '0',
            'custom-class': ''
        },
        textarea: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'textarea',
            options: [],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            input_type: 'line',
            limit_type: 'characters',
            field_label: '',
            placeholder: '',
            required: '0',
            'custom-class': ''
        },
        select: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'select',
            options: [
                {
                    label: 'Option 1',
                    value: 'option-1',
                    key: generateRandomKey(),
                    default: ''
                },
                {
                    label: 'Option 2',
                    value: 'option-2',
                    key: generateRandomKey(),
                    default: ''
                }
            ],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            field_label: '',
            placeholder: 'Select an option',
            required: '0',
            'custom-class': '',
            multiple: '0'
        },
        checkbox: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'checkbox',
            options: [
                {
                    label: 'Option 1',
                    value: 'option-1',
                    key: generateRandomKey(),
                    error: '',
                    default: ''
                },
                {
                    label: 'Option 2',
                    value: 'option-2',
                    key: generateRandomKey(),
                    error: '',
                    default: ''
                }
            ],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            field_label: '',
            required: '0',
            'custom-class': '',
            hidden_behavior: 'zero',
            value_type: 'checkbox',
            layout: 'vertical'
        },
        radio: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'radio',
            options: [
                {
                    label: 'Option 1',
                    value: 'option-1',
                    key: generateRandomKey(),
                    error: '',
                    default: ''
                },
                {
                    label: 'Option 2',
                    value: 'option-2',
                    key: generateRandomKey(),
                    error: '',
                    default: ''
                }
            ],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            field_label: '',
            required: '0',
            'custom-class': '',
            value_type: 'radio',
            layout: 'vertical'
        },
        captcha: {
            id: '',
            element_id: '',
            form_id: '',
            parent_group: '',
            type: 'captcha',
            options: [],
            cols: '12',
            conditions: [],
            wrapper_id: '',
            captcha_provider: 'recaptcha',
            captcha_alignment: 'left',
            captcha_type: 'v3_recaptcha',
            hcaptcha_type: 'hc_checkbox',
            score_threshold: '0.5',
            captcha_badge: 'bottomright',
            hc_invisible_notice: 'This site is protected by hCaptcha and its <a href="https://hcaptcha.com/privacy">Privacy Policy</a> and <a href="https://hcaptcha.com/terms">Terms of Service</a> apply.',
            recaptcha_error_message: 'reCAPTCHA verification failed. Please try again.',
            hcaptcha_error_message: 'hCaptcha verification failed. Please try again.'
        }
    };

    // Helper function to generate random key for options (Forminator format)
    function generateRandomKey() {
        return Math.floor(1000+Math.random()*9000)+'-'+Math.floor(1000+Math.random()*9000);
    }

    // Load forms from server
    function loadForms() {
        showLoading('Loading forms...');
        fetch('?action=get_other_contents&type=forms')
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if(data.success) {
                    forms=data.data||[];
                    renderFormsList();
                } else {
                    showError('Failed to load forms: '+(data.error||'Unknown error'));
                }
            })
            .catch(err => {
                hideLoading();
                console.error('Error loading forms:',err);
                showError('Failed to load forms');
            });
    }

    // Render forms list
    function renderFormsList() {
        formsListEl.innerHTML='';
        if(forms.length===0) {
            formsListEl.innerHTML='<p style="text-align: center; color: #666; padding: 2rem;">No forms found. Create your first form!</p>';
            return;
        }

        forms.forEach((form,idx) => {
            const item=document.createElement('div');
            item.className='forms-list-item';
            item.innerHTML=`
        <div class="forms-list-info">
          <span class="form-name">${form.name||'Untitled Form'}</span>
          <span class="form-fields-count">${form.json.fields? form.json.fields.length:0} fields</span>
        </div>
        <div class="forms-list-actions">
          <button data-edit="${idx}" class="btn btn-sm btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
          </button>
          <button data-delete="${idx}" class="btn btn-sm btn-danger">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            Delete
          </button>
        </div>
      `;
            formsListEl.appendChild(item);
        });
    }

    // Event handlers for form list
    formsListEl.addEventListener('click',function(e) {
        if(e.target.dataset.edit!==undefined) {
            const idx=parseInt(e.target.dataset.edit);
            editForm(idx);
        }
        if(e.target.dataset.delete!==undefined) {
            const idx=parseInt(e.target.dataset.delete);
            deleteForm(idx);
        }
    });

    // Edit form
    function editForm(idx) {
        editingFormIndex=idx;
        editingForm=JSON.parse(JSON.stringify(forms[idx].json));
        formNameInput.value=forms[idx].name||'';

        // Load submit button text
        const submitButtonText=editingForm.settings&&editingForm.settings.submitData&&editingForm.settings.submitData['custom-submit-text']?
            editingForm.settings.submitData['custom-submit-text']:'SUBMIT';
        document.getElementById('submit-button-text').value=submitButtonText;

        // Load placeholders from forms-config.json
        const formId=forms[idx].id;
        formPlaceholders=[];

        fetch('/config/forms-config.json?t='+Date.now())
            .then(res => res.json())
            .then(formsConfig => {
                if(formsConfig[formId]&&formsConfig[formId].placeholders) {
                    formPlaceholders=formsConfig[formId].placeholders;
                }
                renderPlaceholders();
            })
            .catch(err => {
                console.error('Error loading placeholders:',err);
                renderPlaceholders();
            });

        document.getElementById('form-editor-title').textContent='Edit Form';
        renderFormElements();
        formEditorEl.style.display='block';
        formDeleteBtn.style.display='inline-flex';
        formEditorEl.scrollIntoView({behavior: 'smooth',block: 'start'});
    }

    // Delete form
    function deleteForm(idx) {
        if(!confirm('Are you sure you want to delete this form? This action cannot be undone.')) return;

        showLoading('Deleting form...');
        fetch(`?action=delete_other_content&type=forms&id=${encodeURIComponent(forms[idx].id)}`,{method: 'POST'})
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if(data.success) {
                    showSuccess('Form deleted successfully');
                    loadForms();
                    // Reload dynamic forms in integration settings if adminInterface exists
                    if(window.adminInterface&&typeof window.adminInterface.loadDynamicForms==='function') {
                        window.adminInterface.loadDynamicForms().catch(err => console.error('Failed to reload integration forms:',err));
                    }
                    formEditorEl.style.display='none';
                } else {
                    showError('Failed to delete form: '+(data.error||'Unknown error'));
                }
            })
            .catch(err => {
                hideLoading();
                console.error('Error deleting form:',err);
                showError('Failed to delete form');
            });
    }

    // Add new form
    addFormBtn.addEventListener('click',function() {
        editingFormIndex=null;
        editingForm={
            fields: [],
            settings: {
                formName: '',
                'form-type': 'default',
                'submission-behaviour': 'behaviour-thankyou',
                'thankyou-message': 'Thank you for your submission!',
                submitData: {
                    'custom-submit-text': 'SUBMIT',
                    'custom-invalid-form-message': 'Error: Your form is not valid, please fix the errors!'
                }
            }
        };
        formNameInput.value='';
        document.getElementById('submit-button-text').value='SUBMIT';
        formPlaceholders=[];
        renderPlaceholders();
        document.getElementById('form-editor-title').textContent='Create New Form';
        renderFormElements();
        formEditorEl.style.display='block';
        formDeleteBtn.style.display='none';
        formEditorEl.scrollIntoView({behavior: 'smooth',block: 'start'});
    });

    // Cancel editing
    const cancelBtn=document.getElementById('form-cancel-btn');
    if(cancelBtn) {
        cancelBtn.addEventListener('click',function() {
            if(confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                formEditorEl.style.display='none';
                editingFormIndex=null;
                editingForm=null;
                hideSuccess();
                hideError();
            }
        });
    }

    // Add element to form
    addElementBtn.addEventListener('click',function() {
        const type=elementTypeInput.value;
        const label=elementLabelInput.value.trim();

        // Captcha doesn't need a label
        if(type!=='captcha'&&!label) {
            showError('Element label is required');
            return;
        }

        editingForm.fields=editingForm.fields||[];
        const fieldId=type+'-'+(Date.now()%10000);
        const wrapperId='wrapper-'+Math.floor(Math.random()*9000+1000)+'-'+Math.floor(Math.random()*9000+1000);

        const newField=Object.assign({},fieldTemplates[type]);
        newField.id=fieldId;
        newField.element_id=fieldId;
        newField.form_id=wrapperId;
        newField.wrapper_id=wrapperId;

        // Set field label only for non-captcha fields
        if(type!=='captcha') {
            newField.field_label=label;
        }

        // Set placeholder only for fields that support it
        if(['text','email','textarea','select'].includes(type)) {
            newField.placeholder=label;
        }

        editingForm.fields.push(newField);
        elementLabelInput.value='';
        renderFormElements();
        showSuccess('Element added successfully');
    });

    // Render form elements
    function renderFormElements() {
        formElementsListEl.innerHTML='';
        const fields=editingForm.fields||[];

        if(fields.length===0) {
            return; // CSS will show empty state message
        }

        fields.forEach((field,idx) => {
            const item=document.createElement('div');
            item.className='form-element-item';

            // Show options for select, checkbox, radio
            const hasOptions=['select','checkbox','radio'].includes(field.type);
            const optionsCount=hasOptions&&field.options? field.options.length:0;
            const optionsText=hasOptions? ` • ${optionsCount} option${optionsCount!==1? 's':''}`:'';

            // Captcha doesn't have required/optional status
            const isCaptcha=field.type==='captcha';
            const fieldLabel=isCaptcha? 'reCAPTCHA Protection':(field.field_label||'Untitled Field');
            const statusText=isCaptcha? '':(field.required==='1'? ' • Required':' • Optional');

            item.innerHTML=`
        <div class="form-element-info">
          <span class="element-label">${fieldLabel}</span>
          <span class="element-type">${field.type}${statusText}${optionsText}</span>
        </div>
        <div class="form-element-actions">
          ${hasOptions? `<button type="button" data-edit-options="${idx}" class="btn btn-sm btn-primary" title="Edit options">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Options
          </button>` :''}
          ${!isCaptcha? `<button type="button" data-toggle-required="${idx}" class="btn btn-sm btn-outline-secondary" title="Toggle required status">
            ${field.required==='1'? 'Make Optional':'Make Required'}
          </button>`:''}
          <button type="button" data-remove="${idx}" class="btn btn-sm btn-danger" title="Remove field">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            Remove
          </button>
        </div>
      `;
            formElementsListEl.appendChild(item);
        });
    }

    // Handle element actions
    formElementsListEl.addEventListener('click',function(e) {
        if(e.target.dataset.remove!==undefined) {
            e.preventDefault();
            const idx=parseInt(e.target.dataset.remove);
            editingForm.fields.splice(idx,1);
            renderFormElements();
            showSuccess('Element removed');
        }
        if(e.target.dataset.toggleRequired!==undefined) {
            e.preventDefault();
            const idx=parseInt(e.target.dataset.toggleRequired);
            editingForm.fields[idx].required=editingForm.fields[idx].required==='1'? '0':'1';
            renderFormElements();
        }
        if(e.target.dataset.editOptions!==undefined) {
            e.preventDefault();
            const idx=parseInt(e.target.dataset.editOptions);
            editFieldOptions(idx);
        }
    });

    // Edit field options (for select, checkbox, radio)
    function editFieldOptions(fieldIdx) {
        const field=editingForm.fields[fieldIdx];
        if(!['select','checkbox','radio'].includes(field.type)) {
            return;
        }

        // Create modal overlay
        const modal=document.createElement('div');
        modal.className='options-modal-overlay';
        modal.innerHTML=`
            <div class="options-modal">
                <div class="options-modal-header">
                    <h3>Edit Options: ${field.field_label}</h3>
                    <button type="button" class="options-modal-close">&times;</button>
                </div>
                <div class="options-modal-body">
                    <div class="options-list" id="options-list-${fieldIdx}"></div>
                    <div class="options-add">
                        <input type="text" id="new-option-label" placeholder="New option label" />
                        <button type="button" id="add-option-btn" class="btn btn-primary">Add Option</button>
                    </div>
                </div>
                <div class="options-modal-footer">
                    <button type="button" class="btn btn-outline-secondary options-modal-cancel">Cancel</button>
                    <button type="button" class="btn btn-primary options-modal-save">Save Options</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const optionsList=modal.querySelector(`#options-list-${fieldIdx}`);
        const newOptionInput=modal.querySelector('#new-option-label');
        const addOptionBtn=modal.querySelector('#add-option-btn');

        // Render current options
        function renderOptions() {
            optionsList.innerHTML='';
            (field.options||[]).forEach((option,optIdx) => {
                const optionEl=document.createElement('div');
                optionEl.className='option-item';
                optionEl.innerHTML=`
                    <input type="text" value="${option.label}" data-option-idx="${optIdx}" class="option-label-input" />
                    <button type="button" data-remove-option="${optIdx}" class="btn btn-sm btn-danger">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                `;
                optionsList.appendChild(optionEl);
            });
        }

        renderOptions();

        // Add new option
        addOptionBtn.addEventListener('click',() => {
            const label=newOptionInput.value.trim();
            if(!label) {
                return;
            }

            const newOption={
                label: label,
                value: label.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''),
                key: generateRandomKey(),
                error: ''
            };

            // Add default property for checkbox
            if(field.type==='checkbox') {
                newOption.default='';
            }

            field.options=field.options||[];
            field.options.push(newOption);
            newOptionInput.value='';
            renderOptions();
        });

        // Handle option events
        optionsList.addEventListener('click',(e) => {
            if(e.target.dataset.removeOption!==undefined) {
                const optIdx=parseInt(e.target.dataset.removeOption);
                field.options.splice(optIdx,1);
                renderOptions();
            }
        });

        optionsList.addEventListener('input',(e) => {
            if(e.target.classList.contains('option-label-input')) {
                const optIdx=parseInt(e.target.dataset.optionIdx);
                const newLabel=e.target.value;
                field.options[optIdx].label=newLabel;
                field.options[optIdx].value=newLabel.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
            }
        });

        // Close modal
        function closeModal() {
            document.body.removeChild(modal);
            renderFormElements();
        }

        modal.querySelector('.options-modal-close').addEventListener('click',closeModal);
        modal.querySelector('.options-modal-cancel').addEventListener('click',closeModal);
        modal.querySelector('.options-modal-save').addEventListener('click',() => {
            showSuccess(`Options updated for ${field.field_label}`);
            closeModal();
        });

        // Close on overlay click
        modal.addEventListener('click',(e) => {
            if(e.target===modal) {
                closeModal();
            }
        });
    }

    // Placeholder Management Functions
    const placeholderInput=document.getElementById('placeholder-input');
    const addPlaceholderBtn=document.getElementById('add-placeholder-btn');
    const placeholdersListEl=document.getElementById('placeholders-list');

    // Check if elements exist
    if(!placeholderInput||!addPlaceholderBtn||!placeholdersListEl) {
        console.error('Placeholder elements not found in DOM');
    }

    // Load all existing placeholders from all forms (for validation)
    async function getAllExistingPlaceholders() {
        const allPlaceholders=new Map(); // Map placeholder to form name

        // Read forms-config.json to get all form placeholders
        try {
            const response=await fetch('/config/forms-config.json?t='+Date.now());
            const formsConfig=await response.json();

            for(const [formId,formData] of Object.entries(formsConfig)) {
                if(formData.placeholders) {
                    formData.placeholders.forEach(placeholder => {
                        allPlaceholders.set(placeholder,formId);
                    });
                }
            }
        } catch(err) {
            console.error('Error loading placeholders:',err);
        }

        return allPlaceholders;
    }

    // Normalize placeholder format - auto-add brackets if missing and slugify
    function normalizePlaceholder(input) {
        let normalized=input.trim();

        // Remove any existing brackets first
        normalized=normalized.replace(/^\[|\]$/g,'');

        // Slugify: convert to lowercase, replace spaces/special chars with hyphens
        normalized=normalized
            .toLowerCase()
            .replace(/[^a-z0-9-]+/g,'-')  // Replace non-alphanumeric (except hyphens) with hyphens
            .replace(/^-+|-+$/g,'')       // Remove leading/trailing hyphens
            .replace(/-+/g,'-');          // Replace multiple consecutive hyphens with single hyphen

        // Add brackets
        return `[${normalized}]`;
    }

    // Validate placeholder format (after normalization)
    function isValidPlaceholder(placeholder) {
        // Must be in format [placeholder-name] with valid characters
        return /^\[[\w-]+\]$/.test(placeholder);
    }

    // Render placeholders list
    function renderPlaceholders() {
        if(!placeholdersListEl) return;

        placeholdersListEl.innerHTML='';
        formPlaceholders.forEach((placeholder,idx) => {
            const tag=document.createElement('div');
            tag.className='placeholder-tag';
            tag.innerHTML=`
                <span>${placeholder}</span>
                <span class="remove-placeholder" data-idx="${idx}" title="Remove placeholder">×</span>
            `;
            placeholdersListEl.appendChild(tag);
        });
    }

    // Add placeholder
    if(addPlaceholderBtn) {
        addPlaceholderBtn.addEventListener('click',async function() {
            const rawInput=placeholderInput.value.trim();

            if(!rawInput) {
                showError('Please enter a placeholder');
                return;
            }

            // Normalize the placeholder (auto-add brackets)
            const placeholder=normalizePlaceholder(rawInput);

            if(!isValidPlaceholder(placeholder)) {
                showError('Placeholder must contain only letters, numbers, and hyphens (e.g., contact-form)');
                return;
            }

            // Check if already exists in current form
            if(formPlaceholders.includes(placeholder)) {
                showError('This placeholder is already added to this form');
                return;
            }

            // Check if placeholder exists in other forms
            const allPlaceholders=await getAllExistingPlaceholders();
            const currentFormKey=editingFormIndex!==null? forms[editingFormIndex].id.replace(/-/g,'_')+'_form':null;

            for(const [existingPlaceholder,formKey] of allPlaceholders.entries()) {
                if(existingPlaceholder===placeholder&&formKey!==currentFormKey) {
                    showError(`Placeholder "${placeholder}" is already assigned to ${formKey}`);
                    return;
                }
            }

            // Add placeholder to list
            formPlaceholders.push(placeholder);
            renderPlaceholders();
            placeholderInput.value='';
            hideError();
        });
    }

    // Remove placeholder
    if(placeholdersListEl) {
        placeholdersListEl.addEventListener('click',function(e) {
            if(e.target.classList.contains('remove-placeholder')) {
                const idx=parseInt(e.target.dataset.idx);
                formPlaceholders.splice(idx,1);
                renderPlaceholders();
            }
        });
    }

    // Allow Enter key to add placeholder
    if(placeholderInput&&addPlaceholderBtn) {
        placeholderInput.addEventListener('keypress',function(e) {
            if(e.key==='Enter') {
                e.preventDefault();
                addPlaceholderBtn.click();
            }
        });
    }

    // Save form
    formSaveBtn.addEventListener('click',function() {
        const name=formNameInput.value.trim();
        if(!name) {
            showError('Form name is required');
            return;
        }

        if(!editingForm.fields||editingForm.fields.length===0) {
            showError('Please add at least one field to the form');
            return;
        }

        // Update form name in settings
        editingForm.settings=editingForm.settings||{};
        editingForm.settings.formName=name;

        // Update submit button text
        const submitButtonText=document.getElementById('submit-button-text').value.trim()||'SUBMIT';
        editingForm.settings.submitData=editingForm.settings.submitData||{};
        editingForm.settings.submitData['custom-submit-text']=submitButtonText;
        editingForm.settings.submitData['custom-invalid-form-message']=editingForm.settings.submitData['custom-invalid-form-message']||'Error: Your form is not valid, please fix the errors!';

        formSaveBtn.disabled=true;
        formSaveBtn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Saving...';
        showLoading('Saving form...');

        // Generate form ID from name if creating new form
        const formId=editingFormIndex!==null? forms[editingFormIndex].id:name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');

        // Update the forms array with the edited/new form
        const updatedForm={
            id: formId,
            name: name,
            json: editingForm
        };

        let updatedForms=[...forms];
        if(editingFormIndex!==null) {
            // Update existing form
            updatedForms[editingFormIndex]=updatedForm;
        } else {
            // Add new form
            updatedForms.push(updatedForm);
        }

        // Save form JSON files
        const saveFormPromise=fetch('?action=save_other_contents',{
            method: 'POST',
            body: JSON.stringify({
                type: 'forms',
                contents: updatedForms
            }),
            headers: {'Content-Type': 'application/json'}
        });

        // Save placeholders to forms-config.json
        const savePlaceholdersPromise=fetch('/config/forms-config.json?t='+Date.now())
            .then(res => res.json())
            .then(formsConfig => {
                // Ensure the form entry exists
                if(!formsConfig[formId]) {
                    formsConfig[formId]={};
                }
                formsConfig[formId].placeholders=formPlaceholders;

                // Save the updated forms-config.json
                return fetch('?action=save_forms_config',{
                    method: 'POST',
                    body: JSON.stringify(formsConfig),
                    headers: {'Content-Type': 'application/json'}
                });
            })
            .then(res => res.json());

        // Wait for both saves to complete
        Promise.all([saveFormPromise,savePlaceholdersPromise])
            .then(([formData,configData]) => {
                hideLoading();
                formSaveBtn.disabled=false;
                formSaveBtn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Form';

                console.log('Form save response:',formData);
                console.log('Placeholders save response:',configData);

                // Check if both operations succeeded (handle both explicit success and no response as success)
                const formSuccess=!formData||(formData&&formData.success!==false);
                const placeholdersSuccess=!configData||(configData&&configData.success!==false);

                if(formSuccess&&placeholdersSuccess) {
                    showSuccess('Form and placeholders saved successfully!');
                    loadForms();
                    // Reload dynamic forms in integration settings if adminInterface exists
                    if(window.adminInterface&&typeof window.adminInterface.loadDynamicForms==='function') {
                        window.adminInterface.loadDynamicForms().catch(err => console.error('Failed to reload integration forms:',err));
                    }
                    setTimeout(() => {
                        formEditorEl.style.display='none';
                    },1500);
                } else {
                    // Determine which part failed
                    let errorMsg='Unknown error';
                    if(!formSuccess&&!placeholdersSuccess) {
                        errorMsg='Failed to save form and placeholders';
                    } else if(!formSuccess) {
                        errorMsg='Failed to save form: '+(formData?.error||formData?.message||'Unknown error');
                    } else if(!placeholdersSuccess) {
                        errorMsg='Failed to save placeholders: '+(configData?.error||configData?.message||'Unknown error');
                    }
                    console.error('Save error:',errorMsg,{formData,configData});
                    showError(errorMsg);
                }
            })
            .catch(err => {
                hideLoading();
                formSaveBtn.disabled=false;
                formSaveBtn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Form';
                console.error('Error saving form:',err);
                showError('Failed to save form: '+err.message);
            });
    });

    // Delete form (from editor)
    formDeleteBtn.addEventListener('click',function() {
        if(editingFormIndex===null) return;
        deleteForm(editingFormIndex);
    });

    // Utility functions
    function showSuccess(message) {
        formSuccessEl.textContent=message;
        formSuccessEl.style.display='flex';
        formErrorEl.style.display='none';
        setTimeout(() => {formSuccessEl.style.display='none';},5000);
    }

    function showError(message) {
        formErrorEl.textContent=message;
        formErrorEl.style.display='flex';
        formSuccessEl.style.display='none';
    }

    function hideSuccess() {
        formSuccessEl.style.display='none';
    }

    function hideError() {
        formErrorEl.style.display='none';
    }

    // Initial load
    loadForms();
});
