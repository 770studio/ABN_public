<?php

namespace App\ProfitBase;

#TODO логирование и эксепшны

use Exception;

use App\House;
use App\ZHK;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ProfitBase
{
     protected $access_token;
     //protected $API_ENDPOINT = "https://pb10227.profitbase.ru/api/v4/json/";
     protected $API_ENDPOINT = "https://pb2635.profitbase.ru/api/v4/json/";
     //protected $pb_api_key = "app-5ee9c7f4d2cb0";
     protected $pb_api_key = "app-5ee881e19a9ca";

     function __construct()
     {

         $this->access_token = $this->authenicate();

     }




     // ЖК
    function getProjects()
    {




        return    $this->apiRequest( 'projects'  )   ;

    }

    function updateProjects()
    {


        $projects = $this->getProjects()   ;
        #TODO try exception если проблема с получением
        try {

            MassInsert::run(  $projects, 'zhk_params' , ['title', 'type', 'pb_id', 'region', 'district' , 'locality', 'developer', 'developer_brand', 'banks', 'currency'],
            'pb_id', ['id' => 'pb_id']);


            } catch (Exception $e) {
                dump ( $e->getMessage() );

            }

            dump(   'комплексы обновлены.' );
        Log::channel('pbase')->info('комплексы обновлены');

    }



    function getHouses()
    {

        $response = $this->apiRequest( 'house'  ) ;
       // $c = collect($response);
     #TODO   if($response->success) exception
        return $response->data;



    }

    function getHouseFloors( $houseId )
    {
        // этажи берутся по одному дому, во избежаннии абуза здесь слип
        sleep(1);
        if(!$houseId) throw new Exception('нет houseId' );
        $response = $this->apiRequest( 'floor' , 'GET', '', ['houseId' => $houseId ] ) ;
        return $response;



    }

    function deleteHouseFloors($house_id) {

        DB::table('house_floors')->where('house_id', $house_id )->delete();
    }
    function updateHouseFloors()
    {
        // взять все id домов
        $hIds = DB::table('house_params')->select('pb_id' ) ->get()->pluck('pb_id');

        foreach($hIds as $houseId) {
            //этажи закидываем по каждому дому , всех домов ждать не будем
            $data = [];
            $floors = $this->getHouseFloors( $houseId );


            if($floors) {

              //  $dbFloors =  DB::table('house_floors')->where('house_id', $houseId )->get(); // этажи дома из бд до апдейта
                $dbFloorsNotToBeDeleted = [];

                // в pb много этажей лишних, нужен только последний вариант этажа
                foreach(collect($floors)->groupBy('number') as $__floors ) {
                    $floor = $__floors->where('id',  $__floors->max('id') )->first() ;

                    $dbFloorsNotToBeDeleted[] = $floor->id  ;



                    $data[] = [
                        'house_id' => $houseId,
                        'pb_id' => $floor->id,
                        'number' => $floor->number,
                        'image_source' => @$floor->images->source,
                        'image_preview' => @$floor->images->preview,
                        'image_big' => @$floor->images->big,
                        'areas' => json_encode( @$floor->areas ) ,
                        'rooms_total' => (int)count(@$floor->areas) ,
                    ];
                }

                MassInsert::run(  $data, 'house_floors' ,  null ,
                    'pd_id' );


                if($dbFloorsNotToBeDeleted) {
                    $dbFloorsNotToBeDeleted = array_unique($dbFloorsNotToBeDeleted);
                    Log::channel('pbase')->info('инсерт прошел успешно, удалим старые этажи: ' . $houseId . ' whereNotIn:' . implode( ',' , $dbFloorsNotToBeDeleted ) );

                    DB::table('house_floors')
                        ->where('house_id', $houseId )
                        ->whereNotIn('pb_id', $dbFloorsNotToBeDeleted )
                        ->delete();
                }

            }

        }

        dump(  ' этажи обновлены.' );
        Log::channel('pbase')->info('этажи обновлены');

    }


    function updateHouses()
    {




            try {

                #TODO try exception если проблема с получением
                $houses = $this->getHouses() ;

                //  houses придктся переработать
                $data = [];
                foreach($houses as $house) {
                    $data[] = [
                        'pb_id' => $house->id,
                        'projectId' => $house->projectId,
                        'projectName' => $house->projectName,
                        'title' => $house->title,
                        'street' => $house->street,
                        'number' => $house->number,
                        'facing' => $house->facing,
                        'material' => $house->material,
                        'buildingState' => $house->buildingState,
                        'developmentStartQuarterYear' => @$house->developmentStartQuarter->year,
                        'developmentStartQuarterQuarter' => @$house->developmentStartQuarter->quarter,
                        'developmentEndQuarterYear' => @$house->developmentEndQuarter->year,
                        'developmentEndQuarterQuarter' => @$house->developmentEndQuarter->quarter,
                        'salesStartMonth' => @$house->salesStart->month,
                        'salesStartYear' => @$house->salesStart->year,
                        'salesEndMonth' => @$house->salesEnd->month,
                        'salesEndYear' => @$house->salesEnd->year,
                        'type' => $house->type,
                        'image' => $house->image,
                        'minFloor' => $house->minFloor,
                        'maxFloor' => $house->maxFloor,
                        'minPrice' => $house->minPrice,
                        'minPriceArea' => $house->minPriceArea,
                        'countFilteredProperty' => $house->countFilteredProperty,
                        'currency_code' => @$house->currency->code,
                        'address_full' => @$house->address->full,
                    ];
                }
                unset($houses);
                MassInsert::run(  $data, 'house_params' ,  null ,
                    'pd_id' );


            } catch (Exception $e) {
                dump ( $e->getMessage() );

            }

            dump(  ' дома обновлены.' );
        Log::channel('pbase')->info('дома обновлены');



    }



    function authenicate() {

         $json = json_encode( [
             "type"=> "api-app",
             "credentials"=>  [
                 "pb_api_key" => $this->pb_api_key
             ],
         ] );
         //dd($json);
        $response = $this->apiRequest( 'authentication', 'POST',   $json ) ;

        #TODO exception
        return $response->access_token;

    }

     function apiRequest( $method, $http_method = 'GET', $body = '', $params = []  ) {

         $url = $this->API_ENDPOINT . $method;
         // добавить к запросу access_token
         if($method != 'authentication' && $http_method == 'GET') {
              $url .= '?access_token=' .  $this->access_token;
              if($params) $url .= '&' . http_build_query($params);

         }

         $curl = curl_init();
         curl_setopt_array($curl, array(
             CURLOPT_URL => $url,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING => "",
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_TIMEOUT => 0,
             CURLOPT_FOLLOWLOCATION => true,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_SSL_VERIFYHOST => 0,
             CURLOPT_SSL_VERIFYPEER => 0,
             CURLOPT_CUSTOMREQUEST => $http_method,
             CURLOPT_POSTFIELDS => $body,
             CURLOPT_HTTPHEADER => array(
                 "Content-Type: application/json"
             ),
         ));





         $response = curl_exec($curl);  // dump($response);
         curl_close($curl);
         return json_decode($response) ;


     }


}
