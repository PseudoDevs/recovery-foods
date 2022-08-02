<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\tbl_incomingprod;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_outgoingprod;
use App\Models\tbl_requestprod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RequestProductsController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For retrieving products list
    public function getProductsList(Request $request)
    {
        $where = ($request->category ? "category !=0  and category=" . $request->category : "category != 0") .
            ($request->subcategory ? " and sub_category=" . $request->subcategory : "");

        $table = tbl_masterlistprod::with("category", "sub_category")
            ->selectRaw("*, case when exp_date is null THEN null when datediff(exp_date,current_timestamp) > 7 THEN null ELSE datediff(exp_date,current_timestamp) end as days")
            ->whereRaw($where);

        if ($request->search) { //Wherehas if this portion is in relationship, if not then just use where
            $table = $table->where('product_name', 'like', "%" . $request->search . "%");
        }

        return $table->get();
    }

    //For retrieving requests
    public function get(Request $t)
    {
        $table = tbl_requestprod::select(['ref', 'user', 'request_date'])
            ->selectRaw('min(status) as status')
            ->groupBy(['ref', 'user', 'request_date'])
            ->wherein('status', [1, 2])
            ->where('branch', auth()->user()->branch)
            ->orderBy("request_date", "desc")
            ->get();

        $return = [];
        $row = 1;
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['row'] = $row++;
            $temp['id'] = $value->ref;
            $temp['ref'] = $value->ref;
            $temp['status'] = $value->status;
            $temp['user'] = $value->user_details->name;
            $temp['request_date'] = $value->request_date;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);
    }

    //For storing products request
    public function storeProducts(Request $request)
    {
        //Delete upon updating
        //Get all the request rows
        $ids = [];
        foreach ($request->all() as $key => $value) {
            array_push($ids, $value['id']);
        }
        //If the ref is found and id is not found in that reference then status = 0
        tbl_requestprod::where('ref', $request[0]['ref'])->whereNotIn('product_name', $ids)->update(['status' => 0]);

        //Update / Save as new row for existing request or if no reference found save all
        $date = date("Y-m-d H:i:s");
        $refno = strtotime(date("Y-m-d h:i:s"));

        foreach ($request->all() as $key => $value) {
            //First, find the ref and id
            if (tbl_requestprod::where(['ref' => $value['ref'], 'product_name' => $value['id']])->get()->count() > 0) {
                tbl_requestprod::where(['ref' => $value['ref'], 'product_name' => $value['id']])
                    ->update(
                        [
                            'product_name' => $value['id'],
                            'quantity' => $value['quantity'],
                            'request_date' => $date,
                            'user' => auth()->user()->id,
                        ]
                    );
            } else {
                //If the item has ref then add new with that ref
                if ($value['ref']) {
                    tbl_requestprod::create(
                        [
                            'ref' => $value['ref'],
                            'product_name' => $value['id'],
                            'quantity' => $value['quantity'],
                            'request_date' => $date,
                            'branch' => auth()->user()->branch,
                            'user' => auth()->user()->id,
                        ]
                    );
                } else {
                    //If no ref found then save as new ref
                    tbl_requestprod::create(
                        [
                            'ref' => $refno,
                            'product_name' => $value['id'],
                            'quantity' => $value['quantity'],
                            'request_date' => $date,
                            'branch' => auth()->user()->branch,
                            'user' => auth()->user()->id,
                        ]
                    );
                }
            }
        }
        return $request;
    }

    //For retrieving requests
    public function getRequested(Request $request)
    {
        $table = tbl_requestprod::where('ref', $request->ref)
            ->wherein("status", [1, 2])
            ->where('deleted', 0)
            ->get();
        $return = [];
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['id'] = $value->product_name;
            $temp['product_name'] = $value->product_name_details['product_name'] . ' ' . $value->product_name_details['description'];
            $temp['quantity'] = $value->quantity;
            $temp['status'] = $value->status;
            array_push($return, $temp);
        }
        return $return;
    }

    //For completing requests
    public function completeRequest(Request $request)
    {
        foreach (tbl_requestprod::where(['ref' => $request->ref])->where('status', '!=', 0)->get() as $key => $value) {

            $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
            $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

            $get_amount = tbl_incomingprod::where("product_name", $value->product_name)
                ->whereBetween('incoming_date', [$date1, $date2]);
            $get_quantity = tbl_incomingprod::where("product_name", $value->product_name)
                ->whereBetween('incoming_date', [$date1, $date2]);

            $get_quantity->sum('quantity');
            $get_wov = ($get_amount->sum('amount') ? $get_amount->sum('amount') / $get_quantity->sum('quantity') : 0);

            tbl_outgoingprod::create(
                ['category' => tbl_masterlistprod::where("id", $value->product_name)->first()->category,
                    'product_name' => $value->product_name,
                    'sub_category' => tbl_masterlistprod::where("id", $value->product_name)->first()->sub_category,
                    'quantity' => $value->quantity,
                    'requesting_branch' => $value->branch,
                    'request_ref' => $value->ref,
                    'amount' => $get_wov * $value->quantity,
                    'outgoing_date' => date('Y-m-d h:i:s'),
                ]
            );
        }
        tbl_requestprod::where(['ref' => $request->ref])->where('status', '!=', 0)->update(['status' => 3]);
    }

    //For cancelling requests
    public function cancelRequest(Request $request)
    {
        tbl_requestprod::where(['ref' => $request->ref])->where('status', '!=', 0)->update(['status' => 0]);
    }
}
