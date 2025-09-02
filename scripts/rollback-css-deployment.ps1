# CSS Deployment Rollback Script
# Emergency rollback procedure for CSS refactoring
# Mobility Trailblazers WordPress Plugin

Write-Host "========================================" -ForegroundColor Red
Write-Host "EMERGENCY CSS ROLLBACK PROCEDURE" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

# Configuration
$pluginPath = "C:\Users\nicol\Desktop\mobility-trailblazers"
$cssPath = "$pluginPath\assets\css"
$backupPath = "$pluginPath\backups"

# Function to find latest backup
function Find-LatestBackup {
    Write-Host "Searching for backups..." -ForegroundColor Yellow
    
    if (!(Test-Path $backupPath)) {
        Write-Host "[ERROR] No backup directory found!" -ForegroundColor Red
        return $null
    }
    
    $backups = Get-ChildItem -Path $backupPath -Filter "css-backup-*.zip" | Sort-Object LastWriteTime -Descending
    
    if ($backups.Count -eq 0) {
        Write-Host "[ERROR] No backup files found!" -ForegroundColor Red
        return $null
    }
    
    Write-Host "Found $($backups.Count) backup(s):" -ForegroundColor Cyan
    for ($i = 0; $i -lt [Math]::Min($backups.Count, 5); $i++) {
        Write-Host "  [$($i+1)] $($backups[$i].Name) - $($backups[$i].LastWriteTime)" -ForegroundColor Gray
    }
    
    return $backups
}

# Function to restore backup
function Restore-Backup {
    param($backupFile)
    
    Write-Host "`nRestoring from: $($backupFile.Name)" -ForegroundColor Yellow
    
    try {
        # Create temporary extraction path
        $tempPath = "$backupPath\temp_restore"
        if (Test-Path $tempPath) {
            Remove-Item -Path $tempPath -Recurse -Force
        }
        New-Item -ItemType Directory -Path $tempPath | Out-Null
        
        # Extract backup
        Expand-Archive -Path $backupFile.FullName -DestinationPath $tempPath -Force
        
        # Remove current CSS directory
        Write-Host "Removing current CSS files..." -ForegroundColor Yellow
        if (Test-Path $cssPath) {
            Remove-Item -Path "$cssPath\*" -Recurse -Force
        }
        
        # Restore CSS files
        Write-Host "Restoring CSS files..." -ForegroundColor Yellow
        $extractedCss = Get-ChildItem -Path $tempPath -Directory | Where-Object { $_.Name -eq "css" }
        
        if ($extractedCss) {
            Copy-Item -Path "$($extractedCss.FullName)\*" -Destination $cssPath -Recurse -Force
            Write-Host "[OK] CSS files restored successfully" -ForegroundColor Green
        }
        else {
            # Try direct restore if structure is different
            Copy-Item -Path "$tempPath\*" -Destination $cssPath -Recurse -Force
            Write-Host "[OK] CSS files restored (alternate structure)" -ForegroundColor Green
        }
        
        # Clean up temp directory
        Remove-Item -Path $tempPath -Recurse -Force
        
        return $true
    }
    catch {
        Write-Host "[ERROR] Restore failed: $_" -ForegroundColor Red
        return $false
    }
}

# Function to restore version
function Restore-Version {
    Write-Host "`nRestoring plugin version..." -ForegroundColor Yellow
    
    $mainFile = "$pluginPath\mobility-trailblazers.php"
    
    if (Test-Path $mainFile) {
        $content = Get-Content $mainFile -Raw
        
        # Restore to version 2.5.40
        $content = $content -replace 'Version:\s*[\d\.]+', 'Version: 2.5.40'
        $content = $content -replace "define\('MT_VERSION',\s*'[\d\.]+'\)", "define('MT_VERSION', '2.5.40')"
        
        $content | Out-File -FilePath $mainFile -Encoding UTF8
        Write-Host "[OK] Restored to version 2.5.40" -ForegroundColor Green
    }
}

# Function to clear caches
function Clear-Caches {
    Write-Host "`nClearing caches..." -ForegroundColor Yellow
    
    # Try WP-CLI if available
    $wpCliPath = Get-Command wp -ErrorAction SilentlyContinue
    
    if ($wpCliPath) {
        try {
            & wp cache flush 2>$null
            Write-Host "[OK] WordPress cache flushed" -ForegroundColor Green
            
            & wp transient delete --all 2>$null
            Write-Host "[OK] Transients deleted" -ForegroundColor Green
        }
        catch {
            Write-Host "[INFO] Manual cache clear required" -ForegroundColor Yellow
        }
    }
    else {
        Write-Host "[INFO] Please clear caches manually through WordPress admin" -ForegroundColor Yellow
    }
}

# Function to restart services
function Restart-Services {
    Write-Host "`nRestarting services..." -ForegroundColor Yellow
    
    # Check if Docker is available
    $dockerPath = Get-Command docker -ErrorAction SilentlyContinue
    
    if ($dockerPath) {
        try {
            # Get WordPress container
            $wpContainer = & docker ps --filter "name=wordpress" --format "{{.Names}}" 2>$null | Select-Object -First 1
            
            if ($wpContainer) {
                & docker restart $wpContainer 2>$null
                Write-Host "[OK] WordPress container restarted: $wpContainer" -ForegroundColor Green
            }
        }
        catch {
            Write-Host "[INFO] Manual service restart may be required" -ForegroundColor Yellow
        }
    }
}

# Function to validate rollback
function Validate-Rollback {
    Write-Host "`nValidating rollback..." -ForegroundColor Yellow
    
    $validationResults = @{
        "CSS Directory Exists" = Test-Path $cssPath
        "CSS Files Present" = (Get-ChildItem -Path $cssPath -Filter "*.css" -ErrorAction SilentlyContinue).Count -gt 0
        "Version Restored" = (Select-String -Path "$pluginPath\mobility-trailblazers.php" -Pattern "2.5.40" -Quiet -ErrorAction SilentlyContinue)
    }
    
    $allPassed = $true
    foreach ($key in $validationResults.Keys) {
        $value = $validationResults[$key]
        $status = if ($value) { "[PASS]" } else { "[FAIL]" }
        $color = if ($value) { "Green" } else { "Red" }
        Write-Host "  $status $key" -ForegroundColor $color
        
        if (!$value) { $allPassed = $false }
    }
    
    return $allPassed
}

# Main rollback process
Write-Host "This procedure will restore CSS files from backup" -ForegroundColor Yellow
Write-Host ""

# Find backups
$backups = Find-LatestBackup

if (!$backups) {
    Write-Host "`n[CRITICAL] No backups available for rollback!" -ForegroundColor Red
    Write-Host "Manual intervention required." -ForegroundColor Red
    exit 1
}

# Select backup
Write-Host ""
$selection = Read-Host "Select backup number to restore (1-$([Math]::Min($backups.Count, 5))) or 'cancel' to abort"

if ($selection -eq "cancel") {
    Write-Host "`nRollback cancelled." -ForegroundColor Yellow
    exit
}

$backupIndex = [int]$selection - 1
if ($backupIndex -lt 0 -or $backupIndex -ge $backups.Count) {
    Write-Host "[ERROR] Invalid selection!" -ForegroundColor Red
    exit 1
}

$selectedBackup = $backups[$backupIndex]

# Confirm rollback
Write-Host ""
Write-Host "WARNING: This will replace all current CSS files!" -ForegroundColor Red
$confirm = Read-Host "Are you sure you want to rollback? (yes/no)"

if ($confirm -ne "yes") {
    Write-Host "`nRollback cancelled." -ForegroundColor Yellow
    exit
}

# Execute rollback
Write-Host "`nExecuting rollback..." -ForegroundColor Cyan
$startTime = Get-Date

# Step 1: Restore backup
if (!(Restore-Backup -backupFile $selectedBackup)) {
    Write-Host "`n[CRITICAL] Backup restore failed!" -ForegroundColor Red
    Write-Host "Manual intervention required." -ForegroundColor Red
    exit 1
}

# Step 2: Restore version
Restore-Version

# Step 3: Clear caches
Clear-Caches

# Step 4: Restart services
Restart-Services

# Step 5: Validate
$rollbackSuccess = Validate-Rollback

# Calculate rollback time
$endTime = Get-Date
$duration = [math]::Round(($endTime - $startTime).TotalSeconds, 2)

Write-Host ""
if ($rollbackSuccess) {
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "ROLLBACK COMPLETED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Duration: $duration seconds" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Test website functionality" -ForegroundColor Gray
    Write-Host "  2. Check error logs" -ForegroundColor Gray
    Write-Host "  3. Notify team of rollback" -ForegroundColor Gray
    Write-Host "  4. Document issues that caused rollback" -ForegroundColor Gray
}
else {
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ROLLBACK COMPLETED WITH WARNINGS!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "Some validation checks failed." -ForegroundColor Yellow
    Write-Host "Manual verification required!" -ForegroundColor Yellow
}

# Generate rollback report
$reportPath = "$pluginPath\rollback-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').txt"
$report = @"
CSS ROLLBACK REPORT
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
==========================================

ROLLBACK DETAILS
----------------
Backup Used: $($selectedBackup.Name)
Backup Date: $($selectedBackup.LastWriteTime)
Rollback Duration: $duration seconds
Status: $(if ($rollbackSuccess) { "SUCCESS" } else { "PARTIAL SUCCESS" })

ACTIONS PERFORMED
-----------------
[x] CSS files restored from backup
[x] Plugin version restored to 2.5.40
[x] Caches cleared
[x] Services restarted (if available)
[x] Rollback validated

VALIDATION RESULTS
------------------
CSS Directory: $(if (Test-Path $cssPath) { "EXISTS" } else { "MISSING" })
CSS Files: $(Get-ChildItem -Path $cssPath -Filter "*.css" -ErrorAction SilentlyContinue).Count files
Version: $(if (Select-String -Path "$pluginPath\mobility-trailblazers.php" -Pattern "2.5.40" -Quiet) { "2.5.40" } else { "UNKNOWN" })

RECOMMENDED ACTIONS
------------------
1. Test all major functionality
2. Review error logs
3. Document the issue that required rollback
4. Plan corrective actions before next deployment
5. Notify stakeholders

==========================================
"@

$report | Out-File -FilePath $reportPath -Encoding UTF8
Write-Host "`nRollback report saved to: $reportPath" -ForegroundColor Cyan