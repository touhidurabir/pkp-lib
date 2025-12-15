#!/bin/bash
# Analyze Laravel jobs (PKP + Application)
# Usage: ./analyze-jobs.sh [additional phpstan options]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PKP_DIR"

echo "=== Analyzing Laravel Jobs ==="
echo "Paths: jobs/, ../../jobs/"
echo ""

php -d memory_limit=1G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --memory-limit=1G \
    jobs/ \
    ../../jobs/ \
    "$@"

echo ""
echo "✓ Jobs analysis complete"
