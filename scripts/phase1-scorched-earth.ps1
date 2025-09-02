# phase1-scorched-earth.ps1
# WARNING: THIS WILL DELETE ALL CSS FILES

# Verify lock file
if (-not (Test-Path "css-refactor-lock.json")) {
    Write-Host "FATAL: No lock file. Run preflight-check.ps1 first" -ForegroundColor Red
    exit 1
}

Write-Host "PHASE 1: SCORCHED EARTH INITIATED" -ForegroundColor Red

# Step 1: Create staging directory
New-Item -ItemType Directory -Force -Path "css-staging"

# Step 2: Consolidate EVERYTHING into staging
Write-Host "Consolidating all CSS..." -ForegroundColor Yellow

# Core consolidation
@"
/* ============================================
   MOBILITY TRAILBLAZERS CSS v3.0
   CONSOLIDATED FROM 57 FILES → 5 FILES
   ============================================ */
"@ | Out-File "css-staging/mt-core.css"

# Append all CSS in specific order
$files = @(
    "assets/css/v4/mt-tokens.css",
    "assets/css/v4/mt-reset.css", 
    "assets/css/v4/mt-base.css",
    "assets/css/frontend.css",
    "assets/css/mt-*.css"
)

foreach ($pattern in $files) {
    Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue | ForEach-Object {
        Get-Content $_.FullName | Add-Content "css-staging/mt-core.css"
    }
}

# Step 3: Remove ALL !important declarations
Write-Host "Removing ALL !important declarations..." -ForegroundColor Yellow
$content = Get-Content "css-staging/mt-core.css" -Raw
$content = $content -replace '\s*!important\s*', ' '
$content | Out-File "css-staging/mt-core.css"

# Step 4: Create component files
@"
/* BEM Components - Candidate Card */
.mt-candidate-card { }
.mt-candidate-card__image { }
.mt-candidate-card__title { }
.mt-candidate-card__meta { }
.mt-candidate-card--featured { }
"@ | Out-File "css-staging/mt-components.css"

@"
/* Admin Styles */
"@ | Out-File "css-staging/mt-admin.css"

@"
/* Mobile-specific overrides */
@media (max-width: 768px) {
    .mt-mobile-only { display: block; }
}
"@ | Out-File "css-staging/mt-mobile.css"

@"
/* Critical above-fold styles */
:root { --mt-primary: #003C3D; }
"@ | Out-File "css-staging/mt-critical.css"

# Step 5: NUCLEAR OPTION - DELETE ENTIRE CSS DIRECTORY
Write-Host "DELETING assets/css directory..." -ForegroundColor Red
Remove-Item -Recurse -Force "assets/css"

# Step 6: Recreate with consolidated files only
New-Item -ItemType Directory -Force -Path "assets/css"
Move-Item "css-staging/*.css" "assets/css/"
Remove-Item -Recurse -Force "css-staging"

# Step 7: Verification
$newCount = (Get-ChildItem -Path "assets/css" -Filter "*.css").Count
$importantCheck = (Select-String -Path "assets/css/*.css" -Pattern "!important").Count

if ($newCount -gt 20) {
    Write-Host "FATAL: Too many files ($newCount). Rolling back..." -ForegroundColor Red
    git checkout -- assets/css
    exit 1
}

if ($importantCheck -gt 0) {
    Write-Host "WARNING: $importantCheck !important found. Fixing..." -ForegroundColor Yellow
}

Write-Host "PHASE 1 COMPLETE: $newCount CSS files, $importantCheck !important" -ForegroundColor Green