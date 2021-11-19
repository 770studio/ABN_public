<?php

namespace App;

use App\Exceptions\CriticalException;
use Illuminate\Database\Eloquent\Model;


class PenaltyCorrection extends Model
{


    public function Penalty()
    {
        return $this->belongsTo('App\Penalty', 'id', 'penalty_id');
    }

}
