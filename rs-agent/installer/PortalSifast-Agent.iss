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

; Service install is done in [Code] CurStepChanged(ssPostInstall) so failures
; surface as a message box (silent [Run] previously left EXE without a service).

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

function GetEnrollmentKey(): string;
begin
  if KeyPage <> nil then
    Result := Trim(KeyPage.Values[0])
  else
    Result := CompiledKey;
end;

function GetServerURL(): string;
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

function WriteEnrollFile(const FileName: string): Boolean;
var
  Lines: TArrayOfString;
begin
  SetArrayLength(Lines, 2);
  Lines[0] := 'server=' + GetServerURL();
  Lines[1] := 'enrollment_key=' + GetEnrollmentKey();
  Result := SaveStringsToFile(FileName, Lines, False);
end;

function InstallWindowsService(): Boolean;
var
  EnrollFile: string;
  Params: string;
  ResultCode: Integer;
  Ok: Boolean;
begin
  Result := False;
  EnrollFile := ExpandConstant('{tmp}\psa-enroll.txt');
  if not WriteEnrollFile(EnrollFile) then
  begin
    MsgBox('Gagal menulis file enrollment sementara.', mbError, MB_OK);
    exit;
  end;

  if Trim(GetEnrollmentKey()) = '' then
  begin
    MsgBox('Enrollment key kosong. Install dibatalkan.', mbError, MB_OK);
    exit;
  end;

  Params :=
    '-NoProfile -ExecutionPolicy Bypass -File "' + ExpandConstant('{app}\scripts\install-service.ps1') + '"' +
    ' -SourceExe "' + ExpandConstant('{app}\{#MyAppExeName}') + '"' +
    ' -EnrollmentKeyFile "' + EnrollFile + '"';

  WizardForm.StatusLabel.Caption := 'Menginstal Windows Service...';
  Ok := Exec(
    ExpandConstant('{sys}\WindowsPowerShell\v1.0\powershell.exe'),
    Params,
    '',
    SW_HIDE,
    ewWaitUntilTerminated,
    ResultCode
  );

  if (not Ok) or (ResultCode <> 0) then
  begin
    MsgBox(
      'Instalasi service gagal (kode ' + IntToStr(ResultCode) + ').' + #13#10#13#10 +
      'Agent TIDAK berjalan otomatis.' + #13#10 +
      'Cek log: %ProgramData%\PortalSifast Agent\logs\install.log' + #13#10 +
      'atau Event Viewer → Windows Logs → Application.',
      mbError,
      MB_OK
    );
    exit;
  end;

  Result := True;
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    if not InstallWindowsService() then
    begin
      { Keep files on disk for diagnosis, but make failure obvious. }
      SuppressibleMsgBox(
        'Setup selesai menyalin file, tetapi Windows Service gagal diaktifkan.' + #13#10 +
        'Jalankan ulang Setup sebagai Administrator, atau:' + #13#10 +
        'powershell -ExecutionPolicy Bypass -File "C:\Program Files\PortalSifast Agent\scripts\install-service.ps1"',
        mbError,
        MB_OK,
        IDOK
      );
    end;
  end;
end;
