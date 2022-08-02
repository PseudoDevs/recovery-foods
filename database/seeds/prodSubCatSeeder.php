<?php

use App\Models\tbl_prodsubcat;
use Illuminate\Database\Seeder;

class ProdSubCatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        tbl_prodsubcat::insert([
            ['id' => 1, 'prod_sub_cat_name' => 'Beverages', 'status' => '1'],
            ['id' => 2, 'prod_sub_cat_name' => 'Sweets', 'status' => '1'],
            ['id' => 3, 'prod_sub_cat_name' => 'Noodles', 'status' => '1'],
            ['id' => 4, 'prod_sub_cat_name' => 'Main', 'status' => '1'],
            ['id' => 5, 'prod_sub_cat_name' => 'Caldo', 'status' => '1'],
            ['id' => 6, 'prod_sub_cat_name' => 'Sandwich', 'status' => '1'],
            ['id' => 7, 'prod_sub_cat_name' => 'Extras', 'status' => '1'],
            ['id' => 8, 'prod_sub_cat_name' => 'Appetizers', 'status' => '1'],
        ]);
    }
}
