<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tbl_masterlistprod extends Model
{
    //Always include this code for every model/table created
    protected $guarded = ['id'];
    public $appends = ['diff_quantity', 'without_vat', 'category_details', 'sub_category_details'];

    //For product categories
    public function category()
    {
        return $this->hasOne(tbl_prodcat::class, 'id', 'category');
    }

    //For product subcategories
    public function sub_category()
    {
        return $this->hasOne(tbl_prodsubcat::class, 'id', 'sub_category');
    }

    //For computing quantity difference
    public function getDiffQuantityAttribute()
    {
        return tbl_incomingprod::where("product_name", $this->id)->get()->sum("quantity") - tbl_outgoingprod::where("product_name", $this->id)->get()->sum("quantity");
    }

    //For product categories
    public function getCategoryDetailsAttribute()
    {
        return tbl_prodcat::where("id", $this->category)->first();
    }

    //For product subcategories
    public function getSubCategoryDetailsAttribute()
    {
        return tbl_prodsubcat::where("id", $this->sub_category)->first();
    }

    //For without VAT
    public function getWithoutVatAttribute()
    {
        $without_vat = $this->price ? $this->price / $this->vat : 0;
        return $without_vat;
    }

}
