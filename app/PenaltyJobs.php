<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;




class PenaltyJobs extends Model
{

    const DONE_FINE = '1';
    const DONE_QUEUE = '2'; // в очереди
    const DONE_NO_FINE = '3'; // без долга

    protected $table = '__penalty_jobs';
    /*
     * Done:
     0 - не выполнялось,
     1- штраф начислен,
     2 - поставлено в очередь на обработку,
     3 - нет долга,
     */



}
