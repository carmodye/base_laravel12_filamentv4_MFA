<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
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

        // Create organization permissions
        Permission::firstOrCreate(['name' => 'view_organization']);
        Permission::firstOrCreate(['name' => 'view_any_organization']);
        Permission::firstOrCreate(['name' => 'create_organization']);
        Permission::firstOrCreate(['name' => 'update_organization']);
        Permission::firstOrCreate(['name' => 'delete_organization']);
        Permission::firstOrCreate(['name' => 'delete_any_organization']);
        Permission::firstOrCreate(['name' => 'force_delete_organization']);
        Permission::firstOrCreate(['name' => 'force_delete_any_organization']);
        Permission::firstOrCreate(['name' => 'restore_organization']);
        Permission::firstOrCreate(['name' => 'restore_any_organization']);
        Permission::firstOrCreate(['name' => 'replicate_organization']);
        Permission::firstOrCreate(['name' => 'reorder_organization']);

        // Assign permissions to roles
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo('panel_admin');
        $adminRole->givePermissionTo([
            'view_organization',
            'view_any_organization',
            'create_organization',
            'update_organization',
            'delete_organization',
            'delete_any_organization',
            'force_delete_organization',
            'force_delete_any_organization',
            'restore_organization',
            'restore_any_organization',
            'replicate_organization',
            'reorder_organization',
        ]);

        $userRole = Role::findByName('user');
        $userRole->givePermissionTo('panel_user');
        $userRole->givePermissionTo([
            'view_organization',
            'view_any_organization',
        ]);

        // Get root organizations
        $dev1 = Organization::where('name', 'dev1')->first();
        $qa2 = Organization::where('name', 'qa2')->first();

        // Create dev1admin
        $dev1Admin = User::firstOrCreate([
            'email' => 'dev1admin@example.com',
        ], [
            'name' => 'Dev1 Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $dev1Admin->assignRole('admin');
        $dev1Admin->organizations()->sync([$dev1->id]);
        $dev1Admin->update(['email_verified_at' => now()]);

        // Create dev1user
        $dev1User = User::firstOrCreate([
            'email' => 'dev1user@example.com',
        ], [
            'name' => 'Dev1 User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        // $dev1User->assignRole('user');
        $dev1User->givePermissionTo(['panel_admin', 'panel_user', 'view_organization', 'view_any_organization']);
        $dev1User->organizations()->sync([$dev1->id]);
        $dev1User->update(['email_verified_at' => now()]);

        // Create qa2admin
        $qa2Admin = User::firstOrCreate([
            'email' => 'qa2admin@example.com',
        ], [
            'name' => 'Qa2 Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $qa2Admin->assignRole('admin');
        $qa2Admin->organizations()->sync([$qa2->id]);
        $qa2Admin->update(['email_verified_at' => now()]);

        // Create qa2user
        $qa2User = User::firstOrCreate([
            'email' => 'qa2user@example.com',
        ], [
            'name' => 'Qa2 User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        // $qa2User->assignRole('user');
        $qa2User->givePermissionTo(['panel_admin', 'panel_user', 'view_organization', 'view_any_organization']);
        $qa2User->organizations()->sync([$qa2->id]);
        $qa2User->update(['email_verified_at' => now()]);

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