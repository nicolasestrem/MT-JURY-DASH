# install-git-hook.ps1
# PREVENTS !important FROM BEING COMMITTED

$hookContent = @'
#!/bin/bash
# Pre-commit hook: Block !important in CSS

if git diff --cached --name-only | grep -q "\.css$"; then
    if git diff --cached | grep -q "!important"; then
        echo "❌ COMMIT BLOCKED: !important detected in CSS files"
        echo "Remove all !important declarations before committing"
        exit 1
    fi
fi

exit 0
'@

$hookContent | Out-File ".git/hooks/pre-commit" -Encoding ASCII

# Make executable on Windows (Git Bash compatibility)
if (Get-Command chmod -ErrorAction SilentlyContinue) {
    chmod +x .git/hooks/pre-commit
}

Write-Host "Git hook installed - !important commits now blocked" -ForegroundColor Green