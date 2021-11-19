<?php

namespace App;

//TODO убрать
define('MIN_PENALTY_DATE', '2019-01-01'); // до 2019 по графику начисления пени не ведутся

use App\Services\Penalty\PenaltyChargeFacade;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Penalty extends Model
{

    const MIN_DATE = '1950-01-01'; // раньше этой даты работы нет

    public static $penalty_payment_target = [4, 12];
    public static $statuses = [
        0 => 'не оплачено', //  (этот статус присваивается по умолчанию при формировании записи);
        1 => 'оплачено', //   (статус присваивается, если сумма поступления, поступившая от клиента в дату >=даты начисления, равна сумме “начислено пени”);
        2 => 'оплачено частично', //   (статус присваивается, если сумма поступления, поступившая от клиента в дату >=даты начисления, меньше сумме начислено пени);
        3 => 'отменено', //    (статус проставляется вручную).


    ]; // OpsI_08030000 Невыполнения условий договора купли-продажи (штрафы, пени)
    private static $refin_rate_types = [2, 3];
    public $type_options = [
        1 => 'Величина пени в сумме',
        2 => '1/360 ставки рефинансирования',
        3 => '1/300 ставки рефинансирования',
        4 => 'Величина пени в процентах',

    ];
    protected $table = 'penalty';
    protected $guarded = ['status', 'id'];
    protected $fillable = ['lead_id', 'overdue_days', 'overdue_sum',
        'overdue_date', 'penalty_sum', 'update_reason', 'status', 'penalty_date', 'comments', 'postponed',
        'paid', 'paid_at', 'refin_id', 'schedule_id', 'date_payment'
    ];


    public static function CalcFee($type_value, $SUM_BASE, $irate)
    {

        if ($type_value instanceof Instalment) {
            $type_value = [$type_value->penalty_type, $type_value->penalty_value];

        }
        list($penalty_type, $penalty_value) = $type_value;


        switch ($penalty_type) {
            case 1:
                $SUM_PENALTY = $penalty_value;
                break;
            case 2:
                $SUM_PENALTY = ($SUM_BASE * $irate) / (360 * 100);
                break;
            case 3:
                $SUM_PENALTY = ($SUM_BASE * $irate) / (300 * 100);
                break;
            case 4:
                // произвольный процент без привязки к ставке
                $SUM_PENALTY = ($SUM_BASE * $penalty_value) / 100;
                break;
            default:
                throw new Exception('неизвестный penalty_type');
        }

        return round($SUM_PENALTY, 2);
    }

    public static function ProcessPayment(IncomPays $incomp, $nocorrection = false)
    {

        //dd( $incomp->lead_id,  $incomp->Lead  );
        if (!$incomp || !$incomp->Lead) {
            throw new Exception('ProcessPayment: нет платежа или lead_id  , incomp_id,  ' . $incomp->incomp_id);
        }
        if (!$incomp->isPenaltyPayment()) {
            throw new Exception('ProcessPayment:  это не оплата штрафа ' . $incomp->incomp_id);
        }  // в failed jobs


        // итак  есть платеж в погашенее пеней на сумму $incomp->sum

        DB::transaction(function () use ($incomp, $nocorrection) {
            self::DoProcessPayment($incomp, $nocorrection);

            // self::ProcessPaymentAbstract( $incomp->Lead->PenaltyDaily,  $paid );
            // self::ProcessPaymentAbstract( $incomp->Lead->Penalty ,  $paid );


            $incomp->update([$nocorrection ? 'processed3' : 'processed2' => 1]);
        });
    }

    public static function DoProcessPayment($incomp, $nocorrection = false)
    {

        // if($incomp == 6666)  $incomp = IncomPays::find(11467);

        $paid = $incomp->sum;

        if ($nocorrection) {
            // по penalty_no_corrections без учета коррекций
            $ps = $incomp->Lead->PenaltyNoCorrection;
        } else {
            // по penalty с учетом коррекций
            $ps = $incomp->Lead->Penalty;
        }


        // посмотрим , какие есть штрафы
        foreach ($ps as $penalty) {
            $penalty_sum = $penalty->penalty_sum; // cумма штрафа
            $already_paid = (float)$penalty->paid; // частичная оплата, если была

            switch ($penalty->status) {

                case 1: //оплачено
                case 3: //отменено
                    // оплаченные и отмененные нас не интересуют
                    continue 2;
                    break;
                case 2: // оплачено частично
                    $penalty_sum = $penalty_sum - $already_paid;

                    break;
                default: // не оплачено


            }
            // $penalty_sum сумма остатка к погашению
            $diff = $paid - $penalty_sum;

            if ($diff < 0) {
                $sum_paid = $paid;  // штраф будет частично погашен
            } else {
                $sum_paid = $penalty_sum; // штраф будет полностью погашен
            }

            $penalty->update(['paid' => (float)$sum_paid + (float)$already_paid, 'paid_at' => Carbon::now()->toDateTimeString()]);

            $oldStatus = $penalty->status;
            $newStatus = self::isPaid($penalty);

            // если статус изменился и пред. статус не "отменено" (хотя отменено сюда дойти по идее не может )
            if ($newStatus != $oldStatus && $oldStatus < 3) {
                $penalty->status = $newStatus;
                if (!$penalty->save()) {
                    throw new  Exception(' невозможно обновить статус оплаты штрафы платежа ');
                }
            }


            //dd($penalty,  $incomp);

            if ($diff > 0) {
                // еще остались деньги на другие штрафы
                $paid = $diff; // деньги = разница м/у тем , что было минус платеж
                continue;
            } else {
                break;
            }


            #  что делать если оплата пеней перекрывает сумму штрафа с остатком
            # Теоретически возможна. Штраф закрывать. А дальше возвратом лишней суммы будет бухгалтерия заниматься.
        }
    }

    public static function isPaid(Penalty $penalty)
    {
        $penalty->refresh();

        $diff = $penalty->paid - $penalty->penalty_sum;
        if ($diff >= 0) {
            return 1;
        } // оплачено
        elseif ($penalty->paid > 0) {
            return 2;
        } // частично оплачено
        return 0; // не оплачено
    }

    /**
     *  проверка одного контракта
     *  с выдачей профиля начислений
     * @param $lead_id
     * @param $job_date
     * @return bool|void
     * @throws Exception
     */
    public static function TestLeadOnDate_DEPR($lead_id, $job_date)
    {
        Log::channel('test')->debug('TestLeadOnDate:', ['лидид' => $lead_id, 'дата:' => $job_date]);

        $contract = Instalment::find($lead_id);
        if (!$contract) {
            throw new Exception('Обработка (начисление пеней) нет договора в инсталментс, лид_ид: ' . $lead_id);
        }

        // начисление на дату $job_date
        $yesterday = $job_date;

        // чтобы отследить непрерывные начисления (идущие подряд некое кол-во дней) нам нужна дата позавчерашняя
        $d = Carbon::parse($yesterday);
        $day_before = $d->subDays(1)->toDateString();

        // проверим что было позавчера
        // нам нужна pd за позавчерашний день по lead_id
        $pd_dby = PenaltyDaily::where('lead_id', $lead_id)
            ->where('date', $day_before)
            ->first();

        // сумма к оплате на дату по графику
        $SUM_PAYMENT_TO_NOFEE = round((float)Penalty::getPaymentDue($contract->Schedule, $yesterday, !$pd_dby), 2);
        //DB::connection()->enableQueryLog();
        Log::channel('test')->debug('сумма к оплате на дату по графику:', ['SUM_PAYMENT_TO_NOFEE:' => $SUM_PAYMENT_TO_NOFEE]);

        // внесено в оплату
        $SUM_PAID = round((float)IncomPays::getTotalPaid_on_due($lead_id, $yesterday), 2);
        Log::channel('test')->debug('внесено в оплату', ['SUM_PAID:' => $SUM_PAID]);

        if (!$SUM_PAYMENT_TO_NOFEE || $SUM_PAYMENT_TO_NOFEE <= $SUM_PAID) {
            Log::channel('test')->debug('текущего долга не обнаружено');

            return 'нет долга';
        }

        // расчет штрафа
        // ставка на вчера
        $irate = RefinRate::getRate($yesterday, false);

        if (!$irate) {
            throw new Exception('Обработка (начисление пеней) по  договору  лид_ид: ' . $lead_id . 'не могу получить ставку рефинансирования');
        }
        Log::channel('test')->debug('ставка получена', [$irate->rate]);

        $SUM_BASE = $SUM_PAYMENT_TO_NOFEE - $SUM_PAID;

        Log::channel('test')->debug('сумма долга', [$SUM_BASE]);


        $SUM_PENALTY = round(Penalty::CalcFee($contract->penalty_type, $contract->penalty_value, $SUM_BASE, $irate->rate), 2);

        Log::channel('test')->debug('штраф:', ['сумма' => $SUM_PENALTY, 'тип расчета пени' => $contract->penalty_type, 'фикс. величина пени для типа 1' => $contract->penalty_value]);


        return 'долг:' . $SUM_PENALTY;
    }

    public static function getPaymentDue($Schedule, $duedate, $firsttime = false)
    {


        /*        $sum_due = 0;
                foreach($Schedule->sortBy('n') as $row ) {
                    // пропуск перв. платежа
                    if($row->n == 1 ) continue;
                    // день платежа по графику меньше или равно
                    if($row->payment_date <= $duedate  ) {
                        $sum_due+= (float) $row->sum_total;

                    }

                }*/

        $end = Carbon::parse($duedate);


        $filtered = $Schedule->filter(function ($row, $key) use ($end, $duedate, $firsttime) {

            // дата платежа меньше даты проверки
            $isInCharge = $row->payment_date > MIN_PENALTY_DATE && $row->payment_date <= $duedate;


            if ($firsttime) {


                // разница между датами хотя бы 2 дня, т.к пени начисляются по прошествии дня
                //  11го мы не начисляем за 10е, т.к день еще не прошел
                // а 12го начисляем за 11, т.к прошел день с 10го по 11е

                // на  ноябрь 2019г положение изменено на 1 день

                // март 20г начислять за вчерашнйи день, если платеж не поступил на текущий момент (т.е стандарт)
                // https://trello.com/c/82bFXoh8/91-%D0%B0%D0%B1%D0%BD-%D0%BE%D0%BF-%D0%BF%D0%B5%D1%80%D0%B5%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B0%D1%82%D1%8C-%D0%BB%D0%BE%D0%B3%D0%B8%D0%BA%D1%83-%D1%80%D0%B0%D1%81%D1%87%D1%91%D1%82%D0%BE%D0%B2-%D0%BF%D0%B5%D0%BD%D0%B8


                return $isInCharge && $end->diffInDays(Carbon::parse($row->payment_date)) >= 0;
            } else {
                return $isInCharge;
            } // $row->n!=1 &&
        });


        return (float)$filtered->sum(function ($sched) {
            return $sched['sum_total'] > 0 ? $sched['sum_total'] : $sched['sum_payment']; // сумма долга с процентами либо без
        });


    }

    // TODO depricated?  getPaymentDue больше не нужен

    public function PenaltyDaily()
    {
        return $this->hasMany('App\PenaltyDaily', 'penalty_id', 'id');
    }

    public function PenaltyCorrection()
    {
        return $this->hasMany('App\PenaltyCorrection', 'penalty_id', 'id');
    }

    public function Lead()
    {
        return $this->belongsTo('App\Lead', 'lead_id', 'lead_id');
    }

    public function Incompays()
    {
        return $this->hasMany('App\IncomPays', 'lead_id', 'lead_id');
    }

    public function Schedule()
    {
        return $this->belongsTo('App\Schedule', 'schedule_id', 'id');
    }
    public function Instalment()
    {
        return $this->belongsTo('App\Instalment', 'lead_id', 'lead_id');
    }

}
