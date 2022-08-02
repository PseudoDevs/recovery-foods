<?php

use App\Models\tbl_branches;
use Illuminate\Database\Seeder;

class BranchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        tbl_branches::insert([
            ['id' => 1, 'status' => '1', 'branch_name' => 'Crossroads BGC', 'location' => 'Bonifacio Global City, Taguig City', 'phone_number' => '(0912) 345-6789', 'type' => '0', 'email_add' => 'crossroadsbgc@gmail.com', 'branch_image' => ''],
            ['id' => 2, 'status' => '1', 'branch_name' => 'BGC Stopover', 'location' => 'Bonifacio Global City, Taguig City', 'phone_number' => '(0912) 345-6788', 'type' => '0', 'email_add' => 'bgcstopover@gmail.com', 'branch_image' => ''],
            ['id' => 3, 'status' => '1', 'branch_name' => 'Molito Alabang', 'location' => 'Alabang, Muntinlupa City', 'phone_number' => '(0912) 345-6787', 'type' => '0', 'email_add' => 'molitoalabang@gmail.com', 'branch_image' => ''],
            ['id' => 4, 'status' => '1', 'branch_name' => 'Secret Kitchen Quezon City', 'location' => 'Quezon City', 'phone_number' => '(0912) 345-6786', 'type' => '0', 'email_add' => 'secretkitchenqc@gmail.com', 'branch_image' => ''],
            ['id' => 5, 'status' => '1', 'branch_name' => 'Warehouse', 'location' => 'Carmona, Cavite', 'phone_number' => '(123) 456-789', 'type' => '1', 'email_add' => 'warehouse@gmail.com', 'branch_image' => ''],
            ['id' => 6, 'status' => '1', 'branch_name' => 'Arkipelago Makati', 'location' => 'Makati', 'phone_number' => '(0912) 345-6785', 'type' => '0', 'email_add' => 'arkipelagomakati@gmail.com', 'branch_image' => ''],
            ['id' => 7, 'status' => '1', 'branch_name' => 'Grab-Kitchen Malate', 'location' => 'Malate', 'phone_number' => '(0912) 345-6784', 'type' => '0', 'email_add' => 'grabkitchenmalate@gmail.com', 'branch_image' => ''],
            ['id' => 8, 'status' => '1', 'branch_name' => 'Megamall', 'location' => 'Mandaluyong, Manila', 'phone_number' => '(0912) 345-6783', 'type' => '0', 'email_add' => 'megamall@gmail.com', 'branch_image' => ''],
        ]);
    }
}
