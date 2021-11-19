<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nathanmac\Utilities\Parser\Parser;
use Spatie\ArrayToXml\ArrayToXml;

class FilterAvito2FeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avito2filter:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Filter avito 2 feed';

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
        $this->line('start avito 2 filter');
        $url = 'https://pb2635.profitbase.ru/export/profitbase_xml/660312aa59bc7d18fc17b6ea6058f303';


        try {
            $xml = file_get_contents($url);
            $parser = new Parser();
            $parsed = $parser->xml($xml);

            if (isset($parsed['offer'])) {

                $this->line('Успешный парсинг фида');

                $offers = collect($parsed['offer']);

                $offersFiltered = $offers->map(function ($row) {


                      $result = [
                        'ID' => $row['@internal-id'],
                        'AllowEmail' => 'Да',
                        'ManagerName' => 'Отдел продаж',
                        'ContactPhone' => '+7 843 295 77 77',
                        'Category' => 'Гаражи и машиноместа',
                        'OperationType' => 'Продам',
                        'PropertyRights'=>'Собственник',
                        'ObjectType' => 'Машиноместо',
                        'ObjectSubtype' => 'Многоуровневый паркинг',
                        'Secured' => 'Нет',
                        'Address' =>
                            $row['object']['location']['country'].', '.
                            $row['object']['location']['locality-name'].', '.
                            $row['object']['location']['region'].', '.
                           // $row['object']['location']['district'].', '.
                            $row['object']['location']['address']
                          ,
                          'Square'=>$row['area']['value'],
                          'Description'=>'Продаётся машиноместо '.Str::random(20),
                          'Price'=>$row['price']['value'],
                          'AdStatus'=>'Free',

                    ];

                    return $result;
                });
                $xmlString = ArrayToXml::convert([
                     'Ad' => $offersFiltered->toArray()],
                    [
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


                $xml = simplexml_load_string($xmlString);

                $xml->asXML(base_path('public/feeds/avito2.xml'));

                $this->line('Успешное сохранение xml фида');
            } else {
                $this->line('Ошибка парсинга');
            }
        } catch (\Exception $e) {
            $this->line($e->getMessage());
        }
    }
}
