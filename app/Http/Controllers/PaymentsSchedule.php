<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Contact;
use App\Events\ScheduleModifiedEvent;
use App\IncomPays;
use App\Instalment;
use App\Lead;
use App\Penalty;
use App\RefinRate;
use App\Schedule;
use App\ScheduleHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PaymentsSchedule extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public $contract = null;

    public function __construct()
    {
        $this->middleware(['auth', 'onlyForPayments']);
        // DB::connection()->enableQueryLog();
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */


    public function index()
    {

        // dd(Auth::user()->roles->first()->IsAbleToEditSchedule());

        return view('payments');
    }


    public function api_deleteSchRows(Request $request)
    {

        //dd(Auth::user()->roles->first()->IsAbleToEditSchedule());

        $msg = null;

        try {
            $request->validate([
                'del' => 'required',
            ]);

            $del = explode(',', $request->del);
            foreach ($del as $d) {

                $d = (int)$d;
                if (!$d) throw new Exception('Некорректные данные на входе');

                $ps = Schedule::find($d);

                if (Auth::user()->roles->first()->IsAbleToEditSchedule() == 'no') {
                    // доступ кроме статуса продано (142)

                    if ($ps->Lead->stage == 142) {
                        $msg = 'Один или более платежей не были удалены по причине: Статус 142. Доступ запрещен.';
                        continue;
                    }
                    // throw new Exception('Статус 142. Доступ запрещен.');


                }


                $ps->delete();// ScheduleModifiedEvent

            }


            if (!Schedule::where('lead_id', $ps->lead_id)->count()) {
                // все записи графика удалены
                $inst = Instalment::find($ps->lead_id);
                if ($inst) {


                    $inst->schedule_created = $inst->instalment_sum = $inst->inst_prs = $inst->total_sum = $inst->initial_payment_sum = 0;
                    $inst->period = 1;
                    $inst->initial_payment_date = $inst->first_payment_date = '';

                    $inst->save();


                }
            }


        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }


        return $this->apiReply(false, $msg,
            $ps->Lead->More()
        );
    }

    public function api_deleteRefinRateRows(Request $request)
    {


        $request->validate([
            'del_refin_rates' => 'required',
        ], $request->json()->all());


        RefinRate::destroy(
            collect($request->json()->get('del'))
                ->pluck('id')
                ->toArray()
        );

        return $this->apiReply(false, null,
            ['data' => RefinRate::getHistory(),
                'msg' => 'Ставки успешно удалены!']
        );
        /*            return response()->json(
                        array_merge(RefinRate::getHistory()->toArray(),
                         ['msg' => 'Ставки успешно удалены!']
                        )
                        ,200);*/


    }

    public function api_editRefinRateRow(Request $request)
    {
        //(new \Illuminate\Validation\ValidationException())->

        $request->validate([
            'edit_refin_rate' => 'required',
        ], $request->json()->all());

        $data = $request->json()->get('edit_refin_rate');

        Validator::make($data, [
            'rate' => 'required|numeric|between:0.01,99999.99',
            'start_date' => 'required|date',
        ])->validate();


        $refin = RefinRate::findOrfail($data['id']);
        $refin->rate = $data['rate'];
        $refin->start_date = $data['start_date'];
        $refin->save(); //TODO сохранить в журнал

        //TODO updated_by, updated_at


        return $this->apiReply(false, null,
            ['data' => RefinRate::getHistory(),
                'msg' => 'Изменения успешно сохранены!']
        );
        /*            return response()->json(
                        array_merge(RefinRate::getHistory()->toArray(),
                         ['msg' => 'Ставки успешно удалены!']
                        )
                        ,200);*/


    }

    public function api_editSchRow(Request $request)
    {


        try {
            $request->validate([
                'id' => 'required|integer',
                'lead_id' => 'required|integer',
                'payment_date' => 'required',
                'sum_prs' => 'required',
                'sum_payment' => 'required',
                'sum_total' => 'required',
                'total_payings' => 'required',
                'added' => 'required',
            ]);

            Carbon::createFromFormat('Y-m-d', $request->payment_date);

            $lead = Lead::find($request->lead_id);

            if (Auth::user()->roles->first()->IsAbleToEditSchedule() == 'no') {
                // доступ кроме статуса продано (142)
                if ($lead->stage == 142)
                    throw new Exception('Статус 142. Доступ запрещен.');


            }

            $ps = Schedule::find($request->id);
            //  if($ps->n == 1) throw new Exception('Нельзя редактировать первоначальный взнос.');
            $ps->payment_date = $request->get('payment_date');
            $ps->sum_prs = $request->get('sum_prs');
            $ps->sum_payment = $request->get('sum_payment');
            $ps->sum_total = $request->get('sum_total');
            $ps->total_payings = $request->get('total_payings');
            $ps->save();//  ScheduleModifiedEvent

        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }


        return $this->apiReply(false, null,
            $lead->More()
        );


    }

    public function api_addPayment(Request $request)
    {

        $lead_id = (int)$request->input('lead_id');
        $sum_payment = (float)$request->input('sum_payment');


        #TODO проверка прав с учетом есть ли уже действующий график платежей


        try {
            $payment_date = Carbon::createFromFormat('Y-m-d', $request->input('payment_date'))->toDateString();

            if (!$lead_id) throw new Exception('Пожалуйста загрузите действующий договор');
            $contract = Instalment::find($lead_id);
            if (!$contract) throw new Exception(' нет договора с lead_id:' . $lead_id);
            if (!$sum_payment) throw new Exception(' нет  sum_payment ');
            if ($payment_date !== $request->payment_date) throw new Exception('дата платежа не указана');
            if ($request->bailout != 2) throw new Exception('Для доп платежа должно быть соответствующее основание внесения изменений');
            if ($contract->Schedule->isEmpty()) throw new Exception('график еще не создан');
            // if( $contract->Schedule->where('added', 1 )->first() ) throw new Exception('доп платеж уже есть, добавлять можно только один платеж'  );


            $added_p = [
                'added' => 1,
                'lead_id' => $lead_id,
                'n' => count($contract->Schedule) + 1,
                'payment_date' => $payment_date,
                'sum_payment' => $sum_payment,
                'sum_prs' => 0,
                'sum_total' => 0,
                'total_payings' => 0,


            ];

            DB::transaction(function () use ($contract, $added_p, $sum_payment) {

                // сохраняем в Instalments
                $contract->update(['total_sum' => $contract->total_sum + $sum_payment, 'bailout' => 2]);

                ScheduleHistory::create(['event' => 'add_row', 'dump' => serialize($contract->Schedule->toArray()), 'lead_id' => $contract->lead_id, 'bailout' => 2, 'employee_id' => Auth::user()->id]);
                Schedule::insert($added_p);
                ScheduleModifiedEvent::dispatch($contract, __CLASS__ . ':' . __LINE__) ;


            });

        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }


        return $this->apiReply(false, null,
            $contract->Lead->More()
        );


    }

    public function api_updatePenalty(Request $request, $new = false)
    {


        try {

            if (!$request->id && !$request->lead_id) throw new Exception('нет id');
            if (!$request->update_reason || !in_array($request->update_reason, [1, 2, 3, 4, 5])) throw new Exception('нет update_reason');
            if (!in_array($request->status, [0, 1, 2, 3])) throw new Exception('нет status');
            if (!strtotime($request->penalty_date)) throw new Exception('нет penalty_date');
            if ($request->postponed && $request->status != 3) throw new Exception('Для отсрочки статус должен быть в значении "отменено" ');

            if (!$request->id) {
                $p = new Penalty;
                $p->lead_id = $request->lead_id;
                if (!strtotime($request->overdue_date)) throw new Exception('нет Даты наступления просрочки');
                $p->overdue_date = $request->overdue_date;
                $p->overdue_sum = (float)$request->overdue_sum;
                $p->overdue_days = (int)$request->overdue_days;
                $p->save();

                $new = true;

            } else $p = Penalty::find($request->id);

            if ($p->status == 3 && $request->status != 3) throw new Exception('Изменить статус отмененного штрафа нельзя ');
            if ($p->postponed == 1 && $request->postponed != 1) throw new Exception('Отсрочку нельзя вернуть назад) ');

            if (!$p->update($request->only(['update_reason', 'penalty_sum', 'status', 'penalty_date', 'comments', 'postponed'])))
                return $this->apiReply(true, 'Невозможно сохранить данные базу');


        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }

        return $this->apiReply(false, null, $new ? $p->Lead->More() : []);
    }


    public function api_getSettings()
    {
        /*     return $this->apiReply(false, null,
                 Settings::first()
             );*/

        try {

            $rate = RefinRate::getCurrentRate();
        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());

        }


        return $this->apiReply(false, null, $rate
        // RefinRate::orderBy('id', 'desc')->first()
        );

    }

    public function api_updateSettings(Request $request)
    {

        #TODO возможно дублировать записи Refinrate , unique key или по крайней мере
        # исключить дублирование

        try {
            $now = Carbon::now();

            if (!$rate = (float )$request->rate) throw new Exception('ставка не указана');
            if (Carbon::createFromFormat('Y-m-d', $request->start_date)->toDateString() !== $request->start_date) throw new Exception('дата не указана');
            $r = new RefinRate;
            $r->rate = $rate;

            $r->start_date = $request->start_date;
            $r->employee_id = Auth::user()->id;

            $r->save();


        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }

        return $this->apiReply();
    }


    public function api_createPaymentSchedule(Request $request)
    {

        $lead_id = (int)$request->input('lead_id');


        // создать график :

        try {
            if (!$lead_id) throw new Exception('Пожалуйста загрузите действующий договор');
            $contract = Instalment::find($lead_id);
            if (!$contract) throw new Exception(' нет договора с lead_id:' . (int)$request->input('lead_id'));
            if (@$request->added_cost['sum_payment'] && $request->bailout != 2) throw new Exception('Для доп платежа должно быть соответствующее основание внесения изменений');

            if ($contract->Schedule->isNotEmpty()) {
                if ($contract->Schedule->count() == 1) {
                    // перекинем нп апдейт
                    $data = Schedule::Calculate($request);
                    $data[0]['id'] = $contract->Schedule->first()->id;
                    $r = new Request();
                    $r->replace($data[0]);

                    return $this->api_editSchRow($r);
                }


                throw new Exception('график уже создан, обновление возможно только с помощью импорта');
            }

            DB::transaction(function () use ($contract, $request) {

                // сохраняем в Instalments
                $contract->update($request->only($contract->getFillable()));
                $contract->increment('schedule_created');
                $data = Schedule::Calculate($request);
                ScheduleHistory::create(['event' => 'create', 'dump' => serialize($data), 'lead_id' => $contract->lead_id, 'bailout' => 0, 'employee_id' => Auth::user()->id]);
                Schedule::insert($data);
                ScheduleModifiedEvent::dispatch($contract, __CLASS__ . ':' . __LINE__);


            });

        } catch (Exception $e) { //dd($e);
            // var_dump();
            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }


        return $this->apiReply(false, null,
            Instalment::find($lead_id)->Lead->More()
        );


        //dd(  DB::getQueryLog() );


    }


    public function api_createContract(Request $request)
    {
        $lead_id = (int)$request->input('lead_id');

        if (Instalment::find($lead_id)) return $this->apiReply(true, ' Договор уже есть:' . $request->input('contract_number'));

        $contract = new Instalment();
        $contract->lead_id = $lead_id;
        $contract->save();
        $contract = Instalment::find($lead_id);

        //dd(  DB::getQueryLog() );

        return $this->apiReply(false, null,
            $contract->Lead->More()
        );   //  ['lead_params.lead_id'=> $lead_id ]


    }

    public function api_getRefRateHistory(Request $request)
    {
        return $this->apiReply(false, null,
            ['data' => RefinRate::getHistory()]
        );
    }

    public function api_updateContract(Request $request)
    {
        //  dd( $request->input('lead_id'), $request->input('comments'), $request->input('nds'));
        //  $request->input('comments');
        $lead_id = (int)$request->input('lead_id');
        $contract = Instalment::exists($lead_id);
        if ($contract) {

            if (!$contract->update($request->only(['nds', 'comments', 'ttl_area'])))
                return $this->apiReply(true, 'Невозможно сохранить данные базу');

            //dd(  DB::getQueryLog() );
            return $this->apiReply();

            /*             return $this->apiReply(false, 'Успешно обновлено',   [
                             'lead' => Lead::GetWithJoins()->find( $lead_id )  ,
                             'inst'=> $contract
                         ]  );*/

        } else  return $this->apiReply(true, 'Договор не найден!');

    }

    public function api_searchContract(Request $request)
    {


        $stext = trim($request->input('stext'));
        $search_by = $request->input('search_by');


        try {
            //DB::connection()->enableQueryLog();

            if ($search_by && is_array($search_by) && $search_by['lead_id']) {
                return $this->apiReply($error = false, $msg = false, Lead::where('lead_id', (int)$search_by['lead_id'])->first()->More());
            }


            if (!$stext) throw new Exception('запрос пустой');
            // если  $stext это номер контракта и он единственный
            $leads = Lead::where('contract_number', $stext)->get();

            // не номер контракта , тогда это поиск по номеру контракта и сумме
            if ($leads->isEmpty()) {
                $leads = Lead::
                where('contract_number', 'like', '%' . $stext . '%')
                    ->orWhere('contract_sum', 'like', $stext . '%')
                    // ->orwhereHas('Contact', function ($query) use ($stext) {
                    //    $query->where('name', 'like', '%' . $stext . '%');
                    // })
                    // ->select('contract_number', 'contract_sum')
                    ->limit(10)
                    ->get();
            }
            if ($leads->isEmpty()) {
                // или по фио
                $lead_ids = Contact::with(array('ContactsLink' => function ($query) {
                    $query->select('contact_id', 'lead_id');

                }))
                    ->where('name', 'like', '%' . $stext . '%')
                    ->limit(10)
                    ->get()
                    //->where('ContactsLink.deleted', '<>', 1 )
                    ->where('ContactsLink.lead_id', '>', 0)
                    ->pluck('ContactsLink.lead_id');
                if ($lead_ids->isNotEmpty()) {
                    $leads = Lead::
                    whereIn('lead_id', $lead_ids)
                        ->limit(10)
                        ->get();
                }


            }


            // dd(  DB::getQueryLog() );

            //->first();


            $grouped = $leads->groupBy(function ($item, $key) {
                return str_replace(['№', ' '], '', $item->contract_number);
            });
            $leads = $grouped->flatMap(function ($values) {
                $maxDate = $values->max('contract_date');
                // dd( $values->max('contract_date') );
                return $values->where('contract_date', $maxDate);
            });

            $data = ['search_result' => true];
            if (@$leads->count() > 1 || @$leads->first()->contract_number != $stext) {

                $data['lead'] = $leads->transform(function ($item, $key) {  // pluck('contract_number', 'contract_sum')
                    // dd($item, $key);
                    return ['value' => $item->lead_id, 'cn' => $item->contract_number, 'c_sum' => $item->contract_sum, 'text' => $item->lead_id . ' | ' . $item->contract_number . ', сумма:' . $item->contract_sum];
                });


            } elseif ($leads->count() == 1) { //dd($leads->first()->More());
                $data = $leads->first()->More();

            }

            return $this->apiReply($error = false, $msg = false, $data);


        } catch (Exception $e) {

            return $this->apiReply($error = false, $msg = $e->getMessage());

        }
        //$whereArray = [ 'contract_number' => $stext ];


    }


    //Перуступка

    public function api_getAssignment(Request $request)
    {
//
        $lead_id = (int)$request->input('lead_id');
        dd($lead_id);
//        try {
//            if (!$lead_id) throw new Exception('Пожалуйста загрузите действующий договор');
//            $assignment = Assignment::find($lead_id);
//
//            return $this->apiReply($error = false, $msg = false, $assignment  ? $assignment->More() : []  );
//
//        }
//        catch(Exception $e)
//        {
//            // var_dump();
//            return $this->apiReply(true, 'Ошибка:' . $e->getMessage() );
//        }
//
    }

    public function api_addAssignment(Request $request)
    {


        //validate

        $lead_id = (int)$request->input('lead_id');


        try {


            $a = Assignment::create([
                'assignor' => $request->input('assignor'),
                'cessionary' => $request->input('cessionary'),
                'date' => Carbon::now()->toDateString(),
                'base' => $request->input('base'),
                'comments' => $request->input('comments'),
                'lead_id' => $lead_id
            ]);


            return $this->apiReply(false, null, $a
            // $a->Lead->More(  )
            );
            return $this->apiReply();

        } catch (Exception $e) {
            // var_dump();
            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }


    }


    //поиск клиентов
    public function api_searchCessionary(Request $request)
    {

        $searchQuery = $request->get('query');

        try {


            if ($searchQuery != null || $searchQuery != '') {
                $contacts = Contact::where('name', '!=', '')->where('name', 'like', '%' . $searchQuery . '%')->pluck('name', 'contact_id');
                $contactsArr['name'] = array();
                foreach ($contacts as $contact_id => $contact_name) {
                    array_push($contactsArr['name'], $contact_name);
                }

                return $this->apiReply(false, null, $contactsArr);
            } else {
                return $this->apiReply(false, null, null);
            }


        } catch (Exception $e) {
            // var_dump();
            return $this->apiReply(true, 'Ошибка: ' . $e->getMessage());
        }


    }




    //отчет по договору
    public function api_makeReport($lead_id)
    {

        try {

            //проверка на загрузку договора
            if ($lead_id) {

                //параметры сделки
                $lead_params = DB::table('lead_params')
                    ->select(DB::raw(
                        "
                    contacts.name as client_name,
                    lead_params.contract_number as contract_number,
                    lead_contract_type.contract_name as contract_name,
                    lead_params.contract_date as contract_date,
                    lead_params.contract_sum as contract_sum,
                    object_params.complex as complex,
                    object_params.address,
                    object_params.price_meter,
                    object_params.total_area,
                    lead_params.payment_type,
                    lead_params.bank_ipoteka,
                    lead_params.installment


                "))
                    ->leftJoin('lead_contract_type', 'lead_contract_type.contract_type_id', '=', 'lead_params.contract_type')
                    ->leftJoin('contacts', 'contacts.contact_id', '=', 'lead_params.client_id')
                    ->leftJoin('object_params', 'object_params.object_id', '=', 'lead_params.object_id')
                    ->where('lead_id', '=', $lead_id)
                    ->first();


                //рассрочка
                $instalment = Instalment::find($lead_id);

                //график платежей на дату формирования отчета

                $payments = DB::table('payment_shedule')
                    ->select(DB::raw(
                        "
                            SUM(payment_shedule.sum_payment) as sum_payment

                       "))
                    ->where('lead_id', '=', $lead_id)
                    ->where('payment_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->first();


                //поступления на дату формирования отчета
                $incomes = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                        SUM(IncomPays.sum) as incomes_sum
                        "))
                    ->where('contractNumber', '=', $lead_params->contract_number)
                    ->where('incomDate', '<=', Carbon::now()->format('Y-m-d'))
                    ->first();


                //дата формирования отчета
                $dateReport = Carbon::now()->format('d.m.Y H:i:s');

                //создаем новый  эксель
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                //название листа
                $sheet->setTitle('Отчет по договору');

                //ширина столбцов
                $sheet->getColumnDimension('A')->setWidth(2);
                $sheet->getColumnDimension('B')->setWidth(15);
                //номер договора
                $sheet->setCellValue('B1', $lead_params->contract_number)->getStyle("B1")->getFont()->setBold(true);
                $sheet->mergeCells('B1:G1');
                //Период
                $sheet->setCellValue('H1', 'по состоянию на ' . $dateReport)->getStyle("H1")->getFont()->setBold(true);
                $sheet->mergeCells('H1:K1');

                //Информация о договоре(заголовок)
                $sheet->setCellValue('B3', 'Информация о договоре')->getStyle("B3")->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0');
                $sheet->mergeCells('B3:K3');

                //блок Информация о договоре

                //номер договора
                $sheet->setCellValue('B4', 'Номер договора ')->getStyle("B4")->getFont()->setBold(true);
                $sheet->mergeCells('B4:C4');
                $sheet->setCellValue('D4', $lead_params->contract_number);
                $sheet->mergeCells('D4:K4');

                //вид договора
                $sheet->setCellValue('B5', 'Вид договора ')->getStyle("B5")->getFont()->setBold(true);
                $sheet->mergeCells('B5:C5');
                $sheet->setCellValue('D5', $lead_params->contract_name);
                $sheet->mergeCells('D5:K5');

                //покупатель
                $sheet->setCellValue('B6', 'Покупатель ')->getStyle("B6")->getFont()->setBold(true);
                $sheet->mergeCells('B6:C6');
                $sheet->setCellValue('D6', $lead_params->client_name);
                $sheet->mergeCells('D6:K6');


                //Комплекс/здание
                $sheet->setCellValue('B8', 'Комплекс/здание ')->getStyle("B8")->getFont()->setBold(true);
                $sheet->mergeCells('B8:C8');
                $sheet->setCellValue('D8', $lead_params->complex);
                $sheet->mergeCells('D8:K8');

                //Действует с
                $sheet->setCellValue('B9', 'Действует с  ')->getStyle("B9")->getFont()->setBold(true);
                $sheet->mergeCells('B9:C9');
                $sheet->setCellValue('D9', Carbon::createFromFormat('Y-m-d', $lead_params->contract_date)->format('d.m.Y'));
                $sheet->mergeCells('D9:E9');

                //Помещение
                $sheet->setCellValue('F9', 'Помещение ')->getStyle("F9")->getFont()->setBold(true);
                $sheet->mergeCells('F9:G9');
                $sheet->setCellValue('H9', $lead_params->address);
                $sheet->mergeCells('H9:K9');

                //Стоимость кв.м
                $sheet->setCellValue('B10', 'Стоимость кв.м ')->getStyle("B10")->getFont()->setBold(true);
                $sheet->mergeCells('B10:C10');
                $sheet->setCellValue('D10', $lead_params->price_meter . ' руб.');
                $sheet->mergeCells('D10:E10');

                //Площадь
                $sheet->setCellValue('F10', 'Площадь ')->getStyle("F10")->getFont()->setBold(true);
                $sheet->mergeCells('F10:G10');
                $sheet->setCellValue('H10', $lead_params->total_area . ' кв.м');
                $sheet->mergeCells('H10:K10');


                //Всего платежей
                $sheet->setCellValue('B11', 'Всего платежей ')->getStyle("B11")->getFont()->setBold(true);
                $sheet->mergeCells('B11:C11');
                $sheet->setCellValue('D11', $instalment->payments_count);
                $sheet->getStyle('D11')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->mergeCells('D11:K11');


                //Конечная сумма
                $sheet->setCellValue('B13', 'Конечная сумма  ')->getStyle("B13")->getFont()->setBold(true);
                $sheet->mergeCells('B13:C13');
                $sheet->setCellValue('D13', $instalment->total_sum . ' руб.');
                $sheet->mergeCells('D13:E13');

                //Текущая задолженность
                $sheet->setCellValue('F13', 'Задолженность по договору ')->getStyle("F13")->getFont()->setBold(true);
                $sheet->mergeCells('F13:H13');
                $sheet->setCellValue('I13', $instalment->total_sum - $incomes->incomes_sum . ' руб.');
                $sheet->mergeCells('I13:K13');

                //В т.ч. первонач. взнос
                $sheet->setCellValue('B14', 'В т.ч. первонач. взнос   ')->getStyle("B14")->getFont()->setBold(true);
                $sheet->mergeCells('B14:C14');
                $sheet->setCellValue('D14', $instalment->initial_payment_sum . ' руб.');
                $sheet->mergeCells('D14:E14');

                //Переплата

                if ($incomes->incomes_sum <= $payments->sum_payment) {
                    $pereplata = '0.00';
                } else {
                    $pereplata = $incomes->incomes_sum - $payments->sum_payment;
                }




                $sheet->setCellValue('F14', 'Переплата ')->getStyle("F14")->getFont()->setBold(true);
                $sheet->mergeCells('F14:H14');
                $sheet->setCellValue('I14', $pereplata . ' руб.');
                $sheet->mergeCells('I14:K14');

                $sheet->getStyle('B4:B14')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F4:F14')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT);


                //Тип оплаты
                $sheet->setCellValue('B15', 'Тип оплаты  ')->getStyle("B15")->getFont()->setBold(true);
                $sheet->mergeCells('B15:C15');
                $sheet->getStyle('B15')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('D15', $lead_params->payment_type);

                //Ипотека
                $sheet->setCellValue('E15', 'Ипотека  ')->getStyle("E15")->getFont()->setBold(true);
                $sheet->getStyle('E15')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->mergeCells('F15:G15');
                $sheet->setCellValue('F15', $lead_params->bank_ipoteka);

                //Рассрочка
                $sheet->setCellValue('H15', 'Рассрочка  ')->getStyle("H15")->getFont()->setBold(true);
                $sheet->getStyle('H15')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('I15', $lead_params->installment);


                //График погашения задолженности(заголовок)
                $sheet->setCellValue('B17', 'График погашения задолженности')->getStyle("B17")->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('B17')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0');
                $sheet->mergeCells('B17:K17');

                //шапка таблицы
                $sheet->setCellValue('B19', '№')->getStyle("B19")->getFont()->setBold(true);
                $sheet->setCellValue('C19', 'Дата')->getStyle("C19")->getFont()->setBold(true);
                $sheet->setCellValue('D19', 'Начислено')->getStyle("D19")->getFont()->setBold(true);
                $sheet->setCellValue('E19', 'Зачтено в счет оплаты')->getStyle("E19")->getFont()->setBold(true);
                // $sheet->setCellValue('F18', 'О*')->getStyle("F18")->getFont()->setBold(true);
                $sheet->setCellValue('F19', 'Пени')->getStyle("F19")->getFont()->setBold(true);
                $sheet->setCellValue('G19', 'Примечание')->getStyle("G19")->getFont()->setBold(true);

                //стиль
                $style = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]
                    ]

                ];

                $sheet->mergeCells('G19:K19');
                $sheet->getStyle('B19:K19')->applyFromArray($style);

                //ширина столбцов

//                $sheet->getColumnDimension('B')->setWidth(3);
//                $sheet->getColumnDimension('C')->setWidth(17);
//                $sheet->getColumnDimension('D')->setWidth(17);
//                $sheet->getColumnDimension('E')->setWidth(17);
//                $sheet->getColumnDimension('F')->setWidth(10);
                //$sheet->getColumnDimension('G')->setWidth(50);


                $sheet->getStyle('B19:K19')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                //2-я таблица

                ///////////////////////////////////////////////////////////////


                $IncomPays = IncomPays::collectByLeadId($lead_id);

                // $paid =  IncomPays::where('contractNumber','=',$lead_params->contract_number)->get();

                $Data = collect();
                $extra = collect();

                foreach (Schedule::where('lead_id', $lead_id)->orderBy('id', 'ASC')->get() as $n => $sch_row) {


                    $sum_on_schedule0 = $sum_on_schedule = $sch_row->sum_total > 0 ? $sch_row->sum_total : $sch_row->sum_payment;  // Начислено;

                    $Data->push(
                        (object)[

                            'n' => $n + 1, // № по порядку;
                            'date' => $sch_row->payment_date, // Дата;
                            'sum_on_schedule' => $sum_on_schedule,  // Начислено;
                            'sum_paid' => 0, // $profile->sum('sum'),  // Зачтено в счет оплаты;
                            'sum_penalty' => 0,  /*Penalty::
                                                     where('lead_id', $lead_id)
                                                         ->where('status', '!=', 3) // не отменено
                                                         ->where('penalty_date', '<=', $sch_row->payment_date)
                                                         ->sum('penalty_sum'),   // Пени; где дата <= даты платежа по графику*/
                            'note' => collect(), // $profile,  // Примечание.

                        ]


                    );


                }


                // распределение платежей


                foreach ($IncomPays->where('sum', '>', 0) as $p) {

                    // пришел платеж

                    foreach ($Data as $sch_row) {   //$sch_row = collect($sch_row);

                        // пока есть деньги , гасим график
                        // если платеж по графику полностью погашен пропускем его

                        if ($sch_row->sum_paid >= $sch_row->sum_on_schedule) continue;

                        //остаток от суммы по графику , который нужно доплатить
                        $sum_payoff = $sch_row->sum_on_schedule - $sch_row->sum_paid;

                        // сначала добьем остатки , если они есть
                        foreach ($extra->where('sum', '>', 0) as $ep) {
                            if ($ep->get('sum') <= $sum_payoff) {
                                // экстра запас равен остатку суммы по графику  или меньше ее
                                $sch_row->sum_paid += $ep->get('sum');
                                $sch_row->note->push(['date' => $ep->get('date'), 'sum' => $ep->get('sum')]);    // внесено;
                                $sum_payoff -= $ep->get('sum'); // остаток платежа, может быть больше ноля т.к. экстра запас может быть меньше  sum_payoff
                                $ep->put('sum', 0); // использован весь экстра запас

                            } else {
                                // экстра запас больше остатка суммы по графику $p->sum > $sum_payoff
                                // значит часть уйдет ,  а часть останется на следующий платеж по графику
                                $sch_row->sum_paid += $sum_payoff;
                                $sch_row->note->push(['date' => $ep->get('date'), 'sum' => $sum_payoff]);    // внесено;

                                $ep->put('sum', $ep->get('sum') - $sum_payoff); // использована часть экстра запаса
                                $sum_payoff = 0;  // остаток платежа


                            }

                        }


                        if ($sum_payoff == 0) continue; // к следующей дате по графику

                        if ($p->sum <= $sum_payoff) {
                            // платеж равен остатку суммы по графику  или меньше ее
                            $sch_row->sum_paid += $p->sum;
                            $sch_row->note->push(['date' => $p->incomDate, 'sum' => $p->sum]);    // внесено;
                            $p->sum = 0; // весь платеж ушел
                            break; // к следующему платежу

                        } else {
                            // платеж больше остатка суммы по графику $p->sum > $sum_payoff
                            $sch_row->sum_paid += $sum_payoff;
                            $sch_row->note->push(['date' => $p->incomDate, 'sum' => $sum_payoff]);    // внесено;
                            // и есть остаток , переходящий на следующий платеж по графику
                            $extra->push(collect(['date' => $p->incomDate, 'sum' => $p->sum - $sum_payoff]));
                            //$p->sum -= $sum_payoff ; // часть платежа ушла
                            $p->sum = 0; // весь платеж ушел ( часть в оплату, а часть в extra)
                            // идем к следующему пункту (дате платежа) по графику

                        }


                    }


                }


                $highestRow = 20;

                foreach ($Data as $dataItem) {

                    $sheet->setCellValue('B' . $highestRow, $dataItem->n);
                    $sheet->setCellValue('C' . $highestRow, Carbon::createFromFormat('Y-m-d', $dataItem->date)->format('d.m.Y'));
                    $sheet->setCellValue('D' . $highestRow, $dataItem->sum_on_schedule);
                    $sheet->setCellValue('E' . $highestRow, $dataItem->sum_paid);
                    $sheet->setCellValue('F' . $highestRow, $dataItem->sum_penalty);


//                    $sheet->getStyle('G'.$highestRow.':K'.$highestRow)
//                        ->getAlignment()->setWrapText(true);

                    $notesAll = [];
                    foreach ($dataItem->note as $noteArr) {
                        //$noteStrRow = $noteArr['sum'].' от '.Carbon::createFromFormat('Y-m-d',$noteArr['date'])->format('d.m.Y');
                        if ($noteArr['sum'] > 0) $notesAll[] = number_format($noteArr['sum'], 2, ',', ' ') . ' от ' . Carbon::createFromFormat('Y-m-d', $noteArr['date'])->format('d.m.Y');
                    }
                    $notesStr = '';
                    if (isset($notesAll)) {
                        $notesStr = implode(', ', $notesAll);


                        $rc = 0;
                        $line = explode("\n", $notesStr);
                        foreach ($line as $source) {
                            $rc += intval((strlen($source) / 55) + 1);
                        }


                        $numrows = $rc;
                        $sheet->setCellValue('G' . $highestRow, $notesStr);
                        $sheet->getRowDimension($highestRow)->setRowHeight($numrows * 12.75 + 2.25);
                        $sheet->mergeCells('G' . $highestRow . ':K' . $highestRow);
                        $sheet->getStyle('G' . $highestRow . ':K' . $highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getStyle('G' . $highestRow . ':K' . $highestRow)->applyFromArray($style);


                    }


                    $sheet->getStyle('B' . $highestRow . ':G' . $highestRow)->applyFromArray($style);


                    $highestRow++;
                }


                $sheet->setCellValue('C' . $highestRow, 'Итого')->getStyle('C' . $highestRow)->getFont()->setBold(true);

                $sumLastRow = $highestRow - 1;
                $sheet->setCellValue('D' . $highestRow, '=SUM(D19:D' . $sumLastRow . ')')->getStyle('D' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('E' . $highestRow, '=SUM(E19:E' . $sumLastRow . ')')->getStyle('E' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('F' . $highestRow, '=SUM(F19:F' . $sumLastRow . ')')->getStyle('F' . $highestRow)->getFont()->setBold(true);

                $sheet->getStyle('D' . $highestRow . ':F' . $highestRow)->applyFromArray($style);

                $highestRow = $highestRow + 1;
                $sheet->setCellValue('C' . $highestRow, 'Итого с пенями')->getStyle('C' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('D' . $highestRow, '=SUM(D19:D' . $sumLastRow . ')+SUM(F19:F' . $sumLastRow . ')')->getStyle('D' . $highestRow)->getFont()->setBold(true);
                $sheet->getStyle('D' . $highestRow)->applyFromArray($style);


                //3-я таблица
                $highestRow = $highestRow + 2;

                //Оплаты по датам
                $sheet->setCellValue('B' . $highestRow, 'Оплаты по датам')->getStyle('B' . $highestRow)->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('B' . $highestRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0');
                $sheet->mergeCells('B' . $highestRow . ':K' . $highestRow);

                $highestRow = $highestRow + 2;

                //шапка таблицы
                $sheet->setCellValue('B' . $highestRow, '№')->getStyle('B' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('C' . $highestRow, 'Дата')->getStyle("C" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('D' . $highestRow, 'Сумма оплаты')->getStyle("D" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('E' . $highestRow, 'Примечание')->getStyle("E" . $highestRow)->getFont()->setBold(true);
                $sheet->mergeCells('E' . $highestRow . ':K' . $highestRow);
                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);

                //ширина столбцов
                $sheet->getColumnDimension('B')->setWidth(3);
                $sheet->getColumnDimension('C')->setWidth(17);
                $sheet->getColumnDimension('D')->setWidth(17);
                $sheet->getColumnDimension('E')->setWidth(30);

                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                //поступления на дату формирования отчета
                $incomes = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                        incomDate as income_date,
                        sum as income_sum,
                        PaymentTargets.name as payment_target
                        "))
                    ->leftJoin('PaymentTargets', 'PaymentTargets.id', '=', 'IncomPays.payment_target')
                    ->where('contractNumber', '=', $lead_params->contract_number)
                    ->where('incomDate', '<=', Carbon::now()->format('Y-m-d'))
                    ->orderBy('income_date', 'ASC')
                    ->get();

                $highestRow = $highestRow + 1;
                $totalIncomeSum = 0;
                foreach ($incomes as $num => $incomeItem) {
                    $sheet->setCellValue('B' . $highestRow, $num + 1);
                    $sheet->setCellValue('C' . $highestRow, Carbon::createFromFormat('Y-m-d', $incomeItem->income_date)->format('d.m.Y'));
                    $sheet->setCellValue('D' . $highestRow, $incomeItem->income_sum);
                    $totalIncomeSum += $incomeItem->income_sum;//для итого

                    $sheet->setCellValue('E' . $highestRow, $incomeItem->payment_target);
                    $sheet->mergeCells('E' . $highestRow . ':K' . $highestRow);
                    $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);
                    $highestRow++;
                }


                $sheet->setCellValue('C' . $highestRow, 'Итого')->getStyle('C' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('D' . $highestRow, $totalIncomeSum)->getStyle('D' . $highestRow)->getFont()->setBold(true);
                $sheet->getStyle('D' . $highestRow)->applyFromArray($style);


                //4-я таблица
                $highestRow = $highestRow + 2;

                //Начисленные пени (расчет)
                $sheet->setCellValue('B' . $highestRow, 'Начисленные пени (расчет)')->getStyle('B' . $highestRow)->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('B' . $highestRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0');
                $sheet->mergeCells('B' . $highestRow . ':K' . $highestRow);

                $highestRow = $highestRow + 2;


                //шапка таблицы
                $sheet->setCellValue('B' . $highestRow, '№')->getStyle('B' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('C' . $highestRow, 'Дата начисления')->getStyle("C" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('D' . $highestRow, 'Сумма начисления')->getStyle("D" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('E' . $highestRow, 'Дата погашения')->getStyle("E" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('F' . $highestRow, 'П*')->getStyle("F" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('G' . $highestRow, 'Пени, %')->getStyle("G" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('H' . $highestRow, 'Начислено пени, руб.')->getStyle("H" . $highestRow)->getFont()->setBold(true);


                $sheet->mergeCells('H' . $highestRow . ':K' . $highestRow);
                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);


                //ширина столбцов
                $sheet->getColumnDimension('B')->setWidth(3);
                $sheet->getColumnDimension('C')->setWidth(17);
                $sheet->getColumnDimension('D')->setWidth(17);
                $sheet->getColumnDimension('E')->setWidth(17);
                $sheet->getColumnDimension('F')->setWidth(5);
                //$sheet->getColumnDimension('G')->setWidth(17);
                $sheet->getColumnDimension('H')->setWidth(17);
                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $penalty = Penalty::with('Instalment:lead_id,penalty_type,penalty_value')
                     //->with('Schedule:id,date_paid')
                     ->where('lead_id',  $lead_id)
                     ->orderBy('schedule_id')
                     ->orderBy('overdue_date')
                     ->get();



                // DB::connection()->enableQueryLog();
/*                $penalty = Penalty//DB::table('penalty as penalty')
                ::select(DB::raw(
                    "
                        penalty.id as id,
                        penalty.lead_id as lead_id,
                        overdue_date,
                        overdue_sum,
                        paid_at,
                        overdue_days,
                        penalty_sum,
                        instalments.penalty_type,
                        instalments.penalty_value,
						date_payment
                        "))
                    ->join('instalments', 'instalments.lead_id', '=', 'penalty.lead_id')
                    ->where('penalty.lead_id', '=', $lead_id)
                    // ->where('overdue_date','<=',Carbon::now()->format('Y-m-d'))
                    ->orderBy('overdue_date', 'ASC')
                    ->get();*/


                //dd(  DB::getQueryLog() );
                $highestRow = $highestRow + 1;
                $totalPenaltySum = 0;

                foreach ($penalty as $num => $penaltyItem) {


                    $sheet->setCellValue('B' . $highestRow, $penaltyItem->id);
                    $sheet->setCellValue('C' . $highestRow, Carbon::createFromFormat('Y-m-d', $penaltyItem->overdue_date)->format('d.m.Y')); // дата +1 день, т.к. просрочка наступает через день после образования задолжности
                    $sheet->setCellValue('D' . $highestRow, $penaltyItem->overdue_sum);

                    $sheet->setCellValue('E' . $highestRow,  $penaltyItem->date_payment
                        ? Carbon::parse($penaltyItem->date_payment)->format('d.m.Y')
                        : '');

                    $sheet->setCellValue('F' . $highestRow, $penaltyItem->overdue_days);

                    $penalty_percent = '-';
                    switch (optional($penaltyItem->Instalment)->penalty_type) {
                        case 4:
                        case 1:
                            $penalty_percent = optional($penaltyItem->Instalment)->penalty_value;
                            break;
                        case 2:
                            $penalty_percent = "1/360  ставки рефинансирования";
                            break;
                        case 3:
                            $penalty_percent = "1/300  ставки рефинансирования";
                            break;
                    }

                    $sheet->setCellValue('G' . $highestRow, $penalty_percent);
                    // $sheet->getColumnDimension('G')->setWidth(17);
                    $sheet->getStyle('G' . $highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                    $sheet->setCellValue('H' . $highestRow, $penaltyItem->penalty_sum);

                    $totalPenaltySum += $penaltyItem->penalty_sum;//для итого

                    $sheet->mergeCells('H' . $highestRow . ':K' . $highestRow);
                    $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);
                    $highestRow++;
                }


                $sheet->setCellValue('G' . $highestRow, 'Итого')->getStyle('G' . $highestRow)->getFont()->setBold(true);
                $sheet->mergeCells('H' . $highestRow . ':K' . $highestRow);
                $sheet->setCellValue('H' . $highestRow, $totalPenaltySum)->getStyle('H' . $highestRow)->getFont()->setBold(true);
                $sheet->getStyle('H' . $highestRow . ':K' . $highestRow)->applyFromArray($style);


                //5-я таблица
                $highestRow = $highestRow + 2;

                //Начисленные пени (перерасчет)
                $sheet->setCellValue('B' . $highestRow, 'Начисленные пени (перерасчет)')->getStyle('B' . $highestRow)->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('B' . $highestRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0');
                $sheet->mergeCells('B' . $highestRow . ':K' . $highestRow);

                $highestRow = $highestRow + 2;

                //шапка таблицы
                $sheet->setCellValue('B' . $highestRow, '№')->getStyle('B' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('C' . $highestRow, 'Дата начисления')->getStyle("C" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('D' . $highestRow, 'Начислено пени, руб.')->getStyle("D" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('E' . $highestRow, 'Примечание')->getStyle("E" . $highestRow)->getFont()->setBold(true);
                $sheet->mergeCells('E' . $highestRow . ':K' . $highestRow);
                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);

                //ширина столбцов
                $sheet->getColumnDimension('B')->setWidth(3);
                $sheet->getColumnDimension('C')->setWidth(17);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(30);

                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $penalty = Penalty::has('PenaltyCorrection')
                    ->select(DB::raw(
                        "

                        penalty_date,
                        penalty_sum,
                        comments

                        "))
                    ->where('penalty.lead_id', '=', $lead_id)
                    ->orderBy('penalty_date', 'ASC')
                    ->get();

                $highestRow = $highestRow + 1;
                foreach ($penalty as $num => $penaltyItem) {
                    $sheet->setCellValue('B' . $highestRow, $num + 1);
                    $sheet->setCellValue('C' . $highestRow, Carbon::createFromFormat('Y-m-d', $penaltyItem->penalty_date)->format('d.m.Y'));
                    $sheet->setCellValue('D' . $highestRow, $penaltyItem->penalty_sum);
                    $sheet->setCellValue('E' . $highestRow, $penaltyItem->comments);
                    $sheet->mergeCells('E' . $highestRow . ':K' . $highestRow);
                    $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);
                    $highestRow++;
                }


                //6-я таблица Оплаченные пени
                $highestRow = $highestRow + 2;

                //Начисленные пени (перерасчет)
                $sheet->setCellValue('B' . $highestRow, 'Оплаченные пени')->getStyle('B' . $highestRow)->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('B' . $highestRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0');
                $sheet->mergeCells('B' . $highestRow . ':K' . $highestRow);

                $highestRow = $highestRow + 2;

                //шапка таблицы
                $sheet->setCellValue('B' . $highestRow, '№')->getStyle('B' . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('C' . $highestRow, 'Дата')->getStyle("C" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('D' . $highestRow, 'Сумма оплаты')->getStyle("D" . $highestRow)->getFont()->setBold(true);
                $sheet->setCellValue('E' . $highestRow, 'Примечание')->getStyle("E" . $highestRow)->getFont()->setBold(true);
                $sheet->mergeCells('E' . $highestRow . ':K' . $highestRow);
                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);

                //ширина столбцов
                $sheet->getColumnDimension('B')->setWidth(3);
                $sheet->getColumnDimension('C')->setWidth(17);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(30);

                $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $penalty = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                        incomDate as income_date,
                        sum as income_sum,
                        PaymentTargets.name as payment_target
                        "))
                    ->leftJoin('PaymentTargets', 'PaymentTargets.id', '=', 'IncomPays.payment_target')
                    ->where('contractNumber', '=', $lead_params->contract_number)
                    ->where('incomDate', '<=', Carbon::now()->format('Y-m-d'))
                    ->whereIn('payment_target', Penalty::$penalty_payment_target )
                    ->orderBy('income_date', 'ASC')
                    ->get();


                $highestRow = $highestRow + 1;
                foreach ($penalty as $num => $penaltyItem) {
                    $sheet->setCellValue('B' . $highestRow, $num + 1);
                    $sheet->setCellValue('C' . $highestRow, Carbon::createFromFormat('Y-m-d', $penaltyItem->income_date)->format('d.m.Y'));
                    $sheet->setCellValue('D' . $highestRow, $penaltyItem->income_sum);
                    $sheet->setCellValue('E' . $highestRow, $penaltyItem->payment_target);
                    $sheet->mergeCells('E' . $highestRow . ':K' . $highestRow);
                    $sheet->getStyle('B' . $highestRow . ':K' . $highestRow)->applyFromArray($style);
                    $highestRow++;
                }


                //Сформировать файл и скачать
                $writer = new Xlsx($spreadsheet);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="abn_report.xlsx"');
                $writer->save("php://output");


            }


        } catch (Exception $e) {

            return $this->apiReply(true, 'Ошибка:' . $e->getMessage());
        }

    }


}



