# Mobility Trailblazers CSS Consolidation Script
# Phase 1: Consolidate Fix CSS Files
# Version: 1.0.0
# Date: 2025-08-24

param(
    [switch]$DryRun = $false,
    [switch]$Verbose = $false,
    [switch]$Force = $false
)

# Script configuration
$scriptStartTime = Get-Date
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $scriptPath
$cssPath = Join-Path $projectRoot "assets\css"
$refactoredPath = Join-Path $cssPath "refactored"
$logPath = Join-Path $scriptPath "logs"
$logFile = Join-Path $logPath "css-consolidation-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"

# CSS files to consolidate
$cssFilesToConsolidate = @(
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

# Output files
$tempOutputFile = Join-Path $refactoredPath "consolidated-fixes-temp.css"
$finalOutputFile = Join-Path $refactoredPath "consolidated-fixes.css"
$backupFile = Join-Path $refactoredPath "consolidated-fixes-backup-$(Get-Date -Format 'yyyyMMdd-HHmmss').css"

# Statistics tracking
$stats = @{
    FilesProcessed = 0
    TotalOriginalSize = 0
    TotalFinalSize = 0
    TotalRules = 0
    DuplicateRules = 0
    UniqueRules = 0
    ProcessingTime = 0
    Errors = @()
    Warnings = @()
}

# Logging functions
function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    
    # Console output with color coding
    switch ($Level) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        "DEBUG" { if ($Verbose) { Write-Host $logMessage -ForegroundColor Gray } }
        default { Write-Host $logMessage }
    }
    
    # File logging
    if (-not $DryRun) {
        Add-Content -Path $logFile -Value $logMessage -ErrorAction SilentlyContinue
    }
}

# Initialize script
function Initialize-Script {
    Write-Log "========================================" "INFO"
    Write-Log "CSS Consolidation Script Started" "INFO"
    Write-Log "========================================" "INFO"
    Write-Log "Script Version: 1.0.0" "INFO"
    Write-Log "Dry Run Mode: $DryRun" "INFO"
    Write-Log "Verbose Mode: $Verbose" "INFO"
    Write-Log "Force Mode: $Force" "INFO"
    Write-Log "" "INFO"
    
    # Create log directory if it doesn't exist
    if (-not (Test-Path $logPath)) {
        New-Item -ItemType Directory -Path $logPath -Force | Out-Null
        Write-Log "Created log directory: $logPath" "DEBUG"
    }
    
    # Create refactored directory if it doesn't exist
    if (-not (Test-Path $refactoredPath)) {
        if ($DryRun) {
            Write-Log "[DRY RUN] Would create directory: $refactoredPath" "INFO"
        } else {
            New-Item -ItemType Directory -Path $refactoredPath -Force | Out-Null
            Write-Log "Created refactored directory: $refactoredPath" "SUCCESS"
        }
    }
    
    # Check if output file exists and handle accordingly
    if ((Test-Path $finalOutputFile) -and -not $Force) {
        Write-Log "Output file already exists: $finalOutputFile" "WARNING"
        $response = Read-Host "Do you want to overwrite it? (y/n)"
        if ($response -ne 'y') {
            Write-Log "Consolidation cancelled by user" "INFO"
            exit 0
        }
        
        # Create backup
        if (-not $DryRun) {
            Copy-Item $finalOutputFile $backupFile
            Write-Log "Created backup: $backupFile" "SUCCESS"
        } else {
            Write-Log "[DRY RUN] Would create backup: $backupFile" "INFO"
        }
    }
}

# Validate CSS files exist
function Test-CSSFiles {
    Write-Log "Validating CSS files..." "INFO"
    $missingFiles = @()
    
    foreach ($file in $cssFilesToConsolidate) {
        $filePath = Join-Path $cssPath $file
        if (-not (Test-Path $filePath)) {
            $missingFiles += $file
            $stats.Errors += "Missing file: $file"
            Write-Log "Missing file: $file" "ERROR"
        } else {
            $fileInfo = Get-Item $filePath
            $stats.TotalOriginalSize += $fileInfo.Length
            Write-Log "Found: $file ($('{0:N2}' -f ($fileInfo.Length / 1KB)) KB)" "DEBUG"
        }
    }
    
    if ($missingFiles.Count -gt 0) {
        Write-Log "Missing $($missingFiles.Count) file(s)" "ERROR"
        if (-not $Force) {
            Write-Log "Use -Force to continue with available files" "INFO"
            return $false
        }
        Write-Log "Continuing with available files (-Force enabled)" "WARNING"
    }
    
    return $true
}

# Parse CSS content and extract rules
function Get-CSSRules {
    param(
        [string]$Content
    )
    
    $rules = @()
    
    # Remove comments but preserve rule structure
    $cleanContent = $Content -replace '/\*[\s\S]*?\*/', ''
    
    # Extract CSS rules (simplified parser)
    # This regex captures selector and declaration block
    $rulePattern = '([^{}]+)\s*\{([^{}]+)\}'
    $matches = [regex]::Matches($cleanContent, $rulePattern)
    
    foreach ($match in $matches) {
        if ($match.Success) {
            $selector = $match.Groups[1].Value.Trim()
            $declarations = $match.Groups[2].Value.Trim()
            
            if ($selector -and $declarations) {
                # Normalize the rule for comparison
                $normalizedSelector = ($selector -split ',') | ForEach-Object { $_.Trim() } | Sort-Object
                $normalizedDeclarations = ($declarations -split ';') | 
                    Where-Object { $_.Trim() } | 
                    ForEach-Object { $_.Trim() -replace '\s+', ' ' } | 
                    Sort-Object
                
                $rules += @{
                    Original = "$selector { $declarations }"
                    Selector = $selector
                    Declarations = $declarations
                    NormalizedSelector = ($normalizedSelector -join ', ')
                    NormalizedDeclarations = ($normalizedDeclarations -join '; ')
                    Hash = ([System.Security.Cryptography.SHA256]::Create().ComputeHash(
                        [System.Text.Encoding]::UTF8.GetBytes(
                            ($normalizedSelector -join ',') + ($normalizedDeclarations -join ';')
                        )
                    ) | ForEach-Object { $_.ToString("x2") }) -join ''
                }
            }
        }
    }
    
    return $rules
}

# Process and consolidate CSS files
function Invoke-CSSConsolidation {
    Write-Log "" "INFO"
    Write-Log "Starting CSS consolidation..." "INFO"
    
    $allRules = @()
    $fileHeaders = @()
    
    # Process each CSS file
    foreach ($file in $cssFilesToConsolidate) {
        $filePath = Join-Path $cssPath $file
        
        if (-not (Test-Path $filePath)) {
            Write-Log "Skipping missing file: $file" "WARNING"
            continue
        }
        
        Write-Log "Processing: $file" "INFO"
        
        try {
            $content = Get-Content $filePath -Raw -ErrorAction Stop
            $fileSize = (Get-Item $filePath).Length
            
            # Extract header comments if present
            if ($content -match '^(/\*[\s\S]*?\*/)') {
                $header = $Matches[1]
                if ($header -notmatch 'Emergency Fixes for Mobility Trailblazers') {
                    $fileHeaders += "`n/* Source: $file */`n"
                }
            }
            
            # Parse CSS rules
            $rules = Get-CSSRules -Content $content
            $allRules += $rules
            
            $stats.FilesProcessed++
            Write-Log "  - Extracted $($rules.Count) rules ($('{0:N2}' -f ($fileSize / 1KB)) KB)" "DEBUG"
            
        } catch {
            $stats.Errors += "Error processing $file : $_"
            Write-Log "Error processing $file : $_" "ERROR"
        }
    }
    
    # Remove duplicates
    Write-Log "" "INFO"
    Write-Log "Removing duplicate rules..." "INFO"
    
    $uniqueRules = @{}
    $duplicateCount = 0
    
    foreach ($rule in $allRules) {
        if (-not $uniqueRules.ContainsKey($rule.Hash)) {
            $uniqueRules[$rule.Hash] = $rule
        } else {
            $duplicateCount++
            Write-Log "  Duplicate found: $($rule.Selector)" "DEBUG"
        }
    }
    
    $stats.TotalRules = $allRules.Count
    $stats.DuplicateRules = $duplicateCount
    $stats.UniqueRules = $uniqueRules.Count
    
    Write-Log "Found $duplicateCount duplicate rules" "INFO"
    Write-Log "Retained $($uniqueRules.Count) unique rules" "SUCCESS"
    
    # Generate consolidated CSS
    Write-Log "" "INFO"
    Write-Log "Generating consolidated CSS..." "INFO"
    
    $consolidatedCSS = @"
/**
 * Consolidated CSS Fixes for Mobility Trailblazers
 * Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
 * Source Files: $($stats.FilesProcessed) files
 * Original Rules: $($stats.TotalRules)
 * Unique Rules: $($stats.UniqueRules)
 * Duplicates Removed: $($stats.DuplicateRules)
 * 
 * This file consolidates multiple CSS fix files to improve performance
 * and maintainability while removing duplicate rules.
 */

"@
    
    # Group rules by similarity for better organization
    $groupedRules = $uniqueRules.Values | Group-Object { 
        if ($_.Selector -match '^\.mt-') { 'MT Components' }
        elseif ($_.Selector -match 'evaluation|criterion') { 'Evaluation System' }
        elseif ($_.Selector -match 'jury|dashboard') { 'Jury Dashboard' }
        elseif ($_.Selector -match 'candidate') { 'Candidate System' }
        elseif ($_.Selector -match 'modal') { 'Modals' }
        elseif ($_.Selector -match 'medal|badge') { 'Medals and Badges' }
        else { 'General Fixes' }
    }
    
    foreach ($group in $groupedRules | Sort-Object Name) {
        $consolidatedCSS += "`n/* ========== $($group.Name) ========== */`n"
        
        foreach ($rule in $group.Group | Sort-Object Selector) {
            $consolidatedCSS += "`n$($rule.Selector) {`n"
            
            # Format declarations nicely
            $declarations = $rule.Declarations -split ';' | Where-Object { $_.Trim() }
            foreach ($declaration in $declarations) {
                $consolidatedCSS += "    $($declaration.Trim());`n"
            }
            
            $consolidatedCSS += "}`n"
        }
    }
    
    # Add EOF marker
    $consolidatedCSS += "`n/* End of consolidated CSS */"
    
    return $consolidatedCSS
}

# Write consolidated CSS to file
function Save-ConsolidatedCSS {
    param(
        [string]$Content
    )
    
    if ($DryRun) {
        Write-Log "" "INFO"
        Write-Log "[DRY RUN] Would write consolidated CSS to:" "INFO"
        Write-Log "  Temp file: $tempOutputFile" "INFO"
        Write-Log "  Final file: $finalOutputFile" "INFO"
        
        # Show sample of output
        $lines = $Content -split "`n"
        Write-Log "" "INFO"
        Write-Log "Sample output (first 20 lines):" "INFO"
        for ($i = 0; $i -lt [Math]::Min(20, $lines.Count); $i++) {
            Write-Log $lines[$i] "DEBUG"
        }
        
        # Calculate what the size would be
        $bytes = [System.Text.Encoding]::UTF8.GetByteCount($Content)
        $stats.TotalFinalSize = $bytes
        
    } else {
        try {
            # Write to temp file first
            Write-Log "Writing to temp file: $tempOutputFile" "INFO"
            Set-Content -Path $tempOutputFile -Value $Content -Encoding UTF8 -ErrorAction Stop
            
            # Verify temp file
            if (Test-Path $tempOutputFile) {
                $tempInfo = Get-Item $tempOutputFile
                Write-Log "Temp file created successfully ($('{0:N2}' -f ($tempInfo.Length / 1KB)) KB)" "SUCCESS"
                
                # Move to final location
                Write-Log "Moving to final location: $finalOutputFile" "INFO"
                Move-Item -Path $tempOutputFile -Destination $finalOutputFile -Force
                
                if (Test-Path $finalOutputFile) {
                    $finalInfo = Get-Item $finalOutputFile
                    $stats.TotalFinalSize = $finalInfo.Length
                    Write-Log "Final file created successfully ($('{0:N2}' -f ($finalInfo.Length / 1KB)) KB)" "SUCCESS"
                } else {
                    throw "Failed to create final output file"
                }
            } else {
                throw "Failed to create temp file"
            }
            
        } catch {
            $stats.Errors += "Error saving consolidated CSS: $_"
            Write-Log "Error saving consolidated CSS: $_" "ERROR"
            return $false
        }
    }
    
    return $true
}

# Generate summary report
function Show-Summary {
    $stats.ProcessingTime = (Get-Date) - $scriptStartTime
    
    Write-Log "" "INFO"
    Write-Log "========================================" "INFO"
    Write-Log "CONSOLIDATION SUMMARY" "SUCCESS"
    Write-Log "========================================" "INFO"
    Write-Log "" "INFO"
    
    Write-Log "Files Processed: $($stats.FilesProcessed) / $($cssFilesToConsolidate.Count)" "INFO"
    Write-Log "Total Rules Processed: $($stats.TotalRules)" "INFO"
    Write-Log "Duplicate Rules Removed: $($stats.DuplicateRules)" "INFO"
    Write-Log "Unique Rules Retained: $($stats.UniqueRules)" "INFO"
    Write-Log "" "INFO"
    
    $originalSizeKB = $stats.TotalOriginalSize / 1KB
    $finalSizeKB = $stats.TotalFinalSize / 1KB
    $reduction = if ($stats.TotalOriginalSize -gt 0) {
        (($stats.TotalOriginalSize - $stats.TotalFinalSize) / $stats.TotalOriginalSize) * 100
    } else { 0 }
    
    Write-Log "Original Total Size: $('{0:N2}' -f $originalSizeKB) KB" "INFO"
    Write-Log "Final Size: $('{0:N2}' -f $finalSizeKB) KB" "INFO"
    Write-Log "Size Reduction: $('{0:N1}' -f $reduction)%" "SUCCESS"
    Write-Log "" "INFO"
    
    if ($stats.Errors.Count -gt 0) {
        Write-Log "Errors Encountered: $($stats.Errors.Count)" "ERROR"
        foreach ($error in $stats.Errors) {
            Write-Log "  - $error" "ERROR"
        }
        Write-Log "" "INFO"
    }
    
    if ($stats.Warnings.Count -gt 0) {
        Write-Log "Warnings: $($stats.Warnings.Count)" "WARNING"
        foreach ($warning in $stats.Warnings) {
            Write-Log "  - $warning" "WARNING"
        }
        Write-Log "" "INFO"
    }
    
    Write-Log "Processing Time: $($stats.ProcessingTime.TotalSeconds) seconds" "INFO"
    
    if ($DryRun) {
        Write-Log "" "INFO"
        Write-Log "DRY RUN COMPLETE - No files were modified" "WARNING"
        Write-Log "Run without -DryRun to perform actual consolidation" "INFO"
    } else {
        Write-Log "" "INFO"
        Write-Log "Output File: $finalOutputFile" "SUCCESS"
        
        if (Test-Path $backupFile) {
            Write-Log "Backup File: $backupFile" "INFO"
        }
    }
    
    Write-Log "" "INFO"
    Write-Log "========================================" "INFO"
    Write-Log "CSS Consolidation Complete" "SUCCESS"
    Write-Log "========================================" "INFO"
}

# Main execution
try {
    # Initialize
    Initialize-Script
    
    # Validate files
    if (-not (Test-CSSFiles)) {
        if (-not $Force) {
            Write-Log "Consolidation aborted due to missing files" "ERROR"
            exit 1
        }
    }
    
    # Process and consolidate
    $consolidatedContent = Invoke-CSSConsolidation
    
    if ($consolidatedContent) {
        # Save consolidated CSS
        if (Save-ConsolidatedCSS -Content $consolidatedContent) {
            Write-Log "CSS consolidation completed successfully" "SUCCESS"
        } else {
            Write-Log "CSS consolidation completed with errors" "WARNING"
        }
    } else {
        Write-Log "No content to consolidate" "ERROR"
        exit 1
    }
    
} catch {
    Write-Log "Unexpected error: $_" "ERROR"
    Write-Log $_.ScriptStackTrace "DEBUG"
    exit 1
} finally {
    # Show summary
    Show-Summary
    
    # Save log file location
    if (-not $DryRun -and (Test-Path $logFile)) {
        Write-Log "" "INFO"
        Write-Log "Log file saved: $logFile" "INFO"
    }
}

# Return success
exit 0