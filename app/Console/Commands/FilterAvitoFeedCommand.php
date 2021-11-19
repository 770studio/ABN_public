<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Mockery\Exception;
use Nathanmac\Utilities\Parser\Parser;
use Spatie\ArrayToXml\ArrayToXml;

class FilterAvitoFeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avitofilter:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Filter avito feed';

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
        $this->line('start avito filter');

        $ids = [
            7175245,
            7175483,
            7175603,
            7175121,
            7175566,
            7175259,
            7175479,
            7175507,
            7175598,
            7175598,
            7175422,
            7175434,
            7175287,
            7175412,
            7175411,
            5451675,
            5451585,
            5451589,
            5451771,
            5451570,
            5451717,
            5451681,
            5451509,
            5451530,
            5451600,
            5451569,
            5451539,
            5451544,
            5451674,
            5451525,
            5296906,
            5297096,
            5297821,
            5296905,
            5297390,
            5297236,
            5297256,
            5297137,
            5297160,
            5297910,
            5297904,
            5297874,
            5297130,
            5297167,
            5297232
        ];

        $url = 'https://pb2635.profitbase.ru/export/avito/d5649829c2c4a73f886ee1e1650f8f93';


        try{
            $xml = file_get_contents($url);
            $parser = new Parser();
            $parsed = $parser->xml($xml);

            if (isset($parsed['Ad'])){

               $this->line('Успешный парсинг фида');

               $objects = collect($parsed['Ad']);


               $objectsFiltered = $objects->whereIn('Id',$ids);

               if($objectsFiltered->count()){

                   $this->line('Успешная фильтрация объектов. Найдено: '.$objectsFiltered->count());

                   $objectsFilteredArr = [];
                   foreach($objectsFiltered->toArray() as $object){
                       $imagesArr = [];
                       foreach($object as $key=>$value){

                           if ($key == 'Images'){

                               if (is_array($value)){
                                  foreach ($value as $imageArr){
                                      foreach ($imageArr as $image){
                                          $imagesArr['Image'][] = ['_attributes' => ['url' => $image['@url']]];
                                         //array_push($imagesArr,$img);

                                      }
                                  }
                               }

                           }

                       }

                       unset($object['Images']);
                       $object['Images'][] = $imagesArr;

                       array_push($objectsFilteredArr,$object);
                   }

                  // dd($objectsFilteredArr[59]);

                   $xmlString = ArrayToXml::convert(['Ad'=>$objectsFilteredArr], [
                       'rootElementName' => 'Ads',
                       '_attributes' => [
                           'formatVersion' => '3',
                           'target'=>'Avito.ru'
                       ],
                   ],
                       true,
                       'UTF-8',
                       '1.0',
                       ['formatOutput' => true]
                   );


                   $xml  = simplexml_load_string($xmlString);

                   $xml->asXML(base_path('public/feeds/avito.xml'));

                   $this->line('Успешное сохранение xml фида');

                }
            }
        }
        catch(Exception $e){
            $this->line($e->getMessage());
        }



    }
}
