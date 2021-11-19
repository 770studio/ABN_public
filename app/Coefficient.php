<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coefficient extends Model
{
    protected $table = 'coefficients';
    protected $fillable = ['coefficient','contracts_count'];
}
