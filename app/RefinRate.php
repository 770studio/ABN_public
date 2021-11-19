<?php

namespace App;

use App\Events\RefinRateModifiedEvent;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class RefinRate extends Model


{

    private static $HistoryRates;
    protected $table = 'refin_rate';
    protected $dispatchesEvents = [
        'saved' => RefinRateModifiedEvent::class,
        'deleted' => RefinRateModifiedEvent::class,
        'updated' => RefinRateModifiedEvent::class,
    ];

    static function getRate($date = 'today', $value = false, $nocache = true)
    {
        $dt = new Carbon($date);

        if ($nocache) {
            // dd( $dt->toDateString() );
            $r = self::where('start_date', '<=', $dt->toDateString())->orderBy('start_date', 'desc')->first();

        } else {
            $r = self::getInstance()->where('start_date', '<=', $dt->toDateString())->first();
        }
        // возврат значения либо объекта
        $rate = $value && $r ? $r->rate : $r;
        if (!$rate) {
            throw new Exception('не могу получить ставку рефинансирования на дату:' . $date);
        }
        return $rate;

    }

    static function getInstance()
    {
        if (!self::$HistoryRates) {
            self::$HistoryRates = self::orderBy('start_date', 'desc')->get();
        }
        return self::$HistoryRates;
    }

    static function getNearestRateChangeDate($date)
    {

        return self::getInstance()->where('start_date', '>', $date)->min('start_date');

    }

    static function getCurrentRate()
    {

        return self::orderBy('id', 'desc')->first();

    }

    public static function getHistory()
    {
        return self::
        join('users', 'users.id', 'refin_rate.employee_id')
            ->when(request()->sortBy && in_array(request()->sortBy, ['id', 'user_name', 'rate', 'updated_at', 'created_at', 'start_date']),
                function ($q) {
                    return $q->orderBy(request()->sortBy, request()->sortDesc ? 'desc' : 'asc');
                })
            ->select(['refin_rate.*', 'users.name as user_name'])
            ->get();

        /*            ->paginate(request()->perPage, $columns = ['refin_rate.*', 'users.name as user_name'], $pageName = 'page',
                        request()->currentPage ) ;*/


    }

    public function User()
    {

        return $this->hasOne('App\User', 'id', 'employee_id');
    }

    public function contracts()
    {
        return Instalment::OfPercentPenaltyType();

    }

}
