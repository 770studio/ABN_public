<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;
class ForecastController extends Controller
{
    public function index(){
        return view('reports.forecast.index');
    }

    public function makeReport(Request $request){

        if ($request->get('year') && $request->get('month') && $request->get('singleDate') == null){


            $year = $request->get('year');
            $month = $request->get('month');

            $monthQuery = $month;
            $lastDayInMonth = date("t", strtotime($year.'-'.$month));

            $dt = Carbon::createFromFormat('d.m.Y', $lastDayInMonth.'.'.$month.'.'.$year )->format('Y-m-d');

            switch ($month) {
                case 1:
                    $month =  "Январь";
                    break;
                case 2:
                    $month = "Февраль";
                    break;
                case 3:
                    $month = "Март";
                    break;
                case 4:
                    $month = "Апрель";
                    break;
                case 5:
                    $month =  "Май";
                    break;
                case 6:
                    $month = "Июнь";
                    break;
                case 7:
                    $month = "Июль";
                    break;
                case 8:
                    $month = "Август";
                    break;
                case 9:
                    $month =  "Сентябрь";
                    break;
                case 10:
                    $month = "Октябрь";
                    break;
                case 11:
                    $month = "Ноябрь";
                    break;
                case 12:
                    $month = "Декабрь";
                    break;
            }


        }
        elseif($request->get('year') && $request->get('month') && $request->get('singleDate') != null){

            $year = $request->get('year');
            $month = $request->get('month');
            $monthQuery = $month;
            $date = $request->get('singleDate');

            $dt = Carbon::createFromFormat('d.m.Y', $date)->format('Y-m-d');


            if (strlen($month)==1){
                $month = '0'.$month;
            }

        }
        else{
            return redirect()->back()->with('status','Не выбран период');
        }



        $dataLeadsWithPayments = DB::table('payment_shedule')

            ->join('lead_params','lead_params.lead_id','=','payment_shedule.lead_id')
            ->join('object_params','object_params.object_id','=','lead_params.object_id')
            ->join('object_types','object_types.type_id','=','object_params.type_id')
            ->whereMonth('payment_shedule.payment_date','=',$monthQuery)
            ->whereYear('payment_shedule.payment_date','=',$year)
            ->whereNotNull('payment_shedule.lead_id')
            ->where('lead_params.pipeline_id','!=',1878445)
            ->distinct()
            ->get()
            ->groupBy('class_property');






        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет по прогнозу поступлений');

        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);


        //заголовок
        $sheet->setCellValue('A1', 'Отчет по прогнозу поступлений')->getStyle("A1")->getFont()->setSize(16);
        $sheet->mergeCells('A1:F1');
        //Период
        if (isset($month)){
            $sheet->setCellValue('A2', 'Отчетный период: ' .$month.' '.$year )->getStyle("A2")->getFont()->setSize(16);

        }

        if (isset($date)){
            $sheet->setCellValue('A2', 'Отчетный период: 01.'.$month.'.'.$year.'-' . $date )->getStyle("A2")->getFont()->setSize(16);

        }
        $sheet->mergeCells('A2:F2'); //8

        //шапка таблицы
        $sheet->setCellValue('A4', 'Наименование статьи')->getStyle("A4")->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->setCellValue('B4', '8')->getStyle("B4")->getFont()->setBold(true);
        $sheet->setCellValue('C4', '15')->getStyle("C4")->getFont()->setBold(true);
        $sheet->setCellValue('D4', '22')->getStyle("D4")->getFont()->setBold(true);
        $sheet->setCellValue('E4', '30/31')->getStyle("E4")->getFont()->setBold(true);
        $sheet->setCellValue('F4', 'Произвольная дата')->getStyle("F4")->getFont()->setBold(true);

        $sheet->setCellValue('G4', 'Итого')->getStyle("G4")->getFont()->setBold(true);

        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);

        $sheet->getStyle('A4:G4')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        $highestRow = 6;
        foreach ($dataLeadsWithPayments as $classProperty=>$dataByClassProperty){
            switch ($classProperty) {
                case 'pervichka':
                    $classPropertyTitle =  "Квартиры";
                    break;
                case 'parking':
                    $classPropertyTitle = "Парковки";
                    break;
                case 'commercial':
                    $classPropertyTitle = "Офисы";
                    break;
                case 'pantry':
                    $classPropertyTitle = "Кладовки";
                    break;

            }
            $sheet->setCellValue('A'.$highestRow, $classPropertyTitle)->getStyle('A'.$highestRow)->getFont()->setBold(true);
            $sheet->mergeCells('A'.$highestRow.':G'.$highestRow);


            foreach ($dataByClassProperty->groupBy('complex') as $complex=>$dataByComplex){

                $dataByComplexLeadIds = $dataByComplex->pluck('lead_id');


                $complex8 = 0;
                $complex15 = 0;
                $complex22 = 0;
                $complex30 = 0;
                $complex_randomDate = 0;


                foreach ($dataByComplexLeadIds as $lead_id){

                    $complexPayment8 = 0;
                    $complexPayment15 = 0;
                    $complexPayment22 = 0;
                    $complexPayment30 = 0;
                    $complexPaymentRandomDate = 0;

                    $complexIncome8 = 0;
                    $complexIncome15 = 0;
                    $complexIncome22 = 0;
                    $complexIncome30 = 0;
                    $complexIncome_randomDate = 0;

                    $payments = DB::table('payment_shedule')
                        ->select(DB::raw(
                            "
                    payment_shedule.lead_id,
                    payment_shedule.sum_payment,
                    payment_shedule.sum_total,
                    payment_shedule.payment_date

                "))

                        ->where('payment_shedule.lead_id','=',$lead_id)
                        ->whereMonth('payment_shedule.payment_date','<=',$monthQuery)
                        ->whereYear('payment_shedule.payment_date','<=',$year)
                        ->get();

                    $incomes = DB::table('IncomPays')
                        ->select(DB::raw(
                            "
                    IncomPays.lead_id,
                    IncomPays.sum,
                    IncomPays.incomDate

                "))
                        ->where('IncomPays.lead_id','=',$lead_id)
                        ->get();


                    foreach ($payments as $payment){
                        $payment_date_day = Carbon::createFromFormat('Y-m-d', $payment->payment_date)->format('d');

                        //произвольная дата

                        if ($payment->sum_total != null){
                            $complexPaymentRandomDate += $payment->sum_total;
                        }
                        else{
                            $complexPaymentRandomDate+= $payment->sum_payment;
                        }


                        if ($payment_date_day <=8){
                            if ($payment->sum_total != null){
                                $complexPayment8 += $payment->sum_total;
                            }
                            else{
                                $complexPayment8+= $payment->sum_payment;
                            }

                        }

                        if ($payment_date_day >8 && $payment_date_day<= 15){
                            if ($payment->sum_total != null){
                                $complexPayment15 += $payment->sum_total;
                            }
                            else{
                                $complexPayment15+= $payment->sum_payment;
                            }

                        }

                        if ($payment_date_day >15 && $payment_date_day<= 22){
                            if ($payment->sum_total != null){
                                $complexPayment22 += $payment->sum_total;
                            }
                            else{
                                $complexPayment22+= $payment->sum_payment;
                            }

                        }

                        if ($payment_date_day >22){
                            if ($payment->sum_total != null){
                                $complexPayment30 += $payment->sum_total;
                            }
                            else{
                                $complexPayment30+= $payment->sum_payment;
                            }

                        }

                    }
                    foreach ($incomes as $income){
                        $income_date_day = Carbon::createFromFormat('Y-m-d', $income->incomDate)->format('d');

                        //произвольная дата


                        $complexIncome_randomDate+= $income->sum;



                        if ($income_date_day <=8){

                            $complexIncome8+=  $income->sum;

                        }

                        if ($income_date_day >8 && $income_date_day<= 15){

                            $complexIncome15+= $income->sum;


                        }

                        if ($income_date_day >15 && $income_date_day<= 22){

                            $complexIncome22+= $income->sum;

                        }

                        if ($income_date_day >22){

                            $complexIncome30+= $income->sum;

                        }

                    }



                    $complexPayment8 = $complexPayment8 - $complexIncome8;
                    $complexPayment15 = $complexPayment15 - $complexIncome15;
                    $complexPayment22 = $complexPayment22 - $complexIncome22;
                    $complexPayment30 = $complexPayment30 - $complexIncome30;
                    $complexPaymentRandomDate = $complexPaymentRandomDate - $complexIncome_randomDate;


                    $complex8 += $complexPayment8;
                    $complex15 += $complexPayment15;
                    $complex22 += $complexPayment22;
                    $complex30 += $complexPayment30;
                    $complex_randomDate += $complexPaymentRandomDate;

                }


                $highestRow = $highestRow+1;
                $sheet->setCellValue('A'.$highestRow, $complex);

                $sheet->setCellValue('B'.$highestRow, abs($complex8));
                if (abs($complex8) != 0){
                    $sheet->getStyle('B'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->setCellValue('C'.$highestRow, abs($complex15));
                if (abs($complex15) != 0){
                    $sheet->getStyle('C'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->setCellValue('D'.$highestRow,  abs($complex22));
                if ( abs($complex22) != 0){
                    $sheet->getStyle('D'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->setCellValue('E'.$highestRow,  abs($complex30));
                if ( abs($complex30) != 0){
                    $sheet->getStyle('E'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }

                if ($request->get('singleDate') != null){
                    $sheet->setCellValue('F'.$highestRow,  abs($complex_randomDate));
                    $sheet->getStyle('F'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                else{
                    $sheet->setCellValue('F'.$highestRow, 'нет');
                }

                $sheet->setCellValue('G'.$highestRow, '=SUM(B'.$highestRow.':E'.$highestRow.')');
                $sheet->getStyle('G'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');




            }


            $highestRow++;
        }


        //верхняя строка всего
        $sheet->setCellValue('B5', '=SUM(B6:B'.$highestRow.')');
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('### ### ### ###');

        $sheet->setCellValue('C5', '=SUM(C6:C'.$highestRow.')');
        $sheet->getStyle('C5')->getNumberFormat()->setFormatCode('### ### ### ###');

        $sheet->setCellValue('D5', '=SUM(D6:D'.$highestRow.')');
        $sheet->getStyle('D5')->getNumberFormat()->setFormatCode('### ### ### ###');

        $sheet->setCellValue('E5', '=SUM(E6:E'.$highestRow.')');
        $sheet->getStyle('E5')->getNumberFormat()->setFormatCode('### ### ### ###');

        $sheet->setCellValue('F5', '=SUM(F6:F'.$highestRow.')');
        $sheet->getStyle('F5')->getNumberFormat()->setFormatCode('### ### ### ###');

        $sheet->setCellValue('G5', '=SUM(G6:G'.$highestRow.')');
        $sheet->getStyle('G5')->getNumberFormat()->setFormatCode('### ### ### ###');





        //стиль обводки
        $styleBorderThin = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'inside'=>[
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ]
            ],

        ];


        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A4:G'.$highestRow)->applyFromArray($styleBorderThin);
        $sheet->getStyle('A5:A'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_forecast.xlsx"');
        $writer->save("php://output");

    }
}
