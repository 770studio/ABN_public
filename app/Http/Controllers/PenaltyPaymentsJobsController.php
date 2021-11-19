<?php

namespace App\Http\Controllers;


use App\IncomPays;
use App\Jobs\ProcessPaymentJob;
use Illuminate\Support\Facades\DB;


// обработка платежей с целью фиксации оплаты пеней
//  возможна обработка любой даты
// processed2 в Incompays для контроля выполнения операции погашеней пеней в системе учета "с коррекцими" - фактический учет
// processed3 в Incompays для контроля выполнения операции погашеней пеней в системе учета "без коррекций" - теоретический учет


class PenaltyPaymentsJobsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    private $rowsPerMinute = 1000;

    public function __construct()
    {


    }

    public function setJob()
    {


        /*
                 * необр. платежи (processed2=0) с непустым lead_id
                 * для которых существует (по lead_id) хотябы один неполностью оплаченный или неоплаченный штраф
                 *
                select * from `IncomPays` where exists (select * from `penalty_daily` where `IncomPays`.`lead_id` = `penalty_daily`.`lead_id`
                and `paid` < `penalty_sum`)
                and (`processed2` = ?
                and `lead_id` > ?)
                limit 100
            */

        // DB::connection()->enableQueryLog();
        $ips = IncomPays::whereHas('Penalty', function ($query) {     // //has('PenaltyDaily')
           // $query->whereColumn('paid', '<', 'penalty_sum');
        })
            ->where('lead_id', '>', 0)
            ->where(function ($q) { // необр.
                return $q->where('processed2', 0)
                    ->orWhere('processed3', 0);
            })
            ->limit($this->rowsPerMinute)
            ->get();
        // dd($ips->first(),   DB::getQueryLog() );


        foreach ($ips as $ip) {
            DB::transaction(function () use ($ip) {
                if (!$ip->processed2) $ip->processed2 = 2; // отправлено в очередь
                if (!$ip->processed3) $ip->processed3 = 2; // отправлено в очередь
                $ip->save();
                if($ip->wasChanged()) {
                    ProcessPaymentJob::dispatch($ip)->onQueue('low');
                }

            });

        }


    }


}
