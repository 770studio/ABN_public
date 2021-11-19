<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Coefficient;

class SubAgentsController extends Controller
{
    public function index(Request $request){

        $now = Carbon::now();
        $request->session()->put([
            'df'=>$now->startOfMonth(),
            'dt'=>$now->endOfMonth(),
        ]);

        $subagents =  DB::table('lead_params')
            ->select(DB::raw(
                "
                lead_params.subagent_name

                "))
            ->whereNotNull('subagent_name')
            ->distinct()
            ->orderBy('lead_params.subagent_name','asc')
            ->pluck('subagent_name');

        foreach ($subagents as $key=>$name){
            $subagentsArr[$name] = $name;
        }

        return view('reports.subagents.index',['subagents'=>$subagentsArr]);
    }

    public function makeReport(Request $request){

        if ($request->get('dateRange')){

            $date = $request->get('dateRange');
            $dateArr = explode('-',$date);
            $dateFrom = str_replace(' ', '', $dateArr['0']);
            $dateTo = str_replace(' ', '', $dateArr['1']);

            $df = Carbon::createFromFormat('d.m.Y', $dateFrom);
            $dt = Carbon::createFromFormat('d.m.Y', $dateTo  );

            $request->session()->put([
                'date'=>$date,
                'df'=>$df,
                'dt'=>$dt
            ]);

        }
        else{
            return redirect()->back()->with('status','Не выбран период');

        }

        if ($request->get('subagents')){

            $subagents = $request->get('subagents');
            $request->session()->put([
                'subagents'=>$subagents
            ]);
        }
        else{
            return redirect()->back()->with('status','Не выбраны субагенты');
        }






        $data = DB::table('IncomPays')
            ->select(DB::raw(
                "
                lead_params.subagent_name,
                lead_params.is_subagent,
                abned_users.user_name as manager,
                object_params.complex,
                object_params.address,
                object_params.object_number,
                object_params.rooms_number,
                object_params.total_area,
                object_params.house_number,
                contacts.name as client_name,
                IncomPays.contractNumber,
                lead_params.contract_date,
                lead_params.contract_sum,
                IncomPays.sum as income_sum,
                IncomPays.incomDate,
                IncomPays.payment_target
                "))
            ->whereNotNull('IncomPays.contractNumber')
            ->whereNotIn('IncomPays.contractNumber',[''])
            //новое условие : Также в отчёте по субагентам не должны учитываться
            // поступления по пеням, они приходят из казначейства
            // с ID платежа "OpsI_08030000 Невыполнения условий договора купли-продажи (штрафы, пени)"
            // 4 payment_target
            ->where(function($query){
                $query->where('payment_target', '!=' ,4)
                    ->orWhereNull('payment_target');
            })



            ->join('lead_params','lead_params.contract_number','=','IncomPays.contractNumber')

            ->whereNotNull('subagent_name')

            ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
            ->join('abned_users','abned_users.id','=','lead_params.employee_id')
            ->join('contacts','contacts.contact_id','=','lead_params.client_id')



            ->where('incomDate','>=',$df)
            ->where('incomDate','<=',$dt)
            //->where('is_subagent','=',1)
            ->whereIn('subagent_name',$subagents)
            ->orderBy('lead_params.subagent_name','asc')
            ->get();

        //dd($data->groupBy('contractNumber'));

        $data = $data->groupBy('contractNumber');

        //dd($data);

        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет по субагентам');

        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);

        //заголовок
        $sheet->setCellValue('B1', 'Отчет по субагентам')->getStyle("B1")->getFont()->setSize(16);

        //Период
        $sheet->setCellValue('B2', 'Отчетный период: ' .$date )->getStyle("B2")->getFont()->setSize(16);

        //ширина колонок
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->getColumnDimension('P')->setAutoSize(true);
//        //авторазмер
//        foreach(range('A','N') as $columnID) {
//            $sheet->getColumnDimension($columnID)
//                ->setAutoSize(true);
//        }
        //шапка таблицы
        $sheet->setCellValue('A4', 'п/п');
        $sheet->setCellValue('B4', 'Субагент');
        $sheet->setCellValue('C4', 'Стр. № дома');
        $sheet->setCellValue('D4', '№ договора');
        $sheet->setCellValue('E4', 'Дата заключения договора');
        $sheet->setCellValue('F4', '№ кв-ры');
        $sheet->setCellValue('G4', 'Кол-во комнат');
        $sheet->setCellValue('H4', 'S, кв.м.');
        $sheet->setCellValue('I4', 'Покупатель');
        $sheet->setCellValue('J4', 'Стоимость по договору КП, руб.');
        $sheet->setCellValue('K4', 'Дата поступления денежных средств');
        $sheet->setCellValue('L4', 'Поступление нарастающим, руб.');
        $sheet->setCellValue('M4', 'Поступление за отчетный период, руб.');
        $sheet->setCellValue('N4', 'Задолженность');
        $sheet->setCellValue('O4', 'Коэффициент (%)');
        $sheet->setCellValue('P4', 'Вознаграждение');

        //Стили
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



        //1-я колонка с номером
        //$sheet->getColumnDimension('A')->setWidth(10);

        //формат даты
        $spreadsheet->getActiveSheet()->getStyle('E')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY
            );

        //фомат цифровой у сумм
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('K')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('L')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('P')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);




        $sheet->getStyle('A4:O4')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        //начало динамических данных
        $highestRow = 5;
        $num = 1;
        $sum_contract_sum = 0;
        $sum_income_sum = 0;



        foreach ($data as $contractNum=>$valuesArr){

            //берем первый договор(строку) из группы
            $firstData = $valuesArr->first();


            /////новые столбцы
            //месяц заключения договора
//            $ContractDate = Carbon::createFromFormat('Y-m-d', $firstData->contract_date);
//            $month = $ContractDate->format("n");
//
//
//            $year = $ContractDate->format("Y");
//            //договоры , заключенные в этом месяце
//            $contractsByMonth = DB::table('lead_params')
//                ->where('subagent_name','=',$firstData->subagent_name)
//                ->whereYear('contract_date','=',$year)
//                ->whereMonth('contract_date','=',$month)
//                ->get()
//                ->count();
//
//
////            if ($contractsByMonth == 1 || $contractsByMonth == 2){
////                $coef = 0.01;
////            }
////            if ($contractsByMonth == 3 || $contractsByMonth == 4 ){
////                $coef = 0.015;
////
////            }
////            if ($contractsByMonth >= 5){
////                $coef = 0.02;
////            }
//
//            $max_contracts_count = Coefficient::max('contracts_count');
//
//            //после 1 октября 2019
//            if($month >= 10 && $year>=2019){
//                $coef = 0.02;
//            }
//            //до 1 октября 2019
//            else{
//
//
//                if ($contractsByMonth >= $max_contracts_count){
//                    $coef = Coefficient::where('contracts_count',$max_contracts_count)->first()->coefficient;
//                }
//                else{
//                    $coef = Coefficient::where('contracts_count',$contractsByMonth)->first()->coefficient;
//                }
//            }
//





            //сумма итого по таблице
            $sum_contract_sum+=$firstData->contract_sum;


            $sheet->setCellValue('A'.$highestRow,$num++);
            $sheet->getStyle('A'.$highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('B'.$highestRow,$firstData->subagent_name);
            $sheet->setCellValue('C'.$highestRow,$firstData->house_number);
            $sheet->setCellValue('D'.$highestRow,$contractNum);
            $sheet->setCellValue('E'.$highestRow,Carbon::createFromFormat('Y-m-d',$firstData->contract_date)->format('d.m.Y'));
            $sheet->setCellValue('F'.$highestRow,$firstData->object_number);
            $sheet->setCellValue('G'.$highestRow,$firstData->rooms_number);
            $sheet->setCellValue('H'.$highestRow,$firstData->total_area);
            $sheet->setCellValue('I'.$highestRow,$firstData->client_name);
            $sheet->setCellValue('J'.$highestRow,$firstData->contract_sum ); //сумма договора


            //сумма поступлений по каждому договору
            $incomeSumArr = null;
            $sum_income_sum_row = 0;

//            //Поступление за отчетный период, руб. //новое тз
//            $incomeSumCurrentMonth = 0;

            foreach ($valuesArr as $value){
                $incomeSumArr[] = number_format($value->income_sum, 2, ',', ' '). ' от '. date_create_from_format('Y-m-d',$value->incomDate)->format('d.m.Y');
                $incomeSumStr = implode(", ", $incomeSumArr);
                //сумма итого поступлений по таблице
                $sum_income_sum+= $value->income_sum;
                $sum_income_sum_row+=$value->income_sum;
            }

            $sheet->setCellValue('K'.$highestRow, $incomeSumStr);

            //Поступление нарастающим, руб.

            $incomesTotal = DB::table('IncomPays')
                    ->where('contractNumber','=',$contractNum)
                    ->where(function($query){
                        $query->where('payment_target', '!=' ,4)
                            ->orWhereNull('payment_target');
                    })
                    ->sum('sum');




            $sheet->setCellValue('L'.$highestRow,$incomesTotal); //Поступление нарастающим, руб.
            $sheet->setCellValue('M'.$highestRow,$sum_income_sum_row); //Поступление за отчетный период, руб.

            $debt = $firstData->contract_sum - $incomesTotal;


            if ($debt >0 ){
                $sheet->getStyle('N'.$highestRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->setCellValue('N'.$highestRow,$debt); //Задолженность
            }
            else{
                $sheet->setCellValue('N'.$highestRow,'нет'); //Задолженность 0 или отрицательная
            }

            //старый расчет кефов
            //после 1 октября 2019
//            if($month >= '10' && $year>='2019'){
//                $sheet->setCellValue('O'.$highestRow, $coef*100);
//
//                $rewardSum = $coef*$sum_income_sum_row;
//
//                $sheet->setCellValue('P'.$highestRow, $rewardSum);
//            }
//            //до 1 октября 2019
//            else{
//                //если однокомнатные
//                if ($firstData->rooms_number == '1'){
//                    $sheet->setCellValue('O'.$highestRow, '');
//                    //сумма вознаграждения 1000руб с кв м
//                    $rewardSum = $firstData->total_area*1000;
//                    $sheet->setCellValue('P'.$highestRow, $rewardSum);
//                }
//                else{
//                    $sheet->setCellValue('O'.$highestRow, $coef*100);
//
//                    $rewardSum = $coef*$sum_income_sum_row;
//
//                    $sheet->setCellValue('P'.$highestRow, $rewardSum);
//                }
//            }


            //TODO: уточнить нвую логику по кефам
//                $coef = 2;
//                $rewardSum = 0.02*$sum_income_sum_row;
                $coef = 1;
                $rewardSum = 0.01*$sum_income_sum_row;
            $sheet->setCellValue('O'.$highestRow, $coef);
            $sheet->setCellValue('P'.$highestRow, $rewardSum);


            $sheet->getStyle('A4:P'.$highestRow)->applyFromArray($styleBorderThin);

            $highestRow++;
        }


        $sheet->setCellValue('I'.$highestRow,"Итого:" )->getStyle('I'.$highestRow)->getFont()->setBold(true);
        $sheet->setCellValue('J'.$highestRow,$sum_contract_sum )->getStyle('J'.$highestRow)->getFont()->setBold(true);
        $sheet->setCellValue('K'.$highestRow,$sum_income_sum)->getStyle('K'.$highestRow)->getFont()->setBold(true);

        $lastRewardRow = $highestRow-1;

        $sheet->setCellValue('P'.$highestRow,'=SUM(P10:P'.$lastRewardRow.')' )->getStyle('P'.$highestRow)->getFont()->setBold(true);


        $sheet->getStyle('A4:P'.$highestRow)->applyFromArray($styleBorderThin);
        $sheet->getStyle('A4:P'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_subagents.xlsx"');
        $writer->save("php://output");




    }
}
