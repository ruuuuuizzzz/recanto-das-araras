Option Explicit
Dim shell, files, workerPath, powershellPath, command, exitCode
Set shell = CreateObject("WScript.Shell")
Set files = CreateObject("Scripting.FileSystemObject")
workerPath = files.BuildPath(files.GetParentFolderName(WScript.ScriptFullName), "process-notifications.ps1")
powershellPath = shell.ExpandEnvironmentStrings("%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe")
command = Chr(34) & powershellPath & Chr(34) & " -NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File " & Chr(34) & workerPath & Chr(34)
' No console host is created for this launcher; run the worker hidden and preserve its exit code.
exitCode = shell.Run(command, 0, True)
WScript.Quit exitCode
