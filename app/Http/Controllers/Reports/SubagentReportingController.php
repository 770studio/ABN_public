<?php

namespace App\Http\Controllers\Reports;

use App\SubagentParams;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SubagentReportingController extends Controller
{
    public function index(){

        $subagents = SubagentParams::all()->pluck('name','id');

        return view('reports.reporting_subagents.index',[
            'subagents'=>$subagents
        ]);
    }

    public function makeReport(Request $request){

        //проверяем месяц
        if ($request->get('month') ){


            $year = Carbon::now()->format('Y');
            $month = $request->get('month');

            $request->session()->put([
                'month_selected'=>$month
            ]);

            $month_rp = $request->get('month');


            $lastDayInMonth = date("t", strtotime($year.'-'.$month));


            $firstPeriodDay =  Carbon::createFromFormat('d.m.Y', '1.'.$month.'.'.$year )->format('d.m.Y');
            $lastPeriodDay =  Carbon::createFromFormat('d.m.Y', $lastDayInMonth.'.'.$month.'.'.$year )->format('d.m.Y');

            $df = Carbon::createFromFormat('d.m.Y', $firstPeriodDay);
            $dt = Carbon::createFromFormat('d.m.Y',$lastPeriodDay );


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

            //месяцы в род падеже
            switch ($month_rp) {
                case 1:
                    $month_rp =  "января";
                    break;
                case 2:
                    $month_rp = "февраля";
                    break;
                case 3:
                    $month_rp = "марта";
                    break;
                case 4:
                    $month_rp = "апреля";
                    break;
                case 5:
                    $month_rp =  "мая";
                    break;
                case 6:
                    $month_rp = "июня";
                    break;
                case 7:
                    $month_rp = "июля";
                    break;
                case 8:
                    $month_rp = "августа";
                    break;
                case 9:
                    $month_rp =  "сентября";
                    break;
                case 10:
                    $month_rp = "октября";
                    break;
                case 11:
                    $month_rp = "ноября";
                    break;
                case 12:
                    $month_rp = "декабря";
                    break;
            }


        }
        else{
            return redirect()->back()->with('status','Не выбран период');
        }
        //проверяем субагента
        if ($request->get('subagent')){

            $subagent_id = $request->get('subagent');
            $request->session()->put([
                'subagent'=>$subagent_id
            ]);
        }
        else{
            return redirect()->back()->with('status','Не выбран субагенты');
        }
        //получаем субагента
        $subagent = SubagentParams::findOrFail($subagent_id);

        $data = DB::table('IncomPays')
            ->select(DB::raw(
                "
                lead_params.subagent_name,
                lead_params.is_subagent,
                lead_params.contract_type,
                lead_contract_type.contract_name,
                object_params.owner,
                object_params.address,
                object_params.house_number,
                object_params.object_number,
                object_params.rooms_number,
                object_types.class_property,
                object_params.total_area,
                object_params.BTI_area,
                contacts.name as client_name,
                IncomPays.contractNumber,
                lead_params.contract_date,
                lead_params.contract_sum,
                lead_params.filing_date,
                SUM(IncomPays.sum) as income_sum

                "))
            ->whereNotNull('IncomPays.contractNumber')
            ->whereNotIn('IncomPays.contractNumber',['','б/н'])
            ->join('lead_params','lead_params.contract_number','=','IncomPays.contractNumber')
            ->join('lead_contract_type','lead_contract_type.contract_type_id','=','lead_params.contract_type')
            ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
            ->join('object_types','object_types.type_id','=','object_params.type_id')
            ->join('contacts','contacts.contact_id','=','lead_params.client_id')
            ->where('subagent_name',$subagent->name)
            ->where('IncomPays.incomDate','>=',$df)
            ->where('IncomPays.incomDate','<=',$dt)
            ->groupBy('IncomPays.contractNumber')
            ->get();

        dd($data);

        //общая сумма поступлений
        $totalIncomeSum = 0;
        foreach ($data as $incomeItem){
            $totalIncomeSum+=$incomeItem->income_sum;
        }


        //получаем квартиры
        $objectsByPervichka = $data
            ->where('class_property','pervichka')
            ->sortBy('house_number');

        //получаем офисы
        $objectsByCommercial = $data
            ->where('class_property','commercial')
            ->sortBy('house_number');

        //получаем паркинги
        $objectsByParking = $data
            ->where('class_property','parking')
            ->sortBy('house_number');
        //получаем кладовки
        $objectsByPantry = $data
            ->where('class_property','pantry')
            ->sortBy('house_number');




        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет');

        //колонка A
        $sheet->getColumnDimension('A')->setWidth(2);
        //Наименование субагента
        $sheet->setCellValue('B1',$subagent->name )->getStyle("B1")->getFont()->setSize(14);
        $sheet->mergeCells('B1:O1');
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->getStyle('B1')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        //делаем массив из реквизитов субагента
        $requisitesArr = array(
            'ИНН'=>$subagent->inn,
            'ОГРН'=>$subagent->ogrn,
            'Расчетный счет'=>$subagent->rs,
            'Банк'=>$subagent->bank_name,
            'БИК'=>$subagent->bik,
            'Корр.счет'=>$subagent->ks

        );

        //$requisitesArr = explode(',',$subagent->requisites);

        //след.строка
        $highestRow = 2;

        foreach ($requisitesArr as $key=>$rowReqItem){
            if ($rowReqItem != null){
                $sheet->setCellValue('B'.$highestRow,$key.': '.$rowReqItem );
                $highestRow++;
            }

        }

        //берем последнюю строку
        $highestRow = $sheet->getHighestRow();

        //след строка
        $nextRow  = $highestRow+3;

        //формат шапки
        $sheet->mergeCells('B'.$nextRow.':O'.$nextRow);
        $sheet->getRowDimension($nextRow)->setRowHeight(50);
        $sheet->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        //шапка листа
        $sheet->setCellValue('B'.$nextRow, $subagent->name.', именуемый в дальнейшем «Субагент», в лице '.
            $subagent->head_name_2.', действующего на основании '.$subagent->base_of_rules.', составил настоящий отчет за период  с '.
            '«1»'.$month_rp.' '.$year.'г. по «'.$lastDayInMonth.'»'.$month_rp.' '.$year.'г. о нижеследующем:');


        //берем последнюю строку
        $highestRow = $sheet->getHighestRow();
        //след.строка
        $nextRow  = $highestRow+1;

        //формат строки
        $sheet->mergeCells('B'.$nextRow.':O'.$nextRow);
        $sheet->getRowDimension($nextRow)->setRowHeight(30);
        $sheet->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('B'.$nextRow,'1. В соответствии с Субагентским  Договором №'.
            $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').
            ' Субагент  организовал:- реализацию имущества путем заключения договоров купли-продажи;'
        );

        //берем последнюю строку
        $highestRow = $sheet->getHighestRow();
        //след.строка
        $nextRow  = $highestRow+1;

        //формат строки
        $sheet->mergeCells('B'.$nextRow.':O'.$nextRow);
        $sheet->getRowDimension($nextRow)->setRowHeight(30);
        $sheet->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('B'.$nextRow,'2. При выполнении поручения доходы от продажи площадей в рамках договора: №'.
            $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').
            ' за период с '.$firstPeriodDay.'г. по '.$lastPeriodDay.'г. составили: ' .number_format($totalIncomeSum, 2, ',', ' ') .' руб. в том числе: '
        );

        $coef = 0.01;//TODO уточнить про коэффициэнты

         //если есть первичка
        if (count($objectsByPervichka)>0){
            //берем последнюю строку
            $highestRow = $sheet->getHighestRow();
            //след.строка
            $nextRow  = $highestRow+1;

            //мердж строка
            $mergeNextRow = $highestRow+2;

            $sheet->mergeCells('B'.$nextRow.':B'.$mergeNextRow);
            $sheet->mergeCells('C'.$nextRow.':C'.$mergeNextRow);
            $sheet->mergeCells('D'.$nextRow.':D'.$mergeNextRow);
            $sheet->mergeCells('E'.$nextRow.':E'.$mergeNextRow);
            $sheet->mergeCells('F'.$nextRow.':F'.$mergeNextRow);
            $sheet->mergeCells('G'.$nextRow.':G'.$mergeNextRow);
            $sheet->mergeCells('H'.$nextRow.':H'.$mergeNextRow);
            $sheet->mergeCells('I'.$nextRow.':I'.$mergeNextRow);
            $sheet->mergeCells('J'.$nextRow.':J'.$mergeNextRow);
            $sheet->mergeCells('K'.$nextRow.':K'.$mergeNextRow);
            $sheet->mergeCells('L'.$nextRow.':L'.$mergeNextRow);
            $sheet->mergeCells('O'.$nextRow.':O'.$mergeNextRow);


            $sheet->getColumnDimension('B')->setWidth(7);
            $sheet->getColumnDimension('C')->setWidth(5);
            $sheet->getColumnDimension('D')->setWidth(5);
            $sheet->getColumnDimension('E')->setWidth(7);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(10);
            $sheet->getColumnDimension('I')->setWidth(10);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(30);
            $sheet->getColumnDimension('O')->setWidth(15);

            $styleHeader = [
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

                'font'  => [
                    'bold'  => true,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];
            $styleBody = [
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

                'font'  => [
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];

            //шапка таблицы
            $sheet->setCellValue('B'.$nextRow,'стр. № дома');
            $sheet->setCellValue('C'.$nextRow,'№ квартиры');
            $sheet->setCellValue('D'.$nextRow,'Кол-во комнат');
            $sheet->setCellValue('E'.$nextRow,'Коэффициент (%)');
            $sheet->setCellValue('F'.$nextRow,'Покупатель');
            $sheet->setCellValue('G'.$nextRow,'№ договора');
            $sheet->setCellValue('H'.$nextRow,'Дата договора');
            $sheet->setCellValue('I'.$nextRow,'Вид договора');
            $sheet->setCellValue('J'.$nextRow,'S, м2');
            $sheet->setCellValue('K'.$nextRow,'Стоимость реализации 1 кв.м, руб.');
            $sheet->setCellValue('L'.$nextRow,'Стоимость по договору КП, руб.');

            $sheet->setCellValue('M'.$nextRow,'Поступления , руб.');
            $sheet->mergeCells('M'.$nextRow.':N'.$nextRow);
            $sheet->setCellValue('M'.$mergeNextRow,'нарастающим итогом');
            $sheet->setCellValue('N'.$mergeNextRow,'за отчетный период');

            $sheet->setCellValue('O'.$nextRow,'Задолженность, руб.');



            $sheet->getRowDimension($nextRow)->setRowHeight(50);
            $sheet->getRowDimension($mergeNextRow)->setRowHeight(25);
            $sheet->getColumnDimension('M')->setWidth(15);
            $sheet->getColumnDimension('N')->setWidth(15);
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('M'.$mergeNextRow.':N'.$mergeNextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet ->getStyle('B'.$nextRow .':O'.$mergeNextRow)->applyFromArray($styleHeader);

            $nextRow = $mergeNextRow+1;

            //переменные для строки всего
            $SUM_TOTAL_AREA = 0;
            $SUM_TOTAL_CONTRACT_SUM = 0;
            $SUM_TOTAL_NARITOG = 0;
            $SUM_TOTAL_INCOMES = 0;
            $SUM_TOTAL_DEBT = 0;


            foreach ($objectsByPervichka as $key=>$houseData){

                $sheet->setCellValue('B'.$nextRow,$houseData->house_number);
                $sheet->setCellValue('C'.$nextRow,$houseData->object_number);
                $sheet->setCellValue('D'.$nextRow,$houseData->rooms_number);
                $sheet->setCellValue('E'.$nextRow,$coef);
                $sheet->setCellValue('F'.$nextRow,$houseData->client_name);
                $sheet->setCellValue('G'.$nextRow,$houseData->contractNumber);
                $sheet->setCellValue('H'.$nextRow,$houseData->contract_date);
                $sheet->setCellValue('I'.$nextRow,$houseData->contract_name);
                $sheet->setCellValue('J'.$nextRow,$houseData->total_area);

                $SUM_TOTAL_AREA+=$houseData->total_area;//для строки всего

                //Стоимость реализации 1 кв.м, руб.
                if ($houseData->BTI_area == '0'||$houseData->BTI_area=='0,0'||$houseData->BTI_area == null){
                    $oneMetr = round($houseData->contract_sum/$houseData->total_area);
                }
                else{
                    $oneMetr = round($houseData->contract_sum/$houseData->BTI_area);
                }
                $sheet->setCellValue('K'.$nextRow,$oneMetr);
                if ($oneMetr != 0){
                    $sheet->getStyle('K'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $sheet->setCellValue('L'.$nextRow,$houseData->contract_sum);
                $SUM_TOTAL_CONTRACT_SUM+=$houseData->contract_sum;//для строки всего
                if ($houseData->contract_sum != 0){
                    $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //поступления нарастающим итогом

                $totalSumByObject = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                          SUM(IncomPays.sum) as income_sum_tot
                "))
                    ->where('IncomPays.contractNumber','=',$houseData->contractNumber)
                    ->where('IncomPays.incomDate','<=',$dt)
                    ->first('income_sum');
                $sheet->setCellValue('M'.$nextRow,$totalSumByObject->income_sum_tot);

                $SUM_TOTAL_NARITOG+=$totalSumByObject->income_sum_tot;//для строки всего

                if ($totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                //за отчетный период
                $sheet->setCellValue('N'.$nextRow,$houseData->income_sum);

                $SUM_TOTAL_INCOMES+=$houseData->income_sum;//для строки всего

                if ($houseData->income_sum != 0){
                    $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //задолженность
                $sheet->setCellValue('O'.$nextRow, $houseData->contract_sum - $totalSumByObject->income_sum_tot);

                $SUM_TOTAL_DEBT+=$houseData->contract_sum - $totalSumByObject->income_sum_tot;//для строки всего

                if ($houseData->contract_sum - $totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleBody);

                $nextRow++;
            }

            //строка всего
            $sheet->mergeCells('B'.$nextRow.':F'.$nextRow);
            $sheet->mergeCells('H'.$nextRow.':I'.$nextRow);
            $sheet->setCellValue('G'.$nextRow,'Всего');
            $sheet->setCellValue('J'.$nextRow,$SUM_TOTAL_AREA);
            $sheet->setCellValue('L'.$nextRow,$SUM_TOTAL_CONTRACT_SUM);
            if ($SUM_TOTAL_CONTRACT_SUM != 0){
                $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('M'.$nextRow,$SUM_TOTAL_NARITOG);
            if ($SUM_TOTAL_NARITOG != 0){
                $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('N'.$nextRow,$SUM_TOTAL_INCOMES);
            if ($SUM_TOTAL_INCOMES != 0){
                $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('O'.$nextRow,$SUM_TOTAL_DEBT);
            if ($SUM_TOTAL_DEBT != 0){
                $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleHeader);

        }
        //если есть офисы
        if(count($objectsByCommercial)>0){

            //берем последнюю строку
            $highestRow = $sheet->getHighestRow();
            //след.строка
            $nextRow  = $highestRow+1;

            //мердж строка
            $mergeNextRow = $highestRow+2;

            $sheet->mergeCells('B'.$nextRow.':B'.$mergeNextRow);
            $sheet->mergeCells('C'.$nextRow.':C'.$mergeNextRow);
            $sheet->mergeCells('D'.$nextRow.':D'.$mergeNextRow);
            $sheet->mergeCells('E'.$nextRow.':E'.$mergeNextRow);
            $sheet->mergeCells('F'.$nextRow.':F'.$mergeNextRow);
            $sheet->mergeCells('G'.$nextRow.':G'.$mergeNextRow);
            $sheet->mergeCells('H'.$nextRow.':H'.$mergeNextRow);
            $sheet->mergeCells('I'.$nextRow.':I'.$mergeNextRow);
            $sheet->mergeCells('J'.$nextRow.':J'.$mergeNextRow);
            $sheet->mergeCells('K'.$nextRow.':K'.$mergeNextRow);
            $sheet->mergeCells('L'.$nextRow.':L'.$mergeNextRow);
            $sheet->mergeCells('O'.$nextRow.':O'.$mergeNextRow);


            $sheet->getColumnDimension('B')->setWidth(7);
            $sheet->getColumnDimension('C')->setWidth(5);
            $sheet->getColumnDimension('D')->setWidth(5);
            $sheet->getColumnDimension('E')->setWidth(7);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(10);
            $sheet->getColumnDimension('I')->setWidth(10);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(30);
            $sheet->getColumnDimension('O')->setWidth(15);

            $styleHeader = [
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

                'font'  => [
                    'bold'  => true,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];
            $styleBody = [
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

                'font'  => [
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];

            //шапка таблицы
            $sheet->setCellValue('B'.$nextRow,'стр. № дома');
            $sheet->setCellValue('C'.$nextRow,'№ квартиры');
            $sheet->setCellValue('D'.$nextRow,'Кол-во комнат');
            $sheet->setCellValue('E'.$nextRow,'Коэффициент (%)');
            $sheet->setCellValue('F'.$nextRow,'Покупатель');
            $sheet->setCellValue('G'.$nextRow,'№ договора');
            $sheet->setCellValue('H'.$nextRow,'Дата договора');
            $sheet->setCellValue('I'.$nextRow,'Вид договора');
            $sheet->setCellValue('J'.$nextRow,'S, м2');
            $sheet->setCellValue('K'.$nextRow,'Стоимость реализации 1 кв.м, руб.');
            $sheet->setCellValue('L'.$nextRow,'Стоимость по договору КП, руб.');

            $sheet->setCellValue('M'.$nextRow,'Поступления , руб.');
            $sheet->mergeCells('M'.$nextRow.':N'.$nextRow);
            $sheet->setCellValue('M'.$mergeNextRow,'нарастающим итогом');
            $sheet->setCellValue('N'.$mergeNextRow,'за отчетный период');

            $sheet->setCellValue('O'.$nextRow,'Задолженность, руб.');



            $sheet->getRowDimension($nextRow)->setRowHeight(50);
            $sheet->getRowDimension($mergeNextRow)->setRowHeight(25);
            $sheet->getColumnDimension('M')->setWidth(15);
            $sheet->getColumnDimension('N')->setWidth(15);
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('M'.$mergeNextRow.':N'.$mergeNextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet ->getStyle('B'.$nextRow .':O'.$mergeNextRow)->applyFromArray($styleHeader);

            $nextRow = $mergeNextRow+1;

            //переменные для строки всего
            $SUM_TOTAL_AREA_OFFICE = 0;
            $SUM_TOTAL_CONTRACT_SUM_OFFICE = 0;
            $SUM_TOTAL_NARITOG_OFFICE = 0;
            $SUM_TOTAL_INCOMES_OFFICE = 0;
            $SUM_TOTAL_DEBT_OFFICE = 0;


            foreach ($objectsByCommercial as $key=>$houseData){

                $sheet->setCellValue('B'.$nextRow,$houseData->house_number);
                $sheet->setCellValue('C'.$nextRow,$houseData->object_number);
                $sheet->setCellValue('D'.$nextRow,$houseData->rooms_number);
                $sheet->setCellValue('E'.$nextRow,$coef);
                $sheet->setCellValue('F'.$nextRow,$houseData->client_name);
                $sheet->setCellValue('G'.$nextRow,$houseData->contractNumber);
                $sheet->setCellValue('H'.$nextRow,$houseData->contract_date);
                $sheet->setCellValue('I'.$nextRow,$houseData->contract_name);
                $sheet->setCellValue('J'.$nextRow,$houseData->total_area);

                $SUM_TOTAL_AREA_OFFICE+=$houseData->total_area;//для строки всего

                //Стоимость реализации 1 кв.м, руб.
                if ($houseData->BTI_area == '0'||$houseData->BTI_area=='0,0'||$houseData->BTI_area == null){
                    $oneMetr = round($houseData->contract_sum/$houseData->total_area);
                }
                else{
                    $oneMetr = round($houseData->contract_sum/$houseData->BTI_area);
                }
                $sheet->setCellValue('K'.$nextRow,$oneMetr);
                if ($oneMetr != 0){
                    $sheet->getStyle('K'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $sheet->setCellValue('L'.$nextRow,$houseData->contract_sum);
                $SUM_TOTAL_CONTRACT_SUM_OFFICE+=$houseData->contract_sum;//для строки всего
                if ($houseData->contract_sum != 0){
                    $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //поступления нарастающим итогом

                $totalSumByObject = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                          SUM(IncomPays.sum) as income_sum_tot
                "))
                    ->where('IncomPays.contractNumber','=',$houseData->contractNumber)
                    ->where('IncomPays.incomDate','<=',$dt)
                    ->first('income_sum');
                $sheet->setCellValue('M'.$nextRow,$totalSumByObject->income_sum_tot);

                $SUM_TOTAL_NARITOG_OFFICE+=$totalSumByObject->income_sum_tot;//для строки всего

                if ($totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                //за отчетный период
                $sheet->setCellValue('N'.$nextRow,$houseData->income_sum);

                $SUM_TOTAL_INCOMES_OFFICE+=$houseData->income_sum;//для строки всего

                if ($houseData->income_sum != 0){
                    $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //задолженность
                $sheet->setCellValue('O'.$nextRow, $houseData->contract_sum - $totalSumByObject->income_sum_tot);

                $SUM_TOTAL_DEBT_OFFICE+=$houseData->contract_sum - $totalSumByObject->income_sum_tot;//для строки всего

                if ($houseData->contract_sum - $totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleBody);

                $nextRow++;
            }

            //строка всего
            $sheet->mergeCells('B'.$nextRow.':F'.$nextRow);
            $sheet->mergeCells('H'.$nextRow.':I'.$nextRow);
            $sheet->setCellValue('G'.$nextRow,'Всего');
            $sheet->setCellValue('J'.$nextRow,$SUM_TOTAL_AREA_OFFICE);
            $sheet->setCellValue('L'.$nextRow,$SUM_TOTAL_CONTRACT_SUM_OFFICE);
            if ($SUM_TOTAL_CONTRACT_SUM_OFFICE != 0){
                $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('M'.$nextRow,$SUM_TOTAL_NARITOG_OFFICE);
            if ($SUM_TOTAL_NARITOG_OFFICE != 0){
                $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('N'.$nextRow,$SUM_TOTAL_INCOMES_OFFICE);
            if ($SUM_TOTAL_INCOMES_OFFICE != 0){
                $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('O'.$nextRow,$SUM_TOTAL_DEBT_OFFICE);
            if ($SUM_TOTAL_DEBT_OFFICE != 0){
                $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleHeader);

        }
        //если есть кладовки
        if(count($objectsByPantry)>0){
            //берем последнюю строку
            $highestRow = $sheet->getHighestRow();
            //след.строка
            $nextRow  = $highestRow+1;

            //мердж строка
            $mergeNextRow = $highestRow+2;

            $sheet->mergeCells('B'.$nextRow.':B'.$mergeNextRow);
            $sheet->mergeCells('C'.$nextRow.':C'.$mergeNextRow);
            $sheet->mergeCells('D'.$nextRow.':D'.$mergeNextRow);
            $sheet->mergeCells('E'.$nextRow.':E'.$mergeNextRow);
            $sheet->mergeCells('F'.$nextRow.':F'.$mergeNextRow);
            $sheet->mergeCells('G'.$nextRow.':G'.$mergeNextRow);
            $sheet->mergeCells('H'.$nextRow.':H'.$mergeNextRow);
            $sheet->mergeCells('I'.$nextRow.':I'.$mergeNextRow);
            $sheet->mergeCells('J'.$nextRow.':J'.$mergeNextRow);
            $sheet->mergeCells('K'.$nextRow.':K'.$mergeNextRow);
            $sheet->mergeCells('L'.$nextRow.':L'.$mergeNextRow);
            $sheet->mergeCells('O'.$nextRow.':O'.$mergeNextRow);


            $sheet->getColumnDimension('B')->setWidth(7);
            $sheet->getColumnDimension('C')->setWidth(5);
            $sheet->getColumnDimension('D')->setWidth(5);
            $sheet->getColumnDimension('E')->setWidth(7);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(10);
            $sheet->getColumnDimension('I')->setWidth(10);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(30);
            $sheet->getColumnDimension('O')->setWidth(15);

            $styleHeader = [
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

                'font'  => [
                    'bold'  => true,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];
            $styleBody = [
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

                'font'  => [
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];

            //шапка таблицы
            $sheet->setCellValue('B'.$nextRow,'стр. № дома');
            $sheet->setCellValue('C'.$nextRow,'№ квартиры');
            $sheet->setCellValue('D'.$nextRow,'Кол-во комнат');
            $sheet->setCellValue('E'.$nextRow,'Коэффициент (%)');
            $sheet->setCellValue('F'.$nextRow,'Покупатель');
            $sheet->setCellValue('G'.$nextRow,'№ договора');
            $sheet->setCellValue('H'.$nextRow,'Дата договора');
            $sheet->setCellValue('I'.$nextRow,'Вид договора');
            $sheet->setCellValue('J'.$nextRow,'S, м2');
            $sheet->setCellValue('K'.$nextRow,'Стоимость реализации 1 кв.м, руб.');
            $sheet->setCellValue('L'.$nextRow,'Стоимость по договору КП, руб.');

            $sheet->setCellValue('M'.$nextRow,'Поступления , руб.');
            $sheet->mergeCells('M'.$nextRow.':N'.$nextRow);
            $sheet->setCellValue('M'.$mergeNextRow,'нарастающим итогом');
            $sheet->setCellValue('N'.$mergeNextRow,'за отчетный период');

            $sheet->setCellValue('O'.$nextRow,'Задолженность, руб.');



            $sheet->getRowDimension($nextRow)->setRowHeight(50);
            $sheet->getRowDimension($mergeNextRow)->setRowHeight(25);
            $sheet->getColumnDimension('M')->setWidth(15);
            $sheet->getColumnDimension('N')->setWidth(15);
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('M'.$mergeNextRow.':N'.$mergeNextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet ->getStyle('B'.$nextRow .':O'.$mergeNextRow)->applyFromArray($styleHeader);

            $nextRow = $mergeNextRow+1;

            //переменные для строки всего
            $SUM_TOTAL_AREA_PANTRY = 0;
            $SUM_TOTAL_CONTRACT_SUM_PANTRY = 0;
            $SUM_TOTAL_NARITOG_PANTRY = 0;
            $SUM_TOTAL_INCOMES_PANTRY = 0;
            $SUM_TOTAL_DEBT_PANTRY = 0;


            foreach ($objectsByPantry as $key=>$houseData){

                $sheet->setCellValue('B'.$nextRow,$houseData->house_number);
                $sheet->setCellValue('C'.$nextRow,$houseData->object_number);
                $sheet->setCellValue('D'.$nextRow,$houseData->rooms_number);
                $sheet->setCellValue('E'.$nextRow,$coef);
                $sheet->setCellValue('F'.$nextRow,$houseData->client_name);
                $sheet->setCellValue('G'.$nextRow,$houseData->contractNumber);
                $sheet->setCellValue('H'.$nextRow,$houseData->contract_date);
                $sheet->setCellValue('I'.$nextRow,$houseData->contract_name);
                $sheet->setCellValue('J'.$nextRow,$houseData->total_area);

                $SUM_TOTAL_AREA_PANTRY+=$houseData->total_area;//для строки всего

                //Стоимость реализации 1 кв.м, руб.
                if ($houseData->BTI_area == '0'||$houseData->BTI_area=='0,0'||$houseData->BTI_area == null){
                    $oneMetr = round($houseData->contract_sum/$houseData->total_area);
                }
                else{
                    $oneMetr = round($houseData->contract_sum/$houseData->BTI_area);
                }
                $sheet->setCellValue('K'.$nextRow,$oneMetr);
                if ($oneMetr != 0){
                    $sheet->getStyle('K'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $sheet->setCellValue('L'.$nextRow,$houseData->contract_sum);
                $SUM_TOTAL_CONTRACT_SUM_PANTRY+=$houseData->contract_sum;//для строки всего
                if ($houseData->contract_sum != 0){
                    $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //поступления нарастающим итогом

                $totalSumByObject = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                          SUM(IncomPays.sum) as income_sum_tot
                "))
                    ->where('IncomPays.contractNumber','=',$houseData->contractNumber)
                    ->where('IncomPays.incomDate','<=',$dt)
                    ->first('income_sum');
                $sheet->setCellValue('M'.$nextRow,$totalSumByObject->income_sum_tot);

                $SUM_TOTAL_NARITOG_PANTRY+=$totalSumByObject->income_sum_tot;//для строки всего

                if ($totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                //за отчетный период
                $sheet->setCellValue('N'.$nextRow,$houseData->income_sum);

                $SUM_TOTAL_INCOMES_PANTRY+=$houseData->income_sum;//для строки всего

                if ($houseData->income_sum != 0){
                    $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //задолженность
                $sheet->setCellValue('O'.$nextRow, $houseData->contract_sum - $totalSumByObject->income_sum_tot);

                $SUM_TOTAL_DEBT_PANTRY+=$houseData->contract_sum - $totalSumByObject->income_sum_tot;//для строки всего

                if ($houseData->contract_sum - $totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleBody);

                $nextRow++;
            }

            //строка всего
            $sheet->mergeCells('B'.$nextRow.':F'.$nextRow);
            $sheet->mergeCells('H'.$nextRow.':I'.$nextRow);
            $sheet->setCellValue('G'.$nextRow,'Всего');
            $sheet->setCellValue('J'.$nextRow,$SUM_TOTAL_AREA_PANTRY);
            $sheet->setCellValue('L'.$nextRow,$SUM_TOTAL_CONTRACT_SUM_PANTRY);
            if ($SUM_TOTAL_CONTRACT_SUM_PANTRY != 0){
                $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('M'.$nextRow,$SUM_TOTAL_NARITOG_PANTRY);
            if ($SUM_TOTAL_NARITOG_PANTRY != 0){
                $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('N'.$nextRow,$SUM_TOTAL_INCOMES_PANTRY);
            if ($SUM_TOTAL_INCOMES_PANTRY != 0){
                $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('O'.$nextRow,$SUM_TOTAL_DEBT_PANTRY);
            if ($SUM_TOTAL_DEBT_PANTRY != 0){
                $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleHeader);
        }
        //если есть паркинги
        if(count($objectsByParking)>0){
            //берем последнюю строку
            $highestRow = $sheet->getHighestRow();
            //след.строка
            $nextRow  = $highestRow+1;

            //мердж строка
            $mergeNextRow = $highestRow+2;

            $sheet->mergeCells('B'.$nextRow.':B'.$mergeNextRow);
            $sheet->mergeCells('C'.$nextRow.':C'.$mergeNextRow);
            $sheet->mergeCells('D'.$nextRow.':D'.$mergeNextRow);
            $sheet->mergeCells('E'.$nextRow.':E'.$mergeNextRow);
            $sheet->mergeCells('F'.$nextRow.':F'.$mergeNextRow);
            $sheet->mergeCells('G'.$nextRow.':G'.$mergeNextRow);
            $sheet->mergeCells('H'.$nextRow.':H'.$mergeNextRow);
            $sheet->mergeCells('I'.$nextRow.':I'.$mergeNextRow);
            $sheet->mergeCells('J'.$nextRow.':J'.$mergeNextRow);
            $sheet->mergeCells('K'.$nextRow.':K'.$mergeNextRow);
            $sheet->mergeCells('L'.$nextRow.':L'.$mergeNextRow);
            $sheet->mergeCells('O'.$nextRow.':O'.$mergeNextRow);


            $sheet->getColumnDimension('B')->setWidth(7);
            $sheet->getColumnDimension('C')->setWidth(5);
            $sheet->getColumnDimension('D')->setWidth(5);
            $sheet->getColumnDimension('E')->setWidth(7);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(10);
            $sheet->getColumnDimension('I')->setWidth(10);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('J')->setWidth(7);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(30);
            $sheet->getColumnDimension('O')->setWidth(15);

            $styleHeader = [
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

                'font'  => [
                    'bold'  => true,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];
            $styleBody = [
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

                'font'  => [
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 9,

                ]

            ];

            //шапка таблицы
            $sheet->setCellValue('B'.$nextRow,'стр. № дома');
            $sheet->setCellValue('C'.$nextRow,'№ квартиры');
            $sheet->setCellValue('D'.$nextRow,'Кол-во комнат');
            $sheet->setCellValue('E'.$nextRow,'Коэффициент (%)');
            $sheet->setCellValue('F'.$nextRow,'Покупатель');
            $sheet->setCellValue('G'.$nextRow,'№ договора');
            $sheet->setCellValue('H'.$nextRow,'Дата договора');
            $sheet->setCellValue('I'.$nextRow,'Вид договора');
            $sheet->setCellValue('J'.$nextRow,'S, м2');
            $sheet->setCellValue('K'.$nextRow,'Стоимость реализации 1 кв.м, руб.');
            $sheet->setCellValue('L'.$nextRow,'Стоимость по договору КП, руб.');

            $sheet->setCellValue('M'.$nextRow,'Поступления , руб.');
            $sheet->mergeCells('M'.$nextRow.':N'.$nextRow);
            $sheet->setCellValue('M'.$mergeNextRow,'нарастающим итогом');
            $sheet->setCellValue('N'.$mergeNextRow,'за отчетный период');

            $sheet->setCellValue('O'.$nextRow,'Задолженность, руб.');



            $sheet->getRowDimension($nextRow)->setRowHeight(50);
            $sheet->getRowDimension($mergeNextRow)->setRowHeight(25);
            $sheet->getColumnDimension('M')->setWidth(15);
            $sheet->getColumnDimension('N')->setWidth(15);
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('M'.$mergeNextRow.':N'.$mergeNextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet ->getStyle('B'.$nextRow .':O'.$mergeNextRow)->applyFromArray($styleHeader);

            $nextRow = $mergeNextRow+1;

            //переменные для строки всего
            $SUM_TOTAL_AREA_PARKING = 0;
            $SUM_TOTAL_CONTRACT_SUM_PARKING = 0;
            $SUM_TOTAL_NARITOG_PARKING = 0;
            $SUM_TOTAL_INCOMES_PARKING = 0;
            $SUM_TOTAL_DEBT_PARKING = 0;


            foreach ($objectsByParking as $key=>$houseData){

                $sheet->setCellValue('B'.$nextRow,$houseData->house_number);
                $sheet->setCellValue('C'.$nextRow,$houseData->object_number);
                $sheet->setCellValue('D'.$nextRow,$houseData->rooms_number);
                $sheet->setCellValue('E'.$nextRow,$coef);
                $sheet->setCellValue('F'.$nextRow,$houseData->client_name);
                $sheet->setCellValue('G'.$nextRow,$houseData->contractNumber);
                $sheet->setCellValue('H'.$nextRow,$houseData->contract_date);
                $sheet->setCellValue('I'.$nextRow,$houseData->contract_name);
                $sheet->setCellValue('J'.$nextRow,$houseData->total_area);

                $SUM_TOTAL_AREA_PARKING+=$houseData->total_area;//для строки всего

                //Стоимость реализации 1 кв.м, руб.
                if ($houseData->BTI_area == '0'||$houseData->BTI_area=='0,0'||$houseData->BTI_area == null){
                    $oneMetr = round($houseData->contract_sum/$houseData->total_area);
                }
                else{
                    $oneMetr = round($houseData->contract_sum/$houseData->BTI_area);
                }
                $sheet->setCellValue('K'.$nextRow,$oneMetr);
                if ($oneMetr != 0){
                    $sheet->getStyle('K'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $sheet->setCellValue('L'.$nextRow,$houseData->contract_sum);
                $SUM_TOTAL_CONTRACT_SUM_PARKING+=$houseData->contract_sum;//для строки всего
                if ($houseData->contract_sum != 0){
                    $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //поступления нарастающим итогом

                $totalSumByObject = DB::table('IncomPays')
                    ->select(DB::raw(
                        "
                          SUM(IncomPays.sum) as income_sum_tot
                "))
                    ->where('IncomPays.contractNumber','=',$houseData->contractNumber)
                    ->where('IncomPays.incomDate','<=',$dt)
                    ->first('income_sum');
                $sheet->setCellValue('M'.$nextRow,$totalSumByObject->income_sum_tot);

                $SUM_TOTAL_NARITOG_PARKING+=$totalSumByObject->income_sum_tot;//для строки всего

                if ($totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                //за отчетный период
                $sheet->setCellValue('N'.$nextRow,$houseData->income_sum);

                $SUM_TOTAL_INCOMES_PARKING+=$houseData->income_sum;//для строки всего

                if ($houseData->income_sum != 0){
                    $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                //задолженность
                $sheet->setCellValue('O'.$nextRow, $houseData->contract_sum - $totalSumByObject->income_sum_tot);

                $SUM_TOTAL_DEBT_PARKING+=$houseData->contract_sum - $totalSumByObject->income_sum_tot;//для строки всего

                if ($houseData->contract_sum - $totalSumByObject->income_sum_tot != 0){
                    $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleBody);

                $nextRow++;
            }

            //строка всего
            $sheet->mergeCells('B'.$nextRow.':F'.$nextRow);
            $sheet->mergeCells('H'.$nextRow.':I'.$nextRow);
            $sheet->setCellValue('G'.$nextRow,'Всего');
            $sheet->setCellValue('J'.$nextRow,$SUM_TOTAL_AREA_PARKING);
            $sheet->setCellValue('L'.$nextRow,$SUM_TOTAL_CONTRACT_SUM_PARKING);
            if ($SUM_TOTAL_CONTRACT_SUM_PARKING != 0){
                $sheet->getStyle('L'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('M'.$nextRow,$SUM_TOTAL_NARITOG_PARKING);
            if ($SUM_TOTAL_NARITOG_PARKING != 0){
                $sheet->getStyle('M'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('N'.$nextRow,$SUM_TOTAL_INCOMES_PARKING);
            if ($SUM_TOTAL_INCOMES_PARKING != 0){
                $sheet->getStyle('N'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->setCellValue('O'.$nextRow,$SUM_TOTAL_DEBT_PARKING);
            if ($SUM_TOTAL_DEBT_PARKING != 0){
                $sheet->getStyle('O'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->getStyle('B'.$nextRow.':O'.$nextRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet ->getStyle('B'.$nextRow .':O'.$nextRow)->applyFromArray($styleHeader);
        }




        //футер 1 листа
        $nextRow = $sheet->getHighestRow()+2;

        //формат строки
        $sheet->mergeCells('B'.$nextRow.':O'.$nextRow);
        $sheet->getRowDimension($nextRow)->setRowHeight(30);
        $sheet->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('B'.$nextRow,'Субагентом   выполнены   обязательства,   обусловленные    Договором №'.
            $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').
           ' за период с '.$firstPeriodDay.'г. по '.$lastPeriodDay.' и сумма субагентского вознаграждения составляет '.
            number_format($coef*$totalIncomeSum, 2, ',', ' ').' руб., без НДС.'

        );

        $nextRow = $nextRow+1;
        $sheet->setCellValue('B'.$nextRow,'Подписи сторон:');

        $nextRow = $nextRow+1;
        $sheet->mergeCells('B'.$nextRow.':H'.$nextRow);
        $sheet->mergeCells('I'.$nextRow.':O'.$nextRow);
        $sheet->setCellValue('B'.$nextRow,'Отчет принял:');
        $sheet->setCellValue('I'.$nextRow,'Отчет сдал:');

        $nextRow = $nextRow+1;
        $sheet->mergeCells('B'.$nextRow.':H'.$nextRow);
        $sheet->mergeCells('I'.$nextRow.':O'.$nextRow);
        $sheet->setCellValue('B'.$nextRow,'Агент');
        $sheet->setCellValue('I'.$nextRow,'Субагент');

        $nextRow = $nextRow+1;
        $sheet->mergeCells('B'.$nextRow.':H'.$nextRow);
        $sheet->mergeCells('I'.$nextRow.':O'.$nextRow);
        $sheet->setCellValue('B'.$nextRow,'ООО «Ак Барс Дом»');
        $sheet->setCellValue('I'.$nextRow,$subagent->name);

        $nextRow = $nextRow+1;
        $sheet->mergeCells('B'.$nextRow.':H'.$nextRow);
        $sheet->setCellValue('B'.$nextRow,'420124, РТ, г.Казань, ул.Меридианная 1,');

        $nextLeftRow = $nextRow+1;
        $sheet->mergeCells('B'.$nextLeftRow.':H'.$nextLeftRow);
        $sheet->setCellValue('B'.$nextLeftRow,'т.(843) 272-09-60, 273-53-87');

        $nextLeftRow = $nextLeftRow+1;
        $sheet->mergeCells('B'.$nextLeftRow.':H'.$nextLeftRow);
        $sheet->setCellValue('B'.$nextLeftRow,'ИНН 1657100885, КПП 165701001, ');

        $nextLeftRow = $nextLeftRow+1;
        $sheet->mergeCells('B'.$nextLeftRow.':H'.$nextLeftRow);
        $sheet->setCellValue('B'.$nextLeftRow,'р/с 40702810700020006093 ');

        $nextLeftRow = $nextLeftRow+1;
        $sheet->mergeCells('B'.$nextLeftRow.':H'.$nextLeftRow);
        $sheet->setCellValue('B'.$nextLeftRow,'в ПАО "АК БАРС" БАНК г. Казань ');

        $nextLeftRow = $nextLeftRow+1;
        $sheet->mergeCells('B'.$nextLeftRow.':H'.$nextLeftRow);
        $sheet->setCellValue('B'.$nextLeftRow,'к/сч  30101810000000000805;');

        $nextLeftRow = $nextLeftRow+1;
        $sheet->mergeCells('B'.$nextLeftRow.':H'.$nextLeftRow);
        $sheet->setCellValue('B'.$nextLeftRow,'БИК 049205805, ОГРН 1101690072032');





        foreach ($requisitesArr as $key=>$rowReqItem){
            $sheet->mergeCells('I'.$nextRow.':O'.$nextRow);
            if ($rowReqItem != null){
                $sheet->setCellValue('I'.$nextRow,$key.': '.$rowReqItem );
                $nextRow++;
            }
        }


        $nextRow = $sheet->getHighestRow()+5;
        $sheet->mergeCells('B'.$nextRow.':H'.$nextRow);
        $sheet->mergeCells('I'.$nextRow.':O'.$nextRow);

        $sheet->setCellValue('B'.$nextRow,'________________/ А.В. Вассерман/');
        $sheet->setCellValue('I'.$nextRow,'________________/ '.$subagent->head_name.'/');

        $nextRow = $nextRow+1;
        $sheet->mergeCells('B'.$nextRow.':H'.$nextRow);
        $sheet->mergeCells('I'.$nextRow.':O'.$nextRow);
        $sheet->setCellValue('B'.$nextRow,'М.П.');
        $sheet->setCellValue('I'.$nextRow,'М.П.');



 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////     АКТ     ///////////////////////////////////////////////////////////////////////////////
/// /////////////////////////////////////////////////////////////////////////////////////////////////

         $sheet2 = $spreadsheet->createSheet();

        //название листа
        $sheet2->setTitle('Акт');



        //Наименование субагента
        $sheet2->setCellValue('A3','АКТ оказанных услуг  к  Договору №'. $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').'г.' )->getStyle("A3")->getFont()->setSize(14);
        $sheet2->mergeCells('A3:F3');
        $sheet2->getRowDimension('3')->setRowHeight(40);
        $sheet2->getStyle('A3')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        //берем последнюю строку
        $highestRow = $sheet2->getHighestRow();

        //след строка
        $nextRow  = $highestRow+1;

        //формат шапки
        $sheet2->mergeCells('A'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(170);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        //шапка листа
        $sheet2->setCellValue('A'.$nextRow, $subagent->name.', именуемый в дальнейшем «Субагент», в лице '.
            $subagent->head_name_2.', действующего на основании '.$subagent->base_of_rules.', с одной стороны, и ООО «Ак Барс Дом», именуемое в дальнейшем "Агент", в лице  директора Вассермана А.В., действующего на основании доверенности № 1 от 24.12.2019г., с другой стороны, совместно именуемые «Стороны», составили настоящий АКТ о нижеследующем:
            1. Субагент в соответствии с Договором №.'. $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').'г. организовал: ' .
            '- реализацию имущества путем заключения договоров купли-продажи;
            2. При выполнении поручения доходы от продажи площадей в рамках договора: №'.$subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').'г. за период с '.$firstPeriodDay.'г. по '.$lastPeriodDay.'г. составили: '.number_format($totalIncomeSum, 2, ',', ' ') .' руб (перечень указан в отчете Субагента).')
            ->getStyle("A".$nextRow)->getFont()->setSize(14);


        $nextRow = $nextRow+1;
        $sheet2->mergeCells('A'.$nextRow.':F'.$nextRow);
        $sheet2->setCellValue('A'.$nextRow, 'Заказчик: ')
            ->getStyle("A".$nextRow)->getFont()->setSize(14);

        $nextRow = $nextRow+2;

        $sheet2->getColumnDimension('A')->setWidth(15);
        $sheet2->getColumnDimension('B')->setWidth(40);
        $sheet2->getColumnDimension('C')->setWidth(15);
        $sheet2->getColumnDimension('D')->setWidth(25);
        $sheet2->getColumnDimension('E')->setWidth(15);
        $sheet2->getColumnDimension('F')->setWidth(15);

        $styleHeaderAkt = [
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

            'font'  => [
                'bold'  => false,
                'color' => array('rgb' => '000000'),
                'size'  => 14,

            ]

        ];

        //шапка таблицы
        $sheet2->setCellValue('A'.$nextRow,'№');
        $sheet2->setCellValue('B'.$nextRow,'Наименование  работы (услуги)');
        $sheet2->setCellValue('C'.$nextRow,'Ед. изм.');
        $sheet2->setCellValue('D'.$nextRow,'Количество');
        $sheet2->setCellValue('E'.$nextRow,'Цена');
        $sheet2->setCellValue('F'.$nextRow,'Сумма');

        $sheet2->getStyle('A'.$nextRow .':F'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2 ->getStyle('A'.$nextRow .':F'.$nextRow)->applyFromArray($styleHeaderAkt);


        $nextRow = $nextRow+1;
        $sheet2->getStyle('A'.$nextRow .':F'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('B'.$nextRow,' Субагентские услуги: ');
        $sheet2->mergeCells('B'.$nextRow .':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(20);
        $sheet2 ->getStyle('A'.$nextRow .':F'.$nextRow)->applyFromArray($styleHeaderAkt);


        //данные по первичке
        $nextRow = $nextRow+1;
        $mergeRow = $nextRow+1;


        $sheet2->setCellValue('A'.$nextRow,'1');
        $sheet2->mergeCells('A'.$nextRow.':A'.$mergeRow);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('B'.$nextRow,'за  первичную недвижимость');
        $sheet2->mergeCells('B'.$nextRow.':B'.$mergeRow);
        $sheet2->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C'.$nextRow,'рубли');
        $sheet2->mergeCells('C'.$nextRow.':C'.$mergeRow);
        $sheet2->getStyle('C'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D'.$nextRow,'Итого');
        $sheet2->setCellValue('D'.$mergeRow,'Без налога (НДС)');
        $sheet2->getStyle('D'.$nextRow.':D'.$mergeRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        if(isset($SUM_TOTAL_INCOMES)){
            $sheet2->setCellValue('E'.$nextRow, $SUM_TOTAL_INCOMES);
            if ($SUM_TOTAL_INCOMES != 0){
                $sheet2->getStyle('E'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $pervichkaSum = $SUM_TOTAL_INCOMES*$coef;
            $sheet2->setCellValue('F'.$nextRow, $pervichkaSum);
            if ($pervichkaSum != 0){
                $sheet2->getStyle('F'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
        else{
            $SUM_TOTAL_INCOMES = 0;
            $pervichkaSum =0;
        }




        //данные по коммерческой недвижимости
        $nextRow = $mergeRow+1;
        $mergeRow = $nextRow+1;


        $sheet2->setCellValue('A'.$nextRow,'2');
        $sheet2->mergeCells('A'.$nextRow.':A'.$mergeRow);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('B'.$nextRow,'за коммерческую недвижимость');
        $sheet2->mergeCells('B'.$nextRow.':B'.$mergeRow);
        $sheet2->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C'.$nextRow,'рубли');
        $sheet2->mergeCells('C'.$nextRow.':C'.$mergeRow);
        $sheet2->getStyle('C'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D'.$nextRow,'Итого');
        $sheet2->setCellValue('D'.$mergeRow,'Без налога (НДС)');
        $sheet2->getStyle('D'.$nextRow.':D'.$mergeRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        if(isset($SUM_TOTAL_INCOMES_OFFICE)){
            $sheet2->setCellValue('E'.$nextRow, $SUM_TOTAL_INCOMES_OFFICE);
            if ($SUM_TOTAL_INCOMES_OFFICE != 0){
                $sheet2->getStyle('E'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $officeSum = $SUM_TOTAL_INCOMES_OFFICE*$coef;
            $sheet2->setCellValue('F'.$nextRow, $officeSum);
            if ($officeSum != 0){
                $sheet2->getStyle('F'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
        else{
            $SUM_TOTAL_INCOMES_OFFICE = 0;
            $officeSum =0;
        }


        //данные по кладовкам
        $nextRow = $mergeRow+1;
        $mergeRow = $nextRow+1;


        $sheet2->setCellValue('A'.$nextRow,'3');
        $sheet2->mergeCells('A'.$nextRow.':A'.$mergeRow);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('B'.$nextRow,'за кладовки');
        $sheet2->mergeCells('B'.$nextRow.':B'.$mergeRow);
        $sheet2->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C'.$nextRow,'рубли');
        $sheet2->mergeCells('C'.$nextRow.':C'.$mergeRow);
        $sheet2->getStyle('C'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D'.$nextRow,'Итого');
        $sheet2->setCellValue('D'.$mergeRow,'Без налога (НДС)');
        $sheet2->getStyle('D'.$nextRow.':D'.$mergeRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        if(isset($SUM_TOTAL_INCOMES_PANTRY)){
            $sheet2->setCellValue('E'.$nextRow, $SUM_TOTAL_INCOMES_PANTRY);
            if ($SUM_TOTAL_INCOMES_PANTRY != 0){
                $sheet2->getStyle('E'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $pantrySum = $SUM_TOTAL_INCOMES_PANTRY*$coef;
            $sheet2->setCellValue('F'.$nextRow, $pantrySum);
            if ($pantrySum != 0){
                $sheet2->getStyle('F'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
        else{
            $SUM_TOTAL_INCOMES_PANTRY = 0;
            $pantrySum =0;
        }


        //данные по парковкам
        $nextRow = $mergeRow+1;
        $mergeRow = $nextRow+1;


        $sheet2->setCellValue('A'.$nextRow,'4');
        $sheet2->mergeCells('A'.$nextRow.':A'.$mergeRow);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('B'.$nextRow,'за парковки');
        $sheet2->mergeCells('B'.$nextRow.':B'.$mergeRow);
        $sheet2->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C'.$nextRow,'рубли');
        $sheet2->mergeCells('C'.$nextRow.':C'.$mergeRow);
        $sheet2->getStyle('C'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D'.$nextRow,'Итого');
        $sheet2->setCellValue('D'.$mergeRow,'Без налога (НДС)');
        $sheet2->getStyle('D'.$nextRow.':D'.$mergeRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        if(isset($SUM_TOTAL_INCOMES_PARKING)){
            $sheet2->setCellValue('E'.$nextRow, $SUM_TOTAL_INCOMES_PARKING);
            if ($SUM_TOTAL_INCOMES_PARKING != 0){
                $sheet2->getStyle('E'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $parkingSum = $SUM_TOTAL_INCOMES_PARKING*$coef;
            $sheet2->setCellValue('F'.$nextRow, $parkingSum);
            if ($parkingSum != 0){
                $sheet2->getStyle('F'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
        else{
            $SUM_TOTAL_INCOMES_PARKING = 0;
            $parkingSum =0;
        }




        $sheet2 ->getStyle('A8:F'.$mergeRow)->applyFromArray($styleHeaderAkt);
        //всего
        $nextRow = $mergeRow+1;

        $sheet2->mergeCells('A'.$nextRow.':C'.$nextRow);
        $sheet2->setCellValue('D'.$nextRow,'Всего');
        $sheet2->getStyle('D'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $TOTAL_SUM = $SUM_TOTAL_INCOMES+$SUM_TOTAL_INCOMES_OFFICE+$SUM_TOTAL_INCOMES_PARKING+$SUM_TOTAL_INCOMES_PANTRY;
        $total_reward = $pervichkaSum+$officeSum+$pantrySum+$pantrySum;

        $sheet2->setCellValue('E'.$nextRow,$TOTAL_SUM);
        $sheet2->setCellValue('F'.$nextRow,$total_reward);

        if ($TOTAL_SUM != 0){
            $sheet2->getStyle('E'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        if ($total_reward != 0){
            $sheet2->getStyle('F'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }


        $sheet2 ->getStyle('A'.$nextRow.':F'.$nextRow)->applyFromArray($styleHeaderAkt);


        ////////////футер акт/////////////////////

        $nextRow = $sheet2->getHighestRow()+2;

        $sheet2->mergeCells('A'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(40);
        $sheet2->setCellValue('A'.$nextRow,'2. Отчет Субагента  за период с '.$firstPeriodDay.'г. по '.$lastPeriodDay.'г.  принят Принципалом без замечаний, стороны претензий друг к другу не имеют.')->getStyle('A'.$nextRow)->getFont()->setSize(14);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $nextRow = $nextRow+1;
        $sheet2->mergeCells('A'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(40);
        $sheet2->setCellValue('A'.$nextRow,'3. Данный АКТ составлен в двух экземплярах на русском языке, по одному для каждой из сторон, оба экземпляра имеют одинаковую юридическую силу.')->getStyle('A'.$nextRow)->getFont()->setSize(14);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $nextRow = $nextRow+1;
        $sheet2->mergeCells('A'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(40);
        $sheet2->setCellValue('A'.$nextRow,'Подписи сторон:')->getStyle('A'.$nextRow)->getFont()->setSize(14);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $nextRow = $nextRow+2;
        $sheet2->mergeCells('A'.$nextRow.':C'.$nextRow);
        $sheet2->mergeCells('D'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(40);
        $sheet2->setCellValue('A'.$nextRow,'Субагент: '.$subagent->name)->getStyle('A'.$nextRow)->getFont()->setSize(14);
        $sheet2->setCellValue('D'.$nextRow,'Агент: ООО «Ак Барс Дом»')->getStyle('D'.$nextRow)->getFont()->setSize(14);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet2->getStyle('D'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $nextRow = $nextRow+2;

        $sheet2->mergeCells('A'.$nextRow.':C'.$nextRow);
        $sheet2->mergeCells('D'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(40);
        $sheet2->setCellValue('A'.$nextRow,' _________________ ('.$subagent->head_name.')')->getStyle('A'.$nextRow)->getFont()->setSize(14);
        $sheet2->setCellValue('D'.$nextRow,'_________________ (А.В.Вассерман)')->getStyle('D'.$nextRow)->getFont()->setSize(14);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet2->getStyle('D'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $nextRow = $nextRow+1;

        $sheet2->mergeCells('A'.$nextRow.':C'.$nextRow);
        $sheet2->mergeCells('D'.$nextRow.':F'.$nextRow);
        $sheet2->getRowDimension($nextRow)->setRowHeight(40);
        $sheet2->setCellValue('A'.$nextRow,'М.П.')->getStyle('A'.$nextRow)->getFont()->setSize(14);
        $sheet2->setCellValue('D'.$nextRow,'М.П.')->getStyle('D'.$nextRow)->getFont()->setSize(14);
        $sheet2->getStyle('A'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet2->getStyle('D'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);




        //////////////////////////////////////////////////////////////////
        /// /////////3 лист ///////////////////////////////////////////////////////////////
        /// АКТ___///////
        /// /////////////


        $sheet3 = $spreadsheet->createSheet();


        foreach ($this->excelColumnRange('A', 'AG') as $value) {
            $sheet3->getColumnDimension($value)
                ->setWidth(2.5);
        }
        foreach(range('1','30') as $row) {
            $sheet3->getRowDimension($row)->setRowHeight(11.5);
        }

        $sheet->getColumnDimension('B')->setWidth(7);
        //название листа
        $sheet3->setTitle('Акт___');

        $sheet3->mergeCells('B3:AF3');
        $sheet3->getRowDimension('3')->setRowHeight(22);
        $sheet3->setCellValue('B3','Акт № __  от '.Carbon::now()->format('d.m.Y').' г.');


        $styleAktTitle = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],


            ],

            'font'  => [
                'bold'  => true,
                'color' => array('rgb' => '000000'),
                'size'  => 14,
                'name'  => 'Arial'

            ]

        ];

        $sheet3 ->getStyle('B3:AF3')->applyFromArray($styleAktTitle);
        $sheet3->getStyle('B3')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('B5:E5');
        $sheet3->setCellValue('B5','Исполнитель: ');
        $sheet3 ->getStyle('B5')->getFont()->setSize(8);
        $sheet3 ->getStyle('B5')->getFont()->setName('Arial');
        $sheet3->getRowDimension('5')->setRowHeight(38);
        $sheet3->getStyle('B5')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('F5:AG5');
        $sheet3->setCellValue('F5',$subagent->name.', '.$subagent->name.', ИНН '.$subagent->inn.', '.$subagent->adress.', р/с '.$subagent->rs.' в '.$subagent->bank_name.', БИК '. $subagent->bik.', к/с '.$subagent->ks);
        $sheet3 ->getStyle('F5')->getFont()->setSize(9);
        $sheet3 ->getStyle('F5')->getFont()->setBold(true);
        $sheet3 ->getStyle('F5')->getFont()->setName('Arial');
        $sheet3->getStyle('F5')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $sheet3->mergeCells('B7:E7');
        $sheet3->setCellValue('B7','Заказчик: ');
        $sheet3 ->getStyle('B7')->getFont()->setSize(8);
        $sheet3 ->getStyle('B7')->getFont()->setName('Arial');
        $sheet3->getRowDimension('7')->setRowHeight(38);
        $sheet3->getStyle('B7')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet3->mergeCells('F7:AG7');
        $sheet3->setCellValue('F7','ООО "Ак Барс Дом", ИНН 1657100885, 420124, РТ г.Казань, ул. Меридианная д.1, р/с 40702810700020006093, в банке ПАО "АК БАРС" БАНК, БИК 049205805, к/с 30101810000000000805');
        $sheet3 ->getStyle('F7')->getFont()->setSize(9);
        $sheet3 ->getStyle('F7')->getFont()->setBold(true);
        $sheet3 ->getStyle('F7')->getFont()->setName('Arial');
        $sheet3->getStyle('F7')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);


        $sheet3->mergeCells('B9:E9');
        $sheet3->setCellValue('B9','Основание: ');
        $sheet3 ->getStyle('B9')->getFont()->setSize(8);

        $sheet3 ->getStyle('B9')->getFont()->setName('Arial');
        $sheet3->getRowDimension('9')->setRowHeight(13);
        $sheet3->getStyle('B9')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet3->mergeCells('F9:AG9');
        $sheet3->setCellValue('F9','Субагентский договор № '. $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').' г.');
        $sheet3 ->getStyle('F9')->getFont()->setSize(9);
        $sheet3 ->getStyle('F9')->getFont()->setBold(true);
        $sheet3 ->getStyle('F9')->getFont()->setName('Arial');
        $sheet3->getStyle('F9')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('B11:C12');
        $sheet3->setCellValue('B11','№');
        $sheet3 ->getStyle('B11')->getFont()->setSize(9);
        $sheet3 ->getStyle('B11')->getFont()->setBold(true);
        $sheet3 ->getStyle('B11')->getFont()->setName('Arial');
        $sheet3->getStyle('B11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet3->mergeCells('D11:T12');
        $sheet3->setCellValue('D11','Наименование работ, услуг');
        $sheet3 ->getStyle('D11')->getFont()->setSize(9);
        $sheet3 ->getStyle('D11')->getFont()->setBold(true);
        $sheet3 ->getStyle('D11')->getFont()->setName('Arial');
        $sheet3->getStyle('D11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('U11:W12');
        $sheet3->setCellValue('U11','Кол-во');
        $sheet3 ->getStyle('U11')->getFont()->setSize(9);
        $sheet3 ->getStyle('U11')->getFont()->setBold(true);
        $sheet3 ->getStyle('U11')->getFont()->setName('Arial');
        $sheet3->getStyle('U11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('X11:Y12');
        $sheet3->setCellValue('X11','Ед.');
        $sheet3 ->getStyle('X11')->getFont()->setSize(9);
        $sheet3 ->getStyle('X11')->getFont()->setBold(true);
        $sheet3 ->getStyle('X11')->getFont()->setName('Arial');
        $sheet3->getStyle('X11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('Z11:AC12');
        $sheet3->setCellValue('Z11','Цена');
        $sheet3 ->getStyle('Z11')->getFont()->setSize(9);
        $sheet3 ->getStyle('Z11')->getFont()->setBold(true);
        $sheet3 ->getStyle('Z11')->getFont()->setName('Arial');
        $sheet3->getStyle('Z11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('AD11:AG12');
        $sheet3->setCellValue('AD11','Сумма');
        $sheet3 ->getStyle('AD11')->getFont()->setSize(9);
        $sheet3 ->getStyle('AD11')->getFont()->setBold(true);
        $sheet3 ->getStyle('AD11')->getFont()->setName('Arial');
        $sheet3->getStyle('AD11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $nextRow = 13;
        $num_akt = 1;

        $aktDataArr = array(
            'pervichka'=>$SUM_TOTAL_INCOMES,
            'commercial'=>$SUM_TOTAL_INCOMES_OFFICE,
            'parking'=>$SUM_TOTAL_INCOMES_PARKING,
            'pantry'=>$SUM_TOTAL_INCOMES_PANTRY
        );

        $styleAktTable = [
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

        foreach ($aktDataArr as $type=>$itemRow){


                $sheet3->mergeCells('B'.$nextRow.':C'.$nextRow);
                $sheet3->getRowDimension($nextRow)->setRowHeight(25);
                $sheet3->setCellValue('B'.$nextRow,$num_akt);
                $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(8);
                $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(false);
                $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
                $sheet3->getStyle('B'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet3->mergeCells('D'.$nextRow.':T'.$nextRow);

                if ($type == 'pervichka'){
                    $akt_row_text = 'первичную недвижимость';
                }
                elseif($type == 'commercial'){
                    $akt_row_text = 'коммерческую недвижимость';
                }
                elseif($type == 'parking'){
                    $akt_row_text = 'паркинг';
                }
                elseif($type == 'pantry'){
                    $akt_row_text = 'кладовые';
                }

                $sheet3->setCellValue('D'.$nextRow,'Субагентские услуги за '.$akt_row_text.' по договору №'. $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').' г.  За период с '.$firstPeriodDay.'г. по '.$lastPeriodDay.'г.');
                $sheet3 ->getStyle('D'.$nextRow)->getFont()->setSize(8);
                $sheet3 ->getStyle('D'.$nextRow)->getFont()->setBold(false);
                $sheet3 ->getStyle('D'.$nextRow)->getFont()->setName('Arial');
                $sheet3->getStyle('D'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

                               $sheet3->mergeCells('U'.$nextRow.':W'.$nextRow);
                $sheet3->setCellValue('U'.$nextRow,'1');
                $sheet3 ->getStyle('U'.$nextRow)->getFont()->setSize(8);
                $sheet3 ->getStyle('U'.$nextRow)->getFont()->setBold(false);
                $sheet3 ->getStyle('U'.$nextRow)->getFont()->setName('Arial');
                $sheet3->getStyle('U'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet3->mergeCells('X'.$nextRow.':Y'.$nextRow);
                $sheet3->setCellValue('X'.$nextRow,'шт.');
                $sheet3 ->getStyle('X'.$nextRow)->getFont()->setSize(8);
                $sheet3 ->getStyle('X'.$nextRow)->getFont()->setBold(false);
                $sheet3 ->getStyle('X'.$nextRow)->getFont()->setName('Arial');
                $sheet3->getStyle('X'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet3->mergeCells('Z'.$nextRow.':AC'.$nextRow);
                $sheet3->setCellValue('Z'.$nextRow,$itemRow);
                $sheet3 ->getStyle('Z'.$nextRow)->getFont()->setSize(8);
                $sheet3 ->getStyle('Z'.$nextRow)->getFont()->setBold(false);
                $sheet3 ->getStyle('Z'.$nextRow)->getFont()->setName('Arial');
                $sheet3->getStyle('Z'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet3->getStyle('Z'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet3->mergeCells('AD'.$nextRow.':AG'.$nextRow);
                $sheet3->setCellValue('AD'.$nextRow,$itemRow*$coef);
                $sheet3 ->getStyle('AD'.$nextRow)->getFont()->setSize(8);
                $sheet3 ->getStyle('AD'.$nextRow)->getFont()->setBold(false);
                $sheet3 ->getStyle('AD'.$nextRow)->getFont()->setName('Arial');
                $sheet3->getStyle('AD'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet3->getStyle('AD'.$nextRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $num_akt++;
                $nextRow++;

        }

        $endTableRow =  $nextRow-1;

        $sheet3 ->getStyle('B11:AG'.$endTableRow)->applyFromArray($styleAktTable);

        $sheet3->getRowDimension($nextRow)->setRowHeight(7);

        $nextRow = $nextRow+1;
        $sheet3->mergeCells('Z'.$nextRow.':AC'.$nextRow);
        $sheet3->getRowDimension($nextRow)->setRowHeight(13);
        $sheet3->setCellValue('Z'.$nextRow,'Итого:');
        $sheet3 ->getStyle('Z'.$nextRow)->getFont()->setSize(9);
        $sheet3 ->getStyle('Z'.$nextRow)->getFont()->setBold(true);
        $sheet3 ->getStyle('Z'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('Z'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->mergeCells('AD'.$nextRow.':AG'.$nextRow);
        $sheet3->setCellValue('AD'.$nextRow,$total_reward);
        $sheet3 ->getStyle('AD'.$nextRow)->getFont()->setSize(9);
        $sheet3 ->getStyle('AD'.$nextRow)->getFont()->setBold(true);
        $sheet3 ->getStyle('AD'.$nextRow)->getFont()->setName('Arial');
        if ($total_reward >0){
            $sheet3->getStyle('AD'.$nextRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet3->getStyle('AD'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        $nextRow = $nextRow+1;
        $sheet3->mergeCells('X'.$nextRow.':AC'.$nextRow);
        $sheet3->getRowDimension($nextRow)->setRowHeight(13);
        $sheet3->setCellValue('X'.$nextRow,'Без налога (НДС)');
        $sheet3 ->getStyle('X'.$nextRow)->getFont()->setSize(9);
        $sheet3 ->getStyle('X'.$nextRow)->getFont()->setBold(true);
        $sheet3 ->getStyle('X'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('X'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);


        $nextRow = $nextRow+2;
        $sheet3->mergeCells('B'.$nextRow.':AG'.$nextRow);
        $sheet3->getRowDimension($nextRow)->setRowHeight(11);


        $num_akt = $num_akt-1;

        $sheet3->setCellValue('B'.$nextRow,'Всего оказано  услуг:' .$num_akt .' на сумму ');
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(8);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(false);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $nextRow = $nextRow+1;
        $sheet3->mergeCells('B'.$nextRow.':AG'.$nextRow);
        $sheet3->getRowDimension($nextRow)->setRowHeight(13);

        $sumtext_akt = new \NumberFormatter("ru", \NumberFormatter::SPELLOUT);




        $sumRuble = substr($total_reward, 0, strpos($total_reward, ","));
        $sumRubleTextStr = $sumtext_akt->format($sumRuble);

        $sumKop = substr(substr($total_reward, strpos($total_reward, ",") + 1), 0, 2);
        $sumKopTextStr = $sumtext_akt->format($sumKop);


        $sumRubleLastChar = substr($sumRuble, -1);
        $sumKopLastChar = substr($sumKop, -1);

        switch ($sumRubleLastChar) {
            case 1:
                $rubleString =  "рубль";
                break;
            case 2:
                $rubleString = "рубля";
                break;
            case 3:
                $rubleString = "рубля";
                break;
            case 4:
                $rubleString = "рубля";
                break;
            case 5:
                $rubleString =  "рублей";
                break;
            case 6:
                $rubleString = "рублей";
                break;
            case 7:
                $rubleString = "рублей";
                break;
            case 8:
                $rubleString= "рублей";
                break;
            case 9:
                $rubleString =  "рублей";
                break;
            case 0:
                $rubleString = "рублей";
                break;

        }
        switch ($sumKopLastChar) {
            case 1:
                $kopString =  "копейка";
                break;
            case 2:
                $kopString = "копейки";
                break;
            case 3:
                $kopString = "копейки";
                break;
            case 4:
                $kopString = "копейки";
                break;
            case 5:
                $kopString =  "копеек";
                break;
            case 6:
                $kopString = "копеек";
                break;
            case 7:
                $kopString = "копеек";
                break;
            case 8:
                $kopString= "копеек";
                break;
            case 9:
                $kopString =  "копеек";
                break;
            case 0:
                $kopString = "копеек";
                break;

        }

        $sheet3->setCellValue('B'.$nextRow,$sumRubleTextStr.' '.$rubleString.' ' .$sumKopTextStr.' '.$kopString);



        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(9);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(true);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $nextRow = $nextRow+2;
        $nextMergeRow = $nextRow+1;
        $sheet3->mergeCells('B'.$nextRow.':AG'.$nextMergeRow);
        $sheet3->setCellValue('B'.$nextRow,'Вышеперечисленные услуги выполнены полностью и в срок. Заказчик претензий по объему, качеству и срокам оказания услуг не имеет.');
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(9);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(false);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $nextRow = $nextRow+2;
        $sheet3->mergeCells('B'.$nextRow.':AG'.$nextRow);
        $sheet3->getRowDimension($nextRow)->setRowHeight(7);
        $sheet3 ->getStyle('B'.$nextRow.':AG'.$nextRow)->applyFromArray($styleAktTitle);

        $nextRow = $nextRow+2;
        $sheet3->getRowDimension($nextRow)->setRowHeight(13);
        $sheet3->mergeCells('B'.$nextRow.':N'.$nextRow);
        $sheet3->mergeCells('R'.$nextRow.':AG'.$nextRow);
        $sheet3->setCellValue('B'.$nextRow,'ИСПОЛНИТЕЛЬ');

        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(10);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(true);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->setCellValue('R'.$nextRow,'ЗАКАЗЧИК');
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setSize(10);
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setBold(true);
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('R'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $nextRow = $nextRow+1;
        $sheet3->getRowDimension($nextRow)->setRowHeight(13);
        $sheet3->mergeCells('B'.$nextRow.':N'.$nextRow);
        $sheet3->mergeCells('R'.$nextRow.':AG'.$nextRow);
        $sheet3->setCellValue('B'.$nextRow,$subagent->name);

        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(8);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(false);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->setCellValue('R'.$nextRow,'ООО "Ак Барс Дом"');
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setSize(8);
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setBold(false);
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('R'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $nextRow = $nextRow+1;
        $sheet3->getRowDimension($nextRow)->setRowHeight(19);
        $sheet3->mergeCells('B'.$nextRow.':N'.$nextRow);
        $sheet3->mergeCells('R'.$nextRow.':AG'.$nextRow);
        $styleBorderBottomThin = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],


            ]

        ];
        $sheet3 ->getStyle('B'.$nextRow.':N'.$nextRow)->applyFromArray($styleBorderBottomThin);
        $sheet3 ->getStyle('R'.$nextRow.':AG'.$nextRow)->applyFromArray($styleBorderBottomThin);



        $nextRow = $nextRow+1;
        $sheet3->getRowDimension($nextRow)->setRowHeight(13);
        $sheet3->mergeCells('B'.$nextRow.':N'.$nextRow);
        $sheet3->mergeCells('R'.$nextRow.':AG'.$nextRow);
        $sheet3->setCellValue('B'.$nextRow,$subagent->head_name);

        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setSize(8);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setBold(false);
        $sheet3 ->getStyle('B'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('B'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet3->setCellValue('R'.$nextRow,'Вассерман А.В.');
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setSize(8);
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setBold(false);
        $sheet3 ->getStyle('R'.$nextRow)->getFont()->setName('Arial');
        $sheet3 ->getStyle('R'.$nextRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        //////////////////////////////////////////////////////////////////
        /// /////////4 лист ///////////////////////////////////////////////////////////////
        /// СЧЕТ___///////
        /// /////////////


        $sheet4 = $spreadsheet->createSheet();
        //название листа
        $sheet4->setTitle('Счет');

        foreach ($this->excelColumnRange('A', 'AG') as $value) {
            $sheet4->getColumnDimension($value)
                ->setWidth(2.5);
        }
        foreach(range('1','30') as $row) {
            $sheet4->getRowDimension($row)->setRowHeight(11.5);
        }

        $sheet4->getRowDimension(5)->setRowHeight(16);
        $sheet4->mergeCells('B5:P6');
        $sheet4->setCellValue('B5','Банк получателя                                                   '.$subagent->bank_name);
        $sheet4 ->getStyle('B5')->getFont()->setSize(12);
        $sheet4 ->getStyle('B5')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);


        $sheet4->mergeCells('Q5:S5');
        $sheet4->setCellValue('Q5','БИК');
        $sheet4 ->getStyle('Q5')->getFont()->setSize(12);
        $sheet4 ->getStyle('Q5')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('T5:AG5');
        $sheet4->setCellValue('T5',$subagent->bik);
        $sheet4 ->getStyle('T5')->getFont()->setSize(12);
        $sheet4 ->getStyle('T5')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->getRowDimension(6)->setRowHeight(18);

        $sheet4->mergeCells('Q6:S6');
        $sheet4->setCellValue('Q6','К/С №');
        $sheet4 ->getStyle('Q6')->getFont()->setSize(12);
        $sheet4 ->getStyle('Q6')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('T6:AG6');
        $sheet4->setCellValue('T6',$subagent->ks);
        $sheet4 ->getStyle('T6')->getFont()->setSize(12);
        $sheet4 ->getStyle('T6')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->getStyle('T6')->getNumberFormat()->setFormatCode('####################');


        $sheet4->getRowDimension(7)->setRowHeight(19.5);
        $sheet4->mergeCells('B7:I7');
        $sheet4->setCellValue('B7','ИНН '.$subagent->inn);
        $sheet4 ->getStyle('B7')->getFont()->setSize(12);
        $sheet4 ->getStyle('B7')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('J7:P7');
        $sheet4->setCellValue('J7','КПП '.$subagent->kpp);
        $sheet4 ->getStyle('J7')->getFont()->setSize(12);
        $sheet4 ->getStyle('J7')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->getRowDimension(9)->setRowHeight(42);

        $sheet4->mergeCells('Q7:S9');
        $sheet4->setCellValue('Q7','Р/С №');
        $sheet4 ->getStyle('Q7')->getFont()->setSize(12);
        $sheet4 ->getStyle('Q7')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $sheet4->mergeCells('T7:AG9');
        $sheet4->setCellValue('T7',$subagent->rs);
        $sheet4 ->getStyle('T7')->getFont()->setSize(12);
        $sheet4 ->getStyle('T7')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
        $sheet4->getStyle('T7')->getNumberFormat()->setFormatCode('####################');


        $sheet4->mergeCells('B8:P9');
        $sheet4->setCellValue('B8','Получатель                                                  '.
        $subagent->name.', '.$subagent->adress);
        $sheet4 ->getStyle('B8')->getFont()->setSize(12);
        $sheet4 ->getStyle('B8')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $styleBillHeader = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'inside'=>[
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ]

            ]

        ];
        $sheet4 ->getStyle('B5:AG9')->applyFromArray($styleBillHeader);


        $sheet4->mergeCells('B11:AG12');
        $sheet4->setCellValue('B11','Счет на оплату №__  от '.Carbon::now()->format('d.m.Y').' г.');
//        $sheet4 ->getStyle('B11')->getFont()->setSize(12);

        $sheet4 ->getStyle('B11')->getFont()->setSize(14);
        $sheet4 ->getStyle('B11')->getFont()->setBold(true);
        $sheet4 ->getStyle('B11')->getFont()->setName('Arial');
        $sheet4 ->getStyle('B11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $styleBorderBottomMedium = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],


            ]

        ];

        $sheet4->getRowDimension(13)->setRowHeight(7);
        $sheet4->getRowDimension(14)->setRowHeight(7);
        $sheet4->getRowDimension(15)->setRowHeight(7);
        $sheet4 ->getStyle('B13:AG13')->applyFromArray($styleBorderBottomMedium);

        $sheet4->getRowDimension(16)->setRowHeight(60);
        $sheet4->mergeCells('B16:F16');
        $sheet4 ->getStyle('B16')->getFont()->setSize(12);
        $sheet4->setCellValue('B16','Покупатель');
        $sheet4 ->getStyle('B16')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('G16:AG16');
        $sheet4 ->getStyle('G16')->getFont()->setSize(12);
        $sheet4 ->getStyle('G16')->getFont()->setBold(true);
        $sheet4->setCellValue('G16','ООО "Ак Барс Дом", ИНН 1657100885, 420124, РТ г.Казань, ул. Меридианная д.1, р/с 40702810700020006093, в банке ПАО "АК БАРС" БАНК, БИК 049205805, к/с 30101810000000000805');
        $sheet4 ->getStyle('G16')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
        $sheet4->getRowDimension(17)->setRowHeight(7);

        //шапка таблицы
        $sheet4->getRowDimension(18)->setRowHeight(18);
        $sheet4->mergeCells('B18:C18');
        $sheet4 ->getStyle('B18')->getFont()->setSize(12);
        $sheet4 ->getStyle('B18')->getFont()->setBold(true);
        $sheet4->setCellValue('B18','№');
        $sheet4 ->getStyle('B18')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('D18:Q18');
        $sheet4 ->getStyle('D18')->getFont()->setSize(12);
        $sheet4 ->getStyle('D18')->getFont()->setBold(true);
        $sheet4->setCellValue('D18','Товары (работы, услуги)');
        $sheet4 ->getStyle('D18')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('R18:T18');
        $sheet4 ->getStyle('R18')->getFont()->setSize(12);
        $sheet4 ->getStyle('R18')->getFont()->setBold(true);
        $sheet4->setCellValue('R18','Кол-во');
        $sheet4 ->getStyle('R18')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('U18:W18');
        $sheet4 ->getStyle('U18')->getFont()->setSize(12);
        $sheet4 ->getStyle('U18')->getFont()->setBold(true);
        $sheet4->setCellValue('U18','Ед.');
        $sheet4 ->getStyle('U18')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('X18:AB18');
        $sheet4 ->getStyle('X18')->getFont()->setSize(12);
        $sheet4 ->getStyle('X18')->getFont()->setBold(true);
        $sheet4->setCellValue('X18','Цена');
        $sheet4 ->getStyle('X18')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('AC18:AG18');
        $sheet4 ->getStyle('AC18')->getFont()->setSize(12);
        $sheet4 ->getStyle('AC18')->getFont()->setBold(true);
        $sheet4->setCellValue('AC18','Сумма');
        $sheet4 ->getStyle('AC18')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->getRowDimension(19)->setRowHeight(70);

        $sheet4->mergeCells('B19:C19');
        $sheet4 ->getStyle('B19')->getFont()->setSize(12);
        $sheet4->setCellValue('B19','1');
        $sheet4 ->getStyle('B19')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('D19:Q19');
        $sheet4 ->getStyle('D19')->getFont()->setSize(12);

        $sheet4->setCellValue('D19','Вознаграждение субагента за оказанные услуги по субагентскому договору №'. $subagent->sub_contract_number.' от ' . Carbon::createFromFormat('Y-m-d', $subagent->sub_contract_date )->format('d.m.Y').' г.  За период с '.$firstPeriodDay.'г. по '.$lastPeriodDay.'г.' );
        $sheet4 ->getStyle('D19')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $sheet4->mergeCells('R19:T19');
        $sheet4 ->getStyle('R19')->getFont()->setSize(12);

        $sheet4->setCellValue('R19','1');
        $sheet4 ->getStyle('R19')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('U19:W19');
        $sheet4 ->getStyle('U19')->getFont()->setSize(12);

        $sheet4->setCellValue('U19','шт.');
        $sheet4 ->getStyle('U19')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->mergeCells('X19:AB19');
        $sheet4 ->getStyle('X19')->getFont()->setSize(12);

        $sheet4->setCellValue('X19',$total_reward);
        $sheet4->getStyle('X19')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet4 ->getStyle('X19')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->mergeCells('AC19:AG19');
        $sheet4 ->getStyle('AC19')->getFont()->setSize(12);

        $sheet4->setCellValue('AC19', $total_reward);
        $sheet4->getStyle('AC19')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet4 ->getStyle('AC19')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4 ->getStyle('B18:AG19')->applyFromArray($styleAktTable);


        $sheet4->getRowDimension(20)->setRowHeight(7);
        $sheet4->getRowDimension(21)->setRowHeight(16);
        $sheet4->getRowDimension(22)->setRowHeight(16);
        $sheet4->getRowDimension(23)->setRowHeight(16);



        $sheet4->mergeCells('X21:AB21');
        $sheet4 ->getStyle('X21')->getFont()->setSize(12);
        $sheet4 ->getStyle('X21')->getFont()->setBold(true);
        $sheet4->setCellValue('X21','Итого:');
        $sheet4 ->getStyle('X21')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->mergeCells('AC21:AG21');
        $sheet4 ->getStyle('AC21')->getFont()->setSize(12);
        $sheet4 ->getStyle('AC21')->getFont()->setBold(true);
        $sheet4->setCellValue('AC21', $total_reward);
        $sheet4->getStyle('AC21')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet4 ->getStyle('AC21')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        $sheet4->mergeCells('U22:AB22');
        $sheet4 ->getStyle('U22')->getFont()->setSize(12);
        $sheet4 ->getStyle('U22')->getFont()->setBold(true);
        $sheet4->setCellValue('U22','Без налога(НДС)');
        $sheet4 ->getStyle('U22')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->mergeCells('AC22:AG22');
        $sheet4 ->getStyle('AC22')->getFont()->setSize(12);
        $sheet4 ->getStyle('AC22')->getFont()->setBold(true);
        $sheet4->setCellValue('AC22', '-');

        $sheet4 ->getStyle('AC22')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        $sheet4->mergeCells('U23:AB23');
        $sheet4 ->getStyle('U23')->getFont()->setSize(12);
        $sheet4 ->getStyle('U23')->getFont()->setBold(true);
        $sheet4->setCellValue('U23','Всего к оплате:');
        $sheet4 ->getStyle('U23')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet4->mergeCells('AC23:AG23');
        $sheet4 ->getStyle('AC23')->getFont()->setSize(12);
        $sheet4 ->getStyle('AC23')->getFont()->setBold(true);
        $sheet4->setCellValue('AC23', $total_reward);
        $sheet4->getStyle('AC23')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet4 ->getStyle('AC23')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->getRowDimension(24)->setRowHeight(16);
        $sheet4->mergeCells('B24:AG24');
        $sheet4 ->getStyle('B24')->getFont()->setSize(12);
        $sheet4->setCellValue('B24','Всего наименований 1, на сумму ');
        $sheet4 ->getStyle('B24')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);




        $sheet4->getRowDimension(25)->setRowHeight(30);
        $sheet4->mergeCells('B25:AG25');
        $sheet4 ->getStyle('B25')->getFont()->setSize(12);
        $sheet4 ->getStyle('B25')->getFont()->setBold(true);


        $sumText = new \NumberFormatter("ru", \NumberFormatter::SPELLOUT);

        $sumRuble = substr($total_reward, 0, strpos($total_reward, ","));


        $sumRubleTextStr = $sumText->format($sumRuble);

        $sumKop = substr(substr($total_reward, strpos($total_reward, ",") + 1), 0, 2);
        $sumKopTextStr = $sumText->format($sumKop);




        $sheet4->setCellValue('B25',$sumRubleTextStr.' '.$rubleString.' ' .$sumKopTextStr.' '.$kopString);
        $sheet4 ->getStyle('B25')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->getRowDimension(26)->setRowHeight(10.5);
        $sheet4 ->getStyle('B26:AG26')->applyFromArray($styleBorderBottomMedium);

        $sheet4->getRowDimension(27)->setRowHeight(7);
        $sheet4->getRowDimension(29)->setRowHeight(16);


        $sheet4->mergeCells('B29:H29');
        $sheet4 ->getStyle('B29')->getFont()->setSize(12);
        $sheet4 ->getStyle('B29')->getFont()->setBold(true);
        $sheet4->setCellValue('B29','Руководитель');
        $sheet4 ->getStyle('B29')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet4->mergeCells('I29:T29');
        $sheet4 ->getStyle('I29:T29')->applyFromArray($styleBorderBottomThin);
        $sheet4 ->getStyle('I29')->getFont()->setSize(12);
        $sheet4 ->getStyle('I29')->getFont()->setBold(true);
        $sheet4->setCellValue('I29',$subagent->head_name);
        $sheet4 ->getStyle('I29')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);



        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Отчет_субагенты_'.$month.'_'.$year.'.xlsx"');
        $writer->save("php://output");
    }

    function excelColumnRange($lower, $upper) {
        ++$upper;
        for ($i = $lower; $i !== $upper; ++$i) {
            yield $i;
        }
    }


}
