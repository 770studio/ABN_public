<?php
namespace App\Services\Penalty;

use App\IncomPays;
use App\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PenaltyAccountingService {


    /**
     *  При поступление платежа (кроме платежей в оплату штрафов)
     *  сумма долга изменяется, независимо от того погасил ли платеж
     *  долг или позицию графика платежа полностью.
     *  Если долг не погашен полностью, возникает новое начисление для новой суммы долга.
     *  Одно из начислений (позиций пеналти), а именно очередное по порядку погашено,
     *  можем записать дату погашения.
     *
     *
     */



    /**
     * задача определить date_payment
     * date_payment есть дата погашения платежа IncomPays, только в том случае, если
     * данным платежем оплачена позиция графика (пусть даже частично), по которой имеются штрафные начисления
     * прямой привязки платежей к графику или к пеналти нету
     *
     * процедура начисления пени проверяет , ессть ли долг на дату Х, по разности суммы долга и суммы оплаты на эту дату,
     * только после установки факта наличия долга происходит движение по позициям графика
     * поэтоиму на данный момент нет хорошего решения для включения данной задачи в эту процедуру
     *
     * соответственно при поступлении платежа в уплату основного долга
     * нужно перебрать все платежи, посмотреть какие позции графика они закрывают (не важно полностью или частично),
     * если позция графика связана со штрафом, записать дату погашения date_payment в данный штраф
     * работать только по текущему платежу некорректно, так как по конкретной позиции графика может вообще
     * не быть начислений и происходит неправильный мэппинг платежа на пеналти
     *
     *
     */

    /**
     * @var bool
     */
    private $test;

    /**
     * @var Schedule
     */
    private $Schedule;

    public function __construct()
    {
    }

    public function test()
    {
        $this->test = true;
        return $this;
    }

    /**
     * @param IncomPays $ip
     */
    public function RegisterNewIncomingPayment(IncomPays $ip)
    {
        //set_time_limit(100);
        ini_set('max_execution_time', '100');
        //Log::debug(ini_get('max_execution_time'));
        dump('ЛИДИД:' , $ip->lead_id);

        // весь график
        $this->Schedule = $ip->PaymentSchedule->sortBy('payment_date');
        // все платежи
        IncomPays::collectByLeadId($ip->lead_id)
            ->map(function ($ip)  {
                dump('--------------------');
                dump('--------------------');

                dump('сумма платежа:' , $ip->sum);
                dump('дата платежа:' , $ip->incomDate);
                $this->conduct($ip->sum, $ip->incomDate);
            });

    }

    private function conduct($sum , $incomDate)
    {



            // оставшиеся долги
        dump('кол-во неопл. позиций по граифку (осталось):',  count( $this->Schedule->where('sum_total', '>', 0)) );
             foreach($this->Schedule->where('sum_total', '>', 0) as $sch)
            {
                dump('по графику '.$sch->id.' осталось заплатить:' . $sch->sum_total );
                dump('от оплаченной суммы осталось:' . $sum );

                if($sch->Penalties->isNotEmpty()) {
                    dump('---------');

                    dump('начисленные пени :');

                    $sch->Penalties->each(function($penalty){
                        dump('---------');
                        dump('id :' . $penalty->id);
                        dump('id графика:' . $penalty->schedule_id);
                        dump('сумма начисления :' . $penalty->overdue_sum);
                        dump('начислено :' . $penalty->penalty_sum);



                    });

                    dump('---------');
                    dump('---------');
                }

                if($sum<=0) return; // денег больше нет
                // уменьшили сумму на сумму долга по графику
                if($sum >= $sch->sum_total) {
                    dump('сумма гасит график полностью, дата погашения:' . $incomDate  );
                    $sum-= $sch->sum_total;
                    $sch->sum_total = 0;
                } else { // $sch->sum_total > $sum
                    dump('сумма гасит только частично, дата погашения:' . $incomDate  );

                    $sch->sum_total -= $sum;
                    $sum = 0;
                }



                // если по графику нет штрафных начислений, то запишем дату погашения даже задним числом (ранний платеж)
                // инчаче нельзя использовать ранний платеж, т.к штраф бы не образовался , если платеж перекрыл
                // долг целиком, т.е это только частичное
                // иными словами не берем частичное погашение, если оно было до вступления в силу задолжности по графику
                // при этом фиксируем дату самого первого погашения


                $sch->Penalties->each(function($penalty) use ($incomDate) {
                    if(!$penalty->date_payment && $incomDate > $penalty->overdue_date)
                    {
                        $penalty->date_payment = $incomDate;
                        $penalty->save();
                    }

                });

                // сам инстанс isDirty т.к мы уменьшали sum_total, persist нельзя делать ни в коем случае
                // if($sch->date_paid != $incomDate) {
                // пишем дату только , если платеж Incompays поступил в оплату позиции
                // графика (дата внесения платежа больше  даты наступления задолности по графику)

                if(  !$sch->date_paid || $incomDate > $sch->payment_date ) {
                    Schedule::where('id', $sch->id)
                    //->whereNull('date_paid')
                        ->update(['date_paid' => $incomDate]);

                    $sch->date_paid = $incomDate;
                }

            }





    }

    public function update_date_payment_DEPR(Schedule $sch,  $incomDate)
    {
        $penalty_queryBuilder = $this->nocorrection
            ? $sch->Penalties_nocorrection()
            : $sch->Penalties();

        if($this->test) {
            dump( ' дата погашения: ' . $incomDate );
        } else {
            $penalty_queryBuilder ->where('overdue_sum',  $sch->_sum_debt)
                ->where(function($q) use ($incomDate) {
                    return $q->where('date_payment', '!=', $incomDate) // не надо обновлять повторно
                    ->orWhereNull('date_payment');
                })
                ->update(['date_payment'=>$incomDate]);
        }

    }
}
