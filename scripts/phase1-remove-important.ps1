# Phase 1 !important Removal Script
# Mobility Trailblazers WordPress Plugin
# Purpose: Remove !important declarations and increase specificity
# Created: 2025-08-24

param(
    [string]$InputFile = "assets\css\refactored\consolidated-fixes.css",
    [string]$OutputFile = "assets\css\refactored\consolidated-clean.css",
    [switch]$DryRun = $false,
    [switch]$Verbose = $false,
    [switch]$PreserveComments = $true
)

# Configuration
$projectRoot = Split-Path -Parent $PSScriptRoot
$inputPath = Join-Path $projectRoot $InputFile
$outputPath = Join-Path $projectRoot $OutputFile
$logFile = Join-Path $projectRoot "logs\phase1-important-removal.log"
$mappingFile = Join-Path $projectRoot "assets\css\refactored\important-removal-mapping.json"

# Specificity increase strategies
$specificityStrategies = @{
    # Pattern = Replacement strategy
    '^\.mt-' = '.mt-root $0'  # Add .mt-root parent
    '^#mt-' = 'body $0'       # Add body parent for IDs
    '^\[data-mt' = '.mt-container $0'  # Add container for data attributes
}

# Critical selectors that need extra specificity
$criticalSelectors = @(
    '.mt-candidate-card',
    '.mt-evaluation-form',
    '.mt-jury-dashboard',
    '.mt-modal',
    '.mt-filter'
)

function Write-Log {
    param($Message, $Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    
    # Ensure log directory exists
    $logDir = Split-Path $logFile
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Force -Path $logDir | Out-Null
    }
    
    Add-Content -Path $logFile -Value $logMessage
    
    if ($Verbose) {
        switch ($Level) {
            "ERROR" { Write-Host $Message -ForegroundColor Red }
            "WARNING" { Write-Host $Message -ForegroundColor Yellow }
            "SUCCESS" { Write-Host $Message -ForegroundColor Green }
            "DEBUG" { Write-Host $Message -ForegroundColor Gray }
            default { Write-Host $Message }
        }
    }
}

function Parse-CSSRule {
    param($Rule)
    
    if ($Rule -match '([^{]+)\s*{\s*([^}]+)\s*}') {
        $selector = $matches[1].Trim()
        $declarations = $matches[2].Trim()
        
        return @{
            Selector = $selector
            Declarations = $declarations
            HasImportant = $declarations -match '!important'
        }
    }
    return $null
}

function Calculate-Specificity {
    param($Selector)
    
    # Simple specificity calculation (not perfect but good enough)
    $ids = ([regex]::Matches($Selector, '#[a-zA-Z0-9\-_]+')).Count
    $classes = ([regex]::Matches($Selector, '\.[a-zA-Z0-9\-_]+')).Count
    $attributes = ([regex]::Matches($Selector, '\[[^\]]+\]')).Count
    $elements = ([regex]::Matches($Selector, '\b[a-zA-Z]+\b')).Count
    
    # Weight: ID=100, Class/Attribute=10, Element=1
    return ($ids * 100) + (($classes + $attributes) * 10) + $elements
}

function Increase-Specificity {
    param($Selector, $CurrentSpecificity)
    
    Write-Log "Increasing specificity for: $Selector (current: $CurrentSpecificity)" "DEBUG"
    
    # Check if it's a critical selector
    $isCritical = $false
    foreach ($critical in $criticalSelectors) {
        if ($Selector -match [regex]::Escape($critical)) {
            $isCritical = $true
            break
        }
    }
    
    # Apply specificity strategy
    $newSelector = $Selector
    foreach ($pattern in $specificityStrategies.Keys) {
        if ($Selector -match $pattern) {
            $replacement = $specificityStrategies[$pattern]
            $newSelector = $Selector -replace $pattern, $replacement
            Write-Log "Applied strategy: $pattern -> $newSelector" "DEBUG"
            break
        }
    }
    
    # For critical selectors, add additional specificity if needed
    if ($isCritical -and $newSelector -eq $Selector) {
        $newSelector = "body.mt-plugin-active $Selector"
        Write-Log "Critical selector enhanced: $newSelector" "DEBUG"
    }
    
    # If no strategy matched, add a generic parent
    if ($newSelector -eq $Selector -and $CurrentSpecificity -lt 20) {
        $newSelector = ".mt-wrapper $Selector"
        Write-Log "Generic enhancement: $newSelector" "DEBUG"
    }
    
    return $newSelector
}

function Remove-ImportantDeclarations {
    param($Content)
    
    Write-Log "Starting !important removal process..."
    
    $importantCount = ([regex]::Matches($Content, '!important')).Count
    Write-Log "Found $importantCount !important declarations to remove"
    
    # Track changes for mapping file
    $changeMap = @()
    $processedRules = 0
    $modifiedRules = 0
    
    # Process CSS rule by rule
    $pattern = '([^{}]+)\s*{([^}]+)}'
    $result = [regex]::Replace($Content, $pattern, {
        param($match)
        
        $selector = $match.Groups[1].Value.Trim()
        $declarations = $match.Groups[2].Value.Trim()
        $processedRules++
        
        # Skip media queries and keyframes
        if ($selector -match '^\s*@') {
            return $match.Value
        }
        
        # Check if rule has !important
        if ($declarations -match '!important') {
            $modifiedRules++
            
            # Calculate current specificity
            $currentSpecificity = Calculate-Specificity $selector
            
            # Remove !important from declarations
            $cleanDeclarations = $declarations -replace '\s*!important', ''
            
            # Determine if we need to increase specificity
            $needsIncrease = $currentSpecificity -lt 30
            $newSelector = $selector
            
            if ($needsIncrease) {
                $newSelector = Increase-Specificity $selector $currentSpecificity
                $newSpecificity = Calculate-Specificity $newSelector
                
                # Record the change
                $changeMap += @{
                    Original = $selector
                    Modified = $newSelector
                    OldSpecificity = $currentSpecificity
                    NewSpecificity = $newSpecificity
                    ImportantCount = ([regex]::Matches($declarations, '!important')).Count
                }
                
                Write-Log "Modified selector: $selector -> $newSelector (specificity: $currentSpecificity -> $newSpecificity)" "DEBUG"
            } else {
                # Just remove !important without changing selector
                $changeMap += @{
                    Original = $selector
                    Modified = $selector
                    OldSpecificity = $currentSpecificity
                    NewSpecificity = $currentSpecificity
                    ImportantCount = ([regex]::Matches($declarations, '!important')).Count
                }
            }
            
            return "$newSelector {`n  $cleanDeclarations`n}"
        }
        
        return $match.Value
    })
    
    Write-Log "Processed $processedRules rules, modified $modifiedRules rules"
    
    # Save change mapping
    if (!$DryRun -and $changeMap.Count -gt 0) {
        $changeMap | ConvertTo-Json -Depth 3 | Set-Content $mappingFile
        Write-Log "Change mapping saved to: $mappingFile" "SUCCESS"
    }
    
    return @{
        Content = $result
        ChangeMap = $changeMap
        Statistics = @{
            TotalRules = $processedRules
            ModifiedRules = $modifiedRules
            ImportantRemoved = $importantCount
        }
    }
}

function Add-SafetyOverrides {
    param($Content)
    
    Write-Log "Adding safety overrides for critical styles..."
    
    # Add override section at the end
    $overrides = @"

/* ============================================
   Safety Overrides - Phase 1
   These ensure critical functionality is preserved
   ============================================ */

/* Ensure modals are always on top */
.mt-modal,
.mt-modal-overlay {
    z-index: 99999;
}

/* Preserve form field visibility */
.mt-evaluation-form input,
.mt-evaluation-form select,
.mt-evaluation-form textarea {
    opacity: 1;
    visibility: visible;
}

/* Maintain responsive breakpoints */
@media (max-width: 768px) {
    .mt-root .mt-candidate-card {
        width: 100%;
        max-width: 100%;
    }
    
    .mt-root .mt-jury-dashboard {
        padding: 1rem;
    }
}

/* Critical layout preservation */
.mt-root {
    position: relative;
    display: block;
    width: 100%;
}

"@
    
    return $Content + $overrides
}

function Validate-CSS {
    param($Content)
    
    Write-Log "Validating CSS syntax..."
    
    # Basic validation checks
    $openBraces = ([regex]::Matches($Content, '{')).Count
    $closeBraces = ([regex]::Matches($Content, '}')).Count
    
    if ($openBraces -ne $closeBraces) {
        Write-Log "WARNING: Brace mismatch - Open: $openBraces, Close: $closeBraces" "WARNING"
        return $false
    }
    
    # Check for common issues
    $issues = @()
    
    # Check for empty rules
    if ($Content -match '{\s*}') {
        $issues += "Empty rules detected"
    }
    
    # Check for malformed selectors
    if ($Content -match '^\s*{') {
        $issues += "Malformed selectors detected"
    }
    
    # Check for unclosed strings (simplified check)
    $quoteCount = ([regex]::Matches($Content, '"')).Count
    if ($quoteCount % 2 -ne 0) {
        $issues += "Unclosed quotes detected"
    }
    
    if ($issues.Count -gt 0) {
        foreach ($issue in $issues) {
            Write-Log "Validation issue: $issue" "WARNING"
        }
        return $false
    }
    
    Write-Log "CSS validation passed" "SUCCESS"
    return $true
}

# Main execution
function Main {
    Write-Log "=" * 60
    Write-Log "Phase 1 !important Removal Script Started"
    Write-Log "Input: $InputFile | Output: $OutputFile"
    Write-Log "DryRun: $DryRun | Verbose: $Verbose"
    Write-Log "=" * 60
    
    # Check if input file exists
    if (!(Test-Path $inputPath)) {
        Write-Log "Input file not found: $inputPath" "ERROR"
        Write-Log "Please run phase1-consolidate-css.ps1 first" "ERROR"
        exit 1
    }
    
    # Read input file
    Write-Log "Reading input file..."
    $content = Get-Content $inputPath -Raw
    $originalSize = (Get-Item $inputPath).Length
    $originalImportant = ([regex]::Matches($content, '!important')).Count
    
    Write-Host ""
    Write-Host "Original Statistics" -ForegroundColor Cyan
    Write-Host "==================" -ForegroundColor Cyan
    Write-Host "File size: $([math]::Round($originalSize / 1KB, 2)) KB"
    Write-Host "!important count: $originalImportant"
    
    if ($originalImportant -eq 0) {
        Write-Log "No !important declarations found. Nothing to do." "INFO"
        exit 0
    }
    
    # Process the content
    $result = Remove-ImportantDeclarations $content
    
    # Add safety overrides
    $finalContent = Add-SafetyOverrides $result.Content
    
    # Validate the result
    if (!(Validate-CSS $finalContent)) {
        Write-Log "CSS validation failed. Review the output carefully." "WARNING"
    }
    
    # Write output
    if ($DryRun) {
        Write-Log "DRY RUN: Would write cleaned file to: $outputPath" "WARNING"
        Write-Host ""
        Write-Host "DRY RUN - Changes Summary" -ForegroundColor Yellow
        Write-Host "=========================" -ForegroundColor Yellow
        Write-Host "Rules modified: $($result.Statistics.ModifiedRules)"
        Write-Host "!important removed: $($result.Statistics.ImportantRemoved)"
        Write-Host "Selectors with increased specificity: $($result.ChangeMap | Where-Object { $_.Original -ne $_.Modified } | Measure-Object).Count"
    } else {
        # Ensure output directory exists
        $outputDir = Split-Path $outputPath
        if (!(Test-Path $outputDir)) {
            New-Item -ItemType Directory -Force -Path $outputDir | Out-Null
        }
        
        # Write the cleaned file
        Set-Content -Path $outputPath -Value $finalContent -Encoding UTF8
        Write-Log "Cleaned file written to: $outputPath" "SUCCESS"
        
        # Get final statistics
        $finalSize = (Get-Item $outputPath).Length
        $finalImportant = ([regex]::Matches($finalContent, '!important')).Count
        
        Write-Host ""
        Write-Host "Final Statistics" -ForegroundColor Green
        Write-Host "================" -ForegroundColor Green
        Write-Host "File size: $([math]::Round($finalSize / 1KB, 2)) KB"
        Write-Host "Size change: $([math]::Round((1 - $finalSize / $originalSize) * 100, 1))%"
        Write-Host "!important remaining: $finalImportant"
        Write-Host "!important removed: $($originalImportant - $finalImportant)"
        Write-Host "Removal rate: $([math]::Round((($originalImportant - $finalImportant) / $originalImportant) * 100, 1))%"
        
        # Generate summary report
        $reportPath = Join-Path (Split-Path $outputPath) "important-removal-report.txt"
        $reportContent = @"
!important Removal Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Input File: $InputFile
Output File: $OutputFile

Statistics:
* Original !important count: $originalImportant
* Final !important count: $finalImportant
* Removed: $($originalImportant - $finalImportant)
* Removal rate: $([math]::Round((($originalImportant - $finalImportant) / $originalImportant) * 100, 1))%

Rules Modified: $($result.Statistics.ModifiedRules)
Selectors Enhanced: $($result.ChangeMap | Where-Object { $_.Original -ne $_.Modified } | Measure-Object).Count

File Size:
* Original: $([math]::Round($originalSize / 1KB, 2)) KB
* Final: $([math]::Round($finalSize / 1KB, 2)) KB
* Reduction: $([math]::Round((1 - $finalSize / $originalSize) * 100, 1))%

Next Steps:
1. Review the cleaned CSS file for visual issues
2. Test on local environment (http://localhost:8080/)
3. Run visual regression tests
4. Update WordPress enqueue to use the new file
"@
        Set-Content -Path $reportPath -Value $reportContent
        
        Write-Host ""
        Write-Host "Report saved to: $reportPath" -ForegroundColor Green
        Write-Host "Mapping saved to: $mappingFile" -ForegroundColor Green
    }
    
    Write-Log "Phase 1 !important Removal Script Completed"
    Write-Log "=" * 60
}

# Run the script
Main