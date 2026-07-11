<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Rules\AllowedEmailDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower((string) config('admin.seed_admin.email'));
        $name = (string) config('admin.seed_admin.name');
        $password = (string) config('admin.seed_admin.password');

        if ($email === '' || $name === '' || $password === '') {
            $this->command->warn('Admin not seeded; set SEED_ADMIN_NAME, EMAIL, and PASSWORD.');

            return;
        }

        Validator::make(compact('email'), [
            'email' => ['required', 'email', new AllowedEmailDomain],
        ])->validate();

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->syncRoles([Role::Admin->value]);
    }
}
