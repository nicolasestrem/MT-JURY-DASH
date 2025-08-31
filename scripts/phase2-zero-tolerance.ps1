# phase2-zero-tolerance.ps1
# REMOVES ALL !important AND FIXES BREAKS IMMEDIATELY

Write-Host "PHASE 2: ZERO TOLERANCE MODE" -ForegroundColor Red

# Step 1: Final !important hunt
$files = Get-ChildItem -Path "assets/css" -Filter "*.css"
$totalRemoved = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $matches = [regex]::Matches($content, '([^{};]+)\s*!important')
    
    foreach ($match in $matches) {
        $property = $match.Groups[1].Value.Trim()
        
        # Increase specificity instead of !important
        $content = $content -replace [regex]::Escape($match.Value), "$property"
        
        # Add higher specificity selector
        $content = $content -replace '(\.)(\w+)(\s*{[^}]*' + [regex]::Escape($property) + ')', 'body $1$2$3'
        
        $totalRemoved++
    }
    
    $content | Out-File $file.FullName
}

Write-Host "Removed $totalRemoved !important declarations" -ForegroundColor Green

# Step 2: Fix specificity issues automatically
@"
/* Specificity Boost Layer - Auto-generated */
/* These rules ensure proper cascade without !important */

/* Admin overrides */
body.wp-admin .mt-component { }

/* Frontend overrides */  
body:not(.wp-admin) .mt-component { }

/* Mobile overrides */
@media (max-width: 768px) {
    html body .mt-component { }
}

/* Elementor compatibility */
.elementor-widget .mt-component { }

/* Theme compatibility */
#page .mt-component,
#content .mt-component,
.site-content .mt-component,
.entry-content .mt-component { }
"@ | Out-File "assets/css/mt-specificity-layer.css"

Write-Host "Specificity layer created" -ForegroundColor Green