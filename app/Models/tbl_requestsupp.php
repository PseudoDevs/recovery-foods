<?php

namespace App\Models;

use App\Models\tbl_masterlistsupp;
use App\User;
use Illuminate\Database\Eloquent\Model;

class tbl_requestsupp extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['supply_name_details', 'user_details', 'quantity_available'];

    //For supply name info
    public function getSupplyNameDetailsAttribute()
    {
        return tbl_masterlistsupp::where("id", $this->supply_name)->first();
    }

    //For user info
    public function getUserDetailsAttribute()
    {
        return $this->hasOne(User::class, 'id', 'user')->first();
    }

    //For computing available quantity
    public function getQuantityAvailableAttribute()
    {
        $date1 = date("Y-m-d 00:00:00", strtotime(date("m") . "-01-" . date("Y")));
        $date2 = date("Y-m-t 23:59:59", strtotime(date("m") . '/' . date("t") . '/' . date("Y")));
        return tbl_incomingsupp::where('supply_name', $this->supply_name)->whereDate('incoming_date', '<=', $date2)->sum('quantity')
         - tbl_outgoingsupp::where('supply_name', $this->supply_name)->whereDate('outgoing_date', '<=', $date2)->sum('quantity');
    }
}
