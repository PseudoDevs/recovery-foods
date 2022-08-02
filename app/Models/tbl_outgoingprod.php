<?php

namespace App\Models;

use App\Models\tbl_branches;
use App\Models\tbl_masterlistprod;
use App\Models\tbl_pos;
use App\Models\tbl_prodcat;
use App\Models\tbl_prodsubcat;
use Illuminate\Database\Eloquent\Model;

class tbl_outgoingprod extends Model
{
    // Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['outgoing_amount', 'quantity_diff', 'category_details', 'sub_category_details', 'product_name_details', 'requesting_branch_details'];

    //For product categories
    public function category()
    {
        return $this->hasOne(tbl_prodcat::class, 'id', 'category');
    }

    //For product categories
    public function getCategoryDetailsAttribute()
    {
        return tbl_prodcat::where('id', $this->category)->first();
    }

    //For product subcategories
    public function sub_category()
    {
        return $this->hasOne(tbl_prodsubcat::class, 'id', 'sub_category');
    }

    //For product subcategories
    public function getSubCategoryDetailsAttribute()
    {
        return tbl_prodsubcat::where('id', $this->sub_category)->first();
    }

    //For product names
    public function product_name()
    {
        return $this->hasOne(tbl_masterlistprod::class, 'id', 'product_name');
    }

    //For product names
    public function getProductNameDetailsAttribute()
    {
        return tbl_masterlistprod::where("id", $this->product_name)->first();
    }

    //For branch info
    public function getRequestingBranchDetailsAttribute()
    {
        return tbl_branches::where("id", $this->requesting_branch)->first();
    }

    //For getting quantity difference
    public function getQuantityDiffAttribute()
    {
        return $this->quantity - tbl_pos::where(["product_name" => $this->product_name])->sum('quantity');
    }

    //For branch
    public function requesting_branch()
    {
        return $this->hasOne(tbl_branches::class, 'id', 'requesting_branch');
    }

    //For computing outgoing amount
    public function getOutgoingAmountAttribute()
    {
        return tbl_masterlistprod::where("id", $this->product_name)->first()->price * $this->quantity;
    }
}
