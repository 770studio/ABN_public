<?php

namespace App\Console\Commands;

use App\Events\ScheduleModified;
use App\IncomPays;
use App\Instalment;
use App\Jobs\PenaltyContractRebuildJob;
use App\Jobs\PenaltyGlobalRebuildJob;
use App\Penalty;
use App\PenaltyDaily;
use App\Schedule;
use App\Services\Penalty\PenaltyAccountingService;
use App\Services\Penalty\PenaltyDailyChargeService;
use App\Services\Penalty\PenaltyRebuildService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class testing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        $contracts = Instalment::where('lead_id', 27928684)->get(); //  26524188, 1348

        (new PenaltyRebuildService())
            ->RebuildContracts( $contracts) ;


        return;




        Log::info(phpversion());
        dd(444);
        set_time_limit(600);

        $ips = IncomPays::whereHas('Penalty', function ($query) {     // //has('PenaltyDaily')
            // $query->whereColumn('paid', '<', 'penalty_sum');
        })
            ->where('lead_id', '>', 0)
            ->where(function ($q) { // необр.
                return $q->where('processed2', 0)
                    ->orWhere('processed3', 0);
            })
            ->limit(10)
            ->get();

        dd($ips);

        return 0;





        $ip = IncomPays::where('lead_id', 24486297 )->first();

        (new PenaltyAccountingService())
            //->test()
            ->RegisterNewIncomingPayment($ip);


        return 1;



/*
                 DB::listen(function($query) {

                     dump(
                         date("r") . ":" . $query->time . ":" . $query->sql . ' [' . implode(', ', $query->bindings) . ']' . PHP_EOL

                     );

                 });*/


/*
                $p = Penalty::with('Instalment:lead_id,penalty_type,penalty_value')
                    ->with('Schedule:id,date_paid')->where('lead_id',  25085373)
                    ->orderBy('overdue_date', 'ASC')
                    ->get();
        dd($p);
*/


        $ip = IncomPays::where('lead_id', 24478185 )->first();
        //$ip = IncomPays::find(76336);

        (new PenaltyAccountingService())
               //->test()
                ->RegisterNewIncomingPayment($ip);



        return 0;






        $contracts = Instalment::where('lead_id', 17425425)->get(); //  26524188, 1348

        (new PenaltyRebuildService())
            ->RebuildContracts( $contracts) ;

        return 1;





        (new PenaltyDailyChargeService( 26524188, "2021-05-27"))
            ->test()
            ->dailyJob();

        return 1;




        (new PenaltyDailyChargeService(7777, "2021-05-21"))
            //->test()
            ->dailyJob();

        return 1;



        IncomPays::whereHas('Penalty', function ($query) {     // //has('PenaltyDaily')
            // $query->whereColumn('paid', '<', 'penalty_sum');
        })
            ->where('lead_id', '>', 0)
            ->where(function ($q) { // необр.
                return $q->where('processed2', 0)
                    ->orWhere('processed3', 0);
            })
            ->limit(100)
            ->dd();


        $ips=IncomPays::where('lead_id', 27069800)->get();
        dd(

            $ips->first()->Penalty()->whereNull('date_payment')->orderBy('overdue_date')->pluck('overdue_date')
        );



        return;

        $contracts = Instalment::where('lead_id', 24944955)->get();

        (new PenaltyRebuildService())
            ->setGlobal()
            ->RebuildContracts( $contracts) ;



        return;
        ScheduleModified::dispatch(
            Instalment::where('lead_id', 26524188)->first()->Schedule->first()
        );
        // event(new ScheduleModified(Instalment::first()));
        return;
        // Log::debug('4444444444');
        PenaltyContractRebuildJob::dispatch(Instalment::first());
        return;

        DB::listen(function($query) {
            dump(
                date("r") . ":" . $query->time . ":" . $query->sql . ' [' . implode(', ', $query->bindings) . ']' . PHP_EOL

            );
        });

        (new PenaltyDailyChargeService(26524188, '2021-05-15'))
            ->test()
            ->dailyJob();
        return;



        $c = collect([]);
        $p = (new Penalty())->fill(
            [
                'lead_id' => 11111,
                'overdue_days' => 1,
                'overdue_sum' => 1234,
                'overdue_date' => 2142, // Дата наступления просрочки
                'penalty_sum' => 222,
                'penalty_date' => 11111,
                'refin_id' => 1,
                'schedule_id' => 123,

            ]
        );
        $c->push($p);

        $p->penalty_sum = $p->penalty_sum + 333;
        $c->push($p);
        // dump($c);

        dump($c->where('penalty_sum', '>', 99999)->first());
        //->save();

        dd(666);

        Penalty::TestLeadOnDate('24478185',  '2020-01-18' );


        dd(5555555555);
        DB::table('__penalty_jobs')
            ->where('done',   3 )
            ->where('date', '<',  Carbon::now()->subDays(30)->toDateString()  )
            ->delete();

        dd(

            33333
        );


        dd(5555);

        $c = new Collection();
        $pd = new PenaltyDaily;
        $pd->lead_id = 55555;
        $pd->date = now();
        $pd->overdue_sum = 2222222;
        $pd->penalty_sum = 11;
        $pd->refin_id = 1;
        $pd->save();

        $c->push($pd);

        $pd = new PenaltyDaily;
        $pd->lead_id = 6666666;
        $pd->date =  Carbon::tomorrow();
        $pd->overdue_sum = 5645645;
        $pd->penalty_sum = 34;
        $pd->refin_id = 1;
        $pd->save();

        $c->push($pd);

        dump($c->where('date', '>', now())->first());
        dd(999999999999, $c );
        //$sql = 'SELECT 4534';
        $db =  DB::getPdo(); //->prepare($sql);


        $db->beginTransaction();
        $db->exec( 'LOCK TABLES jobs WRITE, penalty WRITE' );
        # do something with tables
        sleep(10);
        $db->commit();
        $db->exec('UNLOCK TABLES');

        // DB::raw('LOCK TABLES jobs WRITE;');
        //
        dd(55555);
    }
}
