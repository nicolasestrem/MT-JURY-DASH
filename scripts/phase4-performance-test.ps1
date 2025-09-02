# Phase 4: CSS Performance Testing Script
# Tests CSS load times and file sizes after refactoring

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CSS PERFORMANCE TESTING - PHASE 4" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$baseUrl = "http://localhost:8080"
$cssPath = "C:\Users\nicol\Desktop\mobility-trailblazers\assets\css"
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$reportFile = "performance-report-$timestamp.txt"

# Initialize report
$report = @"
CSS Performance Test Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
==========================================

"@

# Function to measure page load time
function Measure-PageLoadTime {
    param($url)
    
    $start = Get-Date
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30
        $end = Get-Date
        $loadTime = ($end - $start).TotalMilliseconds
        return @{
            Success = $true
            LoadTime = $loadTime
            StatusCode = $response.StatusCode
        }
    }
    catch {
        return @{
            Success = $false
            Error = $_.Exception.Message
        }
    }
}

# 1. File Size Analysis
Write-Host "1. Analyzing CSS File Sizes..." -ForegroundColor Yellow
$report += "`n## CSS FILE SIZE ANALYSIS`n"
$report += "==========================`n`n"

# Count CSS files
$cssFiles = Get-ChildItem -Path $cssPath -Recurse -Filter "*.css" | Where-Object { $_.Name -notlike "*.min.css" }
$totalFiles = $cssFiles.Count
$totalSize = ($cssFiles | Measure-Object -Property Length -Sum).Sum / 1KB

Write-Host "   Total CSS Files: $totalFiles"
Write-Host "   Total Size: $([math]::Round($totalSize, 2)) KB"

$report += "Total CSS Files: $totalFiles`n"
$report += "Total Size: $([math]::Round($totalSize, 2)) KB`n`n"

# Analyze by directory
$report += "### By Directory:`n"
$directories = @("v4", "components", "refactored")

foreach ($dir in $directories) {
    $dirPath = Join-Path $cssPath $dir
    if (Test-Path $dirPath) {
        $dirFiles = Get-ChildItem -Path $dirPath -Filter "*.css" | Where-Object { $_.Name -notlike "*.min.css" }
        $dirSize = ($dirFiles | Measure-Object -Property Length -Sum).Sum / 1KB
        $fileCount = $dirFiles.Count
        
        Write-Host "   $dir/: $fileCount files, $([math]::Round($dirSize, 2)) KB"
        $report += "$dir/: $fileCount files, $([math]::Round($dirSize, 2)) KB`n"
    }
}

# Check for consolidated file
$consolidatedFile = Join-Path $cssPath "refactored\mt-consolidated.css"
if (Test-Path $consolidatedFile) {
    $consolidatedSize = (Get-Item $consolidatedFile).Length / 1KB
    Write-Host "   Consolidated CSS: $([math]::Round($consolidatedSize, 2)) KB" -ForegroundColor Green
    $report += "`nConsolidated CSS: $([math]::Round($consolidatedSize, 2)) KB [OK]`n"
}

# 2. Important Declaration Count
Write-Host "`n2. Checking !important Declarations..." -ForegroundColor Yellow
$report += "`n## !IMPORTANT USAGE ANALYSIS`n"
$report += "============================`n`n"

$importantCount = 0
$fileImportantCounts = @{}

foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName -Raw
    $matches = [regex]::Matches($content, "!important")
    $count = $matches.Count
    $importantCount += $count
    
    if ($count -gt 0) {
        $relativePath = $file.FullName.Replace($cssPath, "").TrimStart("\")
        $fileImportantCounts[$relativePath] = $count
    }
}

Write-Host "   Total !important declarations: $importantCount"
$report += "Total !important declarations: $importantCount`n`n"

if ($importantCount -gt 0) {
    Write-Host "   Files with !important:" -ForegroundColor Yellow
    $report += "Files with !important:`n"
    foreach ($file in $fileImportantCounts.GetEnumerator() | Sort-Object Value -Descending | Select-Object -First 5) {
        Write-Host "      $($file.Key): $($file.Value)"
        $report += "  - $($file.Key): $($file.Value)`n"
    }
} else {
    Write-Host "   [OK] No !important declarations found!" -ForegroundColor Green
    $report += "[OK] No !important declarations found!`n"
}

# 3. Page Load Performance
Write-Host "`n3. Testing Page Load Performance..." -ForegroundColor Yellow
$report += "`n## PAGE LOAD PERFORMANCE`n"
$report += "========================`n`n"

$testPages = @(
    @{Name="Jury Dashboard"; Url="$baseUrl/vote/"},
    @{Name="Candidate Profile"; Url="$baseUrl/candidate/nic-knapp/"},
    @{Name="Homepage"; Url="$baseUrl/"}
)

$loadTimes = @()
foreach ($page in $testPages) {
    Write-Host "   Testing $($page.Name)..."
    $result = Measure-PageLoadTime -url $page.Url
    
    if ($result.Success) {
        $loadTimes += $result.LoadTime
        Write-Host "      Load Time: $([math]::Round($result.LoadTime, 2))ms" -ForegroundColor Green
        $report += "$($page.Name): $([math]::Round($result.LoadTime, 2))ms`n"
    } else {
        Write-Host "      Error: $($result.Error)" -ForegroundColor Red
        $report += "$($page.Name): ERROR - $($result.Error)`n"
    }
    
    Start-Sleep -Milliseconds 500
}

if ($loadTimes.Count -gt 0) {
    $avgLoadTime = ($loadTimes | Measure-Object -Average).Average
    Write-Host "   Average Load Time: $([math]::Round($avgLoadTime, 2))ms" -ForegroundColor Cyan
    $report += "`nAverage Load Time: $([math]::Round($avgLoadTime, 2))ms`n"
}

# 4. CSS Custom Properties Check
Write-Host "`n4. Verifying CSS Token System..." -ForegroundColor Yellow
$report += "`n## CSS TOKEN SYSTEM`n"
$report += "==================`n`n"

$tokenFile = Join-Path $cssPath "v4\mt-tokens.css"
if (Test-Path $tokenFile) {
    $tokenContent = Get-Content $tokenFile -Raw
    $customProps = [regex]::Matches($tokenContent, "--mt-[\w-]+:")
    $tokenCount = $customProps.Count
    
    Write-Host "   CSS Custom Properties found: $tokenCount"
    $report += "CSS Custom Properties defined: $tokenCount`n"
    
    # Check for key tokens
    $keyTokens = @("--mt-primary", "--mt-space-md", "--mt-font-base", "--mt-shadow-md")
    $report += "`nKey Tokens Verified:`n"
    
    foreach ($token in $keyTokens) {
        if ($tokenContent -match $token) {
            Write-Host "   [OK] $token found" -ForegroundColor Green
            $report += "  [OK] $token`n"
        } else {
            Write-Host "   [X] $token missing" -ForegroundColor Red
            $report += "  [X] $token MISSING`n"
        }
    }
} else {
    Write-Host "   Token file not found!" -ForegroundColor Red
    $report += "ERROR: Token file not found at $tokenFile`n"
}

# 5. BEM Component Structure Check
Write-Host "`n5. Verifying BEM Components..." -ForegroundColor Yellow
$report += "`n## BEM COMPONENT VERIFICATION`n"
$report += "=============================`n`n"

$componentPath = Join-Path $cssPath "components"
if (Test-Path $componentPath) {
    $componentFiles = Get-ChildItem -Path $componentPath -Filter "*.css"
    $bemComponents = @()
    
    foreach ($file in $componentFiles) {
        $content = Get-Content $file.FullName -Raw
        $bemBlocks = [regex]::Matches($content, "\.mt-[\w-]+(?!__)")
        $bemElements = [regex]::Matches($content, "\.mt-[\w-]+__[\w-]+")
        $bemModifiers = [regex]::Matches($content, "\.mt-[\w-]+--[\w-]+")
        
        $component = @{
            Name = $file.BaseName
            Blocks = $bemBlocks.Count
            Elements = $bemElements.Count
            Modifiers = $bemModifiers.Count
        }
        $bemComponents += $component
        
        Write-Host "   $($file.BaseName): $($bemBlocks.Count) blocks, $($bemElements.Count) elements, $($bemModifiers.Count) modifiers"
        $report += "$($file.BaseName):`n"
        $report += "  - Blocks: $($bemBlocks.Count)`n"
        $report += "  - Elements: $($bemElements.Count)`n"
        $report += "  - Modifiers: $($bemModifiers.Count)`n`n"
    }
}

# 6. Summary and Recommendations
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "PERFORMANCE TEST SUMMARY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$report += "`n## SUMMARY`n"
$report += "==========`n`n"

# Calculate score
$score = 100
$recommendations = @()

# File consolidation check
if ($totalFiles -gt 15) {
    $score -= 10
    $recommendations += "Consider further CSS consolidation (currently $totalFiles files)"
}

# !important check
if ($importantCount -gt 100) {
    $score -= 20
    $recommendations += "High number of !important declarations ($importantCount)"
} elseif ($importantCount -gt 50) {
    $score -= 10
    $recommendations += "Moderate !important usage ($importantCount)"
}

# Load time check
if ($avgLoadTime -gt 2000) {
    $score -= 15
    $recommendations += "Page load times could be improved (avg: $([math]::Round($avgLoadTime, 2))ms)"
}

# Token system check
if ($tokenCount -lt 50) {
    $score -= 5
    $recommendations += "Token system could be expanded (only $tokenCount tokens)"
}

Write-Host "`nPerformance Score: $score/100" -ForegroundColor $(if ($score -ge 80) { "Green" } elseif ($score -ge 60) { "Yellow" } else { "Red" })
$report += "Performance Score: $score/100`n`n"

if ($recommendations.Count -gt 0) {
    Write-Host "`nRecommendations:" -ForegroundColor Yellow
    $report += "Recommendations:`n"
    foreach ($rec in $recommendations) {
        Write-Host "  - $rec"
        $report += "  - $rec`n"
    }
} else {
    Write-Host "`n[OK] All performance metrics are excellent!" -ForegroundColor Green
    $report += "[OK] All performance metrics are excellent!`n"
}

# Success criteria
$report += "`n## SUCCESS CRITERIA`n"
$report += "==================`n`n"

$criteria = @(
    @{Name="CSS Files Consolidated"; Pass=($totalFiles -le 20); Actual=$totalFiles; Target="≤20"},
    @{Name="!important Removed"; Pass=($importantCount -le 100); Actual=$importantCount; Target="≤100"},
    @{Name="Average Load Time"; Pass=($avgLoadTime -le 2000); Actual="$([math]::Round($avgLoadTime, 2))ms"; Target="≤2000ms"},
    @{Name="Token System Implemented"; Pass=($tokenCount -ge 50); Actual=$tokenCount; Target="≥50"},
    @{Name="BEM Components Created"; Pass=($bemComponents.Count -ge 3); Actual=$bemComponents.Count; Target="≥3"}
)

Write-Host "`nSuccess Criteria:" -ForegroundColor Cyan
foreach ($criterion in $criteria) {
    $status = if ($criterion.Pass) { "[PASS]" } else { "[FAIL]" }
    $color = if ($criterion.Pass) { "Green" } else { "Red" }
    Write-Host "  $status - $($criterion.Name): $($criterion.Actual) (target: $($criterion.Target))" -ForegroundColor $color
    $report += "$status - $($criterion.Name): $($criterion.Actual) (target: $($criterion.Target))`n"
}

# Save report
$report | Out-File -FilePath $reportFile -Encoding UTF8
Write-Host "`n[OK] Report saved to: $reportFile" -ForegroundColor Green

# Open report in notepad
Start-Process notepad.exe $reportFile