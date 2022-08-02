<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::insert([
            ['id' => 1, 'name' => 'Admin', 'description' => '', 'guard_name' => 'api'],
            ['id' => 2, 'name' => 'Cashier', 'description' => '', 'guard_name' => 'api'],
            ['id' => 3, 'name' => 'Stockman', 'description' => '', 'guard_name' => 'api'],
            ['id' => 4, 'name' => 'Production Assistant', 'description' => '', 'guard_name' => 'api'],
            ['id' => 5, 'name' => 'Supervisor', 'description' => '', 'guard_name' => 'api'],
            ['id' => 6, 'name' => 'Access Reports', 'description' => '', 'guard_name' => 'api'],
        ]);
    }
}
