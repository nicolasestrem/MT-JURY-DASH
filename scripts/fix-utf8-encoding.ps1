# UTF-8 Encoding Fix Script for Mobility Trailblazers
# This script fixes UTF-8 encoding issues in PHP template files
# Author: Mobility Trailblazers Team
# Date: 2025-08-25

param(
    [switch]$DryRun = $false,
    [switch]$Backup = $true
)

$PluginPath = Split-Path $PSScriptRoot -Parent
$TemplatesPath = Join-Path $PluginPath "templates"
$BackupPath = Join-Path $PluginPath "backups\utf8-fix-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "UTF-8 Encoding Fix Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Files to fix based on grep results
$FilesToFix = @(
    "templates\frontend\single\single-mt_candidate-enhanced-v2.php",
    "templates\frontend\single\single-mt_candidate-enhanced.php",
    "templates\frontend\single\single-mt_candidate.php",
    "templates\admin\settings.php",
    "templates\frontend\jury-evaluation-form.php",
    "templates\frontend\jury-dashboard.php",
    "templates\frontend\candidates-grid.php"
)

# Create backup directory if needed
if ($Backup -and -not $DryRun) {
    Write-Host "Creating backup directory: $BackupPath" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
}

$FixedCount = 0
$ErrorCount = 0

foreach ($RelativePath in $FilesToFix) {
    $FilePath = Join-Path $PluginPath $RelativePath
    
    if (-not (Test-Path $FilePath)) {
        Write-Host "File not found: $RelativePath" -ForegroundColor Red
        $ErrorCount++
        continue
    }
    
    Write-Host "Processing: $RelativePath" -ForegroundColor White
    
    try {
        # Read file with UTF-8 encoding
        $Content = Get-Content -Path $FilePath -Raw -Encoding UTF8
        $OriginalContent = $Content
        $ChangesMade = $false
        
        # Apply specific fixes for known issues
        $Replacements = @(
            @{Find = "Dräxlmaier"; Replace = "Dräxlmaier"},
            @{Find = "öffentliche"; Replace = "öffentliche"},
            @{Find = "öffentlich"; Replace = "öffentlich"},
            @{Find = "für"; Replace = "für"},
            @{Find = "Mobilitätswende"; Replace = "Mobilitätswende"},
            @{Find = "Mobilität"; Replace = "Mobilität"},
            @{Find = "Ã¤"; Replace = "ä"},
            @{Find = "Ã¶"; Replace = "ö"},
            @{Find = "Ã¼"; Replace = "ü"},
            @{Find = "ÃŸ"; Replace = "ß"},
            @{Find = "Ã„"; Replace = "Ä"},
            @{Find = "Ã–"; Replace = "Ö"},
            @{Find = "Ãœ"; Replace = "Ü"}
        )
        
        foreach ($Replacement in $Replacements) {
            if ($Content -match [regex]::Escape($Replacement.Find)) {
                $Content = $Content -replace [regex]::Escape($Replacement.Find), $Replacement.Replace
                $ChangesMade = $true
                Write-Host "  - Fixed: $($Replacement.Find) to $($Replacement.Replace)" -ForegroundColor Green
            }
        }
        
        if ($ChangesMade) {
            if ($DryRun) {
                Write-Host "  [DRY RUN] Would fix encoding issues" -ForegroundColor Cyan
            } else {
                # Backup original file
                if ($Backup) {
                    $BackupFile = Join-Path $BackupPath $RelativePath
                    $BackupDir = Split-Path $BackupFile -Parent
                    if (-not (Test-Path $BackupDir)) {
                        New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
                    }
                    Copy-Item -Path $FilePath -Destination $BackupFile -Force
                    Write-Host "  - Backed up to: $BackupFile" -ForegroundColor DarkGray
                }
                
                # Write fixed content with UTF-8 encoding (without BOM)
                $Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $false
                [System.IO.File]::WriteAllText($FilePath, $Content, $Utf8NoBomEncoding)
                
                Write-Host "  Fixed and saved" -ForegroundColor Green
                $FixedCount++
            }
        } else {
            Write-Host "  - No encoding issues found" -ForegroundColor Gray
        }
        
    } catch {
        Write-Host "  ERROR: $_" -ForegroundColor Red
        $ErrorCount++
    }
    
    Write-Host ""
}

# Summary
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Files processed: $($FilesToFix.Count)" -ForegroundColor White
Write-Host "Files fixed: $FixedCount" -ForegroundColor Green
if ($ErrorCount -gt 0) {
    Write-Host "Errors: $ErrorCount" -ForegroundColor Red
} else {
    Write-Host "Errors: $ErrorCount" -ForegroundColor Green
}

if ($DryRun) {
    Write-Host ""
    Write-Host "This was a DRY RUN. No files were modified." -ForegroundColor Yellow
    Write-Host "Run without -DryRun flag to apply fixes." -ForegroundColor Yellow
}

if ($Backup -and -not $DryRun -and $FixedCount -gt 0) {
    Write-Host ""
    Write-Host "Backup location: $BackupPath" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green