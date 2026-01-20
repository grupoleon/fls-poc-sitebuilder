#!/bin/bash

set -euo pipefail

# Load logging utilities
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"

# Install required extensions if not already installed
extensions=("jq" "curl" "openssl")
for ext in "${extensions[@]}"; do
    if ! command -v "$ext" &> /dev/null; then
        log_info "Installing missing extension: $ext"
        apt update && apt install -y "$ext"
        log_success "Installed extension: $ext"
    else
        log_info "$ext is already installed"
    fi
done

log_step_start "Site Creation"

# Clean up from previous runs
if [ -f "$ROOT_DIR/tmp/site_id.txt" ]; then
    rm -f "$ROOT_DIR/tmp/site_id.txt"
    log_info "Cleaned up previous site data"
fi

if [ -f "$ROOT_DIR/tmp/operation_id.txt" ]; then
    rm -f "$ROOT_DIR/tmp/operation_id.txt"
    log_info "Cleaned up previous operation data"
fi

# Get absolute path to config file
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
config_file="$ROOT_DIR/config/site.json"

if [ ! -f "$config_file" ]; then
    log_error "Configuration file not found: $config_file"
    log_step_failed "Site Creation" "Missing configuration file"
    exit 1
fi

log_info "Reading site configuration..."

JSON_DATA=$(cat "$config_file")

# Validate critical configuration fields
ADMIN_PASSWORD=$(echo "$JSON_DATA" | jq -r '.admin_password')
DISPLAY_NAME=$(echo "$JSON_DATA" | jq -r '.display_name')

# Auto-generate password if missing or empty
if [ -z "$ADMIN_PASSWORD" ] || [ "$ADMIN_PASSWORD" = "" ] || [ "$ADMIN_PASSWORD" = "null" ]; then
    log_info "Auto-generating secure admin password..."
    
    # Generate a strong 16-character password
    ADMIN_PASSWORD=$(openssl rand -base64 12 | tr -d "=+/" | cut -c1-16)
    
    # Update the JSON with the new password
    JSON_DATA=$(echo "$JSON_DATA" | jq --arg pwd "$ADMIN_PASSWORD" '.admin_password = $pwd')
    
    # Save updated config back to file
    echo "$JSON_DATA" > "$config_file"
    
    log_success "Generated admin password: $ADMIN_PASSWORD"
    log_info "Password saved to $config_file"
fi

if [ -z "$DISPLAY_NAME" ] || [ "$DISPLAY_NAME" = "" ] || [ "$DISPLAY_NAME" = "null" ]; then
    log_error "Site name is required. Please set display_name in $config_file"
    log_step_failed "Site Creation" "Missing site name"
    exit 1
fi

# Update JSON with unique display name
JSON_DATA=$(echo "$JSON_DATA" | jq --arg name "$DISPLAY_NAME" '.display_name = $name')


# Save updated config back to file
echo "$JSON_DATA" > "$config_file"

log_info "Configuration validated successfully"
log_info "Using unique site name: $DISPLAY_NAME"
source "$(dirname "${BASH_SOURCE[0]}")/api.sh"

# Add the discovered company ID to the JSON data
if [ -n "$COMPANY_ID" ] && [ "$COMPANY_ID" != "null" ]; then
    ESCAPED_JSON=$(echo "$JSON_DATA" | jq --arg company_id "$COMPANY_ID" '. + {company: $company_id}' | jq -c .)
    log_info "data-preparation" "Using discovered company ID: $COMPANY_ID"
else
    log_error "Company ID is not set. Please set the COMPANY_ID environment variable or ensure it's in config/site.json" "data-preparation"
    end_deployment_session "FAILED"
   exit 1
fi

log_debug "data-preparation" "JSON data being sent to API: $ESCAPED_JSON"

# Ensure tmp directory exists and remove site_id.txt if it exists from previous runs
mkdir -p "$ROOT_DIR/tmp"
if [ -f "$ROOT_DIR/tmp/site_id.txt" ]; then
    rm -f "$ROOT_DIR/tmp/site_id.txt"
    log_info "initialization" "Removed existing tmp/site_id.txt from previous runs"
fi


# Check if site already exists by querying company sites
log_info "site-check" "Checking if site '$DISPLAY_NAME' already exists in company $COMPANY_ID"

SITE_CHECK_RESPONSE=$(api_request "kinsta" "sites?company=$COMPANY_ID" "GET" "") || {
    log_error "Failed to retrieve site list for company: $COMPANY_ID" "site-check"
    log_error "API Error: $SITE_CHECK_RESPONSE" "site-check"
    log_info "site-check" "Cannot verify existing sites, proceeding with creation attempt..."
    SITE_CHECK_RESPONSE=""
}

log_info "site-check" "Site list retrieved successfully for company $COMPANY_ID"

# Check if we got a valid response and if the site exists
if [[ -n "$SITE_CHECK_RESPONSE" ]] && echo "$SITE_CHECK_RESPONSE" | jq -e 'has("company") and .company.sites' > /dev/null 2>&1; then
    # Count total sites
    TOTAL_SITES=$(echo "$SITE_CHECK_RESPONSE" | jq -r '.company.sites | length')
    log_info "site-check" "Found $TOTAL_SITES total sites in company"
    
    # Check if our specific site exists
    EXISTING_SITE=$(echo "$SITE_CHECK_RESPONSE" | jq -r ".company.sites[] | select(.display_name == \"$DISPLAY_NAME\") | .id" 2>/dev/null)
    
    if [[ -n "$EXISTING_SITE" && "$EXISTING_SITE" != "null" ]]; then
        log_success "site-check" "Site '$DISPLAY_NAME' already exists with ID: $EXISTING_SITE"
        echo "$EXISTING_SITE" > "$ROOT_DIR/tmp/site_id.txt"
        log_info "site-check" "Saved existing site ID to $ROOT_DIR/tmp/site_id.txt"
        log_success "deployment" "Using existing site, skipping creation"
        end_deployment_session "SUCCESS"
        exit 0
    else
        log_info "site-check" "Site '$DISPLAY_NAME' does not exist in company"
        log_info "site-check" "Proceeding with new site creation"
    fi
else
    log_warning "site-check" "Could not parse site list response or no sites found"
    if [[ -n "$SITE_CHECK_RESPONSE" ]]; then
        log_warning "site-check" "Response structure: $(echo "$SITE_CHECK_RESPONSE" | jq -c 'keys' 2>/dev/null || echo 'Invalid JSON')"
    fi
    log_info "site-check" "Proceeding with creation attempt..."
fi

log_info "site-creation" "Submitting site creation request to Kinsta API"
log_info "site-creation" "Site data: Display Name='$DISPLAY_NAME', Company='$COMPANY_ID'"

response=$(api_request "kinsta" "sites" "POST" "$ESCAPED_JSON") || {
    log_error "API request failed completely" "site-creation"
    log_error "Error response: $response" "site-creation"
    log_error "Request data was: $ESCAPED_JSON" "site-creation"
    end_deployment_session "FAILED"
    exit 1
}

log_info "site-creation" "Site creation response received"
log_debug "site-creation" "Raw response: $response"

log_info "site-creation" "Processing API response"

# Clean the response and validate JSON - handle invalid JSON gracefully
if clean_response=$(echo "$response" | jq -c '.' 2>/dev/null); then
    log_info "site-creation" "Received valid JSON response"
    log_info "site-creation" "Response preview: $(echo "$clean_response" | jq -c '. | {status: .status, message: .message, operation_id: .operation_id}' 2>/dev/null || echo 'Could not parse preview')"
else
    # Invalid JSON - use raw response
    clean_response="$response"
    log_warning "site-creation" "API returned invalid JSON"
    log_warning "site-creation" "Raw response: $response"
fi

# Check if the response contains an error (4xx or 5xx status codes) - only if valid JSON
if echo "$clean_response" | jq -e '.status and (.status | type) == "number" and .status >= 400' > /dev/null 2>&1; then
    STATUS=$(echo "$clean_response" | jq -r '.status')
    MESSAGE=$(echo "$clean_response" | jq -r '.message // "Unknown error"')
    DETAILED_ERROR=$(echo "$clean_response" | jq -r '.data.message // empty')
    
    # Check for specific "site name already exists" error
    if echo "$DETAILED_ERROR" | grep -i "already exists" > /dev/null 2>&1; then
        log_warning "site-creation" "Site with name '$DISPLAY_NAME' already exists"
        log_info "site-creation" "Attempting to find existing site..."
        
        # Try to find the existing site by name and company
        SITE_QUERY_DATA=$(jq -n --arg display_name "$DISPLAY_NAME" '{display_name: $display_name}')
        EXISTING_SITE_RESPONSE=$(api_request "kinsta" "sites?company=$COMPANY_ID" "GET" "" 2>/dev/null) || {
            log_error "Could not query existing sites" "site-creation"
            end_deployment_session "FAILED"
            exit 1
        }
        
        if echo "$EXISTING_SITE_RESPONSE" | jq -e ".company.sites[]? | select(.display_name == \"$DISPLAY_NAME\")" > /dev/null 2>&1; then
            existing_site_id=$(echo "$EXISTING_SITE_RESPONSE" | jq -r ".company.sites[] | select(.display_name == \"$DISPLAY_NAME\") | .id")
            log_success "site-creation" "Found existing site with ID: $existing_site_id"
            echo "$existing_site_id" > "$ROOT_DIR/tmp/site_id.txt"
            log_info "site-creation" "Stored existing site ID in $ROOT_DIR/tmp/site_id.txt"
            log_success "deployment" "Using existing site - no creation needed"
            end_deployment_session "SUCCESS"
            exit 0
        else
            log_error "SITE NAME CONFLICT: A site with the name '$DISPLAY_NAME' already exists!" "site-creation"
            log_error "SOLUTION: Please change the 'display_name' in config/site.json to something unique" "site-creation"
            log_error "   Example: change 'poc-automation' to 'poc-automation-$(date +%m%d)' or 'poc-automation-v2'" "site-creation"
        fi
    else
        log_error "Site creation failed (Status $STATUS): $MESSAGE" "site-creation"
        if [ -n "$DETAILED_ERROR" ]; then
            log_error "Details: $DETAILED_ERROR" "site-creation"
        fi
    fi
    
    end_deployment_session "FAILED"
    exit 1
fi

# Check if we got a successful response with operation_id - only if valid JSON
if echo "$clean_response" | jq -e '.status and (.status | type) == "number" and (.status == 200 or .status == 201 or .status == 202)' > /dev/null 2>&1; then
    STATUS=$(echo "$clean_response" | jq -r '.status' 2>/dev/null || echo "unknown")
    MESSAGE=$(echo "$clean_response" | jq -r '.message // "Request accepted"' 2>/dev/null || echo "Request accepted")
    log_success "site-creation" "Site creation request successful (Status $STATUS): $MESSAGE"
fi

log_info "site-creation" "Extracting operation ID from response"
OPERATION_ID=$(echo "$clean_response" | jq -r '.operation_id // empty' 2>/dev/null || echo "")

log_info "site-creation" "Operation ID extracted: '$OPERATION_ID'"

if [ -n "$OPERATION_ID" ] && [ "$OPERATION_ID" != "null" ] && [ "$OPERATION_ID" != "empty" ]; then
    log_success "site-creation" "Operation ID: $OPERATION_ID"
    log_info "site-creation" "Site creation initiated."
    
    # Store operation ID for status checking
    echo "$OPERATION_ID" > "$ROOT_DIR/tmp/operation_id.txt"
    log_info "operation-tracking" "Operation ID stored in $ROOT_DIR/tmp/operation_id.txt"
    
    # Store operation metadata for status tracking
    cat > "$ROOT_DIR/tmp/operation_status.json" << EOF
{
    "operation_id": "$OPERATION_ID",
    "status": "initiated",
    "started_at": "$(date -Iseconds)",
    "type": "site_creation",
    "display_name": "$DISPLAY_NAME"
}
EOF
    
    log_success "deployment" "Site creation request submitted successfully!"
    log_info "operation-tracking" "Operation ID: $OPERATION_ID"
    log_info "operation-tracking" "Use 'bash status.sh' to monitor progress"
    log_info "operation-tracking" "Check logs with: tail -f logs/deployment/deployment.log"
    
    end_deployment_session "INITIATED"
    exit 0
else
    log_error "Failed to retrieve operation ID from API response" "site-creation"
    log_error "Operation ID value: '$OPERATION_ID'" "site-creation"
    log_error "Full response: $clean_response" "site-creation"
    
    # Try to extract any error information
    ERROR_STATUS=$(echo "$clean_response" | jq -r '.status // "unknown"' 2>/dev/null)
    ERROR_MESSAGE=$(echo "$clean_response" | jq -r '.message // "No error message"' 2>/dev/null)
    
    log_error "Response status: $ERROR_STATUS" "site-creation"
    log_error "Response message: $ERROR_MESSAGE" "site-creation"
    
    end_deployment_session "FAILED"
    exit 1
fi
