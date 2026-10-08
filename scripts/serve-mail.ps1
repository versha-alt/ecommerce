$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpRuntime = Join-Path $projectRoot '.runtime/php/php.exe'
if (!(Test-Path $phpRuntime)) { $phpRuntime = 'php' }
Set-Location (Join-Path $projectRoot 'apps/backend')
& $phpRuntime -d extension=pdo_mysql artisan queue:work database --queue=emails --sleep=1 --tries=3 --timeout=30
exit $LASTEXITCODE
