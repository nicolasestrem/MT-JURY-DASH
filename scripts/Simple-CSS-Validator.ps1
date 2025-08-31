# Simple CSS Validator for Emergency Files
# Version: 1.0.0
# Purpose: Basic CSS syntax validation avoiding complex regex

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [string]$ProjectRoot = "C:\Users\nicol\Desktop\mobility-trailblazers"
)

Write-Host "🚀 SIMPLE CSS VALIDATOR FOR MOBILITY TRAILBLAZERS" -ForegroundColor Cyan
Write-Host "══════════════════════════════════════════════════════════════" -ForegroundColor Cyan

function Test-BasicCSSSyntax {
    param([string]$FilePath)
    
    $errors = @()
    $warnings = @()
    
    if (!(Test-Path $FilePath)) {
        return @{ Errors = @("File not found: $FilePath"); Warnings = @() }
    }
    
    $content = Get-Content $FilePath -Raw -Encoding UTF8
    $lines = $content -split "`r?`n"
    
    $braceBalance = 0
    $lineNum = 0
    
    foreach ($line in $lines) {
        $lineNum++
        $trimmed = $line.Trim()
        
        # Skip empty lines and comments
        if ($trimmed -eq '' -or $trimmed.StartsWith('/*') -or $trimmed.StartsWith('//')) {
            continue
        }
        
        # Check for !important (critical violation)
        if ($line.Contains('!important')) {
            $errors += "Line $lineNum`: CRITICAL - !important violates CSS v4 framework: $trimmed"
        }
        
        # Count braces
        $openBraces = [regex]::Matches($line, '\{').Count
        $closeBraces = [regex]::Matches($line, '\}').Count
        $braceBalance += $openBraces - $closeBraces
        
        # Check for missing semicolons (simple check)
        if ($line.Contains(':') -and !$line.Contains('{') -and !$line.Contains('}') -and !$line.Contains(';') -and $trimmed.Length -gt 0) {
            $warnings += "Line $lineNum`: Possible missing semicolon: $trimmed"
        }
        
        # Check for unclosed quotes (simple check)
        $singleQuotes = [regex]::Matches($line, "'").Count
        $doubleQuotes = [regex]::Matches($line, '"').Count
        
        if ($singleQuotes % 2 -ne 0) {
            $errors += "Line $lineNum`: Unbalanced single quotes: $trimmed"
        }
        
        if ($doubleQuotes % 2 -ne 0) {
            $errors += "Line $lineNum`: Unbalanced double quotes: $trimmed"
        }
    }
    
    # Check brace balance
    if ($braceBalance -ne 0) {
        $errors += "Unbalanced braces in file (difference: $braceBalance)"
    }
    
    return @{ 
        Errors = $errors
        Warnings = $warnings
        HasCSS4Variables = $content.Contains('--mt-')
        HasImportantViolations = $content.Contains('!important')
    }
}

function Get-EmergencyFiles {
    param([string]$RootPath)
    
    $cssDir = "$RootPath\assets\css"
    $emergencyFiles = @()
    
    if (Test-Path $cssDir) {
        # Get emergency files
        $patterns = @("emergency-fixes.css", "mt-hotfixes-consolidated.css", "frontend-critical-fixes.css", "candidate-single-hotfix.css", "evaluation-fix.css")
        
        foreach ($pattern in $patterns) {
            $filePath = Join-Path $cssDir $pattern
            if (Test-Path $filePath) {
                $emergencyFiles += Get-Item $filePath
            }
        }
        
        # Look for other fix files
        $fixFiles = Get-ChildItem $cssDir -Filter "*fix*.css" -ErrorAction SilentlyContinue
        foreach ($file in $fixFiles) {
            if ($file.FullName -notin $emergencyFiles.FullName) {
                $emergencyFiles += $file
            }
        }
    }
    
    return $emergencyFiles
}

# Main execution
try {
    $emergencyFiles = Get-EmergencyFiles $ProjectRoot
    
    Write-Host "📁 Found $($emergencyFiles.Count) emergency/fix CSS files" -ForegroundColor Green
    
    $totalErrors = 0
    $totalWarnings = 0
    $criticalIssues = 0
    
    foreach ($file in $emergencyFiles) {
        Write-Host "`n🔍 Validating: $($file.Name)" -ForegroundColor Yellow
        Write-Host "─────────────────────────────────────────────" -ForegroundColor Gray
        
        $result = Test-BasicCSSSyntax $file.FullName
        
        if ($result.Errors.Count -eq 0 -and $result.Warnings.Count -eq 0) {
            Write-Host "✅ No issues found" -ForegroundColor Green
        } else {
            foreach ($error in $result.Errors) {
                Write-Host "❌ $error" -ForegroundColor Red
                $totalErrors++
                if ($error.Contains('CRITICAL') -or $error.Contains('!important')) {
                    $criticalIssues++
                }
            }
            
            foreach ($warning in $result.Warnings) {
                Write-Host "⚠️  $warning" -ForegroundColor Yellow
                $totalWarnings++
            }
        }
        
        # Framework compliance check
        Write-Host "📊 CSS v4 Framework:" -ForegroundColor Cyan
        Write-Host "  Variables: " -NoNewline
        Write-Host $result.HasCSS4Variables -ForegroundColor $(if($result.HasCSS4Variables){"Green"}else{"Red"})
        Write-Host "  !important: " -NoNewline
        Write-Host $result.HasImportantViolations -ForegroundColor $(if(!$result.HasImportantViolations){"Green"}else{"Red"})
    }
    
    # Summary
    Write-Host "`n══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "🏁 VALIDATION SUMMARY" -ForegroundColor Cyan
    Write-Host "══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "📁 Files processed: $($emergencyFiles.Count)" -ForegroundColor White
    Write-Host "🔥 Critical issues: $criticalIssues" -ForegroundColor $(if($criticalIssues -eq 0){"Green"}else{"Red"})
    Write-Host "❌ Total errors: $totalErrors" -ForegroundColor $(if($totalErrors -eq 0){"Green"}else{"Red"})
    Write-Host "⚠️  Total warnings: $totalWarnings" -ForegroundColor $(if($totalWarnings -eq 0){"Green"}else{"Yellow"})
    
    if ($criticalIssues -gt 0) {
        Write-Host "`n❌ CRITICAL ISSUES DETECTED" -ForegroundColor Red
        Write-Host "CSS consolidation NOT RECOMMENDED until critical issues are fixed" -ForegroundColor Red
        exit 1
    } elseif ($totalErrors -gt 0) {
        Write-Host "`n⚠️  ERRORS DETECTED" -ForegroundColor Yellow
        Write-Host "Review and fix errors before consolidation" -ForegroundColor Yellow
        exit 2
    } else {
        Write-Host "`n✅ ALL VALIDATIONS PASSED" -ForegroundColor Green
        Write-Host "Emergency files are safe for CSS consolidation" -ForegroundColor Green
        exit 0
    }
    
} catch {
    Write-Host "`n💥 ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}