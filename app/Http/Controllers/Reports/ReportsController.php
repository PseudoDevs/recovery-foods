<?php

namespace App\Http\Controllers\Reports;

use App\Exports\InventoryExport2;
use App\Exports\InventoryExport;
use App\Http\Controllers\Controller;
use App\Models\tbl_branches;
use App\Models\tbl_company;
use App\Models\tbl_incomingsupp;
use App\Models\tbl_masterlistsupp;
use App\Models\tbl_measures;
use App\Models\tbl_outgoingsupp;
use App\Models\tbl_pos;
use App\Models\tbl_purchaseord;
use App\Models\tbl_suppcat;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ReportsController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware("auth");
    }

    //For masterlist supplies report
    public function MasterlistSuppliesReport(Request $t)
    {
        //Filter if all or specific
        $where = ($t->category == 'All' ? "   category != -1 " : ' category =' . $t->category);
        $data = []; //Main array

        //Get all the category then loop
        foreach (tbl_masterlistsupp::whereRaw($where)->groupBy('category')->pluck('category') as $key => $value) {
            $group = []; //Inner Array
            $net_p = 0; //Sub-Total
            $wvat_p = 0; //Sub-Total
            $wovat_p = 0; //Sub-Total

            //Each category add to inner array
            foreach (tbl_masterlistsupp::with("category")->where("category", $value)->orderBy("exp_date", "desc")->get() as $key1 => $value1) {
                $net_p += $value1->net_price;
                $wvat_p += $value1->with_vat;
                $wovat_p += $value1->without_vat;

                $ar = [
                    'supplier_name' => $value1->supplier_name_details['supplier_name'] . " " . "(" . $value1->supplier_name_details['description'] . ")",
                    'supplier_desc' => $value1->supplier_name_details['description'],
                    'category_details' => $value1->category_details['supply_cat_name'],
                    'supply_name' => $value1->supply_name,
                    'description' => $value1->description,
                    'unit' => $value1->unit,
                    'net_price' => $value1->net_price,
                    'with_vat' => $value1->with_vat,
                    'vat' => $value1->vat,
                    'without_vat' => $value1->without_vat,
                    'exp_date' => $value1->exp_date,
                ];
                array_push($group, $ar);
            }

            //Add inner array to main array (Nested array)
            $ar = [
                'supplier_name' => ($t->type == 'excel' ? 'TOTAL' : '<b>TOTAL</b>'),
                'supplier_desc' => '',
                'category_details' => '',
                'supply_name' => '',
                'description' => '',
                'unit' => '',
                'net_price' => $net_p,
                'with_vat' => $wvat_p,
                'vat' => '',
                'without_vat' => $wovat_p,
                'exp_date' => '',
            ];
            array_push($group, $ar);
            array_push($data, $group);
        }

        $content = [];
        switch ($t->type) {
            case 'pdf':
                if (count($data) > 0) {
                    $content['data'] = $data;
                    //Total amounts below the report
                    $content['net_price'] = tbl_masterlistsupp::get()->sum("net_price");
                    $content['with_vat'] = tbl_masterlistsupp::get()->sum("with_vat");
                    $content['without_vat'] = tbl_masterlistsupp::get()->sum("without_vat");
                    $content['process_by'] = auth()->user()->name;
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.masterlistsupplies', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                //Columns
                $columns = ['SUPPLIER NAME', 'CATEGORY', 'SUPPLY NAME', 'UNIT', 'NET PRICE', 'WITH VAT', 'VAT', 'WITHOUT VAT', 'EXPIRATION DATE'];
                //Data
                $dataitems = [];

                foreach ($data as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        $temp = [];
                        $temp['supplier_name'] = $value1['supplier_name'];
                        $temp['category'] = $value1['category_details'];
                        $temp['supply_name'] = $value1['supply_name'] . " " . $value1['description'];
                        $temp['unit'] = $value1['unit'];
                        $temp['format_net_price'] = $value1['net_price'];
                        $temp['format_with_vat'] = $value1['with_vat'];
                        $temp['vat'] = $value1['vat'];
                        $temp['format_without_vat'] = $value1['without_vat'];
                        $temp['exp_date'] = ($value1['exp_date'] ? date("Y-m-d", strtotime($value1['exp_date'])) : null);
                        array_push($dataitems, $temp);
                    }
                }
                return Excel::download(new InventoryExport($dataitems, $columns), "Masterlist Supplies Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For incoming supplies report
    public function IncomingSuppliesReport(Request $t)
    {
        //Filter if all or specific
        $where = ($t->category == 'All' ? "   category != -1 " : ' category =' . $t->category);
        $data = []; //Main array
        //Get all the category then loop
        $g_net_p = 0; //Grand Total
        $g_wvat_p = 0; //Grand Total
        $g_total_p = 0; //Grand Total

        foreach (tbl_incomingsupp::whereRaw($where)
            ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
            ->groupBy('category')->pluck('category') as $key => $value) {
            $group = []; //Inner Array

            $net_p = 0; //Sub-Total
            $wvat_p = 0; //Sub-Total
            $total_p = 0; //Sub-Total

            //Each supply name add to inner array
            foreach (tbl_incomingsupp::with("category")
                ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
                ->groupBy('supply_name')
                ->pluck('supply_name') as $key2 => $value2) {
                $group2 = [];
                $ar2 = [];

                $s_total_a = 0; //Sub-Total Amt
                $s_qty = 0; //Sub-Total Qty
                $s_flc = 0; //Sub-Total Fluc

                //Each category add to inner array
                foreach (tbl_incomingsupp::with("category")
                    ->where("category", $value)
                    ->where("supply_name", $value2)
                    ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
                    ->get() as $key1 => $value1) {

                    //Get the total amount and qty from incoming
                    $get_amount = tbl_incomingsupp::where("supply_name", $value2)
                        ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))]);
                    $get_quantity = tbl_incomingsupp::where("supply_name", $value2)
                        ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))]);

                    //Get with vat
                    if ($get_quantity->sum('quantity') > 0) {
                        $get_wov = number_format($get_amount->sum('amount') / $get_quantity->sum('quantity'), 2);
                    } else {
                        $get_wov = number_format(tbl_masterlistsupp::where('id', $value2)->first()->net_price, 2);
                    }

                    //Get fluctuation
                    if ($get_quantity->sum('amount') < 1) {
                        $get_fl = 0;
                    } else {
                        $get_fl = $get_quantity->sum('quantity')
                             *
                            (($get_amount->sum('amount') / $get_quantity->sum('quantity')) -
                            tbl_masterlistsupp::where("id", $value2)->first()->net_price);
                    }

                    $net_p += $value1->supply_name_details['net_price'];
                    $wvat_p += $get_wov;
                    $total_p += $value1->amount;

                    $s_total_a += $value1->amount;
                    $s_qty += $value1->quantity;
                    $s_flc = number_format($get_fl, 2);

                    $g_net_p += $value1->supply_name_details['net_price'];
                    $g_wvat_p += $get_wov;
                    $g_total_p += $value1->amount;

                    $ar = [
                        'category_details' => $value1->category_details['supply_cat_name'],
                        'supply_name' => $value1->supply_name_details['supply_name'],
                        'description' => $value1->supply_name_details['description'],
                        'unit' => $value1->supply_name_details['unit'],
                        'net_price' => $value1->supply_name_details['net_price'],
                        'with_vat' => $get_wov,
                        'quantity' => $value1->quantity,
                        'amount' => $value1->amount,
                        'incoming_date' => $value1->incoming_date,
                    ];
                    array_push($group2, $ar);
                }
                if (tbl_incomingsupp::with("category")
                    ->where("category", $value)
                    ->where("supply_name", $value2)
                    ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
                    ->count() > 0
                ) {
                    $ar2 = [
                        'category_details' => ($t->type == 'excel' ? 'Fluctuation Impact: ' . $s_flc : ''),
                        'supply_name' => ($t->type == 'excel' ? '' : '<b>Fluctuation Impact: </b> ' . $s_flc),
                        'description' => '',
                        'unit' => '',
                        'net_price' => '',
                        'with_vat' => '',
                        'quantity' => $s_qty,
                        'amount' => $s_total_a,
                        'incoming_date' => '',
                    ];
                    array_push($group2, $ar2); //Add inner array to main array (Nested array)
                    array_push($group, $group2);
                    $group2 = [];
                }
            }

            // Add inner array to main array (Nested array)
            $ar = [
                'category_details' => ($t->type == 'excel' ? 'SUB-TOTAL' : ''),
                'supply_name' => ($t->type == 'excel' ? '' : '<b>SUB-TOTAL</b>'),
                'description' => '',
                'unit' => '',
                'net_price' => '',
                'with_vat' => '',
                'quantity' => '',
                'amount' => $total_p,
                'incoming_date' => '',
            ];
            array_push($group2, $ar);
            array_push($group, $group2);
            array_push($data, $group);
        }

        // return $data;
        $content = [];
        switch ($t->type) {
            case 'pdf':
                if (count($data) > 0) {
                    $content['data'] = $data;
                    $content['net_price'] = $g_net_p;
                    $content['with_vat'] = $g_wvat_p;
                    $content['amount'] = $g_total_p;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['from' => $t->from, 'to' => $t->to];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.incomingsupplies', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                //Columns
                $columns = ['CATEGORY', 'SUPPLY NAME', 'UNIT', 'NET PRICE', 'WITH VAT', 'QTY', 'TOTAL AMT', 'INCOMING DATE'];
                //Data
                $dataitems = [];
                $grandTotal = 0;

                foreach ($data as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        foreach ($value2 as $key1 => $value1) {
                            $temp = [];
                            $temp['category'] = $value1['category_details'];
                            $temp['supply_name'] = $value1['supply_name'] . " " . $value1['description'];
                            $temp['unit'] = $value1['unit'];
                            $temp['net_price'] = $value1['net_price'];
                            $temp['with_vat'] = $value1['with_vat'];
                            $temp['quantity'] = $value1['quantity'];
                            $temp['total_amount'] = $value1['amount'];
                            $temp['incoming_date'] = ($value1['incoming_date'] ? date("Y-m-d", strtotime($value1['incoming_date'])) : null);
                            array_push($dataitems, $temp);
                        }
                    }
                    $grandTotal += $value1['amount'];
                }

                $temp_gt['category'] = 'GRAND-TOTAL';
                $temp_gt['supply_name'] = '';
                $temp_gt['unit'] = '';
                $temp_gt['net_price'] = '';
                $temp_gt['with_vat'] = '';
                $temp_gt['quantity'] = '';
                $temp_gt['amount'] = $grandTotal;
                $temp_gt['incoming_date'] = '';
                array_push($dataitems, $temp_gt);
                return Excel::download(new InventoryExport($dataitems, $columns), "Incoming Supplies Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For outgoing supplies report
    public function OutgoingSuppliesReport(Request $t)
    {
        //Filter if all or specific
        $datax = tbl_outgoingsupp::whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))]);
        //Filter if all or specific
        if ($t->category != 'All') {
            $datax = $datax->where("category", $t->category);
        }

        if ($t->branch != 'All') {
            $datax = $datax->where("requesting_branch", $t->branch);
        }
        $data = []; //Main array

        //Get all the category then loop
        $g_net_p = 0; //Grand Total
        $g_wvat_p = 0; //Grand Total
        $g_total_p = 0; //Grand Total
        $g_quantity = 0; //Grand Total
        $g_total_amount = 0; //Grand Total
        $clone_datax = clone $datax;
        foreach ($clone_datax->select("category")->groupBy('category')->pluck('category') as $key => $value) {
            $group = []; //Inner Array

            $net_p = 0; //Sub-Total
            $wvat_p = 0; //Sub-Total
            $quantity = 0; //Sub-Total
            $total_p = 0; //Sub-Total

            //Each supply name add to inner array
            $clone1_datax = clone $datax;
            foreach ($clone1_datax
                ->where("category", $value)
                ->groupBy('supply_name')
                ->pluck('supply_name') as $key2 => $value2) {
                $group2 = [];
                $ar2 = [];

                $s_total_a = 0; //Sub-Total Amt
                $s_qty = 0; //Sub-Total Qty
                $s_flc = 0; //Sub-Total Fluc

                //Each category add to inner array
                $clone2_datax = clone $datax;
                foreach ($clone2_datax
                    ->where("supply_name", $value2)
                    ->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
                    ->get() as $key1 => $value1) {

                    //Get the amount from incoming
                    $get_amount = tbl_outgoingsupp::where("supply_name", $value2)
                        ->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))]);
                    $get_quantity = tbl_outgoingsupp::where("supply_name", $value2)
                        ->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))]);

                    //Get with vat
                    if ($get_quantity->sum('quantity') > 0) {
                        $get_wov = number_format($get_amount->sum('amount') / $get_quantity->sum('quantity'), 2);
                    } else {
                        $get_wov = number_format(tbl_masterlistsupp::where('id', $value2)->first()->net_price, 2);
                    }

                    //Get fluctuation
                    if ($get_quantity->sum('amount') < 1) {
                        $get_fl = 0;
                    } else {
                        $get_fl = $get_quantity->sum('quantity')
                             *
                            (($get_amount->sum('amount') / $get_quantity->sum('quantity')) -
                            tbl_masterlistsupp::where("id", $value2)->first()->net_price);
                    }

                    $net_p += $value1->supply_name_details['net_price'];
                    $wvat_p += $get_wov;
                    $total_p += $value1->with_vat_price * $value1->quantity;
                    $quantity += $value1->quantity;

                    $s_total_a += $value1->with_vat_price * $value1->quantity;
                    $s_qty += $value1->quantity;
                    $s_flc = number_format($get_fl, 2);

                    $g_net_p += $value1->supply_name_details['net_price'];
                    $g_wvat_p += $get_wov;
                    $g_total_p += $value1->with_vat_price * $value1->quantity;
                    $g_quantity += $value1->quantity;

                    $ar = [
                        'branch' => $value1->requesting_branch_details['branch_name'],
                        'category_details' => $value1->category_details['supply_cat_name'],
                        'supply_name' => $value1->supply_name_details['supply_name'],
                        'description' => $value1->supply_name_details['description'],
                        'unit' => $value1->supply_name_details['unit'],
                        'net_price' => $value1->supply_name_details['net_price'],
                        'with_vat' => $get_wov,
                        'quantity' => $value1->quantity,
                        'quantity_amount' => number_format($value1->with_vat_price * $value1->quantity, 2),
                        'outgoing_date' => $value1->outgoing_date,
                    ];
                    array_push($group2, $ar);
                }
                $clone3_datax = clone $datax;
                if ($clone3_datax
                    ->where("supply_name", $value2)
                    ->whereBetween("outgoing_date", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
                    ->count() > 0
                ) {
                    $ar2 = [
                        'branch' => ($t->branch == 'All' ? ($t->type == 'excel' ? 'Fluctuation Impact: ' . $s_flc : '<b>Fluctuation Impact: </b> ' . $s_flc) : ($t->type == 'excel' ? 'Fluctuation Impact: ' . $s_flc : '<b>Fluctuation Impact: </b> ' . $s_flc)),
                        'category_details' => '',
                        'supply_name' => ($t->branch == 'All' ? '' : ($t->type == 'excel' ? '' : '<b>Fluctuation Impact: </b> ' . $s_flc)),
                        'description' => '',
                        'unit' => '',
                        'net_price' => '',
                        'with_vat' => '',
                        'quantity' => $s_qty,
                        'quantity_amount' => $s_total_a,
                        'outgoing_date' => '',
                    ];
                    array_push($group2, $ar2); //Add inner array to main array (Nested array)
                    array_push($group, $group2);
                    $group2 = [];
                }
            }

            // Add inner array to main array (Nested array)
            $ar = [
                'branch' => ($t->branch == 'All' ? ($t->type == 'excel' ? 'SUB-TOTAL' : '<b>SUB-TOTAL</b>') : ($t->type == 'excel' ? 'SUB-TOTAL' : '<b>SUB-TOTAL</b>')),
                'category_details' => '',
                'supply_name' => ($t->branch == 'All' ? '' : ($t->type == 'excel' ? '' : '<b>SUB-TOTAL</b>')),
                'description' => '',
                'unit' => '',
                'net_price' => '',
                'with_vat' => '',
                'quantity' => '',
                'quantity_amount' => $total_p,
                'outgoing_date' => '',
            ];
            array_push($group2, $ar);
            array_push($group, $group2);
            array_push($data, $group);
        }

        // return $data;
        $content = [];
        switch ($t->type) {
            case 'pdf':
                if (count($data) > 0) {
                    $content['data'] = $data;
                    $content['net_price'] = $g_net_p;
                    $content['with_vat'] = $g_wvat_p;
                    $content['quantity'] = $g_quantity;
                    $content['quantity_amount'] = $g_total_p;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['from' => $t->from, 'to' => $t->to, 'branch' => $t->branch];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.outgoingsupplies', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                //Columns
                $columns = ['BRANCH', 'CATEGORY', 'SUPPLY NAME', 'UNIT', 'NET PRICE', 'WITH VAT', 'QTY', 'TOTAL AMT', 'OUTGOING DATE'];
                //Data
                $dataitems = [];
                $grandTotal = 0;

                foreach ($data as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        foreach ($value2 as $key1 => $value1) {
                            $temp = [];
                            $temp['branch'] = $value1['branch'];
                            $temp['category'] = $value1['category_details'];
                            $temp['supply_name'] = $value1['supply_name'] . " " . $value1['description'];
                            $temp['unit'] = $value1['unit'];
                            $temp['net_price'] = $value1['net_price'];
                            $temp['with_vat'] = $value1['with_vat'];
                            $temp['quantity'] = $value1['quantity'];
                            $temp['amount'] = $value1['quantity_amount'];
                            $temp['outgoing_date'] = ($value1['outgoing_date'] ? date("Y-m-d", strtotime($value1['outgoing_date'])) : null);
                            array_push($dataitems, $temp);
                        }
                    }
                    $grandTotal += $value1['quantity_amount'];
                }

                $temp_gt['branch'] = 'GRAND-TOTAL';
                $temp_gt['category'] = '';
                $temp_gt['supply_name'] = '';
                $temp_gt['unit'] = '';
                $temp_gt['net_price'] = '';
                $temp_gt['with_vat'] = '';
                $temp_gt['quantity'] = '';
                $temp_gt['amount'] = $grandTotal;
                $temp_gt['outgoing_date'] = '';
                array_push($dataitems, $temp_gt);
                return Excel::download(new InventoryExport($dataitems, $columns), "Outgoing Supplies Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For main inventory report
    public function MainInventoryReport(Request $t)
    {
        //Previous month
        $date11 = date("Y-m-d 00:00:00", strtotime("-1 month", strtotime($t->year . "-" . $t->month . "-01")));
        $date22 = date("Y-m-t 23:59:59", strtotime("-1 month", strtotime($t->year . "-" . $t->month . "-01")));
        //Current month
        $date1 = date("Y-m-d 00:00:00", strtotime($t->year . "-" . $t->month . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime($t->year . '-' . $t->month . '-' . date("t")));

        $where = ($t->category == 'All' ? "   category != -1 " : ' category =' . $t->category);
        $return = [];
        $row = 1;

        foreach (tbl_masterlistsupp::select('category')->whereRaw($where)->groupBy('category')->pluck('category') as $key1 => $value1) {
            $group = [];
            $st_beginning_a = 0; //Total
            $st_incoming_a = 0; //Total
            $st_total_a = 0; //Total
            $st_outgoing_a = 0; //Total
            $st_onhand_a = 0; //Total
            $st_average_a = 0; //Total
            $st_ending_a = 0; //Total
            $st_consumption_a = 0; //Total
            $st_ideal_a = 0; //Total
            $st_variance_a = 0; //Total

            foreach (tbl_masterlistsupp::where("category", $value1)->get() as $key => $value) {
                $temp = [];
                $temp['row'] = $row++;
                $temp['category'] = tbl_suppcat::where("id", $value->category)->first()->supply_cat_name;
                $temp['supply_name'] = $value->supply_name . ' ' . $value->description;
                $temp['unit'] = $value->unit;
                $temp['net_price'] = number_format((float) $value->net_price, 2);

                // $var_measure = tbl_measures::whereMonth("month", $value->created_at)->whereYear("year", $value->created_at)->where("supply_id", $value->id);
                // $var_measure_clone = clone $var_measure;
                // if ($var_measure_clone->get()->count() > 0) {
                //     $var_measure_clone1 = clone $var_measure;
                //     $temp['lead_time'] = $var_measure_clone1->lead_time;
                //     $temp['minimum_order_quantity'] = $var_measure_clone1->minimum_order_quantity;
                //     $temp['order_frequency'] = $var_measure_clone1->order_frequency;
                // } else {
                    $temp['lead_time'] = $value->lead_time;
                    $temp['minimum_order_quantity'] = $value->minimum_order_quantity;
                    $temp['order_frequency'] = $value->order_frequency;
                // }

                //From this month last day
                $incoming_all = tbl_incomingsupp::where('supply_name', $value->id)->whereDate('incoming_date', '<=', $date2);
                $outgoing_all = tbl_outgoingsupp::where('supply_name', $value->id)->whereDate('outgoing_date', '<=', $date2);

                //From last month last day
                $incoming_all_past = tbl_incomingsupp::where('supply_name', $value->id)->whereDate('incoming_date', '<=', $date22);
                $outgoing_all_past = tbl_outgoingsupp::where('supply_name', $value->id)->whereDate('outgoing_date', '<=', $date22);

                //Current month only
                $incoming = tbl_incomingsupp::where('supply_name', $value->id)->whereBetween('incoming_date', [$date1, $date2]);
                $outgoing = tbl_outgoingsupp::where('supply_name', $value->id)->whereBetween('outgoing_date', [$date1, $date2]);

                //Beginning (total of previous month)
                $a = clone $incoming_all_past;
                $b = clone $outgoing_all_past;
                $temp['beginning_q'] = $a->sum('quantity') - $b->sum("quantity");
                $temp['beginning_a'] = $temp['beginning_q'] * $value->net_price;
                $st_beginning_a += $temp['beginning_a']; //Sub-Total

                //Incoming (total of current month)
                $a = clone $incoming;
                $temp['incoming_q'] = $a->sum('quantity');
                $temp['incoming_a'] = $a->sum('amount');
                $st_incoming_a += $a->sum('amount'); //Sub-Total

                //Total (total of previous month + current month)
                $a = clone $incoming;
                $temp['total_q'] = $temp['beginning_q'] + $temp['incoming_q'];
                $temp['total_a'] = $a->sum('amount');
                $st_total_a += $a->sum('amount'); //Sub-Total

                //Outgoing (total of current month)
                $b = clone $outgoing;
                $temp['outgoing_q'] = $b->sum('quantity');
                $temp['outgoing_a'] = $b->sum('amount');
                $st_outgoing_a += $b->sum('amount'); //Sub-Total

                //Stocks On Hand (total of previous month + current month) - outgoing
                $c = clone $incoming_all;
                $d = clone $outgoing_all;
                $temp['onhand_q'] = $c->sum('quantity') - $d->sum('quantity');

                $c_a = clone $incoming_all;
                $cc_a = clone $incoming_all;
                if ($temp['onhand_q'] > 0) {
                    $temp['onhand_a'] = ($c_a->sum('amount') / $cc_a->sum('quantity') * $temp['onhand_q']);
                    $st_onhand_a += $c_a->sum('amount') / $cc_a->sum('quantity') * $temp['onhand_q'];
                } else {
                    $temp['onhand_a'] = 0;
                }

                //Average ((total of last month + current month) / (quantity of last month + current month) / (current month quantity / date today))
                $a = clone $outgoing;
                $temp['average_q'] = $a->sum('quantity') / date('d');

                $c_a = clone $incoming_all;
                $cc_a = clone $incoming_all;
                if ($c_a->sum('quantity') > 0) {
                    $temp['average_a'] = number_format(($c_a->sum('amount') / $cc_a->sum('quantity') * $temp['average_q']), 2);
                    $st_average_a += $c_a->sum('amount') / $cc_a->sum('quantity') * $temp['average_q'];
                } else {
                    $temp['average_a'] = 0;
                }

                //Order Point  (lead time of item * total quantity / day today) + outgoing quantity / current day today
                $a = clone $outgoing;
                $temp['orderpoint'] = round(($temp['lead_time'] * ($a->sum('quantity') / date('d'))) + (($a->sum('quantity') / date('d')) * 2), 2);

                //Order Point  (lead time of item * total quantity / day today) + outgoing quantity / current day today
                $a = clone $outgoing;
                $orderqty = $value->order_frequency * ($a->sum('quantity') / date('d'));
                if ($orderqty < $value->minimum_order_quantity) {
                    $temp['ordr'] = number_format(round($value->minimum_order_quantity, 2), 2);
                } else {
                    $temp['ordr'] = number_format(round($orderqty, 2), 2);
                }

                //Trigger Point  (lead time of item * total quantity / day today) + outgoing quantity / day today
                $a = clone $incoming_all;
                $b = clone $outgoing;
                if ($temp['onhand_q'] < $temp['orderpoint']) {
                    $temp['triggerpoint'] = 'Order';
                } else {
                    $temp['triggerpoint'] = 'Manage';
                }

                //Ending
                $a = clone $incoming_all;
                $b = clone $outgoing_all;
                $aa = clone $incoming;
                $temp['ending_q'] = ($a->sum('quantity') - $b->sum('quantity'));
                if ($temp['ending_q'] > 0 && $aa->sum('quantity') > 0) {
                    $temp['ending_a'] = $temp['ending_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                    $st_ending_a += $temp['ending_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                } else {
                    $temp['ending_a'] = $temp['ending_q'] * $value->with_vat_price;
                    $st_ending_a += $temp['ending_q'] * $value->with_vat_price;
                }

                //Consumption
                $temp['consumption_q'] = $temp['total_q'] - $temp['ending_q'];
                if ($aa->sum('amount') > 0) {
                    $temp['consumption_a'] = $temp['consumption_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                    $st_consumption_a += $temp['consumption_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                } else {
                    $temp['consumption_a'] = 0;
                }

                //Ideal
                $temp['ideal_q'] = $temp['total_q'] - $temp['outgoing_q'];
                $aa = clone $incoming;
                if ($temp['ideal_q'] > 0 && $aa->sum('quantity') > 0) {
                    $temp['ideal_a'] = $temp['ideal_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                    $st_ideal_a += $temp['ideal_q'] * ($aa->sum('amount') / $aa->sum('quantity'));
                } else {
                    $temp['ideal_a'] = $temp['ending_q'] * $value->with_vat_price;
                    $st_ideal_a += $temp['ending_q'] * $value->with_vat_price;
                }

                //Variance
                $temp['variance_q'] = $temp['ending_q'] - $temp['ideal_q'];
                $aa = clone $incoming;
                if ($temp['variance_q'] > 0) {
                    $temp['variance_a'] = $temp['ending_q'] - ($temp['ending_q'] * ($aa->sum('amount') / $aa->sum('quantity')));
                    $st_variance_a += $temp['ending_q'] - ($temp['ending_q'] * ($aa->sum('amount') / $aa->sum('quantity')));
                } else {
                    $temp['variance_a'] = 0;
                }
                array_push($group, $temp);
            }

            $ar = [
                'row' => '',
                'category' => ($t->type == 'excel' ? 'SUB-TOTAL' : ''),
                'supply_name' => ($t->type == 'excel' ? '' : '<b>SUB-TOTAL</b>'),
                'unit' => '',
                'net_price' => '',
                'lead_time' => '',
                'minimum_order_quantity' => '',
                'order_frequency' => '',
                'beginning_q' => '',
                'beginning_a' => $st_beginning_a,
                'incoming_q' => '',
                'incoming_a' => $st_incoming_a,
                'total_q' => '',
                'total_a' => $st_total_a,
                'outgoing_q' => '',
                'outgoing_a' => $st_outgoing_a,
                'onhand_q' => '',
                'onhand_a' => $st_onhand_a,
                'average_q' => '',
                'average_a' => $st_average_a,
                'orderpoint' => '',
                'ordr' => '',
                'triggerpoint' => '',
                'ending_q' => '',
                'ending_a' => $st_ending_a,
                'consumption_q' => '',
                'consumption_a' => $st_consumption_a,
                'ideal_q' => '',
                'ideal_a' => $st_ideal_a,
                'variance_q' => '',
                'variance_a' => $st_variance_a,
            ];

            $group = collect($group)->sortByDesc('triggerpoint')->ToArray();
            array_push($group, $ar);
            array_push($return, $group);
        }

        switch ($t->type) {
            case 'pdf':
                if (count($return) > 0) {
                    $content['data'] = $return;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['month' => $t->month];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.maininventory', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                $columns = [
                    'CATEGORY', 'SUPPLY NAME', 'UNIT', 'NET PRICE', 'BEGINNING INVENTORY', '',
                    'INCOMING SUPPLIES', '', 'TOTAL INVENTORY', '', 'OUTGOING SUPPLIES', '',
                    'STOCKS ON HAND', '', 'AVERAGE DAILY USAGE', '', 'LEAD TIME', 'ORDER POINT',
                    'MINIMUM ORDER QTY', 'ORDER QTY', 'TRIGGER POINT', 'ENDING INVENTORY', '',
                    'CONSUMPTION', '', 'IDEAL INVENTORY', '', 'VARIANCE', '',
                ];

                $dataitems = [[
                    "", "", "", "", "QTY", "VALUE", "QTY", "VALUE", "QTY", "VALUE", "QTY", "VALUE",
                    "QTY", "VALUE", "QTY", "VALUE", "", "", "", "", "", "QTY", "VALUE", "QTY", "VALUE",
                    "QTY", "VALUE", "QTY", "VALUE",
                ]];

                foreach ($return as $key1 => $value1) {
                    foreach ($value1 as $key => $value) {
                        $temp = [];
                        $temp['category'] = $value['category'];
                        $temp['supply_name'] = $value['supply_name'];
                        $temp['unit'] = $value['unit'];
                        $temp['net_price'] = $value['net_price'];
                        $temp['beginning_q'] = $value['beginning_q'] ?? 0;
                        $temp['beginning_a'] = round($value['beginning_a'] ?? 0, 2);
                        $temp['incoming_q'] = $value['incoming_q'] ?? 0;
                        $temp['incoming_a'] = round($value['incoming_a'] ?? 0, 2);
                        $temp['total_q'] = $value['total_q'] ?? 0;
                        $temp['total_a'] = round($value['total_a'] ?? 0, 2);
                        $temp['outgoing_q'] = $value['outgoing_q'] ?? 0;
                        $temp['outgoing_a'] = round($value['outgoing_a'] ?? 0, 2);
                        $temp['onhand_q'] = $value['onhand_q'] ?? 0;
                        $temp['onhand_a'] = round($value['onhand_a'] ?? 0, 2);
                        $temp['average_q'] = round($value['average_q'] ?? 0, 2);
                        $temp['average_a'] = round($value['average_a'] ?? 0, 2);
                        $temp['lead_time'] = $value['lead_time'] ?? 0;
                        $temp['orderpoint'] = round($value['orderpoint'] ?? 0, 2);
                        $temp['minimum_order_quantity'] = $value['minimum_order_quantity'] ?? 0;
                        $temp['ordr'] = round($value['ordr'] ?? 0, 2);
                        $temp['triggerpoint'] = $value['triggerpoint'] ?? 0;
                        $temp['ending_q'] = $value['ending_q'] ?? 0;
                        $temp['ending_a'] = round($value['ending_a'] ?? 0, 2);
                        $temp['consumption_q'] = $value['consumption_q'] ?? 0;
                        $temp['consumption_a'] = round($value['consumption_a'] ?? 0, 2);
                        $temp['ideal_q'] = $value['ideal_q'] ?? 0;
                        $temp['ideal_a'] = round($value['ideal_a'] ?? 0, 2);
                        $temp['variance_q'] = $value['variance_q'] ?? 0;
                        $temp['variance_a'] = round($value['variance_a'] ?? 0, 2);
                        array_push($dataitems, $temp);
                    }
                }
                return Excel::download(new InventoryExport2($dataitems, $columns), "Main Inventory Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For inventory summary report
    public function InventorySummaryReport(Request $t)
    {
        //Previous month
        $date11 = date("Y-m-d 00:00:00", strtotime("-1 month", strtotime($t->year . "-" . $t->month . "-01")));
        $date22 = date("Y-m-t 23:59:59", strtotime("-1 month", strtotime($t->year . "-" . $t->month . "-01")));
        //Current month
        $date1 = date("Y-m-d 00:00:00", strtotime($t->year . "-" . $t->month . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime($t->year . '-' . $t->month . '-' . date("t")));

        // //For fluctuation
        // $date1flc = date("Y-m-d 00:00:00", strtotime($t->year . "-" . $t->month));
        // $date2flc = date("Y-m-t 23:59:59", strtotime($t->year . "-" . $t->month));

        $data = [];

        foreach (tbl_suppcat::all() as $key => $value) {
            $temp = [];
            $temp['category'] = $value->supply_cat_name;

            //From last month last day
            $incoming_all_past = tbl_incomingsupp::where('category', $value->id)->whereDate('incoming_date', '<=', $date22);
            $outgoing_all_past = tbl_outgoingsupp::where('category', $value->id)->whereDate('outgoing_date', '<=', $date22);

            //Current month only
            $incoming = tbl_incomingsupp::where('category', $value->id)->whereBetween('incoming_date', [$date1, $date2]);
            $outgoing = tbl_outgoingsupp::where('category', $value->id)->whereBetween('outgoing_date', [$date1, $date2]);

            $temp['beginning'] = round($incoming_all_past->get()->sum("amount") - $outgoing_all_past->get()->sum("amount"), 2);
            //Get incoming based on from, to, and category, then sum amounts
            $temp['incoming'] = round(tbl_incomingsupp::where("category", $value->id)->whereBetween("incoming_date", [$date1, $date2])->get()->sum("amount"), 2);
            //Beginning + incoming
            $temp['total'] = round($temp['beginning'] + $temp['incoming'], 2);
            //Get outgoing based on from, to, and category, then sum outgoing_amount based on masterlist supplies net price
            $temp['outgoing'] = round(tbl_outgoingsupp::where("category", $value->id)->whereBetween("outgoing_date", [$date1, $date2])->get()->sum("amount"), 2);
            //Stocks = total - outgoing
            $temp['stocks'] = round($temp['total'] - $temp['outgoing'], 2);

            //For computing ending
            $temp['ending'] = 0;
            $ending_q = 0;
            foreach (tbl_masterlistsupp::where("category", $value->id)->get() as $key1 => $value1) {
                $incoming_and_past = tbl_incomingsupp::where('supply_name', $value1->id)->whereDate('incoming_date', '<=', $date2);
                $outgoing_and_past = tbl_outgoingsupp::where('supply_name', $value1->id)->whereDate('outgoing_date', '<=', $date2);
                $incoming = tbl_incomingsupp::where('supply_name', $value1->id)->whereBetween('incoming_date', [$date1, $date2]);

                $a = clone $incoming_and_past;
                $b = clone $outgoing_and_past;
                $aa = clone $incoming;
                $endingquantity = ($a->sum('quantity') - $b->sum('quantity'));
                if ($ending_q > 0 && $aa->sum('quantity') > 0) {
                    $temp['ending'] += $endingquantity * ($aa->sum('amount') / $aa->sum('quantity'));
                } else {
                    $temp['ending'] += $endingquantity * $value1->with_vat_price;
                }
            }
            $temp['ending'] = $temp['ending'];

            //For computing variance
            try {
                $temp['variance'] = round($temp['ending'] - $temp['stocks'], 2);
            } catch (\Throwable $th) {
                $temp['variance'] = 0;
            }

            //Get the supplies id
            $temp['fluctuation'] = number_format(0, 2);
            foreach (tbl_incomingsupp::select('supply_name')->where("category", $value->id)
                ->whereBetween("incoming_date", [$date1, $date2])->groupBy("supply_name")->get()->pluck('supply_name') as $keyx => $valuex) {

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
                }
            }
            $temp['fluctuation'] = number_format($temp['fluctuation'], 2);
            array_push($data, $temp);
        }

        $temp = [];
        $get_sum = 0;
        foreach ($data as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($key1 != 'category') {
                    $temp[$key1] = ($temp[$key1] ?? 0) + $value[$key1];
                } else {
                    if ($t->type == 'excel') {
                        $temp[$key1] = 'GRAND TOTALS';
                    } else {
                        $temp[$key1] = '<b>GRAND TOTALS</b>';
                    }
                }
            }
        }
        array_push($data, $temp);
        $content = [];

        switch ($t->type) {
            case 'pdf':
                if (count($data) > 0) {
                    $content['data'] = $data;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['month' => $t->month];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.inventorysummary', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                //Columns
                $columns = [
                    'SUPPLIES CATEGORY', 'BEGINNING INVENTORY', 'PURCHASES', 'TOTAL INVENTORY',
                    'OUTGOING SUPPLIES', 'STOCKS ON HAND', 'ENDING INVENTORY', 'VARIANCE',
                    'FLUCTUATION',
                ];
                return Excel::download(new InventoryExport($data, $columns), "Inventory Summary Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For sales report
    public function SalesReport(Request $t)
    {
        $array = []; //Main Array

        $data = tbl_pos::where("branch", $t->branch)
            ->whereBetween("created_at", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
            ->selectRaw("sum(quantity) as quantity, sum(sub_total_discounted) as sub_total_discounted, branch, max(created_at) as created_at, reference_no")
            ->groupby(["branch", "reference_no"])
            ->get();
        $ar = [];
        foreach (tbl_pos::where("branch", $t->branch)->groupBy("branch")->pluck("branch") as $key => $value) {
            $group = []; //Inner Array

            $total_sa = 0; //Total Sales Amount
            $total_qt = 0;
            //Each branch add to inner array
            foreach ($data as $key1 => $value1) {
                $total_sa += $value1->sub_total_discounted;
                $total_qt += $value1->quantity;
                $ar = [
                    'branch' => $value1->branch_name_details['branch_name'],
                    'reference_no' => $value1->reference_no,
                    'sales_amount' => $value1->sub_total_discounted,
                    'created_at' => $value1->created_at,
                    'items' => tbl_pos::where("reference_no", $value1->reference_no)->get(),
                ];
                array_push($group, $ar);
            }

            //Add inner array to main array (Nested array)
            if ($data->count() > 1) {
                $ar = [
                    'branch' => ($t->type == 'excel' ? ($t->type == 'excel' ? 'TOTAL' : '<b>TOTAL</b>') : ''),
                    'reference_no' => ($t->type == 'excel' ? 'TOTAL' : ($t->type == 'excel' ? 'TOTAL' : '<b>TOTAL</b>')),
                    'sales_amount' => ($t->type == 'excel' ? $total_sa : number_format($total_sa, 2)),
                    'created_at' => $total_qt,
                    'items' => false,
                ];
                array_push($group, $ar);
                array_push($array, $group);
            }
        }
        switch ($t->type) {
            case 'pdf':
                if (count($array) > 0) {
                    $content['data'] = $array;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['from' => $t->from, 'to' => $t->to, 'branch' => tbl_branches::where("id", $t->branch)->first()->branch_name];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.sales', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                //Columns
                $columns = ['REFERENCE', 'SALES AMOUNT', 'DATE', 'PRODUCT(S)', 'QUANTITY', 'TOTAL PRICE'];
                //Data
                $dataitems = [];

                foreach ($array as $key => $value) {
                    $groupings = [];

                    foreach ($value as $key1 => $value1) {

                        if ($value1['items']) {
                            foreach ($value1['items'] as $key => $value2) {
                                $temp = [];
                                if ($key == 0) {
                                    $temp['reference_no'] = $value1['reference_no'];
                                    $temp['sales_amount'] = $value1['sales_amount'];
                                    $temp['date'] = date("Y-m-d", strtotime($value1['created_at']));
                                } else {
                                    $temp['reference_no'] = '';
                                    $temp['sales_amount'] = '';
                                    $temp['date'] = '';
                                }
                                $temp['product_name'] = $value2['product_name_details']['product_name'];
                                $temp['quantity'] = $value2['quantity'];
                                $temp['total_amount'] = $value2['sub_total_discounted'];
                                array_push($dataitems, $temp);
                            }
                        } else {
                            $temp = [];
                            $temp['reference_no'] = $value1['reference_no'];
                            $temp['sales_amount'] = '';
                            $temp['date'] = '';
                            $temp['product_name'] = '';
                            $temp['quantity'] = $value1['created_at'];
                            $temp['total_amount'] = $value1['sales_amount'];
                            array_push($dataitems, $temp);
                        }
                    }
                }
                return Excel::download(new InventoryExport($dataitems, $columns), "Sales Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For transaction report
    public function TransactionReport(Request $t)
    {
        $array = []; //Main Array
        $data = tbl_pos::where('branch', $t->branch)
            ->whereBetween("created_at", [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
            ->selectRaw("
         max(branch) as branch,
         sum(quantity) as quantity,
         sum(sub_total_discounted) as sub_total_discounted,
         max(`change`) as 'change',
         mode,
         max(payment) as payment,
         max(created_at) as created_at,
         reference_no,
         max(discount) as discount ")
            ->orderBy('created_at', "asc")
            ->groupby(["mode", "reference_no", "branch"])->get();

        $group = []; //Inner Array
        $group1 = []; //Inner Array
        $total_p = 0; //Total Products
        $total_a = 0; //Total Amount
        $total_t = 0; //Total Payment
        $total_d = 0; //Total Discount
        //Each branch add to inner array
        foreach ($data as $key1 => $value1) {
            $total_p += $value1->quantity;
            $total_a += $value1->sub_total_discounted;
            $total_t += $value1->payment;
            $total_d += $value1->discount;
            $ar = [
                'branch' => tbl_branches::where("id", $value1->branch)->first()->branch_name,
                'mode' => $value1->mode,
                'reference_no' => $value1->reference_no,
                'total_prod' => $value1->quantity,
                'total_amount' => $value1->sub_total_discounted,
                'change' => $value1->change,
                'created_at' => $value1->created_at,
                'payment' => $value1->payment,
                'discount' => $value1->discount,
            ];
            array_push($group, $ar);
        }
        $arr_top['body'] = $group;
        //Add inner array to main array (Nested array)
        if ($data->count() > 0) {
            $ar = [
                'branch' => ($t->type == 'excel' ? ($t->type == 'excel' ? 'TOTAL' : '<b>TOTAL</b>') : ''),
                'mode' => '',
                'reference_no' => ($t->type == 'excel' ? '' : ($t->type == 'excel' ? 'TOTAL' : '<b>TOTAL</b>')),
                'total_prod' => $total_p,
                'total_amount' => $total_a,
                'change' => null,
                'created_at' => null,
                'payment' => $total_t,
                'discount' => $total_d,
            ];
            array_push($group1, $ar);
            $arr_top['footer'] = $group1;
            array_push($array, $arr_top);
        }

        switch ($t->type) {
            case 'pdf':
                if (count($array) > 0) {
                    $content['data'] = $array;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['from' => $t->from, 'to' => $t->to, 'branch' => tbl_branches::where("id", $t->branch)->first()->branch_name];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.transaction', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                if (count($array) > 0) {
                    //Columns
                    $columns = ['BRANCH', 'REFERENCE NO', 'DATE', 'MODE', 'TOTAL PRODUCT(S)', 'BILL TOTAL', 'PAYMENT', 'DISCOUNT', 'CHANGE'];
                    //Data
                    $dataitems = [];
                    $t = 0;
                    foreach ($array as $key => $value) {
                        foreach ($value['body'] as $keyx => $value1) {
                            $temp = [];
                            $temp['branch'] = $value1['branch'];
                            $temp['reference_no'] = $value1['reference_no'];
                            $temp['created_at'] = ($value1['created_at'] ? date("Y-m-d", strtotime($value1['created_at'])) : null);
                            $temp['mode'] = $value1['mode'];
                            $temp['total_prod'] = $value1['total_prod'];
                            $temp['total_amount'] = $value1['total_amount'];
                            $temp['payment'] = $value1['payment'];
                            $temp['discount'] = $value1['discount'];
                            $temp['change'] = $value1['change'];
                            array_push($dataitems, $temp);
                        }
                        foreach ($value['footer'] as $keyx => $value2) {
                            $temp = [];
                            $temp['branch'] = $value2['branch'];
                            $temp['reference_no'] = '';
                            $temp['created_at'] = '';
                            $temp['mode'] = '';
                            $temp['total_prod'] = $value2['total_prod'];
                            $temp['total_amount'] = $value2['total_amount'];
                            $temp['payment'] = $value2['payment'];
                            $temp['discount'] = $value2['discount'];
                            $temp['change'] = '';
                            array_push($dataitems, $temp);
                        }
                    }
                    return Excel::download(new InventoryExport($dataitems, $columns), "Transaction Report.xlsx");
                }
                return false;
                break;
            default:
                break;
        }
    }

    //For purchase order report
    public function PurchaseOrderReport(Request $t)
    {
        //Filter if all or specific
        $where = ($t->supplier == 'All' ? "   supplier_name != -1 " : ' supplier_name =' . $t->supplier);
        $data = []; //Main array
        //Get all the supplier then loop
        $g_total_a = 0; //Grand Total

        foreach (tbl_purchaseord::whereRaw($where)
            ->whereBetween('incoming_date', [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
            ->groupBy('supplier_name')->pluck('supplier_name') as $key => $value) {
            $group = [];
            $total_a = 0;

            //Each supplier add to inner array
            foreach (tbl_purchaseord::with("supplier_name")->where("supplier_name", $value)
                ->whereBetween('incoming_date', [date("Y-m-d 00:00:00", strtotime($t->from)), date("Y-m-d 23:59:59", strtotime($t->to))])
                ->get() as $key1 => $value1) {

                $total_a += $value1->amount;

                $g_total_a += $value1->amount;

                $ar = [
                    'supplier_name' => $value1->supplier_name_details['supplier_name'],
                    'description' => $value1->supplier_name_details['description'],
                    'invoice_number' => $value1->invoice_number,
                    'amount' => $value1->amount,
                    'incoming_date' => $value1->incoming_date,
                ];
                array_push($group, $ar);
            }

            //Add inner to main array (Nested array)
            $ar = [
                'supplier_name' => ($t->type == 'excel' ? 'TOTAL' : ''),
                'description' => '',
                'invoice_number' => ($t->type == 'excel' ? '' : '<b>TOTAL</b>'),
                'amount' => $total_a,
                'incoming_date' => '',
            ];
            array_push($group, $ar);
            array_push($data, $group);
        }

        $content = [];
        switch ($t->type) {
            case 'pdf':
                if (count($data) > 0) {
                    $content['data'] = $data;
                    $content['amount'] = $g_total_a;
                    $content['process_by'] = auth()->user()->name;
                    $content['param'] = ['from' => $t->from, 'to' => $t->to, 'supplier' => $t->supplier];
                    if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
                        $content['img'] = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
                    } else {
                        $content['img'] = null;
                    }
                    $pdf = PDF::loadView('reports.purchaseorder', $content, [], [
                        'format' => 'A4-L',
                    ]);
                    return $pdf->stream();
                } else {
                    return false;
                }
                break;
            case 'excel':
                //Columns
                $columns = ['SUPPLIER NAME', 'INVOICE NUMBER', 'AMT', 'DATE'];
                //Data
                $dataitems = [];
                $grandTotal = 0;

                foreach ($data as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        $temp = [];
                        $temp['supplier_name'] = $value1['supplier_name'] ? $value1['supplier_name'] . " " . '(' . $value1['description'] . ')' : null;
                        $temp['invoice_number'] = $value1['invoice_number'];
                        $temp['amount'] = $value1['amount'];
                        $temp['incoming_date'] = ($value1['incoming_date'] ? date("Y-m-d", strtotime($value1['incoming_date'])) : null);
                        array_push($dataitems, $temp);
                    }
                    $grandTotal += $value1['amount'];
                }

                $temp_gt['supplier_name'] = 'GRAND TOTAL';
                $temp_gt['invoice_number'] = '';
                $temp_gt['amount'] = '';
                $temp_gt['incoming_date'] = '';
                $temp_gt['amount'] = $grandTotal;
                array_push($dataitems, $temp_gt);
                return Excel::download(new InventoryExport($dataitems, $columns), "Purchase Order Report.xlsx");
                break;
            default:
                break;
        }
    }

    //For retrieving sales list
    public function ListSP(Request $t)
    {
        if (auth()->user()->can('Access POS')) {
            $table = tbl_pos::with(["branch"])
                ->where('branch', auth()->user()->branch)
                ->where('cashier', auth()->user()->id)
                ->whereBetween('created_at', [date("Y-m-d", strtotime(date('Y') . '-' . date('m') . '-01')), date('Y-m-t', strtotime(date("Y") . '-' . date('m') . '-' . date('t')))])
                ->selectRaw("sum(quantity) as quantity, sum(sub_total_discounted) as sub_total_discounted, branch, max(created_at) as created_at, reference_no")
                ->orderBy('created_at', "desc")
                ->groupby(["branch", "reference_no"]);
        } else {
            $table = tbl_pos::with(["branch"])
                ->selectRaw("sum(quantity) as quantity, sum(sub_total_discounted) as sub_total_discounted, branch, max(created_at) as created_at, reference_no")
                ->groupby(["branch", "reference_no"]);
        }

        if ($t->branch) {
            $table->where("branch", $t->branch);
        }

        if ($t->search) {
            $table->where("reference_no", "like", "%" . $t->search . "%");
        }
        if ($t->dateFromSP && $t->dateUntilSP) {
            $table->whereBetween("created_at", [date("Y-m-d 00:00:00", strtotime($t->dateFromSP)), date("Y-m-d 23:59:59", strtotime($t->dateUntilSP))]);
        }

        $return = [];
        $row = 1;
        foreach ($table->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $row++;
            $temp['quantity'] = $value->quantity;
            $temp['sub_total_discounted'] = number_format($value->sub_total_discounted, 2, ".", ",");
            $temp['branch'] = $value->branch;
            $temp['branch_name'] = tbl_branches::where("id", $value->branch)->first()->branch_name;
            $temp['created_at'] = date("Y-m-d", strtotime($value->created_at));
            $temp['reference_no'] = $value->reference_no;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page);
    }

    //For retrieving sales info
    public function getSPInfo(Request $t)
    {
        return tbl_pos::with(["branch", 'product_name', 'cashier'])->where("reference_no", $t->reference_no)->get();
    }

    //For generating receipt
    public function Receipt(Request $t)
    {
        if ($t->reference_no) {
            $data = tbl_pos::where("reference_no", $t->reference_no)->get();

            $temp = [];
            $temp['data'] = $data;
            $temp['branch'] = $data[0]->branch_name_details->branch_name;
            $temp['branch_location'] = $data[0]->branch_name_details->location;
            $temp['branch_number'] = $data[0]->branch_name_details->phone_number;
            $temp['reference_no'] = $data[0]->reference_no;
            $temp['created_at'] = $data[0]->created_at;
            $temp['mode'] = $data[0]->mode;
            $temp['change'] = $data[0]->change;
            $temp['discount'] = $data[0]->discount;
            $temp['payment'] = $data[0]->payment;

            $data_cloned = clone $data;
            $temp['sub_total'] = $data_cloned->sum('sub_total');
            $data_cloned = clone $data;
            $temp['sub_total_discounted'] = $data_cloned->sum('sub_total_discounted');
            $temp['cashier_name_details'] = User::where("id", $data[0]->cashier)->first()->name;
            $temp['vatable_sales'] = $data_cloned->sum('sub_total') / tbl_pos::first()->vat;
            $temp['vat_amount'] = $data_cloned->sum('sub_total') - ($data_cloned->sum('sub_total') / tbl_pos::first()->vat);

            $data_cloned = clone $data;
            $pdf = PDF::loadView(
                'receipt.receipt',
                $temp,
                [],
                [
                    'format' => ['57', 95 + (9 * $data_cloned->count())],
                    'margin_left' => 2,
                    'margin_right' => 2,
                    'margin_top' => 4,
                    'margin_bottom' => 2,
                ]
            );
            return $pdf->stream();
        }
    }
}
