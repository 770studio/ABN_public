<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConsultantsScheduleFlag extends Model
{
   protected $table = 'consultants_schedule_flags';
   protected $guarded = [];

   public $timestamps = false;
   public function abnUserFlags(){
       return $this->belongsTo(
           ConsultantsSchedule::class,
           'abn_user_id',
           'abn_user_id'
       );
   }
}
