<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Nathanmac\Utilities\Parser\Parser;

class UpdatePriceController extends Controller
{
    public function index(){

        $feed1 = 'https://pb2635.profitbase.ru/export/profitbase_xml/e5c1d61d28e819a59f9ad3821586a0b7';
        $feed2 = 'https://pb2635.profitbase.ru/export/profitbase_xml/e0d44a12f51166fd757529a127cf9d28';
        $feeds = [$feed1,$feed2];
        $arrContextOptions = array(

            "ssl" => array(
                "allow_self_signed" => true,
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        try{
               $dates = array();

                foreach ($feeds as $feed){
                    $dateString = file_get_contents($feed, false, stream_context_create($arrContextOptions),154,25);
                    $date = Carbon::parse($dateString)->subHours(2)->format('d.m.Y H:i:s');
                    $dates[] = $date;
                }
        }
        catch (\Exception $exception){
            return $exception->getMessage();
        }


       // dd($dates);

        return view('admin.update_price.index',compact('dates'));
    }
}
