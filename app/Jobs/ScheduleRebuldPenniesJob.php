<?php

namespace App\Jobs;

use App\Instalment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Class ScheduleRebuldPenniesJob
 * @package App\Jobs
 *
 * ПЕРЕКИДКА КОПЕЕК В ПОСЛЕДНИЙ ПЛАТЕЖ
 *
 * DEPR в связи с https://trello.com/c/4hEwVHwk/180-%D0%B0%D0%B1%D0%BD-%D0%BE%D0%BF-%D0%BF%D1%80%D0%B8-%D0%B3%D0%B5%D0%BD%D0%B5%D1%80%D0%B0%D1%86%D0%B8%D0%B8-%D0%B3%D1%80%D0%B0%D1%84%D0%B8%D0%BA%D0%B0-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B5%D0%B9-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-api-%D0%B8-%D0%B8%D0%B7-%D0%B8%D0%BD%D1%82%D0%B5%D1%80%D1%84%D0%B5%D0%B9%D1%81%D0%B0-%D1%83%D0%BC%D0%B5%D0%BD%D1%8C%D1%88%D0%B0%D1%82%D1%8C-%D0%B2%D1%81%D0%B5-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B8-%D0%BA%D1%80%D0%BE%D0%BC%D0%B5-%D0%BF%D0%BE%D1%81%D0%BB%D0%B5%D0%B4%D0%BD%D0%B5%D0%B3%D0%BE-%D0%B4%D0%BE-%D1%80%D1%83%D0%B1%D0%BB%D0%B5%D0%B9-%D0%B2-%D0%BC%D0%B5%D0%BD%D1%8C%D1%88%D1%83%D1%8E-%D1%81%D1%82%D0%BE%D1%80%D0%BE
 */
class ScheduleRebuldPenniesJob_DEPR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Instalment
     */
    public $contract;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Instalment $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        $penny_total = 0;
        $this->contract->Schedule->sortby('payment_date')
            ->map(function ($sch) use (&$penny_total) {

                $whole = floor($sch->sum_total);
                $fraction = $sch->sum_total - $whole;

                if($fraction > 0) {
                    $penny_total += $fraction;
                    $sch->sum_total = $whole;
                    $sch->save();
                }

            });


        if($penny_total > 0) {
            $lastone = $this->contract->Schedule->last();
            $lastone->sum_total += $penny_total;
            $lastone->save();  // saveQuietly только в 8м
        }
    }
}
