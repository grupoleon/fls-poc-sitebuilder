#!/bin/bash

set -euo pipefail

# Load logging system
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"

# Configuration file paths
CONFIG_DIR="$(dirname "${BASH_SOURCE[0]}")/../config"
SITE_CONFIG="$CONFIG_DIR/site.json"
GIT_CONFIG="$CONFIG_DIR/git.json"
MAIN_CONFIG="$CONFIG_DIR/config.json"

# Read tokens from JSON configuration files with environment variable fallbacks
KINSTA_API_TOKEN="${KINSTA_API_TOKEN:-$(jq -r '.site.kinsta_token // empty' "$MAIN_CONFIG")}"
GITHUB_TOKEN="${GITHUB_TOKEN:-$(jq -r '.token // empty' "$GIT_CONFIG")}"
CLICKUP_API_TOKEN="${CLICKUP_API_TOKEN:-$(jq -r '.integrations.clickup.api_token // empty' "$MAIN_CONFIG")}"
COMPANY_ID="${COMPANY_ID:-$(jq -r '.company // empty' "$SITE_CONFIG")}"

# API logging is always enabled

# Check if tokens are set (only for mandatory services)
if [[ -z "$GITHUB_TOKEN" ]]; then
    log_error "GITHUB_TOKEN is not set. Please set the token in $GIT_CONFIG or set the GITHUB_TOKEN environment variable. Example: export GITHUB_TOKEN='your_github_personal_access_token'" "API"
    exit 1
fi

if [[ -z "$KINSTA_API_TOKEN" ]]; then
    log_error "KINSTA_API_TOKEN is not set. Please set site.kinsta_token in $MAIN_CONFIG or set the KINSTA_API_TOKEN environment variable. Example: export KINSTA_API_TOKEN='your_kinsta_api_token'" "API"
    exit 1
fi

# ClickUp token is optional - checked when needed

api_request() {
    local service="$1"
    local endpoint="$2"
    local method="${3:-GET}"
    local data="${4:-}"
    local debug="${5:-false}"
    local accept_header

    local token base_url

    case "$service" in
        github)
            token="$GITHUB_TOKEN"
            base_url="https://api.github.com"
            accept_header="application/vnd.github+json"
            ;;
        kinsta)
            token="$KINSTA_API_TOKEN"
            base_url="https://api.kinsta.com/v2"
            accept_header="application/json"
            ;;
        clickup)
            token="$CLICKUP_API_TOKEN"
            base_url="https://api.clickup.com/api/v2"
            accept_header="application/json"
            
            # Validate ClickUp token when needed
            if [[ -z "$token" ]]; then
                log_error "CLICKUP_API_TOKEN is not set. Please set integrations.clickup.api_token in $MAIN_CONFIG" "API"
                exit 1
            fi
            ;;
        *)
            log_error "Unknown service: $service" "API"
            exit 1
            ;;
    esac

    if [[ -z "$token" || -z "$endpoint" ]]; then
        log_error "Missing required parameters: token or endpoint." "API"
        exit 1
    fi

    local url="${base_url}/${endpoint}"
    local curl_opts=(
        --silent
        --location
        --header "Content-Type: application/json"
        --header "Accept: $accept_header"
        --request "$method"
    )
    
    # Service-specific headers
    if [[ "$service" == "github" ]]; then
        curl_opts+=(--header "Authorization: Bearer $token")
        curl_opts+=(--header "X-GitHub-Api-Version: 2022-11-28")
    elif [[ "$service" == "kinsta" ]]; then
        curl_opts+=(--header "Authorization: Bearer $token")
    elif [[ "$service" == "clickup" ]]; then
        # ClickUp uses a different authorization format
        curl_opts+=(--header "Authorization: $token")
    fi

    if [[ "$method" == "GET" && -n "$data" ]]; then
        local query
        query=$(echo "$data" | jq -r 'to_entries | map("\(.key)=\(.value|tostring)") | join("&")')
        url="${url}?${query}"
    elif [[ -n "$data" ]]; then
        curl_opts+=(--data-raw "$data")
    fi

    if [[ "$debug" == true ]]; then
        log_debug "API Request: $method $url" "API"
        log_debug "Headers: ${curl_opts[*]}" "API"
    fi
    
    # Log the curl command (properly formatted for debug)
    local curl_cmd="curl"
    for opt in "${curl_opts[@]}"; do
        curl_cmd="$curl_cmd \"$opt\""
    done
    curl_cmd="$curl_cmd \"$url\""
    log_debug "Curl command: $curl_cmd" "API"
    
    # Retry logic with intelligent backoff for rate limiting (429) and server errors (5xx)
    local max_retries=3
    local retry_count=0
    local base_delay=5
    local response response_body http_code curl_exit_code
    
    while [[ $retry_count -le $max_retries ]]; do
        response=$(curl -w "HTTPSTATUS:%{http_code}" "${curl_opts[@]}" "$url")
        curl_exit_code=$?
        
        http_code=$(echo "$response" | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
        response_body=$(echo "$response" | sed -E 's/HTTPSTATUS:[0-9]*$//')

        # Handle curl execution failures
        if [[ $curl_exit_code -ne 0 ]]; then
            if [[ $curl_exit_code -eq 6 ]]; then
                # For DNS failures, try with modified curl settings first
                if [[ "$service" == "kinsta" ]]; then
                    {
                        log_warning "DNS resolution failed for $url, retrying with enhanced options..." "API"
                        log_debug "Retry with DNS resolve: $url" "API"
                    } >&2
                    
                    local curl_opts_retry=("${curl_opts[@]}")
                    curl_opts_retry+=(--resolve "api.kinsta.com:443:172.64.147.50")
                    curl_opts_retry+=(--connect-timeout 30)
                    curl_opts_retry+=(--max-time 60)
                    
                    response=$(curl -w "HTTPSTATUS:%{http_code}" "${curl_opts_retry[@]}" "$url")
                    curl_exit_code=$?
                    
                    http_code=$(echo "$response" | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
                    response_body=$(echo "$response" | sed -E 's/HTTPSTATUS:[0-9]*$//')

                    if [[ $curl_exit_code -ne 0 ]]; then
                        log_error "API request failed even with DNS resolve. Exit code: $curl_exit_code" "API" >&2
                        exit 1
                    fi
                else
                    log_error "DNS resolution failed for $url. Exit code: $curl_exit_code" "API" >&2
                    exit 1
                fi
            else
                log_error "API request failed: $url (Exit code: $curl_exit_code)" "API"
                exit 1
            fi
        fi
        
        # Check if we got a rate limit (429) response - special handling
        if [[ "$http_code" == "429" ]]; then
            if [[ $retry_count -eq 0 ]]; then
                # First 429 - fail immediately with clear message
                log_error "API Rate Limit Exceeded (429)" "API"
                log_error "Kinsta API allows 60 requests per hour" "API"
                log_error "You must wait before trying again" "API"
                log_error "" "API"
                log_error "SOLUTIONS:" "API"
                log_error "  1. Wait at least 1 hour before retrying" "API"
                log_error "  2. Check if you have other scripts/processes making API calls" "API"
                log_error "  3. Reduce the frequency of deployment attempts" "API"
                log_error "" "API"
                log_error "Response: $response_body" "API"
                break
            fi
        fi
        
        # Check if we got a server error (5xx) response
        if [[ "$http_code" =~ ^5[0-9]{2}$ ]]; then
            if [[ $retry_count -lt $max_retries ]]; then
                local delay=$((base_delay * (2 ** retry_count)))
                log_warning "Server error ($http_code), retrying in ${delay}s (attempt $((retry_count + 1))/$max_retries)..." "API"
                sleep "$delay"
                ((retry_count++))
                continue
            else
                log_error "Max retries reached for $url after receiving $http_code" "API"
                break
            fi
        fi
        
        # Success or non-retryable error - break out of retry loop
        break
    done
    
    # Log the API request and response
    local operation="$service-$(echo $endpoint | tr '/' '-')"
    log_api_request "$operation" "$method" "$url" "$data" "$response_body" "$http_code"
    
    echo "$response_body"
}

# Function to check operation status
get-operation-status() {
    local operation_id="$1"
    
    if [[ -z "$operation_id" ]]; then
        echo '{"status": "error", "message": "Operation ID is required"}'
        return 1
    fi
    
    # Make API request to check operation status - try different possible endpoints
    local response
    
    # First try the operations endpoint
    response=$(api_request "kinsta" "operations/$operation_id" "GET" "" false)
    
    # If that fails, try the operations status endpoint
    if echo "$response" | grep -q '"status".*404'; then
        response=$(api_request "kinsta" "operations/$operation_id/status" "GET" "" false)
    fi
    
    echo "$response"
}

# Handle command line arguments for direct execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        get-operation-status)
            get-operation-status "$2"
            ;;
        *)
            echo "Usage: $0 {get-operation-status} <operation_id>"
            echo "Example: $0 get-operation-status sites:add-12345..."
            exit 1
            ;;
    esac
fi

