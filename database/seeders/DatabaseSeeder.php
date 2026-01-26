<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create super admin role first
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);

        // Create admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Assign super admin role
        $admin->assignRole('super_admin');

        $this->call([
            RoleSeeder::class,
            OrganizationSeeder::class,
            UserSeeder::class,
        ]);
    }
}
