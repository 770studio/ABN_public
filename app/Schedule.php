<?php

namespace App;

use App\Events\ScheduleModifiedEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;


class Schedule extends Model
{
    use Notifiable;

    protected static $bailouts = [
        1 => 'Заявление о досрочном погашении',
        2 => 'Доплата по замерам БТИ',
        3 => 'Индивидуальные условия'
    ];
    protected $table = 'payment_shedule';
    // при обновлениии будет запущено событие ScheduleModifiedEvent::dispatch
    protected $dispatchesEvents = [
        'updated' => ScheduleModifiedEvent::class,
        //'deleted' => ScheduleModifiedEvent::class,   при удалении, перерасчет будет излишним
    ];

    public static function SumTotal(Collection $schedule)
    {

        return $schedule->sum(function ($sched) {
            dd($sched);
            return $sched['sum_total'] > 0 ? $sched['sum_total'] : $sched['sum_payment']; // сумма долга с процентами либо без
        });


    }

    public static function getBailouts($json = false)
    {

        return $json ? json_encode(self::$bailouts) : self::$bailouts;
    }

    public static function Calculate(&$request_data)
    {
        // полный аналог яваскрипта , который делает калькуляцию на фронте

        $data = [];

        $total_payment = $paid = 0;
        // сумма рассрочки
        $inst_sum = round((float)$request_data->instalment_sum, 2);
        $initial_sum = $total_payment = round((float)$request_data->initial_payment_sum, 2);

        array_push(
            $data,
            [
                'n' => 1,
                'payment_date' => $request_data->initial_payment_date,
                'sum_prs' => 0,  // сумма процентов
                'sum_payment' => $initial_sum,  // сумма перв. взноса
                'sum_total' => $initial_sum,  //  база + проценты
                'total_payings' => round((float)$request_data->contract_sum, 2), // остаток выплат
                'lead_id' => $request_data->lead_id,
                'added' => 0,

            ]
        );


        if ($request_data->payments_count > 1) {
            // если проблема с датой , то вылетит эксепшн
            $first_payment_date = Carbon::createFromFormat('Y-m-d', $request_data->first_payment_date);

            $current_date = $first_payment_date;  //  дата первого платежа (есть еще дата первонач. платежа, не путать)

            $payments_count = (int)$request_data->payments_count - 1; // на какое количество платежей ты делишь сумму
            // рссрочки. К примеру. если мы выбраем 12 платежей, то нужно делить на 11. Т.к. 12-ый платеж занимает первоначальный взнос

            // средний платеж
            $avg_payment_with_kop = (float)($inst_sum / $payments_count);
            $avg_payment = (int)$avg_payment_with_kop;
            $_kop = $avg_payment_with_kop - $avg_payment;

            // остаток
            $remainder = $_kop * $payments_count;

            $added_payment_kop = 0;

             for ($i = 1; $i <= $payments_count; $i++) {

                $current_date = ($i == 1) ? $current_date : $current_date->addMonths((int)$request_data->period);   // добавить  1,3 или 6 мес

                $sum_payment = $avg_payment;
                $total_payings_remained = (float)$request_data->instalment_sum - $paid;  // остаток выплат без процентов
                $sum_prs = (float)($total_payings_remained * (float)$request_data->inst_prs) / 100;

                $added_payment_with_kop =  $sum_payment  +  $sum_prs;
                $added_payment = (int)$added_payment_with_kop;
                $added_payment_kop+= ($added_payment_with_kop - $added_payment);

                if ($payments_count === $i) {
                    // последний платеж, добавить остаток (копейки) в сумму с проц. и без
                    $sum_payment = $avg_payment + $remainder;
                    $added_payment+= $added_payment_kop;

                }

                $total_payment += $added_payment_kop;

                array_push(
                    $data,
                    [
                        'n' => $i + 1,
                        'payment_date' => $current_date->toDateString(),
                        'sum_prs' => round($sum_prs, 2),
                        'sum_payment' => round($sum_payment, 2),
                        'sum_total' => round($added_payment, 2),
                        'total_payings' => round($total_payings_remained, 2),
                        'lead_id' => $request_data->lead_id,
                        'added' => 0,
                    ]
                );


                $paid += $sum_payment; // должно быть выплачено

            }

        }


        // доп платежи , БТИ и др.
        if ($added_payment = round((float)@$request_data->added_cost['sum_payment'], 2)) {
            array_push(
                $data,
                [
                    'n' => $i + 1,
                    'payment_date' => $request_data->added_cost['payment_date'],
                    'sum_payment' => $added_payment,
                    'total_payings' => $added_payment,
                    'lead_id' => $request_data->lead_id,
                    'added' => 1,
                    'sum_prs' => 0,
                    'sum_total' => $added_payment,
                ]
            );

            $total_payment += $added_payment;

        }


//var_dump( $data,  $total_payment  );

        return $data;


    }

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * глобал скоуп для фильтрации невразумительных дат payment_date
         */
        static::addGlobalScope('sensibleDate', function (Builder $builder) {
            $builder->where('payment_date', '>', Penalty::MIN_DATE);
        });
    }

    public function Penalties()
    {
        return $this->hasMany('App\Penalty',  'schedule_id', 'id' );
    }
    public function Penalties_nocorrection()
    {
        return $this->hasMany('App\PenaltyNoCorrection',  'schedule_id', 'id' );
    }
    public function Lead()
    {
        return $this->belongsTo('App\Lead', 'lead_id', 'lead_id');
    }

    /*    public function uploadSchedule($data) {
            if( $this->exists() ) throw new CriticalException(' график уже есть, ошибка:' . $this->lead_id );


        }

        */

    /*    public static function scopeDebtSum($q, $uptoDate)
        {
            return $q->where('payment_date', '>=', MIN_PENALTY_DATE ) // дата платежа больше даты начала отсчета
              ->where('payment_date', '<=', $uptoDate ) ;  // дата платежа меньше даты проверки
        }*/

    public function Instalment()
    {
        return $this->belongsTo('App\Instalment', 'lead_id', 'lead_id');
    }

    public function contract()
    {
        return $this->belongsTo('App\Instalment', 'lead_id', 'lead_id');
    }

    public function exists()
    {
        return (bool)$this->n;
    }

    public function getTotalDeptAttribute()
    {
        return $this->sum_total > 0 ? $this->sum_total : $this->sum_payment; // сумма долга с процентами либо без
    }


}
