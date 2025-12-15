#!/bin/bash
# Generate comprehensive PHPStan reports
# Usage: ./generate-report.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
REPORT_DIR="$PKP_DIR/../../cache/phpstan-reports"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mkdir -p "$REPORT_DIR"

cd "$PKP_DIR"

echo "=== Generating PHPStan Reports ==="
echo "Output directory: $REPORT_DIR"
echo "Timestamp: $TIMESTAMP"
echo ""

# JSON report (machine-readable)
echo "Generating JSON report..."
php -d memory_limit=2G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --error-format=json \
    --no-progress \
    --memory-limit=2G \
    > "$REPORT_DIR/report_${TIMESTAMP}.json" 2>&1 || true

# Table report (human-readable)
echo "Generating table report..."
php -d memory_limit=2G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --error-format=table \
    --memory-limit=2G \
    > "$REPORT_DIR/report_${TIMESTAMP}.txt" 2>&1 || true

# Extract summary if jq is available
if command -v jq &> /dev/null; then
    TOTAL_ERRORS=$(jq -r '.totals.file_errors // 0' "$REPORT_DIR/report_${TIMESTAMP}.json" 2>/dev/null || echo "unknown")
else
    TOTAL_ERRORS="unknown (jq not installed)"
fi

echo ""
echo "✓ Reports generated successfully"
echo ""
echo "Summary:"
echo "  - JSON report: report_${TIMESTAMP}.json"
echo "  - Table report: report_${TIMESTAMP}.txt"
echo "  - Total errors: $TOTAL_ERRORS"
echo ""
echo "View reports:"
echo "  cat $REPORT_DIR/report_${TIMESTAMP}.txt"
echo "  cat $REPORT_DIR/report_${TIMESTAMP}.json | jq"
