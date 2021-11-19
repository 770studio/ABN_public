<?php

namespace App\Jobs;

use App\Services\Penalty\PenaltyDailyChargeService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ProcessContractPenaltyCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $job_date ;
    protected $lead_id  ;
    public $timeout = 360; // 6 мин
    public $tries = 5;
    public $retryAfter = 7200; // повторная попытка через два часа

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(     $job_lead_id, $job_date  )
    {

        $this->job_date = $job_date ;
        $this->lead_id = $job_lead_id ;


     }

    /**
     * Execute the job.
     * @return void
     * @throws Exception
     */
    public function handle()
    {

        (new PenaltyDailyChargeService($this->lead_id, $this->job_date))
            //->test()
            ->dailyJob();
    }
}
