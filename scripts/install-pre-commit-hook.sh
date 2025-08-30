#!/bin/bash
# Install pre-commit hook for Mobility Trailblazers

HOOK_FILE=".git/hooks/pre-commit"

echo "🔧 Installing MT pre-commit hook..."

# Create pre-commit hook
cat > "$HOOK_FILE" << 'EOF'
#!/bin/sh
# Mobility Trailblazers Pre-commit Hook
# Prevents committing debug code and other quality issues

echo "🔍 Running MT pre-commit checks..."

# Check for debug code
DEBUG_FILES=$(git diff --cached --name-only | grep -E '\.(php|js)$' | xargs grep -l "console\.log\|var_dump\|print_r" 2>/dev/null || true)

if [ ! -z "$DEBUG_FILES" ]; then
    echo "❌ Debug code detected in:"
    echo "$DEBUG_FILES"
    echo ""
    echo "Please remove debug statements before committing"
    exit 1
fi

# Check for sensitive data patterns  
SENSITIVE_FILES=$(git diff --cached --name-only | xargs grep -l "password\s*=\|api_key\s*=\|secret\s*=" 2>/dev/null || true)

if [ ! -z "$SENSITIVE_FILES" ]; then
    echo "❌ Sensitive data detected in:"
    echo "$SENSITIVE_FILES"
    exit 1
fi

echo "✅ Pre-commit checks passed!"
exit 0
EOF

# Make executable
chmod +x "$HOOK_FILE"

echo "✅ Pre-commit hook installed successfully!"
echo "   Location: $HOOK_FILE"
echo ""
echo "The hook will check for:"
echo "  ❌ Debug code (console.log, var_dump, print_r)"
echo "  ❌ Sensitive data (passwords, API keys)"
echo ""
echo "To bypass the hook temporarily: git commit --no-verify"