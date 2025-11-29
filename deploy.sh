#!/bin/bash

# Laravel Deployment Script
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DEPLOY_PATH="${DEPLOY_PATH:-$(pwd)}"
ENVIRONMENT="${1:-production}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Starting Deployment${NC}"
echo -e "${GREEN}Environment: ${ENVIRONMENT}${NC}"
echo -e "${GREEN}Deploy Path: ${DEPLOY_PATH}${NC}"
echo -e "${GREEN}========================================${NC}"

# Change to deployment directory
cd "$DEPLOY_PATH" || exit 1

# Step 1: Pull latest code from git
echo -e "\n${YELLOW}[1/6] Pulling latest code from git...${NC}"
if [ -d .git ]; then
    git pull origin main || git pull origin master || {
        echo -e "${RED}Git pull failed${NC}"
        exit 1
    }
    echo -e "${GREEN}✓ Code pulled successfully${NC}"
else
    echo -e "${YELLOW}⚠ Not a git repository, skipping git pull${NC}"
fi

# Step 2: Install/Update Composer dependencies
echo -e "\n${YELLOW}[2/6] Installing Composer dependencies...${NC}"
if command -v composer >/dev/null 2>&1; then
    echo "Using system composer"
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader || {
        echo -e "${RED}Composer install failed${NC}"
        exit 1
    }
elif [ -f ./composer.phar ]; then
    echo "Using local composer.phar"
    php composer.phar install --no-dev --prefer-dist --no-interaction --optimize-autoloader || {
        echo -e "${RED}Composer install failed${NC}"
        exit 1
    }
else
    echo -e "${YELLOW}⚠ Composer not found, skipping${NC}"
fi
echo -e "${GREEN}✓ Composer dependencies installed${NC}"

# Step 3: Clear Laravel caches
echo -e "\n${YELLOW}[3/6] Clearing Laravel caches...${NC}"
if [ -f artisan ]; then
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
    php artisan event:clear || true
    echo -e "${GREEN}✓ Caches cleared${NC}"
else
    echo -e "${YELLOW}⚠ artisan not found, skipping cache clear${NC}"
fi

# Step 4: Cache Laravel configs (for production)
echo -e "\n${YELLOW}[4/6] Caching Laravel configuration...${NC}"
if [ -f artisan ]; then
    if [ "$ENVIRONMENT" = "production" ]; then
        php artisan config:cache || true
        php artisan route:cache || true
        php artisan view:cache || true
        echo -e "${GREEN}✓ Configuration cached${NC}"
    else
        echo -e "${YELLOW}⚠ Skipping cache (not production environment)${NC}"
    fi
else
    echo -e "${YELLOW}⚠ artisan not found, skipping cache${NC}"
fi

# Step 5: Run database migrations (optional - uncomment if needed)
# echo -e "\n${YELLOW}[5/6] Running database migrations...${NC}"
# if [ -f artisan ]; then
#     php artisan migrate --force || {
#         echo -e "${RED}Migration failed${NC}"
#         exit 1
#     }
#     echo -e "${GREEN}✓ Migrations completed${NC}"
# fi

# Step 5: Set proper permissions (or Step 6 if migrations are enabled)
echo -e "\n${YELLOW}[5/6] Setting file permissions...${NC}"
if [ -d storage ]; then
    chmod -R ug+rw storage bootstrap/cache || true
    echo -e "${GREEN}✓ Permissions set${NC}"
else
    echo -e "${YELLOW}⚠ storage directory not found${NC}"
fi

# Step 6: Final checks
echo -e "\n${YELLOW}[6/6] Running final checks...${NC}"
if [ -f artisan ]; then
    php artisan about || true
fi
echo -e "${GREEN}✓ Final checks completed${NC}"

# Success message
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment Completed Successfully!${NC}"
echo -e "${GREEN}========================================${NC}"

