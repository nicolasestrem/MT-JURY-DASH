# Test Consolidation Script for Mobility Trailblazers
# This script safely consolidates test files from /dev/tests to /doc/tests

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Mobility Trailblazers Test Consolidation" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Define paths
$rootPath = Split-Path -Parent $PSScriptRoot
$devTestsPath = Join-Path $rootPath "dev\tests"
$docTestsPath = Join-Path $rootPath "doc\tests"
$backupPath = Join-Path $rootPath "test-backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

# Function to compare files
function Compare-TestFiles {
    param (
        [string]$File1,
        [string]$File2
    )
    
    if (-not (Test-Path $File1) -or -not (Test-Path $File2)) {
        return $false
    }
    
    $hash1 = Get-FileHash -Path $File1 -Algorithm MD5
    $hash2 = Get-FileHash -Path $File2 -Algorithm MD5
    
    return $hash1.Hash -eq $hash2.Hash
}

# Check if directories exist
if (-not (Test-Path $devTestsPath)) {
    Write-Host "ERROR: /dev/tests directory not found!" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $docTestsPath)) {
    Write-Host "ERROR: /doc/tests directory not found!" -ForegroundColor Red
    exit 1
}

Write-Host "Step 1: Creating backup of existing test directories..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
New-Item -ItemType Directory -Path "$backupPath\dev-tests" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupPath\doc-tests" -Force | Out-Null

Copy-Item -Path "$devTestsPath\*" -Destination "$backupPath\dev-tests" -Recurse -Force
Copy-Item -Path "$docTestsPath\*" -Destination "$backupPath\doc-tests" -Recurse -Force
Write-Host "✓ Backup created at: $backupPath" -ForegroundColor Green

Write-Host ""
Write-Host "Step 2: Analyzing test files..." -ForegroundColor Yellow

# Get all test files from both directories
$devFiles = Get-ChildItem -Path $devTestsPath -Filter "*.spec.ts" -Recurse
$docFiles = Get-ChildItem -Path $docTestsPath -Filter "*.spec.ts" -Recurse

$devFileNames = $devFiles | ForEach-Object { $_.Name }
$docFileNames = $docFiles | ForEach-Object { $_.Name }

# Find unique files in dev/tests
$uniqueInDev = $devFileNames | Where-Object { $_ -notin $docFileNames }
$uniqueInDoc = $docFileNames | Where-Object { $_ -notin $devFileNames }
$commonFiles = $devFileNames | Where-Object { $_ -in $docFileNames }

Write-Host "Files unique to /dev/tests: $($uniqueInDev.Count)" -ForegroundColor Cyan
foreach ($file in $uniqueInDev) {
    Write-Host "  - $file" -ForegroundColor Gray
}

Write-Host "Files unique to /doc/tests: $($uniqueInDoc.Count)" -ForegroundColor Cyan
foreach ($file in $uniqueInDoc) {
    Write-Host "  - $file" -ForegroundColor Gray
}

Write-Host "Common files: $($commonFiles.Count)" -ForegroundColor Cyan

Write-Host ""
Write-Host "Step 3: Checking for file conflicts..." -ForegroundColor Yellow

$conflicts = @()
foreach ($fileName in $commonFiles) {
    $devFile = Join-Path $devTestsPath $fileName
    $docFile = Join-Path $docTestsPath $fileName
    
    if (-not (Compare-TestFiles $devFile $docFile)) {
        $conflicts += $fileName
        Write-Host "  ⚠ Conflict detected: $fileName" -ForegroundColor Yellow
    }
}

if ($conflicts.Count -gt 0) {
    Write-Host ""
    Write-Host "WARNING: Found $($conflicts.Count) conflicting files!" -ForegroundColor Yellow
    Write-Host "These files exist in both directories but have different content." -ForegroundColor Yellow
    Write-Host "Manual review required for:" -ForegroundColor Yellow
    foreach ($conflict in $conflicts) {
        Write-Host "  - $conflict" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Step 4: Copying unique files from /dev/tests to /doc/tests..." -ForegroundColor Yellow

foreach ($fileName in $uniqueInDev) {
    $sourcePath = Join-Path $devTestsPath $fileName
    $destPath = Join-Path $docTestsPath $fileName
    
    Copy-Item -Path $sourcePath -Destination $destPath -Force
    Write-Host "  ✓ Copied: $fileName" -ForegroundColor Green
}

# Copy unique utilities from dev/tests/utils
$devUtilsPath = Join-Path $devTestsPath "utils"
$docUtilsPath = Join-Path $docTestsPath "utils"

if (Test-Path $devUtilsPath) {
    $devUtils = Get-ChildItem -Path $devUtilsPath -Filter "*.ts"
    $docUtils = Get-ChildItem -Path $docUtilsPath -Filter "*.ts" -ErrorAction SilentlyContinue
    
    $docUtilNames = @()
    if ($docUtils) {
        $docUtilNames = $docUtils | ForEach-Object { $_.Name }
    }
    
    foreach ($util in $devUtils) {
        if ($util.Name -notin $docUtilNames) {
            $destPath = Join-Path $docUtilsPath $util.Name
            Copy-Item -Path $util.FullName -Destination $destPath -Force
            Write-Host "  ✓ Copied utility: $($util.Name)" -ForegroundColor Green
        }
    }
}

Write-Host ""
Write-Host "Step 5: Creating auth directories..." -ForegroundColor Yellow

$authPaths = @(
    (Join-Path $docTestsPath ".auth"),
    (Join-Path $devTestsPath ".auth")
)

foreach ($authPath in $authPaths) {
    if (-not (Test-Path $authPath)) {
        New-Item -ItemType Directory -Path $authPath -Force | Out-Null
        Write-Host "  ✓ Created: $authPath" -ForegroundColor Green
    } else {
        Write-Host "  ✓ Already exists: $authPath" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "Step 6: Summary Report" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan

$finalDocFiles = Get-ChildItem -Path $docTestsPath -Filter "*.spec.ts" -Recurse
Write-Host "Total test files in /doc/tests: $($finalDocFiles.Count)" -ForegroundColor Green
Write-Host "Backup location: $backupPath" -ForegroundColor Green

if ($conflicts.Count -gt 0) {
    Write-Host ""
    Write-Host "⚠ IMPORTANT: Review conflicting files manually!" -ForegroundColor Yellow
    Write-Host "Compare files in:" -ForegroundColor Yellow
    Write-Host "  - $backupPath\dev-tests" -ForegroundColor Gray
    Write-Host "  - $backupPath\doc-tests" -ForegroundColor Gray
}

Write-Host ""
Write-Host "✓ Test consolidation complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Run 'npm test' from root directory to test with new structure" -ForegroundColor Gray
Write-Host "2. If tests pass, remove /dev/tests directory" -ForegroundColor Gray
Write-Host "3. Update any CI/CD pipelines to use /doc/tests" -ForegroundColor Gray
Write-Host ""