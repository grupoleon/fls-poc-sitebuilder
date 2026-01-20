#!/bin/bash

set -euo pipefail

# Load utilities
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"
source "$(dirname "${BASH_SOURCE[0]}")/api.sh"

# Configuration
CONFIG_FILE="$(dirname "${BASH_SOURCE[0]}")/../config/git.json"
WORKFLOW_ID="deploy.yml"
MAX_WAIT_TIME=${MAX_WAIT_TIME:-1800}  # 30 minutes (override with environment for testing)
POLL_INTERVAL=${POLL_INTERVAL:-30}    # 30 seconds (override with environment for testing)

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

# Convert an ISO8601 timestamp (e.g. 2025-12-03T17:28:36Z) to epoch seconds.
# Uses GNU date if available, otherwise falls back to python3/python.
iso_to_epoch() {
    local iso="$1"
    if [ -z "$iso" ]; then
        return 1
    fi

    # Try GNU date: `date -d 'ISO' +%s`
    if date -d "$iso" +%s >/dev/null 2>&1; then
        date -d "$iso" +%s
        return 0
    fi

    # Try macOS/BSD date parsing pattern (ensure UTC)
    if date -u -j -f "%Y-%m-%dT%H:%M:%SZ" "$iso" +%s >/dev/null 2>&1; then
        date -u -j -f "%Y-%m-%dT%H:%M:%SZ" "$iso" +%s
        return 0
    fi

    # Fallback to Python3/Python
    if command -v python3 >/dev/null 2>&1; then
        # Parse as UTC explicitly
        python3 -c "import sys,datetime; print(int(datetime.datetime.strptime(sys.argv[1], '%Y-%m-%dT%H:%M:%SZ').replace(tzinfo=datetime.timezone.utc).timestamp()))" "$iso"
        return $?
    fi

    if command -v python >/dev/null 2>&1; then
        # Python2 fallback - use calendar.timegm for UTC
        python -c "import sys,datetime,calendar; print(int(calendar.timegm(datetime.datetime.strptime(sys.argv[1], '%Y-%m-%dT%H:%M:%SZ').timetuple())))" "$iso"
        return $?
    fi

    return 1
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

# Get a specific workflow run by ID
get_workflow_run_by_id() {
    local rid="$1"
    if [[ -z "$rid" ]]; then
        return 1
    fi

    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/runs/$rid" "GET" "" false)

    if echo "$response" | jq -e '.' >/dev/null 2>&1; then
        echo "$response"
        return 0
    fi

    return 1
}

# Main monitoring loop
main() {
    local start_time elapsed_time run_data
    start_time=$(date +%s)
    
    log_info "Monitoring GitHub Actions deployment (timeout: ${MAX_WAIT_TIME}s)" "git-monitor"
    
    # Detect if a specific run ID was provided (env or file). If so monitor that run directly.
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    detected_run_id="${GITHUB_RUN_ID:-${RUN_ID:-}}"
    # Check common tmp locations (relative to repo and /tmp)
    if [ -z "$detected_run_id" ] && [ -f "$SCRIPT_DIR/../tmp/github_run_id.txt" ]; then
        detected_run_id=$(tr -d '[:space:]' < "$SCRIPT_DIR/../tmp/github_run_id.txt")
    fi
    if [ -z "$detected_run_id" ] && [ -f "/tmp/github_run_id.txt" ]; then
        detected_run_id=$(tr -d '[:space:]' < "/tmp/github_run_id.txt")
    fi

    if [ -n "$detected_run_id" ]; then
        log_info "Detected explicit run ID to monitor: $detected_run_id" "git-monitor"
        if ! run_data=$(get_workflow_run_by_id "$detected_run_id"); then
            log_warning "Could not fetch workflow run with ID $detected_run_id; falling back to latest run" "git-monitor"
            detected_run_id=""
        else
            # We have a concrete run to monitor - set run_id and skip the 'wait for new run' logic
            run_id=$(echo "$run_data" | jq -r '.id')
            initial_run_id="$run_id"
            log_info "Monitoring explicit run id: $run_id" "git-monitor"
        fi
    fi

    # Get the initial workflow run (if not already set via explicit run id)
    if [ -z "${run_id:-}" ]; then
        if ! run_data=$(get_latest_workflow_run); then
        log_error "Failed to get workflow run data" "git-monitor"
        exit 1
    fi
    
    local run_id
    run_id=$(echo "$run_data" | jq -r '.id')
    local initial_run_id
    initial_run_id="$run_id"
    fi

    # Guard against starting monitoring on an older completed run. If the latest run
    # predates our start time (i.e. it was created before we began the monitor), then
    # wait until a new workflow run appears (created after start_time - a small grace).
    created_at=$(echo "$run_data" | jq -r '.created_at')
    created_epoch=0
    if created_epoch=$(iso_to_epoch "$created_at" 2>/dev/null || true); then
        :
    else
        created_epoch=0
    fi

    local threshold=$((start_time - 15))
    if [ "$created_epoch" -lt "$threshold" ]; then
        log_info "Latest workflow run (created_at=${created_at}) appears older than this deployment (start=${start_time}); waiting for a new run to appear..." "git-monitor"

        local waited=0
        while true; do
            if [ $waited -ge $MAX_WAIT_TIME ]; then
                log_error "Timed out waiting for a new workflow run to be created (waited ${waited}s)" "git-monitor"
                exit 1
            fi

            sleep $POLL_INTERVAL
            waited=$((waited + POLL_INTERVAL))

            if ! run_data=$(get_latest_workflow_run); then
                continue
            fi

            created_at=$(echo "$run_data" | jq -r '.created_at')
            new_created_epoch=$(iso_to_epoch "$created_at" 2>/dev/null || true)

            log_info "Poll check: created_at=${created_at}, created_epoch=${new_created_epoch}, threshold=${threshold}, waited=${waited}s" "git-monitor"

            if [ -z "$new_created_epoch" ] || [ "$new_created_epoch" -le 0 ]; then
                continue
            fi

            # Also check whether the run id has changed from the initial run we saw.
            current_run_id=$(echo "$run_data" | jq -r '.id')

            if [ "$current_run_id" != "$initial_run_id" ] || [ "$new_created_epoch" -ge "$threshold" ]; then
                log_info "Detected new workflow run (id=${current_run_id}, created_at=${created_at}), now monitoring it" "git-monitor"
                break
            fi
        done

        run_id=$(echo "$run_data" | jq -r '.id')
    fi
    
    while true; do
        elapsed_time=$(($(date +%s) - start_time))
        
        # Check timeout
        if [[ $elapsed_time -gt $MAX_WAIT_TIME ]]; then
            log_error "Monitoring timeout reached (${MAX_WAIT_TIME}s)" "git-monitor"
            log_error "GitHub Actions may still be running. Check manually at: https://github.com/$GITHUB_OWNER/$GITHUB_REPO/actions" "git-monitor"
            exit 1
        fi
        
        # Get fresh workflow run data
        if [ -n "$detected_run_id" ]; then
            if ! run_data=$(get_workflow_run_by_id "$run_id"); then
                log_error "Failed to get updated workflow run data for run id $run_id" "git-monitor"
                sleep $POLL_INTERVAL
                continue
            fi
        else
            if ! run_data=$(get_latest_workflow_run); then
                log_error "Failed to get updated workflow run data" "git-monitor"
                sleep $POLL_INTERVAL
                continue
            fi
        fi
        
        # Check if this is still the same run we're monitoring
        local current_run_id
        current_run_id=$(echo "$run_data" | jq -r '.id')
        
        # If we were given an explicit run to monitor, don't switch to a different/latest run.
        if [[ -z "$detected_run_id" ]]; then
            if [[ "$current_run_id" != "$run_id" ]]; then
                log_info "Detected new workflow run (ID: $current_run_id), switching to monitor it" "git-monitor"
                run_id="$current_run_id"
            fi
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
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
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
fi