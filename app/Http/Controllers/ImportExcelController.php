<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\CriticalException;
use App\Lead;
use App\Schedule;
use  App\Instalment;
use App\PaymentsSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportExcelController extends Controller
{


    public function parseFile(Request $request){


        $file = $_FILES['file'];

        $filePath = $file["tmp_name"];




        try {


            if(! $lead_id = (int)$request->input('lead_id')) throw new Exception('нет lead_id'   );
            if(! $bailout = (int)$request->input('bailout')) throw new Exception('нет bailout'   );

            if(Auth::user()->roles->first()->IsAbleToEditSchedule() == 'no') {
                // доступ кроме статуса продано (142)
                if( Lead::find( $lead_id )->stage == 142 )
                    throw new Exception('Статус 142. Доступ запрещен.');



            }


            #TODO bailout_statuses по графику и пеням завести в новюу модель-таблицу


            $contract =  Instalment::find( $lead_id );
            if(!$contract) throw new Exception('договора нет'  );
            // при импорте график должен уже существовать
           // if(!$contract->Schedule) throw new Exception('создайте график платежей'  );

            /*
             * 4)Насчет кнопки Импортировать. Ее надо сделать доступной также на этапе первичного расчета графика, а не только на основании досрочного погашения. Саязано с тем, что если в оплате участвует мат.капитал, а он приходит только месяца через 3, это нужно указать в графике сразу при подписании договора (как частичное досрочное погашение маткапитал не проходит). Соответственно, это не стандартный случай расчета и его будут импортировать их икселя.
             */


            if (!$file['error'] && $file["type"] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ){
                if(File::exists($filePath)){
                    //dd('файл есть');

                    //запускаем ридер
                    $reader = new Xlsx();
                    $spreadsheet = $reader->load($filePath);

                    //получаем данные
                    $dataAll = $spreadsheet->getActiveSheet()->toArray();

                    //убираем лишнее до 21 строки
                    $data =  array_slice($dataAll, 20);


                    $Schedule = [];  $sum_inst = 0; $total = 0;

                    foreach($data as $n=>$row ) {



                        if($row[1] == "ИТОГО:") {
                            if( self::_toDouble( $total ) !=  self::_toDouble( $row[5] ) )  throw new Exception('суммы не бьются'  );
                            break;
                        } else {


                            if($n == 0 ) { $sum_inst = self::_toDouble( $row[2] ); }

                            $total+= self::_toDouble( $row[5] );

                            array_push ($Schedule, [
                                'n' => $n+1,
                                'lead_id' =>  $contract->lead_id,
                                'payment_date' =>  self::_toMysqlDate( $row[1] )   ,
                                'sum_prs' => self::_toDouble( $row[4] ),
                                'sum_payment' =>  self::_toDouble( $row[3] ),
                                'sum_total' =>  self::_toDouble( $row[5] ),
                                'total_payings' =>  self::_toDouble( $row[2] ),

                            ]);




                        }
                    }


                    DB::transaction(function () use ( $contract, $total, $sum_inst,  $Schedule , $bailout)  {
                        // обновляем сумуу рассрочки и общую
                        $contract->update(  ['instalment_sum' => $sum_inst,  'total_sum' =>$total, 'bailout'=> $bailout
                            ]  );
                        $contract->increment('schedule_updated')   ;  // 'schedule_updated' => (int)$contract->schedule_updated + 1
                        // обновить
                        $contract = Instalment::find($contract->lead_id);

                        // удаляем старый график

                        if( $contract->Schedule) {
                            // сохранить для истории
                            \App\ScheduleHistory::create(['event' => 'import', 'dump'=> serialize( $contract->Schedule->toArray() ), 'lead_id'=> $contract->lead_id,   'bailout'=> $bailout, 'employee_id'=>  Auth::user()->id  ]);
                            $contract->Schedule()->delete();
                        }

                       // Schedule::where('lead_id', $contract->lead_id)->delete();


                        $contract->Schedule()->insert($Schedule);

                    });


                    return $this->apiReply(false, null,
                        $contract->Lead->More()
                    );










                }
                else {
                    throw new Exception('файла нет'   );
                }
            }
            else{
                throw new Exception('нужен файл xlsx'   );
            }

            return view('payments');




        }
            catch(\Exception $e)
        {
            // var_dump();
            return $this->apiReply(true, 'Невозможно сохранить данные в базу:' . $e->getMessage() );
        }





    }

    public static function checksum ($Schedule, $row)
    {
        return true;
    }

}
