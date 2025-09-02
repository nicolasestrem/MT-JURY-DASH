# PowerShell CSS Syntax Validator for Emergency Files
# Version: 1.0.0
# Date: 2025-08-26
# Purpose: Specialized CSS syntax validation for emergency/hotfix files

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [string]$CSSFile,
    
    [Parameter(Mandatory = $false)]
    [string]$ProjectRoot = "C:\Users\nicol\Desktop\mobility-trailblazers",
    
    [Parameter(Mandatory = $false)]
    [switch]$CheckAllEmergencyFiles = $false,
    
    [Parameter(Mandatory = $false)]
    [switch]$FixCommonErrors = $false,
    
    [Parameter(Mandatory = $false)]
    [switch]$Verbose = $false
)

# ===================================
# ADVANCED CSS SYNTAX VALIDATION
# ===================================

class CSSValidationError {
    [int]$LineNumber
    [string]$ErrorType
    [string]$Message
    [string]$Severity
    [string]$Context
    [string]$SuggestedFix
    
    CSSValidationError([int]$line, [string]$type, [string]$message, [string]$severity, [string]$context, [string]$fix) {
        $this.LineNumber = $line
        $this.ErrorType = $type
        $this.Message = $message
        $this.Severity = $severity
        $this.Context = $context
        $this.SuggestedFix = $fix
    }
}

function Test-CSSAdvanced {
    param(
        [Parameter(Mandatory = $true)]
        [string]$FilePath
    )
    
    Write-Host "🔍 Advanced CSS Validation: $FilePath" -ForegroundColor Cyan
    
    if (!(Test-Path $FilePath)) {
        return @([CSSValidationError]::new(0, "FileNotFound", "File not found: $FilePath", "CRITICAL", "", "Ensure file path is correct"))
    }
    
    $errors = @()
    $content = Get-Content $FilePath -Raw -Encoding UTF8
    $lines = $content -split "`r?`n"
    
    # Validation state tracking
    $braceStack = @()
    $inComment = $false
    $inRuleSet = $false
    $currentSelector = ""
    $ruleSetStartLine = 0
    
    # CSS v4 framework specific checks
    $hasCSS4Variables = $false
    $usesBEMNaming = $false
    $violatesV4Framework = $false
    
    for ($lineNum = 1; $lineNum -le $lines.Count; $lineNum++) {
        $line = $lines[$lineNum - 1]
        $trimmed = $line.Trim()
        $originalLine = $line
        
        # Skip empty lines
        if ($trimmed -eq '') { continue }
        
        # ===================================
        # COMMENT HANDLING
        # ===================================
        
        # Multi-line comment start
        if ($trimmed -match '/\*' -and !$inComment) {
            $inComment = $true
        }
        
        # Multi-line comment end
        if ($trimmed -match '\*/' -and $inComment) {
            $inComment = $false
            continue
        }
        
        # Skip lines inside comments
        if ($inComment) { continue }
        
        # Single-line comments
        if ($trimmed.StartsWith('//')) { continue }
        
        # ===================================
        # CRITICAL CSS V4 FRAMEWORK CHECKS
        # ===================================
        
        # Check for !important usage (CRITICAL violation)
        if ($line -match '!important') {
            $errors += [CSSValidationError]::new(
                $lineNum, 
                "CSS_V4_VIOLATION", 
                "!important usage violates CSS v4 framework", 
                "CRITICAL", 
                $trimmed,
                "Remove !important and use proper specificity"
            )
            $violatesV4Framework = $true
        }
        
        # Check for CSS v4 design tokens
        if ($line -match '--mt-[\w-]+:') {
            $hasCSS4Variables = $true
        }
        
        # Check for BEM methodology
        if ($line -match '\.mt-[\w-]+(__[\w-]+)?(--[\w-]+)?') {
            $usesBEMNaming = $true
        }
        
        # ===================================
        # BRACE BALANCE TRACKING
        # ===================================
        
        $openBraces = ($line -split '\{' | Measure-Object).Count - 1
        $closeBraces = ($line -split '\}' | Measure-Object).Count - 1
        
        for ($i = 0; $i -lt $openBraces; $i++) {
            $braceStack += $lineNum
        }
        
        for ($i = 0; $i -lt $closeBraces; $i++) {
            if ($braceStack.Count -eq 0) {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SYNTAX_ERROR", 
                    "Unexpected closing brace", 
                    "ERROR", 
                    $trimmed,
                    "Remove extra closing brace or add missing opening brace"
                )
            } else {
                $braceStack = $braceStack[0..($braceStack.Count - 2)]
                $inRuleSet = $braceStack.Count -gt 0
            }
        }
        
        # ===================================
        # SELECTOR VALIDATION
        # ===================================
        
        if ($line -match '\{' -and !$inRuleSet) {
            $selectorPart = ($line -split '\{')[0].Trim()
            $currentSelector = $selectorPart
            $ruleSetStartLine = $lineNum
            $inRuleSet = $true
            
            # Validate selector syntax
            if ($selectorPart -match '^[0-9]') {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SELECTOR_ERROR", 
                    "CSS selector cannot start with a number", 
                    "ERROR", 
                    $selectorPart,
                    "Add class prefix or use attribute selector"
                )
            }
            
            # Check for invalid characters in selector
            if ($selectorPart -match '[^a-zA-Z0-9\s\-_:.,>#~+\[\]="\''()@]') {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SELECTOR_ERROR", 
                    "Invalid characters in CSS selector", 
                    "ERROR", 
                    $selectorPart,
                    "Remove invalid characters from selector"
                )
            }
            
            # Check for too specific selectors (more than 3 levels)
            $selectorDepth = ($selectorPart -split '\s+' | Where-Object { $_ -ne '' }).Count
            if ($selectorDepth -gt 4) {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SPECIFICITY_WARNING", 
                    "Selector is too specific (depth: $selectorDepth)", 
                    "WARNING", 
                    $selectorPart,
                    "Consider using BEM methodology to reduce specificity"
                )
            }
        }
        
        # ===================================
        # PROPERTY VALIDATION
        # ===================================
        
        if ($inRuleSet -and $line -match '^\s*([^:{}]+):\s*([^;{}]+);?\s*$') {
            $property = $matches[1].Trim()
            $value = $matches[2].Trim()
            
            # Check for missing semicolon
            if (!($line -match ';') -and $trimmed -ne '' -and !($line -match '\}')) {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SYNTAX_ERROR", 
                    "Missing semicolon at end of CSS property", 
                    "ERROR", 
                    "$property: $value",
                    "Add semicolon at end: $property: $value;"
                )
            }
            
            # Validate color values
            if ($property -match 'color|background' -and $value -match '#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})') {
                # Valid hex color
            } elseif ($property -match 'color|background' -and $value -match '#[^0-9A-Fa-f\s]') {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "VALUE_ERROR", 
                    "Invalid hex color format", 
                    "ERROR", 
                    $value,
                    "Use valid hex format like #FFFFFF or named colors"
                )
            }
            
            # Check for unknown CSS properties
            $knownProperties = @(
                'display', 'position', 'top', 'right', 'bottom', 'left', 'width', 'height', 'margin', 'padding',
                'border', 'background', 'color', 'font', 'text', 'line-height', 'letter-spacing', 'word-spacing',
                'vertical-align', 'text-align', 'text-decoration', 'text-transform', 'white-space', 'overflow',
                'visibility', 'opacity', 'z-index', 'float', 'clear', 'flex', 'grid', 'animation', 'transition',
                'transform', 'box-shadow', 'border-radius', 'max-width', 'min-width', 'max-height', 'min-height'
            )
            
            $isKnownProperty = $false
            foreach ($knownProp in $knownProperties) {
                if ($property -match "^$knownProp") {
                    $isKnownProperty = $true
                    break
                }
            }
            
            # Check for vendor prefixes
            if ($property.StartsWith('-webkit-') -or $property.StartsWith('-moz-') -or $property.StartsWith('-ms-') -or $property.StartsWith('-o-')) {
                $isKnownProperty = $true
            }
            
            if (!$isKnownProperty -and $property -notmatch '^--' -and $property.Length -gt 3) {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "PROPERTY_WARNING", 
                    "Potentially unknown CSS property: $property", 
                    "WARNING", 
                    "$property: $value",
                    "Verify property name is correct"
                )
            }
            
            # Check for quote balance
            $singleQuotes = ($value -split "'" | Measure-Object).Count - 1
            $doubleQuotes = ($value -split '"' | Measure-Object).Count - 1
            
            if ($singleQuotes % 2 -ne 0) {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SYNTAX_ERROR", 
                    "Unbalanced single quotes in value", 
                    "ERROR", 
                    $value,
                    "Ensure all single quotes are properly closed"
                )
            }
            
            if ($doubleQuotes % 2 -ne 0) {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "SYNTAX_ERROR", 
                    "Unbalanced double quotes in value", 
                    "ERROR", 
                    $value,
                    "Ensure all double quotes are properly closed"
                )
            }
        }
        
        # ===================================
        # EMERGENCY FILE SPECIFIC CHECKS
        # ===================================
        
        $fileName = Split-Path $FilePath -Leaf
        if ($fileName -match 'emergency|hotfix|fix|critical') {
            
            # Check for overly complex emergency fixes
            if ($line -match 'body\.[\w-]+\.[\w-]+\.[\w-]+') {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "EMERGENCY_WARNING", 
                    "Emergency fix uses very specific selector - consider refactoring", 
                    "WARNING", 
                    $trimmed,
                    "Consider using a single specific class instead"
                )
            }
            
            # Check for position: absolute overrides (risky in emergency fixes)
            if ($line -match 'position\s*:\s*absolute') {
                $errors += [CSSValidationError]::new(
                    $lineNum, 
                    "EMERGENCY_WARNING", 
                    "position: absolute in emergency fix may cause layout issues", 
                    "WARNING", 
                    $trimmed,
                    "Test thoroughly across different screen sizes"
                )
            }
        }
    }
    
    # ===================================
    # FINAL STRUCTURAL CHECKS
    # ===================================
    
    # Check for unbalanced braces at end
    if ($braceStack.Count -gt 0) {
        foreach ($unclosedLine in $braceStack) {
            $errors += [CSSValidationError]::new(
                $unclosedLine, 
                "SYNTAX_ERROR", 
                "Unclosed CSS rule set", 
                "ERROR", 
                "Rule set opened here",
                "Add closing brace }"
            )
        }
    }
    
    # ===================================
    # CSS V4 FRAMEWORK COMPLIANCE REPORT
    # ===================================
    
    Write-Host "`n📊 CSS v4 Framework Compliance Analysis:" -ForegroundColor Yellow
    Write-Host "  ✅ Uses CSS v4 variables: " -NoNewline; Write-Host $hasCSS4Variables -ForegroundColor $(if($hasCSS4Variables){"Green"}else{"Red"})
    Write-Host "  ✅ Uses BEM naming: " -NoNewline; Write-Host $usesBEMNaming -ForegroundColor $(if($usesBEMNaming){"Green"}else{"Red"})
    Write-Host "  ❌ Violates v4 framework: " -NoNewline; Write-Host $violatesV4Framework -ForegroundColor $(if(!$violatesV4Framework){"Green"}else{"Red"})
    
    return $errors
}

# ===================================
# ERROR REPORTING AND FIXING
# ===================================

function Show-ValidationResults {
    param([array]$Errors, [string]$FilePath)
    
    $criticalCount = ($Errors | Where-Object { $_.Severity -eq "CRITICAL" }).Count
    $errorCount = ($Errors | Where-Object { $_.Severity -eq "ERROR" }).Count
    $warningCount = ($Errors | Where-Object { $_.Severity -eq "WARNING" }).Count
    
    Write-Host "`n📋 VALIDATION RESULTS for $(Split-Path $FilePath -Leaf)" -ForegroundColor Cyan
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "🔥 Critical Issues: $criticalCount" -ForegroundColor Red
    Write-Host "❌ Errors: $errorCount" -ForegroundColor Red
    Write-Host "⚠️  Warnings: $warningCount" -ForegroundColor Yellow
    Write-Host "📁 File: $FilePath" -ForegroundColor Gray
    
    if ($Errors.Count -eq 0) {
        Write-Host "`n✅ ALL CHECKS PASSED! File is syntactically valid." -ForegroundColor Green
        return
    }
    
    # Group errors by severity
    $criticalErrors = $Errors | Where-Object { $_.Severity -eq "CRITICAL" }
    $regularErrors = $Errors | Where-Object { $_.Severity -eq "ERROR" }
    $warnings = $Errors | Where-Object { $_.Severity -eq "WARNING" }
    
    # Show critical errors first
    if ($criticalErrors.Count -gt 0) {
        Write-Host "`n🔥 CRITICAL ISSUES (MUST FIX BEFORE CONSOLIDATION):" -ForegroundColor Red
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Red
        foreach ($error in $criticalErrors) {
            Write-Host "Line $($error.LineNumber): " -NoNewline -ForegroundColor Red
            Write-Host $error.Message -ForegroundColor Red
            Write-Host "  Context: " -NoNewline -ForegroundColor Gray
            Write-Host $error.Context -ForegroundColor White
            Write-Host "  Fix: " -NoNewline -ForegroundColor Green
            Write-Host $error.SuggestedFix -ForegroundColor Green
            Write-Host ""
        }
    }
    
    # Show regular errors
    if ($regularErrors.Count -gt 0) {
        Write-Host "❌ SYNTAX ERRORS:" -ForegroundColor Red
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Red
        foreach ($error in $regularErrors) {
            Write-Host "Line $($error.LineNumber): " -NoNewline -ForegroundColor Red
            Write-Host $error.Message -ForegroundColor Red
            Write-Host "  Context: " -NoNewline -ForegroundColor Gray
            Write-Host $error.Context -ForegroundColor White
            Write-Host "  Fix: " -NoNewline -ForegroundColor Green
            Write-Host $error.SuggestedFix -ForegroundColor Green
            Write-Host ""
        }
    }
    
    # Show warnings
    if ($warnings.Count -gt 0) {
        Write-Host "⚠️  WARNINGS:" -ForegroundColor Yellow
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Yellow
        foreach ($warning in $warnings) {
            Write-Host "Line $($warning.LineNumber): " -NoNewline -ForegroundColor Yellow
            Write-Host $warning.Message -ForegroundColor Yellow
            Write-Host "  Context: " -NoNewline -ForegroundColor Gray
            Write-Host $warning.Context -ForegroundColor White
            Write-Host "  Suggestion: " -NoNewline -ForegroundColor Green
            Write-Host $warning.SuggestedFix -ForegroundColor Green
            Write-Host ""
        }
    }
    
    # Summary recommendation
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
    if ($criticalCount -gt 0 -or $errorCount -gt 0) {
        Write-Host "❌ RECOMMENDATION: Fix errors before proceeding with CSS consolidation" -ForegroundColor Red
    } else {
        Write-Host "✅ RECOMMENDATION: File is safe for CSS consolidation" -ForegroundColor Green
    }
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
}

function Get-EmergencyCSSFiles {
    param([string]$ProjectRoot)
    
    $emergencyFiles = @()
    $cssDir = "$ProjectRoot\assets\css"
    
    if (!(Test-Path $cssDir)) {
        Write-Host "❌ CSS directory not found: $cssDir" -ForegroundColor Red
        return $emergencyFiles
    }
    
    $patterns = @("*emergency*", "*hotfix*", "*fix*", "*critical*")
    
    foreach ($pattern in $patterns) {
        $files = Get-ChildItem $cssDir -Filter "$pattern.css" -ErrorAction SilentlyContinue
        $emergencyFiles += $files
    }
    
    return $emergencyFiles
}

# ===================================
# MAIN EXECUTION
# ===================================

Write-Host "🚀 CSS SYNTAX VALIDATOR FOR MOBILITY TRAILBLAZERS" -ForegroundColor Cyan
Write-Host "Version: 1.0.0 | Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
Write-Host "══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

try {
    if ($CheckAllEmergencyFiles) {
        Write-Host "🔍 Scanning for emergency CSS files..." -ForegroundColor Yellow
        $emergencyFiles = Get-EmergencyCSSFiles $ProjectRoot
        
        if ($emergencyFiles.Count -eq 0) {
            Write-Host "ℹ️  No emergency CSS files found" -ForegroundColor Yellow
            exit 0
        }
        
        Write-Host "📁 Found $($emergencyFiles.Count) emergency files:" -ForegroundColor Green
        foreach ($file in $emergencyFiles) {
            Write-Host "  - $($file.Name)" -ForegroundColor White
        }
        
        $totalErrors = 0
        $totalCritical = 0
        
        foreach ($file in $emergencyFiles) {
            Write-Host "`n" + ("="*80) -ForegroundColor Cyan
            $errors = Test-CSSAdvanced $file.FullName
            Show-ValidationResults $errors $file.FullName
            
            $totalErrors += $errors.Count
            $totalCritical += ($errors | Where-Object { $_.Severity -eq "CRITICAL" }).Count
        }
        
        Write-Host "`n🏁 FINAL SUMMARY" -ForegroundColor Cyan
        Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
        Write-Host "📁 Files processed: $($emergencyFiles.Count)" -ForegroundColor White
        Write-Host "❌ Total issues: $totalErrors" -ForegroundColor $(if($totalErrors -eq 0){"Green"}else{"Red"})
        Write-Host "🔥 Critical issues: $totalCritical" -ForegroundColor $(if($totalCritical -eq 0){"Green"}else{"Red"})
        
        if ($totalCritical -gt 0) {
            Write-Host "`n❌ CRITICAL ISSUES DETECTED - CSS consolidation not recommended" -ForegroundColor Red
            exit 1
        } elseif ($totalErrors -gt 0) {
            Write-Host "`n⚠️  ISSUES DETECTED - Review warnings before consolidation" -ForegroundColor Yellow
            exit 2
        } else {
            Write-Host "`n✅ ALL EMERGENCY FILES PASSED VALIDATION - Safe for consolidation" -ForegroundColor Green
            exit 0
        }
    }
    
    if ($CSSFile) {
        if (!(Test-Path $CSSFile)) {
            Write-Host "❌ File not found: $CSSFile" -ForegroundColor Red
            exit 1
        }
        
        $errors = Test-CSSAdvanced $CSSFile
        Show-ValidationResults $errors $CSSFile
        
        $criticalCount = ($errors | Where-Object { $_.Severity -eq "CRITICAL" }).Count
        if ($criticalCount -gt 0) {
            exit 1
        } else {
            exit 0
        }
    } else {
        Write-Host "❌ Please specify a CSS file or use -CheckAllEmergencyFiles" -ForegroundColor Red
        Write-Host "Examples:" -ForegroundColor Yellow
        Write-Host "  .\Test-CSS-Syntax.ps1 -CSSFile 'C:\path\to\file.css'" -ForegroundColor White
        Write-Host "  .\Test-CSS-Syntax.ps1 -CheckAllEmergencyFiles" -ForegroundColor White
        exit 1
    }
    
} catch {
    Write-Host "`n💥 CRITICAL ERROR OCCURRED:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host "`nStack Trace:" -ForegroundColor Gray
    Write-Host $_.ScriptStackTrace -ForegroundColor Gray
    exit 1
}