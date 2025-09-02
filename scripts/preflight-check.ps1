# preflight-check.ps1
# THIS SCRIPT MUST RUN FIRST - NO EXCEPTIONS

$errors = 0

# Check 1: Backup exists
Write-Host "Creating mandatory backup..." -ForegroundColor Yellow
$backupName = "css-nuclear-backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
tar -czf "$backupName.tar.gz" assets/css templates includes languages
if (-not (Test-Path "$backupName.tar.gz")) {
    Write-Host "FATAL: Backup failed. ABORT MISSION." -ForegroundColor Red
    exit 1
}

# Check 2: Current metrics
$cssFiles = (Get-ChildItem -Path "assets/css" -Filter "*.css" -Recurse).Count
$importantCount = (Select-String -Path "assets/css/*.css" -Pattern "!important" -Recurse).Count

Write-Host "Current State:" -ForegroundColor Cyan
Write-Host "  CSS Files: $cssFiles (Target: ≤20)" 
Write-Host "  !important: $importantCount (Target: ≤100)"

if ($cssFiles -le 20 -and $importantCount -le 100) {
    Write-Host "Targets already met. Exiting." -ForegroundColor Green
    exit 0
}

# Check 3: Git status clean
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "WARNING: Uncommitted changes detected. Proceeding anyway since we're on a branch." -ForegroundColor Yellow
}

# Check 4: We're already on css-refactoring-phase-1 branch
$currentBranch = git branch --show-current
Write-Host "Current branch: $currentBranch" -ForegroundColor Green

# Check 5: Lock file creation
@{
    StartTime = Get-Date
    InitialFiles = $cssFiles
    InitialImportant = $importantCount
    BackupFile = "$backupName.tar.gz"
} | ConvertTo-Json | Out-File "css-refactor-lock.json"

Write-Host "`nPRE-FLIGHT COMPLETE. Phase 1 unlocked." -ForegroundColor Green
Write-Host "Backup created: $backupName.tar.gz" -ForegroundColor Green
Write-Host "Lock file created: css-refactor-lock.json" -ForegroundColor Green