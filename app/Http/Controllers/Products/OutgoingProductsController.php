<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\tbl_branches;
use App\Models\tbl_incomingprod;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_outgoingprod;
use App\Models\tbl_prodcat;
use App\Models\tbl_prodsubcat;
use App\Models\tbl_requestprod;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OutgoingProductsController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving outgoing products info
    public function save(Request $data)
    {
        $table = tbl_outgoingprod::where("product_name", "!=", null);
        $table_clone = clone $table;

        if ($table_clone->where("id", $data->id)->count() > 0) {
            //Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["category" => $data->category,
                    "sub_category" => $data->sub_category,
                    "product_name" => $data->product_name['id'],
                    "quantity" => $data->quantity,
                    "amount" => tbl_masterlistprod::where("id", $data->product_name['id'])->first()->price * $data->quantity,
                    "requesting_branch" => $data->requesting_branch,
                    "outgoing_date" => date('Y-m-d', strtotime($data->outgoing_date)),
                ]
            );
        } else {
            tbl_outgoingprod::create($data->except(['product_name']) +
                ['product_name' => $data->product_name['id'], "amount" => tbl_masterlistprod::where("id", $data->product_name['id'])->first()->price * $data->quantity]);
        }
        return 0;
    }

    //For retrieving outgoing products info
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0") .
            ($t->subcategory ? " and sub_category=" . $t->subcategory : "") .
            ($t->branch ? " and requesting_branch=" . $t->branch : "");
        $table = tbl_outgoingprod::with(["category", "sub_category", "product_name", "requesting_branch"])
            ->whereRaw($where)
            ->where("product_name", "!=", null);

        if ($t->dateFrom && $t->dateUntil) {
            $table = $table->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
        }

        if ($t->search) { //If has value
            $table = $table->whereHas('product_name', function ($q) use ($t) {
                $q->where('product_name', 'like', "%" . $t->search . "%");
            });
        }

        $return = [];
        foreach ($table->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $key + 1;
            $temp['id'] = $value->id;
            $temp['status'] = $value->status;
            $temp['category'] = $value->category_details;
            $temp['outgoing_amount'] = number_format($value->outgoing_amount, 2);
            $temp['outgoing_date'] = $value->outgoing_date;
            $temp['product_name'] =
            DB::table("tbl_masterlistprods")
                ->selectRaw(' CONCAT(product_name , " ", COALESCE(description,"")) as product_name, category, sub_category, price, description, id')
                ->where("id", $value->product_name)->first();
            $temp['quantity'] = $value->quantity;
            $temp['quantity_diff'] = $value->quantity_diff;
            $temp['requesting_branch'] = $value->requesting_branch_details;
            $temp['sub_category'] = $value->sub_category_details;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }

    //For retrieving product categories
    public function prodCat()
    {
        return tbl_prodcat::select(["product_cat_name", "id"])->where("status", 1)->get();
    }

    //For retrieving product subcategories
    public function prodSubCat()
    {
        return tbl_prodsubcat::select(["prod_sub_cat_name", "id"])->where("status", 1)->get();
    }

    //For retrieving product names
    public function prodName(Request $t)
    {
        $data = DB::table("tbl_masterlistprods")
            ->selectRaw(' CONCAT(product_name , " ", COALESCE(description,"")) as product_name, category, sub_category, price, description, id')
            ->where("category", (integer) $t->category)
            ->where("sub_category", (integer) $t->sub_category)
            ->where("status", 1)->get();
        return $data;
    }

    //For retrieving branch names
    public function branchName()
    {
        return tbl_branches::select(["branch_name", "id"])
            ->where(["status" => 1, 'type' => 0])->get();
    }

    //For validating quantity
    public function validateQuantity(Request $request)
    {
        return tbl_incomingprod::where('product_name', $request->id)->sum('quantity') -
        tbl_outgoingprod::where("product_name", $request->id)->sum('quantity');
    }

    //For retrieving product requests
    public function getRequest(Request $t)
    {
        $table = tbl_requestprod::select(['branch', 'ref', 'user', 'request_date'])
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

    //For retrieving product requests
    public function getRequested(Request $request)
    {
        $table = tbl_requestprod::where('ref', $request->ref)
            ->where('deleted', 0)
            ->get();
        $return = [];
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['ref'] = $request->ref;
            $temp['product_id'] = $value->product_name;
            $temp['product_name'] = $value->product_name_details['product_name'] . ' ' . $value->product_name_details['description'];
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

    //For retrieving processing requests
    public function processRequest(Request $request)
    {
        foreach ($request->checked as $key => $value) {
            tbl_requestprod::where(['product_name' => $value['product_id'], 'ref' => $value['ref']])->update(['status' => 2]);
        }
    }
}
