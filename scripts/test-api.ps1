$projectRoot = Split-Path -Parent $PSScriptRoot
$phpRuntime = Join-Path $projectRoot '.runtime/php/php.exe'
if (!(Test-Path $phpRuntime)) { $phpRuntime = 'php' }
Set-Location (Join-Path $projectRoot 'apps/backend')
& $phpRuntime artisan test
exit $LASTEXITCODE
