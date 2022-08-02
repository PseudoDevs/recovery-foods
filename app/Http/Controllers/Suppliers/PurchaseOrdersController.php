<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\tbl_purchaseord;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrdersController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving purchase order
    public function save(Request $data)
    {
        $table = tbl_purchaseord::where("supplier_name", "!=", null);

        //Check if invoice number is already existing
        $table_clone = clone $table; //Get all items from supplist
        if ($table_clone
            ->where("invoice_number", $data->invoice_number) //Filter using name
            ->where("id", "!=", $data->id) //Filter if id is not selected
            ->count() > 0) {
            return 1;
        }

        $table_clone = clone $table;
        if ($table_clone->where("id", $data->id)->count() > 0) {
            // Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["invoice_number" => $data->invoice_number,
                    "supplier_name" => $data->supplier_name,
                    "amount" => $data->amount,
                ]
            );
        } else {
            tbl_purchaseord::create($data->all());
        }
        return 0;
    }

    //For retrieving purchase order info
    public function get(Request $t)
    {
        $where = ($t->supplier ? "supplier_name != 0 and supplier_name=" . $t->supplier : "supplier_name != 0");

        $table = tbl_purchaseord::with("supplier_name")
            ->whereRaw($where);

        if ($t->dateFrom && $t->dateUntil) {
            $table = $table->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
        }

        if ($t->search) { //If has value
            $table = $table->whereHas('supplier_name', function ($q) use ($t) {
                $q->where('invoice_number', 'like', "%" . $t->search . "%");
            });
        }

        $return = [];
        foreach ($table->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $key + 1;
            $temp['id'] = $value->id;
            $temp['amount'] = $value->amount;
            $temp['format_amount'] = $value->format_amount;
            $temp['incoming_date'] = $value->incoming_date;
            $temp['invoice_number'] = $value->invoice_number;
            $temp['supplier_name'] = $value->supplier_name_details;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);

    }

    //For retrieving supplier names
    public function suppName()
    {
        $data = DB::table("tbl_supplists")
            ->selectRaw(' CONCAT(supplier_name , " (", COALESCE(description,"") ,")") as supplier_name, phone_number, contact_person, address, description, id')
            ->where("status", 1)
            ->get();
        return $data;
    }
}
