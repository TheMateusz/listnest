$ErrorActionPreference = 'Stop'
$env:WP_ENV_HOME = Join-Path (Get-Location) '.test-env/home'
function Run-WpTest {
    param([string[]] $TestArguments)
    & wp-env run cli wp @TestArguments --config=.test-env/current83.json
    if ($LASTEXITCODE -ne 0) { throw 'WP test command failed' }
}
try {
    Run-WpTest @('option', 'update', 'listnest_dev_mail_fail', '1')
    Run-WpTest @('option', 'update', 'listnest_dev_store_fail', '1')
    node dev/test-failures.mjs http://localhost:8893 lost
    if ($LASTEXITCODE -ne 0) { throw 'Combined failure regression failed' }
    Run-WpTest @('option', 'delete', 'listnest_dev_store_fail')
    node dev/test-failures.mjs http://localhost:8893 stored
    if ($LASTEXITCODE -ne 0) { throw 'Mail failure regression failed' }
    Run-WpTest @('eval-file', 'wp-content/listnest-dev/verify-mail-failure.php')
} finally {
    Run-WpTest @('option', 'delete', 'listnest_dev_mail_fail')
    Run-WpTest @('option', 'delete', 'listnest_dev_store_fail')
}
