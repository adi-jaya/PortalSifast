<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * dep_id yang dianggap sebagai petugas emergency (bisa update lokasi, login officer).
     */
    public const OFFICER_DEP_IDS = ['DRIVER', 'IGD'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'simrs_nik',
        'badge_id',
        'phone',
        'telegram_chat_id',
        'source',
        'role',
        'dep_id',
        'can_access_payroll',
        'can_access_patroli',
        'can_manage_mutu',
        'can_input_mutu',
        'can_view_mutu_dashboard',
        'can_manage_web_official',
        'can_buat_dokumen',
        'can_review_dokumen',
        'can_approve_dokumen_mutu',
        'can_tte_dokumen',
        'can_manage_tatanaskah',
        'can_konfirmasi_terima_dokumen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'can_access_payroll' => 'boolean',
            'can_access_patroli' => 'boolean',
            'can_manage_mutu' => 'boolean',
            'can_input_mutu' => 'boolean',
            'can_view_mutu_dashboard' => 'boolean',
            'can_manage_web_official' => 'boolean',
            'can_buat_dokumen' => 'boolean',
            'can_review_dokumen' => 'boolean',
            'can_approve_dokumen_mutu' => 'boolean',
            'can_tte_dokumen' => 'boolean',
            'can_manage_tatanaskah' => 'boolean',
            'can_konfirmasi_terima_dokumen' => 'boolean',
        ];
    }

    // ==================== SESSION RELATIONSHIP ====================

    /**
     * User sessions untuk tracking online status
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(\Illuminate\Session\DatabaseSession::class, 'user_id');
    }

    /**
     * Get avatar URL attribute
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return null; // No avatar field in database
    }

    // ==================== TICKETING RELATIONSHIPS ====================

    /**
     * Tiket yang dibuat oleh user ini (sebagai pemohon)
     */
    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * Tiket yang ditugaskan ke user ini (sebagai assignee)
     */
    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /**
     * Komentar tiket oleh user ini
     */
    public function ticketComments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Aktivitas tiket oleh user ini
     */
    public function ticketActivities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    // ==================== ROLE HELPERS ====================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        $emails = config('auth.superadmin_emails', []);
        if (! is_array($emails)) {
            return false;
        }

        return in_array(mb_strtolower((string) $this->email), $emails, true);
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isPemohon(): bool
    {
        return $this->role === 'pemohon';
    }

    /**
     * Akun integrasi API (token service / kepegawaian). Tidak memiliki NIK pegawai di profil;
     * endpoint payroll harus mengirim ?nik= atau header X-Sifast-Nik.
     */
    public function isPayrollServiceIntegrationAccount(): bool
    {
        $email = mb_strtolower((string) ($this->email ?? ''));

        return str_contains($email, 'api-service')
            || str_contains($email, 'service@')
            || $this->role === 'service';
    }

    public function canAccessPayroll(): bool
    {
        return $this->isSuperAdmin() || (bool) $this->can_access_payroll;
    }

    public function canManagePayrollAccess(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canAccessPatroli(): bool
    {
        return $this->isSuperAdmin() || (bool) $this->can_access_patroli;
    }

    public function canManagePatroliAccess(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageWebOfficial(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || (bool) $this->can_manage_web_official;
    }

    public function canManageWebOfficialAccess(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiProfileArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'simrs_nik' => $this->simrs_nik,
            'phone' => $this->phone,
            'role' => $this->role,
            'dep_id' => $this->dep_id,
            'can_manage_web_official' => $this->canManageWebOfficial(),
            'can_access_patroli' => $this->canAccessPatroli(),
        ];
    }

    public function canAccessSimmutuModule(): bool
    {
        return $this->isSuperAdmin()
            || (bool) $this->can_manage_mutu
            || (bool) $this->can_input_mutu
            || (bool) $this->can_view_mutu_dashboard;
    }

    public function canManageMutu(): bool
    {
        return $this->isSuperAdmin() || (bool) $this->can_manage_mutu;
    }

    public function canRecordMutuRealisation(): bool
    {
        return $this->canManageMutu()
            || ((bool) $this->can_input_mutu && $this->dep_id !== null && $this->dep_id !== '');
    }

    public function canManageMutuAccess(): bool
    {
        return $this->isSuperAdmin();
    }

    // ==================== TATA NASKAH ====================

    public function canAccessTatanaskahModule(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->canManageTatanaskah()
            || $this->canBuatDokumen()
            || $this->canReviewDokumenUnit()
            || $this->canApproveDokumenMutu()
            || $this->canTteDokumen();
    }

    public function canManageTatanaskah(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || (bool) $this->can_manage_tatanaskah;
    }

    public function canBuatDokumen(): bool
    {
        return $this->canManageTatanaskah() || (bool) $this->can_buat_dokumen || $this->isStaff();
    }

    public function canReviewDokumenUnit(): bool
    {
        return $this->canManageTatanaskah() || (bool) $this->can_review_dokumen;
    }

    public function canApproveDokumenMutu(): bool
    {
        return $this->canManageTatanaskah() || (bool) $this->can_approve_dokumen_mutu;
    }

    public function canTteDokumen(): bool
    {
        return $this->isSuperAdmin() || (bool) $this->can_tte_dokumen || $this->isAdmin();
    }

    public function isPenandatanganFor(Dokumen $dokumen): bool
    {
        return filled($this->simrs_nik)
            && filled($dokumen->penandatangan_nik)
            && $this->simrs_nik === $dokumen->penandatangan_nik;
    }

    /**
     * Cek apakah user bisa mengakses tiket departemen tertentu
     */
    public function canAccessDepartment(string $depId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->dep_id === $depId;
    }

    // ==================== OFFICER (EMERGENCY TRACKING) ====================

    /**
     * Apakah user ini petugas emergency (bisa login officer, update lokasi GPS).
     */
    public function isOfficer(): bool
    {
        return $this->role === 'staff' && in_array($this->dep_id, self::OFFICER_DEP_IDS, true);
    }

    /**
     * Scope: hanya user yang role staff dan dep_id petugas emergency.
     */
    public function scopeOfficers($query)
    {
        return $query->where('role', 'staff')->whereIn('dep_id', self::OFFICER_DEP_IDS);
    }

    public function fcmDeviceTokens(): HasMany
    {
        return $this->hasMany(FcmDeviceToken::class);
    }

    public function officerLocations(): HasMany
    {
        return $this->hasMany(OfficerLocation::class, 'officer_id');
    }

    // ==================== CHAT ====================

    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // ==================== CATATAN KERJA ====================

    public function workNotes(): HasMany
    {
        return $this->hasMany(WorkNote::class);
    }

    // ==================== PAYROLL ====================

    public function employeeSalaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    // ==================== TELEGRAM ====================

    /**
     * Chat ID untuk notifikasi Telegram (untuk notifikasi tiket & catatan dari Telegram).
     */
    public function routeNotificationForTelegram(): ?string
    {
        return $this->telegram_chat_id;
    }

    public function hasTelegramConnected(): bool
    {
        return ! empty($this->telegram_chat_id);
    }
}
