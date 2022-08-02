<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\tbl_incomingsupp;
use App\Models\tbl_masterlistsupp;
use App\Models\tbl_outgoingsupp;
use App\Models\tbl_requestsupp;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RequestSuppliesController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For retrieving supplies list
    public function getSuppliesList(Request $request)
    {
        $where = ($request->category ? "category !=0  and category=" . $request->category : "category != 0");
        $table = tbl_masterlistsupp::with("category", "supplier")
            ->selectRaw("*, case when exp_date is null THEN null when datediff(exp_date,current_timestamp) > 7 THEN null ELSE datediff(exp_date,current_timestamp) end as days")
            ->whereRaw($where);

        if ($request->search) { //Wherehas if this portion is in relationship, if not then just use where
            $table = $table->where('supply_name', 'like', "%" . $request->search . "%");
        }

        return $table->get();
    }

    //For retrieving requests
    public function get(Request $t)
    {
        $table = tbl_requestsupp::select(['ref', 'user', 'request_date'])
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

    public function storeSupplies(Request $request)
    {
        //Delete upon updating
        //Get all the request row
        $ids = [];
        foreach ($request->all() as $key => $value) {
            array_push($ids, $value['id']);
        }
        //If the ref is found and id is not found in that reference then status = 0
        tbl_requestsupp::where('ref', $request[0]['ref'])->whereNotIn('supply_name', $ids)->update(['status' => 0]);

        //Update / save as new row for existing request or if no reference found save all
        $date = date("Y-m-d H:i:s");
        $refno = strtotime(date("Y-m-d h:i:s"));

        foreach ($request->all() as $key => $value) {
            //First find the ref and id
            if (tbl_requestsupp::where(['ref' => $value['ref'], 'supply_name' => $value['id']])->get()->count() > 0) {
                tbl_requestsupp::where(['ref' => $value['ref'], 'supply_name' => $value['id']])
                    ->update(
                        [
                            'supply_name' => $value['id'],
                            'quantity' => $value['quantity'],
                            'request_date' => $date,
                            'user' => auth()->user()->id,
                        ]
                    );
            } else {
                //If the item has ref then add new with that ref
                if ($value['ref']) {
                    tbl_requestsupp::create(
                        [
                            'ref' => $value['ref'],
                            'supply_name' => $value['id'],
                            'quantity' => $value['quantity'],
                            'request_date' => $date,
                            'branch' => auth()->user()->branch,
                            'user' => auth()->user()->id,
                        ]
                    );
                } else {
                    //If no ref found then save as new ref
                    tbl_requestsupp::create(
                        [
                            'ref' => $refno,
                            'supply_name' => $value['id'],
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
        $table = tbl_requestsupp::where('ref', $request->ref)
            ->wherein("status", [1, 2])
            ->where('deleted', 0)
            ->get();
        $return = [];
        foreach ($table as $key => $value) {
            $temp = [];
            $temp['id'] = $value->supply_name;
            $temp['supply_name'] = $value->supply_name_details['supply_name'] . ' ' . $value->supply_name_details['description'];
            $temp['unit'] = $value->supply_name_details['unit'];
            $temp['quantity'] = $value->quantity;
            $temp['status'] = $value->status;
            array_push($return, $temp);
        }
        return $return;
    }

    //For completing requests
    public function completeRequest(Request $request)
    {
        foreach (tbl_requestsupp::where(['ref' => $request->ref])->where('status', '!=', 0)->get() as $key => $value) {

            $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
            $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

            $get_amount = tbl_incomingsupp::where("supply_name", $value->supply_name)
                ->whereBetween('incoming_date', [$date1, $date2]);
            $get_quantity = tbl_incomingsupp::where("supply_name", $value->supply_name)
                ->whereBetween('incoming_date', [$date1, $date2]);

            $get_quantity->sum('quantity');
            $get_wov = ($get_amount->sum('amount') ? $get_amount->sum('amount') / $get_quantity->sum('quantity') : 0);

            tbl_outgoingsupp::create(
                ['category' => tbl_masterlistsupp::where("id", $value->supply_name)->first()->category,
                    'supply_name' => $value->supply_name,
                    'quantity' => $value->quantity,
                    'requesting_branch' => $value->branch,
                    'request_ref' => $value->ref,
                    'amount' => $get_wov * $value->quantity,
                    'outgoing_date' => date('Y-m-d h:i:s'),
                ]
            );
        }
        tbl_requestsupp::where(['ref' => $request->ref])->where('status', '!=', 0)->update(['status' => 3]);
    }

    //For cancelling requests
    public function cancelRequest(Request $request)
    {
        tbl_requestsupp::where(['ref' => $request->ref])->where('status', '!=', 0)->update(['status' => 0]);
    }
}
