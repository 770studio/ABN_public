<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\IncomPays;
use App\PenaltyDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Lead;
use App\Instalment;
use App\RefinRate;
use App\Penalty;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CriticalException;
use App\Schedule;
use App\PenaltyNoCorrection;
use Exception;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;



#TODO удалить из роутов в том числе
class ScheduleController_notused extends Controller

{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public $contract = null;

    public function __construct()
    {
        $this->middleware(['auth','onlyForPayments']);
       // DB::connection()->enableQueryLog();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('payments');
    }


    public function sheet()
    {



        /*      5.Начисленные пени (перерасчет):
№;
дата начисления;
пени, руб;
примечание.
*/

        $penalty = DB::table('penalty')
            ->select(DB::raw(
                "
                        penalty_date,
                        penalty_sum,
                        comments
                        "))
             ->where('lead_id','=', $lead_id)
            ->orderBy('penalty_date','ASC')
            ->get();












        EXIT;
        DB::connection()->enableQueryLog();
        DB::transaction(function ()   {
/*        $p = new Penalty;
        $p->fill(
            [
                'lead_id' => '7777778888',
                'overdue_days' => 1,
                'overdue_sum' => 666,
                'penalty_sum' =>  666,

            ]

        );
        $p->save();*/


            Penalty::find(1)->update(['comments'=> 'uuuuuu55555555']);
        });

        //
EXIT;
        //  $ps = PenaltyNoCorrection::where('id' , '<', 100 )  ->get(); // ->with('Penalty')

dd($ps);
        $pss = [];
        foreach($ps as $p) {


            dd( $p->Penalty->id, $p->Penalty->PenaltyDaily->sum('paid'),  DB::getQueryLog() );
            array_push($pss, $p->Penalty );
        }


       // dd( $ps->first() );




        DB::transaction(function ()   {
            $now = Carbon::now();
            $lead_id = 23077287 ;
            $ps = PenaltyDaily::where('lead_id', $lead_id )->get();

            DB::table('__penalty_jobs_copy')
                ->where('id', 5 )
                ->update(['done' => 5]);

            DB::table('__settings5645')
                ->update(['updated_by' => 1]);

        });








        //Начисленные пени (расчет)
        /*              номер;
                        дата начисления;
                        сумма начисления;
                        дата погашения;
                        просрочка в днях;
                        пени, %;
                        начислено пени, руб.
        */

        $lead_id = 23077287 ; // 23077287

        $contract = Instalment::find($lead_id)->first();
        if(!$contract) {
            // нет контракта ???
        } else {
            switch ($contract->penalty_type){
                case 1:
                case 4:
                    $penalty_percent = $contract->penalty_value;
                    break;
                case 2:
                    $penalty_percent = "1/360";
                    break;
                case 3:
                    $penalty_percent = "1/300";
                    break;
                default:  $penalty_percent = null;
            }



            $ps = PenaltyDaily::where('lead_id', $lead_id )->get();

            /*      "id" => 14
                    "lead_id" => 23077287
                    "date" => "2019-09-12"
                    "overdue_sum" => "456263.00"
                    "penalty_sum" => "91.89"
                    "created_at" => "2019-09-13 15:58:01"
                    "updated_at" => "2019-09-13 15:58:01"
                    "penalty_id" => 13
                    "refin_id" => 0
                    "status" => 0
                    "paid" => null
                    "paid_at" => null*/

            $Data = collect();
            $n = 1;

            // группы штрафов образованы непреывной просрочкой
            // просрочка прерыается в случае:
            //  1. частичной или полной оплаты
            //  2. изменения суммы начисления (долга) , например пришел срок очередного платежа и сумма платежа добавилась к долгу

            $pn = 0;
            foreach($ps as $n => $p_row) {

             if($n == 0) {
                 // ничего пока нет
             } else {
                 $prev_ps = $ps[$n-1];
                 if($p_row->paid > 0 ||  $prev_ps->overdue_sum!=  $p_row->overdue_sum ) {
                     // новый штраф
                     $pn++;
                     $Data[$pn] = [
         /*                дата начисления;
                        сумма начисления;
                        дата погашения;
                        просрочка в днях;
                        пени, %;
                        начислено пени, руб.*/

                        'date' => $p_row->date,
                        'sum' => $p_row->penalty_sum,
                        'date_payout' => $p_row->paid_at,
                        'days' => 1,

                     ];
                 } else {
                     // доливка

                 }

             }


                if( !isset($Data[$n]) ) {
                    // ничего еще не заносили
                    $Data->push();
                }
            }
            dd($ps);

        }











/*
        2.График погашения задолженности.

№ по порядку;
Дата;
Начислено;
Зачтено в счет оплаты;
Отсрочка платежа;
Пени;
Примечание.
*/

        $lead_id = 23077287 ; // 23077287


        $paid =  IncomPays::where('lead_id', $lead_id )->get();

        // DB::connection()->enableQueryLog();


        //dd(   DB::getQueryLog() );


       $Data = collect();
        foreach(Schedule::where('lead_id', $lead_id )->get() as $n => $sch_row) {


            $sum_on_schedule = $sch_row->sum_total > 0 ? $sch_row->sum_total : $sch_row->sum_payment ;  // Начислено;


            // Зачтено в счет оплаты;   это сумма  , которая была уплачена до даты платежа включительно

            $profile = collect();
            foreach($paid ->where('incomDate', '<=', $sch_row->payment_date )->where('sum', '>', 0 ) as $p) {

                if($p->sum >= $sum_on_schedule ) {
                    $p->sum-= $sum_on_schedule; // сумма пагашена платежем
                    $profile->push(['date'=>$p->incomDate, 'sum' => $sum_on_schedule ] );    // внесено
                    break; // выходим из цикла
                } else  {  // сумма частично погашена платежем
                    $sum_on_schedule-=$p->sum; // остаток платежа
                    $profile->push(['date'=>$p->incomDate, 'sum' => $p->sum ] );  // внесено
                    $p->sum = 0; // весь платеж ушел


                }
            }


            $Data->push(
                 [

                     'n' => $n, // № по порядку;
                     'date' => $sch_row->payment_date, // Дата;
                     'sum_on_schedule' => $sum_on_schedule,  // Начислено;
                     'sum_paid' => $profile->sum('sum' ),  // Зачтено в счет оплаты;
                     'sum_penalty' =>  PenaltyDaily::join('penalty','penalty_daily.penalty_id', '=', 'penalty.id')
                                                 ->where('penalty_daily.lead_id', $lead_id )
                                                 ->where('penalty.status', '!=', 3) // не отменено
                                                 ->where('penalty_daily.date', '<=',  $sch_row->payment_date )
                                                 ->sum('penalty_daily.penalty_sum'),   // Пени; где дата <= даты платежа по графику
                     'note' => $profile ,  // Примечание.

                 ]
            );
        }

        dd($Data);
    }
}



