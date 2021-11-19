<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\FilterAvitoFeedCommand',
        'App\Console\Commands\SetScheduleToRegisterDepartment',
        'App\Console\Commands\FilterCianFeedCommand',
        'App\Console\Commands\testing',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('queue:restart')->daily();
        //$schedule->command('test:tset')->everyMinute();

        // $schedule->command('masspsimport:run')
        //   ->twiceDaily(3, 6);

        $schedule->command('profitbase:updateProjectsAndHouses')
            //->dailyAt('3:50');
            ->twiceDaily(2, 6);
        // ->between('15:00', '16:00'); // profitbase обновление ЖК и Домов раз в день

        $schedule->command('pn:run')->hourly();

        $schedule->call('App\Http\Controllers\PenaltyPaymentsJobsController@setJob')->everyMinute();  // постановка новых платежей в очередь на обработку

        // $schedule->call('App\Http\Controllers\PenaltyJobsController@setJob')
        //   ->everyMinute(); // начисление пени, постановка в очередь на проверку всех контрактов за вчерашний день
        // ->between('20:00', '23:00');
        $schedule->command('penalty:rebuild --noprompt')
            ->dailyAt('1:10');
        //->daily()
        //->between('1:00', '4:00');


        $schedule->call(function () {
            DB::table('__penalty_jobs')
                //->where('done',   3 )
                ->where('date', '<', Carbon::now()->subDays(30)->toDateString())
                ->delete();
        })->daily();  //  чистка таблицы __penalty_jobs, записи старее месяца


        //$schedule->command('avitofilter:start')->cron('0 */3 * * *'); //каждые 3 часа


        //avito filter каждый час
        $schedule->command('avitofilter:start')->hourly();

        //cian filter каждый час
        $schedule->command('cianfilter:start')->hourly();
        //2gis filter каждый час
        $schedule->command('2gisfilter:start')->hourly();

        //avito 2 filter каждый час
        $schedule->command('avito2filter:start')->hourly();

        //cian 2 filter каждый час
        $schedule->command('cian2filter:start')->hourly();

        //график работ сотрудникам отдела оформления сделок на след. месяц
        //каждый месяц 25 числа в 1:00
        $schedule->command('register_dep_schedule:set')->monthlyOn(25, '01:00');


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');


    }
}
