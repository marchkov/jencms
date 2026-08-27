$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot

function Invoke-PhpTest {
    param([string]$RelativePath)

    & php (Join-Path $projectRoot $RelativePath)
    if ($LASTEXITCODE -ne 0) {
        throw "$RelativePath failed with exit code $LASTEXITCODE."
    }
}

Invoke-PhpTest 'tests\migrations-smoke.php'
Invoke-PhpTest 'tests\system-check-smoke.php'
Invoke-PhpTest 'tests\session-security-smoke.php'
Invoke-PhpTest 'tests\content-storage-smoke.php'

& (Join-Path $PSScriptRoot 'routing-smoke.ps1')
if ($LASTEXITCODE -ne 0) {
    throw "tests\routing-smoke.ps1 failed with exit code $LASTEXITCODE."
}

Write-Host 'PASS complete JenCMS smoke suite'
