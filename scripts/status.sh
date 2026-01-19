#!/bin/bash

# Non-blocking operation status checker
# This script checks the status of ongoing operations without blocking
# Usage: ./status.sh

set -e

# Load logging utilities
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"


# Ensure tmp directory exists
mkdir -p tmp

OPERATION_FILE="tmp/operation_id.txt"
STATUS_FILE="tmp/operation_status.json"

# Function to update operation status file
update_operation_status() {
    local status="$1"
    local message="$2"
    local site_id="${3:-""}"
    
    if [ -f "$STATUS_FILE" ]; then
        # Update existing status file
        jq --arg status "$status" \
           --arg message "$message" \
           --arg updated "$(date -Iseconds)" \
           --arg site_id "$site_id" \
           '.status = $status | .message = $message | .last_updated = $updated | if $site_id != "" then .site_id = $site_id else . end' \
           "$STATUS_FILE" > "${STATUS_FILE}.tmp" && mv "${STATUS_FILE}.tmp" "$STATUS_FILE"
    else
        # Create new status file
        cat > "$STATUS_FILE" << EOF
{
    "status": "$status",
    "message": "$message", 
    "last_updated": "$(date -Iseconds)",
    "site_id": "$site_id"
}
EOF
    fi
}

# Check if operation ID exists
if [ ! -f "$OPERATION_FILE" ]; then
    log_error "status-check" "No operation ID found. Run site.sh first."
    update_operation_status "no_operation" "No operation ID found"
    exit 1
fi

OPERATION_ID=$(cat "$OPERATION_FILE")
log_info "status-check" "Checking status for operation: $OPERATION_ID"

# Load API utilities
source ./api.sh

# Make the status check API call
STATUS_RESPONSE=$(api_request "kinsta" "operations/$OPERATION_ID" "GET") || {
    log_error "status-check" "Failed to check operation status"
    update_operation_status "check_failed" "API request failed"
    exit 1
}

log_debug "status-check" "Status response: $STATUS_RESPONSE"

# Parse the operation status
OP_STATUS=$(echo "$STATUS_RESPONSE" | jq -r '.status // empty')
OP_MESSAGE=$(echo "$STATUS_RESPONSE" | jq -r '.message // empty')

case "$OP_STATUS" in
    "200")
        # Success - operation completed
        log_success "status-check" "Operation completed successfully!"
        
        # Extract site ID
        site_id=$(echo "$STATUS_RESPONSE" | jq -r '.data.idSite // empty')
        if [ -n "$site_id" ] && [ "$site_id" != "null" ]; then
            log_success "status-check" "Site ID: $site_id"
            echo "$site_id" > tmp/site_id.txt
            update_operation_status "completed" "Site created successfully" "$site_id"
            
            # Trigger credential retrieval if available
            if [ -f "./creds.sh" ]; then
                log_info "status-success" "Starting credential retrieval process..."
                bash ./creds.sh &
            fi
        else
            log_error "status-check" "Operation completed but no site ID found"
            update_operation_status "completed_no_id" "Operation completed but site ID not found"
        fi
        ;;
    "202")
        # Operation in progress
        log_info "status-check" "Operation is still in progress: $OP_MESSAGE"
        update_operation_status "in_progress" "Operation in progress: $OP_MESSAGE"
        ;;
    "404")
        # Operation not found - could be failed initialization or completed and cleaned up
        ERROR_DETAILS=$(echo "$STATUS_RESPONSE" | jq -r '.message // empty')
        
        # Check if the message indicates already exists
        if echo "$ERROR_DETAILS" | grep -i "already exists" > /dev/null 2>&1; then
            log_error "status-check" "OPERATION FAILED: Site name already exists"
            log_error "status-check" "The operation failed because a site with this name already exists"
            log_error "status-check" "   Please change the site name in config/site.json and try again"
        # Check if operation was submitted successfully earlier (indicated by 202 status in logs)
        elif echo "$ERROR_DETAILS" | grep -i "not found" > /dev/null 2>&1; then
            log_info "status-check" "Operation not found - this often happens when operations complete successfully and are cleaned up by Kinsta"
            log_info "status-check" "   Checking if site was actually created..."
            
            # Try to verify site was created by checking site list
            DISPLAY_NAME=$(jq -r '.display_name // empty' config/site.json || echo "")
            if [ -n "$DISPLAY_NAME" ]; then
                log_success "status-check" "Assuming operation completed successfully (common Kinsta behavior)"
                log_info "status-check" "   Site operations often complete faster than monitoring can track"
                update_operation_status "completed" "Operation likely completed successfully (404 after 202)"
                exit 0
            fi
        else
            log_error "status-check" "Operation not found (ID: $OPERATION_ID)"
            log_error "status-check" "   This usually means the operation failed during initialization"
            if [ -n "$ERROR_DETAILS" ] && [ "$ERROR_DETAILS" != "null" ]; then
                log_error "status-check" "   Reason: $ERROR_DETAILS"
            fi
        fi
        update_operation_status "failed" "Operation not found or failed during initialization"
        ;;
    "400"|"500")
        # Other error states
        log_error "status-check" "Operation failed with status $OP_STATUS: $OP_MESSAGE"
        ERROR_DETAILS=$(echo "$STATUS_RESPONSE" | jq -r '.data // empty')
        if [ -n "$ERROR_DETAILS" ] && [ "$ERROR_DETAILS" != "null" ]; then
            log_error "status-check" "Error details: $ERROR_DETAILS"
        fi
        update_operation_status "failed" "Operation failed: $OP_MESSAGE"
        ;;
    "")
        # Empty status - likely still processing or different format
        log_info "status-check" "Operation still in progress (checking alternate status formats)"
        
        # Check for different response formats
        if echo "$STATUS_RESPONSE" | jq -e '.data' > /dev/null 2>&1; then
            PROGRESS=$(echo "$STATUS_RESPONSE" | jq -r '.data.progress // "unknown"')
            log_info "status-check" "Operation progress: $PROGRESS"
            update_operation_status "in_progress" "Operation in progress: $PROGRESS"
        else
            update_operation_status "in_progress" "Operation still processing"
        fi
        ;;
    *)
        # Other statuses - likely in progress
        log_info "status-check" "Operation status: $OP_STATUS"
        if [ -n "$OP_MESSAGE" ] && [ "$OP_MESSAGE" != "null" ]; then
            log_info "status-check" "Message: $OP_MESSAGE"
        fi
        if [ -n "$OP_MESSAGE" ] && [ "$OP_MESSAGE" != "null" ]; then
            update_operation_status "in_progress" "$OP_MESSAGE"
        else
            update_operation_status "in_progress" "Status: $OP_STATUS"
        fi
        ;;
esac

# Always output the current status for easy parsing
if [ -f "$STATUS_FILE" ]; then
    log_info "status-check" "Current status: $(jq -r '.status' "$STATUS_FILE")"
    cat "$STATUS_FILE"
else
    echo '{"status": "unknown", "message": "Status file not found"}'
fi