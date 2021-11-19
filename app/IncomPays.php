<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class IncomPays extends Model
{

    protected $table = 'IncomPays';
    protected $primaryKey = 'incomp_id';
    protected $fillable = ['processed2', 'processed3',
        'id', 'processed', 'processDate', 'customerINN', 'customerName', 'orgINN',
        'docDate', 'incomNumber', 'incomDate', 'sum', 'sumDoc', 'contractNumber',
        'contractDate', 'payPurpose', 'lead_id', 'payment_target', 'processed2', 'processed3',

    ];

    public static function collectByLeadId($lead_id): Collection
    {
        return DB::table('IncomPays')
            ->where('lead_id', $lead_id)
            ->where(function ($q) {
                $q->whereNotIn('payment_target', Penalty::$penalty_payment_target) // все кроме штрафов
                ->orWhereNull('payment_target');
            })
            ->orderBy('incomDate')
            ->get();

    }

    public function Lead()
    {
        return $this->belongsTo('App\Lead', 'lead_id', 'lead_id');
    }

    public function Penalty()
    {
        return $this->hasMany('App\Penalty', 'lead_id', 'lead_id');
    }

    public function PenaltyNoCorrection()
    {
        return $this->hasMany('App\PenaltyNoCorrection', 'lead_id', 'lead_id');
    }

    public function PaymentSchedule()
    {
        return $this->hasMany('App\Schedule', 'lead_id', 'lead_id');
    }


    public function PenaltyDaily()
    {
        return $this->hasMany('App\PenaltyDaily', 'lead_id', 'lead_id');
    }

    /*
     *  @return все платежи для $lead_id кроме штрафов
     */

    public function isPenaltyPayment()
    {
        return in_array($this->payment_target, Penalty::$penalty_payment_target);
    }

    /*
        //формат даты для view сторонние платежи
        public function getDocDateAttribute($val){
            return Carbon::parse($val)->format('d.m.Y');
        }
        public function getIncomDateAttribute($val){
            return Carbon::parse($val)->format('d.m.Y');
        }
        public function getContractDateAttribute($val){
            return Carbon::parse($val)->format('d.m.Y');
        }*/

}
