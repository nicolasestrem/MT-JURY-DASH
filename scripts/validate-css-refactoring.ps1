# Final CSS Refactoring Validation Script
# Comprehensive validation of all refactoring goals
# Mobility Trailblazers WordPress Plugin

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CSS REFACTORING FINAL VALIDATION" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$pluginPath = "C:\Users\nicol\Desktop\mobility-trailblazers"
$cssPath = "$pluginPath\assets\css"
$baseUrl = "http://localhost:8080"

# Success criteria from implementation guide
$criteria = @{
    MaxCSSFiles = 20
    MaxImportant = 100
    MaxLoadTime = 2000
    MaxTotalSize = 150
    MinTokens = 50
    RequiredBEMComponents = 3
}

# Initialize results
$results = @{
    Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    TotalTests = 0
    PassedTests = 0
    FailedTests = 0
    Score = 0
    Details = @()
}

# Test 1: CSS File Consolidation
Write-Host "[1/10] Checking CSS file consolidation..." -ForegroundColor Yellow
$cssFiles = Get-ChildItem -Path $cssPath -Recurse -Filter "*.css" | Where-Object { $_.Name -notlike "*.min.css" }
$fileCount = $cssFiles.Count

$test1 = @{
    Name = "CSS File Consolidation"
    Target = "≤$($criteria.MaxCSSFiles) files"
    Actual = "$fileCount files"
    Pass = $fileCount -le $criteria.MaxCSSFiles
}
$results.TotalTests++
if ($test1.Pass) { 
    $results.PassedTests++
    Write-Host "   [PASS] $fileCount files (target: ≤$($criteria.MaxCSSFiles))" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] $fileCount files (target: ≤$($criteria.MaxCSSFiles))" -ForegroundColor Red
}
$results.Details += $test1

# Test 2: !important Removal
Write-Host "[2/10] Checking !important declarations..." -ForegroundColor Yellow
$importantCount = 0
foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName -Raw
    $importantCount += ([regex]::Matches($content, "!important")).Count
}

$test2 = @{
    Name = "!important Removal"
    Target = "≤$($criteria.MaxImportant)"
    Actual = "$importantCount declarations"
    Pass = $importantCount -le $criteria.MaxImportant
}
$results.TotalTests++
if ($test2.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] $importantCount declarations (target: ≤$($criteria.MaxImportant))" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] $importantCount declarations (target: ≤$($criteria.MaxImportant))" -ForegroundColor Red
}
$results.Details += $test2

# Test 3: Total CSS Size
Write-Host "[3/10] Checking total CSS size..." -ForegroundColor Yellow
$totalSize = [math]::Round(($cssFiles | Measure-Object -Property Length -Sum).Sum / 1KB, 2)

$test3 = @{
    Name = "Total CSS Size"
    Target = "≤$($criteria.MaxTotalSize)KB"
    Actual = "${totalSize}KB"
    Pass = $totalSize -le $criteria.MaxTotalSize
}
$results.TotalTests++
if ($test3.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] ${totalSize}KB (target: ≤$($criteria.MaxTotalSize)KB)" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] ${totalSize}KB (target: ≤$($criteria.MaxTotalSize)KB)" -ForegroundColor Red
}
$results.Details += $test3

# Test 4: Page Load Performance
Write-Host "[4/10] Testing page load performance..." -ForegroundColor Yellow
$loadTimes = @()
$testUrls = @("$baseUrl/", "$baseUrl/vote/", "$baseUrl/candidate/nic-knapp/")

foreach ($url in $testUrls) {
    try {
        $start = Get-Date
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 10
        $loadTime = ((Get-Date) - $start).TotalMilliseconds
        $loadTimes += $loadTime
    } catch {
        Write-Host "   [WARNING] Failed to test $url" -ForegroundColor Yellow
    }
}

$avgLoadTime = if ($loadTimes.Count -gt 0) { 
    [math]::Round(($loadTimes | Measure-Object -Average).Average, 2) 
} else { 
    9999 
}

$test4 = @{
    Name = "Average Load Time"
    Target = "≤$($criteria.MaxLoadTime)ms"
    Actual = "${avgLoadTime}ms"
    Pass = $avgLoadTime -le $criteria.MaxLoadTime
}
$results.TotalTests++
if ($test4.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] ${avgLoadTime}ms (target: ≤$($criteria.MaxLoadTime)ms)" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] ${avgLoadTime}ms (target: ≤$($criteria.MaxLoadTime)ms)" -ForegroundColor Red
}
$results.Details += $test4

# Test 5: CSS Token System
Write-Host "[5/10] Checking CSS token system..." -ForegroundColor Yellow
$tokenCount = 0
$tokenFiles = Get-ChildItem -Path $cssPath -Recurse -Filter "*token*.css"
foreach ($file in $tokenFiles) {
    $content = Get-Content $file.FullName -Raw
    $tokenCount += ([regex]::Matches($content, "--mt-[\w-]+:")).Count
}

$test5 = @{
    Name = "CSS Token System"
    Target = "≥$($criteria.MinTokens) tokens"
    Actual = "$tokenCount tokens"
    Pass = $tokenCount -ge $criteria.MinTokens
}
$results.TotalTests++
if ($test5.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] $tokenCount tokens (target: ≥$($criteria.MinTokens))" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] $tokenCount tokens (target: ≥$($criteria.MinTokens))" -ForegroundColor Red
}
$results.Details += $test5

# Test 6: BEM Components
Write-Host "[6/10] Checking BEM component structure..." -ForegroundColor Yellow
$bemComponents = @()
$componentPath = "$cssPath\components"
if (Test-Path $componentPath) {
    $componentFiles = Get-ChildItem -Path $componentPath -Filter "*.css"
    foreach ($file in $componentFiles) {
        $content = Get-Content $file.FullName -Raw
        if ($content -match "\.mt-[\w-]+__[\w-]+") {
            $bemComponents += $file.BaseName
        }
    }
}

$test6 = @{
    Name = "BEM Components"
    Target = "≥$($criteria.RequiredBEMComponents) components"
    Actual = "$($bemComponents.Count) components"
    Pass = $bemComponents.Count -ge $criteria.RequiredBEMComponents
}
$results.TotalTests++
if ($test6.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] $($bemComponents.Count) BEM components (target: ≥$($criteria.RequiredBEMComponents))" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] $($bemComponents.Count) BEM components (target: ≥$($criteria.RequiredBEMComponents))" -ForegroundColor Red
}
$results.Details += $test6

# Test 7: Minification
Write-Host "[7/10] Checking CSS minification..." -ForegroundColor Yellow
$minFiles = Get-ChildItem -Path $cssPath -Recurse -Filter "*.min.css"
$minRatio = if ($cssFiles.Count -gt 0) { 
    [math]::Round($minFiles.Count / $cssFiles.Count * 100, 1) 
} else { 
    0 
}

$test7 = @{
    Name = "CSS Minification"
    Target = "Minified versions exist"
    Actual = "$($minFiles.Count) minified files ($minRatio%)"
    Pass = $minFiles.Count -gt 0
}
$results.TotalTests++
if ($test7.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] $($minFiles.Count) minified files found" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] No minified files found" -ForegroundColor Red
}
$results.Details += $test7

# Test 8: Duplicate Selectors
Write-Host "[8/10] Checking for duplicate selectors..." -ForegroundColor Yellow
$allSelectors = @()
foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName -Raw
    $selectors = [regex]::Matches($content, "([.#][\w-]+)\s*\{") | ForEach-Object { $_.Groups[1].Value }
    $allSelectors += $selectors
}
$uniqueSelectors = $allSelectors | Select-Object -Unique
$duplicates = $allSelectors.Count - $uniqueSelectors.Count

$test8 = @{
    Name = "Duplicate Selectors"
    Target = "Minimal duplicates"
    Actual = "$duplicates duplicates"
    Pass = $duplicates -lt 50
}
$results.TotalTests++
if ($test8.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] $duplicates duplicate selectors" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] $duplicates duplicate selectors (excessive)" -ForegroundColor Red
}
$results.Details += $test8

# Test 9: Responsive Design
Write-Host "[9/10] Checking responsive design implementation..." -ForegroundColor Yellow
$mediaQueries = 0
foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName -Raw
    $mediaQueries += ([regex]::Matches($content, "@media")).Count
}

$test9 = @{
    Name = "Responsive Design"
    Target = "Media queries present"
    Actual = "$mediaQueries media queries"
    Pass = $mediaQueries -gt 10
}
$results.TotalTests++
if ($test9.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] $mediaQueries media queries found" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] Insufficient media queries ($mediaQueries)" -ForegroundColor Red
}
$results.Details += $test9

# Test 10: Version Update
Write-Host "[10/10] Checking version update..." -ForegroundColor Yellow
$mainFile = "$pluginPath\mobility-trailblazers.php"
$versionUpdated = $false
if (Test-Path $mainFile) {
    $content = Get-Content $mainFile -Raw
    $versionUpdated = $content -match "Version:\s*2\.6\.0" -or $content -match "MT_VERSION.*2\.6\.0"
}

$test10 = @{
    Name = "Version Update"
    Target = "2.6.0"
    Actual = if ($versionUpdated) { "2.6.0" } else { "Not updated" }
    Pass = $versionUpdated
}
$results.TotalTests++
if ($test10.Pass) {
    $results.PassedTests++
    Write-Host "   [PASS] Version updated to 2.6.0" -ForegroundColor Green
} else {
    $results.FailedTests++
    Write-Host "   [FAIL] Version not updated" -ForegroundColor Red
}
$results.Details += $test10

# Calculate final score
$results.Score = [math]::Round($results.PassedTests / $results.TotalTests * 100, 1)

# Display summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VALIDATION SUMMARY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$scoreColor = if ($results.Score -ge 80) { "Green" } elseif ($results.Score -ge 60) { "Yellow" } else { "Red" }
Write-Host "Overall Score: $($results.Score)%" -ForegroundColor $scoreColor
Write-Host "Tests Passed: $($results.PassedTests)/$($results.TotalTests)" -ForegroundColor Cyan
Write-Host ""

# Show failed tests
if ($results.FailedTests -gt 0) {
    Write-Host "FAILED TESTS:" -ForegroundColor Red
    foreach ($test in $results.Details | Where-Object { -not $_.Pass }) {
        Write-Host "  • $($test.Name): $($test.Actual) (target: $($test.Target))" -ForegroundColor Red
    }
    Write-Host ""
}

# Show passed tests
Write-Host "PASSED TESTS:" -ForegroundColor Green
foreach ($test in $results.Details | Where-Object { $_.Pass }) {
    Write-Host "  • $($test.Name): $($test.Actual)" -ForegroundColor Green
}

# Deployment readiness
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT READINESS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($results.Score -ge 80) {
    Write-Host "STATUS: READY FOR DEPLOYMENT" -ForegroundColor Green -BackgroundColor DarkGreen
    Write-Host ""
    Write-Host "The CSS refactoring meets most success criteria." -ForegroundColor Green
    Write-Host "Proceed with deployment using: .\scripts\deploy-css-refactoring.ps1" -ForegroundColor Cyan
} elseif ($results.Score -ge 60) {
    Write-Host "STATUS: CONDITIONAL DEPLOYMENT" -ForegroundColor Yellow -BackgroundColor DarkYellow
    Write-Host ""
    Write-Host "Some critical issues remain. Review failed tests before deployment." -ForegroundColor Yellow
    Write-Host "Consider addressing high-priority issues first." -ForegroundColor Yellow
} else {
    Write-Host "STATUS: NOT READY FOR DEPLOYMENT" -ForegroundColor Red -BackgroundColor DarkRed
    Write-Host ""
    Write-Host "Multiple critical issues detected. Do not deploy." -ForegroundColor Red
    Write-Host "Address failed tests and run validation again." -ForegroundColor Red
}

# Generate validation report
$reportPath = "$pluginPath\validation-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
$results | ConvertTo-Json -Depth 10 | Out-File -FilePath $reportPath -Encoding UTF8

Write-Host ""
Write-Host "Detailed report saved to: $reportPath" -ForegroundColor Cyan
Write-Host ""

# Exit code based on readiness
if ($results.Score -ge 60) {
    exit 0
} else {
    exit 1
}