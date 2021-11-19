<?php

namespace App\Http\Controllers\Admin;

use App\ABNClient;
use App\ABNCompany;
use App\IncomPays;
use App\Http\Controllers\Controller;
use App\LeadParams;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


// управление сторонними платежами

class IncomePaysController extends Controller
{
    public function index(){

        $payments = IncomPays::where('payment_target',20)->paginate(20); ////target 20 в таблице PaymentTargets

        return view('admin.income_pays.index',['payments'=>$payments]);
    }

    public function create(){
        return view('admin.income_pays.create');
    }

    public function store(Request $request){
        $rules =  [

            'docDate' => 'required',
            'incomNumber' => 'required',
            'incomDate'=>'required',
            'sum'=>'required',
            'contractNumber'=>'required',
            'payPurpose'=>'required',

        ];
        $this->validate($request, $rules);

        $lead_params = LeadParams::where('contract_number','=',$request->contractNumber)->first();

        if (!$lead_params){
            return redirect()->back()->with('status', 'Сделка не найдена');
        }


        $client = ABNClient::where('contact_id','=',$lead_params->client_id)->first();

        if(!$client){
            return redirect()->back()->with('status', 'Клиент не найден');
        }

        if($client->company_id){
            $company = ABNCompany::where('company_id','=',$client->company_id)->first();
            if($company){
                $companyInn = $company->inn;
            }
            else{
                $companyInn = '';
            }
        }
        else{
            $companyInn = '';
        }




        $payment = new IncomPays();

        $payment->id = Str::random(36);
        $payment->processed = 1; // 1 Леша
        $payment->customerINN = $client->inn;
        $payment->customerName = $client->name;
        $payment->orgINN = $companyInn;
        $payment->docDate = $request->docDate;
        $payment->incomNumber = $request->incomNumber;
        $payment->incomDate = $request->incomDate;
        $payment->sum = $request->sum;
        $payment->sumDoc = $lead_params->contract_sum; //sumDoc это сумма договора. Есть возможность подтягивать её автоматически по lead_id или contractNumber? Артем
        $payment->contractNumber = $request->contractNumber;
        $payment->contractDate = $lead_params->contract_date;
        $payment->payPurpose = $request->payPurpose;
        $payment->lead_id = $lead_params->lead_id;
        $payment->payment_target = 20;//target 20 в таблице PaymentTargets
        $payment->manual_payment = 1;//ручное добавление(признак) #RZ 1.06.2021


        $payment->processed2 = 0; //0 Илья
        $payment->processed3 = 0; //0 Илья

        $payment->save();

        return redirect()->route('income_pays_payments.index')->with('ok', 'Новая запись добавлена');

    }

    public function edit($id)
    {
        $payment = IncomPays::findOrFail($id);
        return view('admin.income_pays.edit', ['payment' => $payment]);
    }

    public function update(Request $request, $id)
    {

        $payment = IncomPays::findOrFail($id);

        $rules =  [

            'docDate' => 'required',
            'incomNumber' => 'required',
            'incomDate'=>'required',
            'sum'=>'required',
            'contractNumber'=>'required',
            'payPurpose'=>'required',

        ];
        $this->validate($request, $rules);

        $payment->update($request->all());



        return redirect()->route('income_pays_payments.index')->with('ok', 'Запись изменена');
    }

    public function destroy($id)
    {
        $payment = IncomPays::findOrFail($id);
        $payment->forceDelete();
        return redirect()->route('income_pays_payments.index')->with('ok', 'Запись удалена');
    }
}
