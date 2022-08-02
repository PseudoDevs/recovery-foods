<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function respond($data)
    {
        return response()->json($data);
    }

    public function getConfigDate()
    {
        $isAdmin = DB::table('model_has_roles')->where([['role_id',1],['model_id',request()->user()->id]])->get();

        if(!$isAdmin->isEmpty()){
            return [Carbon::now()->format('Y-m-d'),Carbon::now()->endOfWeek()->format('Y-m-d'),Carbon::now()];
        }
        // For Friday cutoff validation
        if (Carbon::now()->isFriday() && Carbon::now()->format('A') == 'PM' && Carbon::now()->format('H') >= 12 && Carbon::now()->format('i') > 0 ) {
            return [Carbon::now()->startOfWeek()->addDays(14)->format('Y-m-d'),Carbon::now()->endOfWeek()->addDays(14)->format('Y-m-d'),Carbon::now()];
        }
        if(Carbon::now()->isSaturday() || Carbon::now()->isSunday())
        {
            return [Carbon::now()->startOfWeek()->addDays(14)->format('Y-m-d'),Carbon::now()->endOfWeek()->addDays(14)->format('Y-m-d')];
        }
        return [Carbon::now()->startOfWeek()->addDays(7)->format('Y-m-d'),Carbon::now()->endOfWeek()->addDays(7)->format('Y-m-d'),Carbon::now()];
    }

    public function getConfigDate1(){
        return [Carbon::now()->format('Y-m-d'),Carbon::now()->format('h:i A'),Carbon::now()];
    }
}
