$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpRuntime = Join-Path $projectRoot '.runtime/php/php.exe'
if (!(Test-Path $phpRuntime)) { $phpRuntime = 'php' }
$uploadTempDirectory = Join-Path $projectRoot 'tmp/php-uploads'
New-Item -ItemType Directory -Path $uploadTempDirectory -Force | Out-Null
$env:TEMP = $uploadTempDirectory
$env:TMP = $uploadTempDirectory
Set-Location (Join-Path $projectRoot 'apps/backend')
& $phpRuntime artisan serve --host=127.0.0.1 --port=8000 --no-reload
exit $LASTEXITCODE
