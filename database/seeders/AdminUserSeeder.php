<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create or update the admin account.
     *
     * The password stays out of the repository. Set ADMIN_PASSWORD before seeding.
     */
    public function run(): void
    {
        $username = (string) env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->command?->warn('ADMIN_PASSWORD kosong. Akun admin tidak dibuat.');

            return;
        }

        User::query()->updateOrCreate(
            ['username' => $username],
            ['password' => $password],
        );
    }
}
