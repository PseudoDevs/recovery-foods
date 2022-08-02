<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_outgoingprod;
use App\Models\tbl_pos;
use App\Models\tbl_prodcat;
use App\Models\tbl_prodsubcat;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ProductsListController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For retrieving products list
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0") .
            ($t->subcategory ? " and sub_category=" . $t->subcategory : "");

        $table = tbl_outgoingprod::with(["category", "sub_category", "product_name"])
            ->whereRaw($where)
            ->whereHas("requesting_branch", function ($q) {
                $q->where("id", auth()->user()->branch);
            })->whereHas("product_name", function ($q1) use ($t) {
            $q1->where("product_name", "like", "%" . $t->search . "%");
        })->selectRaw('product_name, category, sub_category, sum(quantity) as quantity')
            ->groupBy(['category', 'sub_category', 'product_name']);

        $return = [];
        $row = 1;
        foreach ($table->get() as $key => $value) {
            $temp = [];
            if ($value->quantity_diff != 0) {
                array_push($return, $value);
            }
        }
        $return_data = [];
        foreach ($return as $key => $value) {
            $data = [];
            $data['row'] = $row++;
            $data['category'] = tbl_prodcat::where("id", $value->category)->first();
            $data['outgoing_amount'] = number_format($value->outgoing_amount, 2);
            $data['product_name'] = tbl_masterlistprod::where("id", $value->product_name)->first();
            $data['price'] = number_format(tbl_masterlistprod::where("id", $value->product_name)->first()->price, 2);
            $data['quantity'] = $value->quantity;
            $data['quantity_diff'] = $value->quantity_diff;
            $data['sub_category'] = tbl_prodsubcat::where("id", $value->sub_category)->first();
            array_push($return_data, $data);
        }
        $items = Collection::make($return_data);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }

    //For retrieving sales count
    public function getSalesCount()
    {
        $count = DB::table("tbl_pos")->where(['branch' => auth()->user()->branch])
            ->whereBetween("created_at", [date("Y-m-d 00:00:00", strtotime(date("Y-m-d"))), date("Y-m-d 23:59:59", strtotime(date("Y-m-d")))])
            ->select("reference_no")->groupBy('reference_no')->get();

        $amount = tbl_pos::where(['branch' => auth()->user()->branch])
            ->whereBetween("created_at", [date("Y-m-d 00:00:00", strtotime(date("Y-m-d"))), date("Y-m-d 23:59:59", strtotime(date("Y-m-d")))])
            ->get()->sum('sub_total_discounted');
        return ['count' => count($count), 'amount' => number_format($amount, 2)];
    }

    //For saving transaction made
    public function save(Request $t)
    {
        $refno = strtotime(date("Y-m-d h:i:s.u"));
        foreach ($t->all() as $key => $value) {
            $data = tbl_pos::create(['category' => $value['category'],
                'sub_category' => $value['sub_category'],
                'product_name' => $value['product'],
                'quantity' => $value['quantity'],
                'sub_total' => $value['sub_total'],
                'sub_total_discounted' => $value['sub_total_discounted'],
                'vat' => $value['vat'],
                'payment' => $value['payment'],
                'discount' => ($value['discount'] ?? 0),
                'change' => $value['change'],
                'mode' => $value['mode'],
                'reference_no' => $refno,
                'branch' => auth()->user()->branch,
                'cashier' => auth()->user()->id]);
        }
        return $data;
    }

    //For retrieving sales today
    public function getSalesToday()
    {
        $data = tbl_pos::where(['cashier' => auth()->user()->id])
            ->whereBetween("created_at", [date("Y-m-d 00:00:00", strtotime(date("Y-m-d"))), date("Y-m-d 23:59:59", strtotime(date("Y-m-d")))])
            ->get();

        $content['data'] = $data;
        $pdf = PDF::loadView(
            'pos.transaction',
            $content,
            [],
            ['format' => 'A4-L']
        );
        return $pdf->stream();
    }
}
