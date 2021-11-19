<?php

namespace App;

use App\Exceptions\CriticalException;
use App\Helpers\PenaltyRebuildHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


class Instalment extends Model
{
    #TODO использовать везде
    #TODO перенести в пеналти?
    public const PENALTY_TYPE_FIXED = 1; // фикс. в абс. величине
    public const PENALTY_TYPE_PERCENT360 = 2; // одна трехсотшестьдесятая ставки
    public const PENALTY_TYPE_PERCENT300 = 3; // одна трехсотая ставки
    public const PENALTY_TYPE_PERCENT = 4; // произвольный процент без привязки к ставке
    public const PENALTY_TYPE_RATE_DEPENDANT = [2,3]; // произвольный процент без привязки к ставке

    protected $primaryKey = 'lead_id';
    protected $table = 'instalments';
    protected $guarded = ['id', 'created_at', 'updated_at', 'schedule_created', 'schedule_created_at'];
    protected $fillable = ['lead_id', 'initial_payment_sum', 'initial_payment_date', 'first_payment_date',
        'inst_prs', 'period', 'payments_count', 'add_cost', 'total_sum', 'instalment_sum', 'penalty_value', 'penalty_type', 'bailout',
        'comments', 'ttl_area', 'nds'
    ];

    public function Lead()
    {
        return $this->belongsTo('App\Lead', 'lead_id', 'lead_id');
    }

    public function Schedule()
    {
        return $this->hasMany('App\Schedule', 'lead_id', 'lead_id');
    }

    public function Penalty()
    {
        return $this->hasMany('App\Penalty', 'lead_id', 'lead_id');
    }

    public static function exists($lead_id)
    {

        $Instalment = self::where('lead_id', $lead_id)->get();

        if (!$Instalment->isEmpty()) {
            if ($Instalment->count() > 1) throw new CriticalException(' более одной рассрочки на сделку lead_id:' . $lead_id);
            return $Instalment->first();
        }

        return false;
    }


    public function _____update($data)
    {
        $this->fill($data);
        return $this->save();
    }

    public function scopeOfPercentPenaltyType($q)
    {
       return $q->has('Schedule')
                ->whereIn( 'penalty_type', self::PENALTY_TYPE_RATE_DEPENDANT)
                ->cursor();
    }

    public function PenaltyHasJustBeenSuccessfullyRebuilt()
    {
        //установим кэш до конца дня чтоб клиент мог иметь в виду, что сегодня был полный перерасчет
        Cache::put(
            PenaltyRebuildHelper::getContractCacheKey($this, 'rebuilt'),
            (string)now(),
            Carbon::tomorrow()
        );
    }

    public function PenaltyHasBeenRebuiltToday()
    {
       return Cache::get(
            PenaltyRebuildHelper::getContractCacheKey($this, 'rebuilt')
        );
    }

}
