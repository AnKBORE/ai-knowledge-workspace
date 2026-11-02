#requires -Version 5.1
<#
.SYNOPSIS
    Remove the three Moore PDF files from the whole Git history, then force-push.

.DESCRIPTION
    Run this AFTER the submission, never before.
    Rewriting changes every commit id after the one that introduced the PDFs:
    the hashes quoted in the documentation stop resolving. This script checks
    the repository state, takes a full backup, rewrites, cleans, pushes, and
    verifies the result.

    ASCII only on purpose: Windows PowerShell 5.1 reads a .ps1 without a BOM as
    ANSI, and accented characters break the parser.

.EXAMPLE
    powershell -NoProfile -File tools/purge_history.ps1
    powershell -NoProfile -File tools/purge_history.ps1 -Confirm
#>

[CmdletBinding()]
param(
    [switch]$Confirm,
    [string]$Remote = 'origin',
    [string]$Branch = 'main'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Write-Step([string]$text) {
    Write-Host ''
    Write-Host "== $text" -ForegroundColor Cyan
}

function Write-Ok([string]$text) {
    Write-Host "   OK  $text" -ForegroundColor Green
}

function Write-Warn([string]$text) {
    Write-Host "   !!  $text" -ForegroundColor Yellow
}

# --------------------------------------------------------------- checks
Write-Step 'Checks before rewriting'

$status = git status --porcelain

if ($status) {
    Write-Warn 'the working tree is not clean:'
    $status | ForEach-Object { Write-Host "       $_" }
    throw 'Commit or stash first, then run again.'
}

Write-Ok 'working tree clean'

$inHead = git ls-tree -r HEAD --name-only | Select-String -Pattern 'storage/moore_sources/.*\.pdf'

if ($inHead) {
    Write-Warn 'the PDFs are still in HEAD: push the removal commit first'
} else {
    Write-Ok 'the PDFs are no longer in HEAD'
}

$history = git log --all --oneline -- 'storage/moore_sources/*.pdf'

if (-not $history) {
    Write-Ok 'no commit contains the PDFs: nothing to purge'
    return
}

Write-Host ''
Write-Host '   Commits that contain the PDFs:' -ForegroundColor Yellow
$history | ForEach-Object { Write-Host "       $_" }

if (-not $Confirm) {
    Write-Host ''
    Write-Host 'Dry run finished. Add -Confirm to rewrite and push.' -ForegroundColor Yellow
    return
}

# ------------------------------------------------------------- backup
Write-Step 'Full backup of the repository'

$stamp = Get-Date -Format 'yyyy-MM-dd-HHmm'
$bundle = Join-Path (Split-Path -Parent $root) ("backup-before-purge-{0}.bundle" -f $stamp)

git bundle create $bundle --all | Out-Null

if (-not (Test-Path $bundle)) {
    throw 'Backup was not created: aborting.'
}

Write-Ok ("backup: {0} ({1} MB)" -f $bundle, [math]::Round((Get-Item $bundle).Length / 1MB, 1))

# ------------------------------------------------------------ rewrite
Write-Step 'Rewriting history'

git filter-branch --force --index-filter 'git rm -r --cached --ignore-unmatch "storage/moore_sources"' --prune-empty -- --all

if ($LASTEXITCODE -ne 0) {
    throw 'filter-branch failed: the backup is intact and nothing was pushed.'
}

Write-Ok 'history rewritten'

# --------------------------------------------------------------- clean
Write-Step 'Cleaning the backup references'

git for-each-ref --format='%(refname)' refs/original/ | ForEach-Object { git update-ref -d $_ }
git reflog expire --expire=now --all
git gc --prune=now --aggressive

Write-Ok 'objects purged'

# --------------------------------------------------------------- check
Write-Step 'Local verification'

$still = git log --all --oneline -- 'storage/moore_sources/*.pdf'

if ($still) {
    Write-Warn 'some commits still contain the PDFs:'
    $still | ForEach-Object { Write-Host "       $_" }
    throw 'Rewrite incomplete: do not push.'
}

Write-Ok 'no commit contains the PDFs anymore'

# ---------------------------------------------------------------- push
Write-Step ("Force-push to {0}/{1}" -f $Remote, $Branch)

git push $Remote $Branch --force

if ($LASTEXITCODE -ne 0) {
    throw 'Push failed. The backup allows a rollback.'
}

Write-Ok 'remote updated'

# ------------------------------------------------------- documentation
Write-Step 'Next step, by hand'

Write-Host '   The hashes quoted in the documentation no longer resolve. Add this'
Write-Host '   sentence at the top of docs/JOURNAL_DE_CONSTRUCTION.md and its .fr.md:'
Write-Host ''
Write-Host '     History was rewritten on <date> to remove three third-party PDFs'
Write-Host '     under copyright. Commit ids quoted before that date no longer'
Write-Host '     resolve; the dates and the facts remain.'
Write-Host ''
Write-Host '   Then open an old commit on GitHub (for example 9969fe3): it must be gone.'

Write-Host ''
Write-Host '   Done.' -ForegroundColor Green
