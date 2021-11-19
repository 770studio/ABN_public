<?php


namespace App\Services\Penalty;

use App\Helpers\PenaltyRebuildHelper;
use App\Instalment;
use App\Jobs\PenaltyContractRebuildJob;
use App\Penalty;
use App\PenaltyNoCorrection;
use App\RefinRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;


/**
 * Class PenaltyRebuildService
 * @package App\Services\Penalty
 *
 *   1. Полный перерасчет всех договоров
 *      - лок таблиц, транкейт таблиц, аннулирование платежей в уплату пеней
 *      -
 *         (new PenaltyRebuildService())
 *           ->global()
 *           ->RebuildContracts(Collection $contracts) ;
 *   2. Перерасчет одного договора
 *         (new PenaltyRebuildService())
 *            ->RebuildContracts(Collection $contracts) ;
 *
 */
class PenaltyRebuildService
{
    use InterCharge;

    private const PENALTY_COLUMNS = 'lead_id,overdue_days,overdue_sum,overdue_date,date_payment,penalty_sum,status,created_at,updated_at,comments,penalty_date,update_reason,updated_by,postponed,employee_id,refin_id,paid,paid_at,schedule_id';

    private $log_channel = 'penalty_rebuild';
    /**
     * @var bool
     */
    private $global;

    public static function deferContractRebuild(Instalment $contract): string
    {
        $cacheKey = PenaltyRebuildHelper::getContractCacheKey($contract); //'PenaltyContractRebuildJob_queued-' . $event->contract->id;
        //dump($cacheKey, Cache::get($cacheKey));
        // если есть лок , не ставим выполнениие в очередь
        // одного раза в день достаточно
        if (Cache::has($cacheKey)) {
            return $cacheKey;
        }

        $exec_time = PenaltyRebuildHelper::allocateRebuildTime();
        Cache::put($cacheKey, (string)$exec_time, $exec_time);
        PenaltyContractRebuildJob::dispatch($contract)->delay($exec_time);

        return $exec_time;

    }

    public function setGlobal(): self
    {
        $this->global = true;
        // при полном перерасчете подготовим таблицы


        Log::channel($this->log_channel)->debug('Lock acquired');


        DB::table('penalty_corrections')->truncate();
        DB::table('penalty')->truncate();
        //DB::table('penalty_daily')->truncate();
        DB::table('penalty_no_correction')->truncate();
        //DB::table('penalty_corrections')->truncate(); forein index
        DB::table('IncomPays')->update(['processed2' => 0, 'processed3' => 0]);
        DB::table('__penalty_jobs')->truncate();

        return $this;
    }

    /**
     * @param Collection | LazyCollection $contracts
     * @return mixed
     *
     * @throws Exception
     */
    public function RebuildContracts($contracts)
    {

        foreach ($contracts as $contract) {
            Log::channel($this->log_channel)->debug('lead_id:' . $contract->lead_id);
            // $console->line('lead_id:' . $contract->lead_id);
            try {
                $this->RebuildContract($contract);
            } catch (Exception $e) {
                Log::channel($this->log_channel)->error($e->getMessage(), ['lead_id' => $contract->lead_id]);
            }
        }


        $this->afterAll();

    }

    private function RebuildContract(Instalment $contract)
    {
        dump($contract->lead_id);

        $this->setContract($contract);
        $this->setIncomPays($contract->lead_id);
        $this->setPenalty();

        Log::channel('penalty_rebuild_lead_id')->debug($contract->lead_id);
        DB::transaction(function () use ($contract) {

            #TODO блокировать на уровне контракта , а не всю таблицу
            DB::unprepared('LOCK TABLES jobs WRITE,
                penalty WRITE, __penalty_jobs WRITE,
                penalty_corrections WRITE,
                penalty_no_correction WRITE,
                instalments WRITE,
                IncomPays WRITE,
                payment_shedule WRITE,
                refin_rate WRITE

            ');


            Log::channel($this->log_channel)->debug('начали транзакцию на уровне контракта');

            $this->flushLeadId($contract->lead_id);

            $date = Carbon::parse(Penalty::MIN_DATE);
            // ведем дату до конца (включая текущий день)
            $tomorrow = now()->addDay();

            while (
            $date->lte($tomorrow)
            ) {
                $date_str = $date->toDateString();
                Log::channel($this->log_channel)->debug('--------------========================================--------------');
                Log::channel($this->log_channel)->debug('ДАТА:' . $date_str);

                /**
                 * кол-во дней до изменения ежедневной суммы начисления,
                 * сумма начисления изменяется:
                 *  1. при изменении ставки (если тип начисления зависит от ставки)
                 *  2. при поступлении оплаты (т.к изменяется облагаемая база, позиции графика закрываются платежами, начисления прекращаются)
                 *  3. при изменении суммы долга (наступление даты по графику , появление нового начисления)
                 *
                 *  что раньше ?
                 */
                // очередной платеж (приход)
                $next_income = $this->incompays->where('incomDate', '>', $date_str)->min('incomDate');
                // изменение ставки
                $next_rate = Carbon::parse(RefinRate::getNearestRateChangeDate($date_str))->subDay();
                /*
                 * дата ставки (дата конца периода начислений по изменению ставки) определяется следующим образом:
                 * (например сегодня 1.09)
                 * выясняем дату следующего изменения ставки (например 10.09), берем ставку на 10.09 минус день, т.е
                 * 9.09
                 * т.е по сути берется ставка за день до следующего изменения, т.к. до этого дня (дня следующего
                 * изменения) можем применять данную ставку, причем не важно, попадает ли дата в период начисления
                 * (напр. 1.09 - 5.09), а на следующий день (в следующем периоде) нужно применять уже новую ставку.
                 *
                 * в штатном случае дата начала следующего периода равна дате конца текущего периода
                 * в следующей итерации идет выборка > даты (напр. дата платежа 'incomDate', '>', $date_str , или
                 * дата по графику 'payment_date', '>', $date_str ).
                 * В ситуации с next_rate (дата конца периода = дата изменения ставки минус день) получается, что
                 * следующий период начинается с даты (напр. 4.09), но 5.09 имеем дату изменения ставки, поэтому
                 * здесь будет либо бесконечный луп, либо (на практике) дата начала = дате конца периода, что
                 * приведет к концу работы (выход по getDateDiff), в то время, как расчет не закончен.
                 *
                */

                if ($next_rate->toDateString() == $date_str) {
                    $next_rate = Carbon::parse(RefinRate::getNearestRateChangeDate(
                        $next_rate->addDay()
                    ))->subDay();
                }
                if ($next_rate->toDateString() <= $date_str) {
                    $next_rate = null;
                }

                // очередной платеж по графику
                $next_sched = $contract->Schedule->where('payment_date', '>', $date_str)
                    ->min('payment_date');

                Log::channel($this->log_channel)->debug('ожидается изменение суммы начисления:',
                    ['IncomPays' => $next_income, 'RefinRate' => $next_rate, 'Schedule' => $next_sched]
                );

                $end_date = new Carbon(
                    collect([$next_income, $next_rate, $next_sched])
                        ->reject(function ($_date_str) use ($contract, $tomorrow) {
                            return empty($_date_str)   // не интересуют пустые
                                || $_date_str < $contract->Schedule->min('payment_date') // не интересуют даты до 1го платежа по графику
                                || $_date_str >= $tomorrow->toDateString(); //не интересуют даты в будущем
                        })
                        ->min() ?? now()
                ); // если  $end_date null, $end_date = now()

                Log::channel($this->log_channel)->debug('дата2:'. $end_date->toDateString());

                $this->setDate($date_str, $end_date);

                if (!$this->getDateDiff()) break; // долбимся в now()
                $done = $this->processDateInterval();
                $date = $end_date;

            }

            // прошли по всем днями и если есть долг, то он находится сейчас в penalty
            $this->storePenalty();
            Log::channel($this->log_channel)->debug('заканчиваем транзакцию на уровне контракта');
            $this->contract->PenaltyHasJustBeenSuccessfullyRebuilt();

        });

    }

    private function flushLeadId($lead_id)
    {
        DB::table('penalty')->where('lead_id', $lead_id)->delete();
        //DB::table('penalty_daily')->where('lead_id', $lead_id)->delete();
        DB::table('penalty_no_correction')->where('lead_id', $lead_id)->delete();
        // DB::table('penalty_corrections')->where('lead_id', $contract->lead_id )->delete(); // через foreign index
        DB::table('IncomPays')->where('lead_id', $lead_id)->update(['processed2' => 0, 'processed3' => 0]);
        DB::table('__penalty_jobs')->where('lead_id', $lead_id)->delete();
    }

    /**
     *  в случае rebuild может быть только инсерт
     *  т.к мы готовим коллекшн (не вносим в бд до текущего момента)
     */
    private function storePenalty()
    {
        if ($this->test) {
            dump($this->penalty->toArray());
            return;
        }

        $penalty_usecase = $this->no_correction
            ? PenaltyNoCorrection::class
            : Penalty::class;

        $chunks = $this->penalty->chunk(500);

        foreach ($chunks as $chunk) {
            $penalty_usecase::insert($chunk->toArray());
        }

    }

    private function afterAll()
    {
        if (!$this->global) {
            DB::statement("insert into penalty_no_correction (" . self::PENALTY_COLUMNS . ")
                    select " . self::PENALTY_COLUMNS . " from penalty where lead_id = " . $this->contract->lead_id);
            Log::channel($this->log_channel)->info($this->contract->lead_id . '  REBUILD FINISHED');
            return;
        }
        // after global rebuild:
        Log::channel($this->log_channel)->info('GLOBAL REBUILD FINISHED');
        // копировать penalty в penalty_nocorrection
        DB::table('penalty_no_correction')->truncate();
        DB::statement("insert into penalty_no_correction select * from penalty ");

    }

}
