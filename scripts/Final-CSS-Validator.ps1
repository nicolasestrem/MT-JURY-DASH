# CSS Phase 1 Consolidation Safety Validator
# Version: 1.0.0 
# Purpose: Final validation for CSS consolidation safety

param(
    [Parameter(Mandatory = $false)]
    [string]$ProjectRoot = "C:\Users\nicol\Desktop\mobility-trailblazers"
)

Write-Host "CSS PHASE 1 CONSOLIDATION SAFETY VALIDATOR" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

function Test-CSSFileSafety {
    param([string]$FilePath)
    
    $errors = @()
    $warnings = @()
    
    if (!(Test-Path $FilePath)) {
        return @{ 
            Errors = @("File not found: $FilePath")
            Warnings = @()
            HasCSS4Variables = $false
            HasImportantViolations = $true
        }
    }
    
    try {
        $content = Get-Content $FilePath -Raw -Encoding UTF8 -ErrorAction Stop
        $lines = $content -split "`n"
        
        $braceBalance = 0
        $lineNumber = 0
        
        foreach ($line in $lines) {
            $lineNumber++
            $trimmed = $line.Trim()
            
            if ($trimmed -eq '' -or $trimmed.StartsWith('/*') -or $trimmed.StartsWith('//')) {
                continue
            }
            
            # Critical: Check for !important usage
            if ($line.Contains('!important')) {
                $errors += "Line $lineNumber - CRITICAL: CSS v4 framework violation (!important): $trimmed"
            }
            
            # Count braces for balance
            $openBraces = 0
            $closeBraces = 0
            for ($i = 0; $i -lt $line.Length; $i++) {
                if ($line[$i] -eq '{') { $openBraces++ }
                if ($line[$i] -eq '}') { $closeBraces++ }
            }
            $braceBalance += $openBraces - $closeBraces
            
            # Check for missing semicolons
            if ($line.Contains(':') -and !$line.Contains('{') -and !$line.Contains('}') -and !$line.Contains(';') -and $trimmed.Length -gt 3) {
                $warnings += "Line $lineNumber - Possible missing semicolon: $trimmed"
            }
            
            # Check for unbalanced quotes
            $singleQuoteCount = 0
            $doubleQuoteCount = 0
            for ($i = 0; $i -lt $line.Length; $i++) {
                if ($line[$i] -eq "'") { $singleQuoteCount++ }
                if ($line[$i] -eq '"') { $doubleQuoteCount++ }
            }
            
            if ($singleQuoteCount % 2 -ne 0) {
                $errors += "Line $lineNumber - Unbalanced single quotes: $trimmed"
            }
            if ($doubleQuoteCount % 2 -ne 0) {
                $errors += "Line $lineNumber - Unbalanced double quotes: $trimmed"
            }
        }
        
        # Check overall brace balance
        if ($braceBalance -ne 0) {
            $errors += "File has unbalanced braces (difference: $braceBalance)"
        }
        
        return @{
            Errors = $errors
            Warnings = $warnings
            HasCSS4Variables = $content.Contains('--mt-')
            HasImportantViolations = $content.Contains('!important')
        }
        
    } catch {
        return @{
            Errors = @("Failed to read file: $($_.Exception.Message)")
            Warnings = @()
            HasCSS4Variables = $false
            HasImportantViolations = $true
        }
    }
}

function Get-EmergencyFiles {
    param([string]$RootPath)
    
    $cssDirectory = Join-Path $RootPath "assets\css"
    $emergencyFiles = @()
    
    if (Test-Path $cssDirectory) {
        $priorityFiles = @(
            "emergency-fixes.css",
            "mt-hotfixes-consolidated.css", 
            "frontend-critical-fixes.css",
            "candidate-single-hotfix.css",
            "evaluation-fix.css"
        )
        
        foreach ($fileName in $priorityFiles) {
            $filePath = Join-Path $cssDirectory $fileName
            if (Test-Path $filePath) {
                $emergencyFiles += Get-Item $filePath
            }
        }
        
        try {
            $additionalFixFiles = Get-ChildItem $cssDirectory -Filter "*fix*.css" -ErrorAction SilentlyContinue
            foreach ($file in $additionalFixFiles) {
                $alreadyIncluded = $false
                foreach ($existing in $emergencyFiles) {
                    if ($existing.FullName -eq $file.FullName) {
                        $alreadyIncluded = $true
                        break
                    }
                }
                if (-not $alreadyIncluded) {
                    $emergencyFiles += $file
                }
            }
        } catch {
            Write-Host "Warning: Could not scan for additional fix files" -ForegroundColor Yellow
        }
    }
    
    return $emergencyFiles
}

# Main validation execution
try {
    Write-Host "Project Root: $ProjectRoot" -ForegroundColor Gray
    Write-Host "Scanning for emergency CSS files..." -ForegroundColor Yellow
    
    $emergencyFiles = Get-EmergencyFiles $ProjectRoot
    
    if ($emergencyFiles.Count -eq 0) {
        Write-Host "ERROR: No emergency CSS files found!" -ForegroundColor Red
        exit 1
    }
    
    Write-Host "Found $($emergencyFiles.Count) emergency CSS files:" -ForegroundColor Green
    foreach ($file in $emergencyFiles) {
        Write-Host "  - $($file.Name)" -ForegroundColor White
    }
    
    $totalErrors = 0
    $totalWarnings = 0
    $criticalIssues = 0
    $filesWithIssues = 0
    
    foreach ($file in $emergencyFiles) {
        Write-Host "`nValidating: $($file.Name)" -ForegroundColor Yellow
        Write-Host "----------------------------------------" -ForegroundColor Gray
        
        $result = Test-CSSFileSafety $file.FullName
        
        if ($result.Errors.Count -eq 0 -and $result.Warnings.Count -eq 0) {
            Write-Host "PASS - No syntax issues found" -ForegroundColor Green
        } else {
            $filesWithIssues++
            
            if ($result.Errors.Count -gt 0) {
                Write-Host "ERRORS FOUND:" -ForegroundColor Red
                foreach ($error in $result.Errors) {
                    Write-Host "  $error" -ForegroundColor Red
                    $totalErrors++
                    if ($error.Contains('CRITICAL') -or $error.Contains('!important')) {
                        $criticalIssues++
                    }
                }
            }
            
            if ($result.Warnings.Count -gt 0) {
                Write-Host "WARNINGS FOUND:" -ForegroundColor Yellow
                foreach ($warning in $result.Warnings) {
                    Write-Host "  $warning" -ForegroundColor Yellow
                    $totalWarnings++
                }
            }
        }
        
        Write-Host "CSS v4 Framework Compliance:" -ForegroundColor Cyan
        Write-Host "  Uses CSS v4 variables: " -NoNewline
        if ($result.HasCSS4Variables) {
            Write-Host "YES" -ForegroundColor Green
        } else {
            Write-Host "NO" -ForegroundColor Red
        }
        
        Write-Host "  Contains !important violations: " -NoNewline
        if ($result.HasImportantViolations) {
            Write-Host "YES (CRITICAL)" -ForegroundColor Red
        } else {
            Write-Host "NO" -ForegroundColor Green
        }
    }
    
    Write-Host "`n=============================================" -ForegroundColor Cyan
    Write-Host "VALIDATION SUMMARY REPORT" -ForegroundColor Cyan
    Write-Host "=============================================" -ForegroundColor Cyan
    Write-Host "Total files processed: $($emergencyFiles.Count)" -ForegroundColor White
    Write-Host "Files with issues: $filesWithIssues" -ForegroundColor White
    Write-Host "Critical issues (CSS v4 violations): $criticalIssues" -ForegroundColor $(if($criticalIssues -eq 0){"Green"}else{"Red"})
    Write-Host "Total syntax errors: $totalErrors" -ForegroundColor $(if($totalErrors -eq 0){"Green"}else{"Red"})
    Write-Host "Total warnings: $totalWarnings" -ForegroundColor $(if($totalWarnings -eq 0){"Green"}else{"Yellow"})
    
    Write-Host "`nCONSOLIDATION SAFETY ASSESSMENT:" -ForegroundColor Magenta
    if ($criticalIssues -gt 0) {
        Write-Host "STATUS: NOT SAFE FOR CONSOLIDATION" -ForegroundColor Red
        Write-Host "REASON: Critical CSS v4 framework violations detected" -ForegroundColor Red
        Write-Host "ACTION: Remove all !important declarations before consolidation" -ForegroundColor Yellow
        $exitCode = 1
    } elseif ($totalErrors -gt 0) {
        Write-Host "STATUS: PROCEED WITH CAUTION" -ForegroundColor Yellow
        Write-Host "REASON: Syntax errors detected" -ForegroundColor Yellow
        Write-Host "ACTION: Review and fix syntax errors, test thoroughly" -ForegroundColor Yellow
        $exitCode = 2
    } else {
        Write-Host "STATUS: SAFE FOR CSS CONSOLIDATION" -ForegroundColor Green
        Write-Host "RESULT: All emergency files passed validation" -ForegroundColor Green
        Write-Host "ACTION: Ready for Phase 1 consolidation process" -ForegroundColor Green
        $exitCode = 0
    }
    
    Write-Host "=============================================" -ForegroundColor Cyan
    exit $exitCode
    
} catch {
    Write-Host "`nCRITICAL ERROR DURING VALIDATION:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}