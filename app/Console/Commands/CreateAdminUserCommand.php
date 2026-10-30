<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'user:create-admin
                            {--name= : Nama lengkap admin}
                            {--email= : Email login admin}
                            {--password= : Password awal admin}';

    protected $description = 'Buat atau promote akun admin pertama untuk Portal Sifast';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Nama admin', 'Administrator'));
        $email = (string) ($this->option('email') ?: $this->ask('Email admin'));
        $password = (string) ($this->option('password') ?: $this->secret('Password admin'));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'name' => $name,
                'password' => $password,
                'role' => 'admin',
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $this->info("User existing dipromosikan menjadi admin: {$email}");

            return self::SUCCESS;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'source' => 'manual',
        ])->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $this->info("Admin berhasil dibuat: {$email}");

        return self::SUCCESS;
    }
}
