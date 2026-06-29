<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@operalyn.com'],
            [
                'name'               => 'Operalyn Admin',
                'password'           => Hash::make('Admin@123456'),
                'role'               => 'admin',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );

        $this->command->info('Admin created: admin@operalyn.com / Admin@123456');
        $this->command->warn('Change this password immediately after first login!');
    }
}
