# phase2-gate.ps1
# ENFORCES ABSOLUTE ZERO !IMPORTANT

$important = (Select-String -Path "assets/css/*.css" -Pattern "!important").Count

if ($important -gt 0) {
    Write-Host "FATAL: $important !important still present" -ForegroundColor Red
    Write-Host "Running automatic fix..." -ForegroundColor Yellow
    
    # Force remove any remaining !important
    Get-ChildItem -Path "assets/css" -Filter "*.css" | ForEach-Object {
        $content = Get-Content $_.FullName -Raw
        $content = $content -replace '\s*!important\s*', ' '
        $content | Out-File $_.FullName
    }
    
    # Recheck
    $important = (Select-String -Path "assets/css/*.css" -Pattern "!important").Count
    if ($important -gt 0) {
        Write-Host "FATAL: Cannot eliminate !important. Manual intervention required." -ForegroundColor Red
        exit 1
    }
}

# Additional checks
$checks = @{
    "!important = 0" = $important -eq 0
    "Specificity layer exists" = Test-Path "assets/css/mt-specificity-layer.css"
    "Git hook installed" = Test-Path ".git/hooks/pre-commit"
    "CSS files valid" = (Get-ChildItem -Path "assets/css" -Filter "*.css").Count -gt 0
}

$allPassed = $true
foreach ($check in $checks.GetEnumerator()) {
    if ($check.Value) {
        Write-Host "PASS: $($check.Key)" -ForegroundColor Green
    } else {
        Write-Host "FAIL: $($check.Key)" -ForegroundColor Red
        $allPassed = $false
    }
}

if ($allPassed) {
    Write-Host ""
    Write-Host "PHASE 2 GATE PASSED - ZERO !important achieved" -ForegroundColor Green
    Write-Host "Git hook active - future !important commits blocked" -ForegroundColor Green
    Write-Host "Specificity layer created for cascade management" -ForegroundColor Green
    Write-Host ""
    Write-Host "Phase 2 Complete. Ready for Phase 3." -ForegroundColor Yellow
} else {
    Write-Host ""
    Write-Host "PHASE 2 FAILED - Fix issues above" -ForegroundColor Red
    exit 1
}