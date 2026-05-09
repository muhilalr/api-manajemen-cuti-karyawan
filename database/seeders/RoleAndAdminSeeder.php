<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole    = Role::firstOrCreate(['name' => 'admin']);
        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
                'provider' => 'local',
            ]
        );

        $admin->assignRole($adminRole);
    }
}
