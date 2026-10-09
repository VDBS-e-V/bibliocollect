<#
.SYNOPSIS
  Wendet die Repo-Einstellungen und Rulesets aus .github/rulesets an (nur für Admins des Repos).

.DESCRIPTION
  Liest die Ruleset-Dateien in .github/rulesets und legt die Rulesets in GitHub an oder aktualisiert sie (nach Namen).
  Setzt außerdem die Merge-Optionen (nur Squash, Branch nach Merge löschen), Auto-Merge, "Update branch" und die
  Sicherheitsfunktionen (Secret-Scanning mit Push-Schutz, Dependabot-Warnungen und -Sicherheitsupdates).
  Voraussetzung: GitHub CLI (gh), angemeldet als Admin des Repos.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts/apply-github-settings.ps1

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts/apply-github-settings.ps1 -WhatIf
#>
[CmdletBinding(SupportsShouldProcess = $true)]
param(
    [string]$Repo = 'VDBS-e-V/bibliocollect'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent

if (-not (Get-Command gh -ErrorAction SilentlyContinue)) { throw 'Die GitHub CLI (gh) fehlt: https://cli.github.com' }

# gh liest JSON am zuverlässigsten aus einer Datei (UTF-8 ohne BOM); das Weiterleiten per Pipe scheitert im Windows-PowerShell mit "Problems parsing JSON".
function Invoke-Gh {
    param([string[]]$Arguments)
    $output = & gh @Arguments 2>&1
    if ($LASTEXITCODE -ne 0) { throw "gh $($Arguments -join ' ') ist fehlgeschlagen: $output" }
    return $output
}

function Write-JsonFile {
    param([string]$Json)
    $path = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($path, $Json, (New-Object System.Text.UTF8Encoding($false)))
    return $path
}

# 1. Merge-Optionen und Sicherheit
$settings = @{
    allow_squash_merge          = $true
    allow_merge_commit          = $false
    allow_rebase_merge          = $false
    squash_merge_commit_title   = 'PR_TITLE'
    squash_merge_commit_message = 'PR_BODY'
    delete_branch_on_merge      = $true
    allow_auto_merge            = $true
    allow_update_branch         = $true
    security_and_analysis       = @{
        secret_scanning                 = @{ status = 'enabled' }
        secret_scanning_push_protection = @{ status = 'enabled' }
    }
} | ConvertTo-Json -Depth 5

if ($PSCmdlet.ShouldProcess($Repo, 'Merge-Optionen und Sicherheit setzen')) {
    $file = Write-JsonFile $settings
    try { Invoke-Gh @('api', '--method', 'PATCH', "repos/$Repo", '--input', $file) | Out-Null } finally { Remove-Item $file -ErrorAction SilentlyContinue }
    Invoke-Gh @('api', '--method', 'PUT', "repos/$Repo/vulnerability-alerts") | Out-Null
    Invoke-Gh @('api', '--method', 'PUT', "repos/$Repo/automated-security-fixes") | Out-Null
    Write-Host 'Repo-Einstellungen gesetzt.'
}

# 2. Rulesets
$existing = (Invoke-Gh @('api', "repos/$Repo/rulesets")) -join "`n" | ConvertFrom-Json

foreach ($file in Get-ChildItem (Join-Path $root '.github/rulesets') -Filter *.json) {
    $body = Get-Content $file.FullName -Raw
    $name = ($body | ConvertFrom-Json).name
    $current = $existing | Where-Object { $_.name -eq $name } | Select-Object -First 1
    $json = Write-JsonFile $body

    try {
        if ($current) {
            if ($PSCmdlet.ShouldProcess($name, "Ruleset aktualisieren (#$($current.id))")) {
                Invoke-Gh @('api', '--method', 'PUT', "repos/$Repo/rulesets/$($current.id)", '--input', $json) | Out-Null
                Write-Host "Ruleset '$name' aktualisiert."
            }
        } elseif ($PSCmdlet.ShouldProcess($name, 'Ruleset anlegen')) {
            Invoke-Gh @('api', '--method', 'POST', "repos/$Repo/rulesets", '--input', $json) | Out-Null
            Write-Host "Ruleset '$name' angelegt."
        }
    } finally {
        Remove-Item $json -ErrorAction SilentlyContinue
    }
}

Write-Host "Fertig. Kontrolle: gh api repos/$Repo/rulesets"
