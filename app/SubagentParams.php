<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubagentParams extends Model
{
    protected $table = 'subagent_params';
    protected $fillable = [
        'name',
        'sub_contract_number',
        'sub_contract_date',
        'head_name',
        'head_name_2',
        'base_of_rules',
        'adress',
        'inn',
        'bank_name',
        'bik',
        'rs',
        'ks',
        'kpp',
        'ogrn'
    ];
}
