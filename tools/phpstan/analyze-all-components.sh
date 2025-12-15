#!/bin/bash
# Run all component analyses in sequence
# Usage: ./analyze-all-components.sh [additional phpstan options]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "========================================"
echo "  PHPStan Component Analysis Suite"
echo "========================================"
echo ""

# Track overall status
FAILED=0

# Core Classes
echo "=== 1/4: Analyzing Core Classes ==="
if "$SCRIPT_DIR/analyze-core.sh" "$@"; then
    echo "✓ Core classes passed"
else
    echo "✗ Core classes failed"
    FAILED=$((FAILED + 1))
fi
echo ""

# API
echo "=== 2/4: Analyzing API ==="
if "$SCRIPT_DIR/analyze-api.sh" "$@"; then
    echo "✓ API passed"
else
    echo "✗ API failed"
    FAILED=$((FAILED + 1))
fi
echo ""

# Jobs
echo "=== 3/4: Analyzing Jobs ==="
if "$SCRIPT_DIR/analyze-jobs.sh" "$@"; then
    echo "✓ Jobs passed"
else
    echo "✗ Jobs failed"
    FAILED=$((FAILED + 1))
fi
echo ""

# Controllers
echo "=== 4/4: Analyzing Controllers ==="
if "$SCRIPT_DIR/analyze-controllers.sh" "$@"; then
    echo "✓ Controllers passed"
else
    echo "✗ Controllers failed"
    FAILED=$((FAILED + 1))
fi
echo ""

# Summary
echo "========================================"
echo "  Summary"
echo "========================================"
if [ $FAILED -eq 0 ]; then
    echo "✓ All components passed analysis!"
    exit 0
else
    echo "✗ $FAILED component(s) failed analysis"
    exit 1
fi
