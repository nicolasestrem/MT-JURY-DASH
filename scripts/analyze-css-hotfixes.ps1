# CSS Hotfix Analysis Script for Phase 1 Planning
# Mobility Trailblazers WordPress Plugin
# Created: 2025-08-24

$cssPath = "assets\css"
$targetFiles = @(
    "emergency-fixes.css",
    "frontend-critical-fixes.css",
    "candidate-single-hotfix.css",
    "mt-jury-filter-hotfix.css",
    "evaluation-fix.css",
    "mt-evaluation-fixes.css",
    "mt-jury-dashboard-fix.css",
    "mt-modal-fix.css",
    "mt-medal-fix.css"
)

Write-Host "CSS Hotfix Files Analysis" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan
Write-Host ""

$totalLines = 0
$totalSize = 0
$totalImportant = 0
$fileDetails = @()

foreach ($file in $targetFiles) {
    $filePath = Join-Path $cssPath $file
    if (Test-Path $filePath) {
        $fileInfo = Get-Item $filePath
        $content = Get-Content $filePath -Raw
        $lines = (Get-Content $filePath).Count
        $importantCount = ([regex]::Matches($content, '!important')).Count
        $sizeKB = [math]::Round($fileInfo.Length / 1KB, 2)
        
        $fileDetails += [PSCustomObject]@{
            FileName = $file
            Lines = $lines
            SizeKB = $sizeKB
            ImportantCount = $importantCount
            ImportantRatio = if ($lines -gt 0) { [math]::Round($importantCount / $lines * 100, 1) } else { 0 }
        }
        
        $totalLines += $lines
        $totalSize += $fileInfo.Length
        $totalImportant += $importantCount
    }
}

# Display individual file analysis
$fileDetails | Format-Table -AutoSize

Write-Host ""
Write-Host "Summary Statistics" -ForegroundColor Green
Write-Host "------------------" -ForegroundColor Green
Write-Host "Total Files: $($fileDetails.Count)"
Write-Host "Total Lines: $totalLines"
Write-Host "Total Size: $([math]::Round($totalSize / 1KB, 2)) KB"
Write-Host "Total !important: $totalImportant"
Write-Host "Average !important per file: $([math]::Round($totalImportant / $fileDetails.Count, 0))"
Write-Host "!important density: $([math]::Round($totalImportant / $totalLines * 100, 1))%"

# Check for duplicate selectors across files
Write-Host ""
Write-Host "Analyzing for potential duplicates..." -ForegroundColor Yellow

$allSelectors = @{}
foreach ($file in $targetFiles) {
    $filePath = Join-Path $cssPath $file
    if (Test-Path $filePath) {
        $content = Get-Content $filePath -Raw
        $selectorMatches = [regex]::Matches($content, '([\.#\[]?[a-zA-Z0-9\-_\[\]="\s:>+~,]+)\s*{')
        foreach ($match in $selectorMatches) {
            $selector = $match.Groups[1].Value.Trim()
            if ($selector -and $selector -notmatch '^\s*$') {
                if (-not $allSelectors.ContainsKey($selector)) {
                    $allSelectors[$selector] = @()
                }
                $allSelectors[$selector] += $file
            }
        }
    }
}

$duplicates = $allSelectors.GetEnumerator() | Where-Object { $_.Value.Count -gt 1 }
Write-Host "Found $($duplicates.Count) selectors appearing in multiple files"

# Estimate consolidation benefits
Write-Host ""
Write-Host "Consolidation Benefits Estimate" -ForegroundColor Magenta
Write-Host "-------------------------------" -ForegroundColor Magenta
Write-Host "Current: 9 HTTP requests for CSS files"
Write-Host "After: 1 HTTP request"
Write-Host "Estimated load time reduction: ~200-450ms (depending on latency)"
Write-Host "Potential duplicate selector removal: ~$([math]::Round($duplicates.Count * 0.7, 0)) rules"
Write-Host "Estimated size reduction after optimization: ~25-30%"