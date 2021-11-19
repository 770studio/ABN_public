<?php

namespace App\Listeners;

use App\Events\ScheduleModifiedEvent;
use App\Helpers\CacheHelper;
use App\Jobs\ScheduleRebuldPenniesJob;
use App\Services\Penalty\PenaltyRebuildService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScheduleModifiedListener // implements ShouldQueue
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
    public function handle(ScheduleModifiedEvent $event, $originatedFrom='model event update/delete')
    {

        Log::channel('events_daily')->info("ScheduleModifiedEvent before cache check ", ['origin'=> $originatedFrom, 'event'=>$event ]   );

        $cacheKey = CacheHelper::getContractCacheKey($event->contract,  'ScheduleModifiedEvent' ) ;
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, 'ScheduleModifiedEvent', 24*60*60 );
        Log::channel('events_daily')->info("ScheduleModifiedEvent after cache check: ", ['origin'=> $originatedFrom, 'event'=>$event ]   );

        PenaltyRebuildService::deferContractRebuild($event->contract);
       // ScheduleRebuldPenniesJob::dispatch($event->contract);

    }
    /**
     * Determine whether the listener should be queued.
     *
     * @param \App\Events\ScheduleModifiedEvent $event
     * @return bool
     */
    /*    public function shouldQueue(ScheduleModifiedEvent $event)
        {


        }*/
}
