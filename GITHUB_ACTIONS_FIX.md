# GitHub Actions Fix Instructions

## Problem Identified

The main issue causing GitHub Actions failures is the incorrect placement of the Claude Code Action in the test workflow matrix.

## Issue Details

In `.github/workflows/tests.yml`, lines 36-37 contain:
```yaml
- name: Claude Code Action Official
  uses: anthropics/claude-code-action@v1
```

This action should NOT be placed in the test matrix job because:
1. It's designed for issue/PR automation, not testing
2. It causes conflicts when running in matrix builds
3. It's already properly configured in separate workflows

## Required Fix

Replace the content of `.github/workflows/tests.yml` with the corrected version found in `tests-workflow-fix.yml`.

### Key Changes:
- Removed Claude Code Action from the test matrix (lines 36-37)
- All other functionality remains intact
- Preserves Laravel 10-12 support with proper PHP version constraints

## Manual Steps Required:

1. Copy the content from `tests-workflow-fix.yml`
2. Replace the content of `.github/workflows/tests.yml` with this corrected version
3. Commit the changes
4. The workflow should now run successfully

## Verification

After applying the fix:
- Tests should run properly across all PHP/Laravel version combinations
- No conflicts with Claude Code Action
- All existing functionality preserved