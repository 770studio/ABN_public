<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Http\Controllers\Controller;

class WorkoutController extends Controller
{
    public function index(){

        $managers = DB::table('abned_users')
            ->select(DB::raw(
                "
                abned_users.*
                "))
           // ->where('flag','=',1)
            ->where('department','=','Взаимодействие с субагентами')
            ->orWhere('department','=','Отдел продаж')
            ->orWhere('department','=','Отдел коммерческой недвижимости')
            ->orWhere('id','=',2343982) //Ибнеева Кристина Вячеславовна
            ->orderBy('user_name','asc')
            ->pluck('user_name','id');



        return view('reports.workout.index',[
            'managers'=>$managers
        ]);
    }


    public function makeReport(Request $request){


       if ($request->get('month') && $request->get('year')) {


           $monthSelected = $request->get('month');
           $year =  $request->get('year');

               switch ($monthSelected) {
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
                       $month= "Август";
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






           $date = $month .' '. $year;
       }
       else{
           return redirect()->back()->with('status','Не выбран период');
       }






        if ($request->get('managers')){
            $managers = $request->get('managers');
        }
        else{
            return redirect()->back()->with('status','Не выбран сотрудник');
        }




        $managers = DB::table('abned_users')
            ->select(DB::raw(
                "
                 abned_users.user_name as name,
                 abned_users.id,
                 plans_employees.plans


                "))
            ->whereIn('abned_users.id',$managers)
            ->join('plans_employees','employee_id','=','abned_users.id')
            ->where('year',$year)
            ->orderBy('name','asc')
            ->get();





        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет по выработке менеджеров');

        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);

        //заголовок
        $sheet->setCellValue('A1', 'Отчет по выработке менеджеров')->getStyle("A1")->getFont()->setSize(16);

        //Период
        $sheet->setCellValue('B1', 'Отчетный период: ' .$date )->getStyle("B1")->getFont()->setSize(16);

        $sheet->setCellValue('A2', ' Выполнение плана' )->getStyle("A2")->getFont()->setSize(14)->setBold(true);


        //шапка таблицы
        $sheet->setCellValue('A3', 'Менеджер');
        $sheet->setCellValue('B3', 'План, руб.');
        $sheet->setCellValue('C3', 'Заключенные договоры, руб.');
        $sheet->setCellValue('D3', 'Поступления, руб.');
        $sheet->setCellValue('E3', 'Выполнение плана, %');
        $sheet->setCellValue('F3', 'Выплата вознаграждения, руб.');
        $sheet->setCellValue('G3', 'Выплата вознаграждения, руб (накопительным итогом)');


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


        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);

//        //авторазмер
        foreach(range('B','Q') as $columnID) {
//            $sheet->getColumnDimension($columnID)
//                ->setAutoSize(true);
//            $sheet->getStyle('$columnID')->getAlignment()->setWrapText(true);

            $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setWidth(20);
        }



        //фомат цифровой у сумм
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');
        //обводка шапки

        $sheet->getStyle('A3:G3')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3:G3')->applyFromArray($styleBorderThin);
        //начало динамических данных

//

        $highestRow = 4;


        foreach ($managers as $manager){
           // dd($manager);
            $sheet->setCellValue('A'.$highestRow,$manager->name)->getStyle('A'.$highestRow)->getFont()->setBold(true);
            $sheet->setCellValue('B'.$highestRow,$manager->plans);

            //запрос по договорам в месяце

            $contractsInMonth = DB::table('lead_params')
                ->select(DB::raw(
                    "

                 lead_params.contract_date,
                 lead_params.contract_sum,
                 IncomPays.sum as income_sum,
                 IncomPays.incomDate as income_date,
                 object_params.complex,
                 object_params.object_number,
                 object_params.address,
                 lead_params.contract_number,
                 object_params.rooms_number,
                 object_params.type_id,
                 object_params.illiquid,
                 lead_menegers.percent,
                 object_params.house_number,
                 lead_params.subagent_name,
                 object_types.type_name as object_type,
                 object_params.owner

                "))

                ->leftJoin('lead_menegers', function ($join) {
                    $join->on('lead_menegers.lead_id', '=', 'lead_params.lead_id')
                        ->where('lead_menegers.delete_flag', '=', 0);
                })
                ->leftJoin('IncomPays','IncomPays.contractNumber', '=', 'lead_params.contract_number')
                ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
                ->join('object_types','object_types.type_id', '=', 'object_params.type_id')

                ->where('lead_params.stage', '=',142)
                ->where('lead_params.employee_id', '=',$manager->id)
                ->whereYear('contract_date',$year)
                ->whereMonth('contract_date', '=',$monthSelected)
                ->get();


            $contractsInMonthSum = 0;

            foreach($contractsInMonth as $item){
                $contractsInMonthSum += $item->contract_sum;
            }

            $sheet->setCellValue('C'.$highestRow,$contractsInMonthSum);




            //Договора с поступлениями за месяц

            $IncomesInMonth = DB::table('lead_params')
                ->select(DB::raw(
                    "

                 lead_params.contract_date,
                 lead_params.contract_sum,
                 IncomPays.sum as income_sum,
                 IncomPays.incomDate as income_date,
                 object_params.complex,
                 object_params.object_number,
                 object_params.address,
                 lead_params.contract_number,
                 object_params.rooms_number,
                 object_params.type_id,
                 object_params.illiquid,
                 lead_menegers.percent,
                 object_params.house_number,
                 lead_params.subagent_name,
                 object_types.type_name as object_type,
                 object_params.owner

                "))

                ->leftJoin('lead_menegers', function ($join) {
                    $join->on('lead_menegers.lead_id', '=', 'lead_params.lead_id')
                        ->where('lead_menegers.delete_flag', '=', 0);
                })
                ->leftJoin('IncomPays','IncomPays.contractNumber', '=', 'lead_params.contract_number')
                ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
                ->join('object_types','object_types.type_id', '=', 'object_params.type_id')

                ->where('lead_params.stage', '=',142)
                ->where('lead_params.employee_id', '=',$manager->id)
                ->whereYear('incomDate',$year)
                ->whereMonth('incomDate', '=',$monthSelected)
                ->get();



            $incomesInMonthSum = 0;
            $incomesInMonthPercentSum = 0;
            foreach($IncomesInMonth as $item){

                $incomesInMonthSum += $item->income_sum;


                //Доля
                if($item->percent != null){
                    $percent = $item->percent;
                }
                else{
                    $percent = 1;
                }

                //расчет коэффициента
                    if ($item->rooms_number == 1){
                        $coef = 0.002;
                    }

                    if ($item->rooms_number == 2){
                        $coef = 0.006;
                    }
                    if ($item->rooms_number > 2){
                        $coef = 0.01;
                    }
                    if ($item->type_id == 1 ||$item->type_id ==5 || $item->type_id == 7 ){
                        $coef = 0.01;
                    }

                //сумма доли от суммы оплаты
                $incomesInMonthPercentSum += ($item->income_sum*$percent*$coef);

            }
            $sheet->setCellValue('D'.$highestRow,$incomesInMonthSum);
            $sheet->setCellValue('F'.$highestRow,$incomesInMonthPercentSum);



            //Договора с поступлениями нарастающим итогом

            $incomesAll = DB::table('lead_params')
                ->select(DB::raw(
                    "

                 lead_params.contract_date,
                 lead_params.contract_sum,
                 IncomPays.sum as income_sum,
                 IncomPays.incomDate as income_date,
                 object_params.complex,
                 object_params.object_number,
                 object_params.address,
                 lead_params.contract_number,
                 object_params.rooms_number,
                 object_params.type_id,
                 object_params.illiquid,
                 lead_menegers.percent,
                 object_params.house_number,
                 lead_params.subagent_name,
                 object_types.type_name as object_type,
                 object_params.owner

                "))

                ->leftJoin('lead_menegers', function ($join) {
                    $join->on('lead_menegers.lead_id', '=', 'lead_params.lead_id')
                        ->where('lead_menegers.delete_flag', '=', 0);
                })
                ->leftJoin('IncomPays','IncomPays.contractNumber', '=', 'lead_params.contract_number')
                ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
                ->join('object_types','object_types.type_id', '=', 'object_params.type_id')

                ->where('lead_params.stage', '=',142)
                ->where('lead_params.employee_id', '=',$manager->id)
                ->whereYear('incomDate','<=',$year)
                ->whereMonth('incomDate', '<=',$monthSelected)

                ->get();



            $incomesAllSum = 0;
            foreach($incomesAll as $item){


                //Доля
                if($item->percent != null){
                    $percent = $item->percent;
                }
                else{
                    $percent = 1;
                }

                //расчет коэффициента
                if ($item->rooms_number == 1){
                    $coef = 0.002;
                }

                if ($item->rooms_number == 2){
                    $coef = 0.006;
                }
                if ($item->rooms_number > 2){
                    $coef = 0.01;
                }
                if ($item->type_id == 1 ||$item->type_id ==5 || $item->type_id == 7 ){
                    $coef = 0.01;
                }

                //сумма доли от суммы оплаты итог
                $incomesAllSum += ($item->income_sum*$percent*$coef);

            }

            $sheet->setCellValue('G'.$highestRow,$incomesAllSum);

            $sheet->getStyle('A4:G'.$highestRow)->applyFromArray($styleBorderThin);
            $highestRow++;
        }







        //начало 2-й таблицы


        $titleRow = $highestRow+2;
        $sheet->setCellValue('A'.$titleRow, ' Факт по продажам' )->getStyle("A".$titleRow)->getFont()->setSize(14)->setBold(true);


        $highestRow = $highestRow+3;

        //шапка 2-й таблицы

        $sheet->setCellValue('A'.$highestRow, 'Менеджер');
        $sheet->setCellValue('B'.$highestRow, 'Комплекс');
        $sheet->setCellValue('C'.$highestRow, '№ дома');
        $sheet->setCellValue('D'.$highestRow, 'Объект недвижимости');
        $sheet->setCellValue('E'.$highestRow, 'Номер договора');
        $sheet->setCellValue('F'.$highestRow, 'Дата договора');
        $sheet->setCellValue('G'.$highestRow, 'Кол-во комнат, шт.');
        $sheet->setCellValue('H'.$highestRow, 'Сумма договора, руб.');
        $sheet->setCellValue('I'.$highestRow, 'Доля участия в договоре,%');
        $sheet->setCellValue('J'.$highestRow, 'Стоимость по долям, руб.');


        $sheet->getStyle('A'.$highestRow.':J'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A'.$highestRow.':J'.$highestRow)->applyFromArray($styleBorderThin);



        //формат суммы
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('### ### ### ###');

        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('### ### ### ###');
        //формат даты
        $spreadsheet->getActiveSheet()->getStyle('F')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY
            );

        $highestRow = $highestRow+1;
        $rowSumStart = $highestRow+1;

        foreach ($managers as $manager){
            // dd($manager);
            $sheet->setCellValue('A'.$highestRow,$manager->name)->getStyle('A'.$highestRow)->getFont()->setBold(true);
            $sheet->mergeCells('A'.$highestRow.':J'.$highestRow)->getStyle('A'.$highestRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$highestRow.':J'.$highestRow)->applyFromArray($styleBorderThin);

            //запрос по договорам в месяце

            $contractsInMonth = DB::table('lead_params')
                ->select(DB::raw(
                    "

                 lead_params.contract_date,
                 lead_params.contract_sum,

                 object_params.complex,
                 object_params.object_number,
                 object_params.address,
                 lead_params.contract_number,
                 object_params.rooms_number,
                 object_params.type_id,
                 object_params.illiquid,
                 lead_menegers.percent,
                 object_params.house_number,
                 lead_params.subagent_name,
                 object_types.type_name as object_type,
                 object_params.owner

                "))

                ->leftJoin('lead_menegers', function ($join) {
                    $join->on('lead_menegers.lead_id', '=', 'lead_params.lead_id')
                        ->where('lead_menegers.delete_flag', '=', 0);
                })
//                ->leftJoin('IncomPays','IncomPays.contractNumber', '=', 'lead_params.contract_number')
                ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
                ->join('object_types','object_types.type_id', '=', 'object_params.type_id')

                ->where('lead_params.stage', '=',142)
                ->where('lead_params.employee_id', '=',$manager->id)
                ->whereYear('contract_date',$year)
                ->whereMonth('contract_date', '=',$monthSelected)
                ->get();

//                dd($contractsInMonth);

            $highestRow = $highestRow+1;
                 foreach($contractsInMonth as $contractItem){
                    $sheet->setCellValue('B'.$highestRow,$contractItem->complex);
                    $sheet->setCellValue('C'.$highestRow,$contractItem->object_number);
                    $sheet->setCellValue('D'.$highestRow,$contractItem->address);
                    $sheet->setCellValue('E'.$highestRow,$contractItem->contract_number);
                    $sheet->setCellValue('F'.$highestRow,Carbon::createFromFormat('Y-m-d', $contractItem->contract_date)->format('d.m.Y'));
                    $sheet->setCellValue('G'.$highestRow,$contractItem->rooms_number);
                    $sheet->setCellValue('H'.$highestRow,$contractItem->contract_sum);

                     //Доля
                     if($item->percent != null){
                         $percent = $item->percent;
                     }
                     else{
                         $percent = 1;
                     }

                     $sheet->setCellValue('I'.$highestRow,$percent);

                     $sheet->setCellValue('J'.$highestRow,$percent*$contractItem->contract_sum);
                     $sheet->getStyle('A'.$highestRow.':J'.$highestRow)->applyFromArray($styleBorderThin);
                    $highestRow++;
                }
            $sheet->getStyle('A'.$highestRow.':J'.$highestRow)->applyFromArray($styleBorderThin);
            $highestRow++;
        }
        $rowSumEnd = $highestRow-1;
        $sheet->setCellValue('A'.$highestRow,'Всего по менеджерам:')->getStyle('A'.$highestRow)->getFont()->setBold(true);
        $sheet->setCellValue('H'.$highestRow,'=SUM(H'.$rowSumStart.':H'.$rowSumEnd .')')->getStyle('H'.$highestRow)->getFont()->setBold(true);
        $sheet->setCellValue('J'.$highestRow,'=SUM(J'.$rowSumStart.':J'.$rowSumEnd .')')->getStyle('J'.$highestRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$highestRow.':J'.$highestRow)->applyFromArray($styleBorderThin);


        //начало 3-й таблицы
        $titleRow = $highestRow+3;
        $sheet->setCellValue('A'.$titleRow, ' Факт по поступлениям' )->getStyle("A".$titleRow)->getFont()->setSize(14)->setBold(true);

        $highestRow = $highestRow+4;

        //шапка 3-й таблицы

        $sheet->setCellValue('A'.$highestRow, 'Менеджер');
        $sheet->setCellValue('B'.$highestRow, 'Номер договора');
        $sheet->setCellValue('C'.$highestRow, 'Дата договора');
        $sheet->setCellValue('D'.$highestRow, 'Дата поступления денежных средств');
        $sheet->setCellValue('E'.$highestRow, 'Неликвид');
        $sheet->setCellValue('F'.$highestRow, 'Тип объекта');
        $sheet->setCellValue('G'.$highestRow, 'Комплекс');
        $sheet->setCellValue('H'.$highestRow, '№ дома');
        $sheet->setCellValue('I'.$highestRow, '№ квартиры');
        $sheet->setCellValue('J'.$highestRow, 'Количество жилых комнат.');
        $sheet->setCellValue('K'.$highestRow, 'Сумма договора');
        $sheet->setCellValue('L'.$highestRow, 'Сумма доли от суммы договора');
        $sheet->setCellValue('M'.$highestRow, 'Сумма оплаты');
        $sheet->setCellValue('N'.$highestRow, 'Сумма доли от суммы оплаты');
        $sheet->setCellValue('O'.$highestRow, 'Задолженность');
        $sheet->setCellValue('P'.$highestRow, 'Субагент');
        $sheet->setCellValue('Q'.$highestRow, 'Застройщик');
//        $sheet->setCellValue('R'.$highestRow, 'Выплата вознаграждения, руб.');
//        $sheet->setCellValue('S'.$highestRow, 'Выплата вознаграждения, руб (накопительным итогом)');

        $sheet->getStyle('A'.$highestRow.':Q'.$highestRow)->applyFromArray($styleBorderThin);

        $sheet->getStyle('A'.$highestRow.':Q'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        //формат суммы
        $sheet->getStyle('K')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet->getStyle('L')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('O')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $highestRow = $highestRow+1;

        //динамические данные по 3 таблице

        foreach ($managers as $manager) {
            // dd($manager);

            //Договора с поступлениями за месяц

            $IncomesInMonth = DB::table('lead_params')
                ->select(DB::raw(
                    "

                 lead_params.contract_date,
                 lead_params.contract_sum,
                 IncomPays.sum as income_sum,
                 IncomPays.incomDate as income_date,
                 object_params.complex,
                 object_params.object_number,
                 object_params.address,
                 lead_params.contract_number,
                 object_params.rooms_number,
                 object_params.type_id,
                 object_params.illiquid,
                 lead_menegers.percent,
                 object_params.house_number,
                 lead_params.subagent_name,
                 object_types.type_name as object_type,
                 object_params.owner

                "))
                ->leftJoin('lead_menegers', function ($join) {
                    $join->on('lead_menegers.lead_id', '=', 'lead_params.lead_id')
                        ->where('lead_menegers.delete_flag', '=', 0);
                })
                ->leftJoin('IncomPays', 'IncomPays.contractNumber', '=', 'lead_params.contract_number')
                ->join('object_params', 'object_params.object_id', '=', 'lead_params.object_id')
                ->join('object_types', 'object_types.type_id', '=', 'object_params.type_id')
                ->where('lead_params.stage', '=', 142)
                ->where('lead_params.employee_id', '=', $manager->id)
                ->whereYear('incomDate', $year)
                ->whereMonth('incomDate', '=', $monthSelected)
                ->get();

//            $sumContractByManager = 0;
//            $sumPercentContractSumByManager = 0;
//            $sumIncomesByManager = 0;
//            $sumPercentOfIncomesByManager = 0;
//            $sumDebtByManager = 0;


          //  $incomesByContract = $IncomesInMonth->groupBy('contract_number');



            $sheet->setCellValue('A' . $highestRow, $manager->name)->getStyle('A' . $highestRow)->getFont()->setBold(true);

            $mergeManagerRow = $highestRow+$IncomesInMonth->count();
            $sheet->mergeCells('A'.$highestRow.':A'.$mergeManagerRow);

            $sheet->getStyle('A' . $highestRow . ':A' . $mergeManagerRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('A'.$highestRow.':A'.$mergeManagerRow)->applyFromArray($styleBorderThin);

            $highestRow = $highestRow + 1;
            $startTable3Row = $highestRow;


            foreach ($IncomesInMonth->sortBy('income_date') as $item ){
                $sheet->setCellValue('B' . $highestRow, $item->contract_number);
                $sheet->setCellValue('C' . $highestRow, Carbon::createFromFormat('Y-m-d', $item->contract_date)->format('d.m.Y'));
                //дата оплаты
                $sheet->setCellValue('D'.$highestRow,Carbon::createFromFormat('Y-m-d', $item->income_date)->format('d.m.Y'));

                $sheet->setCellValue('E'.$highestRow,$item->illiquid);
                $sheet->setCellValue('F'.$highestRow,$item->object_type);
                $sheet->setCellValue('G'.$highestRow,$item->complex);
                $sheet->setCellValue('H'.$highestRow,$item->house_number);
                $sheet->setCellValue('I'.$highestRow,$item->object_number);
                $sheet->setCellValue('J'.$highestRow,$item->rooms_number);
                //$sheet->setCellValue('K'.$highestRow,number_format($item->contract_sum,2,',',' '));
                $sheet->setCellValue('K'.$highestRow,$item->contract_sum);

                //Доля
                if($item->percent != null){
                    $percent = $item->percent;
                }
                else{
                    $percent = 1;
                }

                //Сумма доли от суммы договра
                $sheet->setCellValue('L'.$highestRow,$item->contract_sum*$percent);


                //сумма оплаты
                $sheet->setCellValue('M'.$highestRow,$item->income_sum);
                //сумма доли от суммы оплаты
                $sheet->setCellValue('N'.$highestRow,$item->income_sum*$percent);

                $sheet->setCellValue('P'.$highestRow,$item->subagent_name);


                $sheet->setCellValue('Q'.$highestRow,$item->owner);


                //все поступления по договору на дату оплаты
                $allIncomes = DB::table('IncomPays')
                    ->where('IncomPays.contractNumber',$item->contract_number)
                    ->where('IncomPays.incomDate','<=',$item->income_date)
                    ->select(
                        'IncomPays.sum'
                    )
                    ->get();

                //задолженность

                $allIncomesSum =  $allIncomes->sum('sum');


                $sheet->setCellValue('O'.$highestRow, $item->contract_sum - $allIncomesSum);

                $sheet->getStyle('A'.$highestRow.':Q'.$highestRow)->applyFromArray($styleBorderThin);
                $sheet->getStyle('A' . $highestRow . ':Q' . $highestRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);


                $highestRow ++;

            }



            $endTable3Row  = $highestRow-1;



            $sheet->setCellValue('A'.$highestRow,'Итого по менеджеру:')->getStyle('A'.$highestRow)->getFont()->setBold(true);

            $sheet->setCellValue('K'.$highestRow,'=SUM(K'.$startTable3Row .':K'.$endTable3Row .')')->getStyle('K'.$highestRow)->getFont()->setBold(true);
            $sheet->setCellValue('L'.$highestRow,'=SUM(L'.$startTable3Row .':L'.$endTable3Row .')')->getStyle('L'.$highestRow)->getFont()->setBold(true);
            $sheet->setCellValue('M'.$highestRow,'=SUM(M'.$startTable3Row .':M'.$endTable3Row .')')->getStyle('M'.$highestRow)->getFont()->setBold(true);
            $sheet->setCellValue('N'.$highestRow,'=SUM(N'.$startTable3Row .':N'.$endTable3Row .')')->getStyle('N'.$highestRow)->getFont()->setBold(true);




            $sheet->getStyle('A' . $highestRow . ':Q' . $highestRow)->applyFromArray($styleBorderThin);
            $sheet->getStyle('A' . $highestRow . ':Q' . $highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);




            $highestRow++;
        }


        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_workout.xlsx"');
        $writer->save("php://output");
    }
}
