#!/bin/bash
# =============================================================================
# FLS POC Site Builder - Kinsta Startup Script
# =============================================================================
# This script runs BEFORE PHP-FPM and Nginx start.
# It handles:
#   1. SSH key injection from environment
#   2. OAuth config generation from Kinsta env vars
#   3. Permission fixes
# =============================================================================

set -e

log() { echo "[KINSTA-START] $1"; }
log_warn() { echo "[KINSTA-START] WARN: $1"; }

# =============================================================================
# 1. SSH KEY SETUP
# =============================================================================
setup_ssh() {
    log "Setting up SSH credentials..."

    SSH_DIR="/app/.ssh"
    mkdir -p "$SSH_DIR"
    chmod 700 "$SSH_DIR"

    if [[ -n "${SSH_PRIVATE_KEY:-}" ]]; then
        # Decode base64 key (NEVER log the key)
        echo "$SSH_PRIVATE_KEY" | base64 -d > "$SSH_DIR/id_rsa" 2>/dev/null || \
        echo "$SSH_PRIVATE_KEY" > "$SSH_DIR/id_rsa"
        chmod 600 "$SSH_DIR/id_rsa"
        # Generate public key
        ssh-keygen -y -f "$SSH_DIR/id_rsa" > "$SSH_DIR/id_rsa.pub" 2>/dev/null || true
        log "SSH key configured from environment"
    elif [[ -f "$SSH_DIR/id_rsa" ]]; then
        chmod 600 "$SSH_DIR/id_rsa"
        log "SSH key permissions fixed"
    else
        log_warn "No SSH key provided - deployments requiring SSH will fail"
    fi

    # Add Kinsta host to known_hosts
    if [[ -n "${KINSTA_HOST:-}" ]]; then
        mkdir -p ~/.ssh
        ssh-keyscan -p "${KINSTA_PORT:-22}" -H "$KINSTA_HOST" >> "$SSH_DIR/known_hosts" 2>/dev/null || true
        ssh-keyscan -p "${KINSTA_PORT:-22}" -H "$KINSTA_HOST" >> ~/.ssh/known_hosts 2>/dev/null || true
        chmod 644 "$SSH_DIR/known_hosts" 2>/dev/null || true
        log "Kinsta host added to known_hosts"
    fi
}

# =============================================================================
# 2. OAUTH CONFIGURATION
# =============================================================================
setup_oauth() {
    log "Configuring OAuth authentication..."

    AUTH_FILE="/app/config/auth.json"
    mkdir -p /app/config

    # Kinsta naming convention (primary): client_id, client_secret, redirect_uri, allowed_domain
    if [[ -n "${client_id:-}" && -n "${client_secret:-}" && -n "${redirect_uri:-}" ]]; then
        cat > "$AUTH_FILE" << EOF
{
    "client_id": "${client_id}",
    "client_secret": "${client_secret}",
    "redirect_uri": "${redirect_uri}",
    "allowed_domain": "${allowed_domain:-frontlinestrategies.co}"
}
EOF
        chmod 640 "$AUTH_FILE"
        log "OAuth configured from Kinsta env vars"
    # Legacy GOOGLE_* naming (fallback)
    elif [[ -n "${GOOGLE_CLIENT_ID:-}" && -n "${GOOGLE_CLIENT_SECRET:-}" && -n "${GOOGLE_REDIRECT_URI:-}" ]]; then
        cat > "$AUTH_FILE" << EOF
{
    "client_id": "${GOOGLE_CLIENT_ID}",
    "client_secret": "${GOOGLE_CLIENT_SECRET}",
    "redirect_uri": "${GOOGLE_REDIRECT_URI}",
    "allowed_domain": "${GOOGLE_ALLOWED_DOMAIN:-frontlinestrategies.co}"
}
EOF
        chmod 640 "$AUTH_FILE"
        log "OAuth configured from GOOGLE_* env vars"
    elif [[ -f "$AUTH_FILE" ]]; then
        log "Using existing auth.json"
    else
        # Create placeholder to prevent app errors
        echo '{"client_id":"","client_secret":"","redirect_uri":"","allowed_domain":"frontlinestrategies.co"}' > "$AUTH_FILE"
        chmod 640 "$AUTH_FILE"
        log_warn "OAuth not configured - set client_id, client_secret, redirect_uri in Kinsta dashboard"
    fi
}

# =============================================================================
# 3. PERMISSIONS FIX
# =============================================================================
fix_permissions() {
    log "Fixing directory permissions..."

    # Create required directories
    mkdir -p /app/config /app/logs /app/logs/api /app/logs/deployment /app/tmp /app/uploads /app/webhook/tasks

    # Make directories writable
    chmod -R 775 /app/config /app/logs /app/tmp /app/uploads /app/webhook 2>/dev/null || true

    # Ensure scripts are executable
    chmod +x /app/scripts/*.sh 2>/dev/null || true

    log "Permissions configured"
}

# =============================================================================
# 4. PHP CONFIGURATION
# =============================================================================
configure_php() {
    log "Configuring PHP runtime..."

    # Create custom PHP ini if Nixpacks allows
    PHP_INI_DIR="/app/.php"
    mkdir -p "$PHP_INI_DIR"

    # Write custom PHP configuration
    cat > "$PHP_INI_DIR/custom.ini" << 'EOF'
; Environment variable access
variables_order = "EGPCS"

; Error handling
display_errors = Off
log_errors = On

; Memory and execution
memory_limit = 256M
max_execution_time = 300

; Upload limits
upload_max_filesize = 64M
post_max_size = 64M

; Session
session.save_handler = files
session.save_path = "/tmp"

; Security
expose_php = Off
EOF

    # Try to set PHP_INI_SCAN_DIR if not set
    export PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR}:/app/.php"

    log "PHP configuration written to $PHP_INI_DIR/custom.ini"
}

# =============================================================================
# MAIN
# =============================================================================
main() {
    START_TIME=$(date +%s)

    log "=========================================="
    log "FLS POC Site Builder - Kinsta Startup"
    log "=========================================="
    log "Environment: ${APP_ENV:-production}"

    # Run setup functions
    setup_ssh
    setup_oauth
    fix_permissions
    configure_php

    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    log "Startup tasks complete (${DURATION}s)"
    log "=========================================="

    # Now start the default Nixpacks PHP-FPM + Nginx stack
    log "Starting PHP-FPM and Nginx..."

    # Check if Nixpacks prestart script exists
    if [[ -f "/assets/scripts/prestart.mjs" ]]; then
        log "Running Nixpacks prestart..."
        node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
    fi

    # Start PHP-FPM in background and Nginx in foreground
    php-fpm -y /assets/php-fpm.conf &
    PHP_FPM_PID=$!

    log "PHP-FPM started (PID: $PHP_FPM_PID)"

    # Handle signals for graceful shutdown
    trap "kill $PHP_FPM_PID 2>/dev/null; exit 0" SIGTERM SIGINT

    # Start Nginx in foreground (this keeps the container running)
    exec nginx -c /nginx.conf -g "daemon off;"
}

main "$@"
