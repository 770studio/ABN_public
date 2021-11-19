<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Mockery\Exception;
use Nathanmac\Utilities\Parser\Parser;
use Spatie\ArrayToXml\ArrayToXml;

class Filter2GisFeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '2gisfilter:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Filter 2gis feed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('start 2gis filter');
        $url = 'https://pb2635.profitbase.ru/export/profitbase_xml/c6fd71928a09d59e2219f67f324a76d7';

        try{
            $xml = file_get_contents($url);
            $parser = new Parser();
            $parsed = $parser->xml($xml);



            if (isset($parsed['offer'])){

                $this->line('Успешный парсинг фида');

                $offers = collect($parsed['offer']);




                $offersFiltered = $offers->map(function ($row){

                   if (isset($row['image'][0]['#text'])){
                       $picture = $row['image'][0]['#text'];
                   }
                   else{
                       $picture = null;
                   }

                   $result = [

                       'categoryId'=>1,
                       'price'=>$row['price']['value'],
                       'name'=>'ЖК '.$row['object']['name'].' '.$row['rooms'] . ' комнатная',
                       'picture'=>$picture,
                       'description'=>'Квартира № '.$row['number'].', дом № '.$row['house']['name'].', Комнат-'.$row['rooms'] .', Площадь-'.$row['area']['value'],
                       '_attributes'=>[
                           'id'=>$row['object']['id'],
                           'available'=>'true'
                       ]
                   ];

                    return $result;
                });


                $xmlString = ArrayToXml::convert(
                    [

                    'shop'=>[
                        'name'=>'Ак Барс Дом',
                        'company'=>'Застройщик',
                        'categories'=>[
                                'category'=>[
                                     '_value' => 'Квартиры',
                                     '_attributes' => ['id'=>'1']
                                ]


                        ],
                        'offers'=>[
                            'offer'=>[

                                $offersFiltered->toArray()

                            ]
                        ]
                      ]

                    ],
                    [
                    'rootElementName' => 'yml_catalog',
                    '_attributes' => [
                            'date' => Carbon::now()->toDateTimeString(),
                        ]
                     ],
                    false,

                    'UTF-8',
                    '1.0',
                    ['formatOutput' => true,

                    ]


                );

                $xmlStringWithDoctype = str_replace('?>','?>'.PHP_EOL.'<!DOCTYPE yml_catalog SYSTEM "shops.dtd">',$xmlString);

                $xml  = simplexml_load_string($xmlStringWithDoctype);

                $xml->asXML(base_path('public/feeds/2gis.xml'));

                $this->line('Успешное сохранение xml фида');

            }
        }

        catch(Exception $e){
            $this->line($e->getMessage());
        }
    }
}
