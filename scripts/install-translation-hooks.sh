#!/bin/bash

# Install Translation Validation Git Hooks
# This script sets up pre-commit hooks for translation validation

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Installing Translation Validation Hooks${NC}"
echo -e "${BLUE}========================================${NC}"

# Get the git directory
GIT_DIR=$(git rev-parse --git-dir 2>/dev/null)
if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Not a git repository${NC}"
    exit 1
fi

PROJECT_ROOT=$(git rev-parse --show-toplevel)

# Create hooks directory if it doesn't exist
HOOKS_DIR="$GIT_DIR/hooks"
if [ ! -d "$HOOKS_DIR" ]; then
    mkdir -p "$HOOKS_DIR"
    echo -e "${GREEN}Created hooks directory${NC}"
fi

# Backup existing pre-commit hook if it exists
if [ -f "$HOOKS_DIR/pre-commit" ]; then
    backup_file="$HOOKS_DIR/pre-commit.backup.$(date +%Y%m%d_%H%M%S)"
    cp "$HOOKS_DIR/pre-commit" "$backup_file"
    echo -e "${YELLOW}Backed up existing pre-commit hook to: $backup_file${NC}"
fi

# Copy the pre-commit hook
if [ -f "$PROJECT_ROOT/.githooks/pre-commit" ]; then
    cp "$PROJECT_ROOT/.githooks/pre-commit" "$HOOKS_DIR/pre-commit"
    chmod +x "$HOOKS_DIR/pre-commit"
    echo -e "${GREEN}✓ Installed pre-commit hook${NC}"
else
    echo -e "${RED}Error: Pre-commit hook not found at .githooks/pre-commit${NC}"
    exit 1
fi

# Set git config to use the hooks directory
git config core.hooksPath .githooks
echo -e "${GREEN}✓ Configured git to use .githooks directory${NC}"

# Check for required tools
echo -e "\n${BLUE}Checking required tools...${NC}"

# PHP
if command -v php > /dev/null 2>&1; then
    PHP_VERSION=$(php -v | head -n 1)
    echo -e "${GREEN}✓ PHP installed: $PHP_VERSION${NC}"
else
    echo -e "${YELLOW}⚠ PHP not found - translation checks will be limited${NC}"
fi

# msgfmt (gettext)
if command -v msgfmt > /dev/null 2>&1; then
    echo -e "${GREEN}✓ gettext tools installed${NC}"
else
    echo -e "${YELLOW}⚠ gettext tools not found${NC}"
    echo -e "${YELLOW}  Install with: apt-get install gettext (Ubuntu/Debian)${NC}"
    echo -e "${YELLOW}  or: brew install gettext (macOS)${NC}"
fi

# WordPress CLI
if command -v wp > /dev/null 2>&1; then
    WP_VERSION=$(wp cli version)
    echo -e "${GREEN}✓ WP-CLI installed: $WP_VERSION${NC}"
else
    echo -e "${YELLOW}⚠ WP-CLI not found${NC}"
    echo -e "${YELLOW}  Install from: https://wp-cli.org${NC}"
fi

# PHPCS (WordPress Coding Standards)
if command -v phpcs > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PHP CodeSniffer installed${NC}"
    
    # Check if WordPress standards are installed
    if phpcs -i | grep -q WordPress; then
        echo -e "${GREEN}✓ WordPress Coding Standards configured${NC}"
    else
        echo -e "${YELLOW}⚠ WordPress Coding Standards not found${NC}"
        echo -e "${YELLOW}  Install with: composer global require wp-coding-standards/wpcs${NC}"
    fi
else
    echo -e "${YELLOW}⚠ PHP CodeSniffer not found${NC}"
    echo -e "${YELLOW}  Install with: composer global require squizlabs/php_codesniffer${NC}"
fi

# Check translation script
if [ -f "$PROJECT_ROOT/scripts/german-translation-automation.php" ]; then
    echo -e "${GREEN}✓ Translation automation script found${NC}"
    
    # Test the script
    php "$PROJECT_ROOT/scripts/german-translation-automation.php" --help > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Translation script is working${NC}"
    else
        echo -e "${YELLOW}⚠ Translation script may have issues${NC}"
    fi
else
    echo -e "${RED}✗ Translation automation script not found${NC}"
fi

# Create a test commit to verify hook works
echo -e "\n${BLUE}Testing hook installation...${NC}"
TEST_FILE="$PROJECT_ROOT/.hook-test-$(date +%s).tmp"
echo "<?php echo 'test'; ?>" > "$TEST_FILE"
git add "$TEST_FILE"

# Try to run the pre-commit hook
if $HOOKS_DIR/pre-commit; then
    echo -e "${GREEN}✓ Pre-commit hook executed successfully${NC}"
else
    echo -e "${YELLOW}⚠ Pre-commit hook encountered issues (this is normal for test)${NC}"
fi

# Clean up test file
git reset HEAD "$TEST_FILE" 2>/dev/null
rm -f "$TEST_FILE"

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Installation Complete!${NC}"
echo -e "${GREEN}========================================${NC}"

echo -e "\n${BLUE}Hook Features:${NC}"
echo "  • PHP syntax validation"
echo "  • Debug code detection (var_dump, console.log)"
echo "  • Translation file validation"
echo "  • Automatic MO compilation"
echo "  • Translation coverage checking"
echo "  • Hardcoded string detection"
echo "  • Sensitive data prevention"
echo "  • WordPress Coding Standards (if available)"

echo -e "\n${BLUE}Usage:${NC}"
echo "  The pre-commit hook will run automatically when you commit."
echo "  To bypass the hook (not recommended): git commit --no-verify"

echo -e "\n${BLUE}To uninstall:${NC}"
echo "  git config --unset core.hooksPath"
echo "  rm $HOOKS_DIR/pre-commit"

exit 0