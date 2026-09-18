#!/bin/bash

#######################################
# FAMILY HUB Remote Deployment Script
#
# Executes deployment on a remote server via SSH
#
# Usage: ./deploy-remote.sh <branch> [options]
#
# Options:
#   --clean-volumes    Also remove data volumes
#   --skip-pull        Skip git pull
#   --server <host>    Override server host (default: $DEFAULT_SERVER)
#   --user <user>      Override SSH user (default: root)
#   --port <port>      Override SSH port (default: 22)
#   --help             Show this help
#
# Examples:
#   ./deploy-remote.sh main
#   ./deploy-remote.sh develop
#   ./deploy-remote.sh main --server 192.168.1.100
#######################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Default configuration
DEFAULT_SERVER="CHANGE_ME_SERVER_IP"
DEFAULT_USER="root"
DEFAULT_PORT="22"
REMOTE_PATH="/opt/family"

# Variables
BRANCH=""
SERVER="$DEFAULT_SERVER"
SSH_USER="$DEFAULT_USER"
SSH_PORT="$DEFAULT_PORT"
DEPLOY_OPTIONS=""

#######################################
# Logging
#######################################
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

#######################################
# Show help
#######################################
show_help() {
    head -20 "$0" | tail -17
    exit 0
}

#######################################
# Parse arguments
#######################################
parse_args() {
    if [[ $# -lt 1 ]]; then
        if [[ "$1" == "--help" ]]; then
            show_help
        fi
        log_error "Missing required argument: branch"
        echo "Usage: $0 <branch> [options]"
        exit 1
    fi

    # Check for help flag
    if [[ "$1" == "--help" ]]; then
        show_help
    fi

    BRANCH="$1"
    shift

    # Parse options
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --clean-volumes)
                DEPLOY_OPTIONS="$DEPLOY_OPTIONS --clean-volumes"
                shift
                ;;
            --skip-pull)
                DEPLOY_OPTIONS="$DEPLOY_OPTIONS --skip-pull"
                shift
                ;;
            --server)
                SERVER="$2"
                shift 2
                ;;
            --user)
                SSH_USER="$2"
                shift 2
                ;;
            --port)
                SSH_PORT="$2"
                shift 2
                ;;
            --help)
                show_help
                ;;
            *)
                log_error "Unknown option: $1"
                exit 1
                ;;
        esac
    done

    # Validate server is configured
    if [[ "$SERVER" == "CHANGE_ME_SERVER_IP" || -z "$SERVER" ]]; then
        log_error "Server host not configured. Set DEFAULT_SERVER in this script or pass --server <host>"
        exit 1
    fi
}

#######################################
# Check SSH connectivity
#######################################
check_connection() {
    log_info "Checking connection to $SSH_USER@$SERVER..."

    if ! ssh -o ConnectTimeout=10 -o BatchMode=yes -p "$SSH_PORT" "$SSH_USER@$SERVER" "echo 'OK'" &>/dev/null; then
        log_warning "SSH key authentication not available, will use password"
    else
        log_success "SSH connection verified"
    fi
}

#######################################
# Sync deploy script to server
#######################################
sync_script() {
    log_info "Syncing deploy script to server..."

    local script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

    scp -P "$SSH_PORT" "$script_dir/deploy.sh" "$SSH_USER@$SERVER:$REMOTE_PATH/deploy.sh"
    ssh -p "$SSH_PORT" "$SSH_USER@$SERVER" "chmod +x $REMOTE_PATH/deploy.sh"

    log_success "Deploy script synced"
}

#######################################
# Execute remote deployment
#######################################
remote_deploy() {
    log_info "Executing remote deployment..."

    echo ""
    echo "========================================"
    echo "   Remote Deployment: FAMILY HUB"
    echo "   Server: $SERVER"
    echo "   Branch: $BRANCH"
    echo "========================================"
    echo ""

    # Execute deployment on remote server
    ssh -t -p "$SSH_PORT" "$SSH_USER@$SERVER" \
        "cd $REMOTE_PATH && ./deploy.sh $BRANCH $DEPLOY_OPTIONS"
}

#######################################
# Main
#######################################
main() {
    echo ""
    echo "========================================"
    echo "   FAMILY HUB Remote Deployment"
    echo "========================================"
    echo ""

    parse_args "$@"

    log_info "Target: production on $SERVER"
    log_info "Branch: $BRANCH"

    if [[ -n "$DEPLOY_OPTIONS" ]]; then
        log_info "Options: $DEPLOY_OPTIONS"
    fi

    echo ""
    read -p "Continue with remote deployment? [Y/n] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        log_warning "Deployment cancelled"
        exit 0
    fi

    check_connection
    sync_script
    remote_deploy

    echo ""
    log_success "Remote deployment completed!"

    # Show access URL
    echo ""
    echo "Access the application at the configured domain"
    echo "(check APP_DOMAIN in .env on the server)"
}

main "$@"
