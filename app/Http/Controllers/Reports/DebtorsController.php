<?php

namespace App\Http\Controllers\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Instalment;
class DebtorsController extends Controller
{
    public function index(){
        return view('reports.debtors.index');
    }

    public function makeReport(Request $request){


//        //запрос
//        $data = DB::table('lead_params')
//            ->select(DB::raw(
//                "
//                    contacts.name,
//                    lead_params.contract_number,
//                    lead_params.contract_date,
//                    object_params.address,
//                    lead_params.payment_type,
//                    MAX(payment_shedule.payment_date) as last_payment_date,
//                    lead_params.contract_sum,
//                    SUM(DISTINCT(IncomPays.sum)) as income_sum,
//                    penalty.overdue_days,
//                    penalty.overdue_sum,
//                    penalty.penalty_sum,
//                    object_params.owner,
//                    MAX(IncomPays.incomDate) as last_income_date,
//
//                    contacts.address as client_address,
//                    contacts.phone
//
//                "))
//            ->join('IncomPays','IncomPays.contractNumber','=','lead_params.contract_number')
//
//            //->join('instalments','instalments.lead_id','=','lead_params.lead_id')
//            ->join('payment_shedule','payment_shedule.lead_id','=','lead_params.lead_id')
//
//            ->join('object_params','object_params.object_id','=','lead_params.object_id')
//            ->join('contacts','contacts.contact_id','=','lead_params.client_id')
//
//            ->leftJoin('penalty','penalty.lead_id','=','lead_params.lead_id')
//
//            ->groupBy('contract_number');



        $data = DB::table('penalty')
            ->select(DB::raw(
                "

                  penalty.overdue_days,
                  penalty.overdue_sum,
                  penalty.penalty_sum,
                  penalty.lead_id,
                  penalty.status as penalty_status


                "))
//            ->join('lead_params','lead_params.lead_id','=','penalty.lead_id')
//            ->join('contacts','contacts.contact_id','=','lead_params.client_id')
//            ->join('object_params','object_params.object_id','=','lead_params.object_id')

            ->where('overdue_days','>',0)
            ->whereIn('penalty.status',[0,2])

            ->orderBy('penalty.overdue_days','DESC')

        ;



             //фильтр
            if ($request->get('late_pay')){
                $days =$request->get('late_pay');
                $data = $data->where('penalty.overdue_days','>=',$days);
            }


            $data = $data->get();



        //создаем новый  эксель
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //название листа
        $sheet->setTitle('Должники');

        //вставка лого
//        $drawing = new Drawing();
//        $drawing->setName('Logo');
//        $drawing->setDescription('Logo');
//        $drawing->setPath(public_path('/img/abn-logo.png'));
//        $drawing->setHeight(90);
//        $drawing->setCoordinates('A1');
//        $drawing->setWorksheet($sheet);


        //заголовок
        $sheet->setCellValue('A1', 'Информация по клиентам, задерживающих оплату ежемесячного платежа сроком более '.$days.' дней.')->getStyle("A1")->getFont()->setSize(16);
        $sheet->mergeCells('A1:F1');
        //Период

        $now = Carbon::now()->format('d.m.Y');
        $sheet->setCellValue('A2', 'Дата: ' . $now )->getStyle("A2")->getFont()->setSize(16);
        $sheet->mergeCells('A2:F2');

        //шапка таблицы
        $sheet->setCellValue('A4','№ п/п');
        $sheet->setCellValue('B4','ФИО, № договора, дата');
        $sheet->setCellValue('C4','Адрес приобретаемого объекта недвижимости');
        $sheet->setCellValue('D4','Форма оплаты');
        $sheet->setCellValue('E4','Дата окончательного платежа');
        $sheet->setCellValue('F4','Сумма по договору, руб.');
        $sheet->setCellValue('G4','Поступило денежных средств по договору, руб.');
        $sheet->setCellValue('H4','Задолженность, руб.');
        $sheet->setCellValue('I4','Дней просрочки');
        $sheet->setCellValue('J4','Сумма просрочки, руб.');
        $sheet->setCellValue('K4','Пени, руб.');
        $sheet->setCellValue('L4','Собственник');
        $sheet->setCellValue('M4','Дата последнего поступления оплаты');
        $sheet->setCellValue('N4','Сумма рассрочки согласно графика оплаты');
        $sheet->setCellValue('O4','Примечание от сектора Управления учета и отчетности ПЭО');
        $sheet->setCellValue('P4','Примечание от службы экономической безопасности АБН');
        $sheet->setCellValue('Q4','Примечание от Юридического отдела АБН');
        $sheet->setCellValue('R4','Адрес регистрации покупателя');
        $sheet->setCellValue('S4','Тел.');


        //ширина столбцов
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('R')->setWidth(25);
        $sheet->getColumnDimension('S')->setWidth(15);


        //динамические данные

        $highestRow = 5;

        $num = 1;

        foreach ($data as $leadIDData){

                //получаем данные по lead id
            $dataByLeadId = DB::table('lead_params')
                ->select(DB::raw(
                    "
                    lead_params.lead_id,
                    contacts.name,
                    lead_params.contract_number,
                    lead_params.contract_date,
                    lead_params.contract_sum,
                    object_params.address,
                    lead_params.payment_type,
                    object_params.owner,
                    contacts.address as client_address,
                    contacts.phone
                "))

           ->leftJoin('contacts','contacts.contact_id','=','lead_params.client_id')
           ->join('object_params','object_params.object_id','=','lead_params.object_id')
           ->where('lead_params.lead_id',$leadIDData->lead_id)
           ->first()
            ;




            //дата окончательного платежа
            $last_payment_date = DB::table('payment_shedule')
                ->select(DB::raw(
                    "
                    MAX(payment_shedule.payment_date) as last_payment_date
                "))
                ->where('payment_shedule.lead_id',$leadIDData->lead_id)
                ->first()->last_payment_date
            ;

            //поступления
            $incomeData = DB::table('IncomPays')
                ->select(DB::raw(
                    "
                     SUM(IncomPays.sum) as income_sum,
                     MAX(IncomPays.incomDate) as last_income_date
                "))
                ->where('IncomPays.lead_id',$leadIDData->lead_id)
                ->first();

            //поступило денежных средств по договру
            $income_sum = $incomeData->income_sum;

            $sheet->setCellValue('A'.$highestRow,$num);
            //задолженность

            if (isset($dataByLeadId)){
                $debt = $dataByLeadId->contract_sum - $income_sum;

                if(isset($dataByLeadId->contract_date)){
                    $sheet->setCellValue('B'.$highestRow,$dataByLeadId->name .', '.$dataByLeadId->contract_number.' от  '. Carbon::createFromFormat('Y-m-d', $dataByLeadId->contract_date)->format('d.m.Y'));

                }
                else{
                    $sheet->setCellValue('B'.$highestRow,$dataByLeadId->name .', '.$dataByLeadId->contract_number);
                }

                $sheet->setCellValue('C'.$highestRow,$dataByLeadId->address);
                $sheet->setCellValue('D'.$highestRow,$dataByLeadId->payment_type);
                $sheet->setCellValue('F'.$highestRow,$dataByLeadId->contract_sum);
                $sheet->getStyle('F'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->setCellValue('H'.$highestRow,$debt);
                $sheet->getStyle('H'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
                $sheet->setCellValue('L'.$highestRow,$dataByLeadId->owner);
                $sheet->setCellValue('R'.$highestRow,$dataByLeadId->client_address);
                $sheet->setCellValue('S'.$highestRow,$dataByLeadId->phone);
            }


            if (isset($last_payment_date)){
                $sheet->setCellValue('E'.$highestRow,Carbon::createFromFormat('Y-m-d',$last_payment_date)->format('d.m.Y'));

            }

            if (isset($incomeData) ||$incomeData == null ){

                $sheet->setCellValue('G'.$highestRow,$incomeData->income_sum);
                $sheet->getStyle('G'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');

                if(isset($incomeData->last_income_date)){
                    $sheet->setCellValue('M'.$highestRow,Carbon::createFromFormat('Y-m-d', $incomeData->last_income_date)->format('d.m.Y'));

                }
            }


            //дней просрочки
            $overdue_days = $leadIDData->overdue_days;
            //сумма просрочки
            $overdue_sum = $leadIDData->overdue_sum;
            //пени
            $penalty_sum = $leadIDData->penalty_sum;

            $sheet->setCellValue('I'.$highestRow,$overdue_days);
            $sheet->setCellValue('J'.$highestRow,$overdue_sum);
            $sheet->setCellValue('K'.$highestRow,$penalty_sum);




            //Сумма рассрочки согласно графика оплаты
            $instalmentsTotalSum =  $instalment = Instalment::find($leadIDData->lead_id);

            $sheet->setCellValue('N'.$highestRow,$instalmentsTotalSum->total_sum);
            $sheet->getStyle('N'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');





            $highestRow++;
            $num++;
        }

//        foreach ($data as $item){
////            dd($item);
//            $sheet->setCellValue('A'.$highestRow,$num);
//            $sheet->setCellValue('B'.$highestRow,$item->name .', '.$item->contract_number.' от  '. Carbon::createFromFormat('Y-m-d', $item->contract_date)->format('d.m.Y'));
//            $sheet->setCellValue('C'.$highestRow,$item->address);
//            $sheet->setCellValue('D'.$highestRow,$item->payment_type);
//            $sheet->setCellValue('E'.$highestRow,Carbon::createFromFormat('Y-m-d', $item->last_payment_date)->format('d.m.Y'));
//            $sheet->setCellValue('F'.$highestRow,$item->contract_sum);
//            $sheet->getStyle('F'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
//            $sheet->setCellValue('G'.$highestRow,$item->income_sum);
//            $sheet->getStyle('G'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
//            $sheet->setCellValue('H'.$highestRow,$item->contract_sum-$item->income_sum);
//            $sheet->setCellValue('I'.$highestRow,$item->overdue_days);
//            $sheet->setCellValue('J'.$highestRow,$item->overdue_sum);
//            $sheet->setCellValue('K'.$highestRow,$item->penalty_sum);
//            $sheet->setCellValue('L'.$highestRow,$item->owner);
//            $sheet->setCellValue('M'.$highestRow,Carbon::createFromFormat('Y-m-d', $item->last_income_date)->format('d.m.Y'));
//
//            $sheet->setCellValue('R'.$highestRow,$item->client_address);
//            $sheet->setCellValue('S'.$highestRow,$item->phone);
//
//            $highestRow++;
//            $num++;
//        }
//
//
        //итого
        $sheet->setCellValue('A'.$highestRow,'Итого:');
        $sheet->mergeCells('A'.$highestRow.':B'.$highestRow);


        $totalRow = $highestRow-1;
        $sheet->setCellValue('F'.$highestRow,'=SUM(F11:F'.$totalRow.')')->getStyle('F'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->setCellValue('G'.$highestRow,'=SUM(G11:G'.$totalRow.')')->getStyle('G'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->setCellValue('H'.$highestRow,'=SUM(H11:H'.$totalRow.')')->getStyle('H'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->setCellValue('J'.$highestRow,'=SUM(J11:J'.$totalRow.')')->getStyle('J'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->setCellValue('K'.$highestRow,'=SUM(K11:K'.$totalRow.')')->getStyle('K'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');
        $sheet->setCellValue('N'.$highestRow,'=SUM(N11:N'.$totalRow.')')->getStyle('N'.$highestRow)->getNumberFormat()->setFormatCode('### ### ### ###');



        //стиль
        $style = [
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
                'size'  => 10,

            ]

        ];


        $sheet->getRowDimension('4')->setRowHeight(70);
        $sheet->getStyle('A4:S'.$highestRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A4:S'.$highestRow)->applyFromArray($style);



        //Сформировать файл и скачать
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="abn_report_debtors.xlsx"');
        $writer->save("php://output");

    }
}
