#!/bin/bash

# Prevent multiple sourcing of logger.sh
[[ -n "${LOGGER_LOADED:-}" ]] && return 0
readonly LOGGER_LOADED=1

# Simple, User-Friendly Logging System
# This replaces the complex logging with simple, readable output

# No color codes - clean output for web interface
# Simple variable assignments (not readonly to avoid conflicts)
RED=''
GREEN=''
YELLOW=''
BLUE=''
NC=''

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$ROOT_DIR/logs"
mkdir -p "$LOG_DIR"

# Main log file for web interface
WEB_LOG="$LOG_DIR/deployment/deployment.log"
mkdir -p "$(dirname "$WEB_LOG")"

# Simple logging functions that output to both stdout AND web log
simple_log() {
    local level="$1"
    local step="$2"
    local message="$3"
    # Use UTC timezone for consistent logging across all operations
    local timestamp=$(TZ=UTC date '+%Y-%m-%d %H:%M:%S')
    
    # Format message for web interface (no colors, simple format)
    echo "[$timestamp] $level | $message" >> "$WEB_LOG"
    
    # Send informational messages to stdout, only actual errors to stderr
    if [[ "$level" == "ERROR" ]]; then
        echo "$message" >&2
    else
        echo "$message"
    fi
}

# User-friendly wrapper functions
log_info() {
    simple_log "INFO" "${2:-general}" "$1"
}

log_success() {
    simple_log "SUCCESS" "${2:-general}" "$1"
}

log_warning() {
    simple_log "WARNING" "${2:-general}" "$1"
}

log_error() {
    simple_log "ERROR" "${2:-general}" "$1"
}

log_debug() {
    # Debug logs are only shown in verbose mode or when DEBUG=1 is set
    if [[ "${DEBUG:-0}" == "1" || "${VERBOSE:-0}" == "1" ]]; then
        simple_log "DEBUG" "${2:-debug}" "$1"
    fi
}

# Progress indicators
log_step_start() {
    local step_name="$1"
    log_info "Starting: $step_name" "step"
}

log_step_complete() {
    local step_name="$1"
    log_success "Completed: $step_name" "step"
}

log_step_failed() {
    local step_name="$1"
    local reason="$2"
    log_error "Failed: $step_name${reason:+ - $reason}" "step"
}

# For API calls and technical operations  
log_api() {
    local operation="$1"
    local status="$2"
    local details="$3"
    
    if [[ "$status" =~ ^[2][0-9][0-9]$ ]]; then
        log_success "API $operation completed successfully" "api"
    else
        log_error "API $operation failed (Status: $status)${details:+ - $details}" "api"
    fi
}

# Detailed API request logging (for .log files as requested)
log_api_request() {
    local operation="$1"
    local method="$2" 
    local url="$3"
    local request_data="$4"
    local response_data="$5"
    local http_status="$6"
    
    # Use UTC timezone for consistent logging across all operations
    local timestamp=$(TZ=UTC date '+%Y-%m-%d %H:%M:%S')
    local api_log_file="$LOG_DIR/api/api.log"
    
    # Ensure API log directory exists
    mkdir -p "$(dirname "$api_log_file")"
    
    # Determine status text
    local status_text=""
    if [ "$http_status" -ge 400 ]; then
        status_text=" [ERROR]"
    elif [ "$http_status" -ge 300 ]; then
        status_text=" [REDIRECT]" 
    else
        status_text=" [SUCCESS]"
    fi
    
    # Clean up request and response data for logging
    local clean_request=$(echo "$request_data" | tr '\n' ' ' | sed 's/  */ /g' | head -c 500)
    local clean_response=$(echo "$response_data" | tr '\n' ' ' | sed 's/  */ /g' | head -c 500)
    
    # Write detailed API log
    cat >> "$api_log_file" << EOF
[$timestamp] $method $url
  Status: $http_status$status_text
  Request: ${clean_request:-"no-data"}
  Response: ${clean_response:-"no-response"} 
  Operation: $operation

EOF

    # Also log to main web log for visibility
    echo "[$timestamp] API | $operation: $method $url (Status: $http_status)" >> "$WEB_LOG"
    
    # Console output for immediate feedback - but only errors to avoid contaminating API responses
    if [ "$http_status" -ge 400 ]; then
        log_error "API $operation failed ($http_status): $url"
    else
        # Only log to file, not stdout, to avoid contaminating API responses
        local timestamp=$(TZ=UTC date '+%Y-%m-%d %H:%M:%S')
        echo "[$timestamp] INFO | API $operation completed ($http_status)" >> "$WEB_LOG"
    fi
}

# For file operations
log_file() {
    local operation="$1"
    local file="$2"
    local status="$3"
    
    if [ "$status" = "success" ]; then
        log_success "$operation: $(basename "$file")" "file"
    elif [ "$status" = "info" ]; then
        log_info "$operation: $(basename "$file")" "file"
    elif [ "$status" = "failed" ]; then
        log_error "Failed to $operation: $(basename "$file")" "file"
    else
        log_warning "$operation: $(basename "$file") (status: $status)" "file"
    fi
}

# Progress tracking
log_progress() {
    local current="$1"
    local total="$2"
    local task="$3"
    log_info "Progress: $current/$total - $task" "progress"
}

# Initialize logging session
init_logging() {
    local session_name="$1"
    # Use UTC timezone for consistent logging across all operations
    local timestamp=$(TZ=UTC date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] INFO | Starting $session_name" >> "$WEB_LOG"
    log_info "Starting $session_name"
}

# Clean up old logs (optional)
cleanup_logs() {
    if [ -f "$WEB_LOG" ]; then
        # Keep only last 200 lines
        tail -n 200 "$WEB_LOG" > "$WEB_LOG.tmp" && mv "$WEB_LOG.tmp" "$WEB_LOG"
    fi
}

# Function to update deployment session status
end_deployment_session() {
    local status="$1"
    local timestamp=$(date +%s)
    # Use UTC timezone for consistent logging across all operations
    local last_update=$(TZ=UTC date '+%Y-%m-%d %H:%M:%S')
    
    # Update deployment status file
    local status_file="$ROOT_DIR/tmp/deployment_status.json"
    mkdir -p "$(dirname "$status_file")"
    
    cat > "$status_file" << EOF
{
    "status": "$(echo "$status" | tr '[:upper:]' '[:lower:]')",
    "timestamp": $timestamp,
    "last_update": "$last_update"
}
EOF
    
    log_info "Deployment session status updated: $status"
}

# Silent logging functions for functions that return values via echo
# These always output to STDERR to avoid contaminating function return values
log_info_silent() {
    simple_log "INFO" "${2:-general}" "$1" >&2
}

log_success_silent() {
    simple_log "SUCCESS" "${2:-general}" "$1" >&2
}

log_warning_silent() {
    simple_log "WARNING" "${2:-general}" "$1" >&2
}

log_error_silent() {
    simple_log "ERROR" "${2:-general}" "$1" >&2
}

# Export functions for use in other scripts
export -f simple_log log_info log_success log_warning log_error log_debug
export -f log_info_silent log_success_silent log_warning_silent log_error_silent
export -f log_step_start log_step_complete log_step_failed
export -f log_api log_api_request log_file log_progress init_logging end_deployment_session