<?php
// Оповещения о задолжности

namespace App\Http\Controllers;



use App\Exceptions\CriticalException;
use App\Lead;
use App\Penalty;
use App\PenaltyNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Str;


/*
 *
 * таблица :
id контакта,
id сделки,
номер телефона,
текст смс,
сумма платежа,
дата платежа,
тип объекта (парковка или квартира),
планируемая дата отправки,
фактическая дата отправки (пустое поле для нас)
 *
 */

class PenaltyNotificationController extends Controller
{

    private $today, $yesterday;
    private $P100ClientType = ['100% оплата', '100% оплата + маткап'];
    private $PClientType = ['беспроцентная рассро', 'процентная рассрочка'];
    private $maxItemsToProcessAtOnce = 100;
    private $P100_sms_text = "Уважаемый клиент, напоминаем Вам о просрочке очередного платежа по договору. С уважением, Ак Барс Дом.";
    private $P_sms_text = "Уважаемый клиент, напоминаем Вам о внесении очередного платежа по договору. С уважением, Ак Барс Дом.";
    private $object_types = ['Паркинг' => '4000', 'Гараж/паркинг'=> '4000', 'Квартира'=> '30000']; // типы объектов и мин. уровень платежа (платеж > )


    public function __construct()
    {
        $this->today = Carbon::now()->toDateString();
        $this->yesterday = Carbon::yesterday()->toDateString();
        $this->before3days = Carbon::now()->subDays(3)->toDateString();
        $this->after7days = Carbon::now()->addDays(7)->toDateString();


    }
    /*
     *
     */
    public function run()
    {
//dd($this->before3days,  $this->yesterday, $this->today);


        $r7d = $this->remind7DaysBefore();
        // здесь все лиды с платежом через 7 дней по графику


        foreach($r7d as  $payIn7  ) {


            //  отправляем уведомление



            $lead = Lead::find($payIn7->lead_id);

            //  для каждого контакта запись
            foreach($lead->ContactsLinks->where('deleted', '<>', 1 ) as $contact ) {

                $pn = new PenaltyNotification;
                $pn->contact_id = $contact->contact_id;
                $pn->lead_id = $payIn7->lead_id;
                $pn->phone = @$contact->Contact->phone;
                $pn->sms_text = $this->P_sms_text;
                $pn->payment_sum = $payIn7->sum_total;
                $pn->payment_date = $payIn7->payment_date;
                $pn->target_date = $this->today;
                $pn->object_type_id = $payIn7->object_type_id;
                $pn->object_type = $payIn7->object_type_name;

                $pn->save();

            }


        }









        $r3d = $this->remindAfter3Days();
        // здесь все должники
        // осталось проверить день платежа и тип должника


        foreach($r3d as $debtor  ) {

            if(!$debtor->debt) continue;  // just in case
           //dd($lead_id);

            // клиент Рассрочка
            if(in_array($debtor->payment_type, $this->PClientType  ) && $debtor->count_payments < 2 ) continue; // условие: больше чем один платеж
            if(in_array($debtor->payment_type, $this->PClientType  ) && !in_array($debtor->object_type_name, array_keys($this->object_types) )    ) continue; // условие: определенные объекты, уже проверено по sql (by any chance)

            // клиент Рассрочка100

            // в иных случаях отправляем уведомление



            $lead = Lead::find($debtor->lead_id);

                //  для каждого контакта запись
            foreach($lead->ContactsLinks->where('deleted', '<>', 1 ) as $contact ) {

                $pn = new PenaltyNotification;
                $pn->contact_id = $contact->contact_id;
                $pn->lead_id = $debtor->lead_id;
                $pn->phone = @$contact->Contact->phone;


                $pn->sms_text = $this->P100_sms_text;
                $pn->payment_sum = $debtor->sum_total;
                $pn->payment_date = $debtor->payment_date;
                $pn->target_date = $this->today;
                $pn->object_type_id = $payIn7->object_type_id;
                $pn->object_type = $payIn7->object_type_name;

                $pn->save();

            }


        }










    }

    /*
     *
     *
     * группировка платежей по лид_ид
     * подсчет суммы долга и суммы факт оплаты, сортировка по разнице  (разница между суммой основного долга и суммой принятых платежей ),
     * т.е по признаку просрочки, лиды с просрочкой будут наверху
     * при таком подходе каждый раз (напр. каждый час) будем отрабатывать лиды с просрочкой
     */
    private function remindAfter3Days() {

        /*
                select payment_shedule.lead_id  ,
        sum( IncomPays.sum ) < sum(payment_shedule.sum_total) as debt,
        payment_shedule.payment_date


        from payment_shedule
            INNER JOIN `lead_params` ON `lead_params`.`lead_id` = `payment_shedule`.`lead_id`
        # 	INNER JOIN `links_report_portal` ON `links_report_portal`.`lead_id` = `payment_shedule`.`lead_id`
            LEFT JOIN `IncomPays` ON `IncomPays`.`lead_id` = `lead_params`.`lead_id` and IncomPays.payment_target not in (4,12)
        WHERE
             `lead_params`.`payment_type` IN ( '100% оплата' )
                AND NOT EXISTS ( SELECT 1 FROM `penalty_notifications` WHERE `penalty_notifications`.`payment_date` = '2020-02-18' AND penalty_notifications.lead_id = lead_params.lead_id )
             # lead_params.lead_id = 15195009
         AND  payment_shedule.payment_date = '2020-02-18'


        GROUP BY payment_shedule.lead_id #, links_report_portal.contact_id

        ORDER BY debt desc
        LIMIT 10


                */





        return DB::table('payment_shedule')
            ->select(DB::raw(' payment_shedule.lead_id,   sum( IncomPays.sum ) < sum(payment_shedule.sum_total) as debt,
                                        payment_shedule.payment_date, payment_shedule.sum_total,  lead_params.payment_type, count(*) as count_payments,
                                        object_types.type_name as object_type_name, object_types.type_id as object_type_id
                                 '))
            //->where('payment_shedule.payment_date',  $this->before3days )  // дата платежа 3-го дня
            ->where('payment_shedule.payment_date',   '>=',  MIN_PENALTY_DATE )  // не считаем график до 1 февраля
            ->where(function ($query) {
                $query->whereIn('lead_params.payment_type',  $this->P100ClientType  );  // клиенты с формой оплаты Рассрочка/100
                 $query->orWhere(function ($query) { // или
                     $query->whereIn('lead_params.payment_type',  $this->PClientType  )  // клиенты с формой оплаты Рассрочка
                           ->whereIn('object_types.type_name',  array_keys($this->object_types )  );  // и  определенный тип объекта

                 }) ;

            })

            ->join('lead_params', 'lead_params.lead_id', '=', 'payment_shedule.lead_id')  // группировка будет по lead_id
            ->leftJoin('object_params', 'object_params.object_id', '=', 'lead_params.object_id')
            ->leftJoin('object_types', 'object_types.type_id', '=', 'object_params.type_id')
            ->leftJoin('IncomPays', function ($join)
                {
                    $join->on('IncomPays.lead_id', '=', 'lead_params.lead_id'); // привязка  к lead_params
                    $join->whereNotIn('IncomPays.payment_target',   Penalty::$penalty_payment_target   ) ;  // все кроме штрафов
                    $join->where('IncomPays.incomDate', '>=',  MIN_PENALTY_DATE  ) ;  //  только платежи после 1го февраля

                })
            ->whereNotExists(function ($query) {

                $query->select(DB::raw(1))
                    ->from('penalty_notifications')
                    ->where('penalty_notifications.payment_date', $this->before3days  )
                    ->whereRaw('penalty_notifications.lead_id = lead_params.lead_id');
            })
            ->groupBy('payment_shedule.lead_id') // группировка  по lead_id
            ->orderBy('debt', 'desc')       // сортировка - должники наверху
            ->limit($this->maxItemsToProcessAtOnce)
            ->get()
             ->where('debt', 1 ) // нужны только должники
             ->where('payment_date',  $this->before3days )  ; // дата платежа 3-го дня









    }


    /*
     *  долги не обязательны
     *  у кого есть платеж через 7 дней, объект  квартира или паркинг
     */

    private function remind7DaysBefore() {


        return DB::table('payment_shedule')
            ->select(DB::raw(' payment_shedule.lead_id,   
                                        payment_shedule.payment_date, payment_shedule.sum_total, lead_params.payment_type, 
                                        object_types.type_name as object_type_name, object_types.type_id as object_type_id
                                 '))
            ->where('payment_shedule.payment_date',  $this->after7days )  //  платеж через 7
            ->whereIn('lead_params.payment_type',  $this->PClientType  )  // клиенты с формой оплаты Рассрочка
            ->where(function ($query) {
                    foreach($this->object_types as $type_name => $min_sum ) {
                        $query->orWhere(function ($query) use ($type_name,  $min_sum) { // или
                            $query->where('object_types.type_name', $type_name  )  // объект определенного типа
                            ->where('payment_shedule.sum_total', '>',  $min_sum  );  // и  требования по мин. сумме  ежем. платежа в соотвестствии с типом объекта

                        }) ;

                    }






            })

            ->join('lead_params', 'lead_params.lead_id', '=', 'payment_shedule.lead_id')  // группировка будет по lead_id
            ->leftJoin('object_params', 'object_params.object_id', '=', 'lead_params.object_id')
            ->leftJoin('object_types', 'object_types.type_id', '=', 'object_params.type_id')

            ->whereNotExists(function ($query) {

                $query->select(DB::raw(1))
                    ->from('penalty_notifications')
                    ->where('penalty_notifications.payment_date', $this->after7days  )
                    ->whereRaw('penalty_notifications.lead_id = lead_params.lead_id');
            })

            ->limit($this->maxItemsToProcessAtOnce)
            ->get();



    }






    private function ______getP100() {

        try {


            return DB::table('lead_params')
                ->whereIn('payment_type', $this->clientP100)  // клиенты с формой оплаты Рассрочка/100
                ->join('payment_shedule', function ($join) //  по графику платежей
                {
                    $join->on('payment_shedule.lead_id', '=', 'lead_params.lead_id'); // привязка  к lead_params
                    $join->on('payment_date', '=', DB::raw("'" . $this->today . "'")); //  сегодня платеж по графику

                })
                ->join('links_report_portal', 'links_report_portal.lead_id', '=', 'lead_params.lead_id') // возьмем сразу все контаткы
                ->whereNotExists(function ($query) {

                    $query->select(DB::raw(1))
                        ->from('penalty_notifications')
                        ->where('penalty_notifications.payment_date', $this->today)
                        ->whereRaw('penalty_notifications.contact_id = links_report_portal.lead_id');
                    //->whereRaw('penalty_notifications.lead_id = lead_params.lead_id');
                })
                ->limit($this->maxClientsToProcessAtOnce)
                ->get();

        } catch(Exception $e)
        {

            throw new CriticalException('не удается получить доступ к базе при формировании очереди на отправку оповещения о задолжности. оповещения могут быть не отпрвалены. ' );

        }


    }




    private function getClients() {
        DB::connection()->enableQueryLog();





        dd(666, DB::getQueryLog());









             //->with('Schedule')
             // ->get();
            //

/*        dd( Str::replaceArray('?', Lead::whereIn ('payment_type', $this->clientP100 )
            ->with('Schedule')->getBindings(), Lead::whereIn ('payment_type', $this->clientP100 )
            ->with('Schedule')->toSql() )
          );*/



    }


}


