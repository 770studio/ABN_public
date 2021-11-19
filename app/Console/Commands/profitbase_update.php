<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ProfitBase\ProfitBase;
use Illuminate\Support\Facades\Log;


class profitbase_update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profitbase:updateProjectsAndHouses';

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


        $api = new ProfitBase();

        Log::channel('pbase')->info('profitbase:updateProjectsAndHouses запущен');

        $api->updateProjects();
        $api->updateHouses();
        $api -> updateHouseFloors() ;  // обновление   каждого дома через паузу 1 сек

        Log::channel('pbase')->info('profitbase:updateProjectsAndHouses закончил работу');


    }
}
