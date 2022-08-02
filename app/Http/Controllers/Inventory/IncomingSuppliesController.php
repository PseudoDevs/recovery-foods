<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\tbl_incomingsupp;
use App\Models\tbl_masterlistsupp;
use App\Models\tbl_suppcat;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomingSuppliesController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving incoming supplies
    public function save(Request $data)
    {
        $table = tbl_incomingsupp::where("supply_name", "!=", null);
        $table_clone = clone $table;

        if ($table_clone->where("id", $data->id)->count() > 0) {
            //Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["category" => $data->category,
                    "supply_name" => $data->supply_name['id'],
                    "quantity" => $data->quantity,
                    "amount" => $data->amount,
                    "incoming_date" => date("Y-m-d h:i:s", strtotime($data->incoming_date . ' ' . date("h:i:s"))),
                ]
            );
        } else {
            tbl_incomingsupp::create($data->except(['supply_name', 'incoming_date']) +
                ['supply_name' => $data->supply_name['id'], 'incoming_date' => date("Y-m-d h:i:s", strtotime($data->incoming_date . ' ' . date("h:i:s")))]);
        }
        return 0;
    }

    //For retrieving incoming supplies
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0");
        $table = tbl_incomingsupp::with(["category", "supply_name", 'supplier'])->whereRaw($where);

        if ($t->dateFrom && $t->dateUntil) {
            $table = $table->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
        }

        if ($t->search) { //If has value
            $table = $table->whereHas('supply_name', function ($q) use ($t) {
                $q->where('supply_name', 'like', "%" . $t->search . "%");
            });
        }

        $return = [];
        $row = 1;
        foreach ($table->orderBy("supply_name")->orderBy("incoming_date")->get() as $key => $value) {

            //Get the total qty and amount from incoming
            $get_amount = tbl_incomingsupp::where("supply_name", $value->supply_name)
                ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
            $get_quantity = tbl_incomingsupp::where("supply_name", $value->supply_name)
                ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);

            $temp = [];
            $temp['row'] = $row++;
            $temp['id'] = $value->id;
            $temp['status'] = $value->status;
            $temp['amount'] = $value->amount;
            $temp['category'] = $value->category_details;
            $temp['incoming_date'] = $value->incoming_date;
            $temp['quantity'] = $value->quantity;
            $temp['quantity_amount'] = $value->quantity_amount;
            $temp['quantity_difference'] = $value->quantity_difference;
            $temp['supply_name'] =
            DB::table("tbl_masterlistsupps")
                ->selectRaw(' CONCAT(supply_name , " ", COALESCE(description,"")) as supply_name, category, net_price, unit, description, id')
                ->where("id", $value->supply_name)->first();
            $temp['supplier'] = $value->supplier_details;
            $temp['amount'] = number_format($value->amount, 2);
            $wvat = tbl_masterlistsupp::where("id", $value->id);
            //Get with vat
            if ($get_quantity->sum('quantity') > 0) {
                $temp['with_vat_price'] =  number_format($get_amount->sum('amount') / $get_quantity->sum('quantity'), 2);
            } else {
                $temp['with_vat_price'] = number_format($value->net_price, 2);
            }
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }

    //For retreiving supply categories
    public function suppCat()
    {
        return tbl_suppcat::select(["supply_cat_name", "id"])->where("status", 1)->get();
    }

    //For retrieving supply names
    public function suppName(Request $t)
    {
        $data = DB::table("tbl_masterlistsupps")
            ->selectRaw(' CONCAT(supply_name , " ", COALESCE(description,"")) as supply_name, category, net_price, unit, description, id')
            ->where("supplier", (integer) $t->supplier)->where("status", 1)
            ->where("category", (integer) $t->category)->where("status", 1)->get();
        return $data;
    }

    //For retrieving suppliers info
    public function suppliers()
    {
        $data = DB::table("tbl_supplists")
            ->selectRaw(' CONCAT(supplier_name , " (", COALESCE(description,"") ,")") as supplier_name, phone_number, contact_person, address, description, id')
            ->where("status", 1)
            ->get();
        return $data;
    }
}
