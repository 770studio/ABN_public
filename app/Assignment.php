<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'cession_ofrights';
    protected $fillable = ['assignor','cessionary','date','base','comments','lead_id'];


    public function Lead()
    {
        return $this->belongsTo('App\Lead', 'lead_id', 'lead_id');
    }


}
