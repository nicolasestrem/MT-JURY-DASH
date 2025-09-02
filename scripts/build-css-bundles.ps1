# Advanced CSS Bundle Builder for Production
# Mobility Trailblazers WordPress Plugin v4.2.0
# Creates optimized CSS bundles with HTTP/2 optimization

param(
    [switch]$Production = $false,
    [switch]$Analyze = $false,
    [switch]$Clean = $false,
    [string]$Environment = "production"
)

$PluginPath = Split-Path $PSScriptRoot -Parent
$SourcePath = Join-Path $PluginPath "assets\css"
$DistPath = Join-Path $PluginPath "assets\css\dist"
$BackupPath = Join-Path $PluginPath "backups\css-bundles-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "CSS Bundle Builder v4.2.0" -ForegroundColor Cyan
Write-Host "Environment: $Environment" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Cyan

# Clean previous builds
if ($Clean) {
    Write-Host "Cleaning previous builds..." -ForegroundColor Yellow
    if (Test-Path $DistPath) {
        Remove-Item -Path $DistPath -Recurse -Force
        Write-Host "  - Removed existing dist folder" -ForegroundColor DarkGray
    }
}

# Create directories
New-Item -ItemType Directory -Path $DistPath -Force | Out-Null
New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null

# Bundle configurations
$BundleConfig = @{
    "mt-core-bundle" = @{
        "files" = @("mt-framework.css", "mt-core.css", "mt-layout.css")
        "description" = "Core framework bundle"
        "priority" = "critical"
    }
    "mt-jury-bundle" = @{
        "files" = @("mt-components.css", "mt-jury-dashboard.css")
        "description" = "Jury dashboard bundle"
        "priority" = "high"
    }
    "mt-candidate-bundle" = @{
        "files" = @("mt-components.css", "frontend.css")
        "description" = "Candidate pages bundle"
        "priority" = "high"
    }
    "mt-admin-bundle" = @{
        "files" = @("mt-admin.css", "mt-evaluations-admin.css")
        "description" = "Admin interface bundle"
        "priority" = "medium"
    }
    "mt-public-bundle" = @{
        "files" = @("mt-components.css", "frontend.css")
        "description" = "Public pages bundle"
        "priority" = "medium"
    }
    "mt-responsive-bundle" = @{
        "files" = @("mt-responsive.css", "mt-mobile.css")
        "description" = "Responsive styles bundle"
        "priority" = "low"
    }
}

$TotalOriginalSize = 0
$TotalBundledSize = 0
$ProcessedBundles = 0
$BundleStats = @()

Write-Host "Building CSS bundles..." -ForegroundColor White
Write-Host ""

foreach ($BundleName in $BundleConfig.Keys) {
    $Bundle = $BundleConfig[$BundleName]
    $BundleContent = ""
    $BundleOriginalSize = 0
    $MissingFiles = @()
    
    Write-Host "Processing: $BundleName" -ForegroundColor Yellow
    Write-Host "  Description: $($Bundle.description)" -ForegroundColor DarkGray
    Write-Host "  Priority: $($Bundle.priority)" -ForegroundColor DarkGray
    
    # Bundle header
    $BundleHeader = @"
/*
 * $BundleName.min.css
 * Mobility Trailblazers CSS Bundle
 * Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
 * Environment: $Environment
 * Priority: $($Bundle.priority)
 * Files: $($Bundle.files -join ', ')
 */

"@
    
    $BundleContent += $BundleHeader
    
    # Process each file in the bundle
    foreach ($FileName in $Bundle.files) {
        $FilePath = Join-Path $SourcePath $FileName
        
        if (Test-Path $FilePath) {
            $FileContent = Get-Content -Path $FilePath -Raw -Encoding UTF8
            $FileSize = (Get-Item $FilePath).Length
            $BundleOriginalSize += $FileSize
            
            Write-Host "    + $FileName ($([Math]::Round($FileSize/1024, 2)) KB)" -ForegroundColor Green
            
            # Add file separator comment
            $BundleContent += "`n/* === $FileName === */`n"
            $BundleContent += $FileContent
            $BundleContent += "`n"
            
        } else {
            $MissingFiles += $FileName
            Write-Host "    - $FileName (MISSING)" -ForegroundColor Red
        }
    }
    
    # Skip bundle if files are missing
    if ($MissingFiles.Count -gt 0) {
        Write-Host "  Warning: Skipping bundle due to missing files" -ForegroundColor Yellow
        continue
    }
    
    # Optimize and minify CSS
    $MinifiedContent = Optimize-CSS -Content $BundleContent -BundleName $BundleName
    
    # Write bundle file
    $BundleFileName = "$BundleName.min.css"
    $BundleFilePath = Join-Path $DistPath $BundleFileName
    
    try {
        # Write with UTF-8 encoding without BOM
        $Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllText($BundleFilePath, $MinifiedContent, $Utf8NoBomEncoding)
        
        $BundledSize = (Get-Item $BundleFilePath).Length
        $Savings = if ($BundleOriginalSize -gt 0) { 
            [Math]::Round((($BundleOriginalSize - $BundledSize) / $BundleOriginalSize) * 100, 2) 
        } else { 0 }
        
        Write-Host "  Original size: $([Math]::Round($BundleOriginalSize/1024, 2)) KB" -ForegroundColor Gray
        Write-Host "  Bundled size: $([Math]::Round($BundledSize/1024, 2)) KB" -ForegroundColor Green
        Write-Host "  Compression: $Savings%" -ForegroundColor Cyan
        
        $TotalOriginalSize += $BundleOriginalSize
        $TotalBundledSize += $BundledSize
        $ProcessedBundles++
        
        # Store stats
        $BundleStats += [PSCustomObject]@{
            Name = $BundleName
            Priority = $Bundle.priority
            Files = $Bundle.files.Count
            OriginalSize = $BundleOriginalSize
            BundledSize = $BundledSize
            Savings = $Savings
            FilePath = $BundleFilePath
        }
        
    } catch {
        Write-Host "  ERROR: Failed to write bundle - $_" -ForegroundColor Red
    }
    
    Write-Host ""
}

# Generate bundle manifest
$ManifestData = @{
    "generated" = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
    "environment" = $Environment
    "version" = "4.2.0"
    "bundles" = @{}
    "stats" = @{
        "totalBundles" = $ProcessedBundles
        "totalOriginalSize" = $TotalOriginalSize
        "totalBundledSize" = $TotalBundledSize
        "totalSavings" = if ($TotalOriginalSize -gt 0) { [Math]::Round((($TotalOriginalSize - $TotalBundledSize) / $TotalOriginalSize) * 100, 2) } else { 0 }
    }
}

foreach ($Bundle in $BundleStats) {
    $ManifestData.bundles[$Bundle.Name] = @{
        "priority" = $Bundle.Priority
        "fileCount" = $Bundle.Files
        "originalSize" = $Bundle.OriginalSize
        "bundledSize" = $Bundle.BundledSize
        "savings" = $Bundle.Savings
        "url" = "assets/css/dist/$($Bundle.Name).min.css"
    }
}

# Write manifest
$ManifestPath = Join-Path $DistPath "bundles-manifest.json"
$ManifestJson = $ManifestData | ConvertTo-Json -Depth 4 -Compress:$false
[System.IO.File]::WriteAllText($ManifestPath, $ManifestJson, [System.Text.Encoding]::UTF8)

# Performance analysis
if ($Analyze) {
    Write-Host "=====================================" -ForegroundColor Cyan
    Write-Host "Performance Analysis" -ForegroundColor Cyan
    Write-Host "=====================================" -ForegroundColor Cyan
    
    # HTTP/2 optimization analysis
    $CriticalBundles = $BundleStats | Where-Object { $_.Priority -eq "critical" }
    $HighPriorityBundles = $BundleStats | Where-Object { $_.Priority -eq "high" }
    
    Write-Host "HTTP/2 Push Candidates:" -ForegroundColor Yellow
    foreach ($Bundle in ($CriticalBundles + $HighPriorityBundles)) {
        $SizeKB = [Math]::Round($Bundle.BundledSize / 1024, 2)
        $PushRecommendation = if ($SizeKB -lt 20) { "RECOMMENDED" } else { "CONSIDER" }
        Write-Host "  - $($Bundle.Name): $SizeKB KB [$PushRecommendation]" -ForegroundColor $(if ($SizeKB -lt 20) { "Green" } else { "Yellow" })
    }
    
    # Bundle loading strategy
    Write-Host "`nLoading Strategy Recommendations:" -ForegroundColor Yellow
    Write-Host "  1. Inline critical CSS from mt-core-bundle (< 8KB)" -ForegroundColor White
    Write-Host "  2. Preload high-priority bundles with resource hints" -ForegroundColor White
    Write-Host "  3. Async load responsive bundle for mobile optimization" -ForegroundColor White
    Write-Host "  4. Use HTTP/2 Server Push for critical bundles" -ForegroundColor White
}

# Summary
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Build Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Bundles created: $ProcessedBundles" -ForegroundColor Green
Write-Host "Total original size: $([Math]::Round($TotalOriginalSize/1024, 2)) KB" -ForegroundColor Gray
Write-Host "Total bundled size: $([Math]::Round($TotalBundledSize/1024, 2)) KB" -ForegroundColor Green
Write-Host "Total savings: $([Math]::Round((($TotalOriginalSize - $TotalBundledSize) / $TotalOriginalSize) * 100, 2))%" -ForegroundColor Cyan
Write-Host ""
Write-Host "Files created in: $DistPath" -ForegroundColor DarkCyan
Write-Host "Manifest: bundles-manifest.json" -ForegroundColor DarkCyan
Write-Host ""

# HTTP requests reduction
$OriginalRequests = ($BundleConfig.Values | ForEach-Object { $_.files.Count } | Measure-Object -Sum).Sum
$BundledRequests = $ProcessedBundles
$RequestReduction = $OriginalRequests - $BundledRequests

Write-Host "HTTP Requests Optimization:" -ForegroundColor Yellow
Write-Host "  Before: $OriginalRequests CSS files" -ForegroundColor Red
Write-Host "  After: $BundledRequests bundle files" -ForegroundColor Green
Write-Host "  Reduction: $RequestReduction requests ($([Math]::Round(($RequestReduction / $OriginalRequests) * 100, 0))%)" -ForegroundColor Cyan

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Update MT_Optimized_Assets to use these bundles in production" -ForegroundColor White
Write-Host "2. Configure HTTP/2 Server Push headers" -ForegroundColor White
Write-Host "3. Test loading performance with bundle manifest" -ForegroundColor White
Write-Host "4. Enable bundle caching with long expiry headers" -ForegroundColor White

Write-Host ""
Write-Host "Build completed successfully!" -ForegroundColor Green

# CSS Optimization Function
function Optimize-CSS {
    param(
        [string]$Content,
        [string]$BundleName
    )
    
    # Advanced CSS minification
    $OptimizedContent = $Content
    
    # Remove comments (preserve license comments)
    $OptimizedContent = $OptimizedContent -replace '/\*(?!\!)[^*]*\*+(?:[^/*][^*]*\*+)*/', ''
    
    # Remove unnecessary whitespace
    $OptimizedContent = $OptimizedContent -replace '\s+', ' '
    $OptimizedContent = $OptimizedContent -replace '\s*([{}:;,>+~])\s*', '$1'
    $OptimizedContent = $OptimizedContent -replace ';\s*}', '}'
    $OptimizedContent = $OptimizedContent -replace '\s*!important', '!important'
    
    # Optimize CSS properties
    $OptimizedContent = $OptimizedContent -replace ':\s*0px\b', ':0'
    $OptimizedContent = $OptimizedContent -replace ':\s*0em\b', ':0'
    $OptimizedContent = $OptimizedContent -replace ':\s*0%\b', ':0'
    
    # Optimize colors (basic)
    $OptimizedContent = $OptimizedContent -replace '#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3', '#$1$2$3'
    
    # Remove trailing semicolon before closing brace
    $OptimizedContent = $OptimizedContent -replace ';}', '}'
    
    # Trim final result
    return $OptimizedContent.Trim()
}