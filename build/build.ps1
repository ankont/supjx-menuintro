$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$pluginRoot = Join-Path $repoRoot 'plugin'
$manifestPath = Join-Path $pluginRoot 'menuintro.xml'
$readmePath = Join-Path $repoRoot 'README.md'
$licensePath = Join-Path $repoRoot 'LICENSE.txt'

if (-not (Test-Path $pluginRoot)) {
    throw "Plugin source folder not found: $pluginRoot"
}

if (-not (Test-Path $manifestPath)) {
    throw "Manifest not found: $manifestPath"
}

[xml]$manifest = Get-Content $manifestPath -Raw
$versionNode = $manifest.SelectSingleNode('/extension/version')
$version = if ($null -ne $versionNode) { $versionNode.InnerText.Trim() } else { '' }

if ([string]::IsNullOrWhiteSpace($version)) {
    throw "Version element not found in $manifestPath"
}

$buildRoot = Join-Path $repoRoot 'build'
$stageRoot = Join-Path $buildRoot 'stage'
$outputRoot = Join-Path $buildRoot 'output'
$zipName = "plg_system_menuintro-v$version.zip"
$zipPath = Join-Path $outputRoot $zipName

New-Item -ItemType Directory -Force $stageRoot | Out-Null
New-Item -ItemType Directory -Force $outputRoot | Out-Null

Get-ChildItem -Path $stageRoot -Force | Remove-Item -Recurse -Force
Copy-Item -Path (Join-Path $pluginRoot '*') -Destination $stageRoot -Recurse -Force

if (Test-Path $readmePath) {
    Copy-Item -Path $readmePath -Destination (Join-Path $stageRoot 'README.md') -Force
}

if (Test-Path $licensePath) {
    Copy-Item -Path $licensePath -Destination (Join-Path $stageRoot 'LICENSE.txt') -Force
}

if (Test-Path $zipPath) {
    Remove-Item -Path $zipPath -Force
}

Compress-Archive -Path (Join-Path $stageRoot '*') -DestinationPath $zipPath -CompressionLevel Optimal

Write-Host "Built package: $zipPath"
