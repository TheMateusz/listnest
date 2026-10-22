$ErrorActionPreference = 'Stop'
$workspace = Split-Path -Parent $PSScriptRoot
$pluginSource = Join-Path $workspace 'listnest'
$header = Get-Content -LiteralPath (Join-Path $pluginSource 'listnest.php') -Raw
$version = [regex]::Match($header, '(?m)^\s*\* Version:\s*([\d.]+)').Groups[1].Value
$readme = Get-Content -LiteralPath (Join-Path $pluginSource 'readme.txt') -Raw
if (-not $version -or $readme -notmatch ('(?m)^Stable tag: ' + [regex]::Escape($version) + '\s*$')) { throw 'Release versions do not match.' }
$outputDirectory = Join-Path $workspace 'releases'
New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
$archivePath = Join-Path $outputDirectory "listnest-$version.zip"
if (Test-Path -LiteralPath $archivePath) { Remove-Item -LiteralPath $archivePath }
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$stream = [System.IO.File]::Open($archivePath, [System.IO.FileMode]::CreateNew)
$zipWriter = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -LiteralPath $pluginSource -Recurse -File | ForEach-Object {
        $entryName = 'listnest/' + $_.FullName.Substring($pluginSource.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipWriter, $_.FullName, $entryName, 'Optimal') | Out-Null
    }
} finally { $zipWriter.Dispose(); $stream.Dispose() }
$archive = [System.IO.Compression.ZipFile]::OpenRead($archivePath)
try {
    $entries = @($archive.Entries | ForEach-Object { $_.FullName })
    if ($entries -notcontains 'listnest/listnest.php' -or $entries -notcontains 'listnest/readme.txt' -or $entries -notcontains 'listnest/LICENSE') { throw 'Missing release files.' }
    foreach ($entry in $entries) {
        if (-not $entry.StartsWith('listnest/') -or $entry.Contains('\') -or $entry -match '(^|/)(dev|node_modules|\.git)(/|$)') { throw "Unexpected archive entry: $entry" }
    }
} finally { $archive.Dispose() }
$hash = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()
Set-Content -LiteralPath "$archivePath.sha256" -Value "$hash  listnest-$version.zip" -Encoding ascii
Write-Output "Created $archivePath ($($entries.Count) files)"
Write-Output "SHA256: $hash"
