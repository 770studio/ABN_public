<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
class OwnersController extends Controller
{
    public function index(){

        //получаем комплексы
        $complexes = DB::table('object_params')
            ->whereNotNull('complex')
            ->orderBy('complex','asc')
            ->distinct()
            ->pluck('complex');

        foreach ($complexes as $complex){
            $complexesArr[$complex] = $complex;
        }


        return view('reports.owners.index',[
            'complexes'=>$complexesArr
        ]);
    }

    public function getAddressList(Request $request){
        $complex_address = DB::table('object_params')
            ->whereNotNull('complex')
            ->whereNotNull('house_number')
            ->where('complex','=',$request->complex)
            ->orderBy('house_number','asc')
            ->distinct()
            ->pluck('house_number');


        foreach ($complex_address as $address){
            $complexesAddressArr[$address] = $address;
        }

        return response()->json($complexesAddressArr);
    }

    public function makeReport(Request $request){
        if ($request->get('complex')){

            $data = DB::table('object_params')
                ->select(DB::raw(
                    "
                 object_params.complex,
                 object_params.house_number,
                 object_params.object_number,
                 object_params.total_area,
                 lead_params.contract_number,
                 lead_params.contract_date,
                 contacts.name as client_name,          
                 contacts.phone as client_phone,          
                 contacts.address as client_address,          
                 abned_users.user_name as manager          

                "))
                ->whereNotNull('complex')
                ->whereNotNull('house_number')
                ->where('complex','=',$request->complex)
                ->join('lead_params','lead_params.object_id','=','object_params.object_id')
                ->join('links_report_portal','links_report_portal.lead_id','=','lead_params.lead_id')
                ->join('contacts','contacts.contact_id','=','links_report_portal.contact_id')
                ->join('abned_users','abned_users.id','=','lead_params.employee_id')
                ->orderBy('complex','asc');

             if ($request->get('complex_address')){
                 $data = $data->whereIn('house_number',$request->complex_address);
             }




        }
        else{
            return redirect()->back()->with('status','Не выбран комплекс!');
        }

        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Отчет по выработке менеджеров');

        //вставка лого
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath(public_path('/img/abn-logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);

        //заголовок
        $sheet->setCellValue('A7', 'Отчет по по собственникам')->getStyle("A7")->getFont()->setSize(16);
        $sheet->mergeCells('A7:M7');

        //шапка таблицы
        $sheet->setCellValue('A9', '№');
        $sheet->setCellValue('B9', 'Жилой комплекс');
        $sheet->setCellValue('C9', 'Номер дома');
        $sheet->setCellValue('D9', '№ квартиры');
        $sheet->setCellValue('E9', 'Общая площадь по СНиП');
        $sheet->setCellValue('F9', 'Общая площадь по ЖК');
        $sheet->setCellValue('G9', '№ договора');
        $sheet->setCellValue('H9', 'Дата договора');
        $sheet->setCellValue('I9', 'Переуступка (дата)');
        $sheet->setCellValue('J9', 'ФИО');
        $sheet->setCellValue('K9', 'Адрес');
        $sheet->setCellValue('L9', 'Телефон');
        $sheet->setCellValue('M9', 'Менеджер');


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

        $sheet->getColumnDimension('A')->setWidth(10);
        //авторазмер
        foreach(range('B','M') as $columnID) {
            $sheet->getColumnDimension($columnID)
                ->setAutoSize(true);
        }

        $sheet->getStyle('A9:M9')->applyFromArray($styleBorderThin);


        //динамические данные
        $highestRow = 10;
        $num = 1;
        foreach ($data->get() as $complex){

            $sheet->setCellValue('A'.$highestRow,$num);
            $sheet->setCellValue('B'.$highestRow,$complex->complex);
            $sheet->setCellValue('C'.$highestRow,$complex->house_number);
            $sheet->setCellValue('D'.$highestRow,$complex->object_number);
            $sheet->setCellValue('E'.$highestRow,$complex->total_area);
            $sheet->setCellValue('F'.$highestRow,'?');
            $sheet->setCellValue('G'.$highestRow,$complex->contract_number);
            $sheet->setCellValue('H'.$highestRow,Carbon::createFromFormat('Y-m-d', $complex->contract_date)->format('d.m.Y'));
            $sheet->setCellValue('I'.$highestRow,'?');
            $sheet->setCellValue('J'.$highestRow,$complex->client_name);
            $sheet->setCellValue('K'.$highestRow,$complex->client_phone);
            $sheet->setCellValue('L'.$highestRow,$complex->client_address);
            $sheet->setCellValue('M'.$highestRow,$complex->manager);

            $sheet->getStyle('A9:M'.$highestRow)->applyFromArray($styleBorderThin);
            $sheet->getStyle('A' . $highestRow)
                ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $num++;
            $highestRow++;

        }


        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_owners.xlsx"');
        $writer->save("php://output");
    }
}
