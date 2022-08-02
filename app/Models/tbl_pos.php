<?php

namespace App\Models;

use App\Models\tbl_branches;
use App\Models\tbl_masterlistprod;
use App\User;
use Illuminate\Database\Eloquent\Model;

class tbl_pos extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['total_amount', 'branch_name_details', 'product_name_details', 'cashier_name_details'];

    //For branch
    public function branch()
    {
        return $this->hasOne(tbl_branches::class, 'id', 'branch');
    }

    //For product name
    public function product_name()
    {
        return $this->hasOne(tbl_masterlistprod::class, 'id', 'product_name');
    }

    //For cashier info
    public function cashier()
    {
        return $this->hasOne(User::class, 'id', 'cashier');
    }

    //For formatting sub total discounted
    public function getTotalAmountAttribute()
    {
        return number_format($this->sub_total_discounted, 2, ".", ",");
    }

    //For branch info
    public function getBranchNameDetailsAttribute()
    {
        return tbl_branches::where("id", $this->branch)->first();
    }

    //For product name info
    public function getProductNameDetailsAttribute()
    {
        return tbl_masterlistprod::where("id", $this->product_name)->first();
    }

    //For cashier info
    public function getCashierNameDetailsAttribute()
    {
        return User::where("id", $this->cashier)->first();
    }

}
