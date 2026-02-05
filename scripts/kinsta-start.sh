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

    # Determine runtime user for ownership (same logic as fix_permissions)
    local RUNTIME_USER="nobody"
    local RUNTIME_GROUP="nogroup"
    if [[ -f "/assets/php-fpm.conf" ]]; then
        local detected_user=$(grep -E "^user\s*=" /assets/php-fpm.conf 2>/dev/null | awk -F= '{print $2}' | tr -d ' ')
        local detected_group=$(grep -E "^group\s*=" /assets/php-fpm.conf 2>/dev/null | awk -F= '{print $2}' | tr -d ' ')
        [[ -n "$detected_user" ]] && RUNTIME_USER="$detected_user"
        [[ -n "$detected_group" ]] && RUNTIME_GROUP="$detected_group"
    fi

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
        chown "${RUNTIME_USER}:${RUNTIME_GROUP}" "$AUTH_FILE" 2>/dev/null || true
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
        chown "${RUNTIME_USER}:${RUNTIME_GROUP}" "$AUTH_FILE" 2>/dev/null || true
        log "OAuth configured from GOOGLE_* env vars"
    elif [[ -f "$AUTH_FILE" ]] && [[ -s "$AUTH_FILE" ]]; then
        # Fix ownership on existing file
        chown "${RUNTIME_USER}:${RUNTIME_GROUP}" "$AUTH_FILE" 2>/dev/null || true
        log "Using existing auth.json (ownership fixed)"
    else
        # Create placeholder
        echo '{"client_id":"","client_secret":"","redirect_uri":"","allowed_domain":"frontlinestrategies.co"}' > "$AUTH_FILE"
        chmod 640 "$AUTH_FILE"
        chown "${RUNTIME_USER}:${RUNTIME_GROUP}" "$AUTH_FILE" 2>/dev/null || true
        log_warn "OAuth not configured - set client_id, client_secret, redirect_uri in Kinsta"
    fi
}

# =============================================================================
# 3. PERMISSIONS & OWNERSHIP
# =============================================================================
fix_permissions() {
    log "===== Permission Check ====="

    # Detect PHP-FPM runtime user (Nixpacks uses nobody:nogroup)
    local RUNTIME_USER="nobody"
    local RUNTIME_GROUP="nogroup"

    # Try to detect from php-fpm config if available
    if [[ -f "/assets/php-fpm.conf" ]]; then
        local detected_user=$(grep -E "^user\s*=" /assets/php-fpm.conf 2>/dev/null | awk -F= '{print $2}' | tr -d ' ')
        local detected_group=$(grep -E "^group\s*=" /assets/php-fpm.conf 2>/dev/null | awk -F= '{print $2}' | tr -d ' ')
        [[ -n "$detected_user" ]] && RUNTIME_USER="$detected_user"
        [[ -n "$detected_group" ]] && RUNTIME_GROUP="$detected_group"
    fi

    log "Runtime user detected: ${RUNTIME_USER}:${RUNTIME_GROUP}"

    # Create directories with correct ownership
    for dir in /app/config /app/logs /app/tmp /app/uploads /app/webhook; do
        mkdir -p "$dir" 2>/dev/null || true
        chmod 755 "$dir" 2>/dev/null || true
        chown "${RUNTIME_USER}:${RUNTIME_GROUP}" "$dir" 2>/dev/null || true
    done

    mkdir -p /app/logs/api /app/logs/deployment /app/webhook/tasks 2>/dev/null || true
    chmod 755 /app/logs/api /app/logs/deployment /app/webhook/tasks 2>/dev/null || true
    chown -R "${RUNTIME_USER}:${RUNTIME_GROUP}" /app/logs 2>/dev/null || true
    chown -R "${RUNTIME_USER}:${RUNTIME_GROUP}" /app/webhook 2>/dev/null || true

    # Fix auth.json ownership (CRITICAL for OAuth)
    if [[ -f "/app/config/auth.json" ]]; then
        chown "${RUNTIME_USER}:${RUNTIME_GROUP}" /app/config/auth.json 2>/dev/null || true
        chmod 640 /app/config/auth.json 2>/dev/null || true
    fi

    # Fix SSH directory (keep restrictive but readable by runtime user)
    if [[ -d "/app/.ssh" ]]; then
        chown -R "${RUNTIME_USER}:${RUNTIME_GROUP}" /app/.ssh 2>/dev/null || true
        chmod 700 /app/.ssh 2>/dev/null || true
        [[ -f "/app/.ssh/id_rsa" ]] && chmod 600 /app/.ssh/id_rsa 2>/dev/null || true
        [[ -f "/app/.ssh/id_rsa.pub" ]] && chmod 644 /app/.ssh/id_rsa.pub 2>/dev/null || true
    fi

    chmod +x /app/scripts/*.sh 2>/dev/null || true

    log "Permissions configured"
}

# =============================================================================
# 3b. VALIDATE AUTH.JSON ACCESS
# =============================================================================
validate_auth() {
    log "===== Auth Validation ====="

    AUTH_FILE="/app/config/auth.json"

    if [[ ! -f "$AUTH_FILE" ]]; then
        log_warn "auth.json does not exist"
        return 1
    fi

    # Get file info
    local owner=$(stat -c '%U:%G' "$AUTH_FILE" 2>/dev/null || stat -f '%Su:%Sg' "$AUTH_FILE" 2>/dev/null)
    local perms=$(stat -c '%a' "$AUTH_FILE" 2>/dev/null || stat -f '%Lp' "$AUTH_FILE" 2>/dev/null)
    local size=$(stat -c '%s' "$AUTH_FILE" 2>/dev/null || stat -f '%z' "$AUTH_FILE" 2>/dev/null)

    log "auth.json owner: $owner"
    log "auth.json perms: $perms"
    log "auth.json size: ${size} bytes"

    # Check if file has content (not just empty JSON)
    if [[ "$size" -lt 50 ]]; then
        log_warn "auth.json appears to be empty or placeholder"
    fi

    # Verify OAuth env vars are available (without printing secrets)
    if [[ -n "${client_id:-}" ]]; then
        log "client_id: SET (${#client_id} chars)"
    else
        log_warn "client_id: NOT SET"
    fi

    if [[ -n "${client_secret:-}" ]]; then
        log "client_secret: SET (${#client_secret} chars)"
    else
        log_warn "client_secret: NOT SET"
    fi

    if [[ -n "${redirect_uri:-}" ]]; then
        log "redirect_uri: ${redirect_uri}"
    else
        log_warn "redirect_uri: NOT SET"
    fi

    # Test if file is readable by checking JSON validity
    if command -v jq &>/dev/null; then
        if jq -e '.client_id' "$AUTH_FILE" &>/dev/null; then
            local has_client_id=$(jq -r '.client_id // empty' "$AUTH_FILE" 2>/dev/null)
            if [[ -n "$has_client_id" && "$has_client_id" != "" ]]; then
                log "Readable by PHP: YES (client_id present)"
            else
                log_warn "Readable by PHP: YES but client_id is empty"
            fi
        else
            log_warn "Readable by PHP: NO or invalid JSON"
        fi
    else
        # Fallback without jq
        if grep -q "client_id" "$AUTH_FILE" 2>/dev/null; then
            log "Readable by PHP: LIKELY (contains client_id key)"
        else
            log_warn "Readable by PHP: UNKNOWN"
        fi
    fi

    log "============================="
}

# =============================================================================
# 3c. RUN DATABASE MIGRATIONS
# =============================================================================
run_migrations() {
    log "===== Database Migrations ====="
    
    # Support both DB_PASSWORD and DB_PASS (Kinsta compatibility)
    DB_PASSWORD="${DB_PASSWORD:-${DB_PASS:-}}"
    
    # Set default port if not provided
    DB_PORT="${DB_PORT:-3306}"
    
    # Check if database credentials are available
    if [[ -z "${DB_HOST:-}" ]] || [[ -z "${DB_USER:-}" ]] || [[ -z "${DB_PASSWORD:-}" ]]; then
        log_warn "Database credentials incomplete - skipping migrations"
        log_warn "Required environment variables:"
        log_warn "  - DB_HOST: ${DB_HOST:+SET}"
        log_warn "  - DB_PORT: ${DB_PORT:-3306 (default)}"
        log_warn "  - DB_USER: ${DB_USER:+SET}"
        log_warn "  - DB_PASSWORD or DB_PASS: ${DB_PASSWORD:+SET}"
        log_warn "  - DB_NAME: ${DB_NAME:-frontline_poc (default)}"
        log_warn "Database logging will not be available. Set these in Kinsta environment variables."
        return 0
    fi
    
    log "Database credentials validated (DB_HOST=$DB_HOST, DB_PORT=$DB_PORT, DB_USER=$DB_USER, DB_PASSWORD=***)"
    
    # Export for PHP scripts
    export DB_PASSWORD
    export DB_PORT
    
    # Check if migration script exists
    if [[ ! -f "/app/php/run-migrations.php" ]]; then
        log_warn "Migration script not found: /app/php/run-migrations.php"
        return 0
    fi
    
    # Check if php-cli is available
    if ! command -v php &>/dev/null; then
        log_warn "PHP CLI not found - cannot run migrations"
        return 0
    fi
    
    # Run migrations
    log "Executing database migrations..."
    if php /app/php/run-migrations.php 2>&1 | while IFS= read -r line; do
        log "  $line"
    done; then
        log "Database migrations completed successfully"
    else
        log_warn "Database migrations completed with warnings (see logs above)"
        log_warn "Application will continue but database logging may not work fully"
    fi
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

    # Run setup (order matters: oauth creates file, permissions fixes ownership)
    setup_ssh
    setup_oauth
    fix_permissions
    validate_auth
    run_migrations

    log "=========================================="
    log "Configuration complete, starting services"
    log "=========================================="

    # Start services
    start_services
}

main "$@"
