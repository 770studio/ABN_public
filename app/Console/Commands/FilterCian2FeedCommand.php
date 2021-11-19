<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mockery\Exception;
use Nathanmac\Utilities\Parser\Parser;
use Spatie\ArrayToXml\ArrayToXml;

class FilterCian2FeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cian2filter:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Filter cian feed';

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
        $this->line('start cian2 filter');


        //$url= 'https://pb2635.profitbase.ru/export/cian/f4f69c54110d317a24a34d6254504c3d';
        $url= 'https://pb2635.profitbase.ru/export/profitbase_xml/660312aa59bc7d18fc17b6ea6058f303';

        try{
            $xml = file_get_contents($url);
            $parser = new Parser();
            $parsed = $parser->xml($xml);



            if (isset($parsed['offer'])){

                $this->line('Успешный парсинг фида');

                $objects = collect($parsed['offer']);

                $objectsFiltered = $objects->map(function ($row){

                    switch ($row['object']['name']){
                        case "Мой Ритм":
                        case "Чистое небо":
                        $phoneNumber = '8432225544';
                        break;
                        case "Казань XXI век (II очередь)":
                        case "Казань XXI век":
                        $phoneNumber = '8432225533';
                        break;
                        case "Солнечный город":
                            $phoneNumber = '8432225500';
                            break;
                        case "Нобелевский":
                            $phoneNumber = '8432225000';
                            break;
                        case "Солнечный город СУПЕР":
                            $phoneNumber = '8432957777';
                            break;
                        case "Светлая долина":
                        case "Тулпар":
                        case "Дома у сада":
                        default:
                            $phoneNumber = '8432225522';
                            break;


                    }

                    $result = [

                            'Category'=>'garageSale',
                            'ExternalId'=>$row['@internal-id'],
                            'Description'=>$row['description'],
                            'Address'=>
                                $row['object']['location']['country'].', '.
                                $row['object']['location']['locality-name'].', '.
                                $row['object']['location']['region'].', '.
                                $row['object']['location']['address'],
                            'Phones'=>[
                                'PhoneSchema'=>[
                                    'CountryCode'=>'+7',
                                    'Number'=>$phoneNumber
                                ]
                            ],
                            'Garage'=>[
                                'Type'=>'parkingPlace'
                            ],
                            'TotalArea'=>$row['area']['value'],
                            'BargainTerms'=>[
                                'Price'=>$row['price']['value'],
                                'Currency'=>'rur'
                            ]

                    ];

                    return $result;
                });


                $xmlString = ArrayToXml::convert([
                    'feed_version' =>'2',
                    'object'=>$objectsFiltered->toArray()
                ],
                    [
                    'rootElementName' => 'feed',
                ],
                    true,
                    'UTF-8',
                    '1.0',
                    ['formatOutput' => true]
                );


                $xml  = simplexml_load_string($xmlString);

                $xml->asXML(base_path('public/feeds/cian2.xml'));

                $this->line('Успешное сохранение xml фида');

            }
        }

        catch(Exception $e){
            $this->line($e->getMessage());
        }



    }
}
