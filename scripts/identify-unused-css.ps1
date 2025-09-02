# CSS File Cleanup Analysis Script
# Identifies unused CSS files that can be safely removed after consolidation

param(
    [switch]$DryRun = $true,
    [switch]$Force = $false
)

$PluginPath = Split-Path $PSScriptRoot -Parent
$CSSPath = Join-Path $PluginPath "assets\css"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "CSS File Cleanup Analysis" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Current active CSS files (v4 framework)
$ActiveFiles = @(
    "mt-framework.css",
    "mt-core.css", 
    "mt-components.css",
    "mt-layout.css",
    "mt-responsive.css",
    "mt-jury-dashboard.css",
    "frontend.css",
    "mt-admin.css",
    "mt-evaluations-admin.css",
    "mt-mobile.css"
)

# Documentation files to keep
$DocumentationFiles = @(
    "CSS-STRUCTURE-DOCUMENTATION.md"
)

# Get all CSS files
$AllFiles = Get-ChildItem -Path $CSSPath -Filter "*.css" | Select-Object -ExpandProperty Name
$AllDocs = Get-ChildItem -Path $CSSPath -Filter "*.md" | Select-Object -ExpandProperty Name

# Identify unused files
$UnusedFiles = $AllFiles | Where-Object { $_ -notin $ActiveFiles }
$UnusedDocs = $AllDocs | Where-Object { $_ -notin $DocumentationFiles }

Write-Host "Current CSS File Inventory:" -ForegroundColor Yellow
Write-Host "  Total files: $($AllFiles.Count)" -ForegroundColor White
Write-Host "  Active files: $($ActiveFiles.Count)" -ForegroundColor Green  
Write-Host "  Unused files: $($UnusedFiles.Count)" -ForegroundColor Red
Write-Host ""

# Display active files
Write-Host "✅ Active CSS Files (KEEP):" -ForegroundColor Green
foreach ($File in $ActiveFiles) {
    $FilePath = Join-Path $CSSPath $File
    if (Test-Path $FilePath) {
        $Size = [Math]::Round((Get-Item $FilePath).Length / 1024, 2)
        Write-Host "  ✓ $File ($Size KB)" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ $File (MISSING)" -ForegroundColor Yellow
    }
}

Write-Host ""

# Display unused files
if ($UnusedFiles.Count -gt 0) {
    Write-Host "❌ Unused CSS Files (CAN REMOVE):" -ForegroundColor Red
    $TotalUnusedSize = 0
    
    foreach ($File in $UnusedFiles) {
        $FilePath = Join-Path $CSSPath $File
        if (Test-Path $FilePath) {
            $Size = [Math]::Round((Get-Item $FilePath).Length / 1024, 2)
            $TotalUnusedSize += $Size
            Write-Host "  ❌ $File ($Size KB)" -ForegroundColor Red
        }
    }
    
    Write-Host "  Total unused size: $([Math]::Round($TotalUnusedSize, 2)) KB" -ForegroundColor Red
} else {
    Write-Host "✅ No unused CSS files found!" -ForegroundColor Green
}

# Display unused documentation
if ($UnusedDocs.Count -gt 0) {
    Write-Host ""
    Write-Host "📄 Unused Documentation (REVIEW):" -ForegroundColor Yellow
    foreach ($Doc in $UnusedDocs) {
        Write-Host "  📄 $Doc" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Cleanup Recommendations" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

if ($UnusedFiles.Count -eq 0) {
    Write-Host "✅ CSS directory is already clean!" -ForegroundColor Green
    Write-Host "All files are part of the v4 framework architecture." -ForegroundColor Green
} else {
    Write-Host "The following files can potentially be removed:" -ForegroundColor Yellow
    Write-Host ""
    
    # Analyze file patterns
    $LegacyFiles = $UnusedFiles | Where-Object { $_ -match "legacy|old|backup|temp" }
    $EmergencyFiles = $UnusedFiles | Where-Object { $_ -match "emergency|fix|hotfix" }
    $TestFiles = $UnusedFiles | Where-Object { $_ -match "test|debug|dev" }
    $V3Files = $UnusedFiles | Where-Object { $_ -match "v3|deprecated" }
    
    if ($LegacyFiles.Count -gt 0) {
        Write-Host "🗂️ Legacy Files (SAFE TO REMOVE):" -ForegroundColor DarkRed
        foreach ($File in $LegacyFiles) {
            Write-Host "  - $File" -ForegroundColor DarkRed
        }
        Write-Host ""
    }
    
    if ($EmergencyFiles.Count -gt 0) {
        Write-Host "🚨 Emergency/Fix Files (CHECK IF INTEGRATED):" -ForegroundColor Yellow
        foreach ($File in $EmergencyFiles) {
            Write-Host "  - $File" -ForegroundColor Yellow
        }
        Write-Host ""
    }
    
    if ($TestFiles.Count -gt 0) {
        Write-Host "🧪 Test/Debug Files (SAFE TO REMOVE IN PRODUCTION):" -ForegroundColor DarkYellow
        foreach ($File in $TestFiles) {
            Write-Host "  - $File" -ForegroundColor DarkYellow
        }
        Write-Host ""
    }
    
    if ($V3Files.Count -gt 0) {
        Write-Host "📜 v3/Deprecated Files (SAFE TO REMOVE):" -ForegroundColor DarkRed
        foreach ($File in $V3Files) {
            Write-Host "  - $File" -ForegroundColor DarkRed
        }
        Write-Host ""
    }
    
    # Files that need manual review
    $ReviewFiles = $UnusedFiles | Where-Object { 
        $_ -notin $LegacyFiles -and 
        $_ -notin $EmergencyFiles -and 
        $_ -notin $TestFiles -and 
        $_ -notin $V3Files 
    }
    
    if ($ReviewFiles.Count -gt 0) {
        Write-Host "🔍 Files Requiring Manual Review:" -ForegroundColor Cyan
        foreach ($File in $ReviewFiles) {
            Write-Host "  - $File" -ForegroundColor Cyan
        }
        Write-Host ""
    }
    
    Write-Host "Safety Recommendations:" -ForegroundColor Yellow
    Write-Host "1. Create backup before removing any files" -ForegroundColor White
    Write-Host "2. Test functionality after removal" -ForegroundColor White
    Write-Host "3. Check if emergency fixes were integrated into v4 files" -ForegroundColor White
    Write-Host "4. Remove files gradually, testing between removals" -ForegroundColor White
}

# Automatic cleanup if not dry run
if (-not $DryRun -and $UnusedFiles.Count -gt 0) {
    if ($Force -or (Read-Host "`nProceed with cleanup? (y/N)") -eq 'y') {
        Write-Host ""
        Write-Host "Creating backup..." -ForegroundColor Yellow
        $BackupPath = Join-Path $PluginPath "backups\css-cleanup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
        
        $RemovedFiles = 0
        $SavedSpace = 0
        
        foreach ($File in $UnusedFiles) {
            $FilePath = Join-Path $CSSPath $File
            if (Test-Path $FilePath) {
                # Create backup
                Copy-Item -Path $FilePath -Destination $BackupPath -Force
                
                # Calculate saved space
                $FileSize = (Get-Item $FilePath).Length
                $SavedSpace += $FileSize
                
                # Remove file
                Remove-Item -Path $FilePath -Force
                Write-Host "  ❌ Removed: $File" -ForegroundColor Red
                $RemovedFiles++
            }
        }
        
        Write-Host ""
        Write-Host "Cleanup completed!" -ForegroundColor Green
        Write-Host "  Files removed: $RemovedFiles" -ForegroundColor Green
        Write-Host "  Space saved: $([Math]::Round($SavedSpace / 1024, 2)) KB" -ForegroundColor Green
        Write-Host "  Backup location: $BackupPath" -ForegroundColor Cyan
    } else {
        Write-Host "Cleanup cancelled." -ForegroundColor Yellow
    }
} elseif (-not $DryRun -and $UnusedFiles.Count -eq 0) {
    Write-Host "No files to clean up." -ForegroundColor Green
}

Write-Host ""
Write-Host "Run with -DryRun:$false to perform actual cleanup" -ForegroundColor Cyan
Write-Host "Done!" -ForegroundColor Green