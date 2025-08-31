# Simple CSS Rollback Test
Write-Host "`n=== CSS Rollback Test ===" -ForegroundColor Cyan

# Test 1: Check backup exists
Write-Host "`n1. Testing Backup..." -ForegroundColor Yellow
$backupPath = ".\Plugin\assets\css\backup-20250830"
if (Test-Path $backupPath) {
    $count = (Get-ChildItem -Path $backupPath -Filter "*.css" -Recurse).Count
    Write-Host "  OK: Backup exists with $count CSS files" -ForegroundColor Green
} else {
    Write-Host "  FAIL: Backup not found" -ForegroundColor Red
}

# Test 2: Check feature flag support
Write-Host "`n2. Testing Feature Flags..." -ForegroundColor Yellow
$pluginFile = Get-Content ".\Plugin\includes\core\class-mt-plugin.php" -Raw
if ($pluginFile -match "MT_CSS_VERSION") {
    Write-Host "  OK: Feature flag support found" -ForegroundColor Green
} else {
    Write-Host "  FAIL: Feature flag support missing" -ForegroundColor Red
}

# Test 3: Check consolidated file
Write-Host "`n3. Testing Consolidated CSS..." -ForegroundColor Yellow
$consolidatedFile = ".\Plugin\assets\css\mt-emergency-consolidated-temp.css"
if (Test-Path $consolidatedFile) {
    $content = Get-Content $consolidatedFile -Raw
    $importantCount = ([regex]::Matches($content, "!important")).Count
    Write-Host "  OK: Consolidated file exists ($importantCount !important)" -ForegroundColor Green
} else {
    Write-Host "  FAIL: Consolidated file missing" -ForegroundColor Red
}

# Test 4: Check pre-commit hook
Write-Host "`n4. Testing Pre-commit Hook..." -ForegroundColor Yellow
if (Test-Path ".\.githooks\pre-commit") {
    Write-Host "  OK: Pre-commit hook installed" -ForegroundColor Green
} else {
    Write-Host "  FAIL: Pre-commit hook missing" -ForegroundColor Red
}

Write-Host "`n=== Rollback Methods ===" -ForegroundColor Cyan
Write-Host "1. Feature Flag: Add to wp-config.php:" -ForegroundColor White
Write-Host "   define('MT_CSS_FORCE_LEGACY', true);" -ForegroundColor Gray
Write-Host "2. Git: git checkout main" -ForegroundColor White
Write-Host "3. Backup: Copy from Plugin\assets\css\backup-20250830\" -ForegroundColor White

Write-Host "`nDone!" -ForegroundColor Green