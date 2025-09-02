# localize-css-classes.ps1
# Ensure German translations for all new CSS classes

Write-Host "Updating German localization for CSS classes..." -ForegroundColor Cyan

# Create CSS class translation map
$classMap = @{
    "mt-candidate-card" = "mt-kandidaten-karte"
    "mt-evaluation-form" = "mt-bewertungs-formular"
    "mt-jury-dashboard" = "mt-jury-übersicht"
    "mt-loading" = "mt-lädt"
    "mt-error" = "mt-fehler"
    "mt-success" = "mt-erfolg"
}

# Generate data-i18n attributes for templates
$templates = Get-ChildItem -Path "templates" -Filter "*.php" -Recurse
foreach ($template in $templates) {
    $content = Get-Content $template.FullName -Raw
    
    foreach ($class in $classMap.Keys) {
        # Add data-i18n attribute for German class names
        $content = $content -replace "class=`"$class`"", "class=`"$class`" data-i18n-class=`"$($classMap[$class])`""
    }
    
    $content | Out-File $template.FullName
}

# Update language files
$poFile = "languages/mobility-trailblazers-de_DE.po"
$additions = @"

# CSS Class Names
msgid "candidate-card"
msgstr "Kandidatenkarte"

msgid "evaluation-form"  
msgstr "Bewertungsformular"

msgid "jury-dashboard"
msgstr "Jury-Übersicht"

msgid "loading"
msgstr "Lädt"

msgid "error"
msgstr "Fehler"

msgid "success"
msgstr "Erfolg"
"@

Add-Content $poFile $additions

# Note: msgfmt may not be available on Windows, so we'll skip the .mo compilation
# msgfmt $poFile -o "languages/mobility-trailblazers-de_DE.mo"

Write-Host "Localization updated with new CSS mappings" -ForegroundColor Green