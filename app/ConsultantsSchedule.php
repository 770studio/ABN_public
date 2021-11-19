<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConsultantsSchedule extends Model
{
   protected $table = 'consultants_schedule';
   protected $guarded = [];

    public function flag(){
        return $this->hasOne(
            ConsultantsScheduleFlag::class,
            "abn_user_id",
            'abn_user_id'
        )->first();
    }
}
