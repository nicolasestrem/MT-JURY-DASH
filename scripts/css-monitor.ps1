# css-monitor.ps1
# Runs continuously to prevent CSS regression

while ($true) {
    Clear-Host
    Write-Host "CSS QUALITY MONITOR - $(Get-Date)" -ForegroundColor Cyan
    Write-Host "================================" -ForegroundColor Cyan
    
    $files = (Get-ChildItem -Path "assets/css" -Filter "*.css").Count
    $important = (Select-String -Path "assets/css/*.css" -Pattern "!important" -ErrorAction SilentlyContinue).Count
    $size = (Get-ChildItem -Path "assets/css" -Filter "*.css" | Measure-Object -Property Length -Sum).Sum / 1KB
    
    Write-Host "Files: $files / 20" -ForegroundColor $(if ($files -le 20) { "Green" } else { "Red" })
    Write-Host "!important: $important / 0" -ForegroundColor $(if ($important -eq 0) { "Green" } else { "Red" })
    Write-Host "Total Size: $([math]::Round($size, 2)) KB" -ForegroundColor Yellow
    
    if ($files -gt 20 -or $important -gt 0) {
        Write-Host "`nâš ï¸ VIOLATION DETECTED!" -ForegroundColor Red
        Write-Host "Rolling back last change..." -ForegroundColor Yellow
        git checkout -- assets/css
    }
    
    Start-Sleep -Seconds 10
}
