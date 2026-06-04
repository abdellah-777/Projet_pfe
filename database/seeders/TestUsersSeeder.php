<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Owner User', 'email' => 'owner@test.com', 'role' => 'owner'],
            ['name' => 'Manager User', 'email' => 'manager@test.com', 'role' => 'manager'],
            ['name' => 'Developer User', 'email' => 'developer@test.com', 'role' => 'developer'],
            ['name' => 'Viewer User', 'email' => 'viewer@test.com', 'role' => 'viewer'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}