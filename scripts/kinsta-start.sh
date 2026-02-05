#!/bin/bash
# =============================================================================
# FLS POC Site Builder - Kinsta Startup Script
# =============================================================================
# Runs configuration BEFORE PHP-FPM and Nginx start.
# Handles: SSH key injection, OAuth config, permissions
# =============================================================================

set -e

log() { echo "[KINSTA-START] $1"; }
log_warn() { echo "[KINSTA-START] WARN: $1"; }
log_error() { echo "[KINSTA-START] ERROR: $1"; }

# =============================================================================
# 1. SSH KEY SETUP (Production-Grade)
# =============================================================================
setup_ssh() {
    log "Setting up SSH credentials..."

    SSH_DIR="/app/.ssh"
    mkdir -p "$SSH_DIR" 2>/dev/null || true
    chmod 700 "$SSH_DIR" 2>/dev/null || true

    if [[ -n "${SSH_PRIVATE_KEY:-}" ]]; then
        log "Injecting SSH key from environment..."

        # Try base64 decode first, fallback to raw
        local key_content=""
        if key_content=$(echo "$SSH_PRIVATE_KEY" | base64 -d 2>/dev/null) && [[ -n "$key_content" ]]; then
            log "SSH key decoded from base64"
        else
            key_content="$SSH_PRIVATE_KEY"
            log "SSH key stored as raw"
        fi

        # Write key to file
        echo "$key_content" > "$SSH_DIR/id_rsa"
        chmod 600 "$SSH_DIR/id_rsa"

        # Validate key format (must contain SSH private key header)
        if ! grep -q "BEGIN.*PRIVATE KEY" "$SSH_DIR/id_rsa" 2>/dev/null; then
            log_error "SSH_PRIVATE_KEY does not contain a valid private key format"
            rm -f "$SSH_DIR/id_rsa"
            return 1
        fi

        # Generate public key if ssh-keygen is available
        if command -v ssh-keygen &>/dev/null; then
            if ssh-keygen -y -f "$SSH_DIR/id_rsa" > "$SSH_DIR/id_rsa.pub" 2>/dev/null; then
                chmod 644 "$SSH_DIR/id_rsa.pub"
                log "Public key generated"
            else
                log_warn "Could not generate public key - private key may be malformed"
            fi
        fi

        # Security: Clear the environment variable to prevent leakage to child processes
        unset SSH_PRIVATE_KEY
        log "SSH key configured (env var cleared for security)"

    elif [[ -f "$SSH_DIR/id_rsa" ]]; then
        chmod 600 "$SSH_DIR/id_rsa"
        log "Existing SSH key permissions fixed"
    else
        log_warn "No SSH key provided - deployment scripts requiring SSH will fail"
    fi

    # Add Kinsta host to known_hosts if available
    if [[ -n "${KINSTA_HOST:-}" ]] && command -v ssh-keyscan &>/dev/null; then
        local known_hosts_file="$SSH_DIR/known_hosts"

        # Scan host keys (suppress stderr noise)
        if ssh-keyscan -p "${KINSTA_PORT:-22}" -H "$KINSTA_HOST" >> "$known_hosts_file" 2>/dev/null; then
            chmod 644 "$known_hosts_file"

            # Symlink for user's home directory SSH config
            mkdir -p ~/.ssh 2>/dev/null || true
            ln -sf "$known_hosts_file" ~/.ssh/known_hosts 2>/dev/null || true

            log "Kinsta host added to known_hosts"
        else
            log_warn "Could not scan Kinsta host keys - SSH connections may prompt for verification"
        fi
    fi
}

# =============================================================================
# 2. OAUTH CONFIGURATION
# =============================================================================
setup_oauth() {
    log "Configuring OAuth authentication..."

    AUTH_FILE="/app/config/auth.json"
    mkdir -p /app/config 2>/dev/null || true

    # Kinsta naming: client_id, client_secret, redirect_uri, allowed_domain
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
        log "OAuth configured from Kinsta env vars (client_id)"
    # Legacy GOOGLE_* naming
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
    elif [[ -f "$AUTH_FILE" ]] && [[ -s "$AUTH_FILE" ]]; then
        log "Using existing auth.json"
    else
        # Create placeholder
        echo '{"client_id":"","client_secret":"","redirect_uri":"","allowed_domain":"frontlinestrategies.co"}' > "$AUTH_FILE"
        chmod 640 "$AUTH_FILE"
        log_warn "OAuth not configured - set client_id, client_secret, redirect_uri in Kinsta"
    fi
}

# =============================================================================
# 3. PERMISSIONS
# =============================================================================
fix_permissions() {
    log "Fixing directory permissions..."

    for dir in /app/config /app/logs /app/tmp /app/uploads /app/webhook; do
        mkdir -p "$dir" 2>/dev/null || true
        chmod 755 "$dir" 2>/dev/null || true
    done

    mkdir -p /app/logs/api /app/logs/deployment /app/webhook/tasks 2>/dev/null || true
    chmod 755 /app/logs/api /app/logs/deployment /app/webhook/tasks 2>/dev/null || true
    chmod +x /app/scripts/*.sh 2>/dev/null || true

    log "Permissions configured"
}

# =============================================================================
# 4. START PHP-FPM + NGINX (Nixpacks default)
# =============================================================================
start_services() {
    log "Starting PHP-FPM and Nginx..."

    # Run Nixpacks prestart if it exists (generates /nginx.conf from template)
    if [[ -f "/assets/scripts/prestart.mjs" ]]; then
        log "Running Nixpacks prestart script..."
        node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
    fi

    # CRITICAL: Create nginx log directory (Nixpacks doesn't create it)
    mkdir -p /var/log/nginx 2>/dev/null || true
    touch /var/log/nginx/error.log /var/log/nginx/access.log 2>/dev/null || true
    log "Nginx log directory created"

    # Check if php-fpm exists
    if ! command -v php-fpm &>/dev/null; then
        log_error "php-fpm not found! Nixpacks may not have detected PHP."
        log_error "Check that index.php exists in project root."
        exit 1
    fi

    # Check if nginx exists
    if ! command -v nginx &>/dev/null; then
        log_error "nginx not found!"
        exit 1
    fi

    # Start PHP-FPM
    if [[ -f "/assets/php-fpm.conf" ]]; then
        php-fpm -y /assets/php-fpm.conf &
    else
        php-fpm &
    fi
    PHP_FPM_PID=$!
    log "PHP-FPM started (PID: $PHP_FPM_PID)"

    # Handle graceful shutdown
    trap "kill $PHP_FPM_PID 2>/dev/null; exit 0" SIGTERM SIGINT

    # Start Nginx in foreground
    # NOTE: Do NOT add "-g daemon off;" - Nixpacks config already includes it
    # Adding it again causes "daemon directive is duplicate" error
    if [[ -f "/nginx.conf" ]]; then
        log "Starting Nginx with Nixpacks config..."
        exec nginx -c /nginx.conf
    else
        log "Starting Nginx with default config..."
        exec nginx -g "daemon off;"
    fi
}

# =============================================================================
# MAIN
# =============================================================================
main() {
    log "=========================================="
    log "FLS POC Site Builder - Kinsta Startup"
    log "=========================================="
    log "Environment: ${APP_ENV:-production}"
    log "PWD: $(pwd)"
    log "User: $(whoami)"

    # Run setup
    setup_ssh
    setup_oauth
    fix_permissions

    log "=========================================="
    log "Configuration complete, starting services"
    log "=========================================="

    # Start services
    start_services
}

main "$@"
