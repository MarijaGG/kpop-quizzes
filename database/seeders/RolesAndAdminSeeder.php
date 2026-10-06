<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'user', 'label' => 'User'],
            ['name' => 'admin', 'label' => 'Administrator'],
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r['name']], ['label' => $r['label']]);
        }

        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (! $adminEmail || ! $adminPassword) {
            $this->command?->warn('Admin account was not created: set ADMIN_EMAIL and ADMIN_PASSWORD in the environment first.');
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator',
                'password' => Hash::make($adminPassword),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && ! $admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->roles()->attach($adminRole->id);
        }
    }
}
