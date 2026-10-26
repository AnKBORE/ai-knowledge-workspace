<#
.SYNOPSIS
    One-command local setup for the Agentic Multimodal Knowledge Workspace.

.DESCRIPTION
    - uses PHP 8.4.27 (or the newest 8.4.x found under C:\wamp64\bin\php)
    - installs Composer dependencies
    - creates .env from .env.example when missing
    - applies the database migrations

.NOTES
    Run from the repository root:   .\setup.ps1
    (or double-click setup.bat, which bypasses the execution policy)
    The database must exist first (the script applies the schema, it does not create the database).
#>

# Silence Xdebug's log warning on stderr (it would otherwise look like an error).
$env:XDEBUG_MODE = 'off'

$root = $PSScriptRoot

# -- 1. Locate PHP 8.4.27 (or the newest 8.4.x) ------------------------------
$preferred = 'C:\wamp64\bin\php\php8.4.27\php.exe'
if (Test-Path $preferred) {
    $php = $preferred
} else {
    $php = Get-ChildItem 'C:\wamp64\bin\php' -Directory -Filter 'php8.4.*' -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending |
        ForEach-Object { Join-Path $_.FullName 'php.exe' } |
        Where-Object { Test-Path $_ } |
        Select-Object -First 1
}

if (-not $php) {
    throw 'PHP 8.4 not found under C:\wamp64\bin\php. Switch WAMP to PHP 8.4.27 and retry.'
}

Write-Host "PHP: $php" -ForegroundColor Cyan
& $php -r "echo 'version: ', PHP_VERSION, PHP_EOL;"

# -- 2. Composer -------------------------------------------------------------
$composer = Join-Path $root 'tools\composer.phar'
if (-not (Test-Path $composer)) {
    $composer = 'C:\wamp64\www\espace_collaboratif\tools\composer.phar'
}
if (-not (Test-Path $composer)) {
    throw 'composer.phar not found. Place it in tools/composer.phar and retry.'
}

Write-Host "`n[1/3] Installing Composer dependencies..." -ForegroundColor Yellow
& $php $composer install --working-dir=$root --no-interaction
if ($LASTEXITCODE -ne 0) {
    throw "composer install failed (exit $LASTEXITCODE)."
}

# -- 3. .env ----------------------------------------------------------------
$envFile = Join-Path $root '.env'
if (-not (Test-Path $envFile)) {
    Copy-Item (Join-Path $root '.env.example') $envFile
    Write-Host '[2/3] Created .env from .env.example — add your Meta API keys.' -ForegroundColor Yellow
} else {
    Write-Host '[2/3] .env already exists.' -ForegroundColor DarkGray
}

# -- 4. Migrations ------------------------------------------------------------
Write-Host '[3/3] Applying database migrations...' -ForegroundColor Yellow
& $php (Join-Path $root 'tools\migrate.php') migrate
if ($LASTEXITCODE -ne 0) {
    throw 'Migrations failed. Create the ai_knowledge_workspace database first, then retry.'
}

Write-Host "`nSetup complete." -ForegroundColor Green
Write-Host 'Next steps:'
Write-Host '  1. Put your Meta Model API keys in .env'
Write-Host '  2. Index a document:  php tools/index_document.php "Annual report" docs/sample-report.txt'
Write-Host '  3. Run the server:    php -S 127.0.0.1:8080 -t public'
