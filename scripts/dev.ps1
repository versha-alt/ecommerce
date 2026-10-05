$ErrorActionPreference='Stop'
$projectRoot=Split-Path -Parent $PSScriptRoot
$nodeDir=Join-Path $projectRoot '.runtime/node-v24.13.1-win-x64'
if(Test-Path $nodeDir){$env:Path=$nodeDir+';'+$env:Path}
Set-Location $projectRoot
node node_modules/concurrently/dist/bin/concurrently.js -k -n api,admin 'npm run dev:api' 'npm run dev -w @ecomm/admin'
exit $LASTEXITCODE
