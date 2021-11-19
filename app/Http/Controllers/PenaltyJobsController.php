<?php

namespace App\Http\Controllers;


use App\Instalment;
use App\Penalty;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\PenaltyJobs;
use App\IncomPays;
use Exception;
use App\Exceptions\CriticalException;
use App\Jobs\ProcessContractPenaltyCheck;

// начисление пени


class PenaltyJobsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $yesterday, $today, $day_before, $job, $lead_id;
    public $jobs_limit_per_start = 100;


    public function __construct()
    {
        // DB::connection()->enableQueryLog(); // TODO delete

        $this->today = now()->toDateString();
        $this->yesterday = Carbon::yesterday()->toDateString();
        $this->day_before = Carbon::now()->subDays(2)->toDateString();

    }


    public function setJob($lead_id = false, $date = false)
    {

        if ($lead_id || $date) {
            EXIT('Ручная обработка и обработка задним числом запрещена');
        }

        // DB::connection()->enableQueryLog();
        $c100 = $this->getContracts()
            // ->inRandomOrder()
            ->limit($this->jobs_limit_per_start)
            ->get();

        //  dd(   DB::getQueryLog() );

        try {
            $this->log(' START.');

            foreach ($c100 as $c) {

                $this->log('найдено задание, лид_ид: ' . $c->lead_id);


                $job = new PenaltyJobs;
                $job->date = $this->today; //$this->yesterday;
                $job->lead_id = $c->lead_id;


                DB::transaction(function () use ($job) {
                    //dd($job->lead_id, $job->date);
                    //закинуть в очередь
                    $this->log('поставновка в очередь: ', [$job->lead_id, $job->date]);
                    ProcessContractPenaltyCheck::dispatch($job->lead_id, $job->date)->onQueue('mid'); // сначала обработаем платежи (high), потом начислим пени с учетом внесенных платежей
                    //  и записать
                    $job->done = PenaltyJobs::DONE_QUEUE; // ушло в очередь
                    $job->save();


                });


            }


        } catch (Exception $e) {
            $this->log(' фатал критикал: ' . $e->getMessage());
            throw new CriticalException('ошибка при обработке заданий, выполнение прервано:' . $e->getMessage());

        }


        $this->log('закончили задачу');
        EXIT;


    }


    function ppc($lead_id, $date)
    {


        // Penalty::ProcessContract( $lead_id,  $date );

        dd(

            IncomPays:: getTotalPaid_on_due(15232921, '2019-12-25')
        );
    }


    /**
     *
     */
    public function getJob($lead_id = false)
    {

        // берем один договор
        #TODO!! действующий договор
        // для которого нет записи в jobs за вчерашний день


        // DB::connection()->enableQueryLog();

        if ($lead_id) return Instalment::where('lead_id', $lead_id)->first();

        return $this->getContracts()
            ->inRandomOrder()
            ->first();

        // dd(  DB::getQueryLog() );

    }


    public function getContracts()
    {
        return Instalment::
        where('schedule_created', 1)
            ->where('penalty_type', '!=', 0)
            ->where(function ($q) {
                $q->whereNotExists(function ($query) {

                    $query->select(DB::raw(1))
                        ->from('__penalty_jobs')
                        ->where('__penalty_jobs.date', $this->today) // $this->yesterday
                        ->whereRaw('__penalty_jobs.lead_id = instalments.lead_id');
                });


                // остальное на ответственность очередей - будет 5 попыток с паузой в 2 часа м/у попытками
                /*
                ->orwhereExists(function ($query) {
                // задания которые сорвались (done=6), через пару часов можно попробовать выполнить еще раз
                $query->select(DB::raw(1))
                    ->from('__penalty_jobs')
                    ->where('__penalty_jobs.date', $this->yesterday )
                    ->whereRaw('__penalty_jobs.lead_id = instalments.lead_id')
                    ->where('__penalty_jobs.done' , 6)
                    ->where('__penalty_jobs.updated_at', '<',  Carbon::now()->subHours(2)->toDateTimeString());
            }); */


            });

    }

}
