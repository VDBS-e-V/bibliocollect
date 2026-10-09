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
    $settings | gh api --method PATCH "repos/$Repo" --input - | Out-Null
    gh api --method PUT "repos/$Repo/vulnerability-alerts" | Out-Null
    gh api --method PUT "repos/$Repo/automated-security-fixes" | Out-Null
    Write-Host 'Repo-Einstellungen gesetzt.'
}

# 2. Rulesets
$existing = gh api "repos/$Repo/rulesets" | ConvertFrom-Json

foreach ($file in Get-ChildItem (Join-Path $root '.github\rulesets') -Filter *.json) {
    $body = Get-Content $file.FullName -Raw
    $name = ($body | ConvertFrom-Json).name
    $current = $existing | Where-Object { $_.name -eq $name } | Select-Object -First 1

    if ($current) {
        if ($PSCmdlet.ShouldProcess($name, "Ruleset aktualisieren (#$($current.id))")) {
            $body | gh api --method PUT "repos/$Repo/rulesets/$($current.id)" --input - | Out-Null
            Write-Host "Ruleset '$name' aktualisiert."
        }
    } else {
        if ($PSCmdlet.ShouldProcess($name, 'Ruleset anlegen')) {
            $body | gh api --method POST "repos/$Repo/rulesets" --input - | Out-Null
            Write-Host "Ruleset '$name' angelegt."
        }
    }
}

Write-Host "Fertig. Kontrolle: gh api repos/$Repo/rulesets"
