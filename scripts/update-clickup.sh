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

# Load API utilities for proper logging
if [[ -f "$SCRIPT_DIR/api.sh" ]]; then
    source "$SCRIPT_DIR/api.sh"
else
    log_error "API utilities not found: $SCRIPT_DIR/api.sh"
    exit 1
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
CONFIG_FILE="$PROJECT_ROOT/config/config.json"

if [[ ! -f "$SITE_CONFIG" ]]; then
    log_error "Site configuration file not found: $SITE_CONFIG"
    exit 1
fi

if [[ ! -f "$CONFIG_FILE" ]]; then
    log_error "Main configuration file not found: $CONFIG_FILE"
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
    # Check if security is enabled first
    SECURITY_ENABLED=$(jq -r '.security.enabled // false' "$CONFIG_FILE")
    HIDE_LOGIN_ENABLED=$(jq -r '.security.login_protection.hide_login_page // false' "$CONFIG_FILE")
    CUSTOM_LOGIN_SLUG=$(jq -r '.security.login_protection.custom_login_url // empty' "$CONFIG_FILE")
    
    if [[ "$SECURITY_ENABLED" == "true" && "$HIDE_LOGIN_ENABLED" == "true" && -n "$CUSTOM_LOGIN_SLUG" && "$CUSTOM_LOGIN_SLUG" != "null" ]]; then
        # Use custom login URL (security enabled + custom login configured)
        ADMIN_URL="${SITE_URL}/${CUSTOM_LOGIN_SLUG}"
        log_info "Using custom login URL: ${CUSTOM_LOGIN_SLUG}"
    else
        # Use default wp-admin
        ADMIN_URL="${SITE_URL}/wp-admin"
        log_info "Using default login URL: wp-admin"
    fi
else
    ADMIN_URL=""
fi

# Get deployment timestamp
DEPLOYMENT_DATE=$(date '+%Y-%m-%d %H:%M:%S %Z')

log_info "Site URL: ${SITE_URL:-'N/A'}"
log_info "Admin URL: ${ADMIN_URL:-'N/A'}"
log_info "Admin User: ${ADMIN_USER:-'N/A'}"

# Prepare comment text with deployment information
COMMENT_TEXT="━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
COMMENT_TEXT+="✅ **DEPLOYMENT COMPLETED**\n"
COMMENT_TEXT+="━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
COMMENT_TEXT+="**Deployment Date:** ${DEPLOYMENT_DATE}\n\n"

if [[ -n "$SITE_URL" ]]; then
    COMMENT_TEXT+="**🌐 Site URL:** [${SITE_URL}](https://${SITE_URL})\n"
fi

if [[ -n "$ADMIN_URL" ]]; then
    COMMENT_TEXT+="**🔐 Admin URL:** [${ADMIN_URL}](https://${ADMIN_URL})\n"
fi

if [[ -n "$ADMIN_USER" || -n "$ADMIN_PASS" ]]; then
    COMMENT_TEXT+="\n**Login Credentials:**\n"
    if [[ -n "$ADMIN_USER" ]]; then
        COMMENT_TEXT+="- **Username:** \`${ADMIN_USER}\`\n"
    fi
    if [[ -n "$ADMIN_PASS" ]]; then
        COMMENT_TEXT+="- **Password:** \`${ADMIN_PASS}\`\n"
    fi
fi

COMMENT_TEXT+="\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"

# Prepare JSON payload for comment
COMMENT_PAYLOAD=$(jq -n \
    --arg text "$COMMENT_TEXT" \
    '{
        comment_text: $text,
        notify_all: true
    }')

log_info "Posting deployment comment to ClickUp task..."

# Post comment to task using api_request (with automatic logging)
COMMENT_RESPONSE=$(api_request "clickup" "task/${TASK_ID}/comment" "POST" "$COMMENT_PAYLOAD")
COMMENT_EXIT_CODE=$?

if [[ $COMMENT_EXIT_CODE -ne 0 ]]; then
    log_error "Failed to post comment to ClickUp task"
    exit 1
fi

# Check if response is valid JSON
if ! echo "$COMMENT_RESPONSE" | jq empty 2>/dev/null; then
    log_error "Invalid JSON response from ClickUp API when posting comment"
    log_error "Response: $COMMENT_RESPONSE"
    exit 1
fi

# Check for ClickUp API error in response
CLICKUP_ERROR=$(echo "$COMMENT_RESPONSE" | jq -r '.err // .error // empty')
if [[ -n "$CLICKUP_ERROR" && "$CLICKUP_ERROR" != "null" ]]; then
    log_error "ClickUp API error: $CLICKUP_ERROR"
    exit 1
fi

log_success "✓ Comment posted to ClickUp task successfully"

# Update Website URL custom field if site URL is available
if [[ -n "$SITE_URL" ]]; then
    log_info "Updating Website URL custom field..."
    
    # First, get task details to find custom field ID
    TASK_DETAILS=$(api_request "clickup" "task/${TASK_ID}" "GET")
    TASK_EXIT_CODE=$?
    
    if [[ $TASK_EXIT_CODE -ne 0 ]]; then
        log_warning "Failed to fetch task details for custom field update"
    else
        # Find Website URL custom field ID
        WEBSITE_FIELD_ID=$(echo "$TASK_DETAILS" | jq -r '.custom_fields[] | select(.name == "Website URL") | .id // empty')
        
        if [[ -n "$WEBSITE_FIELD_ID" && "$WEBSITE_FIELD_ID" != "null" ]]; then
            log_info "Found Website URL custom field: $WEBSITE_FIELD_ID"
            
            # Prepare update payload
            UPDATE_PAYLOAD=$(jq -n \
                --arg field_id "$WEBSITE_FIELD_ID" \
                --arg value "$SITE_URL" \
                '{
                    custom_fields: [
                        {
                            id: $field_id,
                            value: $value
                        }
                    ]
                }')
            
            # Update custom field using api_request (with automatic logging)
            UPDATE_RESPONSE=$(api_request "clickup" "task/${TASK_ID}" "PUT" "$UPDATE_PAYLOAD")
            UPDATE_EXIT_CODE=$?
            
            if [[ $UPDATE_EXIT_CODE -eq 0 ]]; then
                log_success "✓ Website URL custom field updated successfully"
            else
                log_warning "Failed to update Website URL custom field (non-critical)"
            fi
        else
            log_info "Website URL custom field not found in task (skipping)"
        fi
    fi
fi

log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_success "ClickUp Task Update Complete"
log_success "Task ID: $TASK_ID"
log_success "Site URL: $SITE_URL"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
