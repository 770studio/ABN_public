<?php


namespace App\Helpers;


use App\Instalment;
use Illuminate\Database\Eloquent\Model;

class CacheHelper
{
    public static function getContractCacheKey(Instalment $contract, $store = 'default' ) : string
    {
        return  self::getModelCacheKey($contract, $store  ) ;
    }

    public static function getModelCacheKey(Model $entity, $store = 'default' ) : string
    {
        return   $store . '-' . $entity->getKey();
    }
}
