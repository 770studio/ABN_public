<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class PenaltyDaily extends Model
{

    protected $table = 'penalty_daily';
    protected $fillable = ['paid', 'paid_at'];
    protected static $dueCache = [];

    public static $Store;

    public function Instalment()
    {

        return $this->belongsTo('App\Instalment', 'lead_id', 'lead_id');
    }
    public function Penalty()
    {
        return $this->belongsTo('App\Penalty', 'penalty_id', 'id');
    }
    public function PenaltyNoCorrection()
    {
        return $this->belongsTo('App\PenaltyNoCorrection', 'penalty_nc_id', 'id');
    }
    public static function Add2Store(PenaltyDaily $pd) {

        if(!self::$Store) self::$Store = new Collection();
        self::$Store->push($pd);



    }

    public function scopeOnDate($q, $lead_id, $date )
    {
/*        if(!self::$dueCache)  self::$dueCache = new Collection();
        if( $date > self::$dueCache->max('date')  ) {
            // нет в кэше
            self::$dueCache['date'] =
            $q->where('lead_id', $lead_id)
                ->where('date', $date)
                ->get();


        }*/


    }



}
