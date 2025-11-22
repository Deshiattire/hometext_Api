#!/bin/bash
# API Health Check Runner for Linux/Mac
# Usage: ./run_tests.sh [base_url] [admin_email] [admin_password] [user_email] [user_password]

BASE_URL="${1:-http://localhost:8000/api}"
ADMIN_EMAIL="${2:-}"
ADMIN_PASSWORD="${3:-}"
USER_EMAIL="${4:-}"
USER_PASSWORD="${5:-}"

cd "$(dirname "$0")/.."

php api_check/test_all_apis.php \
    --base-url="$BASE_URL" \
    --admin-email="$ADMIN_EMAIL" \
    --admin-password="$ADMIN_PASSWORD" \
    --user-email="$USER_EMAIL" \
    --user-password="$USER_PASSWORD"

