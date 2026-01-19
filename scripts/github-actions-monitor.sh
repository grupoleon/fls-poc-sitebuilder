#!/bin/bash

set -euo pipefail

# Load utilities
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"
source "$(dirname "${BASH_SOURCE[0]}")/api.sh"

# Configuration
CONFIG_FILE="$(dirname "${BASH_SOURCE[0]}")/../config/git.json"
WORKFLOW_ID="deploy.yml"
MAX_WAIT_TIME=1800  # 30 minutes
POLL_INTERVAL=30    # 30 seconds

# Check if git config exists
if [[ ! -f "$CONFIG_FILE" ]]; then
    log_error "Git configuration not found: $CONFIG_FILE" "git-monitor" "git-monitor"
    exit 1
fi

# Read configuration
GITHUB_OWNER=$(jq -r '.org // empty' "$CONFIG_FILE")
GITHUB_REPO=$(jq -r '.repo // empty' "$CONFIG_FILE")
GITHUB_TOKEN_CONFIG=$(jq -r '.token // empty' "$CONFIG_FILE")

# Use token from config or environment
GITHUB_TOKEN="${GITHUB_TOKEN:-$GITHUB_TOKEN_CONFIG}"

if [[ -z "$GITHUB_OWNER" || -z "$GITHUB_REPO" ]]; then
    log_error "GitHub owner and repo must be configured in $CONFIG_FILE" "git-monitor" "git-monitor"
    exit 1
fi

if [[ -z "$GITHUB_TOKEN" ]]; then
    log_error "GitHub token not configured. Set GITHUB_TOKEN environment variable or add 'token' to git.json" "git-monitor" "git-monitor"
    exit 1
fi

log_info "Starting GitHub Actions monitoring for $GITHUB_OWNER/$GITHUB_REPO" "git-monitor" "git-monitor"

# Function to get latest workflow run
get_latest_workflow_run() {
    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/workflows/$WORKFLOW_ID/runs" "GET" '{"per_page": 1}' false)
    
    if echo "$response" | jq -e '.workflow_runs[0]' >/dev/null 2>&1; then
        echo "$response" | jq -r '.workflow_runs[0]'
    else
        log_error "No workflow runs found" "git-monitor" "git-monitor"
        return 1
    fi
}

# Function to check workflow status
check_workflow_status() {
    local run_data="$1"
    local status conclusion created_at html_url run_id
    
    status=$(echo "$run_data" | jq -r '.status')
    conclusion=$(echo "$run_data" | jq -r '.conclusion // "null"')
    created_at=$(echo "$run_data" | jq -r '.created_at')
    html_url=$(echo "$run_data" | jq -r '.html_url')
    run_id=$(echo "$run_data" | jq -r '.id')
    
    log_info "Workflow Run ID: $run_id" "git-monitor"
    log_info "Status: $status" "git-monitor"
    log_info "Conclusion: $conclusion" "git-monitor"
    log_info "Created: $created_at" "git-monitor"
    log_info "URL: $html_url" "git-monitor"
    
    case "$status" in
        "queued")
            log_info "Workflow is queued, waiting to start..." "git-monitor"
            return 1  # Continue monitoring
            ;;
        "in_progress")
            log_info "Workflow is in progress..." "git-monitor"
            return 1  # Continue monitoring
            ;;
        "completed")
            case "$conclusion" in
                "success")
                    log_success "GitHub Actions deployment completed successfully!" "git-monitor"
                    return 0  # Success
                    ;;
                "failure")
                    log_error "GitHub Actions deployment failed!" "git-monitor"
                    log_error "Check logs at: $html_url" "git-monitor"
                    return 2  # Failed
                    ;;
                "cancelled")
                    log_warning "GitHub Actions deployment was cancelled" "git-monitor"
                    return 3  # Cancelled
                    ;;
                "timed_out")
                    log_error "GitHub Actions deployment timed out" "git-monitor"
                    return 4  # Timed out
                    ;;
                *)
                    log_error "GitHub Actions deployment completed with unknown conclusion: $conclusion" "git-monitor"
                    return 2  # Treat as failed
                    ;;
            esac
            ;;
        *)
            log_error "Unknown workflow status: $status" "git-monitor"
            return 2  # Treat as failed
            ;;
    esac
}

# Function to get workflow run jobs (for more detailed status)
get_workflow_jobs() {
    local run_id="$1"
    local response
    
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/runs/$run_id/jobs" "GET" "" false)
    
    if echo "$response" | jq -e '.jobs' >/dev/null 2>&1; then
        echo "$response" | jq -r '.jobs[] | "Job: \(.name) - Status: \(.status) - Conclusion: \(.conclusion // "running")"'
    fi
}

# Main monitoring loop
main() {
    local start_time elapsed_time run_data
    start_time=$(date +%s)
    
    log_info "Monitoring GitHub Actions deployment (timeout: ${MAX_WAIT_TIME}s)" "git-monitor"
    
    # Get the initial workflow run
    if ! run_data=$(get_latest_workflow_run); then
        log_error "Failed to get workflow run data" "git-monitor"
        exit 1
    fi
    
    local run_id
    run_id=$(echo "$run_data" | jq -r '.id')
    
    while true; do
        elapsed_time=$(($(date +%s) - start_time))
        
        # Check timeout
        if [[ $elapsed_time -gt $MAX_WAIT_TIME ]]; then
            log_error "Monitoring timeout reached (${MAX_WAIT_TIME}s)" "git-monitor"
            log_error "GitHub Actions may still be running. Check manually at: https://github.com/$GITHUB_OWNER/$GITHUB_REPO/actions" "git-monitor"
            exit 1
        fi
        
        # Get fresh workflow run data
        if ! run_data=$(get_latest_workflow_run); then
            log_error "Failed to get updated workflow run data" "git-monitor"
            sleep $POLL_INTERVAL
            continue
        fi
        
        # Check if this is still the same run we're monitoring
        local current_run_id
        current_run_id=$(echo "$run_data" | jq -r '.id')
        
        if [[ "$current_run_id" != "$run_id" ]]; then
            log_info "Detected new workflow run (ID: $current_run_id), switching to monitor it" "git-monitor"
            run_id="$current_run_id"
        fi
        
        # Check status
        if check_workflow_status "$run_data"; then
            # Success
            log_success "GitHub Actions monitoring completed successfully" "git-monitor"
            
            # Get final job status
            log_info "Final job status:" "git-monitor"
            get_workflow_jobs "$run_id" || true
            
            exit 0
        else
            local status_exit_code=$?
            if [[ $status_exit_code -eq 1 ]]; then
                # Still running, continue monitoring
                sleep $POLL_INTERVAL
            else
                # Failed, cancelled, or timed out
                log_info "Final job status:" "git-monitor"
                get_workflow_jobs "$run_id" || true
                exit $status_exit_code
            fi
        fi
    done
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "GitHub Actions Workflow Monitor"
        echo ""
        echo "Usage: $0 [options]"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --status       Check current workflow status without monitoring"
        echo ""
        echo "Configuration: $CONFIG_FILE"
        echo ""
        exit 0
        ;;
    --status)
        log_info "Checking current GitHub Actions status..." "git-monitor"
        if run_data=$(get_latest_workflow_run); then
            check_workflow_status "$run_data"
            exit_code=$?
            run_id=$(echo "$run_data" | jq -r '.id')
            get_workflow_jobs "$run_id" || true
            exit $exit_code
        else
            exit 1
        fi
        ;;
    *)
        main "$@"
        ;;
esac