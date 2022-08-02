<?php

namespace App\Models;

use App\Models\tbl_masterlistsupp;
use App\Models\tbl_suppcat;
use App\Models\tbl_supplist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class tbl_incomingsupp extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['with_vat_price', 'process_by', 'quantity_difference', 'quantity_amount', 'category_details', 'supply_name_details', 'supplier_details', 'fluctuation'];

    //For supply categories
    public function category()
    {
        return $this->hasOne(tbl_suppcat::class, 'id', 'category');
    }

    //For processing
    public function getProcessByAttribute()
    {
        return auth()->user()->name;
    }

    //For masterlist supply names
    public function supply_name()
    {
        return $this->hasOne(tbl_masterlistsupp::class, 'id', 'supply_name');
    }

    //For suppliers info
    public function supplier()
    {
        return $this->hasOne(tbl_supplist::class, 'id', 'supplier');
    }

    //For getting the quantity difference
    public function getQuantityDifferenceAttribute()
    {
        $incoming = DB::table("tbl_incomingsupps")->where("supply_name", $this->supply_name)->sum("quantity");
        $outgoing = DB::table("tbl_outgoingsupps")->where("supply_name", $this->supply_name)->sum("quantity");

        return ceil($incoming - $outgoing);
    }

    //For supply categories
    public function getCategoryDetailsAttribute()
    {
        return tbl_suppcat::where("id", $this->category)->first();
    }

    //For masterlist supply names
    public function getSupplyNameDetailsAttribute()
    {
        return tbl_masterlistsupp::where("id", $this->supply_name)->first();
    }

    //For supplier info
    public function getSupplierDetailsAttribute()
    {
        return tbl_supplist::where("id", $this->supplier)->first();
    }

    // //For getting quantity amount
    // public function getQuantityAmountAttribute()
    // {
    //     $date1 = date("Y-m-d 00:00:00", strtotime(date("m") . "-01-" . date("Y")));
    //     $date2 = date("Y-m-t 23:59:59", strtotime(date("m") . '/' . date("t") . '/' . date("Y")));
    //     $incoming = tbl_incomingsupp::where("id", $this->id)->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($date1)), date("Y-m-t 23:59:59", strtotime($date2))])->sum("amount");
    //     return $incoming;
    // }

    //For getting net price
    public function getNetPriceAttribute()
    {
        return tbl_masterlistsupp::where("id", $this->supply_name)->first()->net_price;
    }

    //For getting fluctuation
    public function getFluctuationAttribute()
    {
        //For list with VAT column
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

        //Get the amount from incoming
        $get_amount = tbl_incomingsupp::where("supply_name", $this->supply_name)
            ->whereBetween('incoming_date', [$date1, $date2]);
        $get_quantity = $get_amount = tbl_incomingsupp::where("supply_name", $this->supply_name)
            ->whereBetween('incoming_date', [$date1, $date2]);

        //Get average amount
        if ($get_quantity->sum('amount') < 1) {
            $get_wov = 0;
        } else {
            $get_wov = $get_quantity->sum('quantity')
                 *
                (($get_amount->sum('amount') / $get_quantity->sum('quantity')) -
                tbl_masterlistsupp::where("id", $this->supply_name)->first()->net_price);
        }
        return round($get_wov, 2);
    }

    //For with VAT
    public function getWithVatPriceAttribute()
    {
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

        //Get the amount from incoming
        $get_amount = tbl_incomingsupp::where("supply_name", $this->supply_name)
            ->whereBetween('incoming_date', [$date1, $date2]);
        $get_quantity = tbl_incomingsupp::where("supply_name", $this->supply_name)
            ->whereBetween('incoming_date', [$date1, $date2]);

        //Get average amount
        if ($get_quantity->sum('quantity') > 0) {
            $get_wov = $get_amount->sum('amount') / $get_quantity->sum('quantity');
        } else {
            $get_wov = tbl_masterlistsupp::where('id', $this->supply_name)->first()->net_price;
        }
        return $get_wov;
    }
}
