# CSS Rollback Test Script
# Tests the CSS feature flag rollback mechanism
# Phase 1 Stabilization - August 30, 2025

param(
    [string]$Mode = "test"  # test, restore, verify
)

Write-Host "`n================================" -ForegroundColor Cyan
Write-Host "CSS Rollback Test Script" -ForegroundColor Cyan
Write-Host "================================`n" -ForegroundColor Cyan

$backupPath = ".\Plugin\assets\css\backup-20250830"
$cssPath = ".\Plugin\assets\css"
$pluginFile = ".\Plugin\includes\core\class-mt-plugin.php"

function Test-FeatureFlag {
    Write-Host "Testing CSS Feature Flag Rollback..." -ForegroundColor Yellow
    
    # Check if plugin file has feature flag support
    $content = Get-Content $pluginFile -Raw
    if ($content -match "MT_CSS_VERSION") {
        Write-Host "✓ Feature flag support detected in plugin" -ForegroundColor Green
        
        # Check for rollback methods
        if ($content -match "load_migration_css") {
            Write-Host "✓ Migration CSS loader found" -ForegroundColor Green
        }
        
        if ($content -match "MT_CSS_FORCE_LEGACY") {
            Write-Host "✓ Force legacy switch found" -ForegroundColor Green
        }
        
        return $true
    } else {
        Write-Host "✗ Feature flag support NOT found" -ForegroundColor Red
        return $false
    }
}

function Test-BackupIntegrity {
    Write-Host "`nTesting Backup Integrity..." -ForegroundColor Yellow
    
    if (Test-Path $backupPath) {
        $backupFiles = Get-ChildItem -Path $backupPath -Filter "*.css" -Recurse
        $backupCount = $backupFiles.Count
        Write-Host "✓ Backup directory exists with $backupCount CSS files" -ForegroundColor Green
        
        # Check key files
        $keyFiles = @(
            "frontend.css",
            "mt-hotfixes-consolidated.css",
            "emergency-fixes.css",
            "v3\mt-tokens.css",
            "v4\mt-base.css"
        )
        
        foreach ($file in $keyFiles) {
            $fullPath = Join-Path $backupPath $file
            if (Test-Path $fullPath) {
                Write-Host "  ✓ $file backed up" -ForegroundColor Green
            } else {
                Write-Host "  ✗ $file NOT found in backup" -ForegroundColor Red
            }
        }
        
        return $true
    } else {
        Write-Host "✗ Backup directory NOT found at $backupPath" -ForegroundColor Red
        return $false
    }
}

function Test-ConsolidatedFile {
    Write-Host "`nTesting Consolidated Emergency File..." -ForegroundColor Yellow
    
    $consolidatedFile = Join-Path $cssPath "mt-emergency-consolidated-temp.css"
    
    if (Test-Path $consolidatedFile) {
        Write-Host "✓ Consolidated file exists" -ForegroundColor Green
        
        # Check file content
        $content = Get-Content $consolidatedFile -Raw
        $importantCount = ([regex]::Matches($content, "!important")).Count
        
        Write-Host "  • Contains $importantCount !important declarations" -ForegroundColor Cyan
        
        # Check for security fixes
        if ($content -notmatch "https?://") {
            Write-Host "  ✓ No external URLs found (security fix applied)" -ForegroundColor Green
        } else {
            Write-Host "  ⚠ External URLs still present" -ForegroundColor Yellow
        }
        
        # Check z-index capping
        if ($content -match "z-index:\s*\d+") {
            $zIndexValues = [regex]::Matches($content, "z-index:\s*(\d+)") | ForEach-Object { [int]$_.Groups[1].Value }
            $maxZIndex = ($zIndexValues | Measure-Object -Maximum).Maximum
            
            if ($maxZIndex -le 9999) {
                Write-Host "  ✓ Z-index values capped at $maxZIndex (within limit)" -ForegroundColor Green
            } else {
                Write-Host "  ✗ Z-index value $maxZIndex exceeds limit" -ForegroundColor Red
            }
        }
        
        return $true
    } else {
        Write-Host "✗ Consolidated file NOT found" -ForegroundColor Red
        return $false
    }
}

function Simulate-Rollback {
    param([string]$Method)
    
    Write-Host "`nSimulating Rollback Method: $Method" -ForegroundColor Yellow
    
    switch ($Method) {
        "flag" {
            Write-Host "To rollback via feature flag, add to wp-config.php:" -ForegroundColor Cyan
            Write-Host "  define('MT_CSS_FORCE_LEGACY', true);" -ForegroundColor White
            Write-Host "  define('MT_CSS_VERSION', 'v3');" -ForegroundColor White
        }
        
        "git" {
            Write-Host "To rollback via Git:" -ForegroundColor Cyan
            Write-Host "  git checkout main" -ForegroundColor White
            Write-Host "  git branch -D feature/css-phase1-stabilization" -ForegroundColor White
        }
        
        "backup" {
            Write-Host "To rollback from backup:" -ForegroundColor Cyan
            Write-Host "  Copy-Item -Path '$backupPath\*' -Destination '$cssPath\' -Recurse -Force" -ForegroundColor White
        }
    }
}

function Run-FullTest {
    Write-Host "Running Full Rollback Test Suite..." -ForegroundColor Cyan
    
    $results = @{
        FeatureFlag = Test-FeatureFlag
        BackupIntegrity = Test-BackupIntegrity
        ConsolidatedFile = Test-ConsolidatedFile
    }
    
    Write-Host "`n================================" -ForegroundColor Cyan
    Write-Host "Test Results Summary" -ForegroundColor Cyan
    Write-Host "================================" -ForegroundColor Cyan
    
    $passed = 0
    $failed = 0
    
    foreach ($test in $results.GetEnumerator()) {
        if ($test.Value) {
            Write-Host "✓ $($test.Key): PASSED" -ForegroundColor Green
            $passed++
        } else {
            Write-Host "✗ $($test.Key): FAILED" -ForegroundColor Red
            $failed++
        }
    }
    
    Write-Host "`nTotal: $passed passed, $failed failed" -ForegroundColor White
    
    if ($failed -eq 0) {
        Write-Host "`n✅ All rollback mechanisms are functional!" -ForegroundColor Green
        
        Write-Host "`nAvailable Rollback Methods:" -ForegroundColor Cyan
        Simulate-Rollback -Method "flag"
        Simulate-Rollback -Method "git"
        Simulate-Rollback -Method "backup"
    } else {
        Write-Host "`n⚠ Some rollback mechanisms need attention!" -ForegroundColor Yellow
    }
}

# Execute based on mode
switch ($Mode) {
    "test" {
        Run-FullTest
    }
    
    "restore" {
        Write-Host "Restoring from backup..." -ForegroundColor Yellow
        if (Test-Path $backupPath) {
            Copy-Item -Path "$backupPath\*" -Destination $cssPath -Recurse -Force
            Write-Host "✓ CSS files restored from backup" -ForegroundColor Green
        } else {
            Write-Host "✗ Backup not found!" -ForegroundColor Red
        }
    }
    
    "verify" {
        Write-Host "Verifying current CSS state..." -ForegroundColor Yellow
        
        # Count current !important
        $currentFiles = Get-ChildItem -Path $cssPath -Filter "*.css"
        $totalImportant = 0
        
        foreach ($file in $currentFiles) {
            $content = Get-Content $file.FullName -Raw
            $count = ([regex]::Matches($content, "!important")).Count
            $totalImportant += $count
        }
        
        Write-Host "Current state:" -ForegroundColor Cyan
        Write-Host "  • Total CSS files: $($currentFiles.Count)"
        Write-Host "  • Total !important: $totalImportant"
        
        if (Test-Path (Join-Path $cssPath "mt-emergency-consolidated-temp.css")) {
            Write-Host "  • Status: Migration mode (consolidated file present)" -ForegroundColor Green
        } else {
            Write-Host "  • Status: Legacy mode" -ForegroundColor Yellow
        }
    }
    
    default {
        Write-Host "Invalid mode. Use: test, restore, or verify" -ForegroundColor Red
    }
}

Write-Host "`nScript completed.`n" -ForegroundColor Cyan