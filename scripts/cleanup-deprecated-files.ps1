# Cleanup Deprecated Files Script for Windows PowerShell
# 
# This script removes backup directories, deprecated files, and test results
# from the Mobility Trailblazers plugin.
# 
# Usage: 
#   .\scripts\cleanup-deprecated-files.ps1 [-DryRun] [-Verbose]
# 
# Parameters:
#   -DryRun: Preview what will be deleted without actually removing files
#   -Verbose: Show detailed output
#
# @package MobilityTrailblazers
# @version 1.0.0

param(
    [switch]$DryRun = $false,
    [switch]$Verbose = $false
)

# Set script location
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$BasePath = Split-Path -Parent $ScriptPath

# Color functions for output
function Write-Info { 
    param($Message)
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] INFO: $Message" -ForegroundColor Cyan 
}

function Write-Success { 
    param($Message)
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] SUCCESS: $Message" -ForegroundColor Green 
}

function Write-Warning { 
    param($Message)
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] WARNING: $Message" -ForegroundColor Yellow 
}

function Write-Error { 
    param($Message)
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ERROR: $Message" -ForegroundColor Red 
}

function Write-Debug {
    param($Message)
    if ($Verbose) {
        Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] DEBUG: $Message" -ForegroundColor Gray
    }
}

# Function to format file sizes
function Format-FileSize {
    param([long]$Size)
    
    if ($Size -gt 1GB) {
        return "{0:N2} GB" -f ($Size / 1GB)
    } elseif ($Size -gt 1MB) {
        return "{0:N2} MB" -f ($Size / 1MB)
    } elseif ($Size -gt 1KB) {
        return "{0:N2} KB" -f ($Size / 1KB)
    } else {
        return "$Size B"
    }
}

# Function to get directory size
function Get-DirectorySize {
    param($Path)
    
    if (-not (Test-Path $Path)) {
        return 0
    }
    
    $size = 0
    Get-ChildItem -Path $Path -Recurse -File -ErrorAction SilentlyContinue | 
        ForEach-Object { $size += $_.Length }
    
    return $size
}

# Define cleanup targets
$CleanupTargets = @(
    @{
        Name = "Main Backups Directory"
        Path = Join-Path $BasePath "backups"
        Type = "Directory"
        Description = "All backup files and directories"
    },
    @{
        Name = "Internal Backups"
        Path = Join-Path $BasePath ".internal\backups"
        Type = "Directory"
        Description = "Internal backup directory"
    },
    @{
        Name = "Test Results (No Auth)"
        Path = Join-Path $BasePath "doc\test-results-no-auth"
        Type = "Directory"
        Description = "Test results without authentication"
    },
    @{
        Name = "Playwright Report (No Auth)"
        Path = Join-Path $BasePath "doc\playwright-report-no-auth"
        Type = "Directory"
        Description = "Playwright reports without authentication"
    },
    @{
        Name = "Test Results (Staging)"
        Path = Join-Path $BasePath "doc\test-results-staging"
        Type = "Directory"
        Description = "Staging test results"
    },
    @{
        Name = "Test Results (Production)"
        Path = Join-Path $BasePath "doc\test-results-production"
        Type = "Directory"
        Description = "Production test results"
    },
    @{
        Name = "Playwright Report (Staging)"
        Path = Join-Path $BasePath "doc\playwright-report-staging"
        Type = "Directory"
        Description = "Staging playwright reports"
    },
    @{
        Name = "Playwright Report (Production)"
        Path = Join-Path $BasePath "doc\playwright-report-production"
        Type = "Directory"
        Description = "Production playwright reports"
    },
    @{
        Name = "Example Old CSS"
        Path = Join-Path $BasePath "Exemple old css"
        Type = "Directory"
        Description = "Example old CSS directory"
    },
    @{
        Name = "CSS Backup"
        Path = Join-Path $BasePath "css backup"
        Type = "Directory"
        Description = "CSS backup directory"
    }
)

# Start cleanup process
Write-Info "Starting Mobility Trailblazers cleanup script"
Write-Info "Base path: $BasePath"

if ($DryRun) {
    Write-Warning "Running in DRY RUN mode - no files will be deleted"
}

$TotalSize = 0
$RemovedCount = 0
$FailedCount = 0
$NotFoundCount = 0

# Process each cleanup target
foreach ($Target in $CleanupTargets) {
    Write-Debug "Checking: $($Target.Path)"
    
    if (Test-Path $Target.Path) {
        if ($Target.Type -eq "Directory") {
            $size = Get-DirectorySize -Path $Target.Path
            $TotalSize += $size
            
            Write-Info "Found: $($Target.Name) ($(Format-FileSize $size)) - $($Target.Description)"
            
            if (-not $DryRun) {
                try {
                    Remove-Item -Path $Target.Path -Recurse -Force -ErrorAction Stop
                    Write-Success "Removed: $($Target.Name)"
                    $RemovedCount++
                } catch {
                    Write-Error "Failed to remove $($Target.Name): $_"
                    $FailedCount++
                }
            } else {
                Write-Warning "Would remove: $($Target.Name)"
                $RemovedCount++
            }
        } elseif ($Target.Type -eq "File") {
            $file = Get-Item $Target.Path
            $size = $file.Length
            $TotalSize += $size
            
            Write-Info "Found: $($Target.Name) ($(Format-FileSize $size)) - $($Target.Description)"
            
            if (-not $DryRun) {
                try {
                    Remove-Item -Path $Target.Path -Force -ErrorAction Stop
                    Write-Success "Removed: $($Target.Name)"
                    $RemovedCount++
                } catch {
                    Write-Error "Failed to remove $($Target.Name): $_"
                    $FailedCount++
                }
            } else {
                Write-Warning "Would remove: $($Target.Name)"
                $RemovedCount++
            }
        }
    } else {
        Write-Debug "Not found: $($Target.Name)"
        $NotFoundCount++
    }
}

# Display summary
Write-Host "`n$('=' * 60)" -ForegroundColor White
Write-Info "Cleanup Summary:"
Write-Info "Total size to be freed: $(Format-FileSize $TotalSize)"
Write-Info "Items to remove: $RemovedCount"

if ($NotFoundCount -gt 0) {
    Write-Info "Items not found: $NotFoundCount"
}

if ($FailedCount -gt 0) {
    Write-Warning "Failed operations: $FailedCount"
}

if ($DryRun) {
    Write-Warning "This was a DRY RUN - no files were actually deleted"
    Write-Warning "Run without -DryRun flag to perform actual cleanup"
    Write-Host "`nTo perform actual cleanup, run:" -ForegroundColor Yellow
    Write-Host "  .\scripts\cleanup-deprecated-files.ps1" -ForegroundColor White
} else {
    Write-Success "Cleanup completed successfully!"
}

# Additional recommendations
Write-Host "`n$('=' * 60)" -ForegroundColor White
Write-Info "Additional recommendations:"
Write-Host "  1. Run 'git gc --aggressive --prune=now' to clean up git objects" -ForegroundColor White
Write-Host "  2. Clear WordPress cache: wp cache flush" -ForegroundColor White
Write-Host "  3. Verify plugin functionality after cleanup" -ForegroundColor White
Write-Host "  4. Commit changes to version control" -ForegroundColor White

# Ask for confirmation if not in dry run mode and items were found
if (-not $DryRun -and $RemovedCount -eq 0 -and $NotFoundCount -gt 0) {
    Write-Info "No items found to clean up. The directories may have already been removed."
}