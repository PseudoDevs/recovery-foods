<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_prodcat;
use App\Models\tbl_prodsubcat;
use App\Models\tbl_vat;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MasterlistProductsController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For saving masterlist products info
    public function save(Request $data)
    {
        $table = tbl_masterlistprod::where("status", "!=", null);

        //Check if product name is already existing
        $table_clone = clone $table; //Get all items from masterlistprod
        if ($table_clone
            ->where("product_name", $data->product_name) //Filter using name
            ->where("description", $data->description) //Filter using description
            ->where("exp_date", $data->exp_date) //Filter using expiration date
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
                    "category" => $data->category,
                    "vat" => $data->vat,
                    "vatable" => 1,
                    "sub_category" => $data->sub_category,
                    "product_name" => $data->product_name,
                    "description" => $data->description,
                    "price" => $data->price,
                    "critical_limit" => $data->critical_limit,
                    "exp_date" => $data->exp_date,
                ]
            );
        } else {
            tbl_masterlistprod::create($data->all() + ['vatable' => 1]);
        }
        return 0;
    }

    //For retrieving masterlist products info
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0") .
            ($t->subcategory ? " and sub_category=" . $t->subcategory : "");
            
        $table = tbl_masterlistprod::with("category", "sub_category")
            ->selectRaw("*, case when exp_date is null THEN null when datediff(exp_date,current_timestamp) > 7 THEN null ELSE datediff(exp_date,current_timestamp) end as days")
            ->whereRaw($where);

        if ($t->search) { //If has value
            $table = $table->where("product_name", "like", "%" . $t->search . "%");
        }

        $return = [];
        $row = 1;
        foreach ($table->orderByRaw("  days desc ")->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $row++;
            $temp['id'] = $value->id;
            $temp['status'] = $value->status;
            $temp['category'] = $value->category_details;
            $temp['description'] = $value->description;
            $temp['diff_quantity'] = $value->diff_quantity;
            $temp['days'] = $value->days;
            $temp['critical_limit'] = $value->critical_limit;
            $temp['exp_date'] = $value->exp_date;
            $temp['without_vat'] = number_format($value->without_vat, 2);
            $temp['vat'] = $value->vat;
            $temp['unit_price'] = number_format($value->unit_price, 2);
            $temp['format_price'] = number_format($value->price, 2);
            $temp['price'] = $value->price;
            $temp['product_name'] = $value->product_name;
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
}
