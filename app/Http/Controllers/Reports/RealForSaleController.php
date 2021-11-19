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
class RealForSaleController extends Controller
{
    public function index(){
        return view('reports.realForSale.index');
    }

    public function makeReport(Request $request){

        //период
//        if ($request->get('dateRange')){
//
//            $date = $request->get('dateRange');
//            $dateArr = explode('-',$date);
//            $dateFrom = str_replace(' ', '', $dateArr['0']);
//            $dateTo = str_replace(' ', '', $dateArr['1']);
//
//            $df = Carbon::createFromFormat('d.m.Y', $dateFrom);
//            $dt = Carbon::createFromFormat('d.m.Y', $dateTo  );
//
//
//        }
//        else{
//            return redirect()->back()->with('status','Не выбран период');
//        }


        $data = DB::table('object_params')
            ->select(DB::raw(
                "
                  house_DATE.DATE as house_date,
                   object_types.type_name as object_type,
                   object_params.house_number,
                   lead_params.contract_number,
                   lead_params.contract_date,
                   object_params.object_number ,
                   object_params.floor_number,
                   object_params.rooms_number,
                   object_params.total_area,
                   lead_params.contract_sum,
                   contacts.name as client_name,
                   lead_params.filing_date,
                   lead_params.ownership_transfer_date,
                   lead_contract_type.contract_name,
                   object_params.owner,
                   object_params.category_id,
                   object_params.entrance_number,
                   object_params.status_id,
                   object_status_directory.status_name
                    
                "))
            //->where('building_stage','=','Сдан в эксплуатацию')
            ->join('object_types','object_types.type_id','=','object_params.type_id')
            ->join('lead_params','lead_params.object_id','=','object_params.object_id')
            ->join('house_DATE','house_DATE.house_number','=','object_params.house_number')
            ->join('object_status_directory','object_status_directory.id','=','object_params.status_id')
            ->leftJoin('contacts','contacts.contact_id','=','lead_params.client_id')
            ->leftJoin('lead_contract_type','lead_contract_type.contract_type_id','=','lead_params.contract_type')
//            ->where('house_DATE.DATE','>=',$df)
//            ->where('house_DATE.DATE','<=',$dt)
            ->orderBy('object_params.house_number','asc');



        if ($request->get('real_type') != null && $request->get('real_type')!='' ){
            $real_type =$request->get('real_type');

            $real_typeArr = explode(',',$real_type);

            $data = $data->whereIn('object_params.category_id',$real_typeArr);
        }

        if ($request->get('akt') != null){

            if($request->get('akt') == '1'){
                $data = $data->whereNotNull('lead_params.filing_date');
            }
            elseif($request->get('akt') == '0'){
                $data = $data->whereNull('lead_params.filing_date');
            }
        }


        //статус

        if ($request->get('status') != null){

            $status = $request->get('status');

            if ($status != '0'){
                $data = $data->where('object_params.status_id',$status);
            }

        }

        $data = $data->get()->groupBy('object_type');



        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет по жилым и нежилым');

//        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);


        //заголовок
        $sheet->setCellValue('A1', 'Отчет по жилым и нежилым объектам')->getStyle("A1")->getFont()->setSize(16);

        $now = Carbon::now()->format('d.m.Y');


        //Период
        $sheet->setCellValue('B1', 'Отчетный период: ' .$now )->getStyle("B1")->getFont()->setSize(16);

        //авторазмер
        foreach(range('A','O') as $columnID) {
            $sheet->getColumnDimension($columnID)
                ->setAutoSize(true);
        }
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

        //формат даты
//        $spreadsheet->getActiveSheet()->getStyle('A')
//            ->getNumberFormat()
//            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY
//            );
        $spreadsheet->getActiveSheet()->getStyle('C')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY
            );
        $spreadsheet->getActiveSheet()->getStyle('L')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY
            );

        //фомат цифровой у сумм
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('### ### ### ###');


        //dd($data);

        //начало динамических данных
        $highestRow = 3;
        foreach ($data as $object_type =>$valuesArr){


            $highestRow = $highestRow+1;
            $sheet->setCellValue('A'.$highestRow,$object_type)->getStyle('A'.$highestRow)->getFont()->setBold(true);
//            $highestRow = $highestRow+1;
//
//            $sheet->setCellValue('A'.$highestRow,'Сдан: ');
//            $highestRow = $highestRow+1;
//            $sheet->setCellValue('A'.$highestRow, Date::dateTimeToExcel(date_create_from_format('Y-m-d',$valuesArr->first()->house_date)));
           $sheet->getStyle('A'.$highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);




            $highestRow = $highestRow+2;

            //шапка таблицы

            $sheet->setCellValue('A'.$highestRow, '№ дома');
            $sheet->setCellValue('B'.$highestRow, '№ договора');
            $sheet->setCellValue('C'.$highestRow, 'Дата договора');
            $sheet->setCellValue('D'.$highestRow, '№  квартиры');
            $sheet->setCellValue('E'.$highestRow, 'Этаж');
            $sheet->setCellValue('F'.$highestRow, 'Кол-во комнат');
            $sheet->setCellValue('G'.$highestRow, '№ подъезда');
            $sheet->setCellValue('H'.$highestRow, 'Общая площадь(кв.м.)');
            $sheet->setCellValue('I'.$highestRow, 'Площадь по ЖК(кв.м.)');
            $sheet->setCellValue('J'.$highestRow, 'Сумма по договору');
            $sheet->setCellValue('K'.$highestRow, 'ФИО');
            $sheet->setCellValue('L'.$highestRow, 'Дата подписания акта приема- передачи');
            $sheet->setCellValue('M'.$highestRow, 'Первоначальная форма договора');
            $sheet->setCellValue('N'.$highestRow, 'Собственник');
            $sheet->setCellValue('O'.$highestRow, 'Примечание');

            $sheet->getStyle('A'.$highestRow.':O'.$highestRow)->applyFromArray($styleBorderThin);

            $highestRow = $highestRow+1;

            $sumTotalArea = 0;
            $sumContractSum = 0;
            foreach ($valuesArr as $contractItem){

                $sumTotalArea+= $contractItem->total_area;
                $sumContractSum +=$contractItem->contract_sum;


                $sheet->setCellValue('A'.$highestRow,$contractItem->house_number);
                $sheet->setCellValue('B'.$highestRow, $contractItem->contract_number);

                if ($contractItem->contract_date != null && $contractItem->contract_date!='0000-00-00'){
                    $sheet->setCellValue('C'.$highestRow, Date::dateTimeToExcel(date_create_from_format('Y-m-d',$contractItem->contract_date)));
                }

                $sheet->setCellValue('D'.$highestRow, $contractItem->object_number);
                $sheet->setCellValue('E'.$highestRow, $contractItem->floor_number);
                $sheet->setCellValue('F'.$highestRow, $contractItem->rooms_number);
               
                $sheet->setCellValue('G'.$highestRow, $contractItem->entrance_number);

                $sheet->setCellValue('H'.$highestRow, $contractItem->total_area);

                //TODO Площадь по ЖК
                $sheet->setCellValue('I'.$highestRow, '');



                $sheet->setCellValue('J'.$highestRow, $contractItem->contract_sum);
                $sheet->setCellValue('K'.$highestRow, $contractItem->client_name);
                if ($contractItem->ownership_transfer_date != null) {
                    $sheet->setCellValue('L' . $highestRow, Date::dateTimeToExcel(date_create_from_format('Y-m-d', $contractItem->ownership_transfer_date)));
                }
                $sheet->setCellValue('M'.$highestRow, $contractItem->contract_name);
                $sheet->setCellValue('N'.$highestRow, $contractItem->owner);
                $sheet->setCellValue('O'.$highestRow, '');

                $sheet->getStyle('A'.$highestRow.':O'.$highestRow)->applyFromArray($styleBorderThin);

                $highestRow++;
            }


            $sheet->setCellValue('A'.$highestRow,"Итого")->getStyle('A'.$highestRow)->getFont()->setBold(true);
            $sheet->setCellValue('D'.$highestRow,$valuesArr->count())->getStyle('D'.$highestRow)->getFont()->setBold(true);

            $sheet->setCellValue('H'.$highestRow,$sumTotalArea)->getStyle('H'.$highestRow)->getFont()->setBold(true);
            $sheet->setCellValue('J'.$highestRow,$sumContractSum)->getStyle('J'.$highestRow)->getFont()->setBold(true);

            $sheet->getStyle('A'.$highestRow.':O'.$highestRow)->applyFromArray($styleBorderThin);


            $highestRow++;
        }








        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_real_for_sale.xlsx"');
        $writer->save("php://output");
    }
}
