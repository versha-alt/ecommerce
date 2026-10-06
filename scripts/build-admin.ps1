$ErrorActionPreference='Stop'
$projectRoot=Split-Path -Parent $PSScriptRoot
$nodeDir=Join-Path $projectRoot '.runtime/node-v24.13.1-win-x64'
if(Test-Path $nodeDir){$env:Path=$nodeDir+';'+$env:Path}
Set-Location $projectRoot
npm.cmd run build -w '@ecomm/admin'
if($LASTEXITCODE -ne 0){exit $LASTEXITCODE}
npm.cmd run build -w '@ecomm/storefront'
exit $LASTEXITCODE
