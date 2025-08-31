# CSS Minification Script for Production
# Mobility Trailblazers WordPress Plugin
# Date: 2025-08-25

param(
    [switch]$Production = $false,
    [switch]$Backup = $true
)

$PluginPath = Split-Path $PSScriptRoot -Parent
$CSSPath = Join-Path $PluginPath "assets\css"
$BackupPath = Join-Path $PluginPath "backups\css-pre-minify-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "CSS Minification Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# CSS files to minify
$CSSFiles = @(
    "mt-critical.css",
    "mt-core.css",
    "mt-components.css",
    "mt-mobile.css",
    "mt-admin.css",
    "mt-specificity-layer.css"
)

# Create backup if requested
if ($Backup) {
    Write-Host "Creating backup: $BackupPath" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
    
    foreach ($File in $CSSFiles) {
        $SourceFile = Join-Path $CSSPath $File
        if (Test-Path $SourceFile) {
            Copy-Item -Path $SourceFile -Destination $BackupPath -Force
            Write-Host "  - Backed up: $File" -ForegroundColor DarkGray
        }
    }
}

$MinifiedCount = 0
$TotalSizeBefore = 0
$TotalSizeAfter = 0

foreach ($File in $CSSFiles) {
    $SourceFile = Join-Path $CSSPath $File
    $MinFile = $SourceFile -replace '\.css$', '.min.css'
    
    if (-not (Test-Path $SourceFile)) {
        Write-Host "File not found: $File" -ForegroundColor Red
        continue
    }
    
    Write-Host "Processing: $File" -ForegroundColor White
    
    try {
        # Get original size
        $OriginalSize = (Get-Item $SourceFile).Length
        $TotalSizeBefore += $OriginalSize
        
        # Read CSS content
        $Content = Get-Content -Path $SourceFile -Raw
        
        # Basic CSS minification (preserve UTF-8 encoding)
        # Remove comments
        $Content = $Content -replace '/\*[\s\S]*?\*/', ''
        
        # Remove unnecessary whitespace
        $Content = $Content -replace '\s+', ' '
        $Content = $Content -replace '\s*:\s*', ':'
        $Content = $Content -replace '\s*;\s*', ';'
        $Content = $Content -replace '\s*\{\s*', '{'
        $Content = $Content -replace '\s*\}\s*', '}'
        $Content = $Content -replace '\s*,\s*', ','
        $Content = $Content -replace ';\}', '}'
        
        # Remove last semicolon before closing brace
        $Content = $Content -replace ';}', '}'
        
        # Trim
        $Content = $Content.Trim()
        
        # Add minification notice
        $Header = "/* Minified: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - v4.1.0 */"
        $Content = "$Header`n$Content"
        
        # Write minified content (UTF-8 without BOM)
        $Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllText($MinFile, $Content, $Utf8NoBomEncoding)
        
        # Get minified size
        $MinifiedSize = (Get-Item $MinFile).Length
        $TotalSizeAfter += $MinifiedSize
        
        # Calculate savings
        $Savings = [Math]::Round((($OriginalSize - $MinifiedSize) / $OriginalSize) * 100, 2)
        
        Write-Host "  - Original: $([Math]::Round($OriginalSize/1024, 2)) KB" -ForegroundColor Gray
        Write-Host "  - Minified: $([Math]::Round($MinifiedSize/1024, 2)) KB" -ForegroundColor Green
        Write-Host "  - Savings: $Savings%" -ForegroundColor Cyan
        
        $MinifiedCount++
        
        # In production mode, replace original with minified
        if ($Production) {
            Copy-Item -Path $MinFile -Destination $SourceFile -Force
            Write-Host "  - Replaced original with minified version" -ForegroundColor Yellow
        }
        
    } catch {
        Write-Host "  ERROR: $_" -ForegroundColor Red
    }
    
    Write-Host ""
}

# Summary
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Files processed: $($CSSFiles.Count)" -ForegroundColor White
Write-Host "Files minified: $MinifiedCount" -ForegroundColor Green

if ($TotalSizeBefore -gt 0) {
    $TotalSavings = [Math]::Round((($TotalSizeBefore - $TotalSizeAfter) / $TotalSizeBefore) * 100, 2)
    Write-Host ""
    Write-Host "Total size before: $([Math]::Round($TotalSizeBefore/1024, 2)) KB" -ForegroundColor Gray
    Write-Host "Total size after: $([Math]::Round($TotalSizeAfter/1024, 2)) KB" -ForegroundColor Green
    Write-Host "Total savings: $TotalSavings%" -ForegroundColor Cyan
}

if ($Production) {
    Write-Host ""
    Write-Host "PRODUCTION MODE: Original files replaced with minified versions" -ForegroundColor Yellow
} else {
    Write-Host ""
    Write-Host "Development mode: Minified files created as .min.css" -ForegroundColor Cyan
    Write-Host "Run with -Production flag to replace originals" -ForegroundColor Cyan
}

if ($Backup) {
    Write-Host ""
    Write-Host "Backup location: $BackupPath" -ForegroundColor DarkCyan
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green