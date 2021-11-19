<?php


namespace App\Services\Penalty;

use App\Instalment;
use App\Penalty;
use App\PenaltyJobs;
use App\PenaltyNoCorrection;
use Exception;
use Illuminate\Support\Facades\DB;


/**
 * Class PenaltyDailyChargeService
 * @package App\Services\Penalty
 *
 *   Ежедневные начисления - штатный режим
 *   запуск из очереди , обработка одношо контракта на дату
 *
 *            (new PenaltyDailyChargeService(26524188, '2021-05-15'))
 *              // ->test()
 *               ->dailyJob();
 */
class PenaltyDailyChargeService
{
    use InterCharge;


    /**
     * @var string
     */
    private $log_channel = 'penalty_daily';
    /**
     * @var PenaltyJobs
     */
    private $job;

    /**
     * PenaltyDailyChargeService constructor.
     * @param $lead_id
     * @param $job_date
     * @throws Exception
     */
    public function __construct($lead_id, $job_date)
    {
        $this->setContract(
            Instalment::find($lead_id)
        );
        $this->setIncomPays($lead_id);
        $this->setPenalty();
        $this->setDate($job_date);
    }

    private function setPenalty($nocorrection = false)
    {
        $this->no_correction = $nocorrection;
        $penalty_usecase = $nocorrection
            ? PenaltyNoCorrection::class
            : Penalty::class;

        $this->penalty = $penalty_usecase::where('lead_id', $this->contract->lead_id)
            ->where('penalty_date', $this->day_before)
            //->where('schedule_id', $sh_item->id)
            //->where('overdue_sum', $this->getDebtSUM())
            ->when(!$this->no_correction, function ($q) {
                return $q->where('status', '!=', 3); // кроме ручной отмены
            })
            ->get();
    }

    /**
     *
     * сумма долга изменяется:
     *  1. при поступлении оплаты (т.к изменяется облогаемая база, позиции графика закрываются платежами, начисления прекращаются)
     *  2. при наступление новой даты по графику (растет общий долг) , появление нового начисления
     *
     *   запись в Penalty для одной и той же позиции графика возможна
     *   только в одном случае, когда поступила оплата, которй не хватило, чтобы закрыть позицию графика целиком
     *   иначе это будет новая позиция графика
     *   следовательно не может быть двух записей пеналти с одинаковой суммой и позицией графика (schedule_id + overdue_sum)
     *
     *   полный перерасчет захватывает текущую дату (now())
     *   и при входе в штатный режим ежедненвных начислений
     *   возможна ситуация, когда начисление за текущий день уже сделано в рамках перерасчета
     *   для избежания данного кейса установим уник. индекс на пеналти по (schedule_id + overdue_sum)
     *
     * @throws Exception
     */
    public function dailyJob()
    {
        if($this->contract->PenaltyHasBeenRebuiltToday()) {
            dump('Сегодня был перерасчет по контракту, никаких начислений сегодня больше не требуется,
                        если бы здесь не было прерывания, то далее был бы вылет с эксепшном типа:
                        Integrity constraint violation: 1062 Duplicate entry
                        или Запись PenaltyJobs на 2021-05-28 отсутствует');
            return;
        }

        if(!$this->test && $this->date != now()->toDateString()) {
            throw new \LogicException('Ежедневные начисления ведутся только день в день');
        }

        $this->setJob();

        DB::transaction(function () {
            //PenaltyNoCorrection
            $this->setPenalty(true);
            $done = $this->processOneDayInterval();
            dump('PenaltyNoCorrection , штраф:', $done == PenaltyJobs::DONE_FINE ? 'да' : 'нет');
            if ($done == PenaltyJobs::DONE_FINE) {
                $this->storePenalty();
            }

            //Penalty
            $this->setPenalty(false);
            $done = $this->processOneDayInterval();
            dump('Penalty , штраф:', $done == PenaltyJobs::DONE_FINE ? 'да' : 'нет');
            if ($done == PenaltyJobs::DONE_FINE) {

                $this->storePenalty();

                if (!$this->test) {
                    $this->job->done = $done;
                    $this->job->save();
                }

            }


        });
    }

    private function setJob()
    {
        if ($this->test) return;
        // штатный запуск из очереди ProcessContractPenaltyCheck,
        // проверка , чтоб не провести двойное начисление не дай б-г
        $this->job = PenaltyJobs::where('lead_id', $this->contract->lead_id)->where('date', $this->date)->first();
        // в случае штатного запуска должна быть запись в PenaltyJobs (внесенная при постановке в очередь на ежедневную проверку начислений)
        # TODO оно должно приходить из очереди
        if (!$this->job) {
            throw new Exception('Обработка (начисление пеней) по  договору  лид_ид: ' . $this->contract->lead_id
                . '. Запись PenaltyJobs на ' . $this->date . ' отсутствует, ahtung! ', 23000);
        }
    }


    /**
     *  в случае dailyJob нужен просто save()
     */
    private function storePenalty()
    {
        if ($this->test) {
            dump($this->penalty->toArray());
            return;
        }

        foreach ($this->penalty as $penalty) {
            if ($penalty->isDirty()) {
                // есть изменения
                $penalty->save();
            }
        }

    }


    public function test()
    {
        $this->test = true;
        return $this;
    }

}
