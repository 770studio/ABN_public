<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Console\Commands\MassPaymentScheduleimport;
use App\Lead;
use App\Schedule;
use App\Instalment;
use App\PaymentsSchedule;


class MPSimport extends MassPaymentScheduleimport
{

}

class MassPaymentScheduleimportTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
return;

        $mscmd = new MPSimport;
//        $mscmd->parseFile('38.xlsx');





        $filePath = '38.xlsx';


        //запускаем ридер
        $reader = new Xlsx();
        $spreadsheet = $reader->load( storage_path($filePath) );

        //получаем данные
        $dataAll = $spreadsheet->getActiveSheet()->toArray();

        if(!$dataAll) throw new \Exception('Не парсится excel');
        $contract_number = null;
        if($dataAll[0][9] == "номер договора:") {
            $contract_number = $dataAll[0][10];

        }

        if(!$contract_number) {
            throw new \Exception('Нет номера договора');
        }

        $lead = Lead::where('contract_number', $contract_number);


        if(!$lead || !$lead->count() ) throw new \Exception('Нет сделки');
        if($lead->count() != 1 ) throw new \Exception('Дупли по сделке');


        $lead_id = $lead->first()->lead_id;

        $contract =  Instalment::find( $lead_id );


        if(!$contract) throw new \Exception('договора нет, договор рассрочки должен существовать'  );

        //убираем лишнее до 21 строки
        $data =  array_slice($dataAll, 20);


        $Schedule = [];  $sum_inst = 0; $total = 0;

        foreach($data as $n=>$row ) {



            if($row[1] == "ИТОГО:") {

                //dd($row[1], $total, self::_toDouble( $row[5] ));
                if( $total !=  $mscmd::_toDouble( $row[5] ) )  {
                    dd($total , $mscmd::_toDouble( $row[5] ),  $total ==  $mscmd::_toDouble( $row[5] ) ,  $mscmd::_toDouble( $total ) ==  $mscmd::_toDouble( $row[5] )  );
                    throw new Exception('суммы не бьются'  );
                }
                break;
            } else {


                if($n == 0 ) { $sum_inst = $mscmd::_toDouble( $row[2] ); }

                $total+= $mscmd::_toDouble( $row[5] );

                array_push ($Schedule, [
                    'n' => $n+1,
                    'lead_id' =>  $lead_id,
                    'payment_date' =>  $mscmd::_toMysqlDate( $row[1] )   ,
                    'sum_prs' => $mscmd::_toDouble( $row[4] ),
                    'sum_payment' =>  $mscmd::_toDouble( $row[3] ),
                    'sum_total' =>  $mscmd::_toDouble( $row[5] ),
                    'total_payings' =>  $mscmd::_toDouble( $row[2] ),

                ]);




            }
        }



        dd($Schedule);

        dd($contract, $total, $sum_inst,  $Schedule  );





    }
}
