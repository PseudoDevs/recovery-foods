<?php

namespace App\Models;

use App\Models\tbl_masterlistprod;
use App\User;
use Illuminate\Database\Eloquent\Model;

class tbl_requestprod extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['product_name_details', 'user_details', 'quantity_available'];

    //For product name info
    public function getProductNameDetailsAttribute()
    {
        return tbl_masterlistprod::where("id", $this->product_name)->first();
    }

    //For user info
    public function getUserDetailsAttribute()
    {
        return $this->hasOne(User::class, 'id', 'user')->first();
    }

    //For computing available quantity
    public function getQuantityAvailableAttribute()
    {
        return tbl_incomingprod::where("product_name", $this->product_name)->get()->sum("quantity")
         - tbl_outgoingprod::where("product_name", $this->product_name)->get()->sum("quantity");
    }
}
