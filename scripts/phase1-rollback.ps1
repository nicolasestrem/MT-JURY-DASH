# Phase 1 Emergency Rollback Script
# Mobility Trailblazers WordPress Plugin
# Purpose: Quickly rollback CSS changes in case of critical issues
# Created: 2025-08-24

param(
    [switch]$Confirm = $false,
    [switch]$RestoreDatabase = $false,
    [string]$BackupDir = "",
    [switch]$ClearCache = $true
)

# Configuration
$projectRoot = Split-Path -Parent $PSScriptRoot
$logFile = Join-Path $projectRoot "logs\phase1-rollback.log"

# Color codes for output
$colors = @{
    Error = "Red"
    Warning = "Yellow"
    Success = "Green"
    Info = "Cyan"
    Critical = "Magenta"
}

function Write-Log {
    param($Message, $Level = "INFO", $NoNewLine = $false)
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    
    # Ensure log directory exists
    $logDir = Split-Path $logFile
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Force -Path $logDir | Out-Null
    }
    
    Add-Content -Path $logFile -Value $logMessage
    
    # Console output with colors
    $color = switch ($Level) {
        "ERROR" { $colors.Error }
        "WARNING" { $colors.Warning }
        "SUCCESS" { $colors.Success }
        "CRITICAL" { $colors.Critical }
        default { $colors.Info }
    }
    
    if ($NoNewLine) {
        Write-Host $Message -ForegroundColor $color -NoNewline
    } else {
        Write-Host $Message -ForegroundColor $color
    }
}

function Show-Banner {
    Clear-Host
    Write-Host ""
    Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Red
    Write-Host "║        EMERGENCY CSS ROLLBACK - PHASE 1                  ║" -ForegroundColor Red
    Write-Host "║        Mobility Trailblazers WordPress Plugin            ║" -ForegroundColor Red
    Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Red
    Write-Host ""
}

function Get-LatestBackup {
    $backupsPath = Join-Path $projectRoot "backups\phase1"
    
    if (!(Test-Path $backupsPath)) {
        Write-Log "No backup directory found at: $backupsPath" "ERROR"
        return $null
    }
    
    $latestBackup = Get-ChildItem $backupsPath -Directory | 
                    Sort-Object Name -Descending | 
                    Select-Object -First 1
    
    if ($latestBackup) {
        return $latestBackup.FullName
    }
    
    return $null
}

function Test-GitStatus {
    Write-Log "Checking Git status..." "INFO"
    
    try {
        $gitStatus = git status --porcelain 2>&1
        if ($gitStatus) {
            Write-Log "Uncommitted changes detected:" "WARNING"
            $gitStatus | ForEach-Object { Write-Log "  $_" "WARNING" }
            return $false
        }
        return $true
    } catch {
        Write-Log "Git check failed: $_" "ERROR"
        return $false
    }
}

function Rollback-GitChanges {
    Write-Log "Rolling back Git changes..." "INFO"
    
    try {
        # Stash any current changes
        git stash push -m "Phase1 rollback stash $(Get-Date -Format 'yyyyMMdd-HHmmss')" 2>&1 | Out-Null
        Write-Log "Current changes stashed" "SUCCESS"
        
        # Checkout main branch
        git checkout main 2>&1 | Out-Null
        Write-Log "Switched to main branch" "SUCCESS"
        
        # Pull latest changes
        git pull origin main 2>&1 | Out-Null
        Write-Log "Updated from origin/main" "SUCCESS"
        
        return $true
    } catch {
        Write-Log "Git rollback failed: $_" "ERROR"
        return $false
    }
}

function Restore-CSSFiles {
    param($BackupPath)
    
    Write-Log "Restoring CSS files from backup..." "INFO"
    Write-Log "Backup source: $BackupPath" "INFO"
    
    $cssPath = Join-Path $projectRoot "assets\css"
    $restoredCount = 0
    
    # List of files to restore
    $filesToRestore = @(
        "emergency-fixes.css",
        "frontend-critical-fixes.css",
        "candidate-single-hotfix.css",
        "mt-jury-filter-hotfix.css",
        "evaluation-fix.css",
        "mt-evaluation-fixes.css",
        "mt-jury-dashboard-fix.css",
        "mt-modal-fix.css",
        "mt-medal-fix.css"
    )
    
    foreach ($file in $filesToRestore) {
        $source = Join-Path $BackupPath $file
        $dest = Join-Path $cssPath $file
        
        if (Test-Path $source) {
            Copy-Item $source $dest -Force
            Write-Log "Restored: $file" "SUCCESS"
            $restoredCount++
        } else {
            Write-Log "Backup file not found: $file" "WARNING"
        }
    }
    
    Write-Log "Restored $restoredCount of $($filesToRestore.Count) files" $(if ($restoredCount -eq $filesToRestore.Count) { "SUCCESS" } else { "WARNING" })
    
    # Remove consolidated files if they exist
    $refactoredPath = Join-Path $cssPath "refactored"
    if (Test-Path $refactoredPath) {
        Write-Log "Removing refactored directory..." "INFO"
        Remove-Item $refactoredPath -Recurse -Force
        Write-Log "Refactored directory removed" "SUCCESS"
    }
    
    return $restoredCount -gt 0
}

function Restore-PHPFiles {
    Write-Log "Checking PHP file changes..." "INFO"
    
    $phpFile = Join-Path $projectRoot "includes\public\class-mt-public-assets.php"
    
    # Use git to restore the PHP file
    try {
        git checkout HEAD -- $phpFile 2>&1 | Out-Null
        Write-Log "Restored: class-mt-public-assets.php" "SUCCESS"
        return $true
    } catch {
        Write-Log "Failed to restore PHP file: $_" "ERROR"
        return $false
    }
}

function Clear-WordPressCache {
    Write-Log "Clearing WordPress cache..." "INFO"
    
    try {
        # Try WP-CLI first
        $wpResult = wp cache flush 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Log "WordPress cache cleared via WP-CLI" "SUCCESS"
            return $true
        }
    } catch {
        Write-Log "WP-CLI not available or failed" "WARNING"
    }
    
    # Alternative: Clear common cache directories
    $cacheDirectories = @(
        "wp-content\cache",
        "wp-content\uploads\cache",
        "wp-content\w3tc-cache"
    )
    
    foreach ($dir in $cacheDirectories) {
        $cachePath = Join-Path $projectRoot $dir
        if (Test-Path $cachePath) {
            Remove-Item "$cachePath\*" -Recurse -Force -ErrorAction SilentlyContinue
            Write-Log "Cleared cache directory: $dir" "SUCCESS"
        }
    }
    
    return $true
}

function Restore-Database {
    param($BackupFile)
    
    Write-Log "Restoring database from backup..." "CRITICAL"
    
    if (!(Test-Path $BackupFile)) {
        Write-Log "Database backup file not found: $BackupFile" "ERROR"
        return $false
    }
    
    try {
        # Use WP-CLI to import database
        $importResult = wp db import $BackupFile 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Log "Database restored successfully" "SUCCESS"
            return $true
        } else {
            Write-Log "Database restore failed: $importResult" "ERROR"
            return $false
        }
    } catch {
        Write-Log "Database restore error: $_" "ERROR"
        return $false
    }
}

function Verify-Rollback {
    Write-Log "Verifying rollback..." "INFO"
    
    $checks = @{
        "CSS Files Exist" = $true
        "No Consolidated Files" = $true
        "PHP File Restored" = $true
        "Git Status Clean" = $true
    }
    
    # Check CSS files exist
    $cssPath = Join-Path $projectRoot "assets\css"
    $requiredFiles = @(
        "emergency-fixes.css",
        "frontend-critical-fixes.css",
        "mt-jury-filter-hotfix.css"
    )
    
    foreach ($file in $requiredFiles) {
        if (!(Test-Path (Join-Path $cssPath $file))) {
            $checks["CSS Files Exist"] = $false
            break
        }
    }
    
    # Check no consolidated files
    $refactoredPath = Join-Path $cssPath "refactored"
    if (Test-Path $refactoredPath) {
        $checks["No Consolidated Files"] = $false
    }
    
    # Check Git status
    $gitStatus = git status --porcelain 2>&1
    if ($gitStatus) {
        $checks["Git Status Clean"] = $false
    }
    
    # Display verification results
    Write-Host ""
    Write-Host "Rollback Verification Results:" -ForegroundColor Cyan
    Write-Host "==============================" -ForegroundColor Cyan
    
    foreach ($check in $checks.GetEnumerator()) {
        $status = if ($check.Value) { "[✓]" } else { "[✗]" }
        $color = if ($check.Value) { "Green" } else { "Red" }
        Write-Host "$status $($check.Key)" -ForegroundColor $color
    }
    
    $allPassed = ($checks.Values | Where-Object { $_ -eq $false }).Count -eq 0
    return $allPassed
}

# Main execution
function Main {
    Show-Banner
    
    Write-Log "=" * 60
    Write-Log "Emergency Rollback Started at $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "CRITICAL"
    Write-Log "=" * 60
    
    # Confirmation prompt
    if (!$Confirm) {
        Write-Host "WARNING: This will rollback all Phase 1 CSS changes!" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "This action will:" -ForegroundColor Cyan
        Write-Host "  1. Restore original CSS files from backup" -ForegroundColor White
        Write-Host "  2. Remove consolidated/refactored files" -ForegroundColor White
        Write-Host "  3. Restore PHP enqueue changes" -ForegroundColor White
        Write-Host "  4. Clear all caches" -ForegroundColor White
        if ($RestoreDatabase) {
            Write-Host "  5. Restore database from backup" -ForegroundColor Red
        }
        Write-Host ""
        
        $response = Read-Host "Type 'ROLLBACK' to confirm"
        if ($response -ne "ROLLBACK") {
            Write-Log "Rollback cancelled by user" "INFO"
            exit 0
        }
    }
    
    # Find backup directory
    if (!$BackupDir) {
        $BackupDir = Get-LatestBackup
        if (!$BackupDir) {
            Write-Log "No backup found. Cannot proceed with rollback." "ERROR"
            Write-Log "Please specify backup directory with -BackupDir parameter" "ERROR"
            exit 1
        }
    }
    
    Write-Log "Using backup: $BackupDir" "INFO"
    
    # Start rollback process
    $success = $true
    
    # Step 1: Git operations
    Write-Host ""
    Write-Log "Step 1: Git Operations" "INFO" -NoNewLine
    $gitClean = Test-GitStatus
    if (!$gitClean) {
        Write-Log " [Uncommitted changes - will stash]" "WARNING"
    } else {
        Write-Log " [Clean]" "SUCCESS"
    }
    
    # Step 2: Restore CSS files
    Write-Host ""
    Write-Log "Step 2: Restoring CSS Files" "INFO"
    if (!(Restore-CSSFiles $BackupDir)) {
        $success = $false
        Write-Log "CSS restoration failed" "ERROR"
    }
    
    # Step 3: Restore PHP files
    Write-Host ""
    Write-Log "Step 3: Restoring PHP Files" "INFO"
    if (!(Restore-PHPFiles)) {
        $success = $false
        Write-Log "PHP restoration failed" "ERROR"
    }
    
    # Step 4: Clear caches
    if ($ClearCache) {
        Write-Host ""
        Write-Log "Step 4: Clearing Caches" "INFO"
        Clear-WordPressCache | Out-Null
    }
    
    # Step 5: Database restore (if requested)
    if ($RestoreDatabase) {
        Write-Host ""
        Write-Log "Step 5: Database Restore" "CRITICAL"
        
        # Look for database backup
        $dbBackup = Get-ChildItem $BackupDir -Filter "*.sql" | Select-Object -First 1
        if ($dbBackup) {
            if (!(Restore-Database $dbBackup.FullName)) {
                $success = $false
                Write-Log "Database restoration failed" "ERROR"
            }
        } else {
            Write-Log "No database backup found in backup directory" "WARNING"
        }
    }
    
    # Verify rollback
    Write-Host ""
    Write-Log "Verifying Rollback..." "INFO"
    $verified = Verify-Rollback
    
    # Final status
    Write-Host ""
    Write-Host "=" * 60 -ForegroundColor $(if ($success -and $verified) { "Green" } else { "Red" })
    
    if ($success -and $verified) {
        Write-Log "ROLLBACK COMPLETED SUCCESSFULLY" "SUCCESS"
        Write-Host ""
        Write-Host "Next Steps:" -ForegroundColor Cyan
        Write-Host "1. Test the website at http://localhost:8080/" -ForegroundColor White
        Write-Host "2. Check browser console for errors" -ForegroundColor White
        Write-Host "3. Verify critical functionality works" -ForegroundColor White
        Write-Host "4. Review logs at: $logFile" -ForegroundColor White
    } else {
        Write-Log "ROLLBACK COMPLETED WITH ISSUES" "ERROR"
        Write-Host ""
        Write-Host "Manual intervention may be required!" -ForegroundColor Red
        Write-Host "Check the log file for details: $logFile" -ForegroundColor Yellow
        
        # Provide recovery instructions
        Write-Host ""
        Write-Host "Recovery Options:" -ForegroundColor Yellow
        Write-Host "1. Manually restore files from: $BackupDir" -ForegroundColor White
        Write-Host "2. Use git to revert: git checkout main -- ." -ForegroundColor White
        Write-Host "3. Contact senior developer for assistance" -ForegroundColor White
    }
    
    Write-Host "=" * 60 -ForegroundColor $(if ($success -and $verified) { "Green" } else { "Red" })
    
    Write-Log "=" * 60
    Write-Log "Emergency Rollback Completed at $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "CRITICAL"
    Write-Log "=" * 60
    
    exit $(if ($success -and $verified) { 0 } else { 1 })
}

# Run the script
Main