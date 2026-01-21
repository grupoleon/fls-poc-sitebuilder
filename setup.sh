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
for dir in config logs tmp; do
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
    echo "   Delete it first if you want to generate a new one: rm /app/.ssh/id_rsa*"
    echo ""
else
    echo "▶ Generating SSH key..."
    ssh-keygen -t rsa -b 4096 -C "kinsta-deployment" -f /app/.ssh/id_rsa -N ""
    chmod 600 /app/.ssh/id_rsa
    chmod 644 /app/.ssh/id_rsa.pub
    echo "  ✓ SSH key generated"
    echo ""
fi

# Display public key
echo "==================================================================="
echo "✓ Setup Complete!"
echo "==================================================================="
echo ""
echo "📋 Copy this PUBLIC KEY and add it to your Kinsta SSH keys:"
echo ""
echo "-------------------------------------------------------------------"
cat /app/.ssh/id_rsa.pub
echo "-------------------------------------------------------------------"
echo ""
echo "🔗 Add it here: MyKinsta → Account Settings → SSH Keys"
echo ""
echo "⚠️  Important: Keep this terminal open until you've added the key!"
echo ""