<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Principal extends Model
{
    protected $table = 'principal_params';
    protected $fillable = ['name','agentcontract_number','agentcontract_date','head_name',
        'head_name_2','adress','requisites'];
}
