#!/bin/bash
# Analyze API endpoints (PKP + Application)
# Usage: ./analyze-api.sh [additional phpstan options]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PKP_DIR"

echo "=== Analyzing API Endpoints ==="
echo "Paths: api/, ../../api/"
echo ""

php -d memory_limit=1G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --memory-limit=1G \
    api/ \
    ../../api/ \
    "$@"

echo ""
echo "✓ API analysis complete"
