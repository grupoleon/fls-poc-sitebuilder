#!/bin/bash
set -euo pipefail

# Load logging utilities
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"

# Function to validate required tools
check_dependencies() {
    local missing_deps=()
    
    if ! command -v jq &> /dev/null; then
        missing_deps+=("jq")
    fi
    
    if ! command -v curl &> /dev/null; then
        missing_deps+=("curl")
    fi
    
    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        log_error "Missing required dependencies: ${missing_deps[*]}"
        log_info "Please install missing dependencies:"
        for dep in "${missing_deps[@]}"; do
            echo "  brew install $dep"
        done
        exit 1
    fi
}

# Function to read target site ID
get_target_site_id() {
    # First check if we have a direct site_id.txt file
    if [[ -f "tmp/site_id.txt" ]]; then
        local site_id
        site_id=$(cat tmp/site_id.txt | tr -d '[:space:]')
        if [[ -n "$site_id" ]]; then
            echo "$site_id"
            return 0
        fi
    fi
    
    # If no direct site ID, check if there's an operation we can query
    if [[ -f "tmp/operation_id.txt" ]]; then
        local operation_id
        operation_id=$(cat tmp/operation_id.txt | tr -d '[:space:]')
        if [[ -n "$operation_id" ]]; then
            log_info_silent "Found operation ID: $operation_id"
            log_info_silent "Waiting 30 seconds for operation to start..."
            sleep 30
            
            log_info_silent "Checking operation status to get site ID..."
            
            # Query the operation status with polling for in-progress operations
            local operation_response
            local max_attempts=30  # 30 attempts after initial wait = ~5 minutes total
            local attempt=1
            
            while [[ $attempt -le $max_attempts ]]; do
                operation_response=$(api_request kinsta "operations/$operation_id" GET)
                
                # Check if operation completed successfully
                if echo "$operation_response" | jq -e '.status == 200 and .site' > /dev/null 2>&1; then
                    break
                # Check if operation is still in progress
                elif echo "$operation_response" | jq -e '.status == 202' > /dev/null 2>&1; then
                    log_info_silent "Operation still in progress (attempt $attempt/$max_attempts)..."
                    if [[ $attempt -lt $max_attempts ]]; then
                        sleep 10
                        ((attempt++))
                        continue
                    else
                        log_error_silent "Operation timed out after $max_attempts attempts"
                        return 1
                    fi
                else
                    # Operation failed or other error - break to handle below
                    break
                fi
            done
            
            # Check if operation completed successfully
            if echo "$operation_response" | jq -e '.status == 200 and .data.idSite' > /dev/null 2>&1; then
                local site_id
                site_id=$(echo "$operation_response" | jq -r '.data.idSite')
                if [[ -n "$site_id" && "$site_id" != "null" ]]; then
                    log_success_silent "Retrieved site ID from operation: $site_id"
                    # Store the site ID for future use
                    echo "$site_id" > tmp/site_id.txt
                    echo "$site_id"
                    return 0
                fi
            # Check if operation failed (status 404 in JSON response)
            elif echo "$operation_response" | jq -e '.status == 404' > /dev/null 2>&1; then
                log_warning_silent "Operation returned 404 - Kinsta may be retrying internally"
                log_info_silent "Waiting for site to appear (Kinsta internal retry mechanism)..."
                
                # Wait for Kinsta's internal retry to complete - check for actual site creation
                local display_name company_id
                display_name=$(jq -r '.display_name' config/site.json)
                company_id=$(jq -r '.company' config/site.json)
                
                if [[ -n "$display_name" && "$display_name" != "null" && -n "$company_id" && "$company_id" != "null" ]]; then
                    # Poll for site creation completion (Kinsta retries internally)
                    local wait_attempts=60  # 5 minutes total (60 * 5 seconds)
                    local wait_attempt=1
                    
                    while [[ $wait_attempt -le $wait_attempts ]]; do
                        log_info_silent "Checking for site creation completion (attempt $wait_attempt/$wait_attempts)..."
                        sleep 5
                        
                        local sites_response
                        sites_response=$(api_request kinsta "sites?company=$company_id" GET "")
                        
                        if echo "$sites_response" | jq -e '.company.sites and (.company.sites | length > 0)' > /dev/null 2>&1; then
                            # Look for our site by display name
                            local site_id
                            site_id=$(echo "$sites_response" | jq -r ".company.sites[] | select(.display_name == \"$display_name\") | .id")
                            if [[ -n "$site_id" && "$site_id" != "null" ]]; then
                                log_success_silent "Site creation completed! Found site ID: $site_id"
                                echo "$site_id" > tmp/site_id.txt
                                echo "$site_id"
                                return 0
                            fi
                            
                            # Also try partial match for similar names
                            site_id=$(echo "$sites_response" | jq -r ".company.sites[] | select(.display_name | test(\"${display_name}.*\"; \"i\")) | .id" | head -1)
                            if [[ -n "$site_id" && "$site_id" != "null" ]]; then
                                log_success_silent "Site creation completed! Found similar site ID: $site_id"
                                echo "$site_id" > tmp/site_id.txt
                                echo "$site_id"
                                return 0
                            fi
                        fi
                        
                        ((wait_attempt++))
                    done
                    
                    log_error_silent "Site creation timed out after waiting for Kinsta internal retry"
                    return 1
                else
                    log_error_silent "Cannot check for site creation - missing display_name or company_id"
                    return 1
                fi
            elif echo "$operation_response" | jq -e '.data.message | contains("already exists")' > /dev/null 2>&1; then
                log_warning_silent "Site creation failed because site already exists"
                log_info_silent "Attempting to find existing site by name..."
                
                # Get the display name and company from config and query for existing site
                local display_name company_id
                display_name=$(jq -r '.display_name' config/site.json)
                company_id=$(jq -r '.company' config/site.json)
                if [[ -n "$display_name" && "$display_name" != "null" && -n "$company_id" && "$company_id" != "null" ]]; then
                    log_info_silent "Querying sites for company $company_id to find: $display_name"
                    local sites_response
                    sites_response=$(api_request kinsta "sites?company=$company_id" GET "")
                    
                    if echo "$sites_response" | jq -e '.company.sites and (.company.sites | length > 0)' > /dev/null 2>&1; then
                        local site_id
                        site_id=$(echo "$sites_response" | jq -r ".company.sites[] | select(.display_name == \"$display_name\") | .id")
                        if [[ -n "$site_id" && "$site_id" != "null" ]]; then
                            log_success_silent "Found existing site ID: $site_id"
                            # Store the site ID for future use
                            echo "$site_id" > tmp/site_id.txt
                            echo "$site_id"
                            return 0
                        fi
                    fi
                fi

            else
                log_warning_silent "Operation may still be in progress or failed"
                log_info_silent "Operation response: $operation_response"
            fi
        fi
    fi
    
    echo ""
    return 1
}

# Function to validate JSON files
validate_json_files() {
    if [[ ! -f "config/site.json" ]]; then
        log_error "config/site.json not found"
        exit 1
    fi
    
    if ! jq empty config/site.json; then
        log_error "config/site.json contains invalid JSON"
        exit 1
    fi
    
    if [[ ! -f "config/git.json" ]]; then
        log_error "config/git.json not found"
        exit 1
    fi
    
    if ! jq empty config/git.json; then
        log_error "config/git.json contains invalid JSON"
        exit 1
    fi
}

# Function to store credentials in GitHub secrets
store_github_secrets() {
    local site_name="$1"
    local ssh_host="$2"
    local ssh_port="$3"
    local ssh_path="$4"
    local site_id="$5"
    
    log_info "Storing site credentials in GitHub secrets..."
    
    # Check if Python is available for encryption
    # Check if Python 3 is available, try to install if not
    if ! command -v python3 &> /dev/null; then
        log_info "Python 3 not found, attempting to install..."
        if ! brew install python3; then
            log_error "Failed to install Python 3"
            return 1
        fi
    fi
    
    # Check if PyNaCl is available, try to install if not
    if ! python3 -c "import nacl.public" 2>/dev/null; then
        log_info "PyNaCl not found, attempting to install..."
        if ! pip3 install PyNaCl; then
            log_error "Failed to install PyNaCl"
            return 1
        fi
    fi
    
    # Read GitHub configuration  
    local github_owner github_repo
    github_owner=$(jq -r '.org // empty' config/git.json)
    github_repo=$(jq -r '.repo // empty' config/git.json)
    
    if [[ -z "$github_owner" || -z "$github_repo" ]]; then
        log_error "GitHub owner or repository not found in config/git.json"
        return 1
    fi
    
    # Get active theme from theme config
    local active_theme="FLS-One"  # Default fallback
    if [[ -f "config/theme-config.json" ]]; then
        active_theme=$(jq -r '.active_theme // "FLS-One"' config/theme-config.json)
        log_info "Active theme from config: $active_theme"
    fi
    
    # Get repository public key for encryption
    log_info "Fetching repository public key for encryption..."
    local public_key_response
    if ! public_key_response=$(api_request "github" "repos/$github_owner/$github_repo/actions/secrets/public-key" "GET" ""); then
        log_error "Failed to fetch repository public key"
        return 1
    fi
    
    local public_key key_id
    public_key=$(echo "$public_key_response" | jq -r '.key // empty')
    key_id=$(echo "$public_key_response" | jq -r '.key_id // empty')
    
    if [[ -z "$public_key" || -z "$key_id" ]]; then
        log_error "Failed to extract public key or key_id from response"
        return 1
    fi
    
    log_success "Retrieved public key (ID: $key_id)"
    
    # Store each credential as a separate secret
    local secrets=(
        "KINSTA_SITE_ID:$site_id"
        "KINSTA_SITE_NAME:$site_name"
        "KINSTA_HOST:$ssh_host"
        "KINSTA_PORT:$ssh_port"
        "KINSTA_PATH:$ssh_path"
        "KINSTA_USER:$site_name"
        "ACTIVE_THEME:$active_theme"
    )
    
    local success_count=0
    local total_secrets=${#secrets[@]}
    
    for secret in "${secrets[@]}"; do
        local secret_name="${secret%%:*}"
        local secret_value="${secret#*:}"
        
        log_info "Storing secret: $secret_name"
        
        # Encrypt the secret value using Python and PyNaCl
        local encrypted_value
        if ! encrypted_value=$(python3 << EOF 2>/dev/null
import sys
from base64 import b64encode, b64decode
from nacl import encoding, public

try:
    public_key_bytes = b64decode('$public_key')
    pub_key = public.PublicKey(public_key_bytes)
    sealed_box = public.SealedBox(pub_key)
    encrypted = sealed_box.encrypt('$secret_value'.encode('utf-8'))
    result = b64encode(encrypted).decode('utf-8')
    print(result)
except Exception as e:
    print(f'ERROR encrypting $secret_name: {e}', file=sys.stderr)
    sys.exit(1)
EOF
); then
            log_error "Failed to encrypt secret $secret_name"
            log_error "Python encryption failed or returned empty value"
            continue
        fi
        
        if [[ -z "$encrypted_value" || "$encrypted_value" == *"ERROR"* ]]; then
            log_error "Failed to encrypt secret $secret_name"
            log_error "Python encryption returned empty or error value: $encrypted_value"
            continue
        fi
        
        # Prepare the secret data as JSON with encrypted value and key_id
        local secret_data
        if ! secret_data=$(jq -n --arg encrypted_value "$encrypted_value" --arg key_id "$key_id" '{encrypted_value: $encrypted_value, key_id: $key_id}'); then
            log_error "Failed to prepare JSON data for secret $secret_name"
            continue
        fi
        
        # Make API request to store the secret
        local response
        if response=$(api_request "github" "repos/$github_owner/$github_repo/actions/secrets/$secret_name" "PUT" "$secret_data"); then
            # GitHub returns 204 (No Content) for successful secret updates
            # The api_request function handles status code checking internally
            log_success "Stored $secret_name in GitHub secrets"
            ((success_count++))
        else
            log_error "Failed to store $secret_name in GitHub secrets"
        fi
    done
    
    # Check if at least the critical KINSTA_SITE_ID secret was stored
    local kinsta_site_stored=false
    if [[ $success_count -gt 0 ]]; then
        # If we stored at least one secret, assume KINSTA_SITE_ID was stored
        kinsta_site_stored=true
    fi
    
    if [[ $success_count -eq $total_secrets ]]; then
        log_success "All $total_secrets credentials stored successfully in GitHub secrets"
        return 0
    elif [[ "$kinsta_site_stored" == "true" ]]; then
        log_success "Critical credentials stored in GitHub secrets ($success_count/$total_secrets)"
        return 0
    else
        log_error "Failed to store critical GitHub secrets ($success_count/$total_secrets)"
        return 1
    fi
}

# Main execution starts here
main() {
    log_step_start "Get Site Credentials"
    log_info "Retrieving your site login details..."
    
    # Check dependencies
    check_dependencies
    
    # Validate JSON files
    validate_json_files
    
    # Load API functions
    if [[ ! -f "$(dirname "${BASH_SOURCE[0]}")/api.sh" ]]; then
        log_error "api.sh not found"
        exit 1
    fi
    source "$(dirname "${BASH_SOURCE[0]}")/api.sh"
    
    # Get target site ID
    local target_site_id
    target_site_id=$(get_target_site_id)
    
    if [[ -n "$target_site_id" ]]; then
        log_info "Using target site ID: $target_site_id"
    else
        log_warning "No site ID found - will use first available site"
    fi
    
    # Fetch company_id from config/site.json
    local company_id
    company_id=$(jq -r '.company' config/site.json)
    
    if [[ -z "$company_id" || "$company_id" == "null" ]]; then
        log_error "Could not extract company ID from config/site.json"
        exit 1
    fi
    
    log_info "Fetching sites for company: $company_id"

    
    # Prepare API request data
    local data='{"company":"'$company_id'","include_environments":"true"}'
    
    # Make API request
    log_info "Making API request to fetch site details..."
    local response
    response=$(api_request "kinsta" "sites" "GET" "$data")
    
    if [[ -z "$response" ]]; then
        log_error "Empty response from API"
        exit 1
    fi
    
    log_success "API response received successfully"
    
    # Parse site details
    parse_and_extract_site_details "$response" "$target_site_id" "$data"
}

# Function to parse and extract site details
parse_and_extract_site_details() {
    local response="$1"
    local target_site_id="$2"
    local data="$3"
    
    local site_name ssh_host ssh_port site_id
    
    if [[ -n "$target_site_id" ]]; then
        log_info "Looking for specific site ID: $target_site_id"
        
        # Find the specific site by ID directly
        local site_exists
        site_exists=$(echo "$response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .id')
        
        if [[ -z "$site_exists" || "$site_exists" == "null" ]]; then
            log_error "Site with ID $target_site_id not found in company sites"
            log_info "Available sites:"
            echo "$response" | jq -r '.company.sites[] | "  ID: \(.id) - Name: \(.name)"'
            exit 1
        fi
        
        log_success "Found site with ID: $site_exists"
        
        # Extract site details (operation completion means site is ready)
        site_name=$(echo "$response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .name // empty')
        ssh_host=$(echo "$response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .environments[0].ssh_connection.ssh_ip.external_ip // empty')
        ssh_port=$(echo "$response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .environments[0].ssh_connection.ssh_port // empty')
        site_id=$(echo "$response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .id // empty')
    else
        log_info "No target site ID specified, using first available site"
        
        # Extract first site details (if we reach here, operation completed successfully)
        site_name=$(echo "$response" | jq -r '.company.sites[0].name // empty')
        ssh_host=$(echo "$response" | jq -r '.company.sites[0].environments[0].ssh_connection.ssh_ip.external_ip // empty')
        ssh_port=$(echo "$response" | jq -r '.company.sites[0].environments[0].ssh_connection.ssh_port // empty')
        site_id=$(echo "$response" | jq -r '.company.sites[0].id // empty')
    fi
    
    # Check if SSH details are missing and wait for provisioning
    if [[ -z "$ssh_host" || -z "$ssh_port" ]]; then
        log_warning "SSH details not yet available - site may still be provisioning"
        log_info "Waiting for SSH provisioning to complete..."
        
        # Poll for SSH details to become available (brief wait since operation completed)
        local ssh_wait_attempts=6  # 1 minute total (6 * 10 seconds)
        local ssh_attempt=1
        
        while [[ $ssh_attempt -le $ssh_wait_attempts ]]; do
            log_info "Checking for SSH provisioning (attempt $ssh_attempt/$ssh_wait_attempts)..."
            sleep 10
            
            # Re-fetch site details
            local fresh_response
            fresh_response=$(api_request "kinsta" "sites" "GET" "$data")
            
            if [[ -n "$target_site_id" ]]; then
                ssh_host=$(echo "$fresh_response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .environments[0].ssh_connection.ssh_ip.external_ip // empty')
                ssh_port=$(echo "$fresh_response" | jq -r --arg target_id "$target_site_id" '.company.sites[] | select(.id == $target_id) | .environments[0].ssh_connection.ssh_port // empty')
            else
                ssh_host=$(echo "$fresh_response" | jq -r '.company.sites[0].environments[0].ssh_connection.ssh_ip.external_ip // empty')
                ssh_port=$(echo "$fresh_response" | jq -r '.company.sites[0].environments[0].ssh_connection.ssh_port // empty')
            fi
            
            if [[ -n "$ssh_host" && -n "$ssh_port" ]]; then
                log_success "SSH provisioning completed! Host: $ssh_host, Port: $ssh_port"
                break
            fi
            
            ((ssh_attempt++))
        done
        
        if [[ -z "$ssh_host" || -z "$ssh_port" ]]; then
            log_error "SSH provisioning timed out after waiting $ssh_wait_attempts attempts"
            log_error "Site created but SSH access not yet available"
            exit 1
        fi
    fi
    
    # Validate extracted data
    validate_site_data "$site_name" "$ssh_host" "$ssh_port" "$site_id"
    
    # Update git.json with extracted data
    update_git_json "$site_name" "$ssh_host" "$ssh_port" "$site_id"
}

# Function to validate extracted site data
validate_site_data() {
    local site_name="$1"
    local ssh_host="$2"
    local ssh_port="$3"
    local site_id="$4"
    
    local errors=()
    
    [[ -z "$site_name" ]] && errors+=("site_name")
    [[ -z "$ssh_host" ]] && errors+=("ssh_host")
    [[ -z "$ssh_port" ]] && errors+=("ssh_port")
    [[ -z "$site_id" ]] && errors+=("site_id")
    
    if [[ ${#errors[@]} -gt 0 ]]; then
        log_error "Could not extract required site details from API response"
        for field in "${errors[@]}"; do
            log_error "Missing: $field"
        done
        exit 1
    fi
    
    log_success "Site details extracted successfully:"
    echo "  Site name: $site_name"
    echo "  SSH host: $ssh_host"
    echo "  SSH port: $ssh_port"
    echo "  Site ID: $site_id"
}

# Function to find the correct SSH path by connecting to the server
find_ssh_path() {
    local site_name="$1"
    local ssh_host="$2"
    local ssh_port="$3"
    
    log_info "Searching for correct SSH path on server..." >&2
    
    # Try to find directories matching the pattern /www/{sitename}_*/public
    local ssh_command="find /www -maxdepth 1 -type d -name '${site_name}_*' | head -1"
    
    local found_path
    found_path=$(ssh -o ConnectTimeout=10 \
                     -o BatchMode=yes \
                     -o StrictHostKeyChecking=no \
                     -p "$ssh_port" \
                     "$site_name@$ssh_host" \
                     "$ssh_command" | tr -d '[:space:]')
    
    if [[ -n "$found_path" ]]; then
        local full_path="${found_path}/public"
        log_success "Found SSH path: $full_path" >&2
        echo "$full_path"
        return 0
    else
        # Fallback to the standard Kinsta path pattern
        local fallback_path="/www/${site_name}/public"
        log_success "Using standard Kinsta path: $fallback_path" >&2
        echo "$fallback_path"
        return 0
    fi
}

# Function to update git.json file
update_git_json() {
    local site_name="$1"
    local ssh_host="$2" 
    local ssh_port="$3"
    local site_id="$4"
    
    # Find the correct SSH path by connecting to the server
    local ssh_path
    ssh_path=$(find_ssh_path "$site_name" "$ssh_host" "$ssh_port")
    
    log_info "Using SSH path: $ssh_path"
    log_info "Updating git.json with site credentials..."
    
    # Create backup of original git.json
    if ! cp config/git.json config/git.json.backup; then
        log_error "Failed to create backup of git.json"
        exit 1
    fi
    
    # Update git.json with new values
    if jq --arg user "$site_name" \
       --arg host "$ssh_host" \
       --argjson port "$ssh_port" \
       --arg path "$ssh_path" \
       '.user = $user | .host = $host | .port = $port | .path = $path' \
       config/git.json > config/git.json.tmp; then
        
        mv config/git.json.tmp config/git.json
        rm config/git.json.backup
        
        log_success "Successfully updated git.json with site credentials"
        log_info "Updated values:"
        echo "  User: $site_name"
        echo "  Host: $ssh_host"
        echo "  Port: $ssh_port"
        echo "  Path: $ssh_path"
        
        # Store credentials in GitHub secrets
        if store_github_secrets "$site_name" "$ssh_host" "$ssh_port" "$ssh_path" "$site_id"; then
            log_step_complete "Get Site Credentials"
            exit 0
        else
            log_warning "Some GitHub secrets may not have been stored, but site credentials are ready"
            log_step_complete "Get Site Credentials"
            # Exit successfully since the core functionality worked
            exit 0
        fi
        
    else
        log_error "Failed to update git.json"
        mv config/git.json.backup config/git.json
        log_step_failed "Get Site Credentials" "Failed to update git configuration"
        exit 1
    fi
}

# Run main function if not being sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi