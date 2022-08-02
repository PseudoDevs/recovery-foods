<?php

namespace App\Imports;

use App\Models\Accumulation;
use Maatwebsite\Excel\Concerns\ToModel;

class AccumulationImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Accumulation([

            "policy_no" =>          $row[0],
            "endt_no" =>            $row[1],
            "inception" =>          $row[2],
            "expiry" =>             $row[3],
            "insured_code" =>       $row[4],
            "insured" =>            $row[5],
            "location_of_risk" =>   $row[6],
            "premium" =>            $row[7],
            "our_si" =>             $row[8],
            "eq_si" =>              $row[9],
            "ty_si" =>              $row[10],
            "fl_si" =>              $row[11],
            "full_si" =>            $row[12],
            "our_si_or" =>          $row[13],
            "our_si_tty" =>         $row[14],
            "our_si_fac" =>         $row[15],
            "our_si_quo" =>         $row[16],
            "cresta_id" =>          $row[17],
            "block" =>              $row[18],
            "zip_code" =>           $row[19],
            "suffix" =>             $row[20],
            "eq_zone" =>            $row[21],
            "ty_zone" =>            $row[22],
            "fl_zone" =>            $row[23],
            "gps_long" =>           $row[24],
            "gps_lat" =>            $row[25],
            "unit_no" =>            $row[26],
            "report_code" =>        $row[27],
            "username" =>           $row[28],
        ]);
    }
}
