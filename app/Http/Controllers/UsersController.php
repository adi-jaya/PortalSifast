<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query();

        // Search by name, email, NIK
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('simrs_nik', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Filter by department
        if ($request->filled('dep_id')) {
            $query->where('dep_id', $request->input('dep_id'));
        }

        $users = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'simrs_nik' => $user->simrs_nik,
                'source' => $user->source,
                'role' => $user->role,
                'dep_id' => $user->dep_id,
                'created_at' => $user->created_at,
                'can_manage_web_official' => (bool) $user->can_manage_web_official,
                'has_web_official_access' => $user->canManageWebOfficial(),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'dep_id' => $request->input('dep_id', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $canManagePayrollAccess = request()->user()?->canManagePayrollAccess() ?? false;
        $canManageMutuAccess = request()->user()?->canManageMutuAccess() ?? false;
        $canManageWebOfficialAccess = request()->user()?->canManageWebOfficialAccess() ?? false;

        $existingUserNiks = User::query()
            ->whereNotNull('simrs_nik')
            ->pluck('simrs_nik')
            ->toArray();

        $existingUserEmails = User::query()
            ->pluck('email')
            ->toArray();

        $availablePegawai = Pegawai::query()
            ->with(['petugas', 'dokter'])
            ->orderBy('nama')
            ->get()
            ->filter(function (Pegawai $p) use ($existingUserNiks, $existingUserEmails) {
                $email = $p->getEmailForSync();
                $validEmail = $email && filter_var($email, FILTER_VALIDATE_EMAIL);

                if (! $validEmail) {
                    return false;
                }

                $alreadyUser = in_array($p->nik, $existingUserNiks)
                    || in_array($email, $existingUserEmails);

                return ! $alreadyUser;
            })
            ->map(function (Pegawai $p) {
                return [
                    'nik' => $p->nik,
                    'nama' => $p->nama ?? $p->nik,
                    'email' => $p->getEmailForSync(),
                    'phone' => $p->getPhoneForSync(),
                ];
            })
            ->values()
            ->all();

        try {
            $departments = \App\Models\Departemen::orderBy('nama')->get(['dep_id', 'nama']);
        } catch (\Throwable) {
            $departments = collect([
                ['dep_id' => 'IT', 'nama' => 'IT'],
                ['dep_id' => 'IPS', 'nama' => 'IPS'],
            ]);
        }

        return Inertia::render('users/create', [
            'availablePegawai' => $availablePegawai,
            'departments' => $departments,
            'canManagePayrollAccess' => $canManagePayrollAccess,
            'canManageMutuAccess' => $canManageMutuAccess,
            'canManageWebOfficialAccess' => $canManageWebOfficialAccess,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $canManagePayrollAccess = $request->user()?->canManagePayrollAccess() ?? false;
        $canManageMutuAccess = $request->user()?->canManageMutuAccess() ?? false;
        $canManageWebOfficialAccess = $request->user()?->canManageWebOfficialAccess() ?? false;

        User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'phone' => $request->validated('phone'),
            'simrs_nik' => $request->validated('simrs_nik'),
            'source' => $request->validated('simrs_nik') ? 'simrs' : 'manual',
            'role' => $request->validated('role'),
            'dep_id' => $request->validated('dep_id') ?: null,
            'can_access_payroll' => $canManagePayrollAccess
                ? (bool) $request->boolean('can_access_payroll')
                : false,
            'can_manage_mutu' => $canManageMutuAccess
                ? (bool) $request->boolean('can_manage_mutu')
                : false,
            'can_input_mutu' => $canManageMutuAccess
                ? (bool) $request->boolean('can_input_mutu')
                : false,
            'can_view_mutu_dashboard' => $canManageMutuAccess
                ? (bool) $request->boolean('can_view_mutu_dashboard')
                : false,
            'can_manage_web_official' => $canManageWebOfficialAccess
                ? (bool) $request->boolean('can_manage_web_official')
                : false,
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): Response
    {
        $canManagePayrollAccess = request()->user()?->canManagePayrollAccess() ?? false;
        $canManageMutuAccess = request()->user()?->canManageMutuAccess() ?? false;
        $canManageWebOfficialAccess = request()->user()?->canManageWebOfficialAccess() ?? false;

        try {
            $departments = \App\Models\Departemen::orderBy('nama')->get(['dep_id', 'nama']);
        } catch (\Throwable) {
            $departments = collect([
                ['dep_id' => 'IT', 'nama' => 'IT'],
                ['dep_id' => 'IPS', 'nama' => 'IPS'],
            ]);
        }

        return Inertia::render('users/edit', [
            'user' => $user->only([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'dep_id',
                'can_access_payroll',
                'can_manage_mutu',
                'can_input_mutu',
                'can_view_mutu_dashboard',
                'can_manage_web_official',
            ]),
            'departments' => $departments,
            'canManagePayrollAccess' => $canManagePayrollAccess,
            'canManageMutuAccess' => $canManageMutuAccess,
            'canManageWebOfficialAccess' => $canManageWebOfficialAccess,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $canManagePayrollAccess = $request->user()?->canManagePayrollAccess() ?? false;
        $canManageMutuAccess = $request->user()?->canManageMutuAccess() ?? false;
        $canManageWebOfficialAccess = $request->user()?->canManageWebOfficialAccess() ?? false;

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'role' => $request->validated('role'),
            'dep_id' => $request->validated('dep_id') ?: null,
        ];

        if ($canManagePayrollAccess) {
            $data['can_access_payroll'] = (bool) $request->boolean('can_access_payroll');
        }

        if ($canManageMutuAccess) {
            $data['can_manage_mutu'] = (bool) $request->boolean('can_manage_mutu');
            $data['can_input_mutu'] = (bool) $request->boolean('can_input_mutu');
            $data['can_view_mutu_dashboard'] = (bool) $request->boolean('can_view_mutu_dashboard');
        }

        if ($canManageWebOfficialAccess) {
            $data['can_manage_web_official'] = (bool) $request->boolean('can_manage_web_official');
        }

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }
}
