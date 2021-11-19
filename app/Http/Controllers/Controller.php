<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public static function _toDouble($str)
    {
        return round((float)  preg_replace(['/,/' ,'/[^\d\.]/'   ], ['.' , ''  ], $str), 2);
        // return round( (float) str_replace([','], [''], $str)   , 2  );
        // preg_replace (   [ '/[\,^\d\.]/'   ], [''   ], $str)
    }
    public static function _toMysqlDate($str)
    {
//          $ts = strtotime($str );
//         return  $ts ? date("Y-m-d", $ts  ) : false;
//        $formatter = new \IntlDateFormatter("en_EN", \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
//        $ts = $formatter->parse($str);
//        return  $ts ? date("Y-m-d", $ts) : false;

       // $str = "21 августа 2018 г.";

        $arr = explode(' ',$str);
        unset($arr[3]);
        $str = implode($arr,' ');

        $ts = Carbon::parseFromLocale($str,'ru_RU','Europe/Moscow')->format('Y-m-d');

        return $ts;

    }

    public function apiReply($error = false, $msg = false, $data  = [])
    {
        if (is_array($msg)) {
            // TODO
        }

        if ($error) {
            return ['error' => $msg ];
        }
        return $data;
    }



    public function log($msg, $vars = null)
    {

  return;
        echo   "<br>\n" . Carbon::now()->toDateTimeString() . " :: \t";
        if (isset($this->lead_id)) {
            echo 'id договора:' . $this->lead_id . " :: \t";
        }
        if (isset($this->job->id)) {
            echo 'id задания:' . $this->job->id . " :: \t";
        }
        echo   $msg . " :: \t";
        if (is_array($vars)) {
            foreach ($vars as $key=> $var) {
                echo $key . ":" . $var . "\t";
            }
        } else {
            var_dump($vars);
        }
    }
}
