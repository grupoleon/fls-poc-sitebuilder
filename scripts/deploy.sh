#!/bin/bash

set -euo pipefail

# Load logging utilities
source "$(dirname "${BASH_SOURCE[0]}")/logger.sh"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$ROOT_DIR/config/git.json"
API_SCRIPT="$SCRIPT_DIR/api.sh"
CONFIG_JSON_FILE="$ROOT_DIR/config/config.json"
THEME_CONFIG_FILE="$ROOT_DIR/config/theme-config.json"

# --- Dependency Checks ---
if [[ ! -f "$CONFIG_FILE" ]]; then
    echo "Configuration file not found: $CONFIG_FILE" >&2
    exit 1
fi
if [[ ! -f "$API_SCRIPT" ]]; then
    echo "API script not found: $API_SCRIPT" >&2
    exit 1
fi
if ! command -v jq &>/dev/null; then
    echo "jq is required but not installed. Install with: brew install jq" >&2
    exit 1
fi

source "$API_SCRIPT"

# --- Configuration ---
read_config() {
    local key="$1"
    local value
    value=$(jq -r "$key // empty" "$CONFIG_FILE")
    if [[ -z "$value" ]]; then
        echo "Warning: $key not found in config" >&2
    fi
    echo "$value"
}

GITHUB_OWNER="$(read_config '.org')"
GITHUB_REPO="$(read_config '.repo')"
WORKFLOW_ID="deploy.yml"
BRANCH="$(read_config '.branch')"

# Fallback to default branch if not set in config
if [[ -z "$BRANCH" || "$BRANCH" == "null" ]]; then
    BRANCH="content_automation"
    print_warning "Branch not configured in git.json, using default: $BRANCH"
fi

KINSTA_HOST="$(read_config '.host')"
KINSTA_USER="$(read_config '.user')"
KINSTA_PORT="$(read_config '.port')"

# Preserve USER environment variable if properly set, otherwise set it to avoid UID fallback
if [[ -z "${USER:-}" ]] || [[ "$USER" =~ ^[0-9]+$ ]]; then
    # Fallback to vishnu since background-deploy.php should have set USER=vishnu
    export USER="vishnu"
fi

# --- Output Formatting ---
# Using logger.sh functions for web interface visibility
print_info() { log_info "$1"; }
print_success() { log_success "$1"; }
print_warning() { log_warning "$1"; }
print_error() { log_error "$1"; }

# --- Password Generation ---
# Generate a strong random password for each deployment
generate_strong_password() {
    local length=16
    local password=""
    
    # Define character sets (removed ambiguous characters for clarity)
    local uppercase="ABCDEFGHJKLMNPQRSTUVWXYZ"  # Removed I, O
    local lowercase="abcdefghijkmnopqrstuvwxyz"  # Removed l, o
    local numbers="23456789"  # Removed 0, 1
    local special="!@#$%^&*()-_=+[]{}|;:,.<>?"
    
    # Ensure at least one character from each set
    password+=$(echo -n "$uppercase" | fold -w1 | shuf | head -c1)
    password+=$(echo -n "$lowercase" | fold -w1 | shuf | head -c1)
    password+=$(echo -n "$numbers" | fold -w1 | shuf | head -c1)
    password+=$(echo -n "$special" | fold -w1 | shuf | head -c1)
    
    # Fill remaining length with random characters from all sets
    local all_chars="${uppercase}${lowercase}${numbers}${special}"
    local remaining=$((length - 4))
    password+=$(echo -n "$all_chars" | fold -w1 | shuf | head -c${remaining})
    
    # Shuffle the password to randomize character positions
    echo -n "$password" | fold -w1 | shuf | tr -d '\n'
}

# Generate and update admin password in config before deployment
generate_and_update_admin_password() {
    print_info "Generating new admin password for this deployment..."
    
    local new_password
    new_password=$(generate_strong_password)
    
    if [[ -z "$new_password" ]]; then
        print_error "Failed to generate admin password"
        exit 1
    fi
    
    print_success "Generated strong admin password (16 characters with mixed case, numbers, special chars)"
    
    # Update config.json with new password
    print_info "Updating config.json with generated password..."
    if jq --arg pass "$new_password" '.site.admin_password = $pass' "$CONFIG_JSON_FILE" > "${CONFIG_JSON_FILE}.tmp" 2>/dev/null; then
        mv "${CONFIG_JSON_FILE}.tmp" "$CONFIG_JSON_FILE"
        print_success "Config file updated with new password"
        print_info "Password will be uploaded to server and appear in ClickUp comments after deployment"
    else
        print_error "Failed to update config.json with new password"
        rm -f "${CONFIG_JSON_FILE}.tmp"
        exit 1
    fi
}

# --- Core Functions ---
check_github_token() {
    if [[ -z "${GITHUB_TOKEN:-}" ]]; then
        print_error "GitHub token not found!"
        echo -e "\nSet the token in api.sh or as an environment variable:\n  export GITHUB_TOKEN='your_token_here'\n"
        exit 1
    fi
}

validate_github_token() {
    print_info "Validating GitHub token..."
    local response
    response=$(api_request "github" "user" "GET" "" false)
    if local username=$(echo "$response" | jq -r '.login // empty'); [[ -n "$username" ]]; then
        log_api "token_validation" "200" "Token valid for user: $username"
        print_success "GitHub token is valid (User: $username)"
    else
        log_api "token_validation" "401" "Token validation failed"
        print_error "GitHub token validation failed"
        echo "Response: $response" >&2
        exit 1
    fi
}

check_repo_access() {
    print_info "Checking repository access..."
    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO" "GET" "" false)
    if local repo_name=$(echo "$response" | jq -r '.full_name // empty'); [[ -n "$repo_name" ]]; then
        log_api "repo_access" "200" "Repository access confirmed: $repo_name"
        print_success "Repository access confirmed ($repo_name)"
    else
        log_api "repo_access" "403" "Cannot access repository"
        print_error "Cannot access repository $GITHUB_OWNER/$GITHUB_REPO"
        echo "Response: $response" >&2
        exit 1
    fi
}

upload_file() {
    local file_path="$1"
    local file_name
    file_name=$(basename "$file_path")

    if [[ ! -f "$file_path" ]]; then
        log_file "locate" "$file_name" "failed"
        print_error "$file_name not found at $file_path"
        exit 1
    fi
    if ! jq empty "$file_path"; then
        log_file "validate" "$file_name" "failed"
        print_error "Invalid JSON in $file_name"
        exit 1
    fi

    log_file "upload_start" "$file_name" "info"
    
    # Direct upload with reduced retry (SSH connectivity already verified)
    local upload_attempts=0
    local max_attempts=2  # Reduced from 3
    
    while (( upload_attempts < max_attempts )); do
        upload_attempts=$((upload_attempts + 1))
        log_progress "$upload_attempts" "$max_attempts" "Uploading $file_name"
        
        # Use rsync with shorter timeout since connectivity is verified
        local rsync_cmd="rsync -avz -e 'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -i $HOME/.ssh/id_rsa -p $KINSTA_PORT'"
        local upload_error_output
        
        if upload_error_output=$(eval "${rsync_cmd} '$file_path' '${KINSTA_USER}@${KINSTA_HOST}:/tmp/'" 2>&1); then
            log_file "upload" "$file_name" "success"
            return 0
        else
            print_warning "Upload failed with output: $upload_error_output"
            if (( upload_attempts < max_attempts )); then
                print_warning "Waiting 5 seconds before retry..."
                sleep 5  # Reduced from 8
            else
                log_file "upload" "$file_name" "failed"
                print_error "Failed to upload $file_name after $max_attempts attempts"
                print_error "Final error output: $upload_error_output"
                exit 1
            fi
        fi
    done
}

upload_configs() {
    if [[ -z "$KINSTA_HOST" || -z "$KINSTA_USER" || -z "$KINSTA_PORT" ]]; then
        print_error "Missing Kinsta connection details (host, user, port) in $CONFIG_FILE"
        exit 1
    fi
    
    # Verify SSH key exists (should be created by setup.sh)
    print_info "Verifying SSH credentials..."
    if [[ ! -f "$HOME/.ssh/id_rsa" ]]; then
        print_error "═══════════════════════════════════════════════════════════════════"
        print_error "SSH Key Not Found"
        print_error "═══════════════════════════════════════════════════════════════════"
        print_error ""
        print_error "SSH key missing at: $HOME/.ssh/id_rsa"
        print_error ""
        print_error "This deployment requires SSH keys to be set up first."
        print_error ""
        print_error "To fix this, run the setup script:"
        print_error "  bash setup.sh"
        print_error ""
        print_error "Then add the generated public key to your Kinsta account."
        print_error "═══════════════════════════════════════════════════════════════════"
        exit 1
    fi
    
    # Test SSH connectivity once at the start of uploads
    print_info "Testing SSH connectivity to Kinsta server..."
    print_info "Using SSH key: $HOME/.ssh/id_rsa"
    print_info "HOME directory: $HOME"
    
    # Create .ssh directory and known_hosts if needed
    mkdir -p "$HOME/.ssh" && chmod 700 "$HOME/.ssh" 2>/dev/null || true
    touch "$HOME/.ssh/known_hosts" 2>/dev/null || true
    
    # Add Kinsta host to known_hosts if not already there to avoid interactive prompt
    ssh-keyscan -p "$KINSTA_PORT" -H "$KINSTA_HOST" >> "$HOME/.ssh/known_hosts" 2>/dev/null || true
    
    # Test connection with better error handling
    if ! ssh -o ConnectTimeout=10 -o BatchMode=yes -i "$HOME/.ssh/id_rsa" -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "echo 'Connected'" >/dev/null 2>&1; then
        print_warning "SSH pre-check failed, but continuing anyway (connection will be tested during upload)"
        print_info "If upload fails, verify SSH key is added to: https://my.kinsta.com/account/ssh-keys"
    else
        print_success "SSH connectivity verified"
    fi
    
    print_info "Uploading configuration files..."
    print_info "Config file path: $CONFIG_JSON_FILE"
    print_info "Theme config file path: $THEME_CONFIG_FILE"
    
    # Verify files exist before upload
    if [[ ! -f "$CONFIG_JSON_FILE" ]]; then
        print_error "Main config file not found: $CONFIG_JSON_FILE"
        exit 1
    fi
    
    if [[ ! -f "$THEME_CONFIG_FILE" ]]; then
        print_error "Theme config file not found: $THEME_CONFIG_FILE"
        exit 1
    fi
    
    upload_file "$CONFIG_JSON_FILE"
    upload_file "$THEME_CONFIG_FILE"
    
    # Upload auth.json if it exists (for Google OAuth configuration)
    local auth_config_file="$ROOT_DIR/config/auth.json"
    if [[ -f "$auth_config_file" ]]; then
        print_info "Uploading auth configuration..."
        upload_file "$auth_config_file"
        print_success "Auth configuration uploaded"
    else
        print_info "No auth.json found - Google OAuth can be configured later"
    fi
    
    # Verify theme config was uploaded correctly
    print_info "Verifying theme config upload..."
    if ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "test -f /tmp/theme-config.json"; then
        print_success "Theme config confirmed on server"
        # Show the active theme from uploaded config
        ACTIVE_THEME=$(ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "jq -r '.active_theme' /tmp/theme-config.json 2>/dev/null" || echo "")
        if [[ -n "$ACTIVE_THEME" && "$ACTIVE_THEME" != "null" ]]; then
            print_success "Active theme detected: $ACTIVE_THEME"
        else
            print_warning "Could not read active theme from uploaded config"
        fi
    else
        print_error "Theme config file not found on server after upload"
        exit 1
    fi
    
    # Note: Individual theme files are no longer used
    # We use the centralized theme-config.json which is uploaded as part of config files
    # However, create legacy theme config files to prevent errors from old code
    create_legacy_theme_configs
}

create_legacy_theme_configs() {
    print_info "Creating legacy theme config files to prevent errors..."
    
    # Create /tmp/themes directory on server
    ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p /tmp/themes"
    
    # Get active theme from the main theme config
    ACTIVE_THEME=$(ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "jq -r '.active_theme // \"FLS-One\"' /tmp/theme-config.json 2>/dev/null")
    
    # Create FLS-One.json
    ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "cat > /tmp/themes/FLS-One.json << 'EOF'
{
  \"theme_name\": \"FLS-One\",
  \"active\": $([ \"$ACTIVE_THEME\" = \"FLS-One\" ] && echo \"true\" || echo \"false\"),
  \"config\": {
    \"colors\": {
      \"primary\": \"#1e3a8a\",
      \"secondary\": \"#7c3aed\",
      \"accent\": \"#14b8a6\"
    },
    \"settings\": {
      \"logo_enabled\": true,
      \"navigation_enabled\": true,
      \"custom_pages\": true
    }
  },
  \"version\": \"1.0.0\",
  \"created\": \"$(date -u +%Y-%m-%d)\"
}
EOF"
    
    # Create FLS-Two.json
    ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "cat > /tmp/themes/FLS-Two.json << 'EOF'
{
  \"theme_name\": \"FLS-Two\",
  \"active\": $([ \"$ACTIVE_THEME\" = \"FLS-Two\" ] && echo \"true\" || echo \"false\"),
  \"config\": {
    \"colors\": {
      \"primary\": \"#4f46e5\",
      \"secondary\": \"#22c55e\",
      \"accent\": \"#6ee7b7\"
    },
    \"settings\": {
      \"logo_enabled\": true,
      \"navigation_enabled\": true,
      \"custom_pages\": true
    }
  },
  \"version\": \"1.0.0\",
  \"created\": \"$(date -u +%Y-%m-%d)\"
}
EOF"
    
    print_success "Legacy theme config files created on remote server"
}

upload_pages() {
    local pages_dir="$SCRIPT_DIR/../pages"
    
    if [[ ! -d "$pages_dir" ]]; then
        print_warning "Pages directory not found: $pages_dir"
        return
    fi
    
    # Read override settings from theme config
    local slides_override="true"
    local pages_override="true"
    local cpt_override="true"
    
    if [[ -f "$THEME_CONFIG_FILE" ]]; then
        slides_override=$(jq -r '.overrides.slides_override // true' "$THEME_CONFIG_FILE")
        pages_override=$(jq -r '.overrides.pages_override // true' "$THEME_CONFIG_FILE")
        cpt_override=$(jq -r '.overrides.cpt_override // true' "$THEME_CONFIG_FILE")
        
        print_info "Override settings - Slides: $slides_override, Pages: $pages_override, CPT: $cpt_override"
    fi
    
    print_info "Uploading page layout files..."
    print_info "These custom layouts will replace theme defaults BEFORE activation"
    
    # Create pages directory on server
    ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p /tmp/pages"
    
    # Upload CPT JSON files first (only if override is enabled)
    if [[ "$cpt_override" == "true" ]]; then
        local custom_posts_dir="$pages_dir/cpt"
        local custom_posts_dir_server="/tmp/pages/cpt"
        if [[ -d "$custom_posts_dir" ]]; then
            print_info "Uploading CPT JSON files..."
            
            # Create CPT directory on server
            ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p '$custom_posts_dir_server'"
            
            # Count and upload JSON files
            local custom_posts_count=$(find "$custom_posts_dir" -name "*.json" -type f | wc -l)
            print_info "Found $custom_posts_count CPT JSON files"
            
            for json_file in "$custom_posts_dir"/*.json; do
                if [[ -f "$json_file" ]]; then
                    local file_name=$(basename "$json_file")
                    print_info "Uploading CPT: $file_name"
                    
                    # Validate JSON before upload
                    if ! jq empty "$json_file" 2>/dev/null; then
                        print_error "Invalid JSON in CPT file: $file_name"
                        exit 1
                    fi

                    if scp -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -P "$KINSTA_PORT" "$json_file" "${KINSTA_USER}@${KINSTA_HOST}:$custom_posts_dir_server/"; then
                        print_success "CPT $file_name uploaded successfully"
                    else
                        print_error "Failed to upload CPT: $file_name"
                        exit 1
                    fi
                fi
            done
            
            if [[ $custom_posts_count -gt 0 ]]; then
                print_success "All CPT files uploaded successfully ($custom_posts_count files)"
            fi
        else
            print_info "No CPT directory found at: $custom_posts_dir"
        fi
    else
        print_warning "CPT override is disabled - skipping custom post types upload (theme defaults will be used)"
    fi
    
    # Get active theme to verify we have custom content for it
    local active_theme="FLS-One"  # Default fallback
    if [[ -f "$THEME_CONFIG_FILE" ]]; then
        active_theme=$(jq -r '.active_theme // "FLS-One"' "$THEME_CONFIG_FILE")
        print_info "Active theme from config: $active_theme"
    fi
    
    # Only upload files for the active theme (excluding CPT which was handled above)
    if [[ "$pages_override" == "true" ]]; then
        local active_theme_dir="$pages_dir/themes/$active_theme"
        if [[ -d "$active_theme_dir" ]]; then
            local theme_name="$active_theme"
            print_info "Uploading pages for ACTIVE theme only: $theme_name"
                
                # Validate custom content in key file (check layouts subdirectory)
                local home_layout_file="$active_theme_dir/layouts/home.json"
                if [[ -f "$home_layout_file" ]] && command -v jq &>/dev/null; then
                    local sample_heading=$(jq -r '.widgets[0].heading // "No custom heading found"' "$home_layout_file" 2>/dev/null)
                    if [[ "$sample_heading" != "No custom heading found" && "$sample_heading" != "null" ]]; then
                        print_success "Custom content detected: '$sample_heading'"
                    else
                        print_warning "home.json may not contain custom content for $theme_name"
                    fi
                fi
                
                # Create theme directory structure on server (only for layouts)
                ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p /tmp/pages/$theme_name/layouts"

                # Upload layout files (these are the main theme-specific files)
                local layouts_dir="$active_theme_dir/layouts"
                if [[ -d "$layouts_dir" ]]; then
                    local layout_count=$(find "$layouts_dir" -name "*.json" -type f | wc -l)
                    print_info "Uploading $layout_count layout files for $theme_name"
                    
                    for layout_file in "$layouts_dir"/*.json; do
                        if [[ -f "$layout_file" ]]; then
                            print_info "Uploading layout: $(basename "$layout_file")"
                            if scp -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -P "$KINSTA_PORT" "$layout_file" "${KINSTA_USER}@${KINSTA_HOST}:/tmp/pages/$theme_name/layouts/"; then
                                print_success "Layout $(basename "$layout_file") uploaded for $theme_name"
                            else
                                print_error "Failed to upload layout $(basename "$layout_file") for $theme_name"
                                exit 1
                            fi
                        fi
                    done
                else
                    print_warning "No layouts directory found for $theme_name"
                fi

                # Note: Forms and slides are now handled via common upload directories (theme-agnostic)
                # - Forms: pages/forms/*.json → /tmp/forms/ (processed by forms.sh)
                # - Slides: pages/slides/*.json → /tmp/slides/ (processed by template.sh)
                
                # Upload any remaining JSON files directly in theme directory (legacy page support)
                for page_file in "$active_theme_dir"/*.json; do
                    if [[ -f "$page_file" ]]; then
                        print_info "Uploading legacy file: $(basename "$page_file")"
                        if scp -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -P "$KINSTA_PORT" "$page_file" "${KINSTA_USER}@${KINSTA_HOST}:/tmp/pages/$theme_name/"; then
                            print_success "Legacy file $(basename "$page_file") uploaded for $theme_name"
                        else
                            print_error "Failed to upload legacy file $(basename "$page_file") for $theme_name"
                            exit 1
                        fi
                    fi
                done
        else
            print_warning "Active theme directory not found: $active_theme_dir"
            print_info "Available theme directories:"
            for theme_dir in "$pages_dir/themes"/*; do
                if [[ -d "$theme_dir" ]]; then
                    local dir_name=$(basename "$theme_dir")
                    print_info "  - $dir_name"
                fi
            done
        fi
    else
        print_warning "Pages override is disabled - skipping custom pages upload (theme defaults will be used)"
    fi
    
    # Upload common slides to single server location (theme-agnostic slide management)
    if [[ "$slides_override" == "true" ]]; then
        local common_slides_dir="$pages_dir/slides"
        if [[ -d "$common_slides_dir" ]]; then
            local slide_count=$(find "$common_slides_dir" -name "*.json" -type f | wc -l)
            
            if [[ $slide_count -gt 0 ]]; then
                print_info "Uploading $slide_count common slide files to server"
                
                # Create single slides directory on server
                ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p /tmp/slides"
                
                # Upload all slides to single location
                for slide_file in "$common_slides_dir"/*.json; do
                    if [[ -f "$slide_file" ]]; then
                        print_info "Uploading common slide: $(basename "$slide_file")"
                        
                        # Validate JSON before upload
                        if ! jq empty "$slide_file" 2>/dev/null; then
                            print_error "Invalid JSON in common slide file: $(basename "$slide_file")"
                            exit 1
                        fi

                        if scp -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -P "$KINSTA_PORT" "$slide_file" "${KINSTA_USER}@${KINSTA_HOST}:/tmp/slides/"; then
                            print_success "Common slide $(basename "$slide_file") uploaded"
                        else
                            print_error "Failed to upload common slide $(basename "$slide_file")"
                            exit 1
                        fi
                    fi
                done
            else
                print_info "No slide files found in common slides directory"
            fi
        else
            print_info "No common slides directory found at: $common_slides_dir"
        fi
    else
        print_warning "Slides override is disabled - skipping slides upload (theme defaults will be used)"
    fi
    
    # Upload common forms to single server location (theme-agnostic form management)
    local common_forms_dir="$pages_dir/forms"
    if [[ -d "$common_forms_dir" ]]; then
        local form_count=$(find "$common_forms_dir" -name "*.json" -type f | wc -l)
        
        if [[ $form_count -gt 0 ]]; then
            print_info "Uploading $form_count common form files to server"
            
            # Create forms directory on server (upload to /tmp/forms for better organization)
            ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p /tmp/forms"
            
            # Upload all forms to server
            for form_file in "$common_forms_dir"/*.json; do
                if [[ -f "$form_file" ]]; then
                    print_info "Uploading common form: $(basename "$form_file")"
                    
                    # Validate JSON before upload
                    if ! jq empty "$form_file" 2>/dev/null; then
                        print_error "Invalid JSON in common form file: $(basename "$form_file")"
                        exit 1
                    fi

                    if scp -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -P "$KINSTA_PORT" "$form_file" "${KINSTA_USER}@${KINSTA_HOST}:/tmp/forms/"; then
                        print_success "Common form $(basename "$form_file") uploaded"
                    else
                        print_error "Failed to upload common form $(basename "$form_file")"
                        exit 1
                    fi
                fi
            done
        else
            print_info "No form files found in common forms directory"
        fi
    else
        print_info "No common forms directory found at: $common_forms_dir"
    fi
}

upload_images() {
    local uploads_dir="$ROOT_DIR/uploads/images"
    
    if [[ ! -d "$uploads_dir" ]]; then
        print_warning "No uploads directory found at $uploads_dir"
        return
    fi
    
    print_info "Uploading images and logos..."
    
    # Get active theme from config for logo detection
    local active_theme="FLS-One"  # Default
    if [[ -f "$THEME_CONFIG_FILE" ]]; then
        active_theme=$(jq -r '.active_theme // "FLS-One"' "$THEME_CONFIG_FILE")
        print_info "Active theme detected: $active_theme"
    fi
    
    # Get logo filename from config.json
    local current_logo=""
    local config_file="$ROOT_DIR/config/config.json"
    
    if [[ -f "$config_file" ]]; then
        current_logo=$(jq -r '.site.logo // empty' "$config_file" 2>/dev/null)
        if [[ -n "$current_logo" && "$current_logo" != "null" ]]; then
            print_info "Logo from config.json: $current_logo"
        fi
    fi
    
    # If no logo in config, fallback to page files
    if [[ -z "$current_logo" || "$current_logo" == "null" ]]; then
        for page_file in "$ROOT_DIR/pages/themes/$active_theme/layouts/header.json" "$ROOT_DIR/pages/themes/$active_theme/layouts/footer.json" "$ROOT_DIR/pages/themes/$active_theme/layouts/home.json"; do
            if [[ -f "$page_file" ]]; then
                current_logo=$(jq -r '.global_logo // empty' "$page_file" 2>/dev/null)
                if [[ -n "$current_logo" && "$current_logo" != "null" ]]; then
                    print_info "Logo from page file: $current_logo"
                    break
                fi
            fi
        done
    fi
    
    # Upload current logo preserving file extension
    # current_logo may be a bare filename or a path — accept both
    # Normalize current_logo to a filename (accept bare filename or path)
    if [[ -n "$current_logo" ]]; then
        if [[ -f "$uploads_dir/$current_logo" ]]; then
            : # keep as-is
        elif [[ -f "$uploads_dir/$(basename "$current_logo")" ]]; then
            current_logo="$(basename "$current_logo")"
        fi
    fi

    if [[ -n "$current_logo" && -f "$uploads_dir/$current_logo" ]]; then
        # Get file extension
        local logo_ext="${current_logo##*.}"
        local logo_target="/tmp/logo.$logo_ext"
        
        print_info "Uploading current logo: $current_logo"
        print_info "Logo file size: $(stat -f%z "$uploads_dir/$current_logo" 2>/dev/null || echo "unknown") bytes"
        print_info "Logo extension: $logo_ext"
        print_info "SSH key file: $HOME/.ssh/id_rsa (exists: $(test -f $HOME/.ssh/id_rsa && echo "yes" || echo "no"))"
        
        if scp -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -P "$KINSTA_PORT" -v "$uploads_dir/$current_logo" "${KINSTA_USER}@${KINSTA_HOST}:$logo_target" 2>&1; then
            print_success "Logo uploaded successfully: $current_logo -> $logo_target"
        else
            scp_exit_code=$?
            print_error "Failed to upload logo: $current_logo (exit code: $scp_exit_code)"
            print_error "Upload directory: $uploads_dir"
            print_error "Logo file path: $uploads_dir/$current_logo"
            print_error "Target: ${KINSTA_USER}@${KINSTA_HOST}:$logo_target"
            exit 1
        fi
    else
        print_warning "No current logo found to upload"
        print_info "Checked directory: $uploads_dir"
        print_info "Logo pattern searched: logo_*"
        ls -la "$uploads_dir"/logo_* 2>/dev/null || print_info "No logo files found matching pattern"
    fi
    
    # Upload all files recursively to server uploads directory
    print_info "Uploading all files recursively from framework-interface/uploads/images..."
    
    # Create uploads directory on server  
    ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p "$KINSTA_PORT" "${KINSTA_USER}@${KINSTA_HOST}" "mkdir -p /tmp/uploads"
    
    # Upload all files recursively via rsync for efficiency
    if find "$uploads_dir" -type f -print -quit | grep -q .; then
        file_count=$(find "$uploads_dir" -type f | wc -l)
        print_info "Found $file_count files to upload (all file types, recursive)"
        
        if rsync -azv -e "ssh -o StrictHostKeyChecking=no -i $HOME/.ssh/id_rsa -p $KINSTA_PORT" \
            "$uploads_dir/" "${KINSTA_USER}@${KINSTA_HOST}:/tmp/uploads/" 2>&1; then
            print_success "All files uploaded successfully ($file_count files, recursive)"
        else
            rsync_exit_code=$?
            print_error "Failed to upload files (exit code: $rsync_exit_code)"
            print_error "Source directory: $uploads_dir"
            print_error "Target: ${KINSTA_USER}@${KINSTA_HOST}:/tmp/uploads/"
            print_error "File count attempted: $file_count"
            exit 1
        fi
    else
        print_info "No files found to upload"
        print_info "Checked directory: $uploads_dir"
        print_info "Directory contents:"
        ls -la "$uploads_dir" 2>/dev/null || print_info "Directory not accessible"
    fi
}

trigger_workflow() {
    local force_deploy="$1"

    print_info "Preparing workflow dispatch..."
    
    # Build inputs object - only include force_deploy, GA ID is handled via config files
    local inputs="{}"
    if [[ "$force_deploy" == "true" ]]; then
        inputs=$(echo "$inputs" | jq '. + {force_deploy: true}')
        print_warning "Force deployment enabled"
    fi

    # Note: ga_measurement_id is not included as it's already in config files
    print_info "Google Analytics ID will be read from config files during deployment"

    local payload
    payload=$(jq -n --arg ref "$BRANCH" --argjson inputs "$inputs" '{ref: $ref, inputs: $inputs}')

    print_info "Triggering workflow on branch: $BRANCH"
    log_debug "Workflow payload: $payload"

    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/workflows/$WORKFLOW_ID/dispatches" "POST" "$payload" false)

    # GitHub workflow dispatch returns empty response on success (204 No Content)
    if [[ -z "$response" || "$response" == "null" || "$response" == "" ]]; then
        log_api "workflow_dispatch" "204" "Workflow triggered successfully"
        print_success "Workflow triggered successfully!"
        echo -e "\nView the workflow run at:\n  https://github.com/$GITHUB_OWNER/$GITHUB_REPO/actions\n"
        
        # Wait a moment and try to fetch the newly created run
        sleep 3
        print_info "Fetching latest workflow run status..."
        
        # Capture the run ID for monitoring
        capture_and_store_run_id
        
        get_latest_run_status
    else
        log_api "workflow_dispatch" "400" "Failed to trigger workflow: $response"
        print_error "Failed to trigger workflow"
        echo "Response: $response" >&2
        
        # Try to parse the error response
        if echo "$response" | jq -e '.message' >/dev/null 2>&1; then
            local error_msg
            error_msg=$(echo "$response" | jq -r '.message')
            print_error "GitHub API Error: $error_msg"
        fi
        
        exit 1
    fi
}

get_latest_run_status() {
    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/workflows/$WORKFLOW_ID/runs" "GET" '{"per_page": 1}' false)

    if echo "$response" | jq -e '.workflow_runs[0]' >/dev/null 2>&1; then
        log_api "get_run_status" "200" "Retrieved latest workflow run status"
        local latest_run
        latest_run=$(echo "$response" | jq -r '.workflow_runs[0]')
        local status=$(echo "$latest_run" | jq -r '.status')
        local conclusion=$(echo "$latest_run" | jq -r '.conclusion // "running"')
        local html_url=$(echo "$latest_run" | jq -r '.html_url')
        local created_at=$(echo "$latest_run" | jq -r '.created_at')
        
        echo -e "\nLatest workflow run:"
        echo "  Status: $status"
        echo "  Result: $conclusion"
        echo "  Started: $created_at"
        echo "  URL: $html_url"
        
        # Log workflow status
        print_info "Workflow Status: $status | Result: $conclusion"
    else
        log_api "get_run_status" "404" "Could not fetch latest run status"
        print_warning "Could not fetch latest run status"
    fi
}

capture_and_store_run_id() {
    print_info "Capturing workflow run ID for monitoring..."
    
    # Get the most recent run (should be the one we just triggered)
    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/workflows/$WORKFLOW_ID/runs" "GET" '{"per_page": 1}' false)

    if echo "$response" | jq -e '.workflow_runs[0]' >/dev/null 2>&1; then
        local latest_run
        latest_run=$(echo "$response" | jq -r '.workflow_runs[0]')
        local run_id=$(echo "$latest_run" | jq -r '.id')
        local created_at=$(echo "$latest_run" | jq -r '.created_at')
        
        # Check if this run was created very recently (within last 2 minutes)
        local created_timestamp=$(date -d "$created_at" +%s 2>/dev/null || date -j -f "%Y-%m-%dT%H:%M:%SZ" "$created_at" +%s 2>/dev/null)
        local current_timestamp=$(date +%s)
        local time_diff=$((current_timestamp - created_timestamp))
        
        if [[ $time_diff -le 120 ]]; then
            # This looks like our run, store it
            mkdir -p tmp
            echo "$run_id" > tmp/github_run_id.txt
            print_success "Captured workflow run ID: $run_id"
            print_info "Monitoring this specific run for GitHub Actions status"
        else
            print_warning "Latest run seems too old ($time_diff seconds), might not be the one we just triggered"
            # Store it anyway, but with a warning
            mkdir -p tmp
            echo "$run_id" > tmp/github_run_id.txt
        fi
    else
        print_warning "Could not capture workflow run ID - will monitor latest runs"
        # Clear any existing run ID file
        rm -f tmp/github_run_id.txt
    fi
}

get_recent_runs() {
    print_info "Fetching recent workflow runs..."
    local response
    response=$(api_request "github" "repos/$GITHUB_OWNER/$GITHUB_REPO/actions/workflows/$WORKFLOW_ID/runs" "GET" '{"per_page": 3}' false)

    if echo "$response" | jq -e '.workflow_runs' >/dev/null 2>&1; then
        echo -e "\nRecent workflow runs:"
        echo "$response" | jq -r '.workflow_runs[] | "  • \(.created_at) - \(.status) (\(.conclusion // "running")) - \(.html_url)"'
    else
        print_warning "Could not fetch recent runs"
        echo "Response: $response" >&2
    fi
}

show_help() {
    cat <<EOF
GitHub Actions Workflow Trigger

Configuration: $CONFIG_FILE
API Functions: $API_SCRIPT

Usage: $0 [FORCE_DEPLOY] [--recent]

Parameters:
  FORCE_DEPLOY       Optional force deployment flag (true/false, default: false)
  --recent           Optional flag to show recent workflow runs after triggering.

Environment Variables:
  GITHUB_TOKEN       Required GitHub Personal Access Token.

Examples:
  $0                           # Basic deployment
  $0 true                      # Force deployment
  $0 --recent                  # Basic deployment and show recent runs
  $0 true --recent             # Force deployment and show recent runs
EOF
    exit 0
}

main() {
    local force_deploy="false"
    local show_recent="false"

    for arg in "$@"; do
        case "$arg" in
        --help | -h)
            show_help
            ;;
        true | false)
            force_deploy="$arg"
            ;;
        --recent)
            show_recent="true"
            ;;
        *)
            print_error "Invalid argument: '$arg'"
            show_help
            ;;
        esac
    done

    # Initialize logging session
    init_logging "GitHub Actions Deployment"

    echo "=============================================================================="
    echo "GitHub Actions Workflow Trigger & Config Upload"
    echo "Repository: $GITHUB_OWNER/$GITHUB_REPO"
    echo "Workflow:   $WORKFLOW_ID"
    echo "=============================================================================="
    echo
    
    # Trap errors to log failures
    trap 'log_step_failed "Deployment" "Script terminated unexpectedly"; end_deployment_session "FAILED"; exit 1' ERR
    
    # Generate new admin password before uploading configs (each deployment gets unique credentials)
    print_info "Preparing security credentials..."
    generate_and_update_admin_password
    echo
    
    log_step_start "STEP 1/6: Uploading configuration files"
    upload_configs
    log_step_complete "Configuration files upload"

    log_step_start "STEP 2/6: Uploading custom page layouts and CPT"
    upload_pages
    log_step_complete "Page layouts and CPT upload"
    
    log_step_start "STEP 3/6: Uploading images and logo"
    upload_images
    log_step_complete "Images and logo upload"

    log_step_start "STEP 4/6: Validating GitHub access"
    check_github_token
    validate_github_token
    check_repo_access
    log_step_complete "GitHub access validation"
    
    log_step_start "STEP 5/6: Triggering GitHub Actions deployment"
    trigger_workflow "$force_deploy"
    log_step_complete "GitHub Actions workflow trigger"

    log_step_start "STEP 6/6: Monitoring deployment status"
    if [[ "$show_recent" == "true" ]]; then
        get_recent_runs
    fi
    log_step_complete "Deployment status monitoring"

    echo
    print_success "Deployment triggered successfully!"
    echo "=============================================================================="
    
    # End deployment session with success status
    end_deployment_session "SUCCESS"
}

main "$@"
