# CSS Performance Testing Script
# Mobility Trailblazers WordPress Plugin
# Tests loading performance before/after CSS optimization

param(
    [string]$BaseUrl = "http://localhost:8080",
    [switch]$Detailed = $false,
    [switch]$Compare = $false,
    [string]$OutputFile = ""
)

$PluginPath = Split-Path $PSScriptRoot -Parent
$ResultsPath = Join-Path $PluginPath "scripts\logs"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "CSS Performance Testing" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Base URL: $BaseUrl" -ForegroundColor Yellow
Write-Host ""

# Ensure results directory exists
New-Item -ItemType Directory -Path $ResultsPath -Force | Out-Null

# Test pages
$TestPages = @(
    @{ Name = "Jury Dashboard"; Url = "$BaseUrl/vote/" }
    @{ Name = "Candidates Archive"; Url = "$BaseUrl/candidates/" }
    @{ Name = "Single Candidate"; Url = "$BaseUrl/candidate/sample-candidate/" }
    @{ Name = "Homepage"; Url = "$BaseUrl/" }
)

$TestResults = @()

function Test-PagePerformance {
    param(
        [string]$Url,
        [string]$PageName
    )
    
    Write-Host "Testing: $PageName" -ForegroundColor Yellow
    Write-Host "  URL: $Url" -ForegroundColor DarkGray
    
    try {
        # Use PowerShell's Measure-Command for timing
        $LoadTime = Measure-Command {
            $Response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 30
        }
        
        # Analyze response
        $ContentLength = $Response.Content.Length
        $StatusCode = $Response.StatusCode
        $LoadTimeMs = [Math]::Round($LoadTime.TotalMilliseconds, 2)
        
        # Extract CSS links from response
        $CssMatches = [regex]::Matches($Response.Content, '<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+\.css[^"\']*)["\'][^>]*>')
        $CssFiles = @()
        foreach ($Match in $CssMatches) {
            $CssFiles += $Match.Groups[1].Value
        }
        
        # Count MT-specific CSS files
        $MtCssCount = ($CssFiles | Where-Object { $_ -match 'mt-|mobility' }).Count
        $TotalCssCount = $CssFiles.Count
        
        Write-Host "    Load Time: $LoadTimeMs ms" -ForegroundColor $(if ($LoadTimeMs -lt 1000) { "Green" } elseif ($LoadTimeMs -lt 2000) { "Yellow" } else { "Red" })
        Write-Host "    Status: $StatusCode" -ForegroundColor $(if ($StatusCode -eq 200) { "Green" } else { "Red" })
        Write-Host "    Content Size: $([Math]::Round($ContentLength / 1024, 2)) KB" -ForegroundColor Gray
        Write-Host "    CSS Files: $TotalCssCount total, $MtCssCount MT-specific" -ForegroundColor $(if ($MtCssCount -lt 5) { "Green" } elseif ($MtCssCount -lt 10) { "Yellow" } else { "Red" })
        
        if ($Detailed) {
            Write-Host "    MT CSS Files:" -ForegroundColor Cyan
            $MtCssFiles = $CssFiles | Where-Object { $_ -match 'mt-|mobility' }
            foreach ($File in $MtCssFiles) {
                Write-Host "      - $File" -ForegroundColor DarkCyan
            }
        }
        
        # Return result object
        return [PSCustomObject]@{
            PageName = $PageName
            Url = $Url
            LoadTimeMs = $LoadTimeMs
            StatusCode = $StatusCode
            ContentLength = $ContentLength
            TotalCssFiles = $TotalCssCount
            MtCssFiles = $MtCssCount
            CssFilesList = $CssFiles
            Timestamp = Get-Date
            Success = ($StatusCode -eq 200 -and $LoadTimeMs -lt 3000)
        }
        
    } catch {
        Write-Host "    ERROR: $_" -ForegroundColor Red
        
        return [PSCustomObject]@{
            PageName = $PageName
            Url = $Url
            LoadTimeMs = -1
            StatusCode = 0
            ContentLength = 0
            TotalCssFiles = 0
            MtCssFiles = 0
            CssFilesList = @()
            Error = $_.Exception.Message
            Timestamp = Get-Date
            Success = $false
        }
    }
    
    Write-Host ""
}

# Test each page
foreach ($Page in $TestPages) {
    $Result = Test-PagePerformance -Url $Page.Url -PageName $Page.Name
    $TestResults += $Result
}

# Calculate summary statistics
$SuccessfulTests = $TestResults | Where-Object { $_.Success }
$FailedTests = $TestResults | Where-Object { -not $_.Success }

if ($SuccessfulTests.Count -gt 0) {
    $AverageLoadTime = ($SuccessfulTests | Measure-Object -Property LoadTimeMs -Average).Average
    $MaxLoadTime = ($SuccessfulTests | Measure-Object -Property LoadTimeMs -Maximum).Maximum
    $MinLoadTime = ($SuccessfulTests | Measure-Object -Property LoadTimeMs -Minimum).Minimum
    $AverageCssFiles = ($SuccessfulTests | Measure-Object -Property MtCssFiles -Average).Average
    $TotalContentSize = ($SuccessfulTests | Measure-Object -Property ContentLength -Sum).Sum
}

# Display summary
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Performance Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Tests completed: $($TestResults.Count)" -ForegroundColor White
Write-Host "Successful: $($SuccessfulTests.Count)" -ForegroundColor Green
Write-Host "Failed: $($FailedTests.Count)" -ForegroundColor $(if ($FailedTests.Count -eq 0) { "Green" } else { "Red" })

if ($SuccessfulTests.Count -gt 0) {
    Write-Host ""
    Write-Host "Load Time Statistics:" -ForegroundColor Yellow
    Write-Host "  Average: $([Math]::Round($AverageLoadTime, 2)) ms" -ForegroundColor $(if ($AverageLoadTime -lt 1000) { "Green" } elseif ($AverageLoadTime -lt 2000) { "Yellow" } else { "Red" })
    Write-Host "  Fastest: $([Math]::Round($MinLoadTime, 2)) ms" -ForegroundColor Green
    Write-Host "  Slowest: $([Math]::Round($MaxLoadTime, 2)) ms" -ForegroundColor $(if ($MaxLoadTime -lt 2000) { "Yellow" } else { "Red" })
    
    Write-Host ""
    Write-Host "CSS File Statistics:" -ForegroundColor Yellow
    Write-Host "  Average MT CSS files per page: $([Math]::Round($AverageCssFiles, 1))" -ForegroundColor $(if ($AverageCssFiles -lt 5) { "Green" } elseif ($AverageCssFiles -lt 10) { "Yellow" } else { "Red" })
    Write-Host "  Total content size: $([Math]::Round($TotalContentSize / 1024 / 1024, 2)) MB" -ForegroundColor Gray
    
    # Performance recommendations
    Write-Host ""
    Write-Host "Recommendations:" -ForegroundColor Yellow
    if ($AverageLoadTime -gt 1500) {
        Write-Host "  - Consider enabling CSS bundling for faster load times" -ForegroundColor Red
    }
    if ($AverageCssFiles -gt 7) {
        Write-Host "  - High CSS file count detected, bundling recommended" -ForegroundColor Red
    }
    if ($MaxLoadTime -gt 3000) {
        Write-Host "  - Some pages are loading slowly, investigate bottlenecks" -ForegroundColor Red
    }
    if ($AverageLoadTime -lt 1000 -and $AverageCssFiles -lt 5) {
        Write-Host "  - Performance looks good! CSS optimization is working" -ForegroundColor Green
    }
}

# Failed tests details
if ($FailedTests.Count -gt 0) {
    Write-Host ""
    Write-Host "Failed Tests:" -ForegroundColor Red
    foreach ($Failed in $FailedTests) {
        Write-Host "  - $($Failed.PageName): $($Failed.Error)" -ForegroundColor Red
    }
}

# Save results to file
$ResultsData = @{
    TestRun = @{
        Timestamp = Get-Date
        BaseUrl = $BaseUrl
        TotalTests = $TestResults.Count
        SuccessfulTests = $SuccessfulTests.Count
        FailedTests = $FailedTests.Count
    }
    Summary = if ($SuccessfulTests.Count -gt 0) {
        @{
            AverageLoadTimeMs = [Math]::Round($AverageLoadTime, 2)
            MinLoadTimeMs = [Math]::Round($MinLoadTime, 2)
            MaxLoadTimeMs = [Math]::Round($MaxLoadTime, 2)
            AverageMtCssFiles = [Math]::Round($AverageCssFiles, 1)
            TotalContentSizeMB = [Math]::Round($TotalContentSize / 1024 / 1024, 2)
        }
    } else { $null }
    Results = $TestResults
}

$ResultsJson = $ResultsData | ConvertTo-Json -Depth 4
$ResultsFile = if ($OutputFile) { $OutputFile } else { Join-Path $ResultsPath "css-performance-$(Get-Date -Format 'yyyyMMdd-HHmmss').json" }
$ResultsJson | Out-File -FilePath $ResultsFile -Encoding UTF8

Write-Host ""
Write-Host "Results saved to: $ResultsFile" -ForegroundColor DarkCyan

# Compare with previous results if requested
if ($Compare) {
    Write-Host ""
    Write-Host "=====================================" -ForegroundColor Cyan
    Write-Host "Performance Comparison" -ForegroundColor Cyan
    Write-Host "=====================================" -ForegroundColor Cyan
    
    # Find previous results
    $PreviousResults = Get-ChildItem -Path $ResultsPath -Filter "css-performance-*.json" | 
                      Sort-Object LastWriteTime -Descending | 
                      Select-Object -Skip 1 -First 1
    
    if ($PreviousResults) {
        try {
            $PreviousData = Get-Content -Path $PreviousResults.FullName -Raw | ConvertFrom-Json
            
            if ($PreviousData.Summary -and $ResultsData.Summary) {
                $LoadTimeChange = $ResultsData.Summary.AverageLoadTimeMs - $PreviousData.Summary.AverageLoadTimeMs
                $CssFileChange = $ResultsData.Summary.AverageMtCssFiles - $PreviousData.Summary.AverageMtCssFiles
                
                Write-Host "Comparing with: $($PreviousResults.Name)" -ForegroundColor Gray
                Write-Host "Load time change: $([Math]::Round($LoadTimeChange, 2)) ms" -ForegroundColor $(if ($LoadTimeChange -lt 0) { "Green" } elseif ($LoadTimeChange -lt 100) { "Yellow" } else { "Red" })
                Write-Host "CSS files change: $([Math]::Round($CssFileChange, 1))" -ForegroundColor $(if ($CssFileChange -lt 0) { "Green" } elseif ($CssFileChange -eq 0) { "Yellow" } else { "Red" })
                
                if ($LoadTimeChange -lt -200) {
                    Write-Host "🎉 Significant performance improvement detected!" -ForegroundColor Green
                } elseif ($LoadTimeChange -gt 200) {
                    Write-Host "⚠️ Performance regression detected!" -ForegroundColor Red
                }
            }
        } catch {
            Write-Host "Could not compare with previous results: $_" -ForegroundColor Yellow
        }
    } else {
        Write-Host "No previous results found for comparison" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Performance testing completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. If performance is poor, run: .\build-css-bundles.ps1 -Production" -ForegroundColor White
Write-Host "2. Test again to verify improvements" -ForegroundColor White
Write-Host "3. Use -Compare flag to track performance over time" -ForegroundColor White