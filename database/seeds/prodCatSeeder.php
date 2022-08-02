<?php

use App\Models\tbl_prodcat;
use Illuminate\Database\Seeder;

class ProdCatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        tbl_prodcat::insert([
            ['id' => 1, 'product_cat_name' => 'Beer 4', 'status' => '1'],
            ['id' => 2, 'product_cat_name' => 'Coffee & Tea 3', 'status' => '1'],
            ['id' => 3, 'product_cat_name' => 'Dessert 4 + 2', 'status' => '1'],
            ['id' => 4, 'product_cat_name' => 'Drinks 7', 'status' => '1'],
            ['id' => 5, 'product_cat_name' => 'Noodles', 'status' => '1'],
            ['id' => 6, 'product_cat_name' => 'Egg Delete 22', 'status' => '1'],
            ['id' => 7, 'product_cat_name' => 'Meat Only 37', 'status' => '1'],
            ['id' => 8, 'product_cat_name' => 'Ala Eh Medley 3', 'status' => '1'],
            ['id' => 9, 'product_cat_name' => 'New Dish', 'status' => '1'],
            ['id' => 10, 'product_cat_name' => 'Rice Toppings 40', 'status' => '1'],
            ['id' => 11, 'product_cat_name' => 'Rice Delete 37', 'status' => '1'],
            ['id' => 12, 'product_cat_name' => 'Sandwiches 4', 'status' => '1'],
            ['id' => 13, 'product_cat_name' => 'Sides 31', 'status' => '1'],
            ['id' => 14, 'product_cat_name' => 'Soda 6', 'status' => '1'],
            ['id' => 15, 'product_cat_name' => 'Start Em Up 6', 'status' => '1'],
            ['id' => 16, 'product_cat_name' => 'Summer Shakes 4', 'status' => '1'],
        ]);
    }
}
