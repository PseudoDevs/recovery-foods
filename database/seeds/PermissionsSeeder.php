<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::insert([
            ['id' => 1, 'name' => 'Access POS', 'description' => 'manage POS', 'guard_name' => 'api'],
            ['id' => 2, 'name' => 'Access Dashboard', 'description' => 'manage dashboard', 'guard_name' => 'api'],
            ['id' => 3, 'name' => 'Access Branches', 'description' => 'manage branches', 'guard_name' => 'api'],
            ['id' => 4, 'name' => 'Access Categories', 'description' => 'manage categories', 'guard_name' => 'api'],
            ['id' => 5, 'name' => 'Access Inventory', 'description' => 'manage inventory', 'guard_name' => 'api'],
            ['id' => 6, 'name' => 'Access Products', 'description' => 'manage products', 'guard_name' => 'api'],
            ['id' => 7, 'name' => 'Access Suppliers', 'description' => 'manage suppliers', 'guard_name' => 'api'],
            ['id' => 8, 'name' => 'Access Reports', 'description' => 'manage reports', 'guard_name' => 'api'],
            ['id' => 9, 'name' => 'Access User Accounts', 'description' => 'manage user accounts', 'guard_name' => 'api'],
            ['id' => 10, 'name' => 'Access Settings', 'description' => 'manage settings', 'guard_name' => 'api'],
            ['id' => 11, 'name' => 'Access Branch Inventory', 'description' => 'manage branch inventory', 'guard_name' => 'api'],
        ]);
    }
}
