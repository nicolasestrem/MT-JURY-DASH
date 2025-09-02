# PowerShell CSS Consolidation Validation Script
# Version: 1.0.0
# Date: 2025-08-26
# Purpose: Phase 1 CSS Architecture Remediation - Safety Validation and Consolidation

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [string]$ProjectRoot = "C:\Users\nicol\Desktop\mobility-trailblazers",
    
    [Parameter(Mandatory = $false)]
    [switch]$DryRun = $true,
    
    [Parameter(Mandatory = $false)]
    [int]$BatchSize = 50,
    
    [Parameter(Mandatory = $false)]
    [switch]$SkipBackup = $false,
    
    [Parameter(Mandatory = $false)]
    [switch]$ValidateOnly = $false
)

# ===================================
# ERROR HANDLING AND LOGGING
# ===================================

$ErrorActionPreference = "Stop"
$ProgressPreference = "Continue"
$ValidationErrors = @()
$ProcessingLog = @()

function Write-ValidationLog {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Message,
        
        [Parameter(Mandatory = $false)]
        [ValidateSet("Info", "Warning", "Error", "Success")]
        [string]$Level = "Info"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$Level] $Message"
    
    $ProcessingLog += $logEntry
    
    switch ($Level) {
        "Info"    { Write-Host $logEntry -ForegroundColor White }
        "Warning" { Write-Warning $logEntry }
        "Error"   { Write-Error $logEntry }
        "Success" { Write-Host $logEntry -ForegroundColor Green }
    }
}

# ===================================
# CSS SYNTAX VALIDATION FUNCTIONS
# ===================================

function Test-CSSSelector {
    param([string]$Selector)
    
    # Basic CSS selector validation patterns
    $invalidPatterns = @(
        '^\s*$',                    # Empty selector
        '[{}]',                     # Braces in selector
        ';;',                       # Double semicolons
        '\)\s*\(',                  # Invalid parentheses
        '^\s*[0-9]',               # Starting with number
        '[^\w\s\-_:.,>#~+\[\]="''()]' # Invalid characters
    )
    
    foreach ($pattern in $invalidPatterns) {
        if ($Selector -match $pattern) {
            return $false
        }
    }
    return $true
}

function Test-CSSProperty {
    param([string]$Property, [string]$Value)
    
    # Check for common CSS syntax errors
    $errors = @()
    
    # Check for !important usage (violates CSS v4 framework)
    if ($Value -match '!important') {
        $errors += "CRITICAL: !important usage violates CSS v4 framework"
    }
    
    # Check for missing semicolons at line end
    if ($Value -notmatch ';$' -and $Value.Trim() -ne '') {
        $errors += "Missing semicolon at end of property value"
    }
    
    # Check for invalid color values
    if ($Property -match 'color|background' -and $Value -match '#[^0-9A-Fa-f]') {
        $errors += "Invalid hex color format"
    }
    
    # Check for unclosed quotes
    $singleQuotes = ($Value -split "'" | Measure-Object).Count - 1
    $doubleQuotes = ($Value -split '"' | Measure-Object).Count - 1
    
    if ($singleQuotes % 2 -ne 0) {
        $errors += "Unclosed single quote"
    }
    
    if ($doubleQuotes % 2 -ne 0) {
        $errors += "Unclosed double quote"
    }
    
    return $errors
}

function Test-CSSFile {
    param([string]$FilePath)
    
    Write-ValidationLog "Validating CSS syntax in: $FilePath" "Info"
    
    if (!(Test-Path $FilePath)) {
        return @("File not found: $FilePath")
    }
    
    $errors = @()
    $content = Get-Content $FilePath -Raw -Encoding UTF8
    $lineNumber = 0
    
    # Split content into lines for line-by-line validation
    $lines = $content -split "`r?`n"
    
    # Track brace balance
    $braceCount = 0
    $inRuleSet = $false
    $currentSelector = ""
    
    foreach ($line in $lines) {
        $lineNumber++
        $trimmedLine = $line.Trim()
        
        # Skip comments and empty lines
        if ($trimmedLine -eq '' -or $trimmedLine.StartsWith('/*') -or $trimmedLine.StartsWith('//')) {
            continue
        }
        
        # Count braces for balance validation
        $openBraces = ($line -split '{' | Measure-Object).Count - 1
        $closeBraces = ($line -split '}' | Measure-Object).Count - 1
        $braceCount += $openBraces - $closeBraces
        
        # Detect rule sets
        if ($line -match '{') {
            $inRuleSet = $true
            $currentSelector = ($line -split '{')[0].Trim()
            
            # Validate selector
            if (!(Test-CSSSelector $currentSelector)) {
                $errors += "Line $lineNumber - Invalid CSS selector: $currentSelector"
            }
        }
        
        # Validate properties within rule sets
        if ($inRuleSet -and $line -match ':' -and !($line -match '{')) {
            $propertyMatch = $line -match '^\s*([^:]+):\s*([^;]+);?\s*$'
            if ($propertyMatch) {
                $property = $matches[1].Trim()
                $value = $matches[2].Trim()
                
                $propertyErrors = Test-CSSProperty $property $value
                foreach ($error in $propertyErrors) {
                    $errors += "Line $lineNumber - $error in property '$property: $value'"
                }
            }
        }
        
        if ($line -match '}') {
            $inRuleSet = $false
        }
    }
    
    # Check brace balance
    if ($braceCount -ne 0) {
        $errors += "Unbalanced braces - difference: $braceCount"
    }
    
    # Check for specific emergency file issues
    $fileName = Split-Path $FilePath -Leaf
    if ($fileName -match 'emergency|hotfix|fix') {
        Write-ValidationLog "Performing enhanced validation on emergency file: $fileName" "Warning"
        
        # Check for CSS v4 framework compliance
        if ($content -match '!important') {
            $errors += "CRITICAL: Emergency file contains !important declarations (violates CSS v4)"
        }
        
        # Check for proper BEM methodology
        if ($content -notmatch '\.mt-[\w-]+(__[\w-]+)?(--[\w-]+)?') {
            $errors += "WARNING: Emergency file may not follow BEM methodology"
        }
    }
    
    return $errors
}

# ===================================
# FILE DISCOVERY AND ANALYSIS
# ===================================

function Get-CSSFiles {
    param([string]$RootPath)
    
    Write-ValidationLog "Discovering CSS files in: $RootPath" "Info"
    
    $cssFiles = @()
    
    # Main CSS directories
    $searchPaths = @(
        "$RootPath\assets\css",
        "$RootPath\assets\css\v3",
        "$RootPath\assets\css\v4",
        "$RootPath\assets\css\components",
        "$RootPath\assets\min\css"
    )
    
    foreach ($path in $searchPaths) {
        if (Test-Path $path) {
            $files = Get-ChildItem $path -Filter "*.css" -Recurse | Where-Object { 
                $_.Name -notmatch '\.min\.css$' -and 
                $_.Directory.Name -ne 'backup' -and
                $_.Directory.Name -ne 'archive' -and
                $_.Directory.Name -notmatch 'backup-'
            }
            $cssFiles += $files
        }
    }
    
    Write-ValidationLog "Found $($cssFiles.Count) CSS files to process" "Info"
    return $cssFiles
}

function Get-EmergencyFiles {
    param([string]$RootPath)
    
    $emergencyPatterns = @(
        "*emergency*",
        "*hotfix*",
        "*fix*",
        "*critical*"
    )
    
    $emergencyFiles = @()
    
    foreach ($pattern in $emergencyPatterns) {
        $files = Get-ChildItem "$RootPath\assets\css" -Filter "$pattern.css" -ErrorAction SilentlyContinue
        $emergencyFiles += $files
    }
    
    Write-ValidationLog "Found $($emergencyFiles.Count) emergency/hotfix files" "Warning"
    return $emergencyFiles
}

# ===================================
# BACKUP AND SAFETY FUNCTIONS
# ===================================

function New-BackupDirectory {
    param([string]$ProjectRoot)
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$ProjectRoot\backups\css-consolidation-$timestamp"
    
    if (!(Test-Path $backupPath)) {
        New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
        Write-ValidationLog "Created backup directory: $backupPath" "Success"
    }
    
    return $backupPath
}

function Backup-CSSFiles {
    param(
        [array]$Files,
        [string]$BackupPath
    )
    
    Write-ValidationLog "Creating backup of $($Files.Count) CSS files..." "Info"
    
    $progress = 0
    foreach ($file in $Files) {
        $progress++
        Write-Progress -Activity "Backing up CSS files" -Status "Processing $($file.Name)" -PercentComplete (($progress / $Files.Count) * 100)
        
        $relativePath = $file.FullName.Replace($ProjectRoot, "").TrimStart('\')
        $backupFilePath = Join-Path $BackupPath $relativePath
        $backupFileDir = Split-Path $backupFilePath -Parent
        
        if (!(Test-Path $backupFileDir)) {
            New-Item -ItemType Directory -Path $backupFileDir -Force | Out-Null
        }
        
        Copy-Item $file.FullName $backupFilePath -Force
    }
    
    Write-Progress -Activity "Backing up CSS files" -Completed
    Write-ValidationLog "Backup completed successfully" "Success"
}

# ===================================
# CONSOLIDATION FUNCTIONS
# ===================================

function Test-ConsolidationSafety {
    param(
        [array]$Files,
        [string]$ProjectRoot
    )
    
    Write-ValidationLog "Performing consolidation safety checks..." "Info"
    
    $safetyIssues = @()
    
    # Check Git status
    try {
        $gitStatus = git -C $ProjectRoot status --porcelain 2>&1
        if ($LASTEXITCODE -ne 0) {
            $safetyIssues += "Git repository not found or corrupted"
        } elseif ($gitStatus) {
            $safetyIssues += "Uncommitted changes detected - commit before consolidation"
        }
    } catch {
        $safetyIssues += "Unable to check Git status: $($_.Exception.Message)"
    }
    
    # Check file locks
    foreach ($file in $Files) {
        try {
            $fileStream = [System.IO.File]::OpenWrite($file.FullName)
            $fileStream.Close()
        } catch {
            $safetyIssues += "File locked or inaccessible: $($file.Name)"
        }
    }
    
    # Check disk space (need at least 100MB free)
    $drive = Split-Path $ProjectRoot -Qualifier
    $freeSpace = (Get-WmiObject -Class Win32_LogicalDisk -Filter "DeviceID='$drive'").FreeSpace
    if ($freeSpace -lt 104857600) {  # 100MB
        $safetyIssues += "Insufficient disk space for consolidation"
    }
    
    return $safetyIssues
}

function Invoke-CSSConsolidation {
    param(
        [array]$Files,
        [string]$OutputPath,
        [int]$BatchSize = 50
    )
    
    Write-ValidationLog "Starting CSS consolidation process..." "Info"
    
    $batches = @()
    for ($i = 0; $i -lt $Files.Count; $i += $BatchSize) {
        $batch = $Files[$i..([Math]::Min($i + $BatchSize - 1, $Files.Count - 1))]
        $batches += , $batch
    }
    
    Write-ValidationLog "Processing $($batches.Count) batches of up to $BatchSize files each" "Info"
    
    $consolidatedContent = @()
    $consolidatedContent += "/**"
    $consolidatedContent += " * Mobility Trailblazers - CSS Consolidation Phase 1"
    $consolidatedContent += " * Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    $consolidatedContent += " * Total Files: $($Files.Count)"
    $consolidatedContent += " * CSS v4 Framework Compliant"
    $consolidatedContent += " */"
    $consolidatedContent += ""
    
    $batchNumber = 0
    foreach ($batch in $batches) {
        $batchNumber++
        Write-Progress -Activity "Consolidating CSS" -Status "Processing batch $batchNumber of $($batches.Count)" -PercentComplete (($batchNumber / $batches.Count) * 100)
        
        $consolidatedContent += "/* ========================================"
        $consolidatedContent += "   BATCH $batchNumber - $($batch.Count) FILES"
        $consolidatedContent += "   ======================================== */"
        $consolidatedContent += ""
        
        foreach ($file in $batch) {
            Write-ValidationLog "Processing file: $($file.Name)" "Info"
            
            $consolidatedContent += "/* ----------------------------------------"
            $consolidatedContent += "   FILE: $($file.Name)"
            $consolidatedContent += "   PATH: $($file.FullName.Replace($ProjectRoot, '').Replace('\', '/'))"
            $consolidatedContent += "   ---------------------------------------- */"
            $consolidatedContent += ""
            
            $fileContent = Get-Content $file.FullName -Raw -Encoding UTF8
            $consolidatedContent += $fileContent
            $consolidatedContent += ""
            $consolidatedContent += ""
        }
    }
    
    Write-Progress -Activity "Consolidating CSS" -Completed
    
    if (!$DryRun) {
        $consolidatedContent | Out-File -FilePath $OutputPath -Encoding UTF8
        Write-ValidationLog "Consolidated CSS written to: $OutputPath" "Success"
    } else {
        Write-ValidationLog "DRY RUN: Would write consolidated CSS to: $OutputPath" "Warning"
    }
}

# ===================================
# MAIN EXECUTION LOGIC
# ===================================

function Invoke-ValidationReport {
    Write-ValidationLog "========================================" "Info"
    Write-ValidationLog "CSS CONSOLIDATION VALIDATION REPORT" "Info"
    Write-ValidationLog "========================================" "Info"
    Write-ValidationLog "Project Root: $ProjectRoot" "Info"
    Write-ValidationLog "Dry Run Mode: $DryRun" "Info"
    Write-ValidationLog "Batch Size: $BatchSize" "Info"
    Write-ValidationLog "Skip Backup: $SkipBackup" "Info"
    Write-ValidationLog "Validate Only: $ValidateOnly" "Info"
    Write-ValidationLog "========================================" "Info"
}

# ===================================
# MAIN SCRIPT EXECUTION
# ===================================

try {
    Invoke-ValidationReport
    
    # Validate project structure
    if (!(Test-Path "$ProjectRoot\assets\css")) {
        throw "CSS directory not found: $ProjectRoot\assets\css"
    }
    
    # Get CSS files
    $cssFiles = Get-CSSFiles $ProjectRoot
    $emergencyFiles = Get-EmergencyFiles $ProjectRoot
    
    Write-ValidationLog "Total CSS files found: $($cssFiles.Count)" "Info"
    Write-ValidationLog "Emergency/hotfix files: $($emergencyFiles.Count)" "Warning"
    
    # Priority validation of emergency files
    Write-ValidationLog "========================================" "Info"
    Write-ValidationLog "EMERGENCY FILES VALIDATION (PRIORITY)" "Warning"
    Write-ValidationLog "========================================" "Info"
    
    foreach ($emergencyFile in $emergencyFiles) {
        Write-ValidationLog "Validating emergency file: $($emergencyFile.Name)" "Warning"
        $errors = Test-CSSFile $emergencyFile.FullName
        
        if ($errors.Count -gt 0) {
            $ValidationErrors += "EMERGENCY FILE: $($emergencyFile.Name)"
            foreach ($error in $errors) {
                $ValidationErrors += "  - $error"
                Write-ValidationLog "ERROR in $($emergencyFile.Name): $error" "Error"
            }
        } else {
            Write-ValidationLog "Emergency file validation passed: $($emergencyFile.Name)" "Success"
        }
    }
    
    # Validate all CSS files
    Write-ValidationLog "========================================" "Info"
    Write-ValidationLog "ALL CSS FILES VALIDATION" "Info"
    Write-ValidationLog "========================================" "Info"
    
    $fileProgress = 0
    foreach ($file in $cssFiles) {
        $fileProgress++
        Write-Progress -Activity "Validating CSS Files" -Status "Processing $($file.Name)" -PercentComplete (($fileProgress / $cssFiles.Count) * 100)
        
        $errors = Test-CSSFile $file.FullName
        
        if ($errors.Count -gt 0) {
            foreach ($error in $errors) {
                $ValidationErrors += "$($file.Name): $error"
            }
        }
    }
    
    Write-Progress -Activity "Validating CSS Files" -Completed
    
    # Safety checks for consolidation
    if (!$ValidateOnly) {
        Write-ValidationLog "========================================" "Info"
        Write-ValidationLog "CONSOLIDATION SAFETY CHECKS" "Info"
        Write-ValidationLog "========================================" "Info"
        
        $safetyIssues = Test-ConsolidationSafety $cssFiles $ProjectRoot
        
        if ($safetyIssues.Count -gt 0) {
            Write-ValidationLog "SAFETY ISSUES DETECTED:" "Error"
            foreach ($issue in $safetyIssues) {
                Write-ValidationLog "  - $issue" "Error"
                $ValidationErrors += $issue
            }
        } else {
            Write-ValidationLog "All safety checks passed" "Success"
        }
        
        # Create backup
        if (!$SkipBackup -and $ValidationErrors.Count -eq 0) {
            $backupPath = New-BackupDirectory $ProjectRoot
            Backup-CSSFiles $cssFiles $backupPath
        }
        
        # Perform consolidation
        if ($ValidationErrors.Count -eq 0) {
            $outputPath = "$ProjectRoot\assets\css\mt-consolidated-phase1.css"
            Invoke-CSSConsolidation $cssFiles $outputPath $BatchSize
        }
    }
    
    # Final report
    Write-ValidationLog "========================================" "Info"
    Write-ValidationLog "VALIDATION SUMMARY" "Info"
    Write-ValidationLog "========================================" "Info"
    Write-ValidationLog "Total files processed: $($cssFiles.Count)" "Info"
    Write-ValidationLog "Emergency files validated: $($emergencyFiles.Count)" "Warning"
    Write-ValidationLog "Validation errors found: $($ValidationErrors.Count)" $(if($ValidationErrors.Count -eq 0) { "Success" } else { "Error" })
    
    if ($ValidationErrors.Count -gt 0) {
        Write-ValidationLog "VALIDATION ERRORS SUMMARY:" "Error"
        foreach ($error in $ValidationErrors) {
            Write-ValidationLog "  - $error" "Error"
        }
        Write-ValidationLog "RECOMMENDATION: Fix validation errors before proceeding with consolidation" "Error"
        exit 1
    } else {
        Write-ValidationLog "ALL VALIDATIONS PASSED - Safe to proceed with consolidation" "Success"
        exit 0
    }
    
} catch {
    Write-ValidationLog "CRITICAL ERROR: $($_.Exception.Message)" "Error"
    Write-ValidationLog "Stack Trace: $($_.ScriptStackTrace)" "Error"
    exit 1
} finally {
    # Write log to file
    $logPath = "$ProjectRoot\logs\css-validation-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"
    $logDir = Split-Path $logPath -Parent
    
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }
    
    $ProcessingLog | Out-File -FilePath $logPath -Encoding UTF8
    Write-ValidationLog "Validation log saved to: $logPath" "Info"
}