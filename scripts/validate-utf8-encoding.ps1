# UTF-8 Encoding Validation Script for Mobility Trailblazers
# This script validates UTF-8 encoding in PHP files and detects common issues
# Author: Mobility Trailblazers Team
# Date: 2025-08-25

param(
    [string]$Path = "",
    [switch]$Detailed = $false
)

$PluginPath = Split-Path $PSScriptRoot -Parent

if ($Path -eq "") {
    $SearchPath = $PluginPath
} else {
    $SearchPath = Join-Path $PluginPath $Path
}

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "UTF-8 Encoding Validation" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Search Path: $SearchPath" -ForegroundColor White
Write-Host ""

# Common UTF-8 encoding issues to detect
$EncodingIssues = @{
    # Double-encoded German characters
    "Ã¤" = "Should be: ä"
    "Ã¶" = "Should be: ö"
    "Ã¼" = "Should be: ü"
    "ÃŸ" = "Should be: ß"
    "Ã„" = "Should be: Ä"
    "Ã–" = "Should be: Ö"
    "Ãœ" = "Should be: Ü"
    
    # Other encoding issues
    "â€™" = "Should be: ' (apostrophe)"
    "â€"" = "Should be: – (en dash)"
    "â€œ" = "Should be: "" (left quote)"
    "â€" = "Should be: "" (right quote)"
    "â€¦" = "Should be: … (ellipsis)"
    
    # Mojibake patterns
    "Ã¢â‚¬" = "Mojibake detected"
    "Ã‚Â" = "Mojibake detected"
    "ï»¿" = "UTF-8 BOM detected"
}

# Get all PHP files
$PhpFiles = Get-ChildItem -Path $SearchPath -Filter "*.php" -Recurse -ErrorAction SilentlyContinue

$TotalFiles = $PhpFiles.Count
$FilesWithIssues = 0
$TotalIssues = 0
$IssueDetails = @()

Write-Host "Scanning $TotalFiles PHP files..." -ForegroundColor Yellow
Write-Host ""

foreach ($File in $PhpFiles) {
    $RelativePath = $File.FullName.Replace($PluginPath + "\", "")
    $FileIssues = @()
    
    try {
        # Read file content
        $Content = Get-Content -Path $File.FullName -Raw -Encoding UTF8
        $Lines = Get-Content -Path $File.FullName -Encoding UTF8
        
        # Check for encoding issues
        foreach ($Issue in $EncodingIssues.Keys) {
            if ($Content -match [regex]::Escape($Issue)) {
                $Matches = ([regex]::Escape($Issue)).Matches($Content)
                $Count = ($Content | Select-String -Pattern ([regex]::Escape($Issue)) -AllMatches).Matches.Count
                
                # Find line numbers
                $LineNumbers = @()
                for ($i = 0; $i -lt $Lines.Count; $i++) {
                    if ($Lines[$i] -match [regex]::Escape($Issue)) {
                        $LineNumbers += ($i + 1)
                    }
                }
                
                $FileIssues += @{
                    Pattern = $Issue
                    Description = $EncodingIssues[$Issue]
                    Count = $Count
                    Lines = $LineNumbers
                }
                
                $TotalIssues += $Count
            }
        }
        
        # Check for proper UTF-8 declaration in HTML output
        if ($Content -match '<meta[^>]*charset[^>]*>') {
            $CharsetMatch = [regex]::Match($Content, 'charset\s*=\s*["\']?([^"\'>\\s]+)')
            if ($CharsetMatch.Success) {
                $Charset = $CharsetMatch.Groups[1].Value
                if ($Charset -ne "UTF-8" -and $Charset -ne "utf-8") {
                    $FileIssues += @{
                        Pattern = "charset=$Charset"
                        Description = "Should be: charset=UTF-8"
                        Count = 1
                        Lines = @()
                    }
                }
            }
        }
        
        if ($FileIssues.Count -gt 0) {
            $FilesWithIssues++
            
            Write-Host "❌ $RelativePath" -ForegroundColor Red
            
            if ($Detailed) {
                foreach ($Issue in $FileIssues) {
                    Write-Host "   - $($Issue.Pattern): $($Issue.Description) ($($Issue.Count) occurrences)" -ForegroundColor Yellow
                    if ($Issue.Lines.Count -gt 0 -and $Issue.Lines.Count -le 5) {
                        Write-Host "     Lines: $($Issue.Lines -join ', ')" -ForegroundColor DarkGray
                    } elseif ($Issue.Lines.Count -gt 5) {
                        $FirstFive = $Issue.Lines[0..4] -join ', '
                        Write-Host "     Lines: $FirstFive, ... (and $($Issue.Lines.Count - 5) more)" -ForegroundColor DarkGray
                    }
                }
            }
            
            $IssueDetails += @{
                File = $RelativePath
                Issues = $FileIssues
            }
        }
        
    } catch {
        Write-Host "⚠️  Error reading $RelativePath : $_" -ForegroundColor Yellow
    }
}

# Check for proper German translations
Write-Host ""
Write-Host "Checking German Translation Patterns..." -ForegroundColor Cyan

$TranslationPatterns = @{
    '\bDu\b|\bdir\b|\bdein\b|\bdeine\b|\bdeinem\b|\bdeinen\b|\bdeiner\b|\bdeines\b' = "Informal 'Du' form detected (should use formal 'Sie')"
    'ä|ö|ü|ß|Ä|Ö|Ü' = "German characters present (good)"
}

$GoodPractices = 0
$BadPractices = 0

foreach ($File in $PhpFiles) {
    $RelativePath = $File.FullName.Replace($PluginPath + "\", "")
    
    # Skip non-template files for translation checks
    if ($RelativePath -notmatch "templates\\|includes\\public\\|includes\\frontend\\") {
        continue
    }
    
    try {
        $Content = Get-Content -Path $File.FullName -Raw -Encoding UTF8
        
        # Check for informal German
        if ($Content -match $TranslationPatterns.Keys[0]) {
            if ($Content -match '__\([^)]*\bDu\b|\bdir\b|\bdein') {
                Write-Host "⚠️  Informal German in: $RelativePath" -ForegroundColor Yellow
                $BadPractices++
            }
        }
        
        # Check for proper use of translation functions
        if ($Content -match '>[^<]*[äöüßÄÖÜ][^<]*<' -and $Content -notmatch '__\([^)]*[äöüßÄÖÜ]') {
            # German text outside translation functions
            if ($Detailed) {
                Write-Host "⚠️  Hardcoded German text in: $RelativePath" -ForegroundColor Yellow
            }
        }
        
    } catch {
        # Silent continue
    }
}

# Summary Report
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Validation Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Total files scanned: $TotalFiles" -ForegroundColor White
Write-Host "Files with issues: $FilesWithIssues" -ForegroundColor $(if ($FilesWithIssues -gt 0) { 'Red' } else { 'Green' })
Write-Host "Total encoding issues: $TotalIssues" -ForegroundColor $(if ($TotalIssues -gt 0) { 'Red' } else { 'Green' })

if ($FilesWithIssues -gt 0) {
    Write-Host ""
    Write-Host "Files requiring attention:" -ForegroundColor Yellow
    foreach ($Detail in $IssueDetails) {
        Write-Host "  - $($Detail.File)" -ForegroundColor White
    }
    
    Write-Host ""
    Write-Host "Run '.\scripts\fix-utf8-encoding.ps1' to fix these issues" -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "✅ No encoding issues detected!" -ForegroundColor Green
}

if (-not $Detailed -and $FilesWithIssues -gt 0) {
    Write-Host ""
    Write-Host "Run with -Detailed flag for more information" -ForegroundColor DarkGray
}

Write-Host ""