<#
.SYNOPSIS
  Baut ein Release-Paket (ZIP) für Hosting ohne SSH, z. B. einen reinen Webserver.

.DESCRIPTION
  Das Paket enthält den Code, die fertig gebauten Oberflächendateien (public/build) und die Abhängigkeiten ohne
  Entwicklungswerkzeuge (vendor). Es enthält nicht: .env, Datenbank, Cover, Sicherungen, Tests und Git-Daten.
  Gebaut wird in einer Kopie im Temp-Ordner, dein Arbeitsordner bleibt unverändert.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1
#>
param(
    [string]$OutputDirectory = 'dist'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$stage = Join-Path $env:TEMP 'bibliocollect-release'
$stamp = Get-Date -Format 'yyyy-MM-dd_HHmm'
$outDir = Join-Path $root $OutputDirectory
$zip = Join-Path $outDir "bibliocollect-$stamp.zip"

Write-Host '1/4 Oberfläche bauen (npm run build) ...'
Push-Location $root
npm run build
if ($LASTEXITCODE -ne 0) { throw 'npm run build ist fehlgeschlagen.' }
Pop-Location

Write-Host '2/4 Dateien kopieren ...'
if (Test-Path $stage) { Remove-Item -Recurse -Force $stage }
New-Item -ItemType Directory -Path $stage | Out-Null

$excludeDirs = @('.git', '.github', '.idea', '.vscode', 'node_modules', 'vendor', 'tests', 'dist', 'docs\altsystem',
    'storage\logs', 'storage\app\backups', 'storage\app\public', 'storage\app\private', 'storage\framework',
    'public\covers', 'public\card-designs', 'public\storage', 'bootstrap\cache')
$excludeFiles = @('.env', '*.sqlite', '*.patch', '.phpunit.result.cache', 'auth.json')

robocopy $root $stage /E /NFL /NDL /NJH /NJS /NP `
    /XD ($excludeDirs | ForEach-Object { Join-Path $root $_ }) `
    /XF $excludeFiles | Out-Null
if ($LASTEXITCODE -ge 8) { throw "Kopieren fehlgeschlagen (robocopy $LASTEXITCODE)." }

foreach ($dir in @('storage\logs', 'storage\app\public', 'storage\app\private', 'storage\app\backups',
        'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views', 'bootstrap\cache')) {
    New-Item -ItemType Directory -Path (Join-Path $stage $dir) -Force | Out-Null
}
'*' + "`n" + '!.gitignore' | Set-Content -Path (Join-Path $stage 'storage\logs\.gitignore') -Encoding ASCII

Write-Host '3/4 Abhängigkeiten ohne Entwicklungswerkzeuge installieren (composer) ...'
Push-Location $stage
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --quiet
if ($LASTEXITCODE -ne 0) { throw 'composer install ist fehlgeschlagen.' }
Pop-Location

Write-Host '4/4 ZIP schreiben ...'
New-Item -ItemType Directory -Path $outDir -Force | Out-Null
if (Test-Path $zip) { Remove-Item $zip }
Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $zip -CompressionLevel Optimal

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Fertig: $zip ($size MB)"
Write-Host 'Weiter mit docs/HOSTING_SHARED.md (Hochladen, .env anlegen, Datenbank einrichten).'
