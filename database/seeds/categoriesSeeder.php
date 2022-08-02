<?php

use App\Models\tbl_suppcat;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        tbl_suppcat::insert([
            ['id' => 1, 'supply_cat_name' => 'Dry Goods', 'status' => '1', ],
            ['id' => 2, 'supply_cat_name' => 'Cleaning Materials', 'status' => '1', ],
            ['id' => 3, 'supply_cat_name' => 'Non-Food', 'status' => '1', ],
            ['id' => 4, 'supply_cat_name' => 'Utensils', 'status' => '1', ],
            ['id' => 5, 'supply_cat_name' => 'Office', 'status' => '1', ],
            ['id' => 6, 'supply_cat_name' => 'Mamou Dry Goods', 'status' => '1', ],
            ['id' => 7, 'supply_cat_name' => 'Mamou Non-Food', 'status' => '1', ],
            ['id' => 8, 'supply_cat_name' => 'Kitchen Supplies', 'status' => '1', ],
        ]);
    }
}
