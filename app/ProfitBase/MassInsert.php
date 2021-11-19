<?php

namespace App\ProfitBase;

use Illuminate\Support\Facades\DB;

class MassInsert
{
    static $items;
    static $limit = 1000;

    function __construct()
    {  EXIT('static');
        // self:$pdo = DB::connection()->getPdo();
    }
    static function run( &$data , $table,  $fields = null , $unique_key = null, $replace = []  )
    {

        $dbh = DB::connection()->getPdo();

        if(!$fields) { // получить поля из $data

            $fields = array_keys(current($data));
        }
        self::$items =   array_chunk(  $data, self::$limit  ) ;
        unset($data); // освободить память

        #TODO logging , exceptions

        foreach(self::$items as $chunkKey => $chunk) {
            $query = "INSERT  INTO {$table} (" . implode(',' , $fields ) . ") VALUES "; //Prequery
            $q = array_fill(0, count($fields), "?");
            $qPart = array_fill(0, count($chunk), "(" . implode(",",$q) . ")");
            $query .=  implode(",",$qPart);
            if($unique_key) {
                $odku = [];
                foreach($fields as $field) { if($field == $unique_key) continue; // апдейт всех полей, кроме ключа
                    $odku[] = "{$field}=VALUES({$field})";
                }

                $query .=  " ON DUPLICATE KEY UPDATE " . implode(', ', $odku)  ;
            }

            $stmt = $dbh -> prepare($query);

            $i = 1;

            foreach($chunk as $n => $item ) { //bind the values one by one

                foreach($fields as $fieldName ) {
                    $item = (array)$item; // объект хдесь по идее

                    if($replace && in_array($fieldName, $replace ) ) $fieldName =  array_search($fieldName, $replace ) ;
                    $stmt->bindParam($i++,  $item[$fieldName]  );
                 }

            }
          //  unset(self::$items[$chunkKey]);

            $stmt -> execute();
            //dd($stmt->debugDumpParams());


        }



    }


}
