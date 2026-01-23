<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create panel permissions if not exist
        Permission::firstOrCreate(['name' => 'panel_admin']);
        Permission::firstOrCreate(['name' => 'panel_user']);

        // Assign permissions to roles
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo('panel_admin');

        $userRole = Role::findByName('user');
        $userRole->givePermissionTo('panel_user');

        // Create org_admin user with admin role
        $orgAdmin = User::firstOrCreate([
            'email' => 'org_admin@example.com',
        ], [
            'name' => 'Org Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $orgAdmin->assignRole('admin');

        // Create test user with user role
        $testUser = User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $testUser->assignRole('user');
    }
}