Option Explicit
Dim WshShell, fso, xamppDir, edgePath, chromePath, targetUrl
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
xamppDir = "C:\xampp"
targetUrl = "http://localhost/servicehub/"

Dim http, isOnline
isOnline = False
On Error Resume Next
Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
http.open "GET", targetUrl, False
http.setTimeouts 500, 500, 500, 500
http.send
If Err.Number = 0 Then isOnline = True
Err.Clear
On Error Goto 0

If Not isOnline Then
    WshShell.CurrentDirectory = xamppDir
    WshShell.Run Chr(34) & xamppDir & "\apache\bin\httpd.exe" & Chr(34) & " -d " & Chr(34) & xamppDir & "\apache" & Chr(34), 0, False
    WshShell.Run Chr(34) & xamppDir & "\mysql\bin\mysqld.exe" & Chr(34) & " --defaults-file=" & Chr(34) & xamppDir & "\mysql\bin\my.ini" & Chr(34) & " --standalone", 0, False
    WScript.Sleep 600
End If

edgePath = WshShell.ExpandEnvironmentStrings("%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe")
If Not fso.FileExists(edgePath) Then edgePath = WshShell.ExpandEnvironmentStrings("%ProgramFiles%\Microsoft\Edge\Application\msedge.exe")
chromePath = WshShell.ExpandEnvironmentStrings("%ProgramFiles%\Google\Chrome\Application\chrome.exe")
If Not fso.FileExists(chromePath) Then chromePath = WshShell.ExpandEnvironmentStrings("%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe")
If Not fso.FileExists(chromePath) Then chromePath = WshShell.ExpandEnvironmentStrings("%LocalAppData%\Google\Chrome\Application\chrome.exe")

If fso.FileExists(edgePath) Then
    WshShell.Run Chr(34) & edgePath & Chr(34) & " --app=" & Chr(34) & targetUrl & Chr(34) & " --window-size=1280,800", 1, False
ElseIf fso.FileExists(chromePath) Then
    WshShell.Run Chr(34) & chromePath & Chr(34) & " --app=" & Chr(34) & targetUrl & Chr(34) & " --window-size=1280,800", 1, False
Else
    WshShell.Run targetUrl, 1, False
End If
