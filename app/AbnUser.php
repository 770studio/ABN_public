<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AbnUser extends Model
{
    protected $table='abned_users';
    protected $guarded=[];

    public function getSchedule(){
        return $this->hasMany('App\ConsultantsSchedule','abn_user_id');
    }

    public function getScheduleItem($day,$month,$year){

        if(!$this->getSchedule->isEmpty()){
            $schedule =  $this->getSchedule->where('day',$day)
            ->where('month',$month)
            ->where('year',$year)->first();
            if ($schedule){
                return $schedule->schedule;
            }

        }
        return null;
    }
}
