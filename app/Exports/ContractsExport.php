<?php

namespace App\Exports;


use App\User;

use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class ContractsExport implements FromCollection,WithHeadings,WithColumnFormatting,WithDrawings,WithEvents,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */

    use Exportable;

    private $headings;

    public function __construct()
    {

        $users = User::all()->groupBy('name');

        foreach ($users as $name=>$user){

            $this->headings = [
                [''],[''],[''],[''],[''],
                ['Отчет по реестру договоров'],

                ['Период: ' .$name ],
                [
                    '№ дома',
                    '№ договора',
                    'Собственник',
                    'Расторжение',
                    'Не вступил в силу',
                    'Дата',
                    '№ кв.',
                    'Паркинг',
                    'Офис',
                    'Этаж',
                    'Кол-во комнат',
                    'Общая площадь по СНиП, кв.м.',
                    'Стоимость кв.м.',
                    'Общая стоимость, руб.',
                    'Срок оплаты',
                    'Форма оплаты',
                    'Субсидии',
                    'Тип договора',
                    'Вид недвижимости',
                    'Ф.И.О.',
                    'Телефон клиента',
                    'Акции',
                    'Менеджер'

                ],



            ];

        }




    }
    public function collection()  {

        $users = User::all()->groupBy('name');

       // dd($users);

        foreach ($users as $key=>$value){
            return $value;
        }
        //return  $users = User::all()->groupBy('name');
    }

//    public function map($data): array
//    {
//
//
//        return [
//
//            $data['item']['param1'],
//            $data['item']['param2'],
//            $data['item']['param3'],
//        ];
//
//    }



    public function headings(): array
    {

       // dd($this->headings[8]);
        return
            $this->headings
        ;



    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath(public_path('/img/abn-logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');
        return $drawing;
    }

    public function registerEvents(): array
    {

        //стили таблицы
        $styleTitle = [
            'font'=>[
                'bold'=>true,
                'size'=>'16'

            ]
        ];

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

        $styleYellowFill = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => [
                    'rgb' => 'FFFF00',
                ],

            ],
        ];

        $styleBordersMonth = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
             ]
        ];


        return [
            BeforeSheet::class =>  function(BeforeSheet $event) use($styleTitle,$styleBorder,$styleYellowFill,$styleBordersMonth){
                $event->sheet->getDelegate()->setTitle('Отчет по реестру договоров');
                $event->sheet->getStyle('A6')->applyFromArray($styleTitle);//стиль заголовка
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(10);//ширна колонки
               // $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(20);//ширина колонки
                $event->sheet->getDefaultColumnDimension()->setWidth(15);
                $event->sheet->getStyle('A8:W8')->applyFromArray($styleBorder);//внешняя и внутрення обводка шапки
                $event->sheet->getStyle('D8:E8')->applyFromArray($styleYellowFill);//заливка желтым цветом
                $event->sheet->getStyle('A8:W8')->getAlignment()->setWrapText(true);//перенос слов
                //$event->sheet->getStyle('A9:W9')->applyFromArray($styleBordersMonth);//обводка месяцев
                $event->sheet->mergeCells('A9:W9');//объединить ячейки
                $event->sheet->mergeCells('A6:W6');//объединить ячейки
                //$event->sheet->freezePane('A1');//активный курсор



            },
            AfterSheet::class => function(AfterSheet $event) {

                $highestRow = $event->sheet->getHighestRow();
                $event->sheet->mergeCells('A'.strval($highestRow+1).':W'.strval($highestRow+1));//объединить ячейки


                return  $users = User::all()->groupBy('name');

            },
        ];
    }
}
