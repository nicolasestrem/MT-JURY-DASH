# CSS Analyzer PowerShell Script
# Analyzes CSS files for !important declarations and other metrics

param(
    [string]$Path = ".\Plugin\assets\css",
    [string]$OutputFile = ".\docs\css-statistics-report.md"
)

$cssFiles = Get-ChildItem -Path $Path -Filter "*.css" -Recurse
$totalImportant = 0
$fileStats = @()
$date = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

Write-Host "Analyzing CSS files in $Path..." -ForegroundColor Cyan

foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName -Raw
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "")
    
    # Count !important declarations
    $importantCount = ([regex]::Matches($content, "!important")).Count
    $totalImportant += $importantCount
    
    # Count total rules (approximate)
    $ruleCount = ([regex]::Matches($content, "\{")).Count
    
    # Calculate file size
    $fileSize = [math]::Round($file.Length / 1KB, 2)
    
    # Count selectors with deep nesting (more than 3 levels)
    $deepNesting = ([regex]::Matches($content, "(\s+\S+){4,}\s*\{")).Count
    
    # Count z-index declarations
    $zIndexCount = ([regex]::Matches($content, "z-index\s*:")).Count
    
    # Count media queries
    $mediaQueries = ([regex]::Matches($content, "@media")).Count
    
    $fileStats += [PSCustomObject]@{
        File = $relativePath
        Important = $importantCount
        Rules = $ruleCount
        SizeKB = $fileSize
        DeepNesting = $deepNesting
        ZIndex = $zIndexCount
        MediaQueries = $mediaQueries
        Ratio = if ($ruleCount -gt 0) { [math]::Round($importantCount / $ruleCount * 100, 1) } else { 0 }
    }
}

# Sort by !important count
$fileStats = $fileStats | Sort-Object -Property Important -Descending

# Generate markdown report
$report = @"
# CSS Statistics Report
**Generated:** $date  
**Total Files Analyzed:** $($cssFiles.Count)  
**Total !important Declarations:** $totalImportant

## Summary Statistics
| Metric | Value |
|--------|-------|
| Total CSS Files | $($cssFiles.Count) |
| Total !important | $totalImportant |
| Average !important per file | $([math]::Round($totalImportant / $cssFiles.Count, 1)) |
| Total Size | $([math]::Round(($cssFiles | Measure-Object -Property Length -Sum).Sum / 1KB, 2)) KB |

## Files by !important Count (Top 20)
| File | !important | Rules | Ratio% | Size(KB) | Deep Nesting | Z-Index | Media Queries |
|------|------------|-------|--------|----------|--------------|---------|---------------|
"@

$topFiles = $fileStats | Select-Object -First 20
foreach ($stat in $topFiles) {
    $report += "`n| $($stat.File) | $($stat.Important) | $($stat.Rules) | $($stat.Ratio)% | $($stat.SizeKB) | $($stat.DeepNesting) | $($stat.ZIndex) | $($stat.MediaQueries) |"
}

# Identify emergency/hotfix files
$emergencyFiles = $fileStats | Where-Object { $_.File -match "(emergency|fix|hotfix|override|rollback|consolidated)" }
$emergencyImportant = ($emergencyFiles | Measure-Object -Property Important -Sum).Sum

$report += @"

## Emergency/Hotfix Files Analysis
**Total Emergency Files:** $($emergencyFiles.Count)  
**Total !important in Emergency Files:** $emergencyImportant  
**Percentage of Total !important:** $([math]::Round($emergencyImportant / $totalImportant * 100, 1))%

| File | !important | Size(KB) |
|------|------------|----------|
"@

foreach ($file in $emergencyFiles) {
    $report += "`n| $($file.File) | $($file.Important) | $($file.SizeKB) |"
}

# Risk assessment
$criticalFiles = $fileStats | Where-Object { $_.Important -gt 100 }
$highRiskFiles = $fileStats | Where-Object { $_.Important -gt 50 -and $_.Important -le 100 }
$mediumRiskFiles = $fileStats | Where-Object { $_.Important -gt 20 -and $_.Important -le 50 }

$report += @"

## Risk Assessment
| Risk Level | File Count | Total !important |
|------------|------------|-----------------|
| CRITICAL (>100) | $($criticalFiles.Count) | $(($criticalFiles | Measure-Object -Property Important -Sum).Sum) |
| HIGH (51-100) | $($highRiskFiles.Count) | $(($highRiskFiles | Measure-Object -Property Important -Sum).Sum) |
| MEDIUM (21-50) | $($mediumRiskFiles.Count) | $(($mediumRiskFiles | Measure-Object -Property Important -Sum).Sum) |
| LOW (1-20) | $(($fileStats | Where-Object { $_.Important -gt 0 -and $_.Important -le 20 }).Count) | $(($fileStats | Where-Object { $_.Important -gt 0 -and $_.Important -le 20 } | Measure-Object -Property Important -Sum).Sum) |

## Recommendations
1. **Immediate Action Required:** Files with >100 !important declarations
2. **Consolidation Candidates:** $($emergencyFiles.Count) emergency/hotfix files
3. **Refactoring Priority:** Focus on top 5 files which contain $((($fileStats | Select-Object -First 5 | Measure-Object -Property Important -Sum).Sum)) !important declarations
4. **Quick Wins:** Remove duplicate !important declarations in consolidated files

## File Size Analysis
| Category | Count | Total Size (KB) |
|----------|-------|-----------------|
| Large Files (>50KB) | $(($fileStats | Where-Object { $_.SizeKB -gt 50 }).Count) | $(($fileStats | Where-Object { $_.SizeKB -gt 50 } | Measure-Object -Property SizeKB -Sum).Sum) |
| Medium Files (10-50KB) | $(($fileStats | Where-Object { $_.SizeKB -ge 10 -and $_.SizeKB -le 50 }).Count) | $([math]::Round(($fileStats | Where-Object { $_.SizeKB -ge 10 -and $_.SizeKB -le 50 } | Measure-Object -Property SizeKB -Sum).Sum, 2)) |
| Small Files (<10KB) | $(($fileStats | Where-Object { $_.SizeKB -lt 10 }).Count) | $([math]::Round(($fileStats | Where-Object { $_.SizeKB -lt 10 } | Measure-Object -Property SizeKB -Sum).Sum, 2)) |
"@

# Write report to file
$report | Out-File -FilePath $OutputFile -Encoding UTF8

Write-Host "`nAnalysis complete!" -ForegroundColor Green
Write-Host "Report saved to: $OutputFile" -ForegroundColor Yellow
Write-Host "Total !important declarations found: $totalImportant" -ForegroundColor Red