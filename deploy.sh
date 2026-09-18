#!/bin/bash

#######################################
# FAMILY HUB Deployment Script
#
# Usage: ./deploy.sh <branch> [options]
#
# Branch: any valid git branch (develop, main, feature/xxx)
#
# Options:
#   --clean-volumes    Also remove data volumes (CAUTION: destroys database!)
#   --skip-pull        Skip git pull (use current code)
#   --help             Show this help
#
# Examples:
#   ./deploy.sh main
#   ./deploy.sh develop
#   ./deploy.sh main --clean-volumes
#######################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Fixed configuration (only production)
ENVIRONMENT="prod"
COMPOSE_FILE="docker-compose.prod.yml"
ENV_EXAMPLE=".env.prod.example"
CONTAINER_PREFIX="family_prod"
PROJECT_NAME="family-prod"

# Default options
CLEAN_VOLUMES=false
SKIP_PULL=false

#######################################
# Logging functions
#######################################
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "\n${GREEN}==>${NC} ${YELLOW}$1${NC}"
}

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
        log_error "Missing required argument: branch"
        echo "Usage: $0 <branch> [options]"
        echo "Run '$0 --help' for more information"
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
                CLEAN_VOLUMES=true
                shift
                ;;
            --skip-pull)
                SKIP_PULL=true
                shift
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
}

#######################################
# Check prerequisites
#######################################
check_prerequisites() {
    log_step "Checking prerequisites"

    # Check Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    log_success "Docker is available"

    # Check Docker Compose
    if ! docker compose version &> /dev/null; then
        log_error "Docker Compose is not available"
        exit 1
    fi
    log_success "Docker Compose is available"

    # Check Git
    if ! command -v git &> /dev/null; then
        log_error "Git is not installed"
        exit 1
    fi
    log_success "Git is available"

    # Check compose file exists
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        log_error "Compose file not found: $COMPOSE_FILE"
        exit 1
    fi
    log_success "Compose file exists: $COMPOSE_FILE"

    # Check env example exists
    if [[ ! -f "$ENV_EXAMPLE" ]]; then
        log_error "Environment file not found: $ENV_EXAMPLE"
        exit 1
    fi
    log_success "Environment template exists: $ENV_EXAMPLE"

    # Check proxy network exists
    if ! docker network ls | grep -q "proxy"; then
        log_warning "Proxy network not found. Creating it..."
        docker network create proxy
        log_success "Proxy network created"
    else
        log_success "Proxy network exists"
    fi
}

#######################################
# Git operations
#######################################
git_update() {
    log_step "Updating repository"

    if [[ "$SKIP_PULL" == true ]]; then
        log_warning "Skipping git pull (--skip-pull)"
        return
    fi

    # Fetch all branches
    log_info "Fetching remote branches..."
    git fetch --all --prune

    # Check if branch exists
    if ! git rev-parse --verify "origin/$BRANCH" &> /dev/null; then
        # Try local branch
        if ! git rev-parse --verify "$BRANCH" &> /dev/null; then
            log_error "Branch not found: $BRANCH"
            exit 1
        fi
    fi

    # Checkout and pull
    log_info "Checking out branch: $BRANCH"
    git checkout "$BRANCH"

    # Pull if tracking remote
    if git rev-parse --abbrev-ref --symbolic-full-name @{u} &> /dev/null; then
        log_info "Pulling latest changes..."
        git pull
    fi

    # Sync filesystem
    log_info "Syncing filesystem..."
    sync

    log_success "Repository updated to branch: $BRANCH"
}

#######################################
# Stop and remove containers
#######################################
cleanup_containers() {
    log_step "Cleaning up containers"

    # Stop containers using compose
    log_info "Stopping containers..."
    docker compose -f "$COMPOSE_FILE" -p "$PROJECT_NAME" down --remove-orphans 2>/dev/null || true

    # Force remove any remaining containers with the prefix
    local containers=$(docker ps -a --filter "name=${CONTAINER_PREFIX}" --format "{{.ID}}" 2>/dev/null)
    if [[ -n "$containers" ]]; then
        log_info "Removing remaining containers..."
        echo "$containers" | xargs -r docker rm -f 2>/dev/null || true
    fi

    log_success "Containers cleaned up"
}

#######################################
# Remove images
#######################################
cleanup_images() {
    log_step "Cleaning up images"

    # Get image names from compose file
    local images=$(docker compose -f "$COMPOSE_FILE" -p "$PROJECT_NAME" config --images 2>/dev/null | grep "$PROJECT_NAME" || true)

    if [[ -n "$images" ]]; then
        log_info "Removing project images..."
        echo "$images" | xargs -r docker rmi -f 2>/dev/null || true
    fi

    # Also remove any dangling images
    log_info "Removing dangling images..."
    docker image prune -f 2>/dev/null || true

    log_success "Images cleaned up"
}

#######################################
# Clean volumes (optional)
#######################################
cleanup_volumes() {
    if [[ "$CLEAN_VOLUMES" != true ]]; then
        log_warning "Volumes preserved (use --clean-volumes to remove)"
        return
    fi

    log_step "Cleaning up volumes (DATA WILL BE LOST!)"

    read -p "Are you sure you want to delete all data volumes? [y/N] " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_warning "Volume cleanup cancelled"
        return
    fi

    # Remove volumes using compose
    docker compose -f "$COMPOSE_FILE" -p "$PROJECT_NAME" down -v 2>/dev/null || true

    # Remove any remaining volumes with the prefix
    local volumes=$(docker volume ls --filter "name=${CONTAINER_PREFIX}" --format "{{.Name}}" 2>/dev/null)
    if [[ -n "$volumes" ]]; then
        echo "$volumes" | xargs -r docker volume rm 2>/dev/null || true
    fi

    log_success "Volumes cleaned up"
}

#######################################
# Setup environment file
#######################################
setup_env() {
    log_step "Setting up environment"

    # Backup existing .env if it exists and is different
    if [[ -f ".env" ]]; then
        if ! diff -q ".env" "$ENV_EXAMPLE" &>/dev/null; then
            local backup=".env.backup.$(date +%Y%m%d_%H%M%S)"
            cp ".env" "$backup"
            log_info "Backed up existing .env to $backup"
        fi
    fi

    # Copy environment template only if there is no real .env yet.
    # On the server the real secrets live in .env; we never overwrite them.
    if [[ ! -f ".env" ]]; then
        cp "$ENV_EXAMPLE" ".env"
        log_success "Environment file created from $ENV_EXAMPLE"
        log_warning "Edit .env with the real secrets (APP_KEY, DB_PASSWORD, MAIL_*) before going live"
    else
        log_info "Existing .env kept (edit it manually to change secrets)"
    fi

    # Check if APP_KEY is empty
    if grep -q "^APP_KEY=$" ".env"; then
        log_warning "APP_KEY is empty - will be generated after container starts"
    fi
}

#######################################
# Build and start containers
#######################################
build_and_start() {
    log_step "Building and starting containers"

    # Clear Docker builder cache
    log_info "Clearing Docker builder cache..."
    docker builder prune -f 2>/dev/null || true

    # Build with no cache
    log_info "Building images (no cache, pulling fresh base images)..."
    docker compose -f "$COMPOSE_FILE" -p "$PROJECT_NAME" build --no-cache --pull

    # Start containers
    log_info "Starting containers..."
    docker compose -f "$COMPOSE_FILE" -p "$PROJECT_NAME" up -d

    # Wait for containers to be healthy
    log_info "Waiting for containers to be ready..."
    sleep 10

    log_success "Containers started"
}

#######################################
# Laravel setup
#######################################
laravel_setup() {
    log_step "Configuring Laravel"

    local app_container="${CONTAINER_PREFIX}_app"

    # Wait for container to be ready
    local max_attempts=30
    local attempt=0
    while ! docker exec "$app_container" php -v &>/dev/null; do
        attempt=$((attempt + 1))
        if [[ $attempt -ge $max_attempts ]]; then
            log_error "Container $app_container is not responding"
            exit 1
        fi
        log_info "Waiting for PHP container... ($attempt/$max_attempts)"
        sleep 2
    done

    # Copy .env to container
    log_info "Copying .env to container..."
    docker cp .env "$app_container":/var/www/.env
    docker exec "$app_container" chown www-data:www-data /var/www/.env
    log_success ".env copied to container"

    # Copy built assets from container to host (nginx mounts host's public folder)
    log_info "Copying built assets to host..."
    rm -rf public/build
    mkdir -p public
    docker cp "$app_container":/var/www/public/build public/build 2>/dev/null || log_warning "No build assets found (may be normal for first deploy)"
    log_success "Assets copied to host"

    # Generate APP_KEY if empty
    if grep -q "^APP_KEY=$" .env 2>/dev/null; then
        log_info "Generating APP_KEY..."
        docker exec "$app_container" php artisan key:generate --force
        # Copy updated .env back to host
        docker cp "$app_container":/var/www/.env .env
        log_success "APP_KEY generated"
    fi

    # Clear and rebuild caches
    log_info "Clearing caches..."
    docker exec "$app_container" php artisan optimize:clear

    # Create MongoDB indexes (project-specific command)
    log_info "Creating MongoDB indexes..."
    docker exec "$app_container" php artisan mongo:indexes || log_warning "mongo:indexes failed (check DB connection)"

    # Seed the shopping catalog (idempotent: truncates and re-creates)
    log_info "Seeding shopping catalog..."
    docker exec "$app_container" php artisan db:seed --class=ShoppingItemSeeder --force || log_warning "ShoppingItemSeeder failed"

    # Set permissions
    log_info "Setting permissions..."
    docker exec "$app_container" chown -R www-data:www-data storage bootstrap/cache
    docker exec "$app_container" chmod -R 775 storage bootstrap/cache

    # Create storage link if not exists
    log_info "Creating storage link..."
    docker exec "$app_container" php artisan storage:link 2>/dev/null || true

    log_success "Laravel configured"
}

#######################################
# Show status
#######################################
show_status() {
    log_step "Deployment Status"

    echo ""
    echo "Environment: production"
    echo "Branch: $BRANCH"
    echo "Compose file: $COMPOSE_FILE"
    echo ""

    log_info "Running containers:"
    docker compose -f "$COMPOSE_FILE" -p "$PROJECT_NAME" ps

    echo ""

    # Get domain from .env
    local domain=$(grep "^APP_DOMAIN=" .env 2>/dev/null | cut -d'=' -f2)
    if [[ -n "$domain" ]]; then
        log_success "Application available at:"
        echo "  - HTTPS: https://$domain"
    else
        log_warning "APP_DOMAIN not set in .env"
        echo "  Configure APP_DOMAIN in .env and restart"
    fi
}

#######################################
# Main execution
#######################################
main() {
    echo ""
    echo "========================================"
    echo "   FAMILY HUB Deployment Script"
    echo "========================================"
    echo ""

    # Handle help flag early
    if [[ "$1" == "--help" ]]; then
        show_help
    fi

    parse_args "$@"

    log_info "Starting deployment: production environment, branch: $BRANCH"

    if [[ "$CLEAN_VOLUMES" == true ]]; then
        log_warning "CLEAN_VOLUMES is enabled - database will be reset!"
    fi

    echo ""
    read -p "Continue with deployment? [Y/n] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        log_warning "Deployment cancelled"
        exit 0
    fi

    check_prerequisites
    cleanup_containers
    cleanup_images
    cleanup_volumes
    git_update
    setup_env
    build_and_start
    laravel_setup
    show_status

    echo ""
    log_success "Deployment completed successfully!"
    echo ""
}

# Run main function
main "$@"
