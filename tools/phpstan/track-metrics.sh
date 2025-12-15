#!/bin/bash
# Track PHPStan metrics over time
# Usage: ./track-metrics.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
METRICS_FILE="$PKP_DIR/../../cache/phpstan-metrics.csv"
TIMESTAMP=$(date +%Y-%m-%d)

cd "$PKP_DIR"

echo "=== Tracking PHPStan Metrics ==="
echo "Metrics file: $METRICS_FILE"
echo "Running analysis..."
echo ""

# Run analysis and capture JSON output
REPORT=$(php -d memory_limit=2G lib/vendor/bin/phpstan analyse \
    --configuration phpstan.neon \
    --error-format=json \
    --no-progress \
    --memory-limit=2G 2>&1 || true)

# Check if jq is available
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required for metrics tracking"
    echo "Install with: brew install jq (macOS) or apt-get install jq (Linux)"
    exit 1
fi

# Extract metrics
TOTAL_ERRORS=$(echo "$REPORT" | jq -r '.totals.file_errors // 0' 2>/dev/null || echo "0")
TOTAL_FILES=$(echo "$REPORT" | jq -r '.files | length // 0' 2>/dev/null || echo "0")

# Create CSV header if file doesn't exist
if [ ! -f "$METRICS_FILE" ]; then
    echo "date,total_errors,total_files" > "$METRICS_FILE"
    echo "Created metrics file: $METRICS_FILE"
fi

# Check if today's entry already exists
if grep -q "^$TIMESTAMP," "$METRICS_FILE"; then
    # Update existing entry
    sed -i.bak "s/^$TIMESTAMP,.*/$TIMESTAMP,$TOTAL_ERRORS,$TOTAL_FILES/" "$METRICS_FILE"
    rm -f "$METRICS_FILE.bak"
    echo "Updated metrics for $TIMESTAMP"
else
    # Append new entry
    echo "$TIMESTAMP,$TOTAL_ERRORS,$TOTAL_FILES" >> "$METRICS_FILE"
    echo "Added metrics for $TIMESTAMP"
fi

echo ""
echo "Current metrics:"
echo "  Date: $TIMESTAMP"
echo "  Total errors: $TOTAL_ERRORS"
echo "  Total files analyzed: $TOTAL_FILES"
echo ""
echo "Historical metrics:"
tail -n 5 "$METRICS_FILE"
