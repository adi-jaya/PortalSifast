package commands

import "testing"

func TestIsBlocked(t *testing.T) {
	t.Parallel()

	cases := []struct {
		exe  string
		want bool
	}{
		{"lsass.exe", true},
		{"LSASS.EXE", true},
		{`C:\Windows\System32\lsass.exe`, true},
		{"explorer.exe", true},
		{"winlogon.exe", true},
		{"csrss.exe", true},
		{"services.exe", true},
		{"dwm.exe", true},
		{"rs-agent.exe", true},
		{"svchost.exe", true},
		{"chrome.exe", false},
		{"notepad.exe", false},
		{"", false},
	}

	for _, tc := range cases {
		if got := IsBlocked(tc.exe); got != tc.want {
			t.Fatalf("IsBlocked(%q)=%v want %v", tc.exe, got, tc.want)
		}
	}
}
