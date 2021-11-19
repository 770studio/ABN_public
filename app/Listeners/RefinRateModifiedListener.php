<?php

namespace App\Listeners;

use App\Events\RefinRateModifiedEvent;
use App\Events\ScheduleModifiedEvent;
use App\Instalment;
use App\Services\Penalty\PenaltyRebuildService;
use Illuminate\Support\Facades\Log;

class RefinRateModifiedListener // implements ShouldQueue
{


    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {


    }

    /**
     * Handle the event.
     *
     * @param ScheduleModifiedEvent $event
     * @return void
     */
    public function handle(RefinRateModifiedEvent $event)
    {
        Log::channel('events_daily')->info("RefinRateModifiedEvent: ", ['event' => $event]);
        if($event->refinrate->start_date < today()) {
            // пересчитать пени по всем контрактам, у которых тип начисления зависит от ставки
            $event->refinrate->contracts()->each(function($contract){
                PenaltyRebuildService::deferContractRebuild($contract);
            });

        }
    }


}
