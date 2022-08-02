<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\tbl_incomingprod;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_prodcat;
use App\Models\tbl_prodsubcat;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomingProductsController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving incoming products info
    public function save(Request $data)
    {
        $table = tbl_incomingprod::where("product_name", "!=", null);
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
                    "incoming_date" => $data->incoming_date,
                ]
            );
        } else {
            tbl_incomingprod::create($data->except('product_name') + ['product_name' => $data->product_name['id'], 'amount' => tbl_masterlistprod::where("id", $data->product_name['id'])->first()->price * $data->quantity]);
        }
        return 0;
    }

    //For retrieving incoming products info
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0") .
            ($t->subcategory ? " and sub_category=" . $t->subcategory : "");
            
        $table = tbl_incomingprod::with(["category", "sub_category", "product_name"])
            ->whereRaw($where);

        if ($t->dateFrom && $t->dateUntil) {
            $table = $table->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->dateFrom)), date("Y-m-d 23:59:59", strtotime($t->dateUntil))]);
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
            $temp['amount'] = number_format($value->amount, 2);
            $temp['incoming_date'] = $value->incoming_date;
            $temp['product_name'] =
            DB::table("tbl_masterlistprods")
                ->selectRaw(' CONCAT(product_name , " ", COALESCE(description,"")) as product_name, category, sub_category, price, description, id')
                ->where("id", $value->product_name)->first();
            $temp['price'] = number_format($value->product_name_details['price'], 2);
            $temp['quantity'] = $value->quantity;
            $temp['category'] = $value->category_details;
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
}
