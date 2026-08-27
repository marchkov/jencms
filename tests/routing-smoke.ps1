param([int] $Port = 18765)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Net.Http
$projectRoot = Split-Path -Parent $PSScriptRoot
$publicRoot = Join-Path $projectRoot 'public'
$uploadFixture = Join-Path $publicRoot 'uploads\routing-smoke.txt'
$baseUrl = "http://127.0.0.1:$Port"
$server = $null
$httpHandler = [System.Net.Http.HttpClientHandler]::new()
$httpHandler.AllowAutoRedirect = $false
$httpClient = [System.Net.Http.HttpClient]::new($httpHandler)

function Assert-Response {
    param([string] $Path, [int] $ExpectedStatus, [string] $ExpectedContent = '')

    $response = $httpClient.GetAsync($baseUrl + $Path).GetAwaiter().GetResult()
    $status = [int] $response.StatusCode
    $content = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
    $response.Dispose()

    if ($status -ne $ExpectedStatus) { throw "$Path returned $status; expected $ExpectedStatus" }
    if ($ExpectedContent -and -not $content.Contains($ExpectedContent)) {
        throw "$Path did not contain expected text: $ExpectedContent"
    }

    Write-Host "PASS $ExpectedStatus $Path"
}

try {
    Set-Content -LiteralPath $uploadFixture -Value 'JenCMS upload smoke test' -NoNewline
    $arguments = @('-S', "127.0.0.1:$Port", '-t', $publicRoot, (Join-Path $publicRoot 'router.php'))
    $server = Start-Process php -ArgumentList $arguments -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru

    $deadline = (Get-Date).AddSeconds(10)
    do {
        Start-Sleep -Milliseconds 200
        try {
            $null = Invoke-WebRequest -Uri ($baseUrl + '/') -UseBasicParsing -TimeoutSec 1
            $ready = $true
        } catch { $ready = $false }
    } while (-not $ready -and (Get-Date) -lt $deadline -and -not $server.HasExited)

    if (-not $ready) { throw 'PHP development server did not become ready.' }

    Assert-Response '/' 200 'JenCMS'
    Assert-Response '/admin' 302
    Assert-Response '/news' 200 'News'
    Assert-Response '/news/sample-post-1' 200 'Sample Post 1'
    Assert-Response '/themes/default/css/style.css' 200
    Assert-Response '/admin-assets/editor.css' 200 '.editor-shell'
    Assert-Response '/admin-assets/editor.js' 200 'initializeTiptap'
    Assert-Response '/admin-assets/tiptap-v3.30.2.min.js' 200 'JenCmsTiptap'
    Assert-Response '/uploads/routing-smoke.txt' 200 'JenCMS upload smoke test'
    Assert-Response '/themes/default/css/missing.css' 404
    Assert-Response '/definitely-missing-page' 404
} finally {
    $httpClient.Dispose()
    $httpHandler.Dispose()
    if ($server -and -not $server.HasExited) { Stop-Process -Id $server.Id -Force }
    if (Test-Path -LiteralPath $uploadFixture) { Remove-Item -LiteralPath $uploadFixture -Force }
}
