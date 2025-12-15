#!/bin/bash
# Analyze controllers and pages (PKP + Application)
# Usage: ./analyze-controllers.sh [additional phpstan options]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PKP_DIR"

echo "=== Analyzing Controllers and Pages ==="
echo "Paths: controllers/, pages/, ../../controllers/, ../../pages/"
echo ""

php -d memory_limit=1G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --memory-limit=1G \
    controllers/ \
    pages/ \
    ../../controllers/ \
    ../../pages/ \
    "$@"

echo ""
echo "✓ Controllers and pages analysis complete"
