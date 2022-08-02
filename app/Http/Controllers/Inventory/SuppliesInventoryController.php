<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\tbl_outgoingsupp;
use App\Models\tbl_suppliesinventory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SuppliesInventoryController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For storing supplies inventory info
    public function store(Request $request)
    {
        tbl_suppliesinventory::create(
            ['ref' => $request->ref,
                'category' => $request->category,
                'supply_name' => $request->supply_name,
                'quantity' => $request->quantity,
                'outgoing_date' => date("Y-m-d h:i:s"),
                'branch' => auth()->user()->branch,
                'user' => auth()->user()->id,
            ]
        );
    }

    //For retrieving supplies inventory info
    public function get(Request $t)
    {
        $where = ($t->category ? "category !=0  and category=" . $t->category : "category != 0") .
            ($t->branch ? " and requesting_branch=" . $t->branch : "");
        $table = tbl_outgoingsupp::with(["category", "supply_name", "requesting_branch"])
            ->selectRaw("max(id) as id, category, supply_name, requesting_branch, sum(quantity) as quantity")
            ->groupby(["category", "supply_name", "requesting_branch"])
            ->whereRaw($where)->where("requesting_branch", auth()->user()->branch)->where("supply_name", "!=", null);

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
        foreach ($table->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $row++;
            $temp['id'] = $value->id;
            $temp['category'] = $value->category_details;
            $temp['quantity'] = $value->quantity - tbl_suppliesinventory::where(['branch' => auth()->user()->branch, 'ref' => $value->id])->sum('quantity');
            $temp['requesting_branch'] = $value->requesting_branch_details;
            $temp['supply_name'] = $value->supply_name_details;
            $temp['outgoing_amount'] = number_format($value->with_vat_price * ($value->quantity - tbl_suppliesinventory::where(['branch' => auth()->user()->branch, 'ref' => $value->id])->sum('quantity')), 2);
            $temp['with_vat_price'] = number_format($value->with_vat_price, 2);
            $temp['fluctuation'] = number_format($value->fluctuation, 2);
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }
}
