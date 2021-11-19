<?php

namespace App\Console\Commands;

use App\Instalment;
use App\Services\Penalty\PenaltyChargeFacade;
use App\Services\Penalty\PenaltyChargeService;
use App\Services\Penalty\PenaltyRebuildService;
use DebugBar\DebugBar;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PenaltyRebuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penalty:rebuild {lead_id?} {--noprompt} {--debug}';

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
     * @throws Exception
     */
    public function handle(PenaltyRebuildService $penaltyRebuildService)
    {

        $LEAD_ID = $this->argument('lead_id');

        if (!$LEAD_ID) {
            $scope = "ПО ВСЕМ СДЕЛКАМ";
            $penaltyRebuildService->setGlobal();
        } else {
            $scope = "ПО СДЕЛКЕ: {$LEAD_ID}";

        }


        if (!$this->option('noprompt') && !$this->confirm("
        Данная процедура запускает полный перерасчет начислений , будут очищены таблицы: penalty,penalty_daily,penalty_no_correction,penalty_corrections
        В таблице Incompays должны быть обнулены поля processed2, processed3 , отвечающие за погашение штрафных начислений.
        Все ручные коррекции penalty_corrections будут аннулированы.
        Во избежании коллизий нужно (на время процедуры) отключить крон обработки ежедневных начислений.
        Время процедуры при 10к договоров с графиком составляет около 15 минут.
        На время процедуры будут заблокированы на запись таблицы: penalty,penalty_daily,penalty_no_correction,penalty_corrections,instalments,IncomPays,payment_shedule,refin_rate,jobs
        По окончании процедуры нужно скопировать penalty в penalty_no_correction (при отсутствии/аннулировании коррекций эти таблицы идентичны)

        Пересчитать все пени ({$scope}) ? ")) {
            return;
        }

        if ($this->option('debug')) {
            config(['app.debug' => true]);
        }
        /*
        DB::listen(function ($query) {
          $this->line($query->sql);
          $this->line($query->bindings);
            // $query->bindings;
            // $query->time;
        });
        */


        set_time_limit(18000);
        ini_set('max_execution_time', '18000');
        $this->line("max_execution_time:" . ini_get('max_execution_time') );
//Todo penalty_date wherehas
        $contracts = Instalment::has('Schedule')
            ->with('Schedule')  // даты фильтруются через глобал скоуп sensibleDate
            //  ->whereDoesntHave('Schedule', function ($query) {
            //      return $query->where('payment_date', '<', "2017-12-18")->withoutGlobalScope('sensibleDate');
            //  })
            ->when($LEAD_ID, function ($q) use ($LEAD_ID) {
                return $q->where('lead_id', $LEAD_ID);  // 14439669 25268305 26524188
            })
            ->where('penalty_type', '!=', 0)
            ->cursor();


        $penaltyRebuildService->RebuildContracts($contracts);

        /*        (new PenaltyRebuildService())
                    ->setGlobal()
                    ->RebuildContracts($contracts) ;*/

        // $this->line('Total contracts with schedule:' . count( $insts ) );

        //  Schedule::flushEventListeners();




        Log::channel('penalty_rebuild')->info( 'REBUILD FINISHED');
        $this->line('REBUILD FINISHED' );









    }
}
