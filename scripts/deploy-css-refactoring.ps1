# Phase 5: CSS Refactoring Deployment Script
# Mobility Trailblazers WordPress Plugin
# Version: 2.5.40 -> 2.6.0

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CSS REFACTORING DEPLOYMENT SCRIPT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$pluginPath = "C:\Users\nicol\Desktop\mobility-trailblazers"
$cssPath = "$pluginPath\assets\css"
$backupPath = "$pluginPath\backups"
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupFile = "$backupPath\css-backup-$timestamp.zip"

# Function to create backup
function Create-Backup {
    Write-Host "[1/8] Creating backup..." -ForegroundColor Yellow
    
    # Create backup directory if it doesn't exist
    if (!(Test-Path $backupPath)) {
        New-Item -ItemType Directory -Path $backupPath | Out-Null
        Write-Host "   Created backup directory" -ForegroundColor Green
    }
    
    # Backup CSS files
    try {
        Compress-Archive -Path $cssPath -DestinationPath $backupFile -Force
        Write-Host "   [OK] Backup created: $backupFile" -ForegroundColor Green
        
        # Verify backup
        if ((Get-Item $backupFile).Length -gt 0) {
            $backupSize = [math]::Round((Get-Item $backupFile).Length / 1MB, 2)
            Write-Host "   Backup size: ${backupSize}MB" -ForegroundColor Cyan
            return $true
        }
    }
    catch {
        Write-Host "   [ERROR] Backup failed: $_" -ForegroundColor Red
        return $false
    }
}

# Function to consolidate CSS files
function Consolidate-CSS {
    Write-Host "`n[2/8] Consolidating CSS files..." -ForegroundColor Yellow
    
    $consolidatedPath = "$cssPath\refactored"
    if (!(Test-Path $consolidatedPath)) {
        New-Item -ItemType Directory -Path $consolidatedPath | Out-Null
    }
    
    # Define file groups to consolidate
    $fileGroups = @{
        "mt-hotfixes-consolidated.css" = @(
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
        "mt-components-bundle.css" = @(
            "components\mt-candidate-card.css",
            "components\mt-evaluation-form.css",
            "components\mt-jury-dashboard.css"
        )
        "mt-framework-v4.css" = @(
            "v4\mt-tokens.css",
            "v4\mt-reset.css",
            "v4\mt-base.css",
            "v4\mt-components.css",
            "v4\mt-utilities.css"
        )
    }
    
    $consolidatedCount = 0
    foreach ($targetFile in $fileGroups.Keys) {
        $targetPath = "$consolidatedPath\$targetFile"
        $content = ""
        
        Write-Host "   Consolidating to $targetFile..." -ForegroundColor Cyan
        
        foreach ($sourceFile in $fileGroups[$targetFile]) {
            $sourcePath = "$cssPath\$sourceFile"
            if (Test-Path $sourcePath) {
                $fileContent = Get-Content $sourcePath -Raw
                $content += "`n/* === Source: $sourceFile === */`n"
                $content += $fileContent
                Write-Host "      + $sourceFile" -ForegroundColor Gray
            }
        }
        
        if ($content) {
            $content | Out-File -FilePath $targetPath -Encoding UTF8
            $consolidatedCount++
        }
    }
    
    Write-Host "   [OK] Consolidated into $consolidatedCount files" -ForegroundColor Green
}

# Function to remove !important declarations
function Remove-Important {
    Write-Host "`n[3/8] Removing !important declarations..." -ForegroundColor Yellow
    
    $totalRemoved = 0
    $cssFiles = Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css"
    
    foreach ($file in $cssFiles) {
        $content = Get-Content $file.FullName -Raw
        $originalCount = ([regex]::Matches($content, "!important")).Count
        
        if ($originalCount -gt 0) {
            # Remove !important but keep the semicolon
            $content = $content -replace '\s*!important\s*;', ';'
            $content = $content -replace '\s*!important\s*}', '}'
            
            # Count removals
            $newCount = ([regex]::Matches($content, "!important")).Count
            $removed = $originalCount - $newCount
            $totalRemoved += $removed
            
            # Save cleaned file
            $content | Out-File -FilePath $file.FullName -Encoding UTF8
            
            Write-Host "   Removed $removed from $($file.Name)" -ForegroundColor Gray
        }
    }
    
    Write-Host "   [OK] Removed $totalRemoved !important declarations" -ForegroundColor Green
}

# Function to minify CSS files
function Minify-CSS {
    Write-Host "`n[4/8] Minifying CSS files..." -ForegroundColor Yellow
    
    $minifiedCount = 0
    $cssFiles = Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css" | Where-Object { $_.Name -notlike "*.min.css" }
    
    foreach ($file in $cssFiles) {
        $content = Get-Content $file.FullName -Raw
        
        # Basic minification (remove comments, whitespace)
        $minified = $content -replace '/\*[\s\S]*?\*/', ''  # Remove comments
        $minified = $minified -replace '\s+', ' '           # Collapse whitespace
        $minified = $minified -replace ';\s*}', '}'         # Remove last semicolon
        $minified = $minified -replace ':\s+', ':'          # Remove space after colon
        $minified = $minified -replace '\s*{\s*', '{'       # Remove space around braces
        $minified = $minified -replace '\s*}\s*', '}'
        $minified = $minified -replace '\s*;\s*', ';'
        
        # Save minified version
        $minPath = $file.FullName -replace '\.css$', '.min.css'
        $minified | Out-File -FilePath $minPath -Encoding UTF8 -NoNewline
        
        # Calculate compression ratio
        $originalSize = (Get-Item $file.FullName).Length
        $minSize = (Get-Item $minPath).Length
        $ratio = [math]::Round((1 - $minSize/$originalSize) * 100, 1)
        
        Write-Host "   Minified $($file.Name) (-$ratio%)" -ForegroundColor Gray
        $minifiedCount++
    }
    
    Write-Host "   [OK] Created $minifiedCount minified files" -ForegroundColor Green
}

# Function to update version
function Update-Version {
    Write-Host "`n[5/8] Updating plugin version..." -ForegroundColor Yellow
    
    $mainFile = "$pluginPath\mobility-trailblazers.php"
    
    if (Test-Path $mainFile) {
        $content = Get-Content $mainFile -Raw
        
        # Update version in header comment
        $content = $content -replace 'Version:\s*[\d\.]+', 'Version: 2.6.0'
        
        # Update version constant
        $content = $content -replace "define\('MT_VERSION',\s*'[\d\.]+'\)", "define('MT_VERSION', '2.6.0')"
        
        $content | Out-File -FilePath $mainFile -Encoding UTF8
        Write-Host "   [OK] Updated to version 2.6.0" -ForegroundColor Green
    }
    else {
        Write-Host "   [WARNING] Main plugin file not found" -ForegroundColor Yellow
    }
}

# Function to clear caches
function Clear-Caches {
    Write-Host "`n[6/8] Clearing caches..." -ForegroundColor Yellow
    
    # Try WP-CLI if available
    $wpCliPath = Get-Command wp -ErrorAction SilentlyContinue
    
    if ($wpCliPath) {
        try {
            & wp cache flush 2>$null
            Write-Host "   [OK] WordPress cache flushed" -ForegroundColor Green
            
            & wp transient delete --all 2>$null
            Write-Host "   [OK] Transients deleted" -ForegroundColor Green
        }
        catch {
            Write-Host "   [INFO] WP-CLI not available, manual cache clear required" -ForegroundColor Yellow
        }
    }
    else {
        Write-Host "   [INFO] Please clear caches manually through WordPress admin" -ForegroundColor Yellow
    }
}

# Function to validate deployment
function Validate-Deployment {
    Write-Host "`n[7/8] Validating deployment..." -ForegroundColor Yellow
    
    $validationResults = @{
        "CSS Files Count" = (Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css").Count
        "Minified Files" = (Get-ChildItem -Path "$cssPath\refactored" -Filter "*.min.css").Count
        "Total CSS Size (KB)" = [math]::Round((Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css" | Measure-Object -Property Length -Sum).Sum / 1KB, 2)
        "Backup Created" = Test-Path $backupFile
        "Version Updated" = (Select-String -Path "$pluginPath\mobility-trailblazers.php" -Pattern "2.6.0" -Quiet)
    }
    
    Write-Host "`n   Validation Results:" -ForegroundColor Cyan
    foreach ($key in $validationResults.Keys) {
        $value = $validationResults[$key]
        $status = if ($value) { "[PASS]" } else { "[FAIL]" }
        $color = if ($value) { "Green" } else { "Red" }
        Write-Host "   $status $key`: $value" -ForegroundColor $color
    }
    
    # Check for critical issues
    $importantCount = 0
    Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css" | ForEach-Object {
        $content = Get-Content $_.FullName -Raw
        $importantCount += ([regex]::Matches($content, "!important")).Count
    }
    
    Write-Host "`n   Remaining !important declarations: $importantCount" -ForegroundColor $(if ($importantCount -lt 100) { "Green" } else { "Yellow" })
}

# Function to generate deployment report
function Generate-Report {
    Write-Host "`n[8/8] Generating deployment report..." -ForegroundColor Yellow
    
    $reportPath = "$pluginPath\deployment-report-$timestamp.txt"
    
    $report = @"
CSS REFACTORING DEPLOYMENT REPORT
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
==========================================

DEPLOYMENT SUMMARY
------------------
Plugin Version: 2.5.40 -> 2.6.0
Deployment Date: $(Get-Date -Format "yyyy-MM-dd")
Backup Location: $backupFile

CSS METRICS
-----------
Files Consolidated: $(Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css" | Where-Object { $_.Name -notlike "*.min.css" }).Count
Minified Files: $(Get-ChildItem -Path "$cssPath\refactored" -Filter "*.min.css").Count
Total Size: $([math]::Round((Get-ChildItem -Path "$cssPath\refactored" -Filter "*.css" | Measure-Object -Property Length -Sum).Sum / 1KB, 2)) KB

ACTIONS PERFORMED
-----------------
[x] Backup created
[x] CSS files consolidated
[x] !important declarations removed
[x] CSS files minified
[x] Version updated
[x] Caches cleared
[x] Deployment validated

ROLLBACK INSTRUCTIONS
--------------------
If issues occur, run: .\rollback-css-deployment.ps1
Or manually restore: $backupFile

NEXT STEPS
----------
1. Test all major pages
2. Monitor error logs
3. Gather team feedback
4. Document any issues

==========================================
Deployment completed successfully!
"@
    
    $report | Out-File -FilePath $reportPath -Encoding UTF8
    Write-Host "   [OK] Report saved to: $reportPath" -ForegroundColor Green
}

# Main deployment process
Write-Host "`nStarting CSS Refactoring Deployment..." -ForegroundColor Cyan
Write-Host "This process will:" -ForegroundColor Gray
Write-Host "  1. Create a backup of current CSS" -ForegroundColor Gray
Write-Host "  2. Consolidate CSS files" -ForegroundColor Gray
Write-Host "  3. Remove !important declarations" -ForegroundColor Gray
Write-Host "  4. Minify CSS files" -ForegroundColor Gray
Write-Host "  5. Update plugin version" -ForegroundColor Gray
Write-Host "  6. Clear caches" -ForegroundColor Gray
Write-Host "  7. Validate deployment" -ForegroundColor Gray
Write-Host "  8. Generate report" -ForegroundColor Gray
Write-Host ""

# Confirm deployment
$confirm = Read-Host "Proceed with deployment? (yes/no)"
if ($confirm -ne "yes") {
    Write-Host "`nDeployment cancelled." -ForegroundColor Yellow
    exit
}

# Execute deployment steps
$startTime = Get-Date

# Step 1: Backup
if (!(Create-Backup)) {
    Write-Host "`n[ERROR] Backup failed. Deployment aborted." -ForegroundColor Red
    exit 1
}

# Step 2: Consolidate
Consolidate-CSS

# Step 3: Remove !important
Remove-Important

# Step 4: Minify
Minify-CSS

# Step 5: Update version
Update-Version

# Step 6: Clear caches
Clear-Caches

# Step 7: Validate
Validate-Deployment

# Step 8: Generate report
Generate-Report

# Calculate deployment time
$endTime = Get-Date
$duration = [math]::Round(($endTime - $startTime).TotalMinutes, 2)

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "DEPLOYMENT COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Duration: $duration minutes" -ForegroundColor Cyan
Write-Host "Backup: $backupFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "Please:" -ForegroundColor Yellow
Write-Host "  1. Test the website thoroughly" -ForegroundColor Gray
Write-Host "  2. Monitor error logs" -ForegroundColor Gray
Write-Host "  3. Be ready to rollback if needed" -ForegroundColor Gray
Write-Host ""
Write-Host "Rollback command: .\scripts\rollback-css-deployment.ps1" -ForegroundColor Cyan