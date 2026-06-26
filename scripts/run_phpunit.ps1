#Requires -Version 5.1
<#
.SYNOPSIS
  PHPUnit via local PHP 7.3 (same major as prod server).

.EXAMPLE
  powershell -File scripts/run_phpunit.ps1 -- --filter OrderPaymentNotificationHelperTest
#>
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$PhpUnitArgs
)

$phpDir = "C:\php\7.3"
if (-not (Test-Path "$phpDir\php.exe")) {
    Write-Error "PHP 7.3 not found at $phpDir. Install from windows.php.net archives."
    exit 1
}

$repoRoot = Split-Path $PSScriptRoot -Parent
$env:Path = "$phpDir;" + $env:Path
Set-Location $repoRoot

$args = @('vendor/bin/phpunit')
if ($PhpUnitArgs) { $args += $PhpUnitArgs }

& php @args
exit $LASTEXITCODE
