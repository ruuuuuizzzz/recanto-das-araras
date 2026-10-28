param([switch]$CheckOnly)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$configPath = Join-Path $projectRoot 'storage\private\smtp.local.json'
$logPath = Join-Path $projectRoot 'storage\logs\email-worker.log'
try {
    Import-Module -Name (Join-Path $PSHOME 'Modules\Microsoft.PowerShell.Security\Microsoft.PowerShell.Security.psd1') -ErrorAction Stop
    if (Test-Path -LiteralPath $configPath) {
        $settings = Get-Content -LiteralPath $configPath -Raw -Encoding UTF8 | ConvertFrom-Json
        $securePassword = ConvertTo-SecureString -String $settings.smtp_password_protected
        $credential = New-Object System.Management.Automation.PSCredential ($settings.smtp_user, $securePassword)
        [Environment]::SetEnvironmentVariable('SMTP_PASSWORD', $credential.GetNetworkCredential().Password, 'Process')
        foreach ($mapping in @(@('SMTP_USER','smtp_user'),@('SMTP_HOST','smtp_host'),@('SMTP_PORT','smtp_port'),@('SMTP_SECURE','smtp_secure'),@('REMETENTE_EMAIL','remetente_email'))) {
            [Environment]::SetEnvironmentVariable($mapping[0], [string]$settings.($mapping[1]), 'Process')
        }
    }
    $phpArgs = @((Join-Path $projectRoot 'bin\process-notifications.php'))
    if ($CheckOnly) { $phpArgs += '--check' }
    $processInfo = New-Object System.Diagnostics.ProcessStartInfo
    $processInfo.FileName = 'C:\xampp\php\php.exe'
    $processInfo.Arguments = '"' + $phpArgs[0] + '"' + $(if ($CheckOnly) { ' --check' } else { '' })
    $processInfo.WorkingDirectory = $projectRoot
    $processInfo.UseShellExecute = $false
    $processInfo.CreateNoWindow = $true
    $processInfo.RedirectStandardOutput = $true
    $processInfo.RedirectStandardError = $true
    $processInfo.StandardOutputEncoding = [Text.Encoding]::UTF8
    $processInfo.StandardErrorEncoding = [Text.Encoding]::UTF8
    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $processInfo
    try {
        [void]$process.Start()
        $stdout = $process.StandardOutput.ReadToEndAsync()
        $stderr = $process.StandardError.ReadToEndAsync()
        $process.WaitForExit()
        $result = @($stdout.GetAwaiter().GetResult(), $stderr.GetAwaiter().GetResult()) | Where-Object { $_ }
        $exitCode = $process.ExitCode
    } finally {
        $process.Dispose()
    }
    if ($CheckOnly) { $result | Write-Output; exit $exitCode }
    if ((Test-Path -LiteralPath $logPath) -and (Get-Item -LiteralPath $logPath).Length -gt 1MB) {
        Move-Item -LiteralPath $logPath -Destination (Join-Path $projectRoot 'storage\logs\email-worker.previous.log') -Force
    }
    ('[' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss') + '] codigo=' + $exitCode) | Add-Content -LiteralPath $logPath -Encoding UTF8
    $result | Add-Content -LiteralPath $logPath -Encoding UTF8
    exit $exitCode
} catch {
    $message = 'Falha ao preparar o envio. Execute bin\configure-email.ps1 com a mesma conta Windows do agendador.'
    if ($CheckOnly) { Write-Output $message } else { $message | Add-Content -LiteralPath $logPath -Encoding UTF8 }
    exit 1
} finally {
    [Environment]::SetEnvironmentVariable('SMTP_PASSWORD', $null, 'Process')
}
