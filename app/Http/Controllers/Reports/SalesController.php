<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Arr;
class SalesController extends Controller
{
    public function index(){
        return view('reports.sales.index');
    }

    public function makeReport(Request $request){

        if ($request->get('singleDate')) {
            $dateRequest = $request->get('singleDate');
            $date = Carbon::createFromFormat('d.m.Y', $dateRequest);

            $dateMinusWeek = Carbon::createFromFormat('d.m.Y', $dateRequest)->subDays(7);
            $dateMinusDay = Carbon::createFromFormat('d.m.Y', $dateRequest)->subDays(1);


            $year = $date->format("Y");
            $month = $date->format("n");

        }
        else{
            return redirect()->back()->with('status','Не выбрана дата');
        }

        $data = DB::table('lead_params')
            ->select(DB::raw(
                "
                    object_params.complex as complex,
                    object_types.class_property,
                    object_types.sort,
                    complexes.sort as complex_sort,
                    count(*) as fact_object_total,
                    SUM(lead_params.contract_sum) as fact_price,
                    SUM(object_params.total_area) as fact_square_total,
                    MONTH(lead_params.contract_date) as period_id


                "))
            ->join('object_params','object_params.object_id','=','lead_params.object_id')
            ->join('object_types','object_types.type_id','=','object_params.type_id')
            ->join('complexes','complexes.complex','=','object_params.complex')
            ->where('lead_params.stage','=',142)
            ->whereYear('lead_params.contract_date',$year)
            ->whereMonth('lead_params.contract_date','<=',$month)
            ->groupBy('complex','class_property','period_id')
            ->orderBy('complex_sort','asc');



        //запрос планы
        $plans = DB::table('plans_objects')
            ->where('year',$year)
            ->where('plans_objects.period_id','<=',$month)
            ->join('plans_codes','plans_codes.period_id','=','plans_objects.period_id')
            ->orderBy('plans_objects.period_id');



        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Ежедневные данные');

//        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);


        //заголовок
        $sheet->setCellValue('A1', 'Еженедельный отчет по продажам')->getStyle("A1")->getFont()->setSize(16);
        $sheet->setCellValue('A2', 'на '.$dateRequest)->getStyle("A2")->getFont()->setSize(16);


        //шапка таблицы
        $sheet->setCellValue('A4', '№ п/п');
        $sheet->mergeCells('A4:A6');

        $sheet->setCellValue('B4', 'Наименование ЖК / Собственник');
        $sheet->mergeCells('B4:C6');
        $sheet->getColumnDimension('B')->setWidth(30);



        $plansByMonth = $plans->get()->groupBy('period_name');
        $plansByPeriod = $plans->get()->groupBy('period_name')->toArray();




        //добавляем кварталы
        if (Arr::exists($plansByPeriod,'январь')){
            $plansByPeriod = array_slice($plansByPeriod, 0, 3, true) +
                array("1 квартал" => array(['period_name'=>'1 квартал'])) +
                array_slice($plansByPeriod, 3, count($plansByPeriod) - 1, true) ;

        }
        if (Arr::exists($plansByPeriod,'апрель')){
            $plansByPeriod = array_slice($plansByPeriod, 0, 7, true) +
                array("2 квартал" => array(['period_name'=>'2 квартал'])) +
                array_slice($plansByPeriod, 7, count($plansByPeriod) - 1, true) ;

        }
        if (Arr::exists($plansByPeriod,'июль')){
            $plansByPeriod = array_slice($plansByPeriod, 0, 11, true) +
                array("3 квартал" => array(['period_name'=>'3 квартал']),
                    "9 месяцев" => array(['period_name'=>'9 месяцев'])) +
                array_slice($plansByPeriod, 11, count($plansByPeriod) - 1, true) ;

        }
        if (Arr::exists($plansByPeriod,'октябрь')){
            $plansByPeriod = array_slice($plansByPeriod, 0, 19, true) +
                array("4 квартал" => array(['period_name'=>'4 квартал'])) +
                array_slice($plansByPeriod, 19, count($plansByPeriod) - 1, true) ;

        }

        //Добавляем год
        $plansByPeriod = array_slice($plansByPeriod, 0, 23, true) +
            array("итого год" => array(['period_name'=>'итого год'])) +
            array_slice($plansByPeriod, 23, count($plansByPeriod) - 1, true) ;


        //начало динамической шапки
        $next_period = "D";

        //функция увеличения буквы колонки
        function increment($val, $increment)
        {
            for ($i = 1; $i <= $increment; $i++) {
                $val++;
            }
            return $val;
        }




       foreach ($plansByPeriod as $period_name=>$valuesArr){


               $sheet->setCellValue($next_period.'5', $period_name);

               $mergeCols = increment($next_period, 4);

               $sheet->mergeCells($next_period . '5:' . $mergeCols . '5');

               if ($period_name == '1 квартал'){
                   $colFirstQuarter = $next_period;
               }
               if ($period_name == '2 квартал'){
                   $colSecondQuarter = $next_period;
               }
               if ($period_name == '3 квартал'){
                   $colThirdQuarter = $next_period;               }

               if ($period_name == '9 месяцев'){
                   $col9Months = $next_period;
               }

               if ($period_name == '4 квартал'){
                   $colFourthQuarter = $next_period;
               }
               if ($period_name == 'итого год'){
                   $colYear = $next_period;
               }



               $sheet->setCellValue($next_period . '6', "План");
               $sheet->getStyle($next_period.'6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('c1d79f');

               $sheet->setCellValue(increment($next_period, 1) . '6', "План по объектам, выставленным на продажу");
               $sheet->getStyle(increment($next_period, 1).'6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('eaf1df');


               $sheet->setCellValue(increment($next_period, 2) . '6', "Факт");
               $sheet->getStyle(increment($next_period, 2).'6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('eaf1df');


               $sheet->setCellValue(increment($next_period, 3) . '6', "% выполнения");
               $sheet->getStyle(increment($next_period, 3).'6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('c1d79f');

               $sheet->setCellValue(increment($next_period, 4) . '6', "% выполнения по объектам, выставленным на продажу");
               $sheet->getStyle(increment($next_period, 4).'6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('eaf1df');


               $sheet->getColumnDimension($next_period)->setWidth(20);
               $sheet->getColumnDimension(increment($next_period, 1))->setWidth(20);
               $sheet->getColumnDimension(increment($next_period, 2))->setWidth(20);
               $sheet->getColumnDimension(increment($next_period, 3))->setWidth(20);
               $sheet->getColumnDimension(increment($next_period, 4))->setWidth(20);

           $next_period = increment($next_period, 5);

       }


        $nextCol = $next_period;


        $sheet->getStyle('D5:'.$nextCol.'5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('c1d79f');


        $sheet->setCellValue($nextCol.'5', 'Реализация за период ('.$dateMinusWeek->format('d.m.Y').'-'.$dateRequest.')');
        $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(20);
        $sheet->getStyle($nextCol.'5:'.$nextCol.'5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('dbe6f0');


        $nextCol = increment($nextCol, 1);
        $sheet->setCellValue($nextCol.'5', 'в т.ч. реализация предыдущего дня');
        $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(20);
        $sheet->getStyle($nextCol.'5:'.$nextCol.'5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('dbe6f0');


        $sheet->mergeCells('D4:'.$nextCol.'4');

        $nextCol = increment($nextCol, 1);
        $sheet->setCellValue($nextCol.'4', 'Нереализованный остаток на ' . $dateRequest);
        $sheet->mergeCells($nextCol . '4:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(30);
        $sheet->getStyle($nextCol.'4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d6e4bf');


        $nextCol = increment($nextCol, 1);
        $sheet->setCellValue($nextCol.'4', 'Факт поступлений ДС нарастающим итогом');
        $sheet->mergeCells($nextCol . '4:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(25);
        $sheet->getStyle($nextCol.'4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');


        $nextCol = increment($nextCol, 1);
        $sheet->setCellValue($nextCol.'4', 'Факт поступлений ДС по договорам заключеннным в ' .$year . 'г');
        $sheet->mergeCells($nextCol . '4:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(25);
        $sheet->getStyle($nextCol.'4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');

        $nextCol = increment($nextCol, 1);
        $sheet->setCellValue($nextCol.'4', 'Факт поступлений ДС за текущий месяц');
        $sheet->mergeCells($nextCol . '4:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(25);
        $sheet->getStyle($nextCol.'4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');


        $nextCol = increment($nextCol, 1);
        $mergeCol = increment($nextCol, count($plansByMonth)-1);


        $sheet->setCellValue($nextCol.'4', 'Поступление ДС за ' .$year. 'г');
        $sheet->mergeCells($nextCol . '4:' . $mergeCol . '6');
        $sheet->getStyle($nextCol . '6:' . $mergeCol . '6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('89b5df');



        $arrMonths = [
            'январь',
            'февраль',
            'март',
            'апрель',
            'май',
            'июнь',
            'июль',
            'август',
            'сентябрь',
            'октябрь',
            'ноябрь',
            'декабрь'
        ];



        foreach ($plansByMonth as $monthName=>$values){

            $sheet->setCellValue($nextCol.'5', $monthName);
            $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
            $sheet->getColumnDimension($nextCol)->setWidth(20);
            $sheet->getStyle($nextCol . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('89b5df');

            $nextCol = increment($nextCol, 1);
        }


        $mergeCol = increment($nextCol, 3);
        $sheet->setCellValue($nextCol.'4', 'Прогноз поступлений ДС по заключенным договорам');
        $sheet->mergeCells($nextCol . '4:' . $mergeCol . '4');
        $sheet->getStyle($nextCol . '4:' . $mergeCol . '4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');


        $sheet->setCellValue($nextCol.'5', $arrMonths[$month-1] . ' '.$year);
        $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(20);
        $sheet->getStyle($nextCol . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');


        $nextCol = increment($nextCol, 1);

        //если декабрь
        if ($month == 12){
            $yearNext = (int)$year+1;
            $sheet->setCellValue($nextCol.'5', 'январь '.$yearNext);
        }
        //все остальные
        else{
            $sheet->setCellValue($nextCol.'5', $arrMonths[$month] . ' '.$year);
        }

        $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(20);
        $sheet->getStyle($nextCol . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');


        $nextCol = increment($nextCol, 1);
        //если ноябрь
        if($month == 11){
            $yearNext = (int)$year+1;
            $sheet->setCellValue($nextCol.'5', 'январь '.$yearNext);

        }
        //если декабрь
        elseif ($month == 12){
            $yearNext = (int)$year+1;
            $sheet->setCellValue($nextCol.'5', 'февраль '.$yearNext);
        }
        //все остальные
        else{
            $sheet->setCellValue($nextCol.'5', $arrMonths[$month+1] . ' '.$year);
        }

        $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(20);
        $sheet->getStyle($nextCol . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');



        $nextCol = increment($nextCol, 1);
        $sheet->setCellValue($nextCol.'5', 'остаток к получ. по заключ. договорам');
        $sheet->mergeCells($nextCol . '5:' . $nextCol . '6');
        $sheet->getColumnDimension($nextCol)->setWidth(20);
        $sheet->getStyle($nextCol . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');



        $highestCol = $sheet->getHighestColumn();

        $sheet->getColumnDimension('A')->setWidth(7);

        $sheet->mergeCells('A2:'.$highestCol.'2');

        $sheet->getStyle('A4:'.$highestCol.'6')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension('4')->setRowHeight(20);
        $sheet->getRowDimension('5')->setRowHeight(20);
        $sheet->getRowDimension('6')->setRowHeight(50);

        $styleHeader = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
                'inside'=>[
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ]

            ],

            'font'  => [
                'bold'  => true,
                'color' => array('rgb' => '000000'),
                'size'  => 10,

            ]

        ];

        $sheet ->getStyle('A4:'.$highestCol.'6')->applyFromArray($styleHeader);




        //Динамические данные(строки)

        $highestRow = 7;
        $num = 1;
//            dd($data);
//        $data = $data->where('complex','Светлая долина')
//        ->get()->groupBy('complex');
//
//        dd($data);

        $dataByComplex = $data->get()->groupBy('complex');

        //dd($dataByComplex);

        foreach ($dataByComplex as $complex=>$complexData){

            $complexDataByProperty = $complexData->sortBy('sort')->groupBy('class_property');



            $sheet->setCellValue('A'.$highestRow,$num);

            $numMergeRow = $highestRow+(count($complexDataByProperty)*3);
            $sheet->mergeCells('A'.$highestRow.':A'.$numMergeRow);

            $sheet->setCellValue('B'.$highestRow,$complex);
            $sheet->mergeCells('B'.$highestRow.':C'.$highestRow);
            $sheet->getStyle('B'.$highestRow.':'.$highestCol.$highestRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('bfbfbf');

            //следующая строка
           $highestRow = $highestRow+1;

            foreach ($complexDataByProperty as $complexProperty=>$propertyData) {

                    switch ($complexProperty) {
                    case 'commercial':
                        $propertyItem =  "коммерческая недвижимость";
                        break;
                    case 'parking':
                        $propertyItem = "паркинг";
                        break;
                    case 'pervichka':
                        $propertyItem = "первичная недвижимость";
                        break;
                    case 'pantry':
                        $propertyItem = "кладовая";
                        break;

                }


                $sheet->setCellValue('B'.$highestRow,$propertyItem);
                $mergeRow = $highestRow+2;

                $sheet->mergeCells('B'.$highestRow.':B'.$mergeRow);

                $sheet->getStyle('B'.$highestRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                //факт количество объектов по кварталам
                $firstQuarterFactObjTotal = 0;
                $secondQuarterFactObjTotal = 0;
                $thirdQuarterFactObjTotal = 0;
                $fourthQuarterFactObjTotal = 0;

                //год
                $yearFactObjTotal = 0;

                //факт  площадь по кварталам
                $firstQuarterFactSqTotal = 0;
                $secondQuarterFactSqTotal = 0;
                $thirdQuarterFactSqTotal = 0;
                $fourthQuarterFactSqTotal = 0;

                //год
                $yearFactSqTotal = 0;

                //факт стоимость  по кварталам
                $firstQuarterFactPrice = 0;
                $secondQuarterFactPrice = 0;
                $thirdQuarterFactPrice = 0;
                $fourthQuarterFactPrice = 0;

                //год
                $yearFactPrice = 0;

                foreach ($propertyData as $item) {

                    //брем планы по комплексу/периоду/типу объекта
                    $plansByProperty = DB::table('plans_objects')

                        ->where('year', $year)
                        ->where('complex', '=', $item->complex)
                        ->where('plans_objects.period_id', '<=', $month)
                        ->where('class_property', '=', $item->class_property)
                        ->get();

                    //реализация за период
                    $salesByPeriod = DB::table('lead_params')
                        ->select(DB::raw(
                            "

                            count(*) as count,
                            SUM(lead_params.contract_sum) as sum,
                            SUM(object_params.total_area) as square

                        "))
                        ->join('object_params','object_params.object_id','=','lead_params.object_id')
                        ->join('object_types','object_types.type_id','=','object_params.type_id')
                        ->where('lead_params.stage','=',142)
                        ->whereYear('lead_params.contract_date',$year)
                        ->where('object_params.complex','=',$item->complex)
                        ->where('object_types.class_property','=',$item->class_property)
                        ;

                    //(минус неделя от текущего дня)
                    $salesWeekBefore =
                        $salesByPeriod
                            ->where('lead_params.contract_date','>=',$dateMinusWeek)
                            ->where('lead_params.contract_date','<=',$date);

                    if ($salesWeekBefore->first()->count == 0){

                        $count =0;
                        $square = 0;
                        $sum = 0;
                    }
                    else{

                        $count =$salesWeekBefore->first()->count;
                        $square = round($salesWeekBefore->first()->square,1);
                        $sum = $salesWeekBefore->first()->sum;
                    }

                    //реализация предыдущего дня

                    $salesDayBefore =
                        $salesByPeriod->whereDate('lead_params.contract_date','=',$dateMinusDay);


                    if ($salesDayBefore->first()->count == 0){

                        $countDB =0;
                        $squareDB = 0;
                        $sumDB = 0;
                    }
                    else{

                        $countDB =$salesDayBefore->first()->count;
                        $squareDB = round($salesDayBefore->first()->square,1);
                        $sumDB = $salesDayBefore->first()->sum;
                    }


                    //НЕреализзуемый остаток(новая схема)


                    $unSaleSum = DB::table('lead_params')
                        ->select(DB::raw(
                            "

                            SUM(lead_params.contract_sum) as unsale_sum


                        "))
                        ->join('object_params','object_params.object_id','=','lead_params.object_id')
                        ->join('object_types','object_types.type_id','=','object_params.type_id')
                        ->where('lead_params.stage','=',142)
                        ->whereYear('lead_params.contract_date',$year)
                        ->where('object_params.complex','=',$item->complex)
                        ->where('object_types.class_property','=',$item->class_property)
                        ->whereIn('object_params.status_id',[1,2])
                        ->first()
                        ->unsale_sum;


                    //Поступление денежных средств

                    $incomePays = DB::table('lead_params')
                        ->select(DB::raw(
                            "

                            SUM(IncomPays.sum) as income_sum

                        "))
                        ->join('object_params','object_params.object_id','=','lead_params.object_id')
                        ->join('object_types','object_types.type_id','=','object_params.type_id')
                        ->join('IncomPays','IncomPays.contractNumber','=','lead_params.contract_number')
                        ->where('lead_params.stage','=',142)
                        ->where('object_params.complex','=',$item->complex)
                        ->where('object_types.class_property','=',$item->class_property);




                        $allIncomes = $incomePays->first()->income_sum;

                        $yearIncomes = $incomePays
                            ->whereYear('lead_params.contract_date','=',$year)
                            ->first()
                            ->income_sum;
                        $monthIncomes = $incomePays
                            ->whereYear('IncomPays.incomDate',$year)
                            ->whereMonth('IncomPays.incomDate',$month)
                            ->first()
                            ->income_sum;


                    //Поступление ДС за текущий год
                    $incomePaysCurrentYear = DB::table('lead_params')
                        ->select(DB::raw(
                            "
                          MONTH(IncomPays.incomDate) as month,
                          SUM(IncomPays.sum) as income_sum_month

                        "))
                        ->join('object_params','object_params.object_id','=','lead_params.object_id')
                        ->join('object_types','object_types.type_id','=','object_params.type_id')
                        ->join('IncomPays','IncomPays.contractNumber','=','lead_params.contract_number')
                        ->where('lead_params.stage','=',142)
                        ->where('object_params.complex','=',$item->complex)
                        ->where('object_types.class_property','=',$item->class_property)
                        ->whereYear('IncomPays.incomDate',$year)
                        ->whereMonth('IncomPays.incomDate','<=',$month)
                        ->groupBy('month')
                        ->get();

                    //dd($incomePaysCurrentYear[0]);



                    switch ($item->period_id) {
                        case 1:

                            $colFact = "F";
                            break;
                        case 2:

                            $colFact = "K";
                            break;
                        case 3:

                            $colFact = "P";
                            break;



                        case 4:

                            $colFact = "Z";
                            break;
                        case 5:

                            $colFact = "AE";
                            break;
                        case 6:

                            $colFact = "AJ";
                            break;



                        case 7:

                            $colFact = "AT";
                            break;
                        case 8:

                            $colFact = "AY";
                            break;
                        case 9:

                            $colFact = "BD";
                            break;



                        case 10:

                            $colFact = "BS";
                            break;
                        case 11:

                            $colFact = "BX";
                            break;
                        case 12:

                            $colFact = "CC";
                            break;

                    }


                    ///////////////////ФАКТ//////////////////////////
                    /// ////////////////////////////////////////////
                    /// /////////////////////////////////////////////


                    ////////////КОЛИЧЕСТВО ОБЪЕКТОВ////////////////
                    // ПО ЯЧЕЙКАМ В СТРОКЕ

                    $sheet->setCellValue($colFact . $highestRow, $item->fact_object_total);

                    //ПО КВАРТАЛАМ
                   //1 квартал
                    if ($item->period_id<=3 && isset($colFirstQuarter)){
                        $colFactFirstQuarter = increment($colFirstQuarter,2);
                        $firstQuarterFactObjTotal+=$item->fact_object_total;
                        $sheet->setCellValue($colFactFirstQuarter . $highestRow, $firstQuarterFactObjTotal);
                    }
                    //2 квартал
                    if ($item->period_id <7 && isset($colSecondQuarter)){
                        $colFactSecondQuarter = increment($colSecondQuarter,2);
                        //за 2 квартал
                        $secondQuarterFactObjTotal+=$item->fact_object_total;

                        $sheet->setCellValue($colFactSecondQuarter . $highestRow, $secondQuarterFactObjTotal);
                    }

                    //3 квартал
                    if ($item->period_id <10  && isset($colThirdQuarter)){
                        $colFactThirdQuarter = increment($colThirdQuarter,2);
                        $colFact9Months = increment($colThirdQuarter,7);//9месяцев
                        $thirdQuarterFactObjTotal+=$item->fact_object_total;
                        $sheet->setCellValue($colFactThirdQuarter . $highestRow, $thirdQuarterFactObjTotal);
                        $sheet->setCellValue($colFact9Months . $highestRow, $thirdQuarterFactObjTotal);//9месяцев
                    }

                    //4 квартал
                    if (isset($colFourthQuarter)){

                        $colFactFourthQuarter = increment($colFourthQuarter,2);
                        $fourthQuarterFactObjTotal+=$item->fact_object_total;
                        $sheet->setCellValue($colFactFourthQuarter . $highestRow, $fourthQuarterFactObjTotal);
                    }

                    //год
                    if (isset($colYear)){
                        $colFactYear = increment($colYear,2);
                        $yearFactObjTotal+=$item->fact_object_total;
                        $sheet->setCellValue($colFactYear . $highestRow, $yearFactObjTotal);


                    }

                    //реализация за период
                    $colSalesByPeriod = increment($colYear,5);
                    $sheet->setCellValue($colSalesByPeriod . $highestRow, $count);

                    //реализация предыдущего дня
                    $colSalesDayBefore = increment($colYear,6);
                    $sheet->setCellValue($colSalesDayBefore . $highestRow, $countDB);


                    //////////////////////////////////////////////////
                    ///////ПОСТУПЛЕНИЕ ДЕНЕЖНЫХ СРЕДСТВ/////////////
                    /// /////////////////////////////////////////////
                    $rowIncomes = $highestRow+2;

                    //за все время
                    $colIncomesAll = increment($colYear,8);
                    $sheet->setCellValue($colIncomesAll . $rowIncomes, $allIncomes);
                    $sheet->getStyle($colIncomesAll.$rowIncomes)->getNumberFormat()->setFormatCode('### ### ### ###');

                    // по договорам заключеннным в текущем году
                    $colIncomesYear = increment($colYear,9);
                    $sheet->setCellValue($colIncomesYear . $rowIncomes, $yearIncomes);
                    $sheet->getStyle($colIncomesYear.$rowIncomes)->getNumberFormat()->setFormatCode('### ### ### ###');

                    //за текущий месяц
                    $colIncomesMonth = increment($colYear,10);
                    $sheet->setCellValue($colIncomesMonth . $rowIncomes, $monthIncomes);
                    $sheet->getStyle($colIncomesMonth.$rowIncomes)->getNumberFormat()->setFormatCode('### ### ### ###');


                    //////////////////////////////////////////////////
                    ///////Поступление ДС за текущий год/////////////
                    /// /////////////////////////////////////////////

                    $colIncomesForYear = increment($colYear,11);



                    foreach ($incomePaysCurrentYear as $index=>$monthIncomesArr){

                        $sheet->setCellValue($colIncomesForYear . $rowIncomes, $monthIncomesArr->income_sum_month);
                        $sheet->getStyle($colIncomesForYear.$rowIncomes)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $colIncomesForYear = increment($colIncomesForYear,1);

                    }



                    ////////////ПЛОЩАДЬ////////////
                    // ПО ЯЧЕЙКАМ В СТРОКЕ
                    $factHighestRow = $highestRow+1;
                    $sheet->setCellValue($colFact . $factHighestRow, round($item->fact_square_total, 1));


                    //ПО КВАРТАЛАМ
                    //1 квартал
                    if ($item->period_id<=3 && isset($colFirstQuarter)){
                        $colFactFirstQuarter = increment($colFirstQuarter,2);
                        $firstQuarterFactSqTotal+=$item->fact_square_total;
                        $sheet->setCellValue($colFactFirstQuarter . $factHighestRow, round($firstQuarterFactSqTotal, 1));
                    }
                    //2 квартал
                    if ($item->period_id <7 && isset($colSecondQuarter)){
                        $colFactSecondQuarter = increment($colSecondQuarter,2);

                        $secondQuarterFactSqTotal+=$item->fact_square_total;
                        $sheet->setCellValue($colFactSecondQuarter . $factHighestRow, round($secondQuarterFactSqTotal, 1));
                    }

                    //3 квартал
                    if ($item->period_id <10  && isset($colThirdQuarter)){
                        $colFactThirdQuarter = increment($colThirdQuarter,2);
                        $colFact9Months = increment($colThirdQuarter,7);//9месяцев
                        $thirdQuarterFactSqTotal+=$item->fact_square_total;
                        $sheet->setCellValue($colFactThirdQuarter . $factHighestRow, round($thirdQuarterFactSqTotal, 1));
                        $sheet->setCellValue($colFact9Months . $factHighestRow, round($thirdQuarterFactSqTotal, 1));//9 месяцев

                    }


                    //4 квартал
                    if ( isset($colFourthQuarter)){

                        $colFactFourthQuarter = increment($colFourthQuarter,2);
                        $fourthQuarterFactSqTotal+=$item->fact_square_total;
                        $sheet->setCellValue($colFactFourthQuarter . $factHighestRow, round($fourthQuarterFactSqTotal, 1));
                    }

                    //год
                    if (isset($colYear)){
                        $colFactYear = increment($colYear,2);
                        $yearFactSqTotal+=$item->fact_square_total;
                        $sheet->setCellValue($colFactYear . $factHighestRow, round($yearFactSqTotal, 1));
                    }

                    //реализация за период
                    $colSalesByPeriod = increment($colYear,5);
                    $sheet->setCellValue($colSalesByPeriod . $factHighestRow,round($square, 1) );


                    //реализация предыдущего дня
                    $colSalesDayBefore = increment($colYear,6);
                    $sheet->setCellValue($colSalesDayBefore . $factHighestRow,round($squareDB, 1) );

                    ///////////СТОИМОСТЬ//////////////

                    // ПО ЯЧЕЙКАМ В СТРОКЕ

                    $factHighestRow = $factHighestRow+1;
                    $sheet->setCellValue($colFact . $factHighestRow, $item->fact_price);
                    $sheet->getStyle($colFact.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');


                    //ПО КВАРТАЛАМ
                    //1 квартал
                    if ($item->period_id<=3 && isset($colFirstQuarter)){
                        $colFactFirstQuarter = increment($colFirstQuarter,2);
                        $firstQuarterFactPrice+=$item->fact_price;
                        $sheet->setCellValue($colFactFirstQuarter . $factHighestRow, $firstQuarterFactPrice);
                        $sheet->getStyle($colFactFirstQuarter.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }
                    //2 квартал
                    if ($item->period_id <7 && isset($colSecondQuarter)){
                        $colFactSecondQuarter = increment($colSecondQuarter,2);

                        $secondQuarterFactPrice+=$item->fact_price;
                        $sheet->setCellValue($colFactSecondQuarter . $factHighestRow, $secondQuarterFactPrice);

                        $sheet->getStyle($colFactSecondQuarter.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }

                    //3 квартал
                    if ($item->period_id <10 && isset($colThirdQuarter)){
                        $colFactThirdQuarter = increment($colThirdQuarter,2);
                        $colFact9Months = increment($colThirdQuarter,7);//9месяцев
                        $thirdQuarterFactPrice+=$item->fact_price;
                        $sheet->setCellValue($colFactThirdQuarter . $factHighestRow, $thirdQuarterFactPrice);
                        $sheet->setCellValue($colFact9Months . $factHighestRow, $thirdQuarterFactPrice);//9месяцев

                        $sheet->getStyle($colFactThirdQuarter.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                        $sheet->getStyle($colFact9Months.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');//9месяцев

                    }


                    //4 квартал
                    if (isset($colFourthQuarter)){

                        $colFactFourthQuarter = increment($colFourthQuarter,2);
                        $fourthQuarterFactPrice+=$item->fact_price;
                        $sheet->setCellValue($colFactFourthQuarter . $factHighestRow, $fourthQuarterFactPrice);
                        $sheet->getStyle($colFactFourthQuarter.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }


                    //год
                    if (isset($colYear)){
                        $colFactYear = increment($colYear,2);
                        $yearFactPrice+=$item->fact_price;
                        $sheet->setCellValue($colFactYear . $factHighestRow, $yearFactPrice);
                        $sheet->getStyle($colFactYear.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }

                    //реализация за период

                    $colSalesByPeriod = increment($colYear,5);

                    $sheet->setCellValue($colSalesByPeriod . $factHighestRow, $sum);
                    if ($sum !=0){
                        $sheet->getStyle($colSalesByPeriod.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }


                    //реализация предыдущего дня
                    $colSalesDayBefore = increment($colYear,6);
                    $sheet->setCellValue($colSalesDayBefore . $factHighestRow, $sumDB);

                    if ($sumDB !=0){
                        $sheet->getStyle($colSalesDayBefore.$factHighestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }



                    if ($plansByProperty->count()>0) {

                        //1 квартал
                        $firstQuarterObjTotal = 0;
                        $firstQuarterSqTotal = 0;
                        $firstQuarterPrice = 0;

                        $firstQuarterObjInSaleTotal = 0;
                        $firstQuarterSqInSaleTotal = 0;
                        $firstQuarterPriceInSale = 0;



                        //2 квартал
                        $secondQuarterObjTotal = 0;
                        $secondQuarterSqTotal = 0;
                        $secondQuarterPrice = 0;

                        $secondQuarterObjInSaleTotal = 0;
                        $secondQuarterSqInSaleTotal = 0;
                        $secondQuarterPriceInSale = 0;
                        //3 квартал
                        $thirdQuarterObjTotal = 0;
                        $thirdQuarterSqTotal = 0;
                        $thirdQuarterPrice = 0;

                        $thirdQuarterObjInSaleTotal = 0;
                        $thirdQuarterSqInSaleTotal = 0;
                        $thirdQuarterPriceInSale = 0;
                        //4 квартал
                        $fourthQuarterObjTotal = 0;
                        $fourthQuarterSqTotal = 0;
                        $fourthQuarterPrice = 0;

                        $fourthQuarterObjInSaleTotal = 0;
                        $fourthQuarterSqInSaleTotal = 0;
                        $fourthQuarterPriceInSale = 0;

                        //год
                        $yearObjTotal = 0;
                        $yearSqTotal = 0;
                        $yearPrice = 0;

                        $yearObjInSaleTotal = 0;
                        $yearSqInSaleTotal = 0;
                        $yearPriceInSale = 0;

                        foreach ($plansByProperty as $itemPlan){

                            switch ($itemPlan->period_id) {
                                case 1:
                                    $colPlan =  "D";
                                    $colPlanInSale =  "E";
                                    $colDonePer = "G";
                                    $colDonePerInSale = "H";

                                    break;
                                case 2:
                                    $colPlan =  "I";
                                    $colPlanInSale =  "J";
                                    $colDonePer = "L";
                                    $colDonePerInSale = "M";
                                    break;
                                case 3:
                                    $colPlan =  "N";
                                    $colPlanInSale =  "O";
                                    $colDonePer = "Q";
                                    $colDonePerInSale = "R";
                                    break;



                                case 4:
                                    $colPlan =  "X";
                                    $colPlanInSale =  "Y";
                                    $colDonePer = "AA";
                                    $colDonePerInSale = "AB";
                                    break;
                                case 5:
                                    $colPlan =  "AC";
                                    $colPlanInSale =  "AD";
                                    $colDonePer = "AF";
                                    $colDonePerInSale = "AG";
                                    break;
                                case 6:
                                    $colPlan =  "AH";
                                    $colPlanInSale =  "AI";
                                    $colDonePer = "AK";
                                    $colDonePerInSale = "AL";
                                    break;


                                case 7:
                                    $colPlan =  "AR";
                                    $colPlanInSale =  "AS";
                                    $colDonePer = "AU";
                                    $colDonePerInSale = "AV";
                                    break;
                                case 8:
                                    $colPlan =  "AW";
                                    $colPlanInSale =  "AX";
                                    $colDonePer = "AZ";
                                    $colDonePerInSale = "BA";
                                    break;
                                case 9:
                                    $colPlan =  "BB";
                                    $colPlanInSale =  "BC";
                                    $colDonePer = "BE";
                                    $colDonePerInSale = "BF";
                                    break;



                                case 10:
                                    $colPlan =  "BQ";
                                    $colPlanInSale =  "BQ";
                                    $colDonePer = "BT";
                                    $colDonePerInSale = "BU";

                                    break;
                                case 11:
                                    $colPlan =  "BV";
                                    $colPlanInSale =  "BW";
                                    $colDonePer = "BY";
                                    $colDonePerInSale = "BZ";
                                    break;
                                case 12:
                                    $colPlan =  "CA";
                                    $colPlanInSale =  "CB";
                                    $colDonePer = "CD";
                                    $colDonePerInSale = "CE";

                                    break;

                            }



                            //КОЛИЧЕСТВО ОБЪЕКТОВ
                            //ПЛАН
                            // ПО ЯЧЕЙКАМ В СТРОКЕ
                            $sheet->setCellValue($colPlan . $highestRow, $itemPlan->object_total);

                            //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                            $factColObjTotal = increment($colPlan, 2);
                            $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                            if ($itemPlan->object_total !=0) {
                                $sheet->setCellValue($colDonePer . $highestRow, round(($factValObjTotal / $itemPlan->object_total) * 100, 0));
                            }

                            //ПРОЦЕНТ ВЫПОЛНЕНИЯ ВЫСТАВЛЕННЫХ НА ПРОДАЖУ
                            if ($itemPlan->objects_insale == 1) {
                                $sheet->setCellValue($colPlanInSale . $highestRow, $itemPlan->object_total);
                                if ($itemPlan->object_total !=0) {
                                    $sheet->setCellValue($colDonePerInSale . $highestRow, round(($factValObjTotal / $itemPlan->object_total) * 100, 0));
                                }
                            }


                            //ПО КВАРТАЛАМ
                            //1 квартал
                            if ($itemPlan->period_id <=3 && isset($colFirstQuarter)){
                                $firstQuarterObjTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colFirstQuarter . $highestRow, $firstQuarterObjTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colFirstQuarter, 2);
                                $colFirstQuarterDonePer = increment($colFirstQuarter,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($firstQuarterObjTotal!= 0) {
                                    $sheet->setCellValue($colFirstQuarterDonePer . $highestRow, round(($factValObjTotal / $firstQuarterObjTotal) * 100, 0));

                                }


                            }


                            //2 квартал
                            if ($itemPlan->period_id <7 && isset($colSecondQuarter)){
                                $secondQuarterObjTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colSecondQuarter . $highestRow, $secondQuarterObjTotal);


                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colSecondQuarter, 2);
                                $colSecondQuarterDonePer = increment($colSecondQuarter,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($secondQuarterObjTotal!= 0) {
                                    $sheet->setCellValue($colSecondQuarterDonePer . $highestRow, round(($factValObjTotal / $secondQuarterObjTotal) * 100, 0));

                                }



                            }


                            //3 квартал
                            if ($itemPlan->period_id <10  && isset($colThirdQuarter)){
                                $thirdQuarterObjTotal+=$itemPlan->object_total;

                                $plan9MonthsColObjTotal = increment($colThirdQuarter, 5);//9 месяцев

                                $sheet->setCellValue($colThirdQuarter . $highestRow, $thirdQuarterObjTotal);
                                $sheet->setCellValue($plan9MonthsColObjTotal . $highestRow, $thirdQuarterObjTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colThirdQuarter, 2);
                                $colThirdQuarterDonePer = increment($colThirdQuarter,3);



                                $colThirdQuarterDonePer9M = increment($colThirdQuarter,8);//9 месяцев


                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($thirdQuarterObjTotal!= 0) {
                                    $sheet->setCellValue($colThirdQuarterDonePer . $highestRow, round(($factValObjTotal / $thirdQuarterObjTotal) * 100, 0));
                                    $sheet->setCellValue($colThirdQuarterDonePer9M . $highestRow, round(($factValObjTotal / $thirdQuarterObjTotal) * 100, 0));

                                }

                            }


                            //4 квартал
                            if ( isset($colFourthQuarter)){
                                $fourthQuarterObjTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colFourthQuarter . $highestRow, $fourthQuarterObjTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colFourthQuarter, 2);
                                $colFourthQuarterDonePer = increment($colFourthQuarter,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($fourthQuarterObjTotal!= 0) {
                                    $sheet->setCellValue($colFourthQuarterDonePer . $highestRow, round(($factValObjTotal / $fourthQuarterObjTotal) * 100, 0));

                                }

                            }

                            //год
                            if ( isset($colYear)){
                                $yearObjTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colYear . $highestRow, $yearObjTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colYear, 2);
                                $colYearDonePer = increment($colYear,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($yearObjTotal!= 0) {
                                    $sheet->setCellValue($colYearDonePer . $highestRow, round(($factValObjTotal / $yearObjTotal) * 100, 0));

                                }

//                                //НЕРЕАЛИЗОВАННЫЙ ОСТАТОК
//                                $unSaleBalanceCol = increment($colYear,7);
//                                $sheet->setCellValue($unSaleBalanceCol . $highestRow, ($yearObjTotal - $factValObjTotal));


                            }

                            //Объекты выставленные  на продажу

                            //1 квартал
                            if ($itemPlan->period_id <=3 && isset($colFirstQuarter) && $itemPlan->objects_insale == 1){

                                $colFirstQuarterInSale = increment($colFirstQuarter,1);

                                $firstQuarterObjInSaleTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colFirstQuarterInSale . $highestRow, $firstQuarterObjInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colFirstQuarter, 2);
                                $colFirstQuarterDonePerInSale = increment($colFirstQuarterInSale,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($firstQuarterObjInSaleTotal!= 0) {
                                    $sheet->setCellValue($colFirstQuarterDonePerInSale . $highestRow, round(($factValObjTotal / $firstQuarterObjInSaleTotal) * 100, 0));

                                }

                            }


                            //2 квартал
                            if ($itemPlan->period_id <7 && isset($colSecondQuarter) && $itemPlan->objects_insale == 1){
                                $colSecondQuarterInSale = increment($colSecondQuarter,1);

                                $secondQuarterObjInSaleTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colSecondQuarterInSale . $highestRow, $secondQuarterObjInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colSecondQuarter, 2);
                                $colSecondQuarterDonePerInSale = increment($colSecondQuarterInSale,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($secondQuarterObjInSaleTotal!= 0) {
                                    $sheet->setCellValue($colSecondQuarterDonePerInSale . $highestRow, round(($factValObjTotal / $secondQuarterObjInSaleTotal) * 100, 0));

                                }


                            }


                            //3 квартал
                            if ($itemPlan->period_id <10  && isset($colThirdQuarter) && $itemPlan->objects_insale == 1){

                                $colThirdQuarterInSale = increment($colThirdQuarter,1);

                                $plan9MonthsColObjTotalInSale = increment($colThirdQuarter, 6);//9 месяцев

                                $thirdQuarterObjInSaleTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colThirdQuarterInSale . $highestRow, $thirdQuarterObjInSaleTotal);
                                $sheet->setCellValue($plan9MonthsColObjTotalInSale . $highestRow, $thirdQuarterObjInSaleTotal);//9 месяцев
                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colThirdQuarter, 2);
                                $colThirdQuarterDonePerInSale = increment($colThirdQuarterInSale,3);

                                $colThirdQuarterDonePerInSale9M = increment($colThirdQuarter,9);

                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($thirdQuarterObjInSaleTotal!= 0) {
                                    $sheet->setCellValue($colThirdQuarterDonePerInSale . $highestRow, round(($factValObjTotal / $thirdQuarterObjInSaleTotal) * 100, 0));
                                    $sheet->setCellValue($colThirdQuarterDonePerInSale9M . $highestRow, round(($factValObjTotal / $thirdQuarterObjInSaleTotal) * 100, 0));//9 месяцев

                                }

                            }


                            //4 квартал
                            if (isset($colFourthQuarter) && $itemPlan->objects_insale == 1){

                                $colFourthQuarterInSale = increment($colFourthQuarter,1);

                                $fourthQuarterObjInSaleTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colFourthQuarterInSale . $highestRow, $fourthQuarterObjInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colFourthQuarter, 2);
                                $colFourthQuarterDonePerInSale = increment($colFourthQuarterInSale,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($fourthQuarterObjInSaleTotal!= 0) {
                                    $sheet->setCellValue($colFourthQuarterDonePerInSale . $highestRow, round(($factValObjTotal / $fourthQuarterObjInSaleTotal) * 100, 0));

                                }

                            }

                            //год
                            if (isset($colYear) && $itemPlan->objects_insale == 1){

                                $colYearInSale = increment($colYear,1);

                                $yearObjInSaleTotal+=$itemPlan->object_total;
                                $sheet->setCellValue($colYearInSale . $highestRow, $yearObjInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColObjTotal = increment($colYear, 2);
                                $colYearDonePerInSale = increment($colYearInSale,3);
                                $factValObjTotal = $sheet->getCell($factColObjTotal.$highestRow)->getValue();
                                if ($yearObjInSaleTotal!= 0) {
                                    $sheet->setCellValue($colYearDonePerInSale . $highestRow, round(($factValObjTotal / $yearObjInSaleTotal) * 100, 0));

                                }

                            }


///////////////////////////////////////////##########################///////////////////////////////////////////////



                            //площадь

                            //ПЛАН
                            // ПО ЯЧЕЙКАМ В СТРОКЕ
                            $highestRowProperty = $highestRow+1;
                            $sheet->setCellValue($colPlan.$highestRowProperty,$itemPlan->square_total);

                            //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                            $factColSqTotal = increment($colPlan, 2);
                            $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                            if ($itemPlan->square_total !=0){
                                $sheet->setCellValue($colDonePer . $highestRowProperty, round(($factValSqTotal/$itemPlan->square_total)*100, 0));
                            }

                            //ПРОЦЕНТ ВЫПОЛНЕНИЯ ВЫСТАВЛЕННЫХ НА ПРОДАЖУ
                            if ($itemPlan->objects_insale == 1){
                                $sheet->setCellValue($colPlanInSale.$highestRowProperty,$itemPlan->square_total);

                                if ($itemPlan->square_total !=0) {
                                    $sheet->setCellValue($colDonePerInSale . $highestRowProperty, round(($factValSqTotal / $itemPlan->square_total) * 100, 0));
                                }
                            }


                            //ПЛАН //ПО КВАРТАЛАМ
                            //1 квартал
                            if ($itemPlan->period_id <=3 && isset($colFirstQuarter)){
                                $firstQuarterSqTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colFirstQuarter . $highestRowProperty, $firstQuarterSqTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colFirstQuarter, 2);
                                $colFirstQuarterDonePer = increment($colFirstQuarter,3);
                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();

                                if ($firstQuarterSqTotal!= 0) {
                                    $sheet->setCellValue($colFirstQuarterDonePer . $highestRowProperty, round(($factValSqTotal / $firstQuarterSqTotal) * 100, 0));
                                }

                            }


                            //2 квартал
                            if ($itemPlan->period_id <7  && isset($colSecondQuarter)){
                                $secondQuarterSqTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colSecondQuarter . $highestRowProperty, $secondQuarterSqTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colSecondQuarter, 2);
                                $colSecondQuarterDonePer = increment($colSecondQuarter,3);
                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($secondQuarterSqTotal!= 0) {
                                    $sheet->setCellValue($colSecondQuarterDonePer . $highestRowProperty, round(($factValSqTotal / $secondQuarterSqTotal) * 100, 0));
                                }
                            }


                            //3 квартал
                            if ($itemPlan->period_id <10  && isset($colThirdQuarter)){
                                $thirdQuarterSqTotal+=$itemPlan->square_total;

                                $plan9MonthsColObjTotal = increment($colThirdQuarter, 5);//9 месяцев

                                $sheet->setCellValue($colThirdQuarter . $highestRowProperty, $thirdQuarterSqTotal);

                                //9 месяцев
                                $sheet->setCellValue($plan9MonthsColObjTotal . $highestRowProperty, $thirdQuarterSqTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colThirdQuarter, 2);
                                $colThirdQuarterDonePer = increment($colThirdQuarter,3);
                                $colThirdQuarterDonePer9M = increment($colThirdQuarter,8);//9 месяцев

                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($thirdQuarterSqTotal!= 0) {
                                    $sheet->setCellValue($colThirdQuarterDonePer . $highestRowProperty, round(($factValSqTotal / $thirdQuarterSqTotal) * 100, 0));
                                    $sheet->setCellValue($colThirdQuarterDonePer9M . $highestRowProperty, round(($factValSqTotal / $thirdQuarterSqTotal) * 100, 0));

                                }
                            }


                            //4 квартал
                            if ( isset($colFourthQuarter)){
                                $fourthQuarterSqTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colFourthQuarter . $highestRowProperty, $fourthQuarterSqTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colFourthQuarter, 2);
                                $colFourthQuarterDonePer = increment($colFourthQuarter,3);
                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($fourthQuarterSqTotal!= 0) {
                                    $sheet->setCellValue($colFourthQuarterDonePer . $highestRowProperty, round(($factValSqTotal / $fourthQuarterSqTotal) * 100, 0));
                                }

                            }

                            //год
                            if ( isset($colYear)){
                                $yearSqTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colYear . $highestRowProperty, $yearSqTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colYear, 2);
                                $colYearDonePer = increment($colYear,3);
                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($yearSqTotal!= 0) {
                                    $sheet->setCellValue($colYearDonePer . $highestRowProperty, round(($factValSqTotal / $yearSqTotal) * 100, 0));

                                }

//                                //НЕРЕАЛИЗОВАННЫЙ ОСТАТОК
//                                $unSaleBalanceCol = increment($colYear,7);
//                                $sheet->setCellValue($unSaleBalanceCol . $highestRowProperty, ($yearSqTotal - $factValSqTotal));


                            }


                            //Объекты выставленные  на продажу

                            //1 квартал
                            if ($itemPlan->period_id <=3 && isset($colFirstQuarter) && $itemPlan->objects_insale == 1){

                                $colFirstQuarterInSale = increment($colFirstQuarter,1);

                                $firstQuarterSqInSaleTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colFirstQuarterInSale . $highestRowProperty, $firstQuarterSqInSaleTotal);


                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colFirstQuarter, 2);
                                $colFirstQuarterDonePerInSale = increment($colFirstQuarter,4);

                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($firstQuarterSqInSaleTotal!= 0) {
                                    $sheet->setCellValue($colFirstQuarterDonePerInSale . $highestRowProperty, round(($factValSqTotal / $firstQuarterSqInSaleTotal) * 100, 0));

                                }

                            }


                            //2 квартал
                            if ($itemPlan->period_id <7  && isset($colSecondQuarter) && $itemPlan->objects_insale == 1){
                                $colSecondQuarterInSale = increment($colSecondQuarter,1);

                                $secondQuarterSqInSaleTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colSecondQuarterInSale . $highestRowProperty, $secondQuarterSqInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colSecondQuarter, 2);
                                $colSecondQuarterDonePerInSale = increment($colSecondQuarter,4);

                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($secondQuarterSqInSaleTotal!= 0) {
                                    $sheet->setCellValue($colSecondQuarterDonePerInSale . $highestRowProperty, round(($factValSqTotal / $secondQuarterSqInSaleTotal) * 100, 0));

                                }

                            }


                            //3 квартал
                            if ($itemPlan->period_id <10  && isset($colThirdQuarter) && $itemPlan->objects_insale == 1){

                                $colThirdQuarterInSale = increment($colThirdQuarter,1);
                                $plan9MonthsColObjTotalInSale = increment($colThirdQuarter, 6);//9 месяцев

                                $thirdQuarterSqInSaleTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colThirdQuarterInSale . $highestRowProperty, $thirdQuarterSqInSaleTotal);
                                //9 месяцев
                                $sheet->setCellValue($plan9MonthsColObjTotalInSale . $highestRowProperty, $thirdQuarterSqInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colThirdQuarter, 2);
                                $colThirdQuarterDonePerInSale = increment($colThirdQuarter,4);
                                $colDonePerInSale9M = increment($colThirdQuarter,9);//9 месяцев

                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($thirdQuarterSqInSaleTotal!= 0) {
                                    $sheet->setCellValue($colThirdQuarterDonePerInSale . $highestRowProperty, round(($factValSqTotal / $thirdQuarterSqInSaleTotal) * 100, 0));

                                    //9 месяцев
                                    $sheet->setCellValue($colDonePerInSale9M . $highestRowProperty, round(($factValSqTotal / $thirdQuarterSqInSaleTotal) * 100, 0));

                                }

                            }


                            //4 квартал
                            if ( isset($colFourthQuarter) && $itemPlan->objects_insale == 1){

                                $colFourthQuarterInSale = increment($colFourthQuarter,1);

                                $fourthQuarterSqInSaleTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colFourthQuarterInSale . $highestRowProperty, $fourthQuarterSqInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colFourthQuarter, 2);
                                $colFourthQuarterDonePerInSale = increment($colFourthQuarter,4);

                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($fourthQuarterSqInSaleTotal!= 0) {
                                    $sheet->setCellValue($colFourthQuarterDonePerInSale . $highestRowProperty, round(($factValSqTotal / $fourthQuarterSqInSaleTotal) * 100, 0));

                                }

                            }

                            //год
                            if ( isset($colYear) && $itemPlan->objects_insale == 1){

                                $colYearInSale = increment($colYear,1);

                                $yearSqInSaleTotal+=$itemPlan->square_total;
                                $sheet->setCellValue($colYearInSale . $highestRowProperty, $yearSqInSaleTotal);

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColSqTotal = increment($colYear, 2);
                                $colYearDonePerInSale = increment($colYear,4);

                                $factValSqTotal = $sheet->getCell($factColSqTotal.$highestRowProperty)->getValue();
                                if ($yearSqInSaleTotal!= 0) {
                                    $sheet->setCellValue($colYearDonePerInSale . $highestRowProperty, round(($factValSqTotal / $yearSqInSaleTotal) * 100, 0));

                                }

                            }




                            //////////////////###############////////////////////////
                            //стоимость

                            //ПЛАН
                            // ПО ЯЧЕЙКАМ В СТРОКЕ
                            $highestRowProperty = $highestRowProperty+1;
                            $sheet->setCellValue($colPlan.$highestRowProperty,$itemPlan->price);
                            $sheet->getStyle($colPlan.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                            //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                            $factColPrice = increment($colPlan, 2);
                            $factValPrice = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                            if ($itemPlan->price !=0){
                                $sheet->setCellValue($colDonePer . $highestRowProperty, round(($factValPrice/$itemPlan->price)*100, 0));

                            }

                            //ПРОЦЕНТ ВЫПОЛНЕНИЯ ВЫСТАВЛЕННЫХ НА ПРОДАЖУ
                            if ($itemPlan->objects_insale == 1){
                                $sheet->setCellValue($colPlanInSale.$highestRowProperty,$itemPlan->price);
                                $sheet->getStyle($colPlanInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                if ($itemPlan->price !=0) {
                                    $sheet->setCellValue($colDonePerInSale . $highestRowProperty, round(($factValPrice / $itemPlan->price) * 100, 0));

                                }
                            }

                            //ПЛАН СТОИМОСТЬ ПО КВАРТАЛАМ
                            //1 квартал
                            if ($itemPlan->period_id <=3 && isset($colFirstQuarter)){
                                $firstQuarterPrice+=$itemPlan->price;
                                $sheet->setCellValue($colFirstQuarter . $highestRowProperty, $firstQuarterPrice);
                                $sheet->getStyle($colFirstQuarter.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colFirstQuarter, 2);
                                $colFirstQuarterDonePer = increment($colFirstQuarter,3);
                                $factValPrice = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($firstQuarterPrice!= 0) {
                                    $sheet->setCellValue($colFirstQuarterDonePer . $highestRowProperty, round(($factValPrice / $firstQuarterPrice) * 100, 0));

                                }


                            }


                            //2 квартал
                            if ($itemPlan->period_id <7 && isset($colSecondQuarter)){
                                $secondQuarterPrice+=$itemPlan->price;
                                $sheet->setCellValue($colSecondQuarter . $highestRowProperty, $secondQuarterPrice);
                                $sheet->getStyle($colSecondQuarter.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colSecondQuarter, 2);
                                $colSecondQuarterDonePer = increment($colSecondQuarter,3);
                                $factValPrice = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($secondQuarterPrice!= 0) {
                                    $sheet->setCellValue($colSecondQuarterDonePer . $highestRowProperty, round(($factValPrice / $secondQuarterPrice) * 100, 0));

                                }


                            }


                            //3 квартал
                            if ($itemPlan->period_id <10  && isset($colThirdQuarter)){
                                $thirdQuarterPrice+=$itemPlan->price;

                                $planColPrice9M = increment($colThirdQuarter, 5);//9 месяцев

                                $sheet->setCellValue($colThirdQuarter . $highestRowProperty, $thirdQuarterPrice);
                                $sheet->getStyle($colThirdQuarter.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //9 месяцев
                                $sheet->setCellValue($planColPrice9M . $highestRowProperty, $thirdQuarterPrice);
                                $sheet->getStyle($planColPrice9M.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');


                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colThirdQuarter, 2);
                                $colThirdQuarterDonePer = increment($colThirdQuarter,3);
                                $col9MDonePer = increment($colThirdQuarter,8);//9 месяцев
                                $factValPrice = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($thirdQuarterPrice!= 0) {
                                    $sheet->setCellValue($colThirdQuarterDonePer . $highestRowProperty, round(($factValPrice / $thirdQuarterPrice) * 100, 0));

                                    //9 месяцев
                                    $sheet->setCellValue($col9MDonePer . $highestRowProperty, round(($factValPrice / $thirdQuarterPrice) * 100, 0));

                                }


                            }


                            //4 квартал
                            if ( isset($colFourthQuarter)){
                                $fourthQuarterPrice+=$itemPlan->price;
                                $sheet->setCellValue($colFourthQuarter . $highestRowProperty, $fourthQuarterPrice);
                                $sheet->getStyle($colFourthQuarter.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colFourthQuarter, 2);
                                $colFourthQuarterDonePer = increment($colFourthQuarter,3);
                                $factValPrice = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($fourthQuarterPrice!= 0) {
                                    $sheet->setCellValue($colFourthQuarterDonePer . $highestRowProperty, round(($factValPrice / $fourthQuarterPrice) * 100, 0));

                                }

                            }

                            //год
                            if ( isset($colYear)){
                                $yearPrice+=$itemPlan->price;
                                $sheet->setCellValue($colYear . $highestRowProperty, $yearPrice);
                                $sheet->getStyle($colYear.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colYear, 2);
                                $colYearDonePer = increment($colYear,3);
                                $factValPrice = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($yearPrice!= 0) {
                                    $sheet->setCellValue($colYearDonePer . $highestRowProperty, round(($factValPrice / $yearPrice) * 100, 0));

                                }

                                //НЕРЕАЛИЗОВАННЫЙ ОСТАТОК
                                $unSaleBalanceCol = increment($colYear,7);
                                $sheet->setCellValue($unSaleBalanceCol . $highestRowProperty, $unSaleSum);
                                $sheet->getStyle($unSaleBalanceCol.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                            }


                            //Объекты выставленные  на продажу

                            //1 квартал
                            if ($itemPlan->period_id <=3 && isset($colFirstQuarter) && $itemPlan->objects_insale == 1){

                                $colFirstQuarterInSale = increment($colFirstQuarter,1);

                                $firstQuarterPriceInSale+=$itemPlan->price;
                                $sheet->setCellValue($colFirstQuarterInSale . $highestRowProperty, $firstQuarterPriceInSale);

                                $sheet->getStyle($colFirstQuarterInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colFirstQuarter, 2);
                                $colFirstQuarterDonePerInSale = increment($colFirstQuarter,4);
                                $factValPriceInSale = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($firstQuarterPriceInSale!= 0) {
                                    $sheet->setCellValue($colFirstQuarterDonePerInSale . $highestRowProperty, round(($factValPriceInSale / $firstQuarterPriceInSale) * 100, 0));

                                }


                            }


                            //2 квартал
                            if ($itemPlan->period_id <7  && isset($colSecondQuarter) && $itemPlan->objects_insale == 1){
                                $colSecondQuarterInSale = increment($colSecondQuarter,1);

                                $secondQuarterPriceInSale+=$itemPlan->price;
                                $sheet->setCellValue($colSecondQuarterInSale . $highestRowProperty, $secondQuarterPriceInSale);

                                $sheet->getStyle($colSecondQuarterInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colSecondQuarter, 2);
                                $colSecondQuarterDonePerInSale = increment($colSecondQuarter,4);
                                $factValPriceInSale = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($secondQuarterPriceInSale!= 0) {
                                    $sheet->setCellValue($colSecondQuarterDonePerInSale . $highestRowProperty, round(($factValPriceInSale / $secondQuarterPriceInSale) * 100, 0));

                                }


                            }


                            //3 квартал
                            if ($itemPlan->period_id <10  && isset($colThirdQuarter) && $itemPlan->objects_insale == 1){

                                $colThirdQuarterInSale = increment($colThirdQuarter,1);
                                $planColPrice9MInSale = increment($colThirdQuarter, 6);//9 месяцев

                                $thirdQuarterPriceInSale+=$itemPlan->price;

                                $sheet->setCellValue($colThirdQuarterInSale . $highestRowProperty, $thirdQuarterPriceInSale);
                                $sheet->getStyle($colThirdQuarterInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //9 месяцев
                                $sheet->setCellValue($planColPrice9MInSale . $highestRowProperty, $thirdQuarterPriceInSale);
                                $sheet->getStyle($planColPrice9MInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colThirdQuarter, 2);
                                $colThirdQuarterDonePerInSale = increment($colThirdQuarter,4);
                                $col9MDonePerInSale = increment($colThirdQuarter,9);
                                $factValPriceInSale = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($thirdQuarterPriceInSale!= 0) {
                                    $sheet->setCellValue($colThirdQuarterDonePerInSale . $highestRowProperty, round(($factValPriceInSale / $thirdQuarterPriceInSale) * 100, 0));
                                    $sheet->setCellValue($col9MDonePerInSale . $highestRowProperty, round(($factValPriceInSale / $thirdQuarterPriceInSale) * 100, 0));

                                }


                            }


                            //4 квартал
                            if ( isset($colFourthQuarter) && $itemPlan->objects_insale == 1){

                                $colFourthQuarterInSale = increment($colFourthQuarter,1);

                                $fourthQuarterPriceInSale+=$itemPlan->price;
                                $sheet->setCellValue($colFourthQuarterInSale . $highestRowProperty, $fourthQuarterSqInSaleTotal);

                                $sheet->getStyle($colFourthQuarterInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colFourthQuarter, 2);
                                $colFourthQuarterDonePerInSale = increment($colFourthQuarter,4);
                                $factValPriceInSale = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($fourthQuarterPriceInSale!= 0) {
                                    $sheet->setCellValue($colFourthQuarterDonePerInSale . $highestRowProperty, round(($factValPriceInSale / $fourthQuarterPriceInSale) * 100, 0));

                                }


                            }

                            //год
                            if ( isset($colYear) && $itemPlan->objects_insale == 1){

                                $colYearInSale = increment($colYear,1);

                                $yearPriceInSale+=$itemPlan->price;
                                $sheet->setCellValue($colYearInSale . $highestRowProperty, $yearPriceInSale);

                                $sheet->getStyle($colYearInSale.$highestRowProperty)->getNumberFormat()->setFormatCode('### ### ### ###');

                                //ПРОЦЕНТ ВЫПОЛНЕНИЯ
                                $factColPrice = increment($colYear, 2);
                                $colYearDonePerInSale = increment($colYear,4);
                                $factValPriceInSale = $sheet->getCell($factColPrice.$highestRowProperty)->getValue();
                                if ($yearPriceInSale!= 0) {
                                    $sheet->setCellValue($colYearDonePerInSale . $highestRowProperty, round(($factValPriceInSale / $yearPriceInSale) * 100, 0));

                                }


                            }

                        }

                    }

                }

                $sheet->setCellValue('C'.$highestRow,'кол-во');
                $sheet->getStyle('C'.$highestRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $highestRow = $highestRow+1;
                $sheet->setCellValue('C'.$highestRow,'м2');
                $sheet->getStyle('C'.$highestRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $highestRow = $highestRow+1;
                $sheet->setCellValue('C'.$highestRow,'сумма');
                $sheet->getStyle('C'.$highestRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $highestRow++;
            }
            $num++;


            if ($sheet->getHighestRow()<7){
                $highestRow++;
            }
        }
        $highestRow = $highestRow-1;
        $styleBody = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
                'inside'=>[
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ]

            ]

        ];
        $sheet ->getStyle('A7:'.$highestCol.$highestRow)->applyFromArray($styleBody);


        //////////////////////////////ИТОГО/////////////////////////////////////////////////

        $highestRow = $highestRow+1;

        $sheet->setCellValue('A'.$highestRow,'ИТОГО по группе, в т.ч.:');
        $sheet ->getStyle('A'.$highestRow) ->getFont()->setSize(12)->setBold(true);
        $sheet->mergeCells('A'.$highestRow .':C'.$highestRow);

        $totalCategoryRow = $highestRow;

       //$dataByProperty = $data->get()->groupBy('class_property');




        $highestRow = $highestRow+1;



        $allPlans = $plans->get()->groupBy('class_property');

        $TOTAL_PLANS_BY_PERIOD = $plans->get()->groupBy('period_id');

        $facts = DB::table('lead_params')
            ->select(DB::raw(
                "

                    object_types.class_property,
                    lead_params.contract_sum as fact_price,
                    object_params.total_area as fact_square_total,
                    MONTH(lead_params.contract_date) as period_id

                "))
            ->join('object_params','object_params.object_id','=','lead_params.object_id')
            ->join('object_types','object_types.type_id','=','object_params.type_id')

            ->where('lead_params.stage','=',142)
            ->whereYear('lead_params.contract_date',$year)
            ->whereMonth('lead_params.contract_date','<=',$month)
            ->get();

        $TOTAL_FACT_BY_PERIOD = $facts->groupBy('period_id');

        foreach ($TOTAL_PLANS_BY_PERIOD as $period_id=>$plansArr){

            switch ($period_id) {
                case 1:
                    $colPlan =  "D";
                    $colPlanInSale =  "E";
                    $colDonePer = "G";
                    $colDonePerInSale = "H";

                    break;
                case 2:
                    $colPlan =  "I";
                    $colPlanInSale =  "J";
                    $colDonePer = "L";
                    $colDonePerInSale = "M";
                    break;
                case 3:
                    $colPlan =  "N";
                    $colPlanInSale =  "O";
                    $colDonePer = "Q";
                    $colDonePerInSale = "R";
                    break;

                case 4:
                    $colPlan =  "X";
                    $colPlanInSale =  "Y";
                    $colDonePer = "AA";
                    $colDonePerInSale = "AB";
                    break;
                case 5:
                    $colPlan =  "AC";
                    $colPlanInSale =  "AD";
                    $colDonePer = "AF";
                    $colDonePerInSale = "AG";
                    break;
                case 6:
                    $colPlan =  "AH";
                    $colPlanInSale =  "AI";
                    $colDonePer = "AK";
                    $colDonePerInSale = "AL";
                    break;


                case 7:
                    $colPlan =  "AR";
                    $colPlanInSale =  "AS";
                    $colDonePer = "AU";
                    $colDonePerInSale = "AV";
                    break;
                case 8:
                    $colPlan =  "AW";
                    $colPlanInSale =  "AX";
                    $colDonePer = "AZ";
                    $colDonePerInSale = "BA";
                    break;
                case 9:
                    $colPlan =  "BB";
                    $colPlanInSale =  "BC";
                    $colDonePer = "BE";
                    $colDonePerInSale = "BF";
                    break;
                case 10:
                    $colPlan =  "BQ";
                    $colPlanInSale =  "BR";
                    $colDonePer = "BT";
                    $colDonePerInSale = "BU";
                    break;
                case 11:
                    $colPlan =  "BV";
                    $colPlanInSale =  "BW";
                    $colDonePer = "BY";
                    $colDonePerInSale = "BZ";
                    break;
                case 12:
                    $colPlan =  "CA";
                    $colPlanInSale =  "CB";
                    $colDonePer = "CD";
                    $colDonePerInSale = "CE";
                    break;
            }

            $TOTAL_PLAN_SUM = $plansArr->pluck('price')->sum();
            $TOTAL_PLAN_INSALE_SUM =0;
            foreach ($plansArr as $item){
                if ($item->objects_insale == 1){
                    $TOTAL_PLAN_INSALE_SUM+=$item->price;
                }
            }

            $sheet->setCellValue($colPlan.$totalCategoryRow, $TOTAL_PLAN_SUM);
            $sheet->getStyle($colPlan.$totalCategoryRow)->getNumberFormat()->setFormatCode('### ### ### ###');

            $sheet->setCellValue($colPlanInSale.$totalCategoryRow, $TOTAL_PLAN_INSALE_SUM);
            $sheet->getStyle($colPlanInSale.$totalCategoryRow)->getNumberFormat()->setFormatCode('### ### ### ###');


        }

        foreach ($TOTAL_FACT_BY_PERIOD as $period_id=>$factsArray){
            switch ($period_id) {
                case 1:
                    $colPlan = "D";
                    $colPlanInSale = "E";
                    $colFact = "F";
                    $colDonePer = "G";
                    $colDonePerInSale = "H";

                    break;
                case 2:
                    $colPlan = "I";
                    $colPlanInSale = "J";
                    $colFact = "K";
                    $colDonePer = "L";
                    $colDonePerInSale = "M";
                    break;
                case 3:
                    $colPlan = "N";
                    $colPlanInSale = "O";
                    $colFact = "P";
                    $colDonePer = "Q";
                    $colDonePerInSale = "R";
                    break;

                case 4:
                    $colPlan = "X";
                    $colPlanInSale = "Y";
                    $colFact = "Z";
                    $colDonePer = "AA";
                    $colDonePerInSale = "AB";
                    break;
                case 5:
                    $colPlan = "AC";
                    $colPlanInSale = "AD";
                    $colFact = "AE";
                    $colDonePer = "AF";
                    $colDonePerInSale = "AG";
                    break;
                case 6:
                    $colPlan = "AH";
                    $colPlanInSale = "AI";
                    $colFact = "AJ";
                    $colDonePer = "AK";
                    $colDonePerInSale = "AL";
                    break;


                case 7:
                    $colPlan = "AR";
                    $colPlanInSale = "AS";
                    $colFact = "AT";
                    $colDonePer = "AU";
                    $colDonePerInSale = "AV";
                    break;
                case 8:
                    $colPlan = "AW";
                    $colPlanInSale = "AX";
                    $colFact = "AY";
                    $colDonePer = "AZ";
                    $colDonePerInSale = "BA";
                    break;
                case 9:
                    $colPlan = "BB";
                    $colPlanInSale = "BC";
                    $colFact = "BD";
                    $colDonePer = "BE";
                    $colDonePerInSale = "BF";
                    break;
                case 10:
                    $colPlan = "BQ";
                    $colPlanInSale = "BR";
                    $colFact = "BS";
                    $colDonePer = "BT";
                    $colDonePerInSale = "BU";
                    break;
                case 11:
                    $colPlan = "BV";
                    $colPlanInSale = "BW";
                    $colFact = "BX";
                    $colDonePer = "BY";
                    $colDonePerInSale = "BZ";
                    break;
                case 12:
                    $colPlan = "CA";
                    $colPlanInSale = "CB";
                    $colFact = "CC";
                    $colDonePer = "CD";
                    $colDonePerInSale = "CE";
                    break;


            }
            $TOTAL_FACT_SUM = $factsArray->pluck('fact_price')->sum();

            $sheet->setCellValue($colFact.$totalCategoryRow, $TOTAL_FACT_SUM);
            $sheet->getStyle($colFact.$totalCategoryRow)->getNumberFormat()->setFormatCode('### ### ### ###');

            $planSumTotal = $sheet->getCell($colPlan.$totalCategoryRow)->getValue();
            if ($planSumTotal != 0){
                $percentSumTotal =  round($TOTAL_FACT_SUM / $planSumTotal*100 ,0);
            }
            else{
                $percentSumTotal = null;
            }

            $sheet->setCellValue($colDonePer.$totalCategoryRow, $percentSumTotal);


            $planSumTotalInSale = $sheet->getCell($colPlanInSale.$totalCategoryRow)->getValue();

            if ($planSumTotalInSale != 0){
                $percentSumTotalInSale =  round($TOTAL_FACT_SUM / $planSumTotalInSale*100 ,0);
            }
            else{
                $percentSumTotalInSale = null;
            }

            $sheet->setCellValue($colDonePerInSale.$totalCategoryRow, $percentSumTotalInSale);

        }

        foreach ($allPlans as $property=>$propertyPlansData){

            switch ($property) {
                case 'commercial':
                    $propertyItem =  "коммерческая недвижимость";
                    break;
                case 'parking':
                    $propertyItem = "паркинг";
                    break;
                case 'pervichka':
                    $propertyItem = "первичная недвижимость";
                    break;
                case 'pantry':
                    $propertyItem = "кладовая";
                    break;

            }

            $sheet->setCellValue('A'.$highestRow,$propertyItem);
            $mergeRow = $highestRow+2;
            $sheet->mergeCells('A'.$highestRow.':B'.$mergeRow);

            $sheet->getStyle('A'.$highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet->setCellValue('C'.$highestRow,'кол-во');
            $sheet->getStyle('C'.$highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $highestRow = $highestRow+1;
            $sheet->setCellValue('C'.$highestRow,'м2');
            $sheet->getStyle('C'.$highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $highestRow = $highestRow+1;
            $sheet->setCellValue('C'.$highestRow,'сумма');
            $sheet->getStyle('C'.$highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



            //планы по месяцам
                foreach ($propertyPlansData->groupBy('period_id') as $period_id=>$plansItemArr){

                 switch ($period_id) {
                     case 1:
                         $colPlan =  "D";
                         $colPlanInSale =  "E";
                         $colDonePer = "G";
                         $colDonePerInSale = "H";

                         break;
                     case 2:
                         $colPlan =  "I";
                         $colPlanInSale =  "J";
                         $colDonePer = "L";
                         $colDonePerInSale = "M";
                         break;
                     case 3:
                         $colPlan =  "N";
                         $colPlanInSale =  "O";
                         $colDonePer = "Q";
                         $colDonePerInSale = "R";
                         break;

                     case 4:
                         $colPlan =  "X";
                         $colPlanInSale =  "Y";
                         $colDonePer = "AA";
                         $colDonePerInSale = "AB";
                         break;
                     case 5:
                         $colPlan =  "AC";
                         $colPlanInSale =  "AD";
                         $colDonePer = "AF";
                         $colDonePerInSale = "AG";
                         break;
                     case 6:
                         $colPlan =  "AH";
                         $colPlanInSale =  "AI";
                         $colDonePer = "AK";
                         $colDonePerInSale = "AL";
                         break;


                     case 7:
                         $colPlan =  "AR";
                         $colPlanInSale =  "AS";
                         $colDonePer = "AU";
                         $colDonePerInSale = "AV";
                         break;
                     case 8:
                         $colPlan =  "AW";
                         $colPlanInSale =  "AX";
                         $colDonePer = "AZ";
                         $colDonePerInSale = "BA";
                         break;
                     case 9:
                         $colPlan =  "BB";
                         $colPlanInSale =  "BC";
                         $colDonePer = "BE";
                         $colDonePerInSale = "BF";
                         break;
                     case 10:
                         $colPlan =  "BQ";
                         $colPlanInSale =  "BR";
                         $colDonePer = "BT";
                         $colDonePerInSale = "BU";
                         break;
                     case 11:
                         $colPlan =  "BV";
                         $colPlanInSale =  "BW";
                         $colDonePer = "BY";
                         $colDonePerInSale = "BZ";
                         break;
                     case 12:
                         $colPlan =  "CA";
                         $colPlanInSale =  "CB";
                         $colDonePer = "CD";
                         $colDonePerInSale = "CE";
                         break;
                 }

                 $planObjectTotalByPeriod = 0;
                 $planSquareTotalByPeriod = 0;
                 $planSumTotalByPeriod = 0;

                 $planObjectTotalByPeriodInSale = 0;
                 $planSquareTotalByPeriodInSale = 0;
                 $planSumTotalByPeriodInSale = 0;




                 foreach ($plansItemArr as $planItem) {
                       $planObjectTotalByPeriod+=$planItem->object_total;
                       $planSquareTotalByPeriod+=$planItem->square_total;
                       $planSumTotalByPeriod+=$planItem->price;

                       if ($planItem->objects_insale ==1){
                           $planObjectTotalByPeriodInSale+=$planItem->object_total;
                           $planSquareTotalByPeriodInSale+=$planItem->square_total;
                           $planSumTotalByPeriodInSale+=$planItem->price;
                       }
                   }

                  $objTotalRow =  $highestRow-2;
                  $objSquareRow =  $highestRow-1;

                  //план
                  $sheet->setCellValue($colPlan.$objTotalRow, $planObjectTotalByPeriod);
                  $sheet->setCellValue($colPlan.$objSquareRow, $planSquareTotalByPeriod);
                  $sheet->setCellValue($colPlan.$highestRow, $planSumTotalByPeriod);
                  $sheet->getStyle($colPlan.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                 // $sheet->setCellValue($colPlan.$totalCategoryRow,'=SUM'.$colPlan.$objTotalRow.':')

                    //план по объектам выставленных на продажу
                 $sheet->setCellValue($colPlanInSale.$objTotalRow, $planObjectTotalByPeriodInSale);
                 $sheet->setCellValue($colPlanInSale.$objSquareRow, $planSquareTotalByPeriodInSale);
                 $sheet->setCellValue($colPlanInSale.$highestRow, $planSumTotalByPeriodInSale);
                 $sheet->getStyle($colPlanInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');


                    //кварталы

                    //ПЛАНЫ ПО КВАРТАЛАМ
                    $plansByProperty1Quarter = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.period_id', '<=', 3)
                        ->first();

                    $plansByProperty2Quarter = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.period_id', '<=', 6)
                        ->first();

                    $plansByProperty3Quarter = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.period_id', '<=', 9)
                        ->first();

                    $plansByProperty4Quarter = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.period_id', '<=', 12)
                        ->first();


                    //ПЛАНЫ ПО объектам выставленным на продажу по КВАРТАЛАМ
                    $plansByProperty1QuarterInSale = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.objects_insale','=',1)
                        ->where('plans_objects.period_id', '<=', 3)
                        ->first();

                    $plansByProperty2QuarterInSale = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.objects_insale','=',1)
                        ->where('plans_objects.period_id', '<=', 6)
                        ->first();

                    $plansByProperty3QuarterInSale = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.objects_insale','=',1)
                        ->where('plans_objects.period_id', '<=', 9)
                        ->first();

                    $plansByProperty4QuarterInSale = DB::table('plans_objects')
                        ->select(DB::raw(
                            "

                    SUM(plans_objects.object_total) as plan_object_total,
                    SUM(plans_objects.square_total) as plan_square_total,
                    SUM(plans_objects.price) as plan_price

                    "))
                        ->where('year', $year)
                        ->where('plans_objects.class_property','=',$property)
                        ->where('plans_objects.objects_insale','=',1)
                        ->where('plans_objects.period_id', '<=', 12)
                        ->first();

                    if (isset($colFirstQuarter)){
                        $sheet->setCellValue($colFirstQuarter.$objTotalRow, $plansByProperty1Quarter->plan_object_total);
                        $sheet->setCellValue($colFirstQuarter.$objSquareRow, $plansByProperty1Quarter->plan_square_total);
                        $sheet->setCellValue($colFirstQuarter.$highestRow, $plansByProperty1Quarter->plan_price);
                        $sheet->getStyle($colFirstQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $colFirstQuarterInSale = increment($colFirstQuarter,1);
                        $sheet->setCellValue($colFirstQuarterInSale.$objTotalRow, $plansByProperty1QuarterInSale->plan_object_total);
                        $sheet->setCellValue($colFirstQuarterInSale.$objSquareRow, $plansByProperty1QuarterInSale->plan_square_total);
                        $sheet->setCellValue($colFirstQuarterInSale.$highestRow, $plansByProperty1QuarterInSale->plan_price);
                        $sheet->getStyle($colFirstQuarterInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }
                    if (isset($colSecondQuarter)){
                        $sheet->setCellValue($colSecondQuarter.$objTotalRow, $plansByProperty2Quarter->plan_object_total);
                        $sheet->setCellValue($colSecondQuarter.$objSquareRow, $plansByProperty2Quarter->plan_square_total);
                        $sheet->setCellValue($colSecondQuarter.$highestRow, $plansByProperty2Quarter->plan_price);
                        $sheet->getStyle($colSecondQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $colSecondQuarterInSale = increment($colSecondQuarter,1);
                        $sheet->setCellValue($colSecondQuarterInSale.$objTotalRow, $plansByProperty2QuarterInSale->plan_object_total);
                        $sheet->setCellValue($colSecondQuarterInSale.$objSquareRow, $plansByProperty2QuarterInSale->plan_square_total);
                        $sheet->setCellValue($colSecondQuarterInSale.$highestRow, $plansByProperty2QuarterInSale->plan_price);
                        $sheet->getStyle($colSecondQuarterInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }
                    if (isset($colThirdQuarter)){
                        $sheet->setCellValue($colThirdQuarter.$objTotalRow, $plansByProperty3Quarter->plan_object_total);
                        $sheet->setCellValue($colThirdQuarter.$objSquareRow, $plansByProperty3Quarter->plan_square_total);
                        $sheet->setCellValue($colThirdQuarter.$highestRow, $plansByProperty3Quarter->plan_price);
                        $sheet->getStyle($colThirdQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $colThirdQuarterInSale = increment($colThirdQuarter,1);
                        $sheet->setCellValue($colThirdQuarterInSale.$objTotalRow, $plansByProperty3QuarterInSale->plan_object_total);
                        $sheet->setCellValue($colThirdQuarterInSale.$objSquareRow, $plansByProperty3QuarterInSale->plan_square_total);
                        $sheet->setCellValue($colThirdQuarterInSale.$highestRow, $plansByProperty3QuarterInSale->plan_price);
                        $sheet->getStyle($colThirdQuarterInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }
                    if (isset($col9Months)){
                        $sheet->setCellValue($col9Months.$objTotalRow, $plansByProperty3Quarter->plan_object_total);
                        $sheet->setCellValue($col9Months.$objSquareRow, $plansByProperty3Quarter->plan_square_total);
                        $sheet->setCellValue($col9Months.$highestRow, $plansByProperty3Quarter->plan_price);
                        $sheet->getStyle($col9Months.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $col9MonthsInSale = increment($col9Months,1);
                        $sheet->setCellValue($col9MonthsInSale.$objTotalRow, $plansByProperty3QuarterInSale->plan_object_total);
                        $sheet->setCellValue($col9MonthsInSale.$objSquareRow, $plansByProperty3QuarterInSale->plan_square_total);
                        $sheet->setCellValue($col9MonthsInSale.$highestRow, $plansByProperty3QuarterInSale->plan_price);
                        $sheet->getStyle($col9MonthsInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }
                    if (isset($colFourthQuarter)){
                        $sheet->setCellValue($colFourthQuarter.$objTotalRow, $plansByProperty4Quarter->plan_object_total);
                        $sheet->setCellValue($colFourthQuarter.$objSquareRow, $plansByProperty4Quarter->plan_square_total);
                        $sheet->setCellValue($colFourthQuarter.$highestRow, $plansByProperty4Quarter->plan_price);
                        $sheet->getStyle($colFourthQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $colFourthQuarterInSale = increment($colFourthQuarter,1);
                        $sheet->setCellValue($colFourthQuarterInSale.$objTotalRow, $plansByProperty4QuarterInSale->plan_object_total);
                        $sheet->setCellValue($colFourthQuarterInSale.$objSquareRow, $plansByProperty4QuarterInSale->plan_square_total);
                        $sheet->setCellValue($colFourthQuarterInSale.$highestRow, $plansByProperty4QuarterInSale->plan_price);
                        $sheet->getStyle($colFourthQuarterInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }
                    if (isset($colYear)){
                        $sheet->setCellValue($colYear.$objTotalRow, $plansByProperty4Quarter->plan_object_total);
                        $sheet->setCellValue($colYear.$objSquareRow, $plansByProperty4Quarter->plan_square_total);
                        $sheet->setCellValue($colYear.$highestRow, $plansByProperty4Quarter->plan_price);
                        $sheet->getStyle($colYear.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        $colYearInSale = increment($colYear,1);
                        $sheet->setCellValue($colYearInSale.$objTotalRow, $plansByProperty4QuarterInSale->plan_object_total);
                        $sheet->setCellValue($colYearInSale.$objSquareRow, $plansByProperty4QuarterInSale->plan_square_total);
                        $sheet->setCellValue($colYearInSale.$highestRow, $plansByProperty4QuarterInSale->plan_price);
                        $sheet->getStyle($colYearInSale.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    }





                }

               // dd($planSumTotalCategory);

                //факты по месяцам

               $propertyFactData = DB::table('lead_params')
                ->select(DB::raw(
                    "

                    object_types.class_property,
                    lead_params.contract_sum as fact_price,
                    object_params.total_area as fact_square_total,
                    MONTH(lead_params.contract_date) as period_id

                "))
                ->join('object_params','object_params.object_id','=','lead_params.object_id')
                ->join('object_types','object_types.type_id','=','object_params.type_id')
                ->where('object_types.class_property','=',$property)
                ->where('lead_params.stage','=',142)
                ->whereYear('lead_params.contract_date',$year)
                ->whereMonth('lead_params.contract_date','<=',$month)
                ->get();

              foreach ($propertyFactData->groupBy('period_id') as $period_id=>$factsItemArr){

                switch ($period_id) {
                    case 1:
                        $colPlan = "D";
                        $colPlanInSale = "E";
                        $colFact = "F";
                        $colDonePer = "G";
                        $colDonePerInSale = "H";

                        break;
                    case 2:
                        $colPlan = "I";
                        $colPlanInSale = "J";
                        $colFact = "K";
                        $colDonePer = "L";
                        $colDonePerInSale = "M";
                        break;
                    case 3:
                        $colPlan = "N";
                        $colPlanInSale = "O";
                        $colFact = "P";
                        $colDonePer = "Q";
                        $colDonePerInSale = "R";
                        break;

                    case 4:
                        $colPlan = "X";
                        $colPlanInSale = "Y";
                        $colFact = "Z";
                        $colDonePer = "AA";
                        $colDonePerInSale = "AB";
                        break;
                    case 5:
                        $colPlan = "AC";
                        $colPlanInSale = "AD";
                        $colFact = "AE";
                        $colDonePer = "AF";
                        $colDonePerInSale = "AG";
                        break;
                    case 6:
                        $colPlan = "AH";
                        $colPlanInSale = "AI";
                        $colFact = "AJ";
                        $colDonePer = "AK";
                        $colDonePerInSale = "AL";
                        break;


                    case 7:
                        $colPlan = "AR";
                        $colPlanInSale = "AS";
                        $colFact = "AT";
                        $colDonePer = "AU";
                        $colDonePerInSale = "AV";
                        break;
                    case 8:
                        $colPlan = "AW";
                        $colPlanInSale = "AX";
                        $colFact = "AY";
                        $colDonePer = "AZ";
                        $colDonePerInSale = "BA";
                        break;
                    case 9:
                        $colPlan = "BB";
                        $colPlanInSale = "BC";
                        $colFact = "BD";
                        $colDonePer = "BE";
                        $colDonePerInSale = "BF";
                        break;
                    case 10:
                        $colPlan = "BQ";
                        $colPlanInSale = "BR";
                        $colFact = "BS";
                        $colDonePer = "BT";
                        $colDonePerInSale = "BU";
                        break;
                    case 11:
                        $colPlan = "BV";
                        $colPlanInSale = "BW";
                        $colFact = "BX";
                        $colDonePer = "BY";
                        $colDonePerInSale = "BZ";
                        break;
                    case 12:
                        $colPlan = "CA";
                        $colPlanInSale = "CB";
                        $colFact = "CC";
                        $colDonePer = "CD";
                        $colDonePerInSale = "CE";
                        break;


                }


                $factSquareTotalByPeriod = 0;
                $factSumTotalByPeriod = 0;



                foreach ($factsItemArr as $factItem) {

                    $factSquareTotalByPeriod+=$factItem->fact_square_total;
                    $factSumTotalByPeriod+=$factItem->fact_price;

                }

                $objTotalRow =  $highestRow-2;
                $objSquareRow =  $highestRow-1;

                //факт
                $sheet->setCellValue($colFact.$objTotalRow, count($factsItemArr));
                $sheet->setCellValue($colFact.$objSquareRow, $factSquareTotalByPeriod);
                $sheet->setCellValue($colFact.$highestRow, $factSumTotalByPeriod);
                $sheet->getStyle($colFact.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                //процент выполнения

                $planObjTotal = $sheet->getCell($colPlan.$objTotalRow)->getValue();
                $planSquareTotal = $sheet->getCell($colPlan.$objSquareRow)->getValue();
                $planSumTotal = $sheet->getCell($colPlan.$highestRow)->getValue();
                if ($planObjTotal != 0){
                    $percentObjTotal =  round(count($factsItemArr) / $planObjTotal*100 ,0);
                }
                else{
                    $percentObjTotal = null;
                }

                if ($planSquareTotal != 0){
                    $percentSquareTotal =  round($factSquareTotalByPeriod / $planSquareTotal*100 ,0);
                }
                else{
                    $percentSquareTotal = null;
                }
                if ($planSumTotal != 0){
                    $percentSumTotal =  round($factSumTotalByPeriod / $planSumTotal*100 ,0);
                }
                else{
                    $percentSumTotal = null;
                }

                $sheet->setCellValue($colDonePer.$objTotalRow, $percentObjTotal);
                $sheet->setCellValue($colDonePer.$objSquareRow, $percentSquareTotal);
                $sheet->setCellValue($colDonePer.$highestRow, $percentSumTotal);

                //процент выполнения выставленных на продажу

                $planObjTotalInSale = $sheet->getCell($colPlanInSale.$objTotalRow)->getValue();
                $planSquareTotalInSale = $sheet->getCell($colPlanInSale.$objSquareRow)->getValue();
                $planSumTotalInSale = $sheet->getCell($colPlanInSale.$highestRow)->getValue();


                if ($planObjTotalInSale != 0){
                    $percentObjTotalInSale =  round(count($factsItemArr) / $planObjTotalInSale*100 ,0);
                }
                else{
                    $percentObjTotalInSale = null;
                }

                if ($planSquareTotalInSale != 0){
                    $percentSquareTotalInSale =  round($factSquareTotalByPeriod / $planSquareTotalInSale*100 ,0);
                }
                else{
                    $percentSquareTotalInSale = null;
                }
                if ($planSumTotalInSale != 0){
                    $percentSumTotalInSale =  round($factSumTotalByPeriod / $planSumTotalInSale*100 ,0);
                }
                else{
                    $percentSumTotalInSale = null;
                }

                $sheet->setCellValue($colDonePerInSale.$objTotalRow, $percentObjTotalInSale);
                $sheet->setCellValue($colDonePerInSale.$objSquareRow, $percentSquareTotalInSale);
                $sheet->setCellValue($colDonePerInSale.$highestRow, $percentSumTotalInSale);

                $firstQuarterFactObjTotal = 0;
                $firstQuarterFactSqTotal =0;
                $firstQuarterFactPrice = 0;

                $secondQuarterFactObjTotal = 0;
                $secondQuarterFactSqTotal =0;
                $secondQuarterFactPrice = 0;

                $thirdQuarterFactObjTotal = 0;
                $thirdQuarterFactSqTotal =0;
                $thirdQuarterFactPrice = 0;

                $fourthQuarterFactObjTotal = 0;
                $fourthQuarterFactSqTotal =0;
                $fourthQuarterFactPrice = 0;

                $yearFactObjTotal = 0;
                $yearFactSqTotal =0;
                $yearFactPrice = 0;



                foreach ($propertyFactData->groupBy('period_id') as $period=>$factsItemArray){
                    //ПО КВАРТАЛАМ ФАКТИЧЕСКИЕ

                    //1 квартал
                    if ($period<=3 && isset($colFirstQuarter)){
                        $colFactFirstQuarter = increment($colFirstQuarter,2);
                        $firstQuarterFactObjTotal+= count($factsItemArray);
                        foreach ($factsItemArray as $itemFact){

                            $firstQuarterFactSqTotal+=$itemFact->fact_square_total;
                            $firstQuarterFactPrice+=$itemFact->fact_price;
                        }
                     }

                    //2 квартал
                    if ($period<=6 && isset($colSecondQuarter)){
                        $colFactSecondQuarter = increment($colSecondQuarter,2);
                        $secondQuarterFactObjTotal+= count($factsItemArray);
                        foreach ($factsItemArray as $itemFact){
                            $secondQuarterFactSqTotal+=$itemFact->fact_square_total;
                            $secondQuarterFactPrice+=$itemFact->fact_price;
                        }
                    }

                    //3 квартал
                    if ($period<=9 && isset($colThirdQuarter) && isset($col9Months)){
                        $colFactThirdQuarter = increment($colThirdQuarter,2);
                        $colFact9Months = increment($col9Months,2);
                        $thirdQuarterFactObjTotal+= count($factsItemArray);
                        foreach ($factsItemArray as $itemFact){
                            $thirdQuarterFactSqTotal+=$itemFact->fact_square_total;
                            $thirdQuarterFactPrice+=$itemFact->fact_price;
                        }
                    }

                    //4 квартал
                    if ($period<=12 && isset($colFourthQuarter)){
                        $colFactFourthQuarter = increment($colFourthQuarter,2);
                        $colFactYear = increment($colYear,2);
                        $fourthQuarterFactObjTotal+= count($factsItemArray);
                        foreach ($factsItemArray as $itemFact){
                            $fourthQuarterFactSqTotal+=$itemFact->fact_square_total;
                            $fourthQuarterFactPrice+=$itemFact->fact_price;
                        }
                    }

                    //4 год
                    if ($period<=12 && isset($colYear)){

                        $colFactYear = increment($colYear,2);
                        $yearFactObjTotal+= count($factsItemArray);
                        foreach ($factsItemArray as $itemFact){
                            $yearFactSqTotal+=$itemFact->fact_square_total;
                            $yearFactPrice+=$itemFact->fact_price;
                        }
                    }
                }



                //1 квартал
                if (isset($colFactFirstQuarter)){
                    $sheet->setCellValue($colFactFirstQuarter . $objTotalRow, $firstQuarterFactObjTotal);
                    $sheet->setCellValue($colFactFirstQuarter . $objSquareRow, $firstQuarterFactSqTotal);
                    $sheet->setCellValue($colFactFirstQuarter . $highestRow, $firstQuarterFactPrice);
                    $sheet->getStyle($colFactFirstQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    //процент выполнения
                    $colDonePer = increment($colFirstQuarter,3);
                    $planObjTotal1Quarter = $sheet->getCell($colFirstQuarter.$objTotalRow)->getValue();
                    $planSquareTotal1Quarter = $sheet->getCell($colFirstQuarter.$objSquareRow)->getValue();
                    $planSumTotal1Quarter = $sheet->getCell($colFirstQuarter.$highestRow)->getValue();
                    if ($planObjTotal1Quarter != 0){
                        $percentObjTotal = round($firstQuarterFactObjTotal/$planObjTotal1Quarter*100,0);
                    }
                    else{
                        $percentObjTotal = null;
                    }

                    if ($planSquareTotal1Quarter != 0){
                        $percentSquareTotal = round($firstQuarterFactSqTotal/$planSquareTotal1Quarter*100,0);
                    }
                    else{
                        $percentSquareTotal = null;
                    }
                    if ($planSumTotal1Quarter != 0){
                        $percentSumTotal = round($firstQuarterFactPrice/$planSumTotal1Quarter*100,0);
                    }
                    else{
                        $percentSumTotal = null;
                    }

                    $sheet->setCellValue($colDonePer . $objTotalRow, $percentObjTotal);
                    $sheet->setCellValue($colDonePer . $objSquareRow, $percentSquareTotal);
                    $sheet->setCellValue($colDonePer . $highestRow, $percentSumTotal);

                    //процент выполнения объектов , выставленных на продажу
                    $col1QuarterDonePerInSale = increment($colFirstQuarter,4);
                    $col1QuarterPlanInSale = increment($colFirstQuarter,1);

                    $planObjTotal1QuarterInSale = $sheet->getCell($col1QuarterPlanInSale.$objTotalRow)->getValue();
                    $planSquareTotal1QuarterInSale = $sheet->getCell($col1QuarterPlanInSale.$objSquareRow)->getValue();
                    $planSumTotal1QuarterInSale = $sheet->getCell($col1QuarterPlanInSale.$highestRow)->getValue();
                    if ($planObjTotal1QuarterInSale != 0){
                        $percentObjTotal1QuarterInSale = round($firstQuarterFactObjTotal/$planObjTotal1QuarterInSale*100,0);
                    }
                    else{
                        $percentObjTotal1QuarterInSale = null;
                    }

                    if ($planSquareTotal1QuarterInSale != 0){
                        $percentSquareTotal1QuarterInSale = round($firstQuarterFactSqTotal/$planSquareTotal1QuarterInSale*100,0);
                    }
                    else{
                        $percentSquareTotal1QuarterInSale = null;
                    }
                    if ($planSumTotal1QuarterInSale != 0){
                        $percentSumTotal1QuarterInSale = round($firstQuarterFactPrice/$planSumTotal1QuarterInSale*100,0);
                    }
                    else{
                        $percentSumTotal1QuarterInSale = null;
                    }

                    $sheet->setCellValue($col1QuarterDonePerInSale . $objTotalRow, $percentObjTotal1QuarterInSale);
                    $sheet->setCellValue($col1QuarterDonePerInSale . $objSquareRow, $percentSquareTotal1QuarterInSale);
                    $sheet->setCellValue($col1QuarterDonePerInSale . $highestRow, $percentSumTotal1QuarterInSale);


                }
                //2 квартал
                if (isset($colFactSecondQuarter)){
                    $sheet->setCellValue($colFactSecondQuarter . $objTotalRow, $secondQuarterFactObjTotal);
                    $sheet->setCellValue($colFactSecondQuarter . $objSquareRow, $secondQuarterFactSqTotal);
                    $sheet->setCellValue($colFactSecondQuarter . $highestRow, $secondQuarterFactPrice);
                    $sheet->getStyle($colFactSecondQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    //процент выполнения
                    $colDonePer2Quarter = increment($colSecondQuarter,3);
                    $planObjTotal2Quarter = $sheet->getCell($colSecondQuarter.$objTotalRow)->getValue();
                    $planSquareTotal2Quarter = $sheet->getCell($colSecondQuarter.$objSquareRow)->getValue();
                    $planSumTotal2Quarter = $sheet->getCell($colSecondQuarter.$highestRow)->getValue();
                    if ($planObjTotal2Quarter != 0){
                        $percentObjTotal2Quarter = round($secondQuarterFactObjTotal/$planObjTotal2Quarter*100,0);
                    }
                    else{
                        $percentObjTotal2Quarter = null;
                    }

                    if ($planSquareTotal2Quarter != 0){
                        $percentSquareTotal2Quarter = round($secondQuarterFactSqTotal/$planSquareTotal2Quarter*100,0);
                    }
                    else{
                        $percentSquareTotal2Quarter = null;
                    }
                    if ($planSumTotal2Quarter != 0){
                        $percentSumTotal2Quarter = round($secondQuarterFactPrice/$planSumTotal2Quarter*100,0);
                    }
                    else{
                        $percentSumTotal2Quarter = null;
                    }

                    $sheet->setCellValue($colDonePer2Quarter . $objTotalRow, $percentObjTotal2Quarter);
                    $sheet->setCellValue($colDonePer2Quarter . $objSquareRow, $percentSquareTotal2Quarter);
                    $sheet->setCellValue($colDonePer2Quarter . $highestRow, $percentSumTotal2Quarter);

                    //процент выполнения объектов , выставленных на продажу
                    $col2QuarterDonePerInSale = increment($colSecondQuarter,4);
                    $col2QuarterPlanInSale = increment($colSecondQuarter,1);

                    $planObjTotal2QuarterInSale = $sheet->getCell($col2QuarterPlanInSale.$objTotalRow)->getValue();
                    $planSquareTotal2QuarterInSale = $sheet->getCell($col2QuarterPlanInSale.$objSquareRow)->getValue();
                    $planSumTotal2QuarterInSale = $sheet->getCell($col2QuarterPlanInSale.$highestRow)->getValue();
                    if ($planObjTotal2QuarterInSale != 0){
                        $percentObjTotal2QuarterInSale = round($secondQuarterFactObjTotal/$planObjTotal2QuarterInSale*100,0);
                    }
                    else{
                        $percentObjTotal2QuarterInSale = null;
                    }

                    if ($planSquareTotal2QuarterInSale != 0){
                        $percentSquareTotal2QuarterInSale = round($secondQuarterFactSqTotal/$planSquareTotal2QuarterInSale*100,0);
                    }
                    else{
                        $percentSquareTotal2QuarterInSale = null;
                    }
                    if ($planSumTotal2QuarterInSale != 0){
                        $percentSumTotal2QuarterInSale = round($secondQuarterFactPrice/$planSumTotal2QuarterInSale*100,0);
                    }
                    else{
                        $percentSumTotal2QuarterInSale = null;
                    }

                    $sheet->setCellValue($col2QuarterDonePerInSale . $objTotalRow, $percentObjTotal2QuarterInSale);
                    $sheet->setCellValue($col2QuarterDonePerInSale . $objSquareRow, $percentSquareTotal2QuarterInSale);
                    $sheet->setCellValue($col2QuarterDonePerInSale . $highestRow, $percentSumTotal2QuarterInSale);



                }

                //3 квартал + 9 месяцев
                if (isset($colFactThirdQuarter) && isset($colFact9Months)){
                    $sheet->setCellValue($colFactThirdQuarter . $objTotalRow, $thirdQuarterFactObjTotal);
                    $sheet->setCellValue($colFactThirdQuarter . $objSquareRow, $thirdQuarterFactSqTotal);
                    $sheet->setCellValue($colFactThirdQuarter . $highestRow, $thirdQuarterFactPrice);
                    $sheet->getStyle($colFactThirdQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                    $sheet->setCellValue($colFact9Months . $objTotalRow, $thirdQuarterFactObjTotal);
                    $sheet->setCellValue($colFact9Months . $objSquareRow, $thirdQuarterFactSqTotal);
                    $sheet->setCellValue($colFact9Months . $highestRow, $thirdQuarterFactPrice);
                    $sheet->getStyle($colFact9Months.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');


                    //процент выполнения
                    $colDonePer3Quarter = increment($colThirdQuarter,3);
                    $colDonePer9Months = increment($col9Months,3);
                    $planObjTotal3Quarter = $sheet->getCell($colThirdQuarter.$objTotalRow)->getValue();
                    $planSquareTotal3Quarter = $sheet->getCell($colThirdQuarter.$objSquareRow)->getValue();
                    $planSumTotal3Quarter = $sheet->getCell($colThirdQuarter.$highestRow)->getValue();
                    if ($planObjTotal3Quarter != 0){
                        $percentObjTotal3Quarter = round($thirdQuarterFactObjTotal/$planObjTotal3Quarter*100,0);
                    }
                    else{
                        $percentObjTotal3Quarter = null;
                    }

                    if ($planSquareTotal3Quarter != 0){
                        $percentSquareTotal3Quarter = round($thirdQuarterFactSqTotal/$planSquareTotal3Quarter*100,0);
                    }
                    else{
                        $percentSquareTotal3Quarter = null;
                    }
                    if ($planSumTotal3Quarter != 0){
                        $percentSumTotal3Quarter = round($thirdQuarterFactPrice/$planSumTotal3Quarter*100,0);
                    }
                    else{
                        $percentSumTotal3Quarter = null;
                    }

                    $sheet->setCellValue($colDonePer3Quarter . $objTotalRow, $percentObjTotal3Quarter);
                    $sheet->setCellValue($colDonePer3Quarter . $objSquareRow, $percentSquareTotal3Quarter);
                    $sheet->setCellValue($colDonePer3Quarter . $highestRow, $percentSumTotal3Quarter);

                    $sheet->setCellValue($colDonePer9Months . $objTotalRow, $percentObjTotal3Quarter);
                    $sheet->setCellValue($colDonePer9Months . $objSquareRow, $percentSquareTotal3Quarter);
                    $sheet->setCellValue($colDonePer9Months . $highestRow, $percentSumTotal3Quarter);


                    //процент выполнения объектов , выставленных на продажу
                    $col3QuarterDonePerInSale = increment($colThirdQuarter,4);
                    $col3QuarterPlanInSale = increment($colThirdQuarter,1);
                    $colDonePer9MonthsInSale = increment($col9Months,4);

                    $planObjTotal3QuarterInSale = $sheet->getCell($col3QuarterPlanInSale.$objTotalRow)->getValue();
                    $planSquareTotal3QuarterInSale = $sheet->getCell($col3QuarterPlanInSale.$objSquareRow)->getValue();
                    $planSumTotal3QuarterInSale = $sheet->getCell($col3QuarterPlanInSale.$highestRow)->getValue();
                    if ($planObjTotal3QuarterInSale != 0){
                        $percentObjTotal3QuarterInSale = round($thirdQuarterFactObjTotal/$planObjTotal3QuarterInSale*100,0);
                    }
                    else{
                        $percentObjTotal3QuarterInSale = null;
                    }

                    if ($planSquareTotal3QuarterInSale != 0){
                        $percentSquareTotal3QuarterInSale = round($thirdQuarterFactSqTotal/$planSquareTotal3QuarterInSale*100,0);
                    }
                    else{
                        $percentSquareTotal3QuarterInSale = null;
                    }
                    if ($planSumTotal3QuarterInSale != 0){
                        $percentSumTotal3QuarterInSale = round($thirdQuarterFactPrice/$planSumTotal3QuarterInSale*100,0);
                    }
                    else{
                        $percentSumTotal3QuarterInSale = null;
                    }

                    $sheet->setCellValue($col3QuarterDonePerInSale . $objTotalRow, $percentObjTotal3QuarterInSale);
                    $sheet->setCellValue($col3QuarterDonePerInSale . $objSquareRow, $percentSquareTotal3QuarterInSale);
                    $sheet->setCellValue($col3QuarterDonePerInSale . $highestRow, $percentSumTotal3QuarterInSale);

                    $sheet->setCellValue($colDonePer9MonthsInSale . $objTotalRow, $percentObjTotal3QuarterInSale);
                    $sheet->setCellValue($colDonePer9MonthsInSale . $objSquareRow, $percentSquareTotal3QuarterInSale);
                    $sheet->setCellValue($colDonePer9MonthsInSale . $highestRow, $percentSumTotal3QuarterInSale);



                }

                //4 квартал
                if (isset($colFactFourthQuarter)){
                    $sheet->setCellValue($colFactFourthQuarter . $objTotalRow, $fourthQuarterFactObjTotal);
                    $sheet->setCellValue($colFactFourthQuarter . $objSquareRow, $fourthQuarterFactSqTotal);
                    $sheet->setCellValue($colFactFourthQuarter . $highestRow, $fourthQuarterFactPrice);
                    $sheet->getStyle($colFactFourthQuarter.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');


                    //процент выполнения
                    $colDonePer4Quarter = increment($colFourthQuarter,3);

                    $planObjTotal4Quarter = $sheet->getCell($colFourthQuarter.$objTotalRow)->getValue();
                    $planSquareTotal4Quarter = $sheet->getCell($colFourthQuarter.$objSquareRow)->getValue();
                    $planSumTotal4Quarter = $sheet->getCell($colFourthQuarter.$highestRow)->getValue();
                    if ($planObjTotal4Quarter != 0){
                        $percentObjTotal4Quarter = round($fourthQuarterFactObjTotal/$planObjTotal4Quarter*100,0);
                    }
                    else{
                        $percentObjTotal4Quarter = null;
                    }

                    if ($planSquareTotal4Quarter != 0){
                        $percentSquareTotal4Quarter = round($fourthQuarterFactSqTotal/$planSquareTotal4Quarter*100,0);
                    }
                    else{
                        $percentSquareTotal4Quarter = null;
                    }
                    if ($planSumTotal4Quarter != 0){
                        $percentSumTotal4Quarter = round($fourthQuarterFactPrice/$planSumTotal4Quarter*100,0);
                    }
                    else{
                        $percentSumTotal4Quarter = null;
                    }

                    $sheet->setCellValue($colDonePer4Quarter . $objTotalRow, $percentObjTotal4Quarter);
                    $sheet->setCellValue($colDonePer4Quarter . $objSquareRow, $percentSquareTotal4Quarter);
                    $sheet->setCellValue($colDonePer4Quarter . $highestRow, $percentSumTotal4Quarter);



                    //процент выполнения объектов , выставленных на продажу
                    $col4QuarterDonePerInSale = increment($colFourthQuarter,4);
                    $col4QuarterPlanInSale = increment($colFourthQuarter,1);


                    $planObjTotal4QuarterInSale = $sheet->getCell($col4QuarterPlanInSale.$objTotalRow)->getValue();
                    $planSquareTotal4QuarterInSale = $sheet->getCell($col4QuarterPlanInSale.$objSquareRow)->getValue();
                    $planSumTotal4QuarterInSale = $sheet->getCell($col4QuarterPlanInSale.$highestRow)->getValue();
                    if ($planObjTotal4QuarterInSale != 0){
                        $percentObjTotal4QuarterInSale = round($fourthQuarterFactObjTotal/$planObjTotal4QuarterInSale*100,0);
                    }
                    else{
                        $percentObjTotal4QuarterInSale = null;
                    }

                    if ($planSquareTotal4QuarterInSale != 0){
                        $percentSquareTotal4QuarterInSale = round($fourthQuarterFactSqTotal/$planSquareTotal4QuarterInSale*100,0);
                    }
                    else{
                        $percentSquareTotal4QuarterInSale = null;
                    }
                    if ($planSumTotal4QuarterInSale != 0){
                        $percentSumTotal4QuarterInSale = round($fourthQuarterFactPrice/$planSumTotal4QuarterInSale*100,0);
                    }
                    else{
                        $percentSumTotal4QuarterInSale = null;
                    }

                    $sheet->setCellValue($col4QuarterDonePerInSale . $objTotalRow, $percentObjTotal4QuarterInSale);
                    $sheet->setCellValue($col4QuarterDonePerInSale . $objSquareRow, $percentSquareTotal4QuarterInSale);
                    $sheet->setCellValue($col4QuarterDonePerInSale . $highestRow, $percentSumTotal4QuarterInSale);


                }


                    // год
                if (isset($colFactYear)){


                        $sheet->setCellValue($colFactYear . $objTotalRow, $yearFactObjTotal);
                        $sheet->setCellValue($colFactYear . $objSquareRow, $yearFactSqTotal);
                        $sheet->setCellValue($colFactYear . $highestRow, $yearFactPrice);
                    $sheet->getStyle($colFactYear.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                        //процент выполнения

                        $colDonePerYear = increment($colYear,3);
                        $planObjTotalYear = $sheet->getCell($colYear.$objTotalRow)->getValue();
                        $planSquareTotalYear = $sheet->getCell($colYear.$objSquareRow)->getValue();
                        $planSumTotalYear = $sheet->getCell($colYear.$highestRow)->getValue();
                        if ($planObjTotalYear != 0){
                            $percentObjTotalYear = round($yearFactObjTotal/$planObjTotalYear*100,0);
                        }
                        else{
                            $percentObjTotalYear = null;
                        }

                        if ($planSquareTotalYear != 0){
                            $percentSquareTotalYear = round($yearFactSqTotal/$planSquareTotalYear*100,0);
                        }
                        else{
                            $percentSquareTotalYear = null;
                        }
                        if ($planSumTotalYear != 0){
                            $percentSumTotalYear = round($yearFactPrice/$planSumTotalYear*100,0);
                        }
                        else{
                            $percentSumTotalYear = null;
                        }


                        $sheet->setCellValue($colDonePerYear . $objTotalRow, $percentObjTotalYear);
                        $sheet->setCellValue($colDonePerYear . $objSquareRow, $percentSquareTotalYear);
                        $sheet->setCellValue($colDonePerYear . $highestRow, $percentSumTotalYear);


                        //процент выполнения объектов , выставленных на продажу

                        $colYearPlanInSale = increment($colYear,1);
                        $colDonePerYearInSale = increment($colYear,4);

                        $planObjTotalYearInSale = $sheet->getCell($colYearPlanInSale.$objTotalRow)->getValue();
                        $planSquareTotalYearInSale = $sheet->getCell($colYearPlanInSale.$objSquareRow)->getValue();
                        $planSumTotalYearInSale = $sheet->getCell($colYearPlanInSale.$highestRow)->getValue();
                        if ($planObjTotalYearInSale != 0){
                            $percentObjTotalYearInSale = round($yearFactObjTotal/$planObjTotalYearInSale*100,0);
                        }
                        else{
                            $percentObjTotalYearInSale = null;
                        }

                        if ($planSquareTotalYearInSale != 0){
                            $percentSquareTotalYearInSale = round($yearFactSqTotal/$planSquareTotalYearInSale*100,0);
                        }
                        else{
                            $percentSquareTotalYearInSale = null;
                        }
                        if ($planSumTotalYearInSale != 0){
                            $percentSumTotalYearInSale = round($yearFactPrice/$planSumTotalYearInSale*100,0);
                        }
                        else{
                            $percentSumTotalYearInSale = null;
                        }


                        $sheet->setCellValue($colDonePerYearInSale . $objTotalRow, $percentObjTotalYearInSale);
                        $sheet->setCellValue($colDonePerYearInSale . $objSquareRow, $percentSquareTotalYearInSale);
                        $sheet->setCellValue($colDonePerYearInSale . $highestRow, $percentSumTotalYearInSale);



                    }

            }


            //реализация за период
            $salesByPeriod = DB::table('lead_params')
                ->select(DB::raw(
                    "

                            count(*) as count,
                            SUM(lead_params.contract_sum) as sum,
                            SUM(object_params.total_area) as square

                        "))
                ->join('object_params','object_params.object_id','=','lead_params.object_id')
                ->join('object_types','object_types.type_id','=','object_params.type_id')
                ->where('lead_params.stage','=',142)
                ->whereYear('lead_params.contract_date',$year)

                ->where('object_types.class_property','=',$property);

            //(минус неделя от текущего дня)
            $salesWeekBefore =
                $salesByPeriod
                    ->where('lead_params.contract_date','>=',$dateMinusWeek)
                    ->where('lead_params.contract_date','<=',$date);

            if ($salesWeekBefore->first()->count == 0){

                $count =0;
                $square = 0;
                $sum = 0;
            }
            else{

                $count =$salesWeekBefore->first()->count;
                $square = $salesWeekBefore->first()->square;
                $sum = $salesWeekBefore->first()->sum;
            }

            //реализация предыдущего дня

            $salesDayBefore =
                $salesByPeriod->whereDate('lead_params.contract_date','=',$dateMinusDay);


            if ($salesDayBefore->first()->count == 0){

                $countDB =0;
                $squareDB = 0;
                $sumDB = 0;
            }
            else{

                $countDB =$salesDayBefore->first()->count;
                $squareDB = $salesDayBefore->first()->square;
                $sumDB = $salesDayBefore->first()->sum;
            }

            //нереализованный остаток
            $unSaleSumTotal = DB::table('lead_params')
                ->select(DB::raw(
                    "


                            SUM(lead_params.contract_sum) as unsale_sum


                        "))
                ->join('object_params','object_params.object_id','=','lead_params.object_id')
                ->join('object_types','object_types.type_id','=','object_params.type_id')
                ->where('lead_params.stage','=',142)
                ->whereYear('lead_params.contract_date',$year)
                ->where('object_types.class_property','=',$property)
                ->whereIn('object_params.status_id',[1,2])
                ->first()
                ->unsale_sum;


            //Поступление денежных средств

            $incomePays = DB::table('lead_params')
                ->select(DB::raw(
                    "

                            SUM(IncomPays.sum) as income_sum

                        "))
                ->join('object_params','object_params.object_id','=','lead_params.object_id')
                ->join('object_types','object_types.type_id','=','object_params.type_id')
                ->join('IncomPays','IncomPays.contractNumber','=','lead_params.contract_number')
                ->where('lead_params.stage','=',142)
                ->where('object_types.class_property','=',$property);




            $allIncomes = $incomePays->first()->income_sum;

            $yearIncomes = $incomePays
                ->whereYear('lead_params.contract_date','=',$year)
                ->first()
                ->income_sum;
            $monthIncomes = $incomePays
                ->whereYear('IncomPays.incomDate',$year)
                ->whereMonth('IncomPays.incomDate',$month)
                ->first()
                ->income_sum;


            //Поступление ДС за текущий год
            $incomePaysCurrentYear = DB::table('lead_params')
                ->select(DB::raw(
                    "
                          MONTH(IncomPays.incomDate) as month,
                          SUM(IncomPays.sum) as income_sum_month

                        "))
                ->join('object_params','object_params.object_id','=','lead_params.object_id')
                ->join('object_types','object_types.type_id','=','object_params.type_id')
                ->join('IncomPays','IncomPays.contractNumber','=','lead_params.contract_number')
                ->where('lead_params.stage','=',142)
                ->where('object_types.class_property','=',$property)
                ->whereYear('IncomPays.incomDate',$year)
                ->whereMonth('IncomPays.incomDate','<=',$month)
                ->groupBy('month')
                ->get();



            $objCountRow =  $highestRow-2;
            $objSquareRow =  $highestRow-1;
            $objSumRow =  $highestRow;


            //реализация за период


            $colSalesByPeriod = increment($colYear,5);
            $sheet->setCellValue($colSalesByPeriod . $objCountRow, $count);
            $sheet->setCellValue($colSalesByPeriod . $objSquareRow, $square);
            $sheet->setCellValue($colSalesByPeriod . $objSumRow, $sum);
            $sheet->getStyle($colSalesByPeriod . $objSumRow)->getNumberFormat()->setFormatCode('### ### ### ###');

            //реализация предыдущего дня

            $colSalesDayBefore = increment($colYear,6);
            $sheet->setCellValue($colSalesDayBefore . $objCountRow, $countDB);
            $sheet->setCellValue($colSalesDayBefore . $objSquareRow, $squareDB);
            $sheet->setCellValue($colSalesDayBefore . $objSumRow, $sumDB);
            $sheet->getStyle($colSalesDayBefore . $objSumRow)->getNumberFormat()->setFormatCode('### ### ### ###');

            //НЕРЕАЛИЗОВАННЫЙ ОСТАТОК
            $unSaleBalanceCol = increment($colYear,7);


//            $propertyPlansData = DB::table('plans_objects')
//                ->select(DB::raw(
//                    "
//
//                    SUM(plans_objects.object_total) as plan_object_total,
//                    SUM(plans_objects.square_total) as plan_square_total,
//                    SUM(plans_objects.price) as plan_price
//
//                    "))
//                ->where('year', $year)
//                ->where('plans_objects.class_property','=',$property)
//                ->where('plans_objects.period_id', '<=', 12)
//                ->first();
//
//            $propertyFactData2 = DB::table('lead_params')
//                ->select(DB::raw(
//                    "
//                    SUM(lead_params.contract_sum) as fact_price,
//                    SUM(object_params.total_area) as fact_square_total
//
//                "))
//                ->join('object_params','object_params.object_id','=','lead_params.object_id')
//                ->join('object_types','object_types.type_id','=','object_params.type_id')
//                ->where('object_types.class_property','=',$property)
//                ->where('lead_params.stage','=',142)
//                ->whereYear('lead_params.contract_date',$year)
//                ->whereMonth('lead_params.contract_date','<=',$month)
//                ->first();




//          $sheet->setCellValue($unSaleBalanceCol . $objCountRow, $propertyPlansData->plan_object_total - count($propertyFactData));
//          $sheet->setCellValue($unSaleBalanceCol . $objSquareRow, $propertyPlansData->plan_square_total - $propertyFactData2->fact_square_total);
          $sheet->setCellValue($unSaleBalanceCol . $objSumRow, $unSaleSumTotal);
          $sheet->getStyle($unSaleBalanceCol . $objSumRow)->getNumberFormat()->setFormatCode('### ### ### ###');




            //////////////////////////////////////////////////
            ///////ПОСТУПЛЕНИЕ ДЕНЕЖНЫХ СРЕДСТВ/////////////
            /// /////////////////////////////////////////////


            //за все время
            $colIncomesAll = increment($colYear,8);
            $sheet->setCellValue($colIncomesAll . $highestRow, $allIncomes);
            $sheet->getStyle($colIncomesAll.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

            // по договорам заключеннным в текущем году
            $colIncomesYear = increment($colYear,9);
            $sheet->setCellValue($colIncomesYear . $highestRow, $yearIncomes);
            $sheet->getStyle($colIncomesYear.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

            //за текущий месяц
            $colIncomesMonth = increment($colYear,10);
            $sheet->setCellValue($colIncomesMonth . $highestRow, $monthIncomes);
            $sheet->getStyle($colIncomesMonth.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');


            //////////////////////////////////////////////////
            ///////Поступление ДС за текущий год/////////////
            /// /////////////////////////////////////////////

            $colIncomesForYear = increment($colYear,11);


            foreach ($incomePaysCurrentYear as $index=>$monthIncomesArr){

                $sheet->setCellValue($colIncomesForYear . $highestRow, $monthIncomesArr->income_sum_month);
                $sheet->getStyle($colIncomesForYear.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                $colIncomesForYear = increment($colIncomesForYear,1);

            }

            $sheet->getStyle($objSumRow)->getNumberFormat()->setFormatCode('### ### ### ###');


            $highestRow++;

        }

        $sheet->getStyle('A7:A'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $highestRow = $highestRow-1;

        $totalRow = $highestRow-($allPlans->count()*3);
        $sheet ->getStyle('A'.$totalRow.':'.$highestCol.$highestRow)->applyFromArray($styleBody);
        $sheet ->getStyle('A'.$totalRow.':'.$highestCol.$highestRow)->getFont()->setSize(12)->setBold(true);


        //заморозить столбцы и строки
        $sheet->freezePane('D7');

        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_sales.xlsx"');
        $writer->save("php://output");
    }



}
