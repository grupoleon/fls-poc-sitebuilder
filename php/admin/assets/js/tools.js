// Admin credentials password functions
function togglePasswordVisibility(inputId) {
    const passwordInput=document.getElementById(inputId);
    const toggleIcon=document.getElementById(inputId.replace('-input','-toggle-icon'));

    if(passwordInput.type==='password') {
        passwordInput.type='text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type='password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function generateSecurePassword(inputId) {
    const passwordInput=document.getElementById(inputId);

    // Generate a secure password with required character types
    const lowercase='abcdefghijklmnopqrstuvwxyz';
    const uppercase='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers='0123456789';
    const symbols='!@#$%^&*()_+-=[]{}|;:,.<>?';

    let password='';

    // Ensure at least one character from each required type
    password+=lowercase[Math.floor(Math.random()*lowercase.length)];
    password+=uppercase[Math.floor(Math.random()*uppercase.length)];
    password+=numbers[Math.floor(Math.random()*numbers.length)];
    password+=symbols[Math.floor(Math.random()*symbols.length)];

    // Fill the rest randomly from all character sets
    const allChars=lowercase+uppercase+numbers+symbols;
    for(let i=4;i<16;i++) {
        password+=allChars[Math.floor(Math.random()*allChars.length)];
    }

    // Shuffle the password to avoid predictable patterns
    password=password.split('').sort(() => Math.random()-0.5).join('');

    passwordInput.value=password;

    // Trigger change event to update the config
    passwordInput.dispatchEvent(new Event('input',{
        bubbles: true
    }));

    // Show password temporarily
    const toggleIcon=document.getElementById(inputId.replace('-input','-toggle-icon'));
    passwordInput.type='text';
    if(toggleIcon) {
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }

    // Update password strength indicator
    updatePasswordStrength(inputId);

    // Hide after 3 seconds
    setTimeout(() => {
        if(passwordInput.type==='text') {
            passwordInput.type='password';
            if(toggleIcon) {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    },3000);
}

function updatePasswordStrength(inputId) {
    const passwordInput=document.getElementById(inputId);
    const strengthDiv=document.getElementById(inputId.replace('-input','-strength'));

    if(!passwordInput||!strengthDiv) return;

    const password=passwordInput.value;
    let strength=0;
    let feedback=[];

    // Length check
    if(password.length>=12) strength+=1;
    else if(password.length>=8) strength+=0.5;
    else feedback.push('Use at least 12 characters');

    // Character variety checks
    if(/[a-z]/.test(password)) strength+=1;
    else feedback.push('Add lowercase letters');

    if(/[A-Z]/.test(password)) strength+=1;
    else feedback.push('Add uppercase letters');

    if(/[0-9]/.test(password)) strength+=1;
    else feedback.push('Add numbers');

    if(/[^A-Za-z0-9]/.test(password)) strength+=1;
    else feedback.push('Add special characters');

    // Display strength
    let strengthText='';
    let strengthClass='';

    if(strength<2) {
        strengthText='Weak';
        strengthClass='text-red-600';
    } else if(strength<4) {
        strengthText='Fair';
        strengthClass='text-yellow-600';
    } else if(strength<5) {
        strengthText='Good';
        strengthClass='text-blue-600';
    } else {
        strengthText='Strong';
        strengthClass='text-green-600';
    }

    if(password.length===0) {
        strengthDiv.innerHTML='';
    } else {
        strengthDiv.innerHTML=`
                    <div class="password-strength-indicator mt-2">
                        <span class="text-sm font-medium ${strengthClass}">Password Strength: ${strengthText}</span>
                        ${feedback.length>0? `<div class="text-xs text-gray-500 mt-1">${feedback.join(', ')}</div>`:''}
                    </div>
                `;
    }
}

// Add event listener for password strength checking
document.addEventListener('DOMContentLoaded',function() {
    const adminPasswordInput=document.getElementById('admin-password-input');
    if(adminPasswordInput) {
        adminPasswordInput.addEventListener('input',function() {
            updatePasswordStrength('admin-password-input');
        });
    }
});

// Password visibility toggle
document.getElementById('toggle-password').addEventListener('click',function() {
    const passwordInput=document.getElementById('admin-password');
    const toggleBtn=this;

    if(passwordInput.type==='password') {
        passwordInput.type='text';
        toggleBtn.innerHTML='<i class="fas fa-eye-slash"></i>';
        toggleBtn.title='Hide password';
    } else {
        passwordInput.type='password';
        toggleBtn.innerHTML='<i class="fas fa-eye"></i>';
        toggleBtn.title='Show password';
    }
});

// Password generator
document.getElementById('generate-password').addEventListener('click',function() {
    const passwordInput=document.getElementById('admin-password');

    // Generate a secure password
    const length=16;
    const charset='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password='';

    for(let i=0;i<length;i++) {
        const randomIndex=Math.floor(Math.random()*charset.length);
        password+=charset[randomIndex];
    }

    passwordInput.value=password;

    // Trigger change event to update the config
    passwordInput.dispatchEvent(new Event('input',{
        bubbles: true
    }));

    // Show password temporarily
    const originalType=passwordInput.type;
    const toggleBtn=document.getElementById('toggle-password');
    passwordInput.type='text';
    toggleBtn.innerHTML='<i class="fas fa-eye-slash"></i>';

    // Hide after 3 seconds
    setTimeout(() => {
        if(passwordInput.type==='text') {
            passwordInput.type='password';
            toggleBtn.innerHTML='<i class="fas fa-eye"></i>';
        }
    },3000);
});
