<?php

namespace App\Http\Controllers;

use App\Instalment;
use App\Lead;
use App\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Studio770Utils\AddWorkHours;

// https://trello.com/c/VTOzL7Wx/44-%D0%B0%D0%B1%D0%BD-api-%D0%BF%D0%BE-%D1%84%D0%BE%D1%80%D0%BC%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D1%8E-%D0%B3%D1%80%D0%B0%D1%84%D0%B8%D0%BA%D0%B0-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B5%D0%B9

/**
 * Class PaymentScheduleAutoBuildAPIController
 * @package App\Http\Controllers
 *
 *
 */
final class PaymentScheduleAutoBuildAPIController extends Controller
{
    private $holidays = [];
    private $request;
    // способы расчета графика в зависимости от формы оплаты
    private $payment_types = [
        // кол-во платежей - 1 TODO проерить ?, ПВ в течение 5 рабдней, дата послед. платежей - через месяц после ПВ
        '1' => ['payments_count' => 1],
        // кол-во платежей в параметрах,   ПВ в течение 5 рабдней,  дата послед. платежей - не позднее 15 числа месяца
        '2' => ['first_payment_date' => '15'],
        // кол-во платежей в параметрах,   ПВ в течение 5 рабдней,  дата послед. платежей - не позднее 15 числа месяца, доп платеж (мат капитал)  + 60 календарных дней 
        '3' => ['first_payment_date' => '15', 'add_payment' => 60],
        // 4 варинт было принято решение- вообще не делать на данный момент, оказалось, что надо рассчитывать по множеству вариантов, у нас такой рассчёт данным ТЗ не предусмотрен
        '4' => [],

    ];
    // по типу воронки определяется способ начисления пеней (тип и значение)
    private $funnel_types = [
        '1088983' => [4, 0.5],  // Величина пени в процентах
        '1088980' => [4, 0.5],  // Величина пени в процентах
        '721195' => [3],  // 1/300  ставки рефинансирования


    ];


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create_schedule(Request $request)
    {
        Log::channel('ps_autobuild')->debug('Новый запрос:', $request->toArray());
        if (!$this->ValidateAPIKey($request)) {
            Log::channel('ps_autobuild')->debug('Ошибка авторизации');
            return response()->json([
                'errors' => 'Unauthorized',
            ], 401);

        }


        $this->request = &$request;
        // до валидации


        // $_request = new Request();
        // набивка
        $request->merge([
                'API_REQUEST' => true,
                'lead_id' => $request->lead_id,  //Validation+
                'penalty_type' => $this->getPenaltyType(),  // funnel_id validated
                'penalty_value' => $this->getPenaltyValue(),  // funnel_id validated
                'total_sum' => $this->getTotalSum(), //  contract_sum validated
                'initial_payment_sum' => $this->getIPS(),  //  initial_payment_sum validated
                'instalment_sum' => $this->getInstalmentSum(), // рассчетная instalment_sum validated
                'period' => 1, // период всегда месяц
                'inst_prs' => 0, // процент всегда 0

                // payments_count =  $request->payments_count  validated
                //'initial_payment_date' =>
                //'first_payment_date'=>

            ]
        );


        $this->validateRequest($validator);


        if ($validator->fails()) {
            Log::channel('ps_autobuild')->debug('Ошибка валидатора', $validator->errors()->toArray());

            return response()->json([
                'errors' => $validator->errors()->toArray(),
            ], 418);

        }

        // $lead = Lead::find(@$data['lead_id']);
        //if(!$lead) throw new \Exception("Тут должна бать сделка , а ее нет :((( ");

        $request->merge([

                'initial_payment_date' => $this->getInitialPaymentDueDate(),
                'first_payment_date' => $this->getPaymentDueDate(),

            ]
        );


        $response = false;
        DB::transaction(function () use (& $response) {

            Auth::loginUsingId(1); #TODO создать юзера
            $ps = new PaymentsSchedule();
            Schedule::where('lead_id', $this->request->lead_id)->delete(); // если есть график - удаляем весь
            if (!Instalment::find($this->request->lead_id)) $ps->api_createContract($this->request);
            Log::channel('ps_autobuild')->debug('Создаем график:', $this->request->toArray());
            $response = $ps->api_createPaymentSchedule($this->request);

            if (isset($response['error']) && $response['error']) {
                Log::channel('ps_autobuild')->debug('Ошибка:', $response);
                return $this->transformResponse($response);

            }

            // 3,4 тип с доп платежем
            if ($this->hasAddPayment()) {

                $this->request->merge([

                        'bailout' => 2, // основание внесения изменений
                        'sum_payment' => $this->getMatCapSum(),
                        'payment_date' => $this->getInitialPaymentDueDate('Carbon')->addDays($this->addPaymentDeadLine())->toDateString(), // например + 60 календарных дней

                    ]
                );

                $response = $ps->api_addPayment($this->request);

            }


        });


        return $this->transformResponse($response);


    }

    private function ValidateAPIKey(Request &$request)
    {
        return $request->header('X-FOOLPROOF') == config('services.ps_autobuild.api_key');


    }

    private function getPenaltyType()
    {
        $funnel = @$this->funnel_types[@$this->request->funnel_id];
        return (int)@$funnel[0];
    }

    private function getPenaltyValue()
    {
        $funnel = @$this->funnel_types[@$this->request->funnel_id];
        return (float)@$funnel[1];
    }

    private function getTotalSum()
    {
        return number_format(self::toFloat(@$this->request->contract_sum), 2, '.', '');
    }

    private static function toFloat($str)
    {
        return (float)trim(str_replace(',', '.', str_replace(' ', '', $str)));
    }

    private function getIPS()
    {
        return self::toFloat(@$this->request->initial_payment_sum);
    }

    private function getInstalmentSum()
    {  // сумма догвора - сумма 1 платежа - мат кап (при наличии)

        return (float)($this->getTotalSum() - $this->getIPS() - $this->getMatCapSum());
    }

    private function validateRequest(&$validator)
    {


        Validator::extend('schedule_not_exists', function ($attribute, $lead_id, $parameters) {
            return !Schedule::where('lead_id', $lead_id)->exists(); // !Instalment::find($lead_id) &&
        }, "График уже создан");


        Validator::extend('lead_exists', function ($attribute, $lead_id, $parameters) {
            return (bool)Lead::find($lead_id);
        }, "Сделки не существует");

        Validator::extend('lead_contract_sum', function ($attribute, $contract_sum, $parameters) {
            // dd( $this->request->lead_id, number_format(Lead::find( $this->request->lead_id )->contract_sum , 2 , '.', ''),  $this->getTotalSum() );
            return number_format(Lead::find($this->request->lead_id)->contract_sum, 2, '.', '') == $this->getTotalSum();
        }, "Сумма договора не соответствует БД");


        Validator::extend('mother_cap', function ($attribute, $payment_type, $parameters) {

            if ($payment_type == 3 || $payment_type == 4) {
                if (!isset($this->request->mother_cap) || !is_numeric($this->request->mother_cap)) return false;
            }
            return true;
        }, "Сумма доп платежа (мат.капитал) должна быть указана для данной формы оплаты");


        //dd($this->request->toArray()['contract_date']);
        $validator = Validator::make($this->request->toArray(), [
            'contract_date' => 'required|date_format:Y-m-d',
            'lead_id' => 'required|numeric|exists:lead_params,lead_id', // |exists:App\Lead,lead_id',
            // 'lead_id' => 'required|numeric|exists:lead_params,lead_id|contract_not_exists' , // |exists:App\Lead,lead_id',
            // 'lead_id' => new ValidateContractNotExists,
            'payments_count' => 'required|numeric|between:1,120',
            'contract_sum' => 'required|numeric|min:1|lead_contract_sum',
            'initial_payment_sum' => 'required|numeric|min:0',
            //'instalment_sum' => 'required|numeric|min:0',
            'payment_type' => [
                'required',
                Rule::In($this->getPaymentTypes()),
                'mother_cap',
            ],

            'funnel_id' => [
                'required',
                Rule::In($this->getFunnelTypes()),
            ],


        ]);


    }

    private function getPaymentTypes($return = 'keys')
    {
        return $return != 'keys' ? $this->payment_types : array_keys($this->payment_types);
    }

    private function getFunnelTypes($return = 'keys')
    {
        return $return != 'keys' ? $this->funnel_types : array_keys($this->funnel_types);
    }

    private function getInitialPaymentDueDate($return = 'DateString')
    {


        $contract_date = Carbon::createFromFormat("Y-m-d", $this->request->contract_date);

        // в завимисомти от формы оплаты
        // тип 1 100% оплата
        //  праздники
        $AddWorkHours = new AddWorkHours(0);

        $wdate = $AddWorkHours->getWorkDays(5, $contract_date->timestamp, $this->holidays);

        //return $return == 'DateString' ? $contract_date->addDays(5)->toDateString() : $contract_date->addDays(5) ;
        return $return == 'DateString' ? Carbon::parse($wdate)->toDateString() : Carbon::parse($wdate);

    }

    private function getPaymentDueDate()
    {
        // может быть стандартным - плюс месяц от ПВ
        // может быть фикс. 15-е число месяца
        $pv_addMonth = $this->getInitialPaymentDueDate("Carbon")->addMonth();
        $payment_type = $this->getPaymentType();
        if (isset($payment_type['first_payment_date']) && $payment_type['first_payment_date'] == '15') {
            $pv_addMonth->day = 15;
        }


        return $pv_addMonth->toDateString();

    }

    private function getPaymentType()
    {
        return $this->payment_types[$this->request->payment_type];
    }

    private function transformResponse($response)
    {
        //$error = false, $msg = false, $data = []
        //return parent::apiReply($error, $msg, $data);

        if (!@$response['error']) return response()->json(['errors' => [], 'result' => 'ok'], 200); //  response()->json($response, 200);
        return response()->json(['errors' => ['api_error' => [$response['error']]]], 418);

    }

    private function hasAddPayment()
    {
        return isset($this->payment_types[$this->request->payment_type]['add_payment']);
    }

    private function getMatCapSum()
    {
        return self::toFloat($this->request->mother_cap);
    }

    private function addPaymentDeadLine()
    {
        return (int)@$this->payment_types[$this->request->payment_type]['add_payment'];
    }


}
