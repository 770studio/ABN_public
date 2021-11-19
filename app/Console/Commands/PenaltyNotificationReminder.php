<?php

namespace App\Console\Commands;

use App\Exceptions\CriticalException;
use App\Http\Controllers\PenaltyNotificationController;
use Illuminate\Console\Command;

class PenaltyNotificationReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pn:run';

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
        try {
            $c = new PenaltyNotificationController;
            $c->run();

        } catch (\Exception $e) {
            // echo "Exception: " . $e->getMessage();
            throw new CriticalException('при формировании очереди на отправку оповещения о задолжности произошел сбой. оповещения могут быть не отпрвалены: ' . $e->getMessage() );


        }

    }
}
