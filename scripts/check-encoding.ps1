# Simple UTF-8 Encoding Check Script
# Mobility Trailblazers
# Date: 2025-08-25

$PluginPath = Split-Path $PSScriptRoot -Parent
$TemplatesPath = Join-Path $PluginPath "templates"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "UTF-8 Encoding Check" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check PHP files in templates
$PhpFiles = Get-ChildItem -Path $TemplatesPath -Filter "*.php" -Recurse -ErrorAction SilentlyContinue

$FilesWithIssues = 0
$IssueList = @()

foreach ($File in $PhpFiles) {
    $RelativePath = $File.FullName.Replace($PluginPath + "\", "")
    
    try {
        $Content = Get-Content -Path $File.FullName -Raw -Encoding UTF8
        $HasIssues = $false
        
        # Check for common double-encoded patterns
        if ($Content -match "Ã¤|Ã¶|Ã¼|ÃŸ|Ã„|Ã–|Ãœ") {
            $HasIssues = $true
            $FilesWithIssues++
            $IssueList += $RelativePath
            Write-Host "❌ $RelativePath - Has encoding issues" -ForegroundColor Red
        }
        
    } catch {
        Write-Host "⚠️  Error reading $RelativePath" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Total files scanned: $($PhpFiles.Count)" -ForegroundColor White

if ($FilesWithIssues -gt 0) {
    Write-Host "Files with issues: $FilesWithIssues" -ForegroundColor Red
    Write-Host ""
    Write-Host "Files requiring fixes:" -ForegroundColor Yellow
    foreach ($File in $IssueList) {
        Write-Host "  - $File" -ForegroundColor White
    }
} else {
    Write-Host "✅ No encoding issues detected!" -ForegroundColor Green
}

Write-Host ""