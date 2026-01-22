<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // Create 2 root organizations
        $root1 = Organization::create(['name' => 'Root Org 1']);
        $root2 = Organization::create(['name' => 'Root Org 2']);

        // Create 1 child for each root
        Organization::create([
            'name' => 'Child Org 1-1',
            'parent_id' => $root1->id,
        ]);
        Organization::create([
            'name' => 'Child Org 2-1',
            'parent_id' => $root2->id,
        ]);
    }
}
