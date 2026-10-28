param([Parameter(Mandatory=$true)][string]$SmtpUser)
$ErrorActionPreference = 'Stop'
# Load the module matching this PowerShell executable, even when PSModulePath
# was inherited from a different PowerShell version.
Import-Module -Name (Join-Path $PSHOME 'Modules\Microsoft.PowerShell.Security\Microsoft.PowerShell.Security.psd1') -ErrorAction Stop
$projectRoot = Split-Path -Parent $PSScriptRoot
Write-Host ('Conta de envio: ' + $SmtpUser)
Write-Host 'Informe uma NOVA senha de aplicativo do Gmail. Ela ficara criptografada para seu usuario Windows.'
$smtpPassword = Read-Host 'Senha de aplicativo (entrada oculta)' -AsSecureString
if ($smtpPassword.Length -eq 0) { throw 'Senha vazia. Nenhuma configuracao foi alterada.' }
$passwordText = ([Net.NetworkCredential]::new('', $smtpPassword).Password -replace '\s', '')
if ($passwordText.Length -ne 16) {
    $passwordText = $null
    throw 'O Gmail exige uma senha DE APLICATIVO com 16 caracteres. Gere-a na conta Google; nao use a senha normal. Nenhuma configuracao foi alterada.'
}
$smtpPassword = ConvertTo-SecureString -String $passwordText -AsPlainText -Force
$passwordText = $null
$settings = @{
    smtp_user = $SmtpUser
    smtp_password_protected = ConvertFrom-SecureString -SecureString $smtpPassword
    smtp_host = 'smtp.gmail.com'
    smtp_port = 587
    smtp_secure = 'tls'
    remetente_email = $SmtpUser
}
$settings | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $projectRoot 'storage\private\smtp.local.json') -Encoding UTF8
Write-Host 'Configuracao salva. A senha so pode ser lida por esta conta Windows neste computador.'
Write-Host 'O agendador enviara as mensagens pendentes na proxima execucao.'
