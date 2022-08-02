<?php

namespace App\Models;

use App\Models\tbl_incomingsupp;
use App\Models\tbl_suppcat;
use App\Models\tbl_supplist;
use Illuminate\Database\Eloquent\Model;

class tbl_masterlistsupp extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['with_vat', 'without_vat', 'format_net_price', 'category_name', 'category_details', 'supplier_name_details', 'without_vat_price', 'with_vat_price'];

    //For supply categories
    public function category()
    {
        return $this->hasOne(tbl_suppcat::class, 'id', 'category');
    }
    
    //For supplier info
    public function supplier()
    {
        return $this->hasOne(tbl_supplist::class, 'id', 'supplier');
    }

    //For with VAT
    public function getWithVatAttribute()
    {
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));
        $incoming = 0;

        try {
            $get_specific_item_amount = tbl_incomingsupp::where("supply_name", $this->id)
                ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($date1)), date("Y-m-t 23:59:59", strtotime($date2))]);

            $get_specific_item_quantity = tbl_incomingsupp::where("supply_name", $this->id)
                ->whereBetween("incoming_date", [date("Y-m-d 00:00:00", strtotime($date1)), date("Y-m-t 23:59:59", strtotime($date2))]);

            $incoming = number_format($get_specific_item_amount->get()->sum('amount') / $get_specific_item_quantity->get()->sum("quantity"), 2, ".", ",");
        } catch (\Throwable $th) {
            $incoming = $this->net_price;
        }
        return $this->vatable == 0 ? round($incoming,2) : round($this->net_price, 2);
    }

    //For without VAT
    public function getWithoutVatAttribute()
    {
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

        //Get the amount from incoming
        $get_amount = tbl_incomingsupp::where("supply_name", $this->id)
            ->whereBetween('incoming_date', [$date1, $date2]);
        $get_quantity = $get_amount = tbl_incomingsupp::where("supply_name", $this->id)
            ->whereBetween('incoming_date', [$date1, $date2]);
        
        //Get average amount
        if ($get_quantity->sum('quantity') > 0) {
            $get_wov = $get_amount->sum('amount') / $get_quantity->sum('quantity');
        } else {
            $get_wov = $this->net_price;
        }

        //Get VAT
        if ($this->vatable == 1) {
            if ($get_wov > 0) {
                $get_wov = $get_wov / $this->vat;
            } else {
                $get_wov = $this->net_price / $this->vat;
            }
        }
        return round($get_wov, 2);
    }

    //For formatting net price
    public function getFormatNetPriceAttribute()
    {
        return number_format($this->net_price, 2);
    }

    //For formatting with VAT
    public function getFormatWithVatAttribute()
    {
        return number_format($this->with_vat, 2);
    }

    //For supply categories
    public function getCategoryNameAttribute()
    {
        return $this->hasOne(tbl_suppcat::class, "id", "category")->first()->supply_cat_name;
    }

    //For supply categories
    public function getCategoryDetailsAttribute()
    {
        return tbl_suppcat::where("id", $this->category)->first();
    }

    //For supplier info
    public function getSupplierNameDetailsAttribute()
    {
        return tbl_supplist::where("id", $this->supplier)->first();
    }

    //For without vat
    public function getWithoutVatPriceAttribute()
    {
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

        //Get the amount from incoming
        $get_amount = tbl_incomingsupp::where("supply_name", $this->id)
            ->whereBetween('incoming_date', [$date1, $date2]);
        $get_quantity = $get_amount = tbl_incomingsupp::where("supply_name", $this->id)
            ->whereBetween('incoming_date', [$date1, $date2]);
        
        //Get average amount
        if ($get_quantity->sum('quantity') > 0) {
            $get_wov = $get_amount->sum('amount') / $get_quantity->sum('quantity');
        } else {
            $get_wov = $this->net_price;
        }

        //Get VAT
        if ($this->vatable == 1) {
            if ($get_wov > 0) {
                $get_wov = $get_wov / $this->vat;
            } else {
                $get_wov = $this->net_price / $this->vat;
            }
        }
        return round($get_wov, 2);
    }

    //For with VAT
    public function getWithVatPriceAttribute()
    {
        $date1 = date("Y-m-d 00:00:00", strtotime(date("Y") . "-" . date("m") . "-01"));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("Y") . '-' . date("m") . '-' . date("t")));

        //Get the amount from incoming
        $get_amount = tbl_incomingsupp::where("supply_name", $this->id)
            ->whereBetween('incoming_date', [$date1, $date2]);
        
        $get_quantity = tbl_incomingsupp::where("supply_name", $this->id)
            ->whereBetween('incoming_date', [$date1, $date2]);
        
        //Get average amount
        $get_wov = $this->net_price;
        if ($get_quantity->sum('quantity') > 0) {
            $get_wov = $get_amount->sum('amount') / $get_quantity->sum('quantity');
            return $get_wov;
        } elseif ($get_quantity->sum('quantity') < 1) {
            return $this->net_price;
        }
    }
 
    // //For getting measures 
    //  public function measures()
    //  {
    //      return $this->hasMany(tbl_measures::class, 'supply_id', 'id');
    //  }
     
}
