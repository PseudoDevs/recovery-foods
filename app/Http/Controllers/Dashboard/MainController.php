<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_masterlistsupp;
use App\Models\tbl_pos;
use App\Models\tbl_purchaseord;
use App\Models\tbl_requestprod;
use App\Models\tbl_requestsupp;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MainController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For retrieving supplies count
    public function getSupp()
    {
        return tbl_masterlistsupp::where("status", 1)->get()->count("supply_name");
    }

    //For retrieving products count
    public function getProd()
    {
        return tbl_masterlistprod::where("status", 1)->get()->count("product_name");
    }

    //For retrieving purchase orders count
    public function getPO()
    {
        return tbl_purchaseord::where("invoice_number", "!=", null)->get()->count("invoice_number");
    }

    //For retrieving users count
    public function getUser()
    {
        return User::where("email", "!=", null)->get()->count("email");
    }

    //For retrieving supplies expiration date count
    public function getSuppExpDate()
    {
        $table = tbl_masterlistsupp::selectRaw("*, case when exp_date is null THEN 999 when datediff(exp_date,current_timestamp) > 8 THEN null ELSE datediff(exp_date,current_timestamp) end as days");
        $expired = 0;
        $warning = 0;
        $temp_array = [];

        foreach ($table->orderByRaw("  days desc ")->get() as $key => $value) {
            if ((integer) $value->days > 0 && (integer) $value->days < 8) {
                $warning += 1;
            }
            if ((integer) $value->days < 1) {
                array_push($temp_array, $value);
                $expired += 1;
            }
        }
        return ['expired_count' => $expired, 'warning_count' => $warning];
    }

    //For retrieving supplies expiration date count
    public function getProdExpDate()
    {
        $table = tbl_masterlistprod::selectRaw("*, case when exp_date is null THEN 999 when datediff(exp_date,current_timestamp) > 7 THEN null ELSE datediff(exp_date,current_timestamp) end as days");
        $expired = 0;
        $warning = 0;
        $temp_array = [];

        foreach ($table->orderByRaw("  days desc ")->get() as $key => $value) {
            if ((integer) $value->days > 0 && (integer) $value->days < 8) {
                $warning += 1;
            }
            if ((integer) $value->days < 1) {
                array_push($temp_array, $value);
                $expired += 1;
            }
        }
        return ['expired_count' => $expired, 'warning_count' => $warning];
    }

    //For retrieving supplies request count
    public function getSuppRequests()
    {
        return tbl_requestsupp::where("status", "=", 1)->count(DB::raw('DISTINCT ref'));
    }

    //For retrieving products request count
    public function getProdRequests()
    {
        return tbl_requestprod::where("status", "=", 1)->count(DB::raw('DISTINCT ref'));
    }

    //For making sales graph
    public function getSalesGraph(Request $t)
    {
        $return = [];
        $months = [];

        if ($t->month == "NaN") {
            $return['month'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            array_push($months, tbl_pos::whereMonth('created_at', 1)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 2)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 3)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 4)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 5)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 6)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 7)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 8)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 9)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 10)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 11)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            array_push($months, tbl_pos::whereMonth('created_at', 12)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            $return['data'] = $months;
        } else {
            $return['month'] = [date("F", strtotime($t->month . '/01/2020'))];
            array_push($months, tbl_pos::whereMonth('created_at', $t->month)->whereYear('created_at', $t->year)->where('branch', $t->branch)->sum('sub_total_discounted'));
            $return['data'] = $months;
        }
        return $return;
    }

    //For making products graph
    public function getProductsGraph(Request $t)
    {
        $months = [];
        $data = [];

        if ($t->month == "NaN") {
            $data = tbl_pos::query()
                ->with(['product_name'])
                ->selectRaw(' sum(quantity) as quantity, max(product_name) as product_name  ')
                ->whereYear('created_at', $t->year)
                ->groupBy(['product_name'])
                ->where('branch', $t->branch)->get()
                ->take(5);
        } else {
            $data = tbl_pos::query()
                ->with(['product_name'])
                ->selectRaw(' sum(quantity) as quantity, max(product_name) as product_name  ')
                ->whereMonth('created_at', $t->month)
                ->whereYear('created_at', $t->year)
                ->groupBy(['product_name'])
                ->where('branch', $t->branch)->get()
                ->take(5);
        }

        //Find all id in masterlist product, then stack the product names
        $name = [];
        $sold = [];

        foreach ($data as $key => $value) {
            array_push($name, tbl_masterlistprod::where('id', $value->product_name)->first()->product_name);
            array_push($sold, $value->quantity);

        }
        return ['name' => $name, 'sold' => $sold];
    }
}
