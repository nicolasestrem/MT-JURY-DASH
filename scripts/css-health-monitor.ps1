# CSS Health Monitoring Script
# Continuous monitoring for CSS quality and performance
# Mobility Trailblazers WordPress Plugin

param(
    [switch]$Continuous = $false,
    [int]$IntervalMinutes = 60,
    [switch]$SendAlerts = $false
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CSS HEALTH MONITORING SYSTEM" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$pluginPath = "C:\Users\nicol\Desktop\mobility-trailblazers"
$cssPath = "$pluginPath\assets\css"
$reportsPath = "$pluginPath\monitoring-reports"
$baseUrl = "http://localhost:8080"

# Thresholds
$thresholds = @{
    MaxFiles = 20
    MaxImportant = 100
    MaxSizeKB = 500
    MaxLoadTimeMs = 2000
    MinTokens = 50
    MaxDuplicates = 10
}

# Create reports directory
if (!(Test-Path $reportsPath)) {
    New-Item -ItemType Directory -Path $reportsPath | Out-Null
}

# Function to check CSS file metrics
function Get-CSSMetrics {
    Write-Host "Analyzing CSS metrics..." -ForegroundColor Yellow
    
    $metrics = @{
        Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        FileCount = 0
        TotalSizeKB = 0
        ImportantCount = 0
        TokenCount = 0
        DuplicateSelectors = 0
        EmptyRules = 0
        Files = @()
    }
    
    # Get all CSS files
    $cssFiles = Get-ChildItem -Path $cssPath -Recurse -Filter "*.css" | Where-Object { $_.Name -notlike "*.min.css" }
    $metrics.FileCount = $cssFiles.Count
    
    foreach ($file in $cssFiles) {
        $content = Get-Content $file.FullName -Raw
        $fileSize = [math]::Round($file.Length / 1KB, 2)
        
        # Count !important
        $importantCount = ([regex]::Matches($content, "!important")).Count
        $metrics.ImportantCount += $importantCount
        
        # Count empty rules
        $emptyRules = ([regex]::Matches($content, "\{\s*\}")).Count
        $metrics.EmptyRules += $emptyRules
        
        # Store file info
        $metrics.Files += @{
            Name = $file.Name
            Path = $file.FullName.Replace($pluginPath, "")
            SizeKB = $fileSize
            ImportantCount = $importantCount
            EmptyRules = $emptyRules
        }
        
        $metrics.TotalSizeKB += $fileSize
    }
    
    # Check for CSS tokens
    $tokenFile = Get-ChildItem -Path $cssPath -Recurse -Filter "*token*.css" | Select-Object -First 1
    if ($tokenFile) {
        $tokenContent = Get-Content $tokenFile.FullName -Raw
        $metrics.TokenCount = ([regex]::Matches($tokenContent, "--mt-[\w-]+:")).Count
    }
    
    # Detect duplicate selectors (simplified check)
    $allSelectors = @()
    foreach ($file in $cssFiles) {
        $content = Get-Content $file.FullName -Raw
        $selectors = [regex]::Matches($content, "([.#][\w-]+)\s*\{") | ForEach-Object { $_.Groups[1].Value }
        $allSelectors += $selectors
    }
    $uniqueSelectors = $allSelectors | Select-Object -Unique
    $metrics.DuplicateSelectors = $allSelectors.Count - $uniqueSelectors.Count
    
    return $metrics
}

# Function to test performance
function Test-Performance {
    Write-Host "Testing page load performance..." -ForegroundColor Yellow
    
    $performance = @{
        Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        Pages = @()
        AverageLoadTime = 0
    }
    
    $testPages = @(
        @{Name="Homepage"; Url="$baseUrl/"},
        @{Name="Jury Dashboard"; Url="$baseUrl/vote/"},
        @{Name="Candidate Page"; Url="$baseUrl/candidate/nic-knapp/"}
    )
    
    $totalTime = 0
    foreach ($page in $testPages) {
        try {
            $start = Get-Date
            $response = Invoke-WebRequest -Uri $page.Url -UseBasicParsing -TimeoutSec 10
            $loadTime = [math]::Round(((Get-Date) - $start).TotalMilliseconds, 2)
            
            $performance.Pages += @{
                Name = $page.Name
                LoadTimeMs = $loadTime
                StatusCode = $response.StatusCode
                Success = $true
            }
            
            $totalTime += $loadTime
        }
        catch {
            $performance.Pages += @{
                Name = $page.Name
                LoadTimeMs = 0
                StatusCode = 0
                Success = $false
                Error = $_.Exception.Message
            }
        }
    }
    
    if ($performance.Pages | Where-Object { $_.Success }) {
        $successfulPages = $performance.Pages | Where-Object { $_.Success }
        $performance.AverageLoadTime = [math]::Round(($successfulPages | Measure-Object -Property LoadTimeMs -Average).Average, 2)
    }
    
    return $performance
}

# Function to check health status
function Get-HealthStatus {
    param($metrics, $performance)
    
    $health = @{
        Status = "Healthy"
        Score = 100
        Issues = @()
        Warnings = @()
    }
    
    # Check file count
    if ($metrics.FileCount -gt $thresholds.MaxFiles) {
        $health.Issues += "Too many CSS files: $($metrics.FileCount) (threshold: $($thresholds.MaxFiles))"
        $health.Score -= 15
    }
    
    # Check !important usage
    if ($metrics.ImportantCount -gt $thresholds.MaxImportant) {
        $health.Issues += "Excessive !important: $($metrics.ImportantCount) (threshold: $($thresholds.MaxImportant))"
        $health.Score -= 20
    }
    elseif ($metrics.ImportantCount -gt ($thresholds.MaxImportant * 0.5)) {
        $health.Warnings += "High !important usage: $($metrics.ImportantCount)"
        $health.Score -= 5
    }
    
    # Check file size
    if ($metrics.TotalSizeKB -gt $thresholds.MaxSizeKB) {
        $health.Issues += "CSS too large: $($metrics.TotalSizeKB)KB (threshold: $($thresholds.MaxSizeKB)KB)"
        $health.Score -= 10
    }
    
    # Check performance
    if ($performance.AverageLoadTime -gt $thresholds.MaxLoadTimeMs) {
        $health.Issues += "Slow load time: $($performance.AverageLoadTime)ms (threshold: $($thresholds.MaxLoadTimeMs)ms)"
        $health.Score -= 15
    }
    
    # Check tokens
    if ($metrics.TokenCount -lt $thresholds.MinTokens) {
        $health.Warnings += "Low token count: $($metrics.TokenCount) (threshold: $($thresholds.MinTokens))"
        $health.Score -= 5
    }
    
    # Check duplicates
    if ($metrics.DuplicateSelectors -gt $thresholds.MaxDuplicates) {
        $health.Warnings += "Duplicate selectors: $($metrics.DuplicateSelectors)"
        $health.Score -= 5
    }
    
    # Check empty rules
    if ($metrics.EmptyRules -gt 0) {
        $health.Warnings += "Empty CSS rules found: $($metrics.EmptyRules)"
        $health.Score -= 3
    }
    
    # Determine overall status
    if ($health.Score -ge 90) {
        $health.Status = "Healthy"
    }
    elseif ($health.Score -ge 70) {
        $health.Status = "Warning"
    }
    else {
        $health.Status = "Critical"
    }
    
    return $health
}

# Function to display dashboard
function Show-Dashboard {
    param($metrics, $performance, $health)
    
    Clear-Host
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "CSS HEALTH MONITORING DASHBOARD" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Last Updated: $($metrics.Timestamp)" -ForegroundColor Gray
    Write-Host ""
    
    # Health Status
    $statusColor = switch ($health.Status) {
        "Healthy" { "Green" }
        "Warning" { "Yellow" }
        "Critical" { "Red" }
    }
    
    Write-Host "HEALTH STATUS: $($health.Status) (Score: $($health.Score)/100)" -ForegroundColor $statusColor
    Write-Host ""
    
    # Metrics Summary
    Write-Host "CSS METRICS" -ForegroundColor Cyan
    Write-Host "-----------" -ForegroundColor Gray
    Write-Host "Files: $($metrics.FileCount) $(if($metrics.FileCount -gt $thresholds.MaxFiles){'[!]'})" -ForegroundColor $(if($metrics.FileCount -gt $thresholds.MaxFiles){'Yellow'}else{'Green'})
    Write-Host "Total Size: $($metrics.TotalSizeKB)KB $(if($metrics.TotalSizeKB -gt $thresholds.MaxSizeKB){'[!]'})" -ForegroundColor $(if($metrics.TotalSizeKB -gt $thresholds.MaxSizeKB){'Yellow'}else{'Green'})
    Write-Host "!important: $($metrics.ImportantCount) $(if($metrics.ImportantCount -gt $thresholds.MaxImportant){'[!]'})" -ForegroundColor $(if($metrics.ImportantCount -gt $thresholds.MaxImportant){'Red'}else{'Green'})
    Write-Host "CSS Tokens: $($metrics.TokenCount) $(if($metrics.TokenCount -lt $thresholds.MinTokens){'[!]'})" -ForegroundColor $(if($metrics.TokenCount -lt $thresholds.MinTokens){'Yellow'}else{'Green'})
    Write-Host "Duplicates: $($metrics.DuplicateSelectors)" -ForegroundColor $(if($metrics.DuplicateSelectors -gt $thresholds.MaxDuplicates){'Yellow'}else{'Green'})
    Write-Host "Empty Rules: $($metrics.EmptyRules)" -ForegroundColor $(if($metrics.EmptyRules -gt 0){'Yellow'}else{'Green'})
    Write-Host ""
    
    # Performance Summary
    Write-Host "PERFORMANCE" -ForegroundColor Cyan
    Write-Host "-----------" -ForegroundColor Gray
    Write-Host "Avg Load Time: $($performance.AverageLoadTime)ms $(if($performance.AverageLoadTime -gt $thresholds.MaxLoadTimeMs){'[!]'})" -ForegroundColor $(if($performance.AverageLoadTime -gt $thresholds.MaxLoadTimeMs){'Yellow'}else{'Green'})
    
    foreach ($page in $performance.Pages) {
        if ($page.Success) {
            Write-Host "  $($page.Name): $($page.LoadTimeMs)ms" -ForegroundColor Gray
        }
        else {
            Write-Host "  $($page.Name): FAILED" -ForegroundColor Red
        }
    }
    Write-Host ""
    
    # Issues and Warnings
    if ($health.Issues.Count -gt 0) {
        Write-Host "CRITICAL ISSUES" -ForegroundColor Red
        Write-Host "---------------" -ForegroundColor Gray
        foreach ($issue in $health.Issues) {
            Write-Host "  • $issue" -ForegroundColor Red
        }
        Write-Host ""
    }
    
    if ($health.Warnings.Count -gt 0) {
        Write-Host "WARNINGS" -ForegroundColor Yellow
        Write-Host "--------" -ForegroundColor Gray
        foreach ($warning in $health.Warnings) {
            Write-Host "  • $warning" -ForegroundColor Yellow
        }
        Write-Host ""
    }
    
    # Top Offenders
    Write-Host "TOP OFFENDERS (!important)" -ForegroundColor Cyan
    Write-Host "-------------------------" -ForegroundColor Gray
    $topFiles = $metrics.Files | Where-Object { $_.ImportantCount -gt 0 } | Sort-Object ImportantCount -Descending | Select-Object -First 5
    foreach ($file in $topFiles) {
        Write-Host "  $($file.Name): $($file.ImportantCount)" -ForegroundColor Gray
    }
}

# Function to generate report
function Generate-Report {
    param($metrics, $performance, $health)
    
    $reportName = "css-health-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $reportPath = "$reportsPath\$reportName"
    
    $report = @{
        Timestamp = $metrics.Timestamp
        Health = $health
        Metrics = $metrics
        Performance = $performance
        Thresholds = $thresholds
    }
    
    $report | ConvertTo-Json -Depth 10 | Out-File -FilePath $reportPath -Encoding UTF8
    
    return $reportPath
}

# Function to send alerts
function Send-Alert {
    param($health)
    
    if ($health.Status -eq "Critical" -and $SendAlerts) {
        Write-Host "`n[ALERT] Critical CSS health issues detected!" -ForegroundColor Red -BackgroundColor DarkRed
        
        # Here you could add email/Slack/webhook notifications
        # For now, just log to event log if available
        try {
            Write-EventLog -LogName Application -Source "CSS Health Monitor" -EventId 1001 -EntryType Error -Message "Critical CSS health issues: $($health.Issues -join '; ')"
        }
        catch {
            # Event log might not be available
        }
    }
}

# Main monitoring loop
function Start-Monitoring {
    do {
        # Collect metrics
        $metrics = Get-CSSMetrics
        $performance = Test-Performance
        $health = Get-HealthStatus -metrics $metrics -performance $performance
        
        # Display dashboard
        Show-Dashboard -metrics $metrics -performance $performance -health $health
        
        # Generate report
        $reportPath = Generate-Report -metrics $metrics -performance $performance -health $health
        Write-Host ""
        Write-Host "Report saved: $reportPath" -ForegroundColor Cyan
        
        # Send alerts if needed
        Send-Alert -health $health
        
        if ($Continuous) {
            Write-Host ""
            Write-Host "Next check in $IntervalMinutes minutes... (Press Ctrl+C to stop)" -ForegroundColor Gray
            Start-Sleep -Seconds ($IntervalMinutes * 60)
        }
        
    } while ($Continuous)
}

# Start monitoring
if ($Continuous) {
    Write-Host "Starting continuous monitoring (check every $IntervalMinutes minutes)" -ForegroundColor Green
    Write-Host "Press Ctrl+C to stop monitoring" -ForegroundColor Yellow
    Write-Host ""
}

Start-Monitoring

if (!$Continuous) {
    Write-Host ""
    Write-Host "Single health check completed." -ForegroundColor Green
    Write-Host "Use -Continuous flag for ongoing monitoring." -ForegroundColor Gray
}