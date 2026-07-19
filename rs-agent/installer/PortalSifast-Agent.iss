; PortalSifast Agent — one-click installer (Windows Service, no console window)
; Build: .\scripts\build-installer.ps1
; If MyEnrollmentKey is set at compile time, the key wizard page is skipped (true 1-click after UAC).

#ifndef MyAppVersion
  #define MyAppVersion "0.2.1"
#endif

#ifndef MyServerURL
  #define MyServerURL "https://portalsifast.rsaisyiyahsitifatimah.com"
#endif

#ifndef MyEnrollmentKey
  #define MyEnrollmentKey ""
#endif

#define MyAppName "PortalSifast Agent"
#define MyAppPublisher "PortalSifast"
#define MyAppExeName "rs-agent.exe"

[Setup]
AppId={{A7C3E9F1-2B4D-4E8A-9C1F-0D6B8A5E3F27}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\{#MyAppName}
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
PrivilegesRequired=admin
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
OutputDir=..\dist
OutputBaseFilename=PortalSifast-Agent-Setup
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
UninstallDisplayName={#MyAppName}
SetupLogging=yes
CloseApplications=no

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Files]
Source: "..\dist\rs-agent.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\scripts\install-service.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\scripts\uninstall-service.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\configs\config.example.json"; DestDir: "{app}\configs"; Flags: ignoreversion

[Icons]
Name: "{group}\Uninstall {#MyAppName}"; Filename: "{uninstallexe}"

[Run]
Filename: "powershell.exe"; \
  Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\install-service.ps1"" -SourceExe ""{app}\{#MyAppExeName}"" -Server ""{code:GetServerURL}"" -EnrollmentKey ""{code:GetEnrollmentKey}"""; \
  StatusMsg: "Menginstal Windows Service..."; \
  Flags: runhidden waituntilterminated

[UninstallRun]
Filename: "powershell.exe"; \
  Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\uninstall-service.ps1"""; \
  RunOnceId: "UninstallPortalSifastAgentService"; \
  Flags: runhidden waituntilterminated

[Code]
var
  KeyPage: TInputQueryWizardPage;
  CompiledKey: string;
  CompiledServer: string;

function GetEnrollmentKey(Param: string): string;
begin
  if KeyPage <> nil then
    Result := Trim(KeyPage.Values[0])
  else
    Result := CompiledKey;
end;

function GetServerURL(Param: string): string;
begin
  if KeyPage <> nil then
    Result := Trim(KeyPage.Values[1])
  else
    Result := CompiledServer;
end;

procedure InitializeWizard;
begin
  CompiledKey := '{#MyEnrollmentKey}';
  CompiledServer := '{#MyServerURL}';

  { Only ask for key when not baked into the installer. }
  if CompiledKey = '' then
  begin
    KeyPage := CreateInputQueryPage(wpSelectDir,
      'Konfigurasi Agent',
      'Masukkan kunci enrollment dari server PortalSifast.',
      'Agent akan jalan sebagai Windows Service (tanpa jendela terminal).');
    KeyPage.Add('Enrollment key:', False);
    KeyPage.Add('Server URL:', False);
    KeyPage.Values[1] := CompiledServer;
  end;
end;

function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;
  if (KeyPage <> nil) and (CurPageID = KeyPage.ID) then
  begin
    if Trim(KeyPage.Values[0]) = '' then
    begin
      MsgBox('Enrollment key wajib diisi.', mbError, MB_OK);
      Result := False;
      exit;
    end;
    if Trim(KeyPage.Values[1]) = '' then
    begin
      MsgBox('Server URL wajib diisi.', mbError, MB_OK);
      Result := False;
    end;
  end;
end;
