<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Mockery\Exception;
use Nathanmac\Utilities\Parser\Parser;
use Spatie\ArrayToXml\ArrayToXml;

class FilterCianFeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cianfilter:start';

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
        $this->line('start cian filter');


        $url = 'https://pb2635.profitbase.ru/export/cian/f4f69c54110d317a24a34d6254504c3d';


        try{
            $xml = file_get_contents($url);
            $parser = new Parser();
            $parsed = $parser->xml($xml);



            if (isset($parsed['object'])){

               $this->line('Успешный парсинг фида');

               $objects = collect($parsed['object']);

               $objectsFiltered = $objects->map(function ($row){
                   if($row['JKSchema']['Name'] === 'Мой ритм'){
                        if ($row['FlatRoomsCount'] === '2' || $row['FlatRoomsCount'] === '3'){

                            $row['BargainTerms']['ActionId'] = '15772';

                            $this->line('Добавили ActionId объекту: '.$row['ExternalId']);

                       }
                   }
                    return $row;
                });


                  $xmlString = ArrayToXml::convert(['feed_version' =>'2','object'=>$objectsFiltered->toArray()], [
                       'rootElementName' => 'feed',
                   ],
                       true,
                       'UTF-8',
                       '1.0',
                       ['formatOutput' => true]
                   );


                   $xml  = simplexml_load_string($xmlString);

                   $xml->asXML(base_path('public/feeds/cian.xml'));

                   $this->line('Успешное сохранение xml фида');

                }
            }

        catch(Exception $e){
            $this->line($e->getMessage());
        }



    }
}
