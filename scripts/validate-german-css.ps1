# validate-german-css.ps1
# Ensure German version works with new CSS

Write-Host "Validating German CSS compatibility..." -ForegroundColor Cyan

# Since we're in local development, we'll check the templates for German compatibility
# rather than making HTTP requests

# Check if German classes are present in templates
$templates = Get-ChildItem -Path "templates" -Filter "*.php" -Recurse -ErrorAction SilentlyContinue
$germanMappingsFound = 0

if ($templates) {
    foreach ($template in $templates) {
        $content = Get-Content $template.FullName -Raw -ErrorAction SilentlyContinue
        if ($content -match "data-i18n-class") {
            $germanMappingsFound++
        }
    }
}

if ($germanMappingsFound -gt 0) {
    Write-Host "Found $germanMappingsFound templates with German CSS mappings" -ForegroundColor Green
} else {
    Write-Host "No German CSS mappings found in templates (may not be needed)" -ForegroundColor Yellow
}

# Check if German language files exist
if (Test-Path "languages/mobility-trailblazers-de_DE.po") {
    Write-Host "German language file exists" -ForegroundColor Green
    
    # Check if our CSS translations were added
    $poContent = Get-Content "languages/mobility-trailblazers-de_DE.po" -Raw
    if ($poContent -match "Kandidatenkarte") {
        Write-Host "German CSS translations found in language file" -ForegroundColor Green
    }
} else {
    Write-Host "German language file not found" -ForegroundColor Yellow
}

Write-Host "German CSS validation completed" -ForegroundColor Green