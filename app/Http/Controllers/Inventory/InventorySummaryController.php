<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\tbl_incomingsupp;
use App\Models\tbl_masterlistsupp;
use App\Models\tbl_outgoingsupp;
use App\Models\tbl_suppcat;
use Illuminate\Http\Request;

class InventorySummaryController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For retrieving inventory summary
    public function get(Request $request)
    {
        $data = tbl_suppcat::all();

        //Previous month
        $date11 = date("Y-m-d 00:00:00", strtotime("-1 month", strtotime($request->year . "-" . $request->month . "-01")));
        $date22 = date("Y-m-t 23:59:59", strtotime("-1 month", strtotime($request->year . "-" . $request->month . "-01")));
        //Current month
        $date1 = date("Y-m-d 00:00:00", strtotime($request->year . "-" . $request->month . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime($request->year . '-' . $request->month . '-' . date("t")));

        //Set array for temporary table
        $return = [];

        foreach ($data as $key => $value) {
            $temp = [];
            $temp['category'] = $value->supply_cat_name;

            //From last month last day
            $incoming_all_past = tbl_incomingsupp::where('category', $value->id)->whereDate('incoming_date', '<=', $date22);
            $outgoing_all_past = tbl_outgoingsupp::where('category', $value->id)->whereDate('outgoing_date', '<=', $date22);

            //Current month only
            $incoming = tbl_incomingsupp::where('category', $value->id)->whereBetween('incoming_date', [$date1, $date2]);
            $outgoing = tbl_outgoingsupp::where('category', $value->id)->whereBetween('outgoing_date', [$date1, $date2]);

            //Begining based on from, to, and category, then sum amount of last month
            $temp['begining_orig'] = $incoming_all_past->get()->sum("amount") - $outgoing_all_past->get()->sum("amount");
            $temp['begining'] = number_format($incoming_all_past->get()->sum("amount") - $outgoing_all_past->get()->sum("amount"), 2);
            //Get incoming based on from, to, per category, then sum amount
            $temp['incoming_orig'] = tbl_incomingsupp::where("category", $value->id)->whereBetween("incoming_date", [$date1, $date2])->get()->sum("amount");
            $temp['incoming'] = number_format(tbl_incomingsupp::where("category", $value->id)->whereBetween("incoming_date", [$date1, $date2])->get()->sum("amount"), 2);
            //Beginning + Incoming
            $temp['total_orig'] = $temp['begining_orig'] + $temp['incoming_orig'];
            $temp['total'] = number_format($temp['begining_orig'] + $temp['incoming_orig'], 2);
            //Get outgoing based on from, to, and category, then sum amount based on masterlist supplies net price
            $temp['outgoing_orig'] = tbl_outgoingsupp::where("category", $value->id)->whereBetween("outgoing_date", [$date1, $date2])->get()->sum("amount");
            $temp['outgoing'] = number_format(tbl_outgoingsupp::where("category", $value->id)->whereBetween("outgoing_date", [$date1, $date2])->get()->sum("amount"), 2);
            //Stocks = Total - Outgoing
            $temp['stocks_orig'] = $temp['total_orig'] - $temp['outgoing_orig'];
            $temp['stocks'] = number_format($temp['total_orig'] - $temp['outgoing_orig'], 2);

            //For computing ending
            $temp['ending'] = 0;
            $ending_q = 0;
            $get_total_ending = 0;
            foreach (tbl_masterlistsupp::where("category", $value->id)->get() as $key1 => $value1) {
                $incoming_and_past = tbl_incomingsupp::where('supply_name', $value1->id)->whereDate('incoming_date', '<=', $date2);
                $outgoing_and_past = tbl_outgoingsupp::where('supply_name', $value1->id)->whereDate('outgoing_date', '<=', $date2);
                $incoming = tbl_incomingsupp::where('supply_name', $value1->id)->whereBetween('incoming_date', [$date1, $date2]);

                $a = clone $incoming_and_past;
                $b = clone $outgoing_and_past;
                $aa = clone $incoming;
                $temp['ending_q'] = ($a->sum('quantity') - $b->sum('quantity'));
                if ($temp['ending_q'] > 0 && $aa->sum('quantity') > 0) {
                    $temp['ending'] += $temp['ending_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                } else {
                    $temp['ending'] += $temp['ending_q'] * $value1->with_vat_price;
                }
                $get_total_ending = $temp['ending'];
            }
            $temp['ending'] = number_format($temp['ending'], 2);

            //For computing ending original
            try {
                $temp['ending_orig'] = $get_total_ending;
            } catch (\Throwable $th) {
                $temp['ending_orig'] = 0;
            }

            //For computing variance
            $variance = number_format($temp['ending_orig'] - $temp['stocks_orig'], 2);
            try {
                $temp['variance'] = $variance < 0 ? "(" . number_format(abs($variance), 2) . ")" : number_format($variance, 2);
            } catch (\Throwable $th) {
                $temp['variance'] = number_format(0, 2);
            }

            //For computing variance original
            $variance_orig = $temp['ending_orig'] - $temp['stocks_orig'];
            try {
                $temp['variance_orig'] = $variance_orig;
            } catch (\Throwable $th) {
                $temp['variance_orig'] = 0;
            }

            $get_total_flc = 0;
            //Get the supplies id
            $temp['fluctuation'] = number_format(0, 2);
            foreach (tbl_incomingsupp::select('supply_name')->where("category", $value->id)
                ->whereBetween("incoming_date", [$date1, $date2])->
                groupBy("supply_name")->get()->pluck('supply_name') as $keyx => $valuex) {
                $get_total_flc = 0;

                //Get the total amount and qty from incoming via supply, category and date
                $get_amount = tbl_incomingsupp::where("supply_name", $valuex)
                    ->whereBetween("incoming_date", [$date1, $date2]);
                $get_quantity = tbl_incomingsupp::where("supply_name", $valuex)->where("supply_name", $valuex)
                    ->whereBetween("incoming_date", [$date1, $date2]);

                //Add fluc
                if ($get_quantity->sum('amount') < 1) {
                } else {
                    $temp['fluctuation'] += $get_quantity->sum('quantity')
                         *
                        (($get_amount->sum('amount') / $get_quantity->sum('quantity')) -
                        tbl_masterlistsupp::where("id", $valuex)->first()->net_price);
                    $get_total_flc += $temp['fluctuation'];
                }

            }
            $temp['fluctuation'] = $temp['fluctuation'] < 0 ? "(" . number_format(abs($temp['fluctuation']), 2) . ")" : number_format($temp['fluctuation'], 2);

            //For computing fluctuation original
            try {
                $temp['fluctuation_orig'] = $get_total_flc;
            } catch (\Throwable $th) {
                $temp['fluctuation_orig'] = 0;
            }
            array_push($return, $temp);
        }
        return $return;
    }
}
