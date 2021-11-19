<?php

namespace App;

use App\Exceptions\CriticalException;
use http\Env\Request;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;



class ScheduleHistory extends Model
{

    protected $table = 'payment_schedule_history';

    protected $fillable = ['event', 'dump', 'lead_id',
        'bailout', 'employee_id',
    ];

   //  protected $appends = ['items'];

/*
    public function getPaymentDue( $due_date  ) {

dd($due_date);

    }*/

    public function Lead()
    {
        return $this->belongsTo('App\Lead', 'lead_id', 'lead_id');
    }
    public function Instalment()
    {
        return $this->belongsTo('App\Instalment', 'lead_id', 'lead_id');
    }
    public function User()
    {

        return $this->hasOne('App\User', 'id', 'employee_id');
    }

/*    public function getItemsAttribute()
    {
        //$this->attributes
        if($this->dump && $data = unserialize($this->dump) ) {

            return $data;
        }

        return null;
    }*/
    public function getDumpAttribute() {
//dd( unserialize( $this->attributes["dump"]  ) );
        if( $this->attributes["dump"] && $data = unserialize( $this->attributes["dump"] ) ) {

            return $data;
        }
        return null;
    }
}
