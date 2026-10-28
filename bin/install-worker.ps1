param([string]$TaskName = 'Recanto das Araras - Notificacoes')
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = 'C:\xampp\php\php.exe'
$workerPath = Join-Path $projectRoot 'bin\process-notifications.ps1'
$launcherPath = Join-Path $projectRoot 'bin\process-notifications-hidden.vbs'
$wscriptPath = Join-Path $env:SystemRoot 'System32\wscript.exe'
if (!(Test-Path -LiteralPath $phpPath) -or !(Test-Path -LiteralPath $workerPath) -or !(Test-Path -LiteralPath $launcherPath) -or !(Test-Path -LiteralPath $wscriptPath)) { throw 'PHP, processador ou iniciador silencioso nao encontrado.' }
$taskAction = New-ScheduledTaskAction -Execute $wscriptPath -Argument ('//B //NoLogo "' + $launcherPath + '"') -WorkingDirectory $projectRoot
$taskTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$taskSettings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 30) -Hidden -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -TaskName $TaskName -Action $taskAction -Trigger $taskTrigger -Settings $taskSettings -Description 'Processa a fila de notificações do Recanto das Araras enquanto o usuário está conectado.' -Force
