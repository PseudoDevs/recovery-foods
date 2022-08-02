<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\tbl_supplist;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SuppliersListController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving suppliers info
    public function save(Request $data)
    {
        $table = tbl_supplist::where("status", "!=", null);

        //Check if supplier name is already existing
        $table_clone = clone $table; //Get all items from supplist
        if ($table_clone
            ->where("supplier_name", $data->supplier_name) //Filter using name
            ->where("description", $data->description) //Filter using description
            ->where("id", "!=", $data->id) //Filter if id is not selected
            ->count() > 0) {
            return 1;
        }

        $table_clone = clone $table;
        if ($table_clone->where("id", $data->id)->count() > 0) {
            //Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["status" => $data->status,
                    "supplier_name" => $data->supplier_name,
                    "description" => $data->description,
                    "phone_number" => $data->phone_number,
                    "contact_person" => $data->contact_person,
                    "address" => $data->address,
                ]
            );
        } else {
            tbl_supplist::create($data->all());
        }
        return 0;
    }

    //For retrieving suppliers info
    public function get(Request $t)
    {
        $table = tbl_supplist::where("status", "!=", null);
        if ($t->search) { //If has value
            $table = $table->where("supplier_name", "like", "%" . $t->search . "%");
        }

        $return = [];
        foreach ($table->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $key + 1;
            $temp['id'] = $value->id;
            $temp['status'] = $value->status;
            $temp['address'] = $value->address;
            $temp['contact_person'] = $value->contact_person;
            $temp['description'] = $value->description;
            $temp['phone_number'] = $value->phone_number;
            $temp['supplier_name'] = $value->supplier_name;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }
}
