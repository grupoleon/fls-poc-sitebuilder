#!/bin/bash
# Update ClickUp Task with deployment details

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Load logger if available
if [[ -f "$SCRIPT_DIR/logger.sh" ]]; then
    source "$SCRIPT_DIR/logger.sh"
else
    log_info() { echo "[INFO] $1"; }
    log_success() { echo "[SUCCESS] $1"; }
    log_error() { echo "[ERROR] $1"; }
    log_warning() { echo "[WARNING] $1"; }
fi

log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_info "Updating ClickUp Task with Deployment Details"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Read deployment status to get ClickUp task ID
STATUS_FILE="$PROJECT_ROOT/tmp/deployment_status.json"

if [[ ! -f "$STATUS_FILE" ]]; then
    log_error "Deployment status file not found: $STATUS_FILE"
    exit 1
fi

# Extract ClickUp task ID from deployment status
TASK_ID=$(jq -r '.clickup_task_id // empty' "$STATUS_FILE" 2>/dev/null)

if [[ -z "$TASK_ID" || "$TASK_ID" == "null" ]]; then
    log_warning "No ClickUp task ID found in deployment status. Skipping task update."
    exit 0
fi

log_info "Found ClickUp Task ID: $TASK_ID"

# Read site configuration for URLs and credentials
SITE_CONFIG="$PROJECT_ROOT/config/site.json"

if [[ ! -f "$SITE_CONFIG" ]]; then
    log_error "Site configuration file not found: $SITE_CONFIG"
    exit 1
fi

# Extract site details
SITE_TITLE=$(jq -r '.site_title // empty' "$SITE_CONFIG")
DISPLAY_NAME=$(jq -r '.display_name // empty' "$SITE_CONFIG")
ADMIN_USER=$(jq -r '.admin_user // "admin"' "$SITE_CONFIG")
ADMIN_PASS=$(jq -r '.admin_pass // empty' "$SITE_CONFIG")

# Read credentials from credentials file if available
CREDS_FILE="$PROJECT_ROOT/tmp/credentials.json"
if [[ -f "$CREDS_FILE" ]]; then
    SITE_URL=$(jq -r '.site_url // empty' "$CREDS_FILE")
    
    # If site_url is not set, try alternative fields
    if [[ -z "$SITE_URL" || "$SITE_URL" == "null" ]]; then
        SITE_URL=$(jq -r '.ssh_connection.host // empty' "$CREDS_FILE")
    fi
else
    # Fallback: try to construct from display_name
    if [[ -n "$DISPLAY_NAME" ]]; then
        SITE_URL="${DISPLAY_NAME}.kinsta.cloud"
    else
        SITE_URL="${SITE_TITLE}.kinsta.cloud"
    fi
fi

# Construct admin URL
if [[ -n "$SITE_URL" ]]; then
    ADMIN_URL="${SITE_URL}/wp-admin"
else
    ADMIN_URL=""
fi

# Get deployment timestamp
DEPLOYMENT_DATE=$(date '+%Y-%m-%d %H:%M:%S %Z')

log_info "Site URL: ${SITE_URL:-'N/A'}"
log_info "Admin URL: ${ADMIN_URL:-'N/A'}"
log_info "Admin User: ${ADMIN_USER:-'N/A'}"

# Prepare JSON payload for API call
JSON_PAYLOAD=$(jq -n \
    --arg task_id "$TASK_ID" \
    --arg site_url "${SITE_URL:-}" \
    --arg admin_url "${ADMIN_URL:-}" \
    --arg admin_user "${ADMIN_USER:-}" \
    --arg admin_pass "${ADMIN_PASS:-}" \
    --arg deployment_date "$DEPLOYMENT_DATE" \
    '{
        task_id: $task_id,
        site_url: $site_url,
        admin_url: $admin_url,
        admin_user: $admin_user,
        admin_pass: $admin_pass,
        deployment_date: $deployment_date
    }')

log_info "Sending update request to ClickUp API..."

# Call the PHP API endpoint to update ClickUp
RESPONSE=$(curl -s -X POST \
    "https://$(hostname)/php/api/update-clickup-task.php" \
    -H "Content-Type: application/json" \
    -d "$JSON_PAYLOAD" \
    2>&1)

CURL_EXIT_CODE=$?

if [[ $CURL_EXIT_CODE -ne 0 ]]; then
    log_error "Failed to send update request (curl exit code: $CURL_EXIT_CODE)"
    log_error "Response: $RESPONSE"
    exit 1
fi

# Check if response is valid JSON
if ! echo "$RESPONSE" | jq empty 2>/dev/null; then
    log_error "Invalid JSON response from API"
    log_error "Response: $RESPONSE"
    exit 1
fi

# Check success status
SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false')

if [[ "$SUCCESS" == "true" ]]; then
    log_success "✓ ClickUp task updated successfully!"
    log_success "Task ID: $TASK_ID"
    log_success "Site URL: $SITE_URL"
else
    ERROR_MSG=$(echo "$RESPONSE" | jq -r '.message // "Unknown error"')
    log_error "Failed to update ClickUp task: $ERROR_MSG"
    exit 1
fi

log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_success "ClickUp Task Update Complete"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
