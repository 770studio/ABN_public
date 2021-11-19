<?php

namespace App\Events;

use App\Instalment;
use App\Schedule;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class ScheduleModifiedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contract;

    /**
     * Create a new event instance.
     *
     * @param Schedule | Instalment $item
     */
    public function __construct($item, $originatedFrom=false)
    {
        $this->contract =  $item instanceof Schedule
            ? $item->contract
            : $item;

        $this->originatedFrom = $originatedFrom;
    }


}
