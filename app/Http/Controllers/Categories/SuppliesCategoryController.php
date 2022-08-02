<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\tbl_suppcat;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SuppliesCategoryController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving supply categories
    public function save(Request $data)
    {
        $table = tbl_suppcat::where("status", "!=", null);

        //Check if supply category name is already existing
        $table_clone = clone $table; //Get all items from suppcat
        if ($table_clone
            ->where("supply_cat_name", $data->supply_cat_name) //Filter using supply category name
            ->where("id", "!=", $data->id) //Filter if id is not selected
            ->count() > 0) {
            return 1;
        }

        $table_clone = clone $table;
        if ($table_clone->where("id", $data->id)->count() > 0) {
            // Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["status" => $data->status,
                    "supply_cat_name" => $data->supply_cat_name,
                ]
            );
        } else {
            tbl_suppcat::create($data->all());
        }
        return 0;
    }

    //For retrieving supply categories
    public function get(Request $t)
    {
        $table = tbl_suppcat::where("status", "!=", null);
        if ($t->search) { // If has value
            $table = $table->where("supply_cat_name", "like", "%" . $t->search . "%")->get();
        } else {
            $table = $table->get();
        }

        $return = [];
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['row'] = $key + 1;
            $temp['id'] = $value->id;
            $temp['status'] = $value->status;
            $temp['supply_cat_name'] = $value->supply_cat_name;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }
}
