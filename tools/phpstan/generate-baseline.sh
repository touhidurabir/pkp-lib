#!/bin/bash
# Generate PHPStan baseline file
# Usage: ./generate-baseline.sh [baseline-filename]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PKP_DIR"

BASELINE_FILE="${1:-phpstan-baseline.neon}"

echo "=== Generating PHPStan Baseline ==="
echo "Output: $BASELINE_FILE"
echo "This may take several minutes..."
echo ""

php -d memory_limit=2G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --generate-baseline="$BASELINE_FILE" \
    --memory-limit=2G

echo ""
echo "✓ Baseline generated: $BASELINE_FILE"
echo ""
echo "Next steps:"
echo "  1. Uncomment the baseline include in phpstan.neon:"
echo "     includes:"
echo "       - $BASELINE_FILE"
echo ""
echo "  2. Run analysis to verify baseline works:"
echo "     php lib/vendor/bin/phpstan analyse"
echo ""
echo "  3. Commit the baseline file to version control"
