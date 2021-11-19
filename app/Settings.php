<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Settings extends Model
{

    protected $table = '__settings';
    protected $fillable = ['interest_rate'];



}
