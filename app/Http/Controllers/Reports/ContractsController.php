<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
class ContractsController extends Controller
{


    public function index(){

        return view('reports.contracts.index');
    }
    public function makeReport(Request $request){

        //запрос


        $data = DB::table('lead_params')
//            ->whereIn('lead_params.stage',[
//                142,143,
//                19248475,
//                19248478,
//                19248481,
//                19248484,
//                19248487,
//                19248613,
//                19248616,
//                19248619,
//                19248622,
//                19248625,
//                19248628,
//                19248547,
//                19248550,
//                19248553,
//                19248556,
//                19248559,
//                19248562,
//                19248565,
//                19248568,
//                19248571,
//                19248574
//            ])
            ->select(DB::raw(
                "
                lead_params.stage,
                object_params.house_number,
                lead_params.contract_number,
                object_params.owner,
                lead_params.contract_date,
                object_params.complex,
                object_params.object_number,
                object_params.rooms_number,
                object_params.floor_number,
                object_params.total_area,
                object_params.BTI_area,
                object_params.price_meter,
                object_params.price,
                lead_params.contract_sum,
                object_params.object_id,
                object_params.type_id,
                lead_params.contract_type,
                lead_params.payment_type,
                lead_contract_type.contract_name,
                lead_params.subsidies,
                lead_params.object_categories,
                lead_params.special_offers,
                lead_params.client_id,
                lead_params.installment,
                lead_params.bank_ipoteka,
                contacts.name as client_name,
                contacts.phone as client_phone,
                ELT( MONTH(lead_params.contract_date), 'Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь') as month,
                abned_users.user_name as manager
                "))
            ->where('lead_params.pipeline_id','!=',1212220)

//            ->join('object_params','object_params.object_id', '=', 'lead_params.object_id')
            ->join('object_params', function($join){
                $join->on('object_params.object_id', '=', 'lead_params.object_id');
                $join->orOn('object_params.object_id', '=', 'lead_params.last_object_id');
            })
            ->join('lead_contract_type','lead_contract_type.contract_type_id','=','lead_params.contract_type')
            ->join('abned_users','abned_users.id','=','lead_params.employee_id')
            ->join('contacts','contacts.contact_id','=','lead_params.client_id')
            ->orderBy('contract_date','asc');



            if ($request->get('months')){

                $currentYear = Carbon::now()->year;

                $months = $request->get('months');


                foreach ($months as $month) {

                    switch ($month) {
                        case 1:
                            $monthsArr[] =  "Январь";
                            break;
                        case 2:
                            $monthsArr[] = "Февраль";
                            break;
                        case 3:
                            $monthsArr[] = "Март";
                            break;
                        case 4:
                            $monthsArr[] = "Апрель";
                            break;
                        case 5:
                            $monthsArr[] =  "Май";
                            break;
                        case 6:
                            $monthsArr[] = "Июнь";
                            break;
                        case 7:
                            $monthsArr[] = "Июль";
                            break;
                        case 8:
                            $monthsArr[] = "Август";
                            break;
                        case 9:
                            $monthsArr[] =  "Сентябрь";
                            break;
                        case 10:
                            $monthsArr[] = "Октябрь";
                            break;
                        case 11:
                            $monthsArr[] = "Ноябрь";
                            break;
                        case 12:
                            $monthsArr[] = "Декабрь";
                            break;
                    }

                }

                $monthsStr = implode(', ',$monthsArr);


                $date =$monthsStr .' '. $currentYear;



                $data = $data
//                    ->where('complex','Светлая долина')
                    ->whereYear('contract_date',$currentYear)
                    ->whereIn(DB::raw('MONTH(contract_date)'), $months);

            }
            elseif($request->get('dateRange')){


                $date = $request->get('dateRange');
                $dateArr = explode('-',$date);
                $dateFrom = str_replace(' ', '', $dateArr['0']);
                $dateTo = str_replace(' ', '', $dateArr['1']);

                $df = Carbon::createFromFormat('d.m.Y', $dateFrom);
                $dt = Carbon::createFromFormat('d.m.Y', $dateTo  );


                $data = $data
                ->where('contract_date','>=',$df)
                ->where('contract_date','<=',$dt);
            }

            else{
                return redirect()->back()->with('status','Не выбран период');
            }

            if (!$request->get('has_entered')){

                $data = $data ->where('lead_params.pipeline_id','!=',1878445);
            }
//            if ($request->get('dissolution')){
//                $data = $data ->where('lead_params.pipeline_id','=',1878445);
//            }


    // dd($data->toSql());
       // dd($data->where('complex','=','Казань XXI век (II очередь)')->get()->groupBy('month'));
        $data = $data->get()->groupBy('month');


     // dd($data);

        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет по реестру договоров');

        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);


        //заголовок
        $sheet->setCellValue('A1', 'Отчет по реестру договоров')->getStyle("A1")->getFont()->setSize(16);

        //Период
        $sheet->setCellValue('B1', 'Отчетный период: ' .$date )->getStyle("B1")->getFont()->setSize(16);


        //шапка таблицы
        $sheet->setCellValue('A3', 'Собственник');
        $sheet->setCellValue('B3', '№ дома');
        $sheet->setCellValue('C3', '№ договора');
        $sheet->setCellValue('D3', 'Расторжение');
        $sheet->setCellValue('E3', 'Не вступил в силу');
        $sheet->setCellValue('F3', 'Дата');
        $sheet->setCellValue('G3', '№ кв.');
        $sheet->setCellValue('H3', 'Паркинг');
        $sheet->setCellValue('I3', 'Офис');
        $sheet->setCellValue('J3', 'Этаж');
        $sheet->setCellValue('K3', 'Кол-во комнат');
        $sheet->setCellValue('L3', 'Общая площадь по СНиП, кв.м.');
        $sheet->setCellValue('M3', 'Стоимость кв.м.');
        $sheet->setCellValue('N3', 'Общая стоимость, руб.');
        $sheet->setCellValue('O3', 'Срок оплаты');
        $sheet->setCellValue('P3', 'Форма оплаты');
        $sheet->setCellValue('Q3', 'Срок рассрочки');
        $sheet->setCellValue('R3', 'Банк(ипотека)');
        $sheet->setCellValue('S3', 'Субсидии');
        $sheet->setCellValue('T3', 'Тип договора');
        $sheet->setCellValue('U3', 'Вид недвижимости');
        $sheet->setCellValue('V3', 'Ф.И.О.');
        $sheet->setCellValue('W3', 'Телефон клиента');
        $sheet->setCellValue('X3', 'Акции');
        $sheet->setCellValue('Y3', 'Менеджер');


        //Стили
        $styleBorder = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
                'inside'=>[
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ]
            ],

        ];

        $styleBorderOutline = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],

            ],

        ];



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

        $styleBorderBoldBottom = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],

            ],

        ];

        $sheet ->getStyle('A3:Y3')->applyFromArray($styleBorder);


//        //авторазмер
//        foreach(range('A','W') as $columnID) {
//            $sheet->getColumnDimension($columnID)
//                ->setAutoSize(true);
//        }


        //ширина колонок //
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(10);
        $sheet->getColumnDimension('K')->setWidth(10);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(15);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(40);
        $sheet->getColumnDimension('P')->setWidth(15);
        $sheet->getColumnDimension('Q')->setWidth(15);

        $sheet->getColumnDimension('R')->setWidth(25);

        $sheet->getColumnDimension('S')->setWidth(10);

        $sheet->getColumnDimension('T')->setWidth(15);

        $sheet->getColumnDimension('U')->setWidth(15);

        $sheet->getColumnDimension('V')->setWidth(35);

        $sheet->getColumnDimension('W')->setWidth(20);

        $sheet->getColumnDimension('X')->setWidth(15);
        $sheet->getColumnDimension('Y')->setWidth(35);


        //формат даты
        $spreadsheet->getActiveSheet()->getStyle('F')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY
            );

        //фомат цифровой у сумм
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode('### ### ### ###');


        $sheet->getRowDimension('3')->setRowHeight(30);
        $sheet->getStyle('A3:Y3')
            ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


        //начало динамических данных
        $highestRow = 4;
        foreach ($data as $month=>$valuesArr){


            //стиль шапки месяцев
            $sheet->setCellValue('A'.$highestRow,$month)->getStyle('A'.$highestRow)->getFont()->setBold(true);

            //объеднинение ячеек месяцев
            $sheet->mergeCells('A'.$highestRow.':W'.$highestRow);

            //следующая строкаBTI_area
            $highestRow = $highestRow+1;


            $groupComplex = $valuesArr->groupBy('complex');

            //dd($groupComplex);
            foreach($groupComplex as $complex=>$complexContracts){
                //dd($highestRow);
                $sheet->setCellValue('A'.$highestRow,$complex);

                //объеднинение ячеек комплекса
                $sheet->mergeCells('A'.$highestRow.':W'.$highestRow);
                //следующая строка
                $highestRow = $highestRow+1;

                $sum_total_area = 0;
                $sum_price = 0;
                foreach ($complexContracts as $complexContract) {

                    if($complexContract->BTI_area !=null || $complexContract->BTI_area !=''){
                        $sum_total_area+=$complexContract->BTI_area;
                    }
                    else{
                        $sum_total_area+=$complexContract->total_area;
                    }





                    //$sum_price+= $complexContract->price;
                    $sum_price+= $complexContract->contract_sum;

                    $sheet->setCellValue('A'.$highestRow,$complexContract->owner);
                    $sheet->setCellValue('B'.$highestRow,$complexContract->house_number);
                    $sheet->setCellValue('C'.$highestRow,$complexContract->contract_number);

                   //TODO Расторжение и Не вступил в силу
//                //$sheet->setCellValue('D'.$highestRow,$value->owner);
//                //$sheet->setCellValue('E'.$highestRow,$value->owner);

                    $sheet->setCellValue('F'.$highestRow, Date::dateTimeToExcel(date_create_from_format('Y-m-d',$complexContract->contract_date)));

                    //если тип объекта квартира или кладовка
                    if ($complexContract->type_id == 2 || $complexContract->type_id == 5){
                        $sheet->setCellValue('G'.$highestRow,$complexContract->object_number);
                    }
                    //если тип объекта паркинг или гараж
                    elseif ($complexContract->type_id == 1 || $complexContract->type_id == 7){
                        $sheet->setCellValue('H'.$highestRow,$complexContract->object_number);
                    }
                    //если офис
                    elseif ($complexContract->type_id == 6){
                        $sheet->setCellValue('I'.$highestRow,$complexContract->object_number);
                    }


                    $sheet->setCellValue('J'.$highestRow,$complexContract->floor_number);
                    $sheet->setCellValue('K'.$highestRow,$complexContract->rooms_number);


                    //если есть площадь по БТИ
                    if ($complexContract->BTI_area != null || $complexContract->BTI_area != ''){
                        $sheet->setCellValue('L'.$highestRow,$complexContract->BTI_area);

                        //стоимость кв метра
                        $price_metr = $complexContract->contract_sum/$complexContract->BTI_area;

                    }
                    else{
                        $sheet->setCellValue('L'.$highestRow,$complexContract->total_area);

                        //стоимость кв метра
                        $price_metr = $complexContract->contract_sum/$complexContract->total_area;
                    }



                    $sheet->setCellValue('M'.$highestRow,$price_metr);
                    $sheet->setCellValue('N'.$highestRow,$complexContract->contract_sum);

                    //    Срок оплаты
                    if ($complexContract->contract_type == 1){
                        $sheet->setCellValue('O'.$highestRow,'не позднее 5 рабочих дней после регистрации');
                    }
                    elseif ($complexContract->contract_type == 2){
                        $sheet->setCellValue('O'.$highestRow,'не позднее 2 рабочих дней после регистрации');
                    }
                    //Форма оплаты
                    $sheet->setCellValue('P'.$highestRow,$complexContract->payment_type);


                    //НОВЫЕ ПОЛЯ В них заливать данные из lead_params.installment и lead_params.bank_ipoteka
                    $sheet->setCellValue('Q'.$highestRow,$complexContract->installment);
                    $sheet->setCellValue('R'.$highestRow,$complexContract->bank_ipoteka);


                    //Субсидии
                    $sheet->setCellValue('S'.$highestRow,$complexContract->subsidies);

                    //Тип договора
                    $sheet->setCellValue('T'.$highestRow,$complexContract->contract_name);

                    //Вид недвижимости

                    if ($complexContract->contract_type == 1){
                        $sheet->setCellValue('U'.$highestRow,'Первичка');
                    }
                    elseif ($complexContract->contract_type == 2 || $complexContract->contract_type == 3){
                        $sheet->setCellValue('U'.$highestRow,'Вторичка');
                    }




                    //Имя клиента
                    $sheet->setCellValue('V'.$highestRow,$complexContract->client_name);

                    //Телефон клиента
                    $sheet->setCellValue('W'.$highestRow,$complexContract->client_phone);

                    //Акции
                    $sheet->setCellValue('X'.$highestRow,$complexContract->special_offers);

                    //Менеджер
                    $sheet->setCellValue('Y'.$highestRow,$complexContract->manager);



                    $sheet->getStyle('A10:Y'.$highestRow)->applyFromArray($styleBorderThin);



                    //если расторгнут
                    if($complexContract->stage == 143){
                        $sheet->getStyle('A'.$highestRow.':Y'.$highestRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('72c3f3');

                    };


                    $sheet->getStyle('A'.$highestRow.':Y'.$highestRow)
                        ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);


                    $highestRow++;
                }
                //количество договоров по комплексу
                $sheet->setCellValue('C'.$highestRow, 'ИТОГО: '. $complexContracts->count().'шт.')->getStyle('C'.$highestRow)->applyFromArray($styleBorderThin);
                //площадь по комплексу
                $sheet->setCellValue('L'.$highestRow, 'ИТОГО: '. $sum_total_area)->getStyle('L'.$highestRow)->applyFromArray($styleBorderThin);
                //сумма  по комплексу
                $sheet->setCellValue('N'.$highestRow, 'ИТОГО: ' .number_format($sum_price, 0, '', ' '))->getStyle('N'.$highestRow)->applyFromArray($styleBorderThin);

                $sheet->mergeCells('A'.$highestRow.':B'.$highestRow);
                $sheet->mergeCells('D'.$highestRow.':K'.$highestRow);
                $sheet->mergeCells('O'.$highestRow.':Y'.$highestRow);

                $sheet->getStyle('A'.$highestRow.':Y'.$highestRow)->applyFromArray($styleBorderBoldBottom);
                //dd('A'.$highestRow.':W'.$highestRow);
                $highestRow++;
            }






            $highestRow++;

        }


        $highestRowLast = $sheet->getHighestRow();


        $sheet->getStyle('A4:Y'.$highestRowLast)->applyFromArray($styleBorderThin);


        //заморозить столбцы и строки
        $sheet->freezePane('A4');

        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_contracts.xlsx"');


        $writer->save("php://output");


    }
}
