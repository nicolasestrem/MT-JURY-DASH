#!/bin/bash
# Setup script for git hooks

echo "Setting up git hooks for Mobility Trailblazers..."

# Ensure hooks directory exists
mkdir -p .git/hooks

# Copy pre-commit hook
if [ -f .githooks/pre-commit ]; then
    cp .githooks/pre-commit .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
    echo "✓ Pre-commit hook installed"
else
    echo "⚠ Pre-commit hook source not found in .githooks/"
fi

# Configure git for proper line endings
git config core.autocrlf input
git config core.eol lf
echo "✓ Git configured for LF line endings"

# Optional: Set hooks path to use .githooks directory directly
# git config core.hooksPath .githooks

echo ""
echo "Git hooks setup complete!"
echo "The pre-commit hook will now run automatically before each commit."
echo "To bypass hooks (not recommended): git commit --no-verify"