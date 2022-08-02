<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\tbl_company;
use App\Models\tbl_vat;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    //For uploading logo
    public function uploadLogo(Request $t)
    {
        try {
            $img = $t->file('file');
            $newfilename = $img->getClientOriginalName() . "~" . time() . '.' . $img->getClientOriginalExtension();
            $input['imagename'] = $newfilename;
            $img->storeAs('public/logo/', $input['imagename']);
            return ['filename' => $img->getClientOriginalName(), 'tempfile' => $newfilename, 'path' => url('/storage/logo/' . $input['imagename'])];
        } catch (\Throwable $th) {
        }
    }

    //For saving logo
    public function storeLogo(Request $t)
    {
        tbl_company::create(['logo' => $t->attachment]);
    }

    //For removing logo
    public function deleteLogo()
    {
        tbl_company::where('active', '!=', 0)->update(['active' => 0]);
    }

    //For retrieving logo
    public function getLogo()
    {
        if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
            $logo = (tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo ? url('/storage/logo/' . tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo) : '');
            $filename = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
            $temp = explode('~', tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo)[0];
        } else {
            $logo = '';
            $filename = '';
            $temp = '';
        }
        return ['path' => $logo, 'tempfile' => $filename, 'filename' => $temp];
    }

    //For retrieving VAT
    public function getVat(Request $t)
    {
        return tbl_vat::where("type", $t->type)->orderby("created_at", 'desc')->first();
    }

    //For saving data
    public function save(Request $data)
    {
        if ($data->prod_vat) {
            if (tbl_vat::where(["type" => 'p'])->get()->count() > 0) {
                tbl_vat::where(["type" => 'p'])
                    ->update(['vat' => $data->prod_vat,
                        'type' => 'p',
                        'cashier' => auth()->user()->id]);
            } else {
                tbl_vat::create(['vat' => $data->prod_vat,
                    'type' => 'p',
                    'cashier' => auth()->user()->id]);
            }
        }
        if ($data->supp_vat) {
            if (tbl_vat::where(["type" => 's'])->get()->count() > 0) {
                tbl_vat::where(["type" => 's'])
                    ->update(['vat' => $data->supp_vat,
                        'type' => 's',
                        'cashier' => auth()->user()->id]);
            } else {
                tbl_vat::create(['vat' => $data->supp_vat,
                    'type' => 's',
                    'cashier' => auth()->user()->id]);
            }
        }

        if ($data->id) {
            return tbl_company::where('id', $data->id)->update(['logo' => $data->attachment, "history" => $data->history, "mission" => $data->mission, "vision" => $data->vision]);
        } else {
            return tbl_company::create(['logo' => $data->attachment, "history" => $data->history, "mission" => $data->mission, "vision" => $data->vision]);
        }

    }

    //For retrieving data
    public function get()
    {
        $return = [];
        if (tbl_company::where("active", 1)->orderBy('id', 'desc')->get()->count() > 0) {
            $logo = url('/storage/logo/' . tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo);
            $filename = tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo;
            $temp = explode('~', tbl_company::where("active", 1)->orderBy('id', 'desc')->first()->logo)[0];
        } else {
            $logo = null;
            $filename = null;
            $temp = null;
        }

        try {
            //code...
            $return['attachment'] = ['path' => $logo, 'tempfile' => $filename, 'filename' => $temp];
            $return['prod_vat'] = tbl_vat::where("type", 'p')->orderby("created_at", 'desc')->first()->vat;
            $return['supp_vat'] = tbl_vat::where("type", 's')->orderby("created_at", 'desc')->first()->vat;
            $return['history'] = tbl_company::first()->history;
            $return['mission'] = tbl_company::first()->mission;
            $return['vision'] = tbl_company::first()->vision;
            $return['id'] = tbl_company::first()->id;
        } catch (\Throwable $th) {
            //throw $th;
            return null;
        }
        return $return;
    }
}
