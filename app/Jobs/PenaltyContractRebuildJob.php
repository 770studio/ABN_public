<?php

namespace App\Jobs;

use App\Instalment;
use App\Services\Penalty\PenaltyRebuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class PenaltyContractRebuildJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     *
     */

    private $contract;

    public function __construct(Instalment $contract)
    {
        $this->contract = $contract;
    }


    /**
     * При каждом изменении графика :
     * - изменение любой позиции по графику через UI или API
     * - изменение параметров договора ???? TODO
     *   требуется пересчет начислений пени по контракту
     *
     * @return void
     *
     *  PenaltyContractRebuildJob::dispatch
     *
     *  ребилд контракта лочит бд таблицы , выполнение занимает нессколько секунд
     *  но в некоторых случаях может быть увеличено до 1 минуты и более
     *  нет смысла запускать его более раза в день
     *
     *
     * (на воркере app/Console/runqueueworker sleep 2 сек - исключен одновременный запуск )
     *
     * @throws \Exception
     */
    public function handle()
    {

        (new PenaltyRebuildService())
            ->RebuildContracts(collect($this->contract));

    }


}
