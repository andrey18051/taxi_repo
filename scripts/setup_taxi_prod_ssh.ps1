#Requires -Version 5.1
<#
.SYNOPSIS
  SSH access to taxi logs (91.219.60.148) for Cursor / local dev.

.DESCRIPTION
  Use a NEW key per PC (do not copy id_taxi_prod private key between machines).
  Add the public key once to root authorized_keys on the server.

  After setup:
    ssh taxi-prod "tail -5 /opt/laravel_logs/taxi_test/laravel.log"
#>
param(
    [string]$HostName = "91.219.60.148",
    [string]$SshUser = "root",
    [string]$SshHostAlias = "taxi-prod",
    [string]$KeyPath = "$env:USERPROFILE\.ssh\id_taxi_prod",
    [switch]$SkipServerHint
)

$ErrorActionPreference = "Stop"
$sshDir = "$env:USERPROFILE\.ssh"
$configPath = "$sshDir\config"
$keyComment = "cursor-taxi-log-reader"

if (-not (Test-Path $sshDir)) {
    New-Item -ItemType Directory -Path $sshDir -Force | Out-Null
}

if (-not (Test-Path $KeyPath)) {
    Write-Host "Creating key: $KeyPath"
    & ssh-keygen -t ed25519 -f $KeyPath -C $keyComment -q -N '""'
    if ($LASTEXITCODE -ne 0) { throw "ssh-keygen failed" }
} else {
    Write-Host "Key exists: $KeyPath"
    $null = & ssh-keygen -y -f $KeyPath 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Key has passphrase or is broken. Delete $KeyPath and run again."
        exit 1
    }
}

$pubKey = (Get-Content "$KeyPath.pub" -Raw).Trim()
Write-Host ""
Write-Host "Public key:"
Write-Host $pubKey
Write-Host ""

$block = @"
Host $SshHostAlias
    HostName $HostName
    User $SshUser
    IdentityFile ~/.ssh/id_taxi_prod
    IdentitiesOnly yes

"@

if (Test-Path $configPath) {
    $existing = Get-Content $configPath -Raw
    if ($existing -match "Host\s+$SshHostAlias\b") {
        Write-Host "Host $SshHostAlias already in $configPath"
    } else {
        Add-Content -Path $configPath -Value "`n$block" -Encoding utf8
        Write-Host "Added Host $SshHostAlias to $configPath"
    }
} else {
    Set-Content -Path $configPath -Value $block.TrimEnd() -Encoding utf8
    Write-Host "Created $configPath"
}

Write-Host ""
Write-Host "Testing key login..."
$prevEap = $ErrorActionPreference
$ErrorActionPreference = 'Continue'
$test = & ssh -o BatchMode=yes -o ConnectTimeout=12 -o IdentitiesOnly=yes -i $KeyPath "${SshUser}@${HostName}" "echo OK_NO_PASSWORD" 2>&1 | Out-String
$sshExit = $LASTEXITCODE
$ErrorActionPreference = $prevEap
if ($sshExit -eq 0 -and ($test -match "OK_NO_PASSWORD")) {
    Write-Host "OK: passwordless login works." -ForegroundColor Green
    Write-Host ""
    Write-Host "Logs:"
    Write-Host "  test: /opt/laravel_logs/taxi_test/laravel.log"
    Write-Host "  work: /opt/laravel_logs/taxi_work/laravel.log"
    Write-Host "  archive: laravel_log_YYYY-MM-DD_21-0*.log in same folders"
    exit 0
}

Write-Host "Server does not accept this key yet. One-time setup with root password:" -ForegroundColor Yellow
if (-not $SkipServerHint) {
    $scriptPath = Join-Path $PSScriptRoot "setup_taxi_prod_ssh.ps1"
    $serverCmd = "grep -q '$keyComment' ~/.ssh/authorized_keys 2>/dev/null || echo '$pubKey' >> ~/.ssh/authorized_keys"
    Write-Host ""
    Write-Host ('  ssh ' + $SshUser + '@' + $HostName)
    Write-Host ""
    Write-Host "  mkdir -p ~/.ssh && chmod 700 ~/.ssh"
    Write-Host ('  ' + $serverCmd)
    Write-Host "  chmod 600 ~/.ssh/authorized_keys"
    Write-Host "  exit"
    Write-Host ""
    Write-Host ('  powershell -ExecutionPolicy Bypass -File "' + $scriptPath + '"')
    Write-Host ""
}
exit 2
