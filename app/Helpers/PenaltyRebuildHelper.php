<?php


namespace App\Helpers;


use App\Instalment;
use Carbon\Carbon;

class PenaltyRebuildHelper
{
    public static function getContractCacheKey(Instalment $contract, $status = 'queued') : string
    {
        return CacheHelper::getContractCacheKey( $contract,
            'PenaltyContractRebuildJob_' . $status)  ;
    }

    public static function allocateRebuildTime() : Carbon
    {// завтра, с 12 ночи до 5 утра
        return Carbon::tomorrow()->addMinutes(rand(0,300));
    }

}
