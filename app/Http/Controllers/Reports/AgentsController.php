<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AgentsController extends Controller
{
    public function index(){

        //получаем принципалов
        $principals = DB::table('principal_params')
            ->select(DB::raw(
                "
                    id,
                    name
                "))


            ->orderBy('id','asc')
            ->pluck('name','id');


        return view('reports.agents.index',
            [
              'principals'=>$principals
            ]);
    }

    public function makeReport(Request $request){

        if($request->get('dateRange')){


            $date = $request->get('dateRange');
            $dateArr = explode('-',$date);
            $dateFrom = str_replace(' ', '', $dateArr['0']);
            $dateTo = str_replace(' ', '', $dateArr['1']);



            $df = Carbon::createFromFormat('d.m.Y', $dateFrom);
            $dt = Carbon::createFromFormat('d.m.Y', $dateTo  );


        }

        else{
            return redirect()->back()->with('status','Не выбран период');
        }

        if ($request->get('principal')){

            $agent_id = $request->get('principal');

            //получаем принципала
            $agent = DB::table('principal_params')
                ->where('id',$agent_id)
                ->first();

            $agentContractDate = Carbon::createFromFormat('Y-m-d', $agent->agentcontract_date)->format('d.m.Y');

        }
        else{
            return redirect()->back()->with('status','Не выбран агент');
        }




        //запрос
        $data = DB::table('IncomPays')
            ->select(DB::raw(
                "

                object_params.owner,
                object_params.address,
                object_params.house_number,
                object_params.object_number,
                object_params.rooms_number,
                object_types.class_property,
                contacts.name as client_name,
                IncomPays.contractNumber,
                IncomPays.incomDate,
                lead_params.contract_date,
                lead_params.contract_sum,
                lead_params.filing_date,
                SUM(IncomPays.sum) as income_sum

                "))
            ->whereNotNull('IncomPays.contractNumber')
            ->whereNotIn('IncomPays.contractNumber',['','б/н'])


            ->join('lead_params','lead_params.contract_number','=','IncomPays.contractNumber')
            ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
            ->join('object_types','object_types.type_id','=','object_params.type_id')
            ->join('contacts','contacts.contact_id','=','lead_params.client_id')
            ->where('object_params.owner',$agent->name)
            ->where('IncomPays.incomDate','>=',$df)
            ->where('IncomPays.incomDate','<=',$dt)
            //->where('object_types.class_property','!=','contry')//исключили загородную
            ->groupBy('IncomPays.contractNumber')
            ->get();




        //общая сумма поступлений
        $totalIncomeSum = 0;
        foreach ($data as $incomeItem){
            $totalIncomeSum+=$incomeItem->income_sum;
        }


        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();


        //колонка A
        $sheet->getColumnDimension('A')->setWidth(2);

        //название листа
        $sheet->setTitle('Отчет агента');

        //вставка лого
        $drawing = new Drawing();
        $drawing->setName('agent_header');
        $drawing->setDescription('agent_header');
        $drawing->setPath(public_path('/img/akb_dom.png'));
        $drawing->setHeight(112.5);
        $drawing->setCoordinates('B1');
        $drawing->setWorksheet($sheet);



        //заголовок
        $sheet->setCellValue('B8', 'Исх.№___ от ___________ 20__ г.')->getStyle("B8")->getFont()->setSize(9);

        $sheet->setCellValue('B10', 'ОТЧЕТ АГЕНТА')->getStyle("B10")->getFont()->setSize(9);
        $sheet->mergeCells('B10:L10');
        $sheet->getStyle('B10')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);



        //шапка отчета

        $sheet->setCellValue('B11', 'Общество с ограниченной ответственностью «Ак Барс Дом», именуемое в дальнейшем « Агент», в лице исполнительного директора Вассермана Антона Владимировича, действующего на основании доверенности №1 от 24.12.2019г. , составил настоящий отчет за период с '.$dateFrom.' г. по '.$dateTo.' г. о нижеследующем:
1. В соответствии с Договором №'.$agent->agentcontract_number.' от '.$agentContractDate.' г. Агент  организовал:
- реализацию имущества '.$agent->name.' путем заключения договоров участия в долевом строительстве, договоров купли-продажи;
2. При выполнении поручения денежные средства, поступившие от продажи площадей в рамках Договора №'.$agent->agentcontract_number.' от '.$agentContractDate.' г. за период с '.$dateFrom.' г. по '.$dateTo.' г. составили: '.$totalIncomeSum.' руб., в том числе:'



        )->getStyle("B11")->getFont()->setSize(9);

        $sheet->mergeCells('B11:L11');
        $sheet->getRowDimension('11')->setRowHeight(100);
        $sheet->getStyle('B11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);





       //получаем квартиры
        $objectsByPervichka = $data
            ->where('class_property','pervichka')
            ->sortBy('contractNumber')
            ->groupBy('house_number');


        //следующая строка
        $highestRow = 13;
        $pervichkaTotalPeriodSum  = 0;

        if ($objectsByPervichka->count()>0){
            foreach($objectsByPervichka as $house_number=>$houseArr){

                $highestRow = $highestRow+1;
                $sheet->getRowDimension($highestRow)->setRowHeight(30);
                //строка для объединения
                $mergeRow= $highestRow+1;

                //шапка таблицы по квартирам
                $sheet->setCellValue('B'.$highestRow, '№ дома');
                $sheet->mergeCells('B'.$highestRow.':B'.$mergeRow);

                $sheet->setCellValue('C'.$highestRow, '№ квартиры');
                $sheet->mergeCells('C'.$highestRow.':C'.$mergeRow);

                $sheet->setCellValue('D'.$highestRow, 'Покупатель');
                $sheet->mergeCells('D'.$highestRow.':D'.$mergeRow);

                $sheet->setCellValue('E'.$highestRow, '№ договора');
                $sheet->mergeCells('E'.$highestRow.':E'.$mergeRow);

                $sheet->setCellValue('F'.$highestRow, 'Дата договора');
                $sheet->mergeCells('F'.$highestRow.':F'.$mergeRow);

                $sheet->setCellValue('G'.$highestRow, 'Стоимость по договору, руб.');
                $sheet->mergeCells('G'.$highestRow.':G'.$mergeRow);

                $sheet->setCellValue('H'.$highestRow, 'Поступления, руб');
                $sheet->mergeCells('H'.$highestRow.':I'.$highestRow);

                $sheet->setCellValue('H'.$mergeRow, 'нарастающим итогом');
                $sheet->setCellValue('I'.$mergeRow, 'за отчетный период');

                $sheet->setCellValue('J'.$highestRow, 'Задолженность, руб');
                $sheet->mergeCells('J'.$highestRow.':J'.$mergeRow);

                $sheet->setCellValue('K'.$highestRow, 'Конечный срок возврата долга');
                $sheet->mergeCells('K'.$highestRow.':K'.$mergeRow);

                $sheet->setCellValue('L'.$highestRow, 'Подан в УФРС');
                $sheet->mergeCells('L'.$highestRow.':L'.$mergeRow);

                $sheet->getStyle('B'.$highestRow .':L'.$mergeRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

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

                $sheet ->getStyle('B'.$highestRow .':L'.$mergeRow)->applyFromArray($styleHeader);



                $highestRow = $highestRow+2;

                //атворазмер
                $sheet->getColumnDimension("E")->setAutoSize(true);
                $sheet->getColumnDimension("D")->setAutoSize(true);


                //фомат цифровой у сумм
                $sheet->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('H')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode('### ### ### ###');

                $houseTotalSum = 0;
                $houseTotalPeriodSum = 0;
                $houseTotalDebtSum = 0;

                foreach ($houseArr as $object){

                    $sheet->setCellValue('B'.$highestRow, (string)$object->house_number);
                    $sheet->setCellValue('C'.$highestRow, $object->object_number);
                    $sheet->setCellValue('D'.$highestRow, $object->client_name);
                    $sheet->setCellValue('E'.$highestRow, $object->contractNumber);
                    $sheet->setCellValue('F'.$highestRow, Carbon::createFromFormat('Y-m-d',$object->contract_date)->format('d.m.Y'));
                    $sheet->setCellValue('G'.$highestRow, $object->contract_sum);

                    //поступления нарастающим итогом

                    $totalSumByObject = DB::table('IncomPays')
                        ->select(DB::raw(
                            "
                          SUM(IncomPays.sum) as income_sum
                "))
                        ->where('IncomPays.contractNumber','=',$object->contractNumber)
                        ->where('IncomPays.incomDate','<=',$dt)
                        ->first('income_sum');


                    $sheet->setCellValue('H'.$highestRow, $totalSumByObject->income_sum);

                    //поступления за период
                    $sheet->setCellValue('I'.$highestRow, $object->income_sum);

                    //задолженность

                    $sheet->setCellValue('J'.$highestRow, $object->contract_sum - $totalSumByObject->income_sum);
                    if ($object->contract_sum - $totalSumByObject->income_sum != 0){
                        $sheet->getStyle('J'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                    }

                    //Подан в УФРС
                    if ($object->filing_date !=null) {
                        $sheet->setCellValue('L' . $highestRow, Carbon::createFromFormat('Y-m-d',$object->filing_date)->format('d.m.Y'));
                    }


                    //Всего
                    $houseTotalSum+=$totalSumByObject->income_sum;
                    $houseTotalPeriodSum += $object->income_sum;
                    $houseTotalDebtSum += $object->contract_sum - $totalSumByObject->income_sum;



                    $sheet ->getStyle('B'.$highestRow.':L'.$highestRow)->applyFromArray($styleBody);

                    $highestRow++;
                }


                // ВСЕГО
                $sheet->setCellValue('F'.$highestRow,'Всего:');
                $sheet->setCellValue('H'.$highestRow,$houseTotalSum);
                $sheet->setCellValue('I'.$highestRow,$houseTotalPeriodSum);
                $sheet->setCellValue('J'.$highestRow,$houseTotalDebtSum);
                if ($houseTotalDebtSum != 0){
                    $sheet->getStyle('J'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet ->getStyle('B'.$highestRow.':L'.$highestRow)->getFont()->setSize(9)->setBold(true);

                $sheet ->getStyle('B'.$highestRow.':L'.$highestRow)->applyFromArray($styleBody);

                $pervichkaTotalPeriodSum+=$houseTotalPeriodSum;

                $highestRow++;
            }
            $spaceRow = $highestRow+1;
            $sheet->mergeCells('A'.$spaceRow .':M'.$spaceRow);
        }






////////////////////////////////////ОФИСЫ///////////////////////////

        //получаем офисы
        $objectsByCommercial = $data
            ->where('class_property','commercial')
            ->sortBy('contractNumber')
            ->groupBy('house_number');


       //следующая строка
        $highestRow = $highestRow+2;

        $commercialTotalPeriodSum = 0;
        if ($objectsByCommercial->count()>0) {
            foreach ($objectsByCommercial as $house_number => $houseArr) {

                $highestRow = $highestRow + 1;
                $sheet->getRowDimension($highestRow)->setRowHeight(30);
                //строка для объединения
                $mergeRow = $highestRow + 1;

                //шапка таблицы по квартирам
                $sheet->setCellValue('B' . $highestRow, '№ дома');
                $sheet->mergeCells('B' . $highestRow . ':B' . $mergeRow);

                $sheet->setCellValue('C' . $highestRow, '№ Офисного помещения');
                $sheet->mergeCells('C' . $highestRow . ':C' . $mergeRow);

                $sheet->setCellValue('D' . $highestRow, 'Покупатель');
                $sheet->mergeCells('D' . $highestRow . ':D' . $mergeRow);

                $sheet->setCellValue('E' . $highestRow, '№ договора');
                $sheet->mergeCells('E' . $highestRow . ':E' . $mergeRow);

                $sheet->setCellValue('F' . $highestRow, 'Дата договора');
                $sheet->mergeCells('F' . $highestRow . ':F' . $mergeRow);

                $sheet->setCellValue('G' . $highestRow, 'Стоимость по договору, руб.');
                $sheet->mergeCells('G' . $highestRow . ':G' . $mergeRow);

                $sheet->setCellValue('H' . $highestRow, 'Поступления, руб');
                $sheet->mergeCells('H' . $highestRow . ':I' . $highestRow);

                $sheet->setCellValue('H' . $mergeRow, 'нарастающим итогом');
                $sheet->setCellValue('I' . $mergeRow, 'за отчетный период');

                $sheet->setCellValue('J' . $highestRow, 'Задолженность, руб');
                $sheet->mergeCells('J' . $highestRow . ':J' . $mergeRow);

                $sheet->setCellValue('K' . $highestRow, 'Конечный срок возврата долга');
                $sheet->mergeCells('K' . $highestRow . ':K' . $mergeRow);

                $sheet->setCellValue('L' . $highestRow, 'Подан в УФРС');
                $sheet->mergeCells('L' . $highestRow . ':L' . $mergeRow);

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $styleHeader = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => true,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $styleBody = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => false,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)->applyFromArray($styleHeader);


                $highestRow = $highestRow + 2;

                //атворазмер
                $sheet->getColumnDimension("E")->setAutoSize(true);
                $sheet->getColumnDimension("D")->setAutoSize(true);


                //фомат цифровой у сумм
                $sheet->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('H')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode('### ### ### ###');

                $houseTotalSum = 0;
                $houseTotalPeriodSum = 0;
                $houseTotalDebtSum = 0;

                foreach ($houseArr as $object) {

                    $sheet->setCellValue('B' . $highestRow, (string)$object->house_number);
                    $sheet->setCellValue('C' . $highestRow, $object->object_number);
                    $sheet->setCellValue('D' . $highestRow, $object->client_name);
                    $sheet->setCellValue('E' . $highestRow, $object->contractNumber);
                    $sheet->setCellValue('F' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->contract_date)->format('d.m.Y'));
                    $sheet->setCellValue('G' . $highestRow, $object->contract_sum);

                    //поступления нарастающим итогом

                    $totalSumByObject = DB::table('IncomPays')
                        ->select(DB::raw(
                            "
                          SUM(IncomPays.sum) as income_sum
                "))
                        ->where('IncomPays.contractNumber', '=', $object->contractNumber)
                        ->where('IncomPays.incomDate', '<=', $dt)
                        ->first('income_sum');


                    $sheet->setCellValue('H' . $highestRow, $totalSumByObject->income_sum);

                    //поступления за период
                    $sheet->setCellValue('I' . $highestRow, $object->income_sum);

                    //задолженность

                    $sheet->setCellValue('J' . $highestRow, $object->contract_sum - $totalSumByObject->income_sum);
                    if ($object->contract_sum - $totalSumByObject->income_sum != 0) {
                        $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                    }

                    //Подан в УФРС
                    if ($object->filing_date != null) {
                        $sheet->setCellValue('L' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->filing_date)->format('d.m.Y'));
                    }


                    //Всего
                    $houseTotalSum += $totalSumByObject->income_sum;
                    $houseTotalPeriodSum += $object->income_sum;
                    $houseTotalDebtSum += $object->contract_sum - $totalSumByObject->income_sum;


                    $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                    $highestRow++;
                }


                // ВСЕГО
                $sheet->setCellValue('F' . $highestRow, 'Всего:');
                $sheet->setCellValue('H' . $highestRow, $houseTotalSum);
                $sheet->setCellValue('I' . $highestRow, $houseTotalPeriodSum);
                $sheet->setCellValue('J' . $highestRow, $houseTotalDebtSum);
                if ($houseTotalDebtSum != 0) {
                    $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->getFont()->setSize(9)->setBold(true);

                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);


                $commercialTotalPeriodSum += $houseTotalPeriodSum;
                $highestRow++;
            }


            $spaceRow = $highestRow + 1;
            $sheet->mergeCells('A' . $spaceRow . ':M' . $spaceRow);
        }

        ////////////////////////////////////ПАРКИНГ///////////////////////////

        //получаем паркинги
        $objectsByParking = $data
            ->where('class_property','parking')
            ->sortBy('contractNumber')
            ->groupBy('house_number');

        $parkingTotalPeriodSum=0;
        if ($objectsByParking->count()>0) {
            foreach ($objectsByParking as $house_number => $houseArr) {

                $highestRow = $highestRow + 1;
                $sheet->getRowDimension($highestRow)->setRowHeight(30);
                //строка для объединения
                $mergeRow = $highestRow + 1;

                //шапка таблицы по квартирам
                $sheet->setCellValue('B' . $highestRow, '№ дома');
                $sheet->mergeCells('B' . $highestRow . ':B' . $mergeRow);

                $sheet->setCellValue('C' . $highestRow, '№ паркинга');
                $sheet->mergeCells('C' . $highestRow . ':C' . $mergeRow);

                $sheet->setCellValue('D' . $highestRow, 'Покупатель');
                $sheet->mergeCells('D' . $highestRow . ':D' . $mergeRow);

                $sheet->setCellValue('E' . $highestRow, '№ договора');
                $sheet->mergeCells('E' . $highestRow . ':E' . $mergeRow);

                $sheet->setCellValue('F' . $highestRow, 'Дата договора');
                $sheet->mergeCells('F' . $highestRow . ':F' . $mergeRow);

                $sheet->setCellValue('G' . $highestRow, 'Стоимость по договору, руб.');
                $sheet->mergeCells('G' . $highestRow . ':G' . $mergeRow);

                $sheet->setCellValue('H' . $highestRow, 'Поступления, руб');
                $sheet->mergeCells('H' . $highestRow . ':I' . $highestRow);

                $sheet->setCellValue('H' . $mergeRow, 'нарастающим итогом');
                $sheet->setCellValue('I' . $mergeRow, 'за отчетный период');

                $sheet->setCellValue('J' . $highestRow, 'Задолженность, руб');
                $sheet->mergeCells('J' . $highestRow . ':J' . $mergeRow);

                $sheet->setCellValue('K' . $highestRow, 'Конечный срок возврата долга');
                $sheet->mergeCells('K' . $highestRow . ':K' . $mergeRow);

                $sheet->setCellValue('L' . $highestRow, 'Подан в УФРС');
                $sheet->mergeCells('L' . $highestRow . ':L' . $mergeRow);

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $styleHeader = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => true,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $styleBody = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => false,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)->applyFromArray($styleHeader);


                $highestRow = $highestRow + 2;

                //атворазмер
                $sheet->getColumnDimension("E")->setAutoSize(true);
                $sheet->getColumnDimension("D")->setAutoSize(true);


                //фомат цифровой у сумм
                $sheet->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('H')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode('### ### ### ###');

                $houseTotalSum = 0;
                $houseTotalPeriodSum = 0;
                $houseTotalDebtSum = 0;

                foreach ($houseArr as $object) {

                    $sheet->setCellValue('B' . $highestRow, (string)$object->house_number);
                    $sheet->setCellValue('C' . $highestRow, $object->object_number);
                    $sheet->setCellValue('D' . $highestRow, $object->client_name);
                    $sheet->setCellValue('E' . $highestRow, $object->contractNumber);
                    $sheet->setCellValue('F' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->contract_date)->format('d.m.Y'));
                    $sheet->setCellValue('G' . $highestRow, $object->contract_sum);

                    //поступления нарастающим итогом

                    $totalSumByObject = DB::table('IncomPays')
                        ->select(DB::raw(
                            "
                          SUM(IncomPays.sum) as income_sum
                "))
                        ->where('IncomPays.contractNumber', '=', $object->contractNumber)
                        ->where('IncomPays.incomDate', '<=', $dt)
                        ->first('income_sum');


                    $sheet->setCellValue('H' . $highestRow, $totalSumByObject->income_sum);

                    //поступления за период
                    $sheet->setCellValue('I' . $highestRow, $object->income_sum);

                    //задолженность

                    $sheet->setCellValue('J' . $highestRow, $object->contract_sum - $totalSumByObject->income_sum);
                    if ($object->contract_sum - $totalSumByObject->income_sum != 0) {
                        $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                    }

                    //Подан в УФРС
                    if ($object->filing_date != null) {
                        $sheet->setCellValue('L' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->filing_date)->format('d.m.Y'));
                    }


                    //Всего
                    $houseTotalSum += $totalSumByObject->income_sum;
                    $houseTotalPeriodSum += $object->income_sum;
                    $houseTotalDebtSum += $object->contract_sum - $totalSumByObject->income_sum;


                    $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                    $highestRow++;
                }


                // ВСЕГО
                $sheet->setCellValue('F' . $highestRow, 'Всего:');
                $sheet->setCellValue('H' . $highestRow, $houseTotalSum);
                $sheet->setCellValue('I' . $highestRow, $houseTotalPeriodSum);
                $sheet->setCellValue('J' . $highestRow, $houseTotalDebtSum);
                if ($houseTotalDebtSum != 0) {
                    $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->getFont()->setSize(9)->setBold(true);

                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                $parkingTotalPeriodSum += $houseTotalPeriodSum;

                $highestRow++;
            }


            $spaceRow = $highestRow + 1;
            $sheet->mergeCells('A' . $spaceRow . ':M' . $spaceRow);
        }
        ////////////////////////////////////КЛАДОВЫЕ///////////////////////////

       //получаем кладовые
        $objectsByPantry = $data
            ->where('class_property','pantry')
            ->sortBy('contractNumber')
            ->groupBy('house_number');
        $pantryTotalPeriodSum = 0;
        if ($objectsByPantry->count()>0) {
            foreach ($objectsByPantry as $house_number => $houseArr) {

                $highestRow = $highestRow + 1;
                $sheet->getRowDimension($highestRow)->setRowHeight(30);
                //строка для объединения
                $mergeRow = $highestRow + 1;

                //шапка таблицы по квартирам
                $sheet->setCellValue('B' . $highestRow, '№ дома');
                $sheet->mergeCells('B' . $highestRow . ':B' . $mergeRow);

                $sheet->setCellValue('C' . $highestRow, '№ помещения');
                $sheet->mergeCells('C' . $highestRow . ':C' . $mergeRow);

                $sheet->setCellValue('D' . $highestRow, 'Покупатель');
                $sheet->mergeCells('D' . $highestRow . ':D' . $mergeRow);

                $sheet->setCellValue('E' . $highestRow, '№ договора');
                $sheet->mergeCells('E' . $highestRow . ':E' . $mergeRow);

                $sheet->setCellValue('F' . $highestRow, 'Дата договора');
                $sheet->mergeCells('F' . $highestRow . ':F' . $mergeRow);

                $sheet->setCellValue('G' . $highestRow, 'Стоимость по договору, руб.');
                $sheet->mergeCells('G' . $highestRow . ':G' . $mergeRow);

                $sheet->setCellValue('H' . $highestRow, 'Поступления, руб');
                $sheet->mergeCells('H' . $highestRow . ':I' . $highestRow);

                $sheet->setCellValue('H' . $mergeRow, 'нарастающим итогом');
                $sheet->setCellValue('I' . $mergeRow, 'за отчетный период');

                $sheet->setCellValue('J' . $highestRow, 'Задолженность, руб');
                $sheet->mergeCells('J' . $highestRow . ':J' . $mergeRow);

                $sheet->setCellValue('K' . $highestRow, 'Конечный срок возврата долга');
                $sheet->mergeCells('K' . $highestRow . ':K' . $mergeRow);

                $sheet->setCellValue('L' . $highestRow, 'Подан в УФРС');
                $sheet->mergeCells('L' . $highestRow . ':L' . $mergeRow);

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $styleHeader = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => true,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $styleBody = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => false,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)->applyFromArray($styleHeader);


                $highestRow = $highestRow + 2;

                //атворазмер
                $sheet->getColumnDimension("E")->setAutoSize(true);
                $sheet->getColumnDimension("D")->setAutoSize(true);


                //фомат цифровой у сумм
                $sheet->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('H')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode('### ### ### ###');

                $houseTotalSum = 0;
                $houseTotalPeriodSum = 0;
                $houseTotalDebtSum = 0;

                foreach ($houseArr as $object) {

                    $sheet->setCellValue('B' . $highestRow, (string)$object->house_number);
                    $sheet->setCellValue('C' . $highestRow, $object->object_number);
                    $sheet->setCellValue('D' . $highestRow, $object->client_name);
                    $sheet->setCellValue('E' . $highestRow, $object->contractNumber);
                    $sheet->setCellValue('F' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->contract_date)->format('d.m.Y'));
                    $sheet->setCellValue('G' . $highestRow, $object->contract_sum);

                    //поступления нарастающим итогом

                    $totalSumByObject = DB::table('IncomPays')
                        ->select(DB::raw(
                            "
                          SUM(IncomPays.sum) as income_sum
                "))
                        ->where('IncomPays.contractNumber', '=', $object->contractNumber)
                        ->where('IncomPays.incomDate', '<=', $dt)
                        ->first('income_sum');


                    $sheet->setCellValue('H' . $highestRow, $totalSumByObject->income_sum);

                    //поступления за период
                    $sheet->setCellValue('I' . $highestRow, $object->income_sum);

                    //задолженность

                    $sheet->setCellValue('J' . $highestRow, $object->contract_sum - $totalSumByObject->income_sum);
                    if ($object->contract_sum - $totalSumByObject->income_sum != 0) {
                        $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                    }

                    //Подан в УФРС
                    if ($object->filing_date != null) {
                        $sheet->setCellValue('L' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->filing_date)->format('d.m.Y'));
                    }


                    //Всего
                    $houseTotalSum += $totalSumByObject->income_sum;
                    $houseTotalPeriodSum += $object->income_sum;
                    $houseTotalDebtSum += $object->contract_sum - $totalSumByObject->income_sum;


                    $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                    $highestRow++;
                }


                // ВСЕГО
                $sheet->setCellValue('F' . $highestRow, 'Всего:');
                $sheet->setCellValue('H' . $highestRow, $houseTotalSum);
                $sheet->setCellValue('I' . $highestRow, $houseTotalPeriodSum);
                $sheet->setCellValue('J' . $highestRow, $houseTotalDebtSum);
                if ($houseTotalDebtSum != 0) {
                    $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->getFont()->setSize(9)->setBold(true);

                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                $pantryTotalPeriodSum += $houseTotalPeriodSum;

                $highestRow++;
            }

        }



        ////////////////////////////////////ЗАГОРОДНАЯ НЕДВИЖИМОСТЬ///////////////////////////

        //получаем кладовые
        $objectsByCountry = $data
            ->where('class_property','contry')
            ->sortBy('contractNumber')
            ->groupBy('house_number');
        $countryTotalPeriodSum = 0;
        if ($objectsByCountry->count()>0) {
            foreach ($objectsByCountry as $house_number => $houseArr) {

                $highestRow = $highestRow + 1;
                $sheet->getRowDimension($highestRow)->setRowHeight(30);
                //строка для объединения
                $mergeRow = $highestRow + 1;

                //шапка таблицы по квартирам
                $sheet->setCellValue('B' . $highestRow, '№ дома');
                $sheet->mergeCells('B' . $highestRow . ':B' . $mergeRow);

                $sheet->setCellValue('C' . $highestRow, '№ помещения');
                $sheet->mergeCells('C' . $highestRow . ':C' . $mergeRow);

                $sheet->setCellValue('D' . $highestRow, 'Покупатель');
                $sheet->mergeCells('D' . $highestRow . ':D' . $mergeRow);

                $sheet->setCellValue('E' . $highestRow, '№ договора');
                $sheet->mergeCells('E' . $highestRow . ':E' . $mergeRow);

                $sheet->setCellValue('F' . $highestRow, 'Дата договора');
                $sheet->mergeCells('F' . $highestRow . ':F' . $mergeRow);

                $sheet->setCellValue('G' . $highestRow, 'Стоимость по договору, руб.');
                $sheet->mergeCells('G' . $highestRow . ':G' . $mergeRow);

                $sheet->setCellValue('H' . $highestRow, 'Поступления, руб');
                $sheet->mergeCells('H' . $highestRow . ':I' . $highestRow);

                $sheet->setCellValue('H' . $mergeRow, 'нарастающим итогом');
                $sheet->setCellValue('I' . $mergeRow, 'за отчетный период');

                $sheet->setCellValue('J' . $highestRow, 'Задолженность, руб');
                $sheet->mergeCells('J' . $highestRow . ':J' . $mergeRow);

                $sheet->setCellValue('K' . $highestRow, 'Конечный срок возврата долга');
                $sheet->mergeCells('K' . $highestRow . ':K' . $mergeRow);

                $sheet->setCellValue('L' . $highestRow, 'Подан в УФРС');
                $sheet->mergeCells('L' . $highestRow . ':L' . $mergeRow);

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)
                    ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $styleHeader = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => true,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $styleBody = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]

                    ],

                    'font' => [
                        'bold' => false,
                        'color' => array('rgb' => '000000'),
                        'size' => 9,

                    ]

                ];

                $sheet->getStyle('B' . $highestRow . ':L' . $mergeRow)->applyFromArray($styleHeader);


                $highestRow = $highestRow + 2;

                //атворазмер
                $sheet->getColumnDimension("E")->setAutoSize(true);
                $sheet->getColumnDimension("D")->setAutoSize(true);


                //фомат цифровой у сумм
                $sheet->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('H')->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode('### ### ### ###');

                $houseTotalSum = 0;
                $houseTotalPeriodSum = 0;
                $houseTotalDebtSum = 0;

                foreach ($houseArr as $object) {

                    $sheet->setCellValue('B' . $highestRow, (string)$object->house_number);
                    $sheet->setCellValue('C' . $highestRow, $object->object_number);
                    $sheet->setCellValue('D' . $highestRow, $object->client_name);
                    $sheet->setCellValue('E' . $highestRow, $object->contractNumber);
                    $sheet->setCellValue('F' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->contract_date)->format('d.m.Y'));
                    $sheet->setCellValue('G' . $highestRow, $object->contract_sum);

                    //поступления нарастающим итогом

                    $totalSumByObject = DB::table('IncomPays')
                        ->select(DB::raw(
                            "
                          SUM(IncomPays.sum) as income_sum
                "))
                        ->where('IncomPays.contractNumber', '=', $object->contractNumber)
                        ->where('IncomPays.incomDate', '<=', $dt)
                        ->first('income_sum');


                    $sheet->setCellValue('H' . $highestRow, $totalSumByObject->income_sum);

                    //поступления за период
                    $sheet->setCellValue('I' . $highestRow, $object->income_sum);

                    //задолженность

                    $sheet->setCellValue('J' . $highestRow, $object->contract_sum - $totalSumByObject->income_sum);
                    if ($object->contract_sum - $totalSumByObject->income_sum != 0) {
                        $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                    }

                    //Подан в УФРС
                    if ($object->filing_date != null) {
                        $sheet->setCellValue('L' . $highestRow, Carbon::createFromFormat('Y-m-d', $object->filing_date)->format('d.m.Y'));
                    }


                    //Всего
                    $houseTotalSum += $totalSumByObject->income_sum;
                    $houseTotalPeriodSum += $object->income_sum;
                    $houseTotalDebtSum += $object->contract_sum - $totalSumByObject->income_sum;


                    $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                    $highestRow++;
                }


                // ВСЕГО
                $sheet->setCellValue('F' . $highestRow, 'Всего:');
                $sheet->setCellValue('H' . $highestRow, $houseTotalSum);
                $sheet->setCellValue('I' . $highestRow, $houseTotalPeriodSum);
                $sheet->setCellValue('J' . $highestRow, $houseTotalDebtSum);
                if ($houseTotalDebtSum != 0) {
                    $sheet->getStyle('J' . $highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                }
                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->getFont()->setSize(9)->setBold(true);

                $sheet->getStyle('B' . $highestRow . ':L' . $highestRow)->applyFromArray($styleBody);

                $countryTotalPeriodSum += $houseTotalPeriodSum;

                $highestRow++;
            }

        }




        //////////////////ФУТЕР///////////////////////////////////


        $highestRow = $highestRow+2;


        $sheet->setCellValue('B'.$highestRow,'Копии первичных документов и договоры, подтверждающие факт полученных исполнения агентом обязательств по Договору за отчетный период прилагаются.'

        )->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;


        $agentSum = ($pervichkaTotalPeriodSum+$commercialTotalPeriodSum+$parkingTotalPeriodSum+$pantryTotalPeriodSum+$countryTotalPeriodSum)*0.02;

        $sheet->setCellValue('B'.$highestRow,'Агентом   выполнены   обязательства, обусловленные Договором № '.$agent->agentcontract_number.' от '.$agentContractDate.' г. за период с '.$dateFrom.' г. по '.$dateTo.' г.
- сумма агентского вознаграждения согласно п.3.1. Договора №'.$agent->agentcontract_number.' от '.$agentContractDate.' г. от поступивших денежных средств составляет '. round($agentSum,2). 'руб.,  без налога (НДС).  '
        )->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $sheet->getRowDimension($highestRow)->setRowHeight(30);
        $sheet->getStyle('B'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        //реквизиты
        $highestRow = $highestRow+1;

        $sheet->setCellValue('B'.$highestRow,'Подписи сторон:')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);
        $sheet->getStyle('B'.$highestRow)
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'Отчет принял:')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);


        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'Принципал:')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,$agent->name)->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,$agent->adress)->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;

        $requisites = $agent->requisites;
        $requisitesArr = explode(',',$requisites);

        foreach ($requisitesArr as $item){
            $sheet->setCellValue('B'.$highestRow,$item)->getStyle('B'.$highestRow)->getFont()->setSize(9);
            $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);
            $highestRow++;
        }

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'Директор _______________/'.$agent->head_name.'/')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'                   М.П.')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);



        $highestRow = $highestRow+2;


        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'Отчет сдал:')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'Агент:')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'ООО «Ак Барс Дом»')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'420124, РТ, г.Казань, ул.Меридианная 1')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'т. (843) 272-09-60, 273-53-87')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'ИНН 1657100885 КПП ')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'р/с 40702810700020006093')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'в ОАО "АК БАРС" БАНК ')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'к/с 30101810000000000805')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'БИК 049205805')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'ОГРН 1101690072032')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'Директор _______________/Вассерман Антон Владимирович/')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);

        $highestRow = $highestRow+1;
        $sheet->setCellValue('B'.$highestRow,'                   М.П.')->getStyle('B'.$highestRow)->getFont()->setSize(9);
        $sheet->mergeCells('B'.$highestRow .':L'.$highestRow);












////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////     АКТ     ///////////////////////////////////////////////////////////////////////////////
/// /////////////////////////////////////////////////////////////////////////////////////////////////
        $sheet2 = $spreadsheet->createSheet();

        //колонка A
        $sheet2->getColumnDimension('A')->setWidth(2);

        //название листа
        $sheet2->setTitle('Акт');

        //вставка лого
        $drawing2 = new Drawing();
        $drawing2->setName('agent_header');
        $drawing2->setDescription('agent_header');
        $drawing2->setPath(public_path('/img/akb_dom.png'));
        $drawing2->setHeight(112.5);
        $drawing2->setCoordinates('B1');
        $drawing2->setWorksheet($sheet2);



        //заголовок
        $sheet2->setCellValue('B8', 'Исх.№___ от ___________ 20__ г.')->getStyle("B8")->getFont()->setSize(9);

        $sheet2->setCellValue('B10', 'АКТ оказанных услуг')->getStyle("B10")->getFont()->setSize(9);
        $sheet2->mergeCells('B10:H10');
        $sheet2->getStyle('B10')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);





        //шапка отчета

        $sheet2->setCellValue('B11', $agent->name.' ,  именуемое в дальнейшем "Принципал", в лице ' .$agent->head_name_2.' , действующего на основании Устава, с одной стороны, и ООО «Ак Барс Дом», именуемое в дальнейшем "Агент", в лице исполнительного директора Вассермана Антона Владимировича, действующего на основании доверенности №1 от 24.12.2019г. , совместно именуемые «Стороны», составили настоящий АКТ о нижеследующем:
1. Агент в соответствии с Договором №'.$agent->agentcontract_number.' от '.$agentContractDate. ' г. организовал:
- реализацию имущества Принципала путем заключения договоров участия в долевом строительстве, договоров купли-продажи.
2. Объем полученных доходов от продажи помещений за период с '.$dateFrom.' г. по '.$dateTo.'  г. составили: '.$totalIncomeSum.' руб. (перечень указан в отчете Агента).  '



        )->getStyle("B11")->getFont()->setSize(9);


        $sheet2->mergeCells('B11:H11');
        $sheet2->getRowDimension('11')->setRowHeight(100);
        $sheet2->getStyle('B11')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);




        $sheet2->setCellValue('B14','Заказчик:')->getStyle('B14')->getFont()->setSize(9);
        $sheet2->mergeCells('B14:H14');


        //шапка таблицы



        $sheet2->setCellValue('B15','№')->getStyle('B15')->getFont()->setSize(9)->setBold(true);
        $sheet2->setCellValue('C15','Наименование  работы (услуги)')->getStyle('C15')->getFont()->setSize(9)->setBold(true);
        $sheet2->setCellValue('D15','Ед. изм.')->getStyle('D15')->getFont()->setSize(9)->setBold(true);
        $sheet2->setCellValue('E15','Количество')->getStyle('E15')->getFont()->setSize(9)->setBold(true);
        $sheet2->setCellValue('F15','Цена')->getStyle('F15')->getFont()->setSize(9)->setBold(true);
        $sheet2->setCellValue('G15','Сумма')->getStyle('G15')->getFont()->setSize(9)->setBold(true);



        //авторазмер
        foreach(range('C','G') as $columnID) {
            $sheet2->getColumnDimension($columnID)
                ->setAutoSize(true);
        }



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

        $sheet2->getStyle('B15:G15')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('C16','Агентские услуги: ')->getStyle('C16')->getFont()->setSize(9);
        $sheet2->mergeCells('C16:G16');

        //формат суммы
        //$sheet2->getStyle('F')->getNumberFormat()->setFormatCode('### ### ### ###');
        //$sheet2->getStyle('G')->getNumberFormat()->setFormatCode('### ### ### ###');


        //данные по первичке
        $sheet2->setCellValue('B17','1')->getStyle('B17')->getFont()->setSize(9);
        $sheet2->mergeCells('B17:B19');
        $sheet2->getStyle('B17')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C17','за  первичную недвижимость')->getStyle('C17')->getFont()->setSize(9);
        $sheet2->mergeCells('C17:C19');
        $sheet2->getStyle('C17')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D17','рубли')->getStyle('D17')->getFont()->setSize(9);
        $sheet2->mergeCells('D17:D19');
        $sheet2->getStyle('D17')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('E17','Итого')->getStyle('E17')->getFont()->setSize(9);
        $sheet2->setCellValue('F17',$pervichkaTotalPeriodSum)->getStyle('F17')->getFont()->setSize(9);

        $sheet2->setCellValue('G17',round($pervichkaTotalPeriodSum*0.02,2))->getStyle('G17')->getFont()->setSize(9);



        $sheet2->setCellValue('E18','Без налога (НДС)')->getStyle('E18')->getFont()->setSize(9);
        $sheet2->setCellValue('E19','Всего')->getStyle('E19')->getFont()->setSize(9);
        $sheet2->setCellValue('G19',round($pervichkaTotalPeriodSum*0.02,2))->getStyle('G19')->getFont()->setSize(9);




        //данные по коммерческой недвижимости
        $sheet2->setCellValue('B20','2')->getStyle('B20')->getFont()->setSize(9);
        $sheet2->mergeCells('B20:B22');
        $sheet2->getStyle('B20')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C20','за коммерческую недвижимость')->getStyle('C20')->getFont()->setSize(9);
        $sheet2->mergeCells('C20:C22');
        $sheet2->getStyle('C20')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D20','рубли')->getStyle('D20')->getFont()->setSize(9);
        $sheet2->mergeCells('D20:D22');
        $sheet2->getStyle('D20')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('E20','Итого')->getStyle('E20')->getFont()->setSize(9);
        if ($commercialTotalPeriodSum != null){
            $sheet2->setCellValue('F20',$commercialTotalPeriodSum)->getStyle('F20')->getFont()->setSize(9);

            $sheet2->setCellValue('G20',round($commercialTotalPeriodSum*0.02,2))->getStyle('G20')->getFont()->setSize(9);

        }



        $sheet2->setCellValue('E21','Без налога (НДС)')->getStyle('E21')->getFont()->setSize(9);
        $sheet2->setCellValue('E22','Всего')->getStyle('E22')->getFont()->setSize(9);
        if ($commercialTotalPeriodSum != null) {
            $sheet2->setCellValue('G22', round($commercialTotalPeriodSum * 0.02, 2))->getStyle('G22')->getFont()->setSize(9);
        }

        //данные по кладовкам
        $sheet2->setCellValue('B23','3')->getStyle('B23')->getFont()->setSize(9);
        $sheet2->mergeCells('B23:B25');
        $sheet2->getStyle('B23')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C23','за кладовки')->getStyle('C23')->getFont()->setSize(9);
        $sheet2->mergeCells('C23:C25');
        $sheet2->getStyle('C23')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D23','рубли')->getStyle('D23')->getFont()->setSize(9);
        $sheet2->mergeCells('D23:D25');
        $sheet2->getStyle('D23')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('E23','Итого')->getStyle('E23')->getFont()->setSize(9);
        if ($pantryTotalPeriodSum != null) {
            $sheet2->setCellValue('F23', $pantryTotalPeriodSum)->getStyle('F23')->getFont()->setSize(9);

            $sheet2->setCellValue('G23', round($pantryTotalPeriodSum * 0.02, 2))->getStyle('G23')->getFont()->setSize(9);

        }

        $sheet2->setCellValue('E24','Без налога (НДС)')->getStyle('E24')->getFont()->setSize(9);
        $sheet2->setCellValue('E25','Всего')->getStyle('E25')->getFont()->setSize(9);
        if ($pantryTotalPeriodSum != null) {
            $sheet2->setCellValue('G25', round($pantryTotalPeriodSum * 0.02, 2))->getStyle('G25')->getFont()->setSize(9);
        }


        //данные по паркингам
        $sheet2->setCellValue('B26','4')->getStyle('B26')->getFont()->setSize(9);
        $sheet2->mergeCells('B26:B28');
        $sheet2->getStyle('B26')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C26','за парковки')->getStyle('C26')->getFont()->setSize(9);
        $sheet2->mergeCells('C26:C28');
        $sheet2->getStyle('C26')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D26','рубли')->getStyle('D26')->getFont()->setSize(9);
        $sheet2->mergeCells('D26:D28');
        $sheet2->getStyle('D26')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('E26','Итого')->getStyle('E26')->getFont()->setSize(9);
        if ($parkingTotalPeriodSum != null) {
            $sheet2->setCellValue('F26', $parkingTotalPeriodSum)->getStyle('F26')->getFont()->setSize(9);

            $sheet2->setCellValue('G26', round($parkingTotalPeriodSum * 0.02, 2))->getStyle('G26')->getFont()->setSize(9);
        }


        $sheet2->setCellValue('E27','Без налога (НДС)')->getStyle('E27')->getFont()->setSize(9);
        $sheet2->setCellValue('E28','Всего')->getStyle('E28')->getFont()->setSize(9);
        if ($parkingTotalPeriodSum != null) {
            $sheet2->setCellValue('G28', round($parkingTotalPeriodSum * 0.02, 2))->getStyle('G28')->getFont()->setSize(9);

        }









//        //всего
//        $sheet2->setCellValue('B26','4')->getStyle('B26')->getFont()->setSize(9);
//        $sheet2->mergeCells('B26:B28');
//        $sheet2->getStyle('B26')
//            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);




        //данные по загородной недвижимости
        $sheet2->setCellValue('B29','5')->getStyle('B29')->getFont()->setSize(9);
        $sheet2->mergeCells('B29:B31');
        $sheet2->getStyle('B29')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('C29','за загородную недвижимость')->getStyle('C29')->getFont()->setSize(9);
        $sheet2->mergeCells('C29:C31');
        $sheet2->getStyle('C29')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D29','рубли')->getStyle('D29')->getFont()->setSize(9);
        $sheet2->mergeCells('D29:D31');
        $sheet2->getStyle('D29')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('E29','Итого')->getStyle('E29')->getFont()->setSize(9);
        if ($countryTotalPeriodSum != null) {
            $sheet2->setCellValue('F29', $countryTotalPeriodSum)->getStyle('F29')->getFont()->setSize(9);

            $sheet2->setCellValue('G29', round($countryTotalPeriodSum * 0.02, 2))->getStyle('G29')->getFont()->setSize(9);
        }


        $sheet2->setCellValue('E30','Без налога (НДС)')->getStyle('E30')->getFont()->setSize(9);
        $sheet2->setCellValue('E31','Всего')->getStyle('E31')->getFont()->setSize(9);
        if ($countryTotalPeriodSum != null) {
            $sheet2->setCellValue('G31', round($countryTotalPeriodSum * 0.02, 2))->getStyle('G31')->getFont()->setSize(9);

        }


        ///////////////////////////ВСЕГО/////////////////////


        $sheet2->mergeCells('B32:B34');

        $sheet2->setCellValue('C32','ВСЕГО:')->getStyle('C32')->getFont()->setSize(9);
        $sheet2->mergeCells('C32:C34');
        $sheet2->getStyle('C32')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('D32','рубли')->getStyle('D32')->getFont()->setSize(9);
        $sheet2->mergeCells('D32:D34');
        $sheet2->getStyle('D32')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('E32','Итого')->getStyle('E32')->getFont()->setSize(9);
        $sheet2->setCellValue('F32',$totalIncomeSum)->getStyle('F32')->getFont()->setSize(9);

        $sheet2->setCellValue('G32',round($totalIncomeSum*0.02,2))->getStyle('G32')->getFont()->setSize(9);



        $sheet2->setCellValue('E33','Без налога (НДС)')->getStyle('E33')->getFont()->setSize(9);
        $sheet2->setCellValue('E34','Всего')->getStyle('E34')->getFont()->setSize(9);
        $sheet2->setCellValue('G34',round($totalIncomeSum*0.02,2))->getStyle('G34')->getFont()->setSize(9);


        $sheet2 ->getStyle('B15:G34')->applyFromArray($styleBody);


        //3 пункт
        $sheet2->setCellValue('B36', '3. Отчет Агента за период с '.$dateFrom.' г. по '.$dateTo.' г. принят Принципалом без замечаний, стороны претензий друг к другу не имеют.'


        )->getStyle("B36")->getFont()->setSize(9);


        $sheet2->mergeCells('B36:H36');
        $sheet2->getRowDimension('36')->setRowHeight(20);
        $sheet2->getStyle('B36')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        //4 пункт
        $sheet2->setCellValue('B38', '4. Данный АКТ составлен в двух экземплярах на русском языке, по одному для каждой из сторон, оба экземпляра имеют одинаковую юридическую силу.'


        )->getStyle("B38")->getFont()->setSize(9);


        $sheet2->mergeCells('B38:H38');
        $sheet2->getRowDimension('38')->setRowHeight(25);
        $sheet2->getStyle('B38')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);



        $sheet2->setCellValue('B40', 'Подписи сторон:')->getStyle("B37")->getFont()->setSize(9);
        $sheet2->mergeCells('B40:G40');
        $sheet2->getStyle('B40')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        $sheet2->setCellValue('B42', 'Принципал: '. $agent->name)->getStyle("B42")->getFont()->setSize(9);
        $sheet2->mergeCells('B42:C42');
        $sheet2->getStyle('B42')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $sheet2->setCellValue('E42', 'Агент: ООО «Ак Барс Дом»')->getStyle("E42")->getFont()->setSize(9);
        $sheet2->mergeCells('E42:F42');
        $sheet2->getStyle('E42')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);


        $sheet2->setCellValue('B43', '_________________________('. $agent->head_name.')')->getStyle("B43")->getFont()->setSize(9);
        $sheet2->mergeCells('B43:C43');
        $sheet2->getStyle('B43')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $sheet2->setCellValue('E43', '_________________________(                    )')->getStyle("E43")->getFont()->setSize(9);
        $sheet2->mergeCells('E43:F43');
        $sheet2->getStyle('E43')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);



        $sheet2->setCellValue('B44', '            М.П.')->getStyle("B44")->getFont()->setSize(9);
        $sheet2->mergeCells('B44:C44');
        $sheet2->getStyle('B44')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

        $sheet2->setCellValue('E44', '            М.П.')->getStyle("E44")->getFont()->setSize(9);
        $sheet2->mergeCells('E44:F44');
        $sheet2->getStyle('E44')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);




        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_agent.xlsx"');
        $writer->save("php://output");

    }
}
