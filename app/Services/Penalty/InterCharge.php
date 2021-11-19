<?php


namespace App\Services\Penalty;


use App\IncomPays;
use App\Instalment;
use App\Penalty;
use App\PenaltyJobs;
use App\PenaltyNoCorrection;
use App\RefinRate;
use App\Schedule;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait InterCharge
{
    /**
     * @var bool
     */
    public $test;
    /**
     * @var Collection
     */
    private $incompays;
    /**
     * @var Instalment
     */
    private $contract;
    /**
     * @var string
     */
    private $date;
    /**
     * @var float
     */
    private $overdue_sum;
    /**
     * @var Collection
     */
    private $penalty;
    /**
     * @var string
     */
    private $day_before;
    /**
     * @var string
     */
    private $end_date;
    /**
     * @var bool
     */
    private $no_correction;

    private function setContract(Instalment $contract)
    {
        $this->contract = $contract;
    }

    private function setIncomPays(int $lead_id)
    {
        $this->incompays = IncomPays::collectByLeadId($lead_id)
            ->where('sum', '>', 0);
    }

    private function setPenalty()
    {
        $this->penalty = collect([]);
    }

    /**
     * @param $date
     * @param $end_date
     *
     * если есть $end_date, то расчет за период (кол-во дней - date diff)
     * иначе за один день
     *
     */
    private function setDate($date, $end_date = false)
    {
        $this->date = $date;
        $this->day_before = Carbon::parse($this->date)->subDays(1)->toDateString();
        $this->end_date = $end_date;

    }

    /**
     * @return int
     * @throws Exception
     *
     * SUM_PAID - сумма поступивших платежей на дату $this->date
     * SCHEDULE_ONDATE_SLICE - позиции графика платежей до настоящей даты включительно
     * SUM_PAYMENT_TO_NOFEE  - размер суммы, необходимый для внесения на дату без образования долга
     *
     * распределяем имеющиющуюся оплату по активным позициям графика
     * если суммы недостатчно, образуем долг по каждой позиции графика
     */
    private function processDateInterval(): int
    {
        //Автоматитческое начисление пеней
        Log::channel($this->log_channel)->debug('::', [' лидид:' => $this->contract->lead_id, ' дата:' => $this->date, ' дата2:' => $this->end_date]);
        // внесено в оплату по договору
        // incomDate берем до даты начала периода (дата расчета), т.о на дату расчета будет начисление, даже если поступила оплата в этот день
        // единств. проблема , что это нужно сделать, только если просрочка старая (последовательная , более одного дня)
        // однако для новой просрочки будет ниже добавлено условие пропуска (амнистии) первого дня, т.о здесь болше ничего делать не нужно
        $this->SUM_PAID = $this->incompays
            ->where('incomDate', '<=', $this->date)
            ->sum('sum');
        Log::channel($this->log_channel)->debug("внесено в оплату до текущего дня:", [$this->SUM_PAID]);

        // позиции графика платежей , где дата платежа наступила на момент расчета
        // т.е те, по которым должна быть оплата на проверяемую дату
        // дата платежа меньше даты проверки
        $SCHEDULE_ONDATE_SLICE = $this->contract->Schedule->where('payment_date', '<=', $this->date);
        // сумма , которую необходимо было оплатить , чтобы не было долга
        // $SCHEDULE_ONDATE_SLICE->ScheduleTotalSum()
        $SUM_PAYMENT_TO_NOFEE = round($SCHEDULE_ONDATE_SLICE->sum('total_dept'), 2);
        Log::channel($this->log_channel)->debug("весь долг составляет:", [$SUM_PAYMENT_TO_NOFEE]);

        if (!$SUM_PAYMENT_TO_NOFEE || $SUM_PAYMENT_TO_NOFEE <= $this->SUM_PAID) {
            Log::channel($this->log_channel)->debug('текущего долга не обнаружено , выходим.');
            return PenaltyJobs::DONE_NO_FINE;
        }

        // начисляем по каждой позиции графика платежей отдельно , пока есть текущий долг
        $SUM_PAID_REMNANTS = $this->SUM_PAID;

        $SCHEDULE_ONDATE_SLICE->sortBy('payment_date')
            ->each(function($sch_item) use (&$SUM_PAID_REMNANTS) {
                // посмотрим, оплачена ли данная позиция графика платежей

                $SUM_PAID_REMNANTS -= $sch_item->total_dept;
                if ( round($SUM_PAID_REMNANTS, 2) < 0) { //не хватило чтоб закрыть данную позицию графика
                    $this->setDebtSUM(abs($SUM_PAID_REMNANTS)); // недоплачено
                    $SUM_PAID_REMNANTS = 0; // для следующего платежа по графику средства на оплату равны нулю
                    Log::channel($this->log_channel)->debug("СУММА ДОЛГА ПО ПОЗИЦИИ #{$sch_item->id}({$sch_item->n}) ГРАФИКА: {$this->getDebtSUM()}");

                    $this->doCharge($sch_item);
                }




            });

        return PenaltyJobs::DONE_FINE;
    }
    private function processOneDayInterval_DEPR(): int
    {
        //Автоматитческое начисление пеней
        Log::channel($this->log_channel)->debug('::', [' лидид:' => $this->contract->lead_id, ' дата:' => $this->date, ' дата2:' => $this->end_date]);
        // внесено в оплату по договору
        $this->SUM_PAID = $this->incompays
            ->where('incomDate', '<', $this->date)
            ->sum('sum');
        Log::channel($this->log_channel)->debug("внесено в оплату до (не включая) текущего дня:", [$this->SUM_PAID]);

        // позиции графика платежей , где дата платежа наступила на момент расчета
        // т.е те, по которым должна быть оплата на проверяемую дату
        // дата платежа меньше даты проверки
        $SCHEDULE_ONDATE_SLICE = $this->contract->Schedule->where('payment_date', '<', $this->date);
        // сумма , которую необходимо было оплатить , чтобы не было долга
        // $SCHEDULE_ONDATE_SLICE->ScheduleTotalSum()
        $SUM_PAYMENT_TO_NOFEE = round($SCHEDULE_ONDATE_SLICE->sum('total_dept'), 2);
        Log::channel($this->log_channel)->debug("весь долг составляет:", [$SUM_PAYMENT_TO_NOFEE]);

        if (!$SUM_PAYMENT_TO_NOFEE || $SUM_PAYMENT_TO_NOFEE <= $this->SUM_PAID) {
            Log::channel($this->log_channel)->debug('текущего долга не обнаружено , выходим.');
            return PenaltyJobs::DONE_NO_FINE;
        }

        // начисляем по каждой позиции графика платежей отдельно , пока есть текущий долг
        $SUM_PAID_REMNANTS = $this->SUM_PAID;

        $SCHEDULE_ONDATE_SLICE->sortBy('payment_date')
            ->each(function($sch_item) use (&$SUM_PAID_REMNANTS) {

                if($sch_item->payment_date == $this->date) {
                    // первый день амнистия
                    Log::channel($this->log_channel)->debug('за первый день расчет не проводим.');

                }
                else {
                    // посмотрим, оплачена ли данная позиция графика платежей
                    $SUM_PAID_REMNANTS -= $sch_item->total_dept;

                    if ( round($SUM_PAID_REMNANTS, 2) < 0) { //не хватило чтоб закрыть данную позицию графика
                        $this->setDebtSUM(abs($SUM_PAID_REMNANTS)); // недоплачено
                        $SUM_PAID_REMNANTS = 0; // для следующего платежа по графику средства на оплату равны нулю
                        Log::channel($this->log_channel)->debug("СУММА ДОЛГА ПО ПОЗИЦИИ #{$sch_item->id}({$sch_item->n}) ГРАФИКА: {$this->getDebtSUM()}");

                        $this->doCharge($sch_item);
                    }

                }


            });

        return PenaltyJobs::DONE_FINE;
    }
    private function setDebtSUM($sum)
    {
        $this->overdue_sum = round($sum, 2);
    }

    private function getDebtSUM(): float
    {
        return $this->overdue_sum;
    }

    /**
     * @param Schedule $sh_item
     * @return void
     * @throws Exception
     *  Начисление по позиции графика
     */
    private function doCharge(Schedule $sh_item)
    {
        // расчет штрафа
        // ставка + 1 день
        // $irate = RefinRate::getRate($this->date, false, false);
        $irate = RefinRate::getRate( (new CarbonImmutable($this->date))->addDay()->toDateString()
            , false, false);
        Log::channel($this->log_channel)->debug("ставка получена: {$irate->rate}");
        $penalty_sum = round(Penalty::CalcFee($this->contract, $this->overdue_sum, $irate->rate) * $this->getDateDiff(), 2);
        Log::channel($this->log_channel)->debug(' штраф: ', ['сумма' => $penalty_sum, 'кол-во дней' => $this->getDateDiff(),
            'тип расчета пени' => $this->contract->penalty_type, 'фикс. величина пени для типа 1' => $this->contract->penalty_value]);

        if ($penalty = $this->isOngoingPenalty($sh_item)) {
            // Если такая есть, то текущую pd надо добавить к прошлой
            Log::channel($this->log_channel)->debug('  есть долг за предыдущий день, не погашен полностью  и не отменен, overdue_sum не изменилась, добавляем к этому долгу');
            $this->updatePenalty($penalty, $irate, $penalty_sum);

        } else {
            // это новая задолжность
            Log::channel($this->log_channel)->debug('это новая задолжность, записываем ');
            $this->addPenalty($sh_item, $irate, $penalty_sum);

        }

        Log::channel($this->log_channel)->debug('штраф начислен');

    }

    private function getDateDiff()
    {
        if (!$this->end_date) return 1;

        return (new Carbon($this->date))->diff($this->end_date)->days;
    }

    /**
     * @param Schedule $sh_item
     * @return Penalty | false
     * @throws Exception
     */
    private function isOngoingPenalty(Schedule $sh_item)
    {
        $cacheKey = "_ongoing_SUMPAID_Cache_" . $sh_item->id . '_' . $this->SUM_PAID;
        // на 3 мин в кеш, за это время одну позицию графика та уж точно пройдем
        // т.о будет сбрасываться (очищать память) по времени

        $cached_penalty_index = Cache::remember($cacheKey, 180, function () use ($sh_item) {

            $SUM_DEBT = $this->getDebtSUM();
            if (!$SUM_DEBT || $SUM_DEBT < 0) return false;
            //throw new Exception("это не долг, сначала проверьте, что долг есть." . print_r($sh_item->toArray(), true) );

            // для выполнения услвия общего (объединенного) наичисления должно выполняться следующее:
            $penalty_index = $this->penalty->search(function ($penalty) use ($sh_item) {

                return $penalty->lead_id == $this->contract->lead_id
                    && $penalty->penalty_date == $this->day_before
                    && $penalty->schedule_id == $sh_item->id
                    && $penalty->overdue_sum == $this->getDebtSUM();
            });

            Log::channel($this->log_channel)->debug('isOngoingPenalty :',
                [
                    'penalty_date' => $this->day_before, 'schedule_id' => $sh_item->id,
                    'overdue_sum' => $this->getDebtSUM(), 'result' => (bool)$penalty_index,
                ]
            );

            // если пеня идет подряд, то она так и будет идти пока не придет платеж в Incompays
            // лишний раз проверять не надо!

            return $penalty_index;
        });


        if ($cached_penalty_index === false) {
            Cache::forget($cacheKey); // иначе null попадет в кэш же..
            return false;
        }

        return $this->penalty->get($cached_penalty_index);
    }

    private function updatePenalty(Penalty $penalty, RefinRate $irate, float $add_penalty_sum)
    {
        $penalty->overdue_days = (int)$penalty->overdue_days + $this->getDateDiff();
        $penalty->penalty_sum = (float)$penalty->penalty_sum + $add_penalty_sum; // добавляем пени за еще один день
        $penalty->penalty_date = $this->getPenaltyDate();  // дата  последнего начисления
        $penalty->refin_id = $irate->id; // последняя исп. ставка
    }

    private function getPenaltyDate()
    {
        return $this->end_date
            ? $this->end_date->clone()->subDay()->toDateString() //  последний день это уже следующий промежуток
            : $this->date;
    }

    private function addPenalty(Schedule $sh_item, RefinRate $irate, float $penalty_sum)
    {
        $this->penalty->push(
            ($this->no_correction ? new PenaltyNoCorrection() : new Penalty())
                ->fill([
                    'lead_id' => $this->contract->lead_id,
                    'overdue_days' => $this->getDateDiff(),
                    'overdue_sum' => $this->overdue_sum,
                    'overdue_date' => $this->date, // Дата наступления просрочки
                    'penalty_sum' => $penalty_sum,
                    'penalty_date' => $this->getPenaltyDate(),
                    'refin_id' => $irate->id,
                    'schedule_id' => $sh_item->id,

                ])
        );
    }






}
