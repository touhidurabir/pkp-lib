#!/bin/bash
# Analyze core PKP library classes only
# Usage: ./analyze-core.sh [additional phpstan options]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PKP_DIR"

echo "=== Analyzing Core PKP Classes ==="
echo "Path: classes/"
echo ""

php -d memory_limit=2G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --memory-limit=2G \
    classes/ \
    "$@"

echo ""
echo "✓ Core classes analysis complete"
