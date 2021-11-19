<?php

namespace App\Jobs;

use App\IncomPays;
use App\Penalty;
use App\Services\Penalty\PenaltyAccountingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 5;
    // повторная попытка через два часа
    public $retryAfter = 7200;

    /**
     * @var PenaltyAccountingService
     */
    public $penaltyAccountingService;
    /**
     * @var IncomPays
     */
    public $incomp;

    /**
     * Create a new job instance.
     *
     * @param IncomPays $incomp
     * @param PenaltyAccountingService $penaltyAccountingService
     */
    public function __construct(IncomPays $incomp )
    {
        $this->incomp = $incomp;
    }


    public function handle(PenaltyAccountingService $penaltyAccountingService)
    {
        $this->penaltyAccountingService = $penaltyAccountingService;

        $this->incomp->isPenaltyPayment()
            ? $this->penalty_payment()
            : $this->none_penalty_payment();


    }

    private function penalty_payment()
    {
        if ($this->incomp->processed2 == 2)
            Penalty::ProcessPayment($this->incomp);
        if ($this->incomp->processed3 == 2)
            Penalty::ProcessPayment($this->incomp, true);
    }


    private function none_penalty_payment()
    {
        if ($this->incomp->processed2 == 2) {
            $this->penaltyAccountingService
                ->RegisterNewIncomingPayment($this->incomp);
        }

        // по ноукоррекшн платежи не регистрируются т.к это нужно только для отчетов
        // а отчеты делаются только по Penalty

    }


}
