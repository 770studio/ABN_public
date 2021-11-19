<?php

namespace App\Events;

use App\RefinRate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class RefinRateModifiedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contract;

    /**
     * Create a new event instance.
     *
     * @param RefinRate $item
     */
    public function __construct($item)
    {
         $this->refinrate = $item;
    }


}
