#!/bin/bash
# =============================================================================
# FLS POC Site Builder - Container Entrypoint (Kinsta-Optimized)
# =============================================================================
# Fast, idempotent startup script optimized for Kinsta environment variables.
# Target startup time: < 2 seconds
# =============================================================================

set -e

# Minimal logging (no colors in production for clean log aggregation)
log() { echo "[ENTRYPOINT] $1"; }
log_warn() { echo "[ENTRYPOINT] WARN: $1"; }

# =============================================================================
# 1. SSH KEY INJECTION (Secure Pattern)
# =============================================================================
# SSH keys are NEVER generated in containers.
# Injected via SSH_PRIVATE_KEY (base64) or mounted volume.

setup_ssh() {
    SSH_DIR="/app/.ssh"
    mkdir -p "$SSH_DIR" && chmod 700 "$SSH_DIR"

    if [[ -n "${SSH_PRIVATE_KEY:-}" ]]; then
        # Decode and write key (NEVER log the key value)
        echo "$SSH_PRIVATE_KEY" | base64 -d > "$SSH_DIR/id_rsa" 2>/dev/null
        chmod 600 "$SSH_DIR/id_rsa"
        chown www-data:www-data "$SSH_DIR/id_rsa"
        # Generate public key from private
        ssh-keygen -y -f "$SSH_DIR/id_rsa" > "$SSH_DIR/id_rsa.pub" 2>/dev/null || true
        log "SSH key configured from environment"
    elif [[ -n "${SSH_PRIVATE_KEY_FILE:-}" && -f "${SSH_PRIVATE_KEY_FILE}" ]]; then
        cp "$SSH_PRIVATE_KEY_FILE" "$SSH_DIR/id_rsa"
        chmod 600 "$SSH_DIR/id_rsa"
        chown www-data:www-data "$SSH_DIR/id_rsa"
        log "SSH key configured from mounted file"
    elif [[ -f "$SSH_DIR/id_rsa" ]]; then
        chmod 600 "$SSH_DIR/id_rsa"
        chown www-data:www-data "$SSH_DIR/id_rsa"
        log "SSH key permissions fixed"
    fi

    # Add Kinsta host to known_hosts if provided
    if [[ -n "${KINSTA_HOST:-}" ]]; then
        ssh-keyscan -p "${KINSTA_PORT:-22}" -H "$KINSTA_HOST" >> "$SSH_DIR/known_hosts" 2>/dev/null || true
        chmod 644 "$SSH_DIR/known_hosts" 2>/dev/null || true
    fi

    chown -R www-data:www-data "$SSH_DIR" 2>/dev/null || true
}

# =============================================================================
# 2. OAUTH CONFIGURATION (Kinsta Environment Variables)
# =============================================================================
# Generates auth.json from Kinsta-injected environment variables.
# Uses EXACT Kinsta naming: client_id, client_secret, redirect_uri, allowed_domain
# NO secrets are logged.

setup_oauth() {
    AUTH_FILE="/app/config/auth.json"

    # Kinsta naming convention (primary)
    if [[ -n "${client_id:-}" && -n "${client_secret:-}" && -n "${redirect_uri:-}" ]]; then
        # Write config - NEVER echo secrets
        cat > "$AUTH_FILE" << EOF
{
    "client_id": "${client_id}",
    "client_secret": "${client_secret}",
    "redirect_uri": "${redirect_uri}",
    "allowed_domain": "${allowed_domain:-frontlinestrategies.co}"
}
EOF
        chmod 640 "$AUTH_FILE"
        chown www-data:www-data "$AUTH_FILE"
        log "OAuth configured (Kinsta vars)"
    # Legacy GOOGLE_* naming (fallback for non-Kinsta environments)
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
        chown www-data:www-data "$AUTH_FILE"
        log "OAuth configured (GOOGLE_* vars)"
    elif [[ -f "$AUTH_FILE" ]]; then
        log "Using existing auth.json"
    else
        # Create minimal placeholder to prevent app errors
        echo '{"client_id":"","client_secret":"","redirect_uri":"","allowed_domain":"frontlinestrategies.co"}' > "$AUTH_FILE"
        chmod 640 "$AUTH_FILE"
        chown www-data:www-data "$AUTH_FILE" 2>/dev/null || true
        log_warn "OAuth not configured - set client_id, client_secret, redirect_uri"
    fi
}

# =============================================================================
# 3. PERMISSIONS (For Mounted Volumes)
# =============================================================================
# Fast permission fix for directories that may be overwritten by volume mounts.

fix_permissions() {
    # Only fix if directories exist (fast check)
    for dir in /app/config /app/logs /app/tmp /app/uploads /app/webhook/tasks; do
        [[ -d "$dir" ]] && chown -R www-data:www-data "$dir" 2>/dev/null || true
    done
    # Ensure scripts are executable
    chmod +x /app/scripts/*.sh 2>/dev/null || true
}

# =============================================================================
# 4. HEALTH CHECK ENDPOINT
# =============================================================================

setup_health() {
    cat > /app/health.php << 'HEALTHEOF'
<?php
header('Content-Type: application/json');
echo json_encode(['status'=>'healthy','ts'=>date('c'),'php'=>PHP_VERSION]);
HEALTHEOF
    chown www-data:www-data /app/health.php 2>/dev/null || true
}

# =============================================================================
# MAIN EXECUTION
# =============================================================================

main() {
    START_TIME=$(date +%s%3N)

    log "Starting FLS POC Site Builder"
    log "Environment: ${APP_ENV:-production}"

    # Run setup functions (order matters)
    setup_ssh
    setup_oauth
    fix_permissions
    setup_health

    END_TIME=$(date +%s%3N)
    DURATION=$((END_TIME - START_TIME))
    log "Startup complete (${DURATION}ms)"

    # Signal-safe handoff to main process
    exec "$@"
}

main "$@"
