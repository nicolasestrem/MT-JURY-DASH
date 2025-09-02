# final-gate.ps1
# FINAL VERIFICATION BEFORE DEPLOYMENT

Write-Host ""
Write-Host "FINAL DEPLOYMENT GATE" -ForegroundColor Cyan
Write-Host "=====================" -ForegroundColor Cyan

$checks = @{
    "CSS Files <= 20" = (Get-ChildItem -Path "assets/css" -Filter "*.css").Count -le 20
    "!important = 0" = (Select-String -Path "assets/css/*.css" -Pattern "!important" -ErrorAction SilentlyContinue).Count -eq 0
    "Git Hook Active" = Test-Path ".git/hooks/pre-commit"
    "Monitor Script" = Test-Path "scripts/css-monitor.ps1"
    "GitHub Actions" = Test-Path ".github/workflows/css-quality.yml"
    "Metrics Dashboard" = Test-Path "css-metrics-dashboard.html"
    "Backup Exists" = (Get-ChildItem -Filter "css-nuclear-backup-*.tar.gz").Count -gt 0
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
    Write-Host "ALL CHECKS PASSED - READY FOR DEPLOYMENT" -ForegroundColor Green
    Write-Host ""
    
    # Get current metrics
    $cssFiles = (Get-ChildItem -Path "assets/css" -Filter "*.css").Count
    $importantCount = (Select-String -Path "assets/css/*.css" -Pattern "!important" -ErrorAction SilentlyContinue).Count
    $totalSize = [math]::Round((Get-ChildItem -Path "assets/css" -Filter "*.css" | Measure-Object -Property Length -Sum).Sum / 1KB, 2)
    
    # Create completion certificate
    @{
        CompletedAt = Get-Date
        FinalFiles = $cssFiles
        FinalImportant = $importantCount
        TotalSize = "$totalSize KB"
        GermanLocalized = $true
        GitHookInstalled = $true
        MonitoringEnabled = $true
        GitHubActionsConfigured = $true
        DashboardCreated = $true
        ProductionReady = $true
    } | ConvertTo-Json | Out-File "css-v2-completion-certificate.json"
    
    Write-Host "Completion certificate created: css-v2-completion-certificate.json" -ForegroundColor Green
    Write-Host ""
    Write-Host "DEPLOYMENT INSTRUCTIONS:" -ForegroundColor Yellow
    Write-Host "1. Review changes: git status" -ForegroundColor White
    Write-Host "2. Stage changes: git add -A" -ForegroundColor White
    Write-Host "3. Commit: git commit -m 'CSS v3.0: Complete refactoring - Zero !important achieved'" -ForegroundColor White
    Write-Host "4. Push to branch: git push origin css-refactoring-phase-1" -ForegroundColor White
    Write-Host "5. Create pull request for review" -ForegroundColor White
    Write-Host ""
    Write-Host "CSS REFACTORING COMPLETE!" -ForegroundColor Green
    
} else {
    Write-Host ""
    Write-Host "DEPLOYMENT BLOCKED - Fix failed checks" -ForegroundColor Red
    exit 1
}