#!/bin/bash

# Kinsta Container Setup Script
# Run this once after deployment via web terminal

set -e

echo "==================================================================="
echo "Kinsta Container Setup - SSH Key Generation"
echo "==================================================================="
echo ""

# Check if running as root (required for apt install)
if [[ $EUID -ne 0 ]]; then
   echo "⚠️  Warning: Not running as root. Some operations may fail."
   echo "   If you see permission errors, try: sudo bash setup.sh"
   echo ""
fi

# Fix ownership of directories (only if they exist and we have permission)
echo "▶ Setting directory permissions..."
for dir in config logs tmp webhook/tasks; do
    if [[ -d "$dir" ]]; then
        chown -R nobody:nogroup "$dir" 2>/dev/null && echo "  ✓ $dir" || echo "  ⚠️  $dir (skipped)"
    fi
done
echo ""

# Install required packages
echo "▶ Installing required packages..."
apt-get update -qq
apt-get install -y -qq jq openssh-client openssl git curl rsync python3 python3-pip
echo "  ✓ Packages installed"
echo ""

# Check if PyNaCl is available, try to install if not
if ! python3 -c "import nacl" &> /dev/null; then
    echo "▶ Installing PyNaCl for SSH support..."
    
    # Try apt first (recommended for Ubuntu 24.04+)
    if apt-get install -y -qq python3-pynacl 2>/dev/null; then
        echo "  ✓ PyNaCl installed via apt"
    else
        # Fallback to pip with --break-system-packages (safe in containers)
        echo "  ⚠️  Falling back to pip installation..."
        pip3 install --quiet --break-system-packages pynacl
        echo "  ✓ PyNaCl installed via pip"
    fi
    echo ""
else
    echo "  ✓ PyNaCl already installed"
    echo ""
fi

# Create SSH directory
echo "▶ Creating SSH directory..."
mkdir -p /app/.ssh
chmod 700 /app/.ssh
chown -R nobody:nogroup /app/.ssh
echo "  ✓ /app/.ssh created with proper permissions"
echo ""

# Generate SSH key (skip if already exists)
if [[ -f /app/.ssh/id_rsa ]]; then
    echo "⚠️  SSH key already exists at /app/.ssh/id_rsa"
    echo "   Fixing ownership and permissions..."
    chown nobody:nogroup /app/.ssh/id_rsa /app/.ssh/id_rsa.pub 2>/dev/null || true
    chmod 600 /app/.ssh/id_rsa
    chmod 644 /app/.ssh/id_rsa.pub
    echo "  ✓ Ownership and permissions updated"
    echo ""
else
    echo "▶ Generating SSH key..."
    ssh-keygen -t rsa -b 4096 -C "kinsta-deployment" -f /app/.ssh/id_rsa -N ""
    # CRITICAL: Set ownership to nobody:nogroup so PHP scripts can read it
    chown nobody:nogroup /app/.ssh/id_rsa /app/.ssh/id_rsa.pub
    chmod 600 /app/.ssh/id_rsa
    chmod 644 /app/.ssh/id_rsa.pub
    echo "  ✓ SSH key generated with proper ownership (nobody:nogroup)"
    echo ""
fi

# Configure Google OAuth from environment variables using PHP
echo "▶ Configuring Google OAuth authentication..."
AUTH_CONFIG_FILE="./config/auth.json"

# Ensure config directory exists
mkdir -p ./config 2>/dev/null || true

# Use PHP to read Kinsta environment variables and write auth.json
# PHP has access to Kinsta env vars, bash does not
if command -v php &> /dev/null; then
    echo "  Running PHP configuration script..."
    
    # Run PHP setup script
    php php/setup-config.php
    PHP_EXIT_CODE=$?
    
    if [[ $PHP_EXIT_CODE -eq 0 ]]; then
        # Verify the file was created
        if [[ -f "$AUTH_CONFIG_FILE" ]]; then
            # Set secure permissions (readable by PHP/nobody user)
            chmod 640 "$AUTH_CONFIG_FILE"
            chown nobody:nogroup "$AUTH_CONFIG_FILE" 2>/dev/null || true
            echo "  ✓ Google OAuth configured successfully"
        else
            echo "  ⚠️  Configuration script ran but auth.json not found"
        fi
    else
        echo "  ⚠️  Configuration script failed (exit code: $PHP_EXIT_CODE)"
        echo "     This usually means environment variables are not set."
        echo ""
        echo "     Set these in Kinsta Environment Variables dashboard:"
        echo "     - client_id"
        echo "     - client_secret"
        echo "     - redirect_uri"
        echo "     - allowed_domain (optional, defaults to frontlinestrategies.co)"
        echo ""
        echo "     After setting env vars, run: php php/setup-config.php"
        
        # Create empty template if file doesn't exist
        if [[ ! -f "$AUTH_CONFIG_FILE" ]]; then
            echo '{"client_id":"","client_secret":"","redirect_uri":"","allowed_domain":"frontlinestrategies.co"}' > "$AUTH_CONFIG_FILE"
            chmod 640 "$AUTH_CONFIG_FILE"
            chown nobody:nogroup "$AUTH_CONFIG_FILE" 2>/dev/null || true
            echo "  ✓ Empty auth.json template created"
        fi
    fi
else
    echo "  ⚠️  PHP not found in PATH"
    echo "     Cannot configure OAuth automatically"
    
    # Create empty template
    if [[ ! -f "$AUTH_CONFIG_FILE" ]]; then
        echo '{"client_id":"","client_secret":"","redirect_uri":"","allowed_domain":"frontlinestrategies.co"}' > "$AUTH_CONFIG_FILE"
        chmod 640 "$AUTH_CONFIG_FILE"
        chown nobody:nogroup "$AUTH_CONFIG_FILE" 2>/dev/null || true
        echo "  ✓ Empty auth.json template created"
    fi
fi
echo ""

# Display public key
echo "==================================================================="
echo "✓ Setup Complete!"
echo "==================================================================="
echo ""
echo "📋 NEXT STEPS - ADD SSH KEY TO KINSTA"
echo ""
echo "1. Copy this PUBLIC KEY (everything from ssh-rsa to kinsta-deployment):"
echo ""
echo "-------------------------------------------------------------------"
cat /app/.ssh/id_rsa.pub
echo "-------------------------------------------------------------------"
echo ""
echo "2. Go to: https://my.kinsta.com/account/ssh-keys"
echo ""
echo "3. Click 'Add SSH Key'"
echo ""
echo "4. Paste the key above and save"
echo ""
echo "5. Wait 1-2 minutes for the key to propagate across Kinsta servers"
echo ""
echo "⚠️  IMPORTANT: Deployment will fail if this key is not added!"
echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "📚 Helpful Commands:"
echo "═══════════════════════════════════════════════════════════════════"
echo ""
echo "View SSH key again:"
echo "  cat /app/.ssh/id_rsa.pub"
echo ""
echo "Test SSH connection (replace with your actual values):"
echo "  ssh -i /app/.ssh/id_rsa -p YOUR_PORT YOUR_USER@YOUR_HOST"
echo ""
echo "Configure OAuth (if env vars set after initial setup):"
echo "  php php/setup-config.php"
echo ""
echo "Check if OAuth env vars are available:"
echo "  php -r \"echo getenv('client_id') ? 'Found' : 'Not set';\""
echo ""
echo "Example SSH connection:"
echo "  ssh -i /app/.ssh/id_rsa -p 47807 pocsite@146.148.59.197"
echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo ""