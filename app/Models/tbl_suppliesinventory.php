<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tbl_suppliesinventory extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['category_details', 'supply_name_details'];

    public function getCategoryDetailsAttribute()
    {
        return tbl_suppcat::where("id", $this->category)->first();
    }
    public function getSupplyNameDetailsAttribute()
    {
        return tbl_masterlistsupp::where("id", $this->supply_name)->first();
    }
}
