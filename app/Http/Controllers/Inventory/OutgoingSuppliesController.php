<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\tbl_branches;
use App\Models\tbl_incomingsupp;
use App\Models\tbl_outgoingsupp;
use App\Models\tbl_requestsupp;
use App\Models\tbl_suppcat;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OutgoingSuppliesController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving outgoing supplies info
    public function save(Request $data)
    {
        $table = tbl_outgoingsupp::where("supply_name", "!=", null);
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

        $get_amount = tbl_incomingsupp::where("supply_name", $data->supply_name['id'])
            ->whereBetween('incoming_date', [$date1, $date2]);
        $get_quantity = tbl_incomingsupp::where("supply_name", $data->supply_name['id'])
            ->whereBetween('incoming_date', [$date1, $date2]);

        $get_quantity->sum('quantity');
        $get_wov = ($get_amount->sum('amount') ? $get_amount->sum('amount') / $get_quantity->sum('quantity') : 0);

        $table_clone = clone $table;
        if ($table_clone->where("id", $data->id)->count() > 0) {
            //Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["category" => $data->category,
                    "supply_name" => $data->supply_name['id'],
                    "quantity" => $data->quantity,
                    "amount" => $get_wov * $data->quantity,
                    "requesting_branch" => $data->requesting_branch,
                    "outgoing_date" => date('Y-m-d h:i:s', strtotime($data->outgoing_date)),
                ]
            );
        } else {
            tbl_outgoingsupp::create($data->except(['supply_name', 'amount', 'outgoing_date']) +
                ['supply_name' => $data->supply_name['id'],
                    'amount' => $get_wov * $data->quantity,
                    'outgoing_date' => date('Y-m-d h:i:s', strtotime($data->outgoing_date)),
                ]);
        }
        return 0;
    }

    //For retrieving outgoing supplies info
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0") .
            ($t->branch ? " and requesting_branch=" . $t->branch : "");

        $table = tbl_outgoingsupp::with(["category", "supply_name", "requesting_branch"])
            ->whereRaw($where);

        if ($t->dateFrom && $t->dateUntil) {
            $table = $table->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
        }

        if ($t->search) { //If has value
            $table = $table->whereHas('supply_name', function ($q) use ($t) {
                $q->where('supply_name', 'like', "%" . $t->search . "%");
            });
        }

        $return = [];
        $row = 1;
        foreach ($table->orderBy("supply_name")->orderBy("outgoing_date")->get() as $key => $value) {

            //Get the total qty and amount from outgoing
            $get_amount = tbl_outgoingsupp::where("supply_name", $value->supply_name)
                ->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
            $get_quantity = tbl_outgoingsupp::where("supply_name", $value->supply_name)
                ->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);

            $temp = [];
            $temp['row'] = $row++;
            $temp['id'] = $value->id;
            $temp['status'] = $value->status;
            $temp['category'] = $value->category_details;
            $temp['outgoing_date'] = $value->outgoing_date;
            $temp['quantity'] = $value->quantity;
            $temp['requesting_branch'] = $value->requesting_branch_details;
            $temp['supply_name'] =
            DB::table("tbl_masterlistsupps")
                ->selectRaw(' CONCAT(supply_name , " ", COALESCE(description,"")) as supply_name, category, net_price, unit, description, id')
                ->where("id", $value->supply_name)->first();
            $temp['outgoing_amount'] = number_format($value->with_vat_price * $value->quantity, 2);
            //Get with vat
            if ($get_quantity->sum('quantity') > 0) {
                $temp['with_vat_price'] = number_format($get_amount->sum('amount') / $get_quantity->sum('quantity'), 2);
            } else {
                $temp['with_vat_price'] = number_format($value->net_price, 2);
            }
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }

    //For retrieving supply categories
    public function suppCat()
    {
        return tbl_suppcat::select(["supply_cat_name", "id"])->where("status", 1)->get();
    }

    //For retrieving supply names
    public function suppName(Request $t)
    {
        $data = DB::table("tbl_masterlistsupps")
            ->selectRaw(' CONCAT(supply_name , " ", COALESCE(description,"")) as supply_name, category, net_price, unit, description, id')
            ->where("category", (integer) $t->category)->where("status", 1)->get();
        return $data;
    }

    //For retrieving branch names
    public function branchName()
    {
        return tbl_branches::select(["branch_name", "id"])->where('type', 0)->where("status", 1)->get();
    }

    //For validating quantity
    public function validateQuantity(Request $request)
    {
        return tbl_incomingsupp::where('supply_name', $request->id)->sum('quantity') -
        tbl_outgoingsupp::where('supply_name', $request->id)->sum('quantity');
    }

    //For retrieving requests
    public function getRequest(Request $t)
    {
        $table = tbl_requestsupp::select(['branch', 'ref', 'user', 'request_date'])
            ->selectRaw('min(status) as status')
            ->groupBy(['branch', 'ref', 'user', 'request_date'])
            ->wherein('status', [1, 2])
            ->orderBy("request_date", "desc")
            ->get();
        $return = [];
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['row'] = $key + 1;
            $temp['branch'] = tbl_branches::where("id", $value->branch)->first()->branch_name;
            $temp['id'] = $value->ref;
            $temp['ref'] = $value->ref;
            $temp['status'] = $value->status;
            $temp['request_date'] = $value->request_date;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }

    //For retrieving requests info
    public function getRequested(Request $request)
    {
        $table = tbl_requestsupp::where('ref', $request->ref)
            ->where('deleted', 0)
            ->get();
        $return = [];
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['ref'] = $request->ref;
            $temp['supply_id'] = $value->supply_name;
            $temp['supply_name'] = $value->supply_name_details['supply_name'] . ' ' . $value->supply_name_details['description'];
            $temp['unit'] = $value->supply_name_details['supply_name'];
            $temp['quantity_requested'] = $value->quantity;
            $temp['quantity_available'] = $value->quantity_available;
            $temp['branch'] = tbl_branches::where("id", $value->branch)->first()->branch_name;
            $temp['user'] = User::where("id", $value->user)->first()->name;
            $temp['request_date'] = $value->request_date;
            $temp['status'] = $value->status;
            array_push($return, $temp);
        }
        return $return;
    }

    //For processing requests
    public function processRequest(Request $request)
    {
        foreach ($request->checked as $key => $value) {
            tbl_requestsupp::where(['supply_name' => $value['supply_id'], 'ref' => $value['ref']])->update(['status' => 2]);
        }
    }
}
