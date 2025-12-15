# PHPStan Analysis Scripts

This directory contains helper scripts for running PHPStan static analysis on the OJS/OPS/OMP codebase.

## Quick Start

```bash
# From lib/pkp directory
cd lib/pkp

# 1. Generate baseline (first time only)
./tools/phpstan/generate-baseline.sh

# 2. Run component analysis
./tools/phpstan/analyze-all-components.sh

# 3. Track metrics over time
./tools/phpstan/track-metrics.sh
```

## Available Scripts

### Component Analysis

**`analyze-core.sh`** - Analyze core PKP library classes
```bash
./tools/phpstan/analyze-core.sh
```

**`analyze-api.sh`** - Analyze API endpoints
```bash
./tools/phpstan/analyze-api.sh
```

**`analyze-jobs.sh`** - Analyze Laravel jobs
```bash
./tools/phpstan/analyze-jobs.sh
```

**`analyze-controllers.sh`** - Analyze controllers and pages
```bash
./tools/phpstan/analyze-controllers.sh
```

**`analyze-all-components.sh`** - Run all component analyses
```bash
./tools/phpstan/analyze-all-components.sh
```

### Baseline Management

**`generate-baseline.sh`** - Generate or regenerate baseline
```bash
# Generate default baseline
./tools/phpstan/generate-baseline.sh

# Generate with custom filename
./tools/phpstan/generate-baseline.sh custom-baseline.neon
```

### Reporting

**`generate-report.sh`** - Generate JSON and table reports
```bash
./tools/phpstan/generate-report.sh
# Outputs to: ../../cache/phpstan-reports/
```

**`track-metrics.sh`** - Track error counts over time
```bash
./tools/phpstan/track-metrics.sh
# Requires: jq (install with: brew install jq)
# Outputs to: ../../cache/phpstan-metrics.csv
```

## Passing Additional Options

All scripts accept additional PHPStan options:

```bash
# Run with verbose output
./tools/phpstan/analyze-core.sh -vvv

# Use different config file
./tools/phpstan/analyze-api.sh --configuration phpstan-enhanced.neon

# Generate specific output format
./tools/phpstan/analyze-jobs.sh --error-format=json
```

## Common Workflows

### Daily Development
```bash
# Analyze only changed components
./tools/phpstan/analyze-core.sh
```

### Weekly Progress Check
```bash
# Track metrics to see improvement
./tools/phpstan/track-metrics.sh
```

### Monthly Baseline Update
```bash
# Regenerate baseline after fixing errors
./tools/phpstan/generate-baseline.sh
```

### Release Preparation
```bash
# Full analysis and report generation
./tools/phpstan/analyze-all-components.sh
./tools/phpstan/generate-report.sh
```

## Troubleshooting

### Memory Issues
If you encounter memory errors, increase the limit in the script:
```bash
# Edit the script and change:
php -d memory_limit=4G lib/vendor/bin/phpstan ...
```

### Slow Analysis
Reduce parallelization by editing `phpstan.neon`:
```neon
parameters:
    parallel:
        maximumNumberOfProcesses: 1
```

### Script Not Found
Ensure you're in the `lib/pkp` directory:
```bash
cd lib/pkp
./tools/phpstan/analyze-core.sh
```

## Documentation

See the comprehensive integration guide: `../../claude/PHPSTAN_INTEGRATION.md`
