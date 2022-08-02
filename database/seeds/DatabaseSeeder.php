<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(BranchesSeeder::class);
        $this->call(CategoriesSeeder::class);
        $this->call(ProdCatSeeder::class);
        $this->call(ProdSubCatSeeder::class);
        $this->call(AccountSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(RolesPermissionsSeeder::class);
    }
}
