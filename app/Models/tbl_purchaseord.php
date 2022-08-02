<?php

namespace App\Models;

use App\Models\tbl_supplist;
use Illuminate\Database\Eloquent\Model;

class tbl_purchaseord extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['format_amount', 'supplier_name_details'];

    //For supplier name
    public function supplier_name()
    {
        return $this->hasOne(tbl_supplist::class, 'id', 'supplier_name');
    }

    //For formatting amount
    public function getFormatAmountAttribute()
    {
        return number_format($this->amount, 2, ".", ",");
    }

    //For supplier info
    public function getSupplierNameDetailsAttribute()
    {
        return tbl_supplist::where("id", $this->supplier_name)->first();
    }
}
