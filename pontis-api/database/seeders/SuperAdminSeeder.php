<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotente: garantiza el login conocido admin@pontis.com / password
        // incluso si el registro ya existe con otros valores.
        User::updateOrCreate(
            ['email' => 'admin@pontis.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
